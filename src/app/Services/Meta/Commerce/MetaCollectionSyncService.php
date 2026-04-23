<?php

namespace App\Services\Meta\Commerce;

use App\Models\CatalogCollection;
use App\Models\CatalogProductSet;
use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaCollectionSyncService
{
    public function __construct(
        protected MetaProductSetSyncService $productSetSyncService
    ) {
    }

    public function sync(CatalogCollection $collection): array
    {
        $collection->loadMissing(['metaCatalog.providerConnection', 'productSets']);

        $metaCatalog = $collection->metaCatalog;
        $connection = $metaCatalog?->providerConnection;

        if (! $metaCatalog || ! $connection || $connection->provider !== 'instagram') {
            throw new RuntimeException('Collection must belong to a Meta catalog connected to an Instagram account.');
        }

        $externalCatalogId = trim((string) $metaCatalog->external_catalog_id);

        if ($externalCatalogId === '') {
            throw new RuntimeException('Meta catalog does not have an external catalog id.');
        }

        $productSets = $collection->productSets;

        if ($productSets->isEmpty()) {
            throw new RuntimeException('Collection does not contain any product sets to sync.');
        }

        $syncedProductSets = [];

        foreach ($productSets as $productSet) {
            $externalProductSetId = trim((string) ($productSet->external_product_set_id ?? ''));

            if ($externalProductSetId === '') {
                $syncResult = $this->productSetSyncService->sync($productSet);
                $externalProductSetId = trim((string) ($syncResult['external_product_set_id'] ?? ''));
            }

            if ($externalProductSetId === '') {
                throw new RuntimeException('Collection product sets must be synced before the collection can be pushed to Meta.');
            }

            $syncedProductSets[] = [
                'id' => $productSet->id,
                'external_product_set_id' => $externalProductSetId,
            ];
        }

        $externalCollectionId = trim((string) ($collection->external_collection_id ?? ''));
        $payload = [
            'name' => trim((string) $collection->name),
            'product_sets' => json_encode(array_column($syncedProductSets, 'external_product_set_id'), JSON_THROW_ON_ERROR),
        ];

        if (filled($collection->description)) {
            $payload['description'] = trim((string) $collection->description);
        }

        $result = $externalCollectionId === ''
            ? $this->createCollection($connection, $externalCatalogId, $payload)
            : $this->updateCollection($connection, $externalCollectionId, $payload);

        $now = now();
        $syncStatus = ($result['ok'] ?? false) ? 'synced' : 'failed';
        $syncError = ($result['ok'] ?? false) ? null : ($result['error'] ?? 'Meta collection sync failed.');
        $resolvedExternalCollectionId = (string) ($result['external_id'] ?? $externalCollectionId);
        $meta = is_array($collection->meta) ? $collection->meta : [];
        $meta['last_meta_sync'] = [
            'status' => $syncStatus,
            'synced_at' => $now->toIso8601String(),
            'external_catalog_id' => $externalCatalogId,
            'external_collection_id' => $resolvedExternalCollectionId !== '' ? $resolvedExternalCollectionId : null,
            'product_sets' => $syncedProductSets,
            'result' => $result,
        ];

        $collection->update([
            'external_collection_id' => $resolvedExternalCollectionId !== '' ? $resolvedExternalCollectionId : null,
            'meta_sync_status' => $syncStatus,
            'meta_sync_error' => $syncError,
            'meta_synced_at' => $now,
            'meta' => $meta,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => $syncStatus,
            'external_collection_id' => $resolvedExternalCollectionId !== '' ? $resolvedExternalCollectionId : null,
            'product_sets' => $syncedProductSets,
            'result' => $result,
        ];
    }

    protected function createCollection(ProviderConnection $connection, string $externalCatalogId, array $payload): array
    {
        $endpoint = "https://graph.facebook.com/{$this->resolveGraphVersion()}/{$externalCatalogId}/collections";
        $requestPayload = array_merge($payload, [
            'access_token' => $this->resolveAccessToken($connection),
        ]);

        if (app()->environment('local')) {
            return [
                'ok' => true,
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => $requestPayload,
                'external_id' => 'local-debug-meta-collection-' . now()->timestamp,
                'body' => [
                    'id' => 'local-debug-meta-collection-' . now()->timestamp,
                ],
            ];
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($endpoint, $requestPayload);

        $json = $response->json();
        $body = is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)];

        return [
            'ok' => $response->successful(),
            'mode' => 'live',
            'endpoint' => $endpoint,
            'payload' => $requestPayload,
            'status' => $response->status(),
            'external_id' => (string) (Arr::get($body, 'id') ?? ''),
            'error' => $response->successful() ? null : Arr::get($body, 'error.message', $response->body()),
            'body' => $body,
        ];
    }

    protected function updateCollection(ProviderConnection $connection, string $externalCollectionId, array $payload): array
    {
        $endpoint = "https://graph.facebook.com/{$this->resolveGraphVersion()}/{$externalCollectionId}";
        $requestPayload = array_merge($payload, [
            'access_token' => $this->resolveAccessToken($connection),
        ]);

        if (app()->environment('local')) {
            return [
                'ok' => true,
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => $requestPayload,
                'external_id' => $externalCollectionId,
                'body' => [
                    'success' => true,
                    'id' => $externalCollectionId,
                ],
            ];
        }

        $response = Http::asForm()
            ->acceptJson()
            ->post($endpoint, $requestPayload);

        $json = $response->json();
        $body = is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)];

        return [
            'ok' => $response->successful(),
            'mode' => 'live',
            'endpoint' => $endpoint,
            'payload' => $requestPayload,
            'status' => $response->status(),
            'external_id' => $externalCollectionId,
            'error' => $response->successful() ? null : Arr::get($body, 'error.message', $response->body()),
            'body' => $body,
        ];
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
