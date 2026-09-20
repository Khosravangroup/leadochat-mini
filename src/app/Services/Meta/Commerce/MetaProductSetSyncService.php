<?php

namespace App\Services\Meta\Commerce;

use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\ProviderConnection;
use App\Support\ProviderSecretRedactor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaProductSetSyncService
{
    public function sync(CatalogProductSet $productSet): array
    {
        $productSet->loadMissing(['metaCatalog.providerConnection', 'products']);

        $metaCatalog = $productSet->metaCatalog;
        $connection = $metaCatalog?->providerConnection;

        if (! $metaCatalog || ! $connection || $connection->provider !== 'instagram') {
            throw new RuntimeException('Product set must belong to a Meta catalog connected to an Instagram account.');
        }

        $externalCatalogId = trim((string) $metaCatalog->external_catalog_id);

        if ($externalCatalogId === '') {
            throw new RuntimeException('Meta catalog does not have an external catalog id.');
        }

        $products = $productSet->products;

        if ($products->isEmpty()) {
            throw new RuntimeException('Product set does not contain any products to sync.');
        }

        $retailerIds = $products
            ->map(fn (CatalogProduct $product) => $this->retailerIdForProduct($product, $externalCatalogId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($retailerIds === []) {
            throw new RuntimeException('Product set products are missing Meta retailer ids for this catalog.');
        }

        $externalProductSetId = trim((string) ($productSet->external_product_set_id ?? ''));
        $payload = [
            'name' => trim((string) $productSet->name),
            'filter' => json_encode([
                'retailer_id' => [
                    'is_any' => $retailerIds,
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        if (filled($productSet->description)) {
            $payload['description'] = trim((string) $productSet->description);
        }

        $result = $externalProductSetId === ''
            ? $this->createProductSet($connection, $externalCatalogId, $payload)
            : $this->updateProductSet($connection, $externalProductSetId, $payload);

        $now = now();
        $syncStatus = ($result['ok'] ?? false) ? 'synced' : 'failed';
        $syncError = ($result['ok'] ?? false) ? null : ($result['error'] ?? 'Meta product set sync failed.');
        $resolvedExternalProductSetId = (string) ($result['external_id'] ?? $externalProductSetId);
        $meta = is_array($productSet->meta) ? $productSet->meta : [];
        $meta['last_meta_sync'] = [
            'status' => $syncStatus,
            'synced_at' => $now->toIso8601String(),
            'external_catalog_id' => $externalCatalogId,
            'external_product_set_id' => $resolvedExternalProductSetId !== '' ? $resolvedExternalProductSetId : null,
            'retailer_ids' => $retailerIds,
            'result' => $result,
        ];

        $productSet->update([
            'external_product_set_id' => $resolvedExternalProductSetId !== '' ? $resolvedExternalProductSetId : null,
            'meta_sync_status' => $syncStatus,
            'meta_sync_error' => $syncError,
            'meta_synced_at' => $now,
            'meta' => $meta,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => $syncStatus,
            'external_product_set_id' => $resolvedExternalProductSetId !== '' ? $resolvedExternalProductSetId : null,
            'retailer_ids' => $retailerIds,
            'result' => $result,
        ];
    }

    protected function createProductSet(ProviderConnection $connection, string $externalCatalogId, array $payload): array
    {
        $endpoint = "https://graph.facebook.com/{$this->resolveGraphVersion()}/{$externalCatalogId}/product_sets";
        $accessToken = $this->resolveAccessToken($connection);
        $requestPayload = array_merge($payload, [
            'access_token' => $accessToken,
        ]);
        $safePayload = ProviderSecretRedactor::payload($requestPayload, [$accessToken]);

        if (app()->environment('local')) {
            return [
                'ok' => true,
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => $safePayload,
                'external_id' => 'local-debug-meta-product-set-'.now()->timestamp,
                'body' => [
                    'id' => 'local-debug-meta-product-set-'.now()->timestamp,
                ],
            ];
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($endpoint, $requestPayload);

        $json = $response->json();
        $body = ProviderSecretRedactor::payload(
            is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)],
            [$accessToken]
        );

        return [
            'ok' => $response->successful(),
            'mode' => 'live',
            'endpoint' => $endpoint,
            'payload' => $safePayload,
            'status' => $response->status(),
            'external_id' => (string) (Arr::get($body, 'id') ?? ''),
            'error' => $response->successful() ? null : ProviderSecretRedactor::text(
                (string) Arr::get($body, 'error.message', $response->body()),
                [$accessToken]
            ),
            'body' => $body,
        ];
    }

    protected function updateProductSet(ProviderConnection $connection, string $externalProductSetId, array $payload): array
    {
        $endpoint = "https://graph.facebook.com/{$this->resolveGraphVersion()}/{$externalProductSetId}";
        $accessToken = $this->resolveAccessToken($connection);
        $requestPayload = array_merge($payload, [
            'access_token' => $accessToken,
        ]);
        $safePayload = ProviderSecretRedactor::payload($requestPayload, [$accessToken]);

        if (app()->environment('local')) {
            return [
                'ok' => true,
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => $safePayload,
                'external_id' => $externalProductSetId,
                'body' => [
                    'success' => true,
                    'id' => $externalProductSetId,
                ],
            ];
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($endpoint, $requestPayload);

        $json = $response->json();
        $body = ProviderSecretRedactor::payload(
            is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)],
            [$accessToken]
        );

        return [
            'ok' => $response->successful(),
            'mode' => 'live',
            'endpoint' => $endpoint,
            'payload' => $safePayload,
            'status' => $response->status(),
            'external_id' => $externalProductSetId,
            'error' => $response->successful() ? null : ProviderSecretRedactor::text(
                (string) Arr::get($body, 'error.message', $response->body()),
                [$accessToken]
            ),
            'body' => $body,
        ];
    }

    protected function retailerIdForProduct(CatalogProduct $product, string $externalCatalogId): ?string
    {
        $metadata = is_array($product->metadata) ? $product->metadata : [];
        $catalogSync = Arr::get($metadata, "meta_catalog_sync.{$externalCatalogId}", []);
        $retailerId = trim((string) ($catalogSync['retailer_id'] ?? $product->external_product_id ?? $product->sku ?? ''));

        return $retailerId !== '' ? $retailerId : null;
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
