<?php

namespace App\Services\Meta\Commerce;

use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaCommerceDiscoveryService
{
    public function discoverForConnection(ProviderConnection $connection): array
    {
        if ($connection->provider !== 'instagram') {
            throw new RuntimeException('Meta Commerce discovery requires an Instagram provider connection.');
        }

        $accessToken = $this->resolveAccessToken($connection);
        $graphVersion = $this->resolveGraphVersion();
        $accountId = (string) $connection->provider_account_id;
        $knownBusinessId = (string) Arr::get($connection->meta ?? [], 'meta_commerce.business_id', '');
        $checks = [];

        $checks['facebook_me'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/me",
            [
                'fields' => 'id,name',
            ]
        );

        $checks['businesses'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/me/businesses",
            [
                'fields' => 'id,name,verification_status',
                'limit' => 50,
            ]
        );

        $checks['instagram_account'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/{$accountId}",
            [
                'fields' => 'id,username,name,ig_id,shopping_product_tag_eligibility,shopping_review_status',
            ]
        );

        $businessIds = collect(Arr::get($checks['businesses'], 'body.data', []))
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->when($knownBusinessId !== '', fn ($ids) => $ids->prepend($knownBusinessId))
            ->unique()
            ->values();

        $catalogs = collect();

        foreach ($businessIds as $businessId) {
            $ownedKey = "business:{$businessId}:owned_product_catalogs";
            $clientKey = "business:{$businessId}:client_product_catalogs";

            $checks[$ownedKey] = $this->requestJson(
                $accessToken,
                "https://graph.facebook.com/{$graphVersion}/{$businessId}/owned_product_catalogs",
                [
                    'fields' => 'id,name,vertical,product_count',
                    'limit' => 100,
                ]
            );

            $checks[$clientKey] = $this->requestJson(
                $accessToken,
                "https://graph.facebook.com/{$graphVersion}/{$businessId}/client_product_catalogs",
                [
                    'fields' => 'id,name,vertical,product_count',
                    'limit' => 100,
                ]
            );

            $catalogs = $catalogs
                ->merge($this->extractCatalogs($checks[$ownedKey], $businessId, 'owned'))
                ->merge($this->extractCatalogs($checks[$clientKey], $businessId, 'client'));
        }

        $catalogs = $catalogs
            ->unique(fn ($catalog) => (string) ($catalog['id'] ?? ''))
            ->values()
            ->all();

        return [
            'ok' => collect($checks)->contains(fn ($check) => (bool) ($check['ok'] ?? false)),
            'checked_at' => now()->toIso8601String(),
            'graph_version' => $graphVersion,
            'provider_connection_id' => $connection->id,
            'provider_account_id' => $accountId,
            'review_scopes' => $this->reviewScopes(),
            'business_ids' => $businessIds->all(),
            'catalogs' => $catalogs,
            'checks' => $checks,
        ];
    }

    protected function requestJson(string $accessToken, string $url, array $query = []): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url, $query);

            $json = $response->json();

            return [
                'url' => $url,
                'query' => $query,
                'status' => $response->status(),
                'ok' => $response->successful(),
                'error' => $response->successful() ? null : Arr::get($json, 'error.message', $response->body()),
                'body' => is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)],
            ];
        } catch (\Throwable $exception) {
            return [
                'url' => $url,
                'query' => $query,
                'status' => null,
                'ok' => false,
                'error' => $exception->getMessage(),
                'body' => [],
            ];
        }
    }

    protected function extractCatalogs(array $check, string $businessId, string $relationship): array
    {
        if (! ($check['ok'] ?? false)) {
            return [];
        }

        return collect(Arr::get($check, 'body.data', []))
            ->filter(fn ($catalog) => is_array($catalog) && filled($catalog['id'] ?? null))
            ->map(fn ($catalog) => [
                'id' => (string) $catalog['id'],
                'name' => (string) ($catalog['name'] ?? 'Meta Catalog ' . $catalog['id']),
                'business_id' => $businessId,
                'relationship' => $relationship,
                'vertical' => $catalog['vertical'] ?? null,
                'product_count' => $catalog['product_count'] ?? null,
                'raw' => $catalog,
            ])
            ->values()
            ->all();
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

    protected function reviewScopes(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.meta.commerce_review_scopes', ''))
        )));
    }
}
