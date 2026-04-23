<?php

namespace App\Services\Meta\Commerce;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\CatalogProductOffer;
use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaCatalogProductSyncService
{
    public function syncProducts(Catalog $sourceCatalog, Catalog $targetCatalog): array
    {
        $connection = $targetCatalog->providerConnection;

        if (! $connection || $connection->provider !== 'instagram') {
            throw new RuntimeException('Target Meta catalog must belong to an Instagram provider connection.');
        }

        $externalCatalogId = trim((string) $targetCatalog->external_catalog_id);

        if ($externalCatalogId === '') {
            throw new RuntimeException('Target Meta catalog does not have an external catalog id.');
        }

        $products = $sourceCatalog->products()
            ->where('is_active', true)
            ->with('offers')
            ->orderBy('title')
            ->get();

        if ($products->isEmpty()) {
            throw new RuntimeException('Source catalog does not have any active products to sync.');
        }

        $requests = $products
            ->map(fn (CatalogProduct $product) => $this->buildProductRequest($product))
            ->values()
            ->all();

        $result = $this->sendBatch($connection, $externalCatalogId, $requests);
        $now = now();
        $syncStatus = ($result['ok'] ?? false) ? 'queued' : 'failed';
        $syncError = ($result['ok'] ?? false) ? null : ($result['error'] ?? 'Meta catalog batch sync failed.');

        foreach ($products as $product) {
            $retailerId = $this->retailerIdForProduct($product);
            $metadata = is_array($product->metadata) ? $product->metadata : [];
            $metadata['meta_catalog_sync'][$externalCatalogId] = [
                'target_catalog_id' => $targetCatalog->id,
                'external_catalog_id' => $externalCatalogId,
                'retailer_id' => $retailerId,
                'status' => $syncStatus,
                'synced_at' => $now->toIso8601String(),
                'batch_handle' => $result['handle'] ?? null,
                'error' => $syncError,
            ];

            $product->update([
                'external_product_id' => $product->external_product_id ?: $retailerId,
                'meta_sync_status' => $syncStatus,
                'meta_sync_error' => $syncError,
                'meta_synced_at' => $now,
                'metadata' => $metadata,
            ]);
        }

        $targetMeta = is_array($targetCatalog->meta) ? $targetCatalog->meta : [];
        $targetMeta['last_product_sync'] = [
            'source_catalog_id' => $sourceCatalog->id,
            'source_catalog_name' => $sourceCatalog->name,
            'product_count' => $products->count(),
            'status' => $syncStatus,
            'synced_at' => $now->toIso8601String(),
            'batch_handle' => $result['handle'] ?? null,
            'result' => $result,
        ];

        $targetCatalog->update([
            'meta_sync_status' => $syncStatus,
            'meta_sync_error' => $syncError,
            'last_synced_at' => $now,
            'meta_synced_at' => $now,
            'meta' => $targetMeta,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => $syncStatus,
            'product_count' => $products->count(),
            'target_catalog_id' => $targetCatalog->id,
            'external_catalog_id' => $externalCatalogId,
            'source_catalog_id' => $sourceCatalog->id,
            'requests' => $requests,
            'result' => $result,
        ];
    }

    protected function sendBatch(ProviderConnection $connection, string $externalCatalogId, array $requests): array
    {
        $endpoint = "https://graph.facebook.com/{$this->resolveGraphVersion()}/{$externalCatalogId}/batch";
        $payload = [
            'requests' => json_encode($requests, JSON_THROW_ON_ERROR),
        ];

        if (app()->environment('local')) {
            return [
                'ok' => true,
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => $payload,
                'handle' => 'local-debug-meta-catalog-batch-' . now()->timestamp,
                'body' => [
                    'handles' => ['local-debug-meta-catalog-batch-' . now()->timestamp],
                ],
            ];
        }

        $response = Http::withToken($this->resolveAccessToken($connection))
            ->asForm()
            ->acceptJson()
            ->post($endpoint, $payload);

        $json = $response->json();
        $body = is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)];

        return [
            'ok' => $response->successful(),
            'mode' => 'live',
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status' => $response->status(),
            'handle' => Arr::get($body, 'handles.0') ?? Arr::get($body, 'handle'),
            'error' => $response->successful() ? null : Arr::get($body, 'error.message', $response->body()),
            'body' => $body,
        ];
    }

    protected function buildProductRequest(CatalogProduct $product): array
    {
        return [
            'method' => 'UPDATE',
            'retailer_id' => $this->retailerIdForProduct($product),
            'data' => $this->buildProductData($product),
        ];
    }

    protected function buildProductData(CatalogProduct $product): array
    {
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $brand = trim((string) ($product->brand ?: Arr::get($metadata, 'brand', config('app.name', 'Leadochat'))));
        $condition = trim((string) ($product->product_condition ?: Arr::get($metadata, 'condition', 'new')));
        $inventory = $product->inventory_quantity ?? Arr::get($metadata, 'inventory');
        $activeOffer = $this->resolveActiveBaseOffer($product);
        $resolvedSalePrice = $activeOffer?->resolvedSalePrice($product->price !== null ? (float) $product->price : null)
            ?? ($product->sale_price !== null ? (float) $product->sale_price : null);

        return array_filter([
            'name' => mb_substr(trim((string) $product->title), 0, 200),
            'description' => mb_substr(trim((string) ($product->description ?: $product->title)), 0, 5000),
            'availability' => $this->mapAvailability((string) $product->availability),
            'condition' => $condition !== '' ? $condition : 'new',
            'price' => $this->formatMetaPrice($product),
            'currency' => strtoupper((string) ($product->currency ?: 'USD')),
            'url' => trim((string) $product->product_url),
            'image_url' => trim((string) $product->image_url),
            'brand' => $brand !== '' ? $brand : 'Leadochat',
            'inventory' => is_numeric($inventory) ? (int) $inventory : null,
            'sale_price' => $resolvedSalePrice !== null ? $this->formatMetaPrice($product, $resolvedSalePrice) : null,
            'google_product_category' => filled($product->google_product_category) ? trim((string) $product->google_product_category) : null,
            'content_language' => filled($product->content_language) ? trim((string) $product->content_language) : null,
            'target_country' => filled($product->target_country) ? strtoupper(trim((string) $product->target_country)) : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function resolveActiveBaseOffer(CatalogProduct $product): ?CatalogProductOffer
    {
        $now = now();

        return $product->offers
            ->whereNull('catalog_product_market_override_id')
            ->filter(fn (CatalogProductOffer $offer) => $offer->isActiveNow($now))
            ->sortBy(fn (CatalogProductOffer $offer) => sprintf('%04d-%010d', (int) $offer->priority, 9999999999 - (int) $offer->id))
            ->first();
    }

    protected function retailerIdForProduct(CatalogProduct $product): string
    {
        $candidate = trim((string) ($product->sku ?: $product->external_product_id ?: 'leadochat-product-' . $product->id));
        $candidate = preg_replace('/[^A-Za-z0-9._:-]+/', '-', $candidate) ?: 'leadochat-product-' . $product->id;

        return mb_substr($candidate, 0, 100);
    }

    protected function mapAvailability(string $availability): string
    {
        return match ($availability) {
            'out_of_stock' => 'out of stock',
            'preorder' => 'preorder',
            default => 'in stock',
        };
    }

    protected function formatMetaPrice(CatalogProduct $product, ?float $amount = null): string
    {
        $amount = $amount ?? ($product->price !== null ? (float) $product->price : 0);

        return number_format($amount, 2, '.', '') . ' ' . strtoupper((string) ($product->currency ?: 'USD'));
    }

    protected function resolveAccessToken(ProviderConnection $connection): string
    {
        $token = $connection->oauthTokens()
            ->where('token_type', 'access_token')
            ->where('is_primary', true)
            ->latest('id')
            ->first();

        $accessToken = (string) ($token?->access_token ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('No primary Meta access token found for this connection.');
        }

        return $accessToken;
    }

    protected function resolveGraphVersion(): string
    {
        return (string) config('services.meta.graph_version', config('services.instagram.graph_version', 'v25.0'));
    }
}
