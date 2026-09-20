<?php

namespace App\Services\Meta\Instagram;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use App\Support\ProviderSecretRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class InstagramTokenExchangeService
{
    public function __construct(
        protected InstagramWebhookSubscriptionService $instagramWebhookSubscriptionService
    ) {}

    public function exchangeAndStore(ProviderConnection $connection, string $authorizationCode): array
    {
        if (app()->environment('local')) {
            return $this->storeLocalDebugResult($connection, $authorizationCode);
        }

        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('services.instagram.client_id'),
            'client_secret' => config('services.instagram.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->resolveRedirectUri(),
            'code' => $authorizationCode,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram token exchange failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$authorizationCode, (string) config('services.instagram.client_secret')]
            ));
        }

        $payload = $response->json();
        $accessToken = (string) ($payload['access_token'] ?? '');
        $oauthUserId = (string) ($payload['user_id'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Instagram token exchange returned an empty access token.');
        }

        $longLivedToken = $this->exchangeForLongLivedToken($accessToken);
        $storedAccessToken = (string) ($longLivedToken['access_token'] ?? $accessToken);
        $identity = $this->fetchInstagramIdentity($storedAccessToken);

        $storedResult = $this->storeExchangeResult($connection, [
            'access_token' => $storedAccessToken,
            'user_id' => $identity['provider_account_id'] ?: $oauthUserId,
            'oauth_user_id' => $oauthUserId,
            'provider_account_id' => $identity['provider_account_id'] ?: $oauthUserId,
            'provider_account_name' => $identity['provider_account_name'] ?: 'Instagram OAuth User',
            'provider_account_type' => $identity['provider_account_type'] ?: 'instagram_account',
            'scopes' => config('services.instagram.scopes'),
            'mode' => 'staging_or_production_long_lived',
            'raw_payload' => [
                'short_lived' => $payload,
                'long_lived' => $longLivedToken,
            ],
            'identity_payload' => $identity['raw_payload'] ?? [],
            'expires_at' => isset($longLivedToken['expires_in'])
                ? now()->addSeconds((int) $longLivedToken['expires_in'])
                : null,
        ]);

        $storedResult['webhook_subscription'] = $this->ensureWebhookSubscription(
            (int) $storedResult['connection_id']
        );

        return $storedResult;
    }

    protected function storeLocalDebugResult(ProviderConnection $connection, string $authorizationCode): array
    {
        return $this->storeExchangeResult($connection, [
            'access_token' => 'local-debug-token-'.substr(md5($authorizationCode), 0, 16),
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
            throw new RuntimeException('Instagram identity fetch failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$accessToken]
            ));
        }

        $payload = $response->json();

        return [
            'provider_account_id' => (string) ($payload['user_id'] ?? ''),
            'provider_account_name' => (string) ($payload['username'] ?? ''),
            'provider_account_type' => 'instagram_account',
            'raw_payload' => $payload,
        ];
    }

    protected function exchangeForLongLivedToken(string $shortLivedAccessToken): array
    {
        $appSecret = $this->resolveAppSecret();

        $response = Http::acceptJson()->get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $appSecret,
            'access_token' => $shortLivedAccessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram long-lived token exchange failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$shortLivedAccessToken, $appSecret]
            ));
        }

        $payload = $response->json();

        if (blank($payload['access_token'] ?? null)) {
            throw new RuntimeException('Instagram long-lived token exchange returned an empty access token.');
        }

        return $payload;
    }

    protected function resolveRedirectUri(): string
    {
        return (string) (config('services.instagram.redirect_uri') ?: route('connections.instagram.callback'));
    }

    protected function resolveAppSecret(): string
    {
        $appSecret = (string) (config('services.instagram.app_secret') ?: config('services.instagram.client_secret'));

        if ($appSecret === '') {
            throw new RuntimeException('Instagram app secret is not configured.');
        }

        return $appSecret;
    }

    protected function storeExchangeResult(ProviderConnection $connection, array $result): array
    {
        return DB::transaction(function () use ($connection, $result) {
            $sourceConnection = ProviderConnection::query()
                ->whereKey($connection->id)
                ->lockForUpdate()
                ->first() ?? $connection;

            $targetConnection = $this->resolveExchangeTargetConnection($sourceConnection, $result);

            OauthToken::query()
                ->where('provider_connection_id', $targetConnection->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $existingMeta = is_array($targetConnection->meta) ? $targetConnection->meta : [];
            $sourceMeta = is_array($sourceConnection->meta) ? $sourceConnection->meta : [];

            if ($targetConnection->id !== $sourceConnection->id) {
                $existingMeta['reconnect_source_connection_id'] = $sourceConnection->id;
                $existingMeta['reconnect_source_meta'] = $sourceMeta;
            }

            $targetConnection->update([
                'provider_account_type' => (string) ($result['provider_account_type'] ?? $targetConnection->provider_account_type ?: 'instagram_account'),
                'provider_account_id' => (string) ($result['provider_account_id'] ?? $result['user_id'] ?? $targetConnection->provider_account_id),
                'external_oauth_user_id' => (string) ($result['oauth_user_id'] ?? $result['user_id'] ?? ''),
                'provider_account_name' => (string) ($result['provider_account_name'] ?? $targetConnection->provider_account_name ?: 'Instagram OAuth User'),
                'status' => 'connected',
                'connected_at' => now(),
                'last_synced_at' => now(),
                'meta' => array_merge($existingMeta, [
                    'exchange_mode' => $result['mode'] ?? null,
                    'exchange_completed_at' => now()->toDateTimeString(),
                    'exchange_payload' => $this->sanitizeMetaPayload($result['raw_payload'] ?? []),
                    'identity_payload' => $this->sanitizeMetaPayload($result['identity_payload'] ?? []),
                ]),
            ]);

            OauthToken::create([
                'provider_connection_id' => $targetConnection->id,
                'token_type' => 'access_token',
                'access_token' => (string) ($result['access_token'] ?? ''),
                'refresh_token' => null,
                'expires_at' => $result['expires_at'] ?? null,
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
                        'provider_connection_id' => $targetConnection->id,
                        'permission' => $scope,
                    ],
                    [
                        'status' => 'granted',
                        'granted_at' => now(),
                        'expires_at' => null,
                    ]
                );
            }

            if ($targetConnection->id !== $sourceConnection->id) {
                $this->cleanupSupersededPendingConnection($sourceConnection, $targetConnection);
            }

            return [
                'connection_id' => $targetConnection->id,
                'provider_account_id' => $targetConnection->provider_account_id,
                'status' => 'connected',
                'mode' => $result['mode'] ?? null,
            ];
        });
    }

    protected function ensureWebhookSubscription(int $connectionId): array
    {
        $connection = ProviderConnection::find($connectionId);

        if (! $connection) {
            return [
                'success' => false,
                'error' => 'Instagram connection was not found after token exchange.',
            ];
        }

        try {
            return $this->instagramWebhookSubscriptionService->ensureSubscribed($connection);
        } catch (Throwable $exception) {
            report($exception);

            $connection->forceFill([
                'meta' => array_merge(is_array($connection->meta) ? $connection->meta : [], [
                    'webhook_subscription' => [
                        'success' => false,
                        'error' => $exception->getMessage(),
                        'verified_at' => now()->toIso8601String(),
                    ],
                ]),
            ])->save();

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    protected function resolveExchangeTargetConnection(ProviderConnection $connection, array $result): ProviderConnection
    {
        $providerAccountId = (string) ($result['provider_account_id'] ?? $result['user_id'] ?? '');

        if ($providerAccountId === '') {
            return $connection;
        }

        $existingConnection = ProviderConnection::query()
            ->where('provider', $connection->provider)
            ->where('provider_account_id', $providerAccountId)
            ->whereKeyNot($connection->id)
            ->lockForUpdate()
            ->first();

        if (! $existingConnection) {
            return $connection;
        }

        if ((int) $existingConnection->workspace_id !== (int) $connection->workspace_id) {
            throw new RuntimeException('This Instagram account is already connected to another workspace.');
        }

        return $existingConnection;
    }

    protected function cleanupSupersededPendingConnection(ProviderConnection $sourceConnection, ProviderConnection $targetConnection): void
    {
        if (! $this->connectionHasDependentRecords($sourceConnection)) {
            $sourceConnection->delete();

            return;
        }

        $sourceConnection->update([
            'provider_account_id' => 'superseded-instagram-account-'.$sourceConnection->id,
            'provider_account_name' => 'Superseded Instagram Connection',
            'status' => 'superseded',
            'meta' => array_merge(is_array($sourceConnection->meta) ? $sourceConnection->meta : [], [
                'superseded_by_connection_id' => $targetConnection->id,
                'superseded_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    protected function connectionHasDependentRecords(ProviderConnection $connection): bool
    {
        foreach ([
            'oauth_tokens',
            'provider_permissions',
            'conversations',
            'social_posts',
            'social_comments',
            'social_stories',
            'social_post_media',
        ] as $table) {
            if (DB::table($table)->where('provider_connection_id', $connection->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeMetaPayload(mixed $payload): mixed
    {
        return ProviderSecretRedactor::payload($payload);
    }
}
