<?php

namespace App\Services\Meta\Instagram;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramTokenExchangeService
{
    public function exchangeAndStore(ProviderConnection $connection, string $authorizationCode): array
    {
        if (app()->environment('local')) {
            return $this->storeLocalDebugResult($connection, $authorizationCode);
        }

        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('services.instagram.client_id'),
            'client_secret' => config('services.instagram.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.instagram.redirect_uri'),
            'code' => $authorizationCode,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram token exchange failed: ' . $response->body());
        }

        $payload = $response->json();
        $accessToken = (string) ($payload['access_token'] ?? '');
        $oauthUserId = (string) ($payload['user_id'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Instagram token exchange returned an empty access token.');
        }

        $identity = $this->fetchInstagramIdentity($accessToken);

        return $this->storeExchangeResult($connection, [
            'access_token' => $accessToken,
            'user_id' => $identity['provider_account_id'] ?: $oauthUserId,
            'oauth_user_id' => $oauthUserId,
            'provider_account_id' => $identity['provider_account_id'] ?: $oauthUserId,
            'provider_account_name' => $identity['provider_account_name'] ?: 'Instagram OAuth User',
            'provider_account_type' => $identity['provider_account_type'] ?: 'instagram_account',
            'scopes' => config('services.instagram.scopes'),
            'mode' => 'staging_or_production',
            'raw_payload' => $payload,
            'identity_payload' => $identity['raw_payload'] ?? [],
        ]);
    }

    protected function storeLocalDebugResult(ProviderConnection $connection, string $authorizationCode): array
    {
        return $this->storeExchangeResult($connection, [
            'access_token' => 'local-debug-token-' . substr(md5($authorizationCode), 0, 16),
            'user_id' => 'local-debug-instagram-account',
            'oauth_user_id' => 'local-debug-instagram-user',
            'provider_account_id' => 'local-debug-instagram-account',
            'provider_account_name' => 'Local Debug Instagram Account',
            'provider_account_type' => 'instagram_account',
            'scopes' => config('services.instagram.scopes'),
            'mode' => 'local_debug_exchange',
            'raw_payload' => [
                'debug' => true,
                'authorization_code' => $authorizationCode,
            ],
            'identity_payload' => [
                'debug' => true,
                'user_id' => 'local-debug-instagram-account',
                'username' => 'local_debug_instagram_account',
            ],
        ]);
    }

    protected function fetchInstagramIdentity(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/me', [
            'fields' => 'user_id,username',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram identity fetch failed: ' . $response->body());
        }

        $payload = $response->json();

        return [
            'provider_account_id' => (string) ($payload['user_id'] ?? ''),
            'provider_account_name' => (string) ($payload['username'] ?? ''),
            'provider_account_type' => 'instagram_account',
            'raw_payload' => $payload,
        ];
    }

    protected function storeExchangeResult(ProviderConnection $connection, array $result): array
    {
        return DB::transaction(function () use ($connection, $result) {
            OauthToken::query()
                ->where('provider_connection_id', $connection->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $existingMeta = is_array($connection->meta) ? $connection->meta : [];

            $connection->update([
                'provider_account_type' => (string) ($result['provider_account_type'] ?? $connection->provider_account_type ?: 'instagram_account'),
                'provider_account_id' => (string) ($result['provider_account_id'] ?? $result['user_id'] ?? $connection->provider_account_id),
                'external_oauth_user_id' => (string) ($result['oauth_user_id'] ?? $result['user_id'] ?? ''),
                'provider_account_name' => (string) ($result['provider_account_name'] ?? $connection->provider_account_name ?: 'Instagram OAuth User'),
                'status' => 'connected',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'meta' => array_merge($existingMeta, [
                    'exchange_mode' => $result['mode'] ?? null,
                    'exchange_completed_at' => now()->toDateTimeString(),
                    'exchange_payload' => $result['raw_payload'] ?? [],
                    'identity_payload' => $result['identity_payload'] ?? [],
                ]),
            ]);

            OauthToken::create([
                'provider_connection_id' => $connection->id,
                'token_type' => 'access_token',
                'access_token' => (string) ($result['access_token'] ?? ''),
                'refresh_token' => null,
                'expires_at' => null,
                'scopes' => (string) ($result['scopes'] ?? ''),
                'is_primary' => true,
            ]);

            $scopes = collect(explode(',', (string) ($result['scopes'] ?? '')))
                ->map(fn ($scope) => trim($scope))
                ->filter()
                ->values();

            foreach ($scopes as $scope) {
                ProviderPermission::updateOrCreate(
                    [
                        'provider_connection_id' => $connection->id,
                        'permission' => $scope,
                    ],
                    [
                        'status' => 'granted',
                        'granted_at' => now(),
                        'expires_at' => null,
                    ]
                );
            }

            return [
                'connection_id' => $connection->id,
                'provider_account_id' => $connection->provider_account_id,
                'status' => 'connected',
                'mode' => $result['mode'] ?? null,
            ];
        });
    }
}
