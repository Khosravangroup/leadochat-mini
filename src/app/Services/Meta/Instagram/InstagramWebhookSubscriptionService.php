<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InstagramWebhookSubscriptionService
{
    public function ensureSubscribed(ProviderConnection $connection, ?array $fields = null): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $accountId = $this->resolveAccountId($connection);
        $subscribedFields = $this->normalizeFields($fields ?? config('services.instagram.webhook_subscribed_fields', []));

        if (empty($subscribedFields)) {
            throw new RuntimeException('No Instagram webhook subscribed fields are configured.');
        }

        $endpoint = $this->resolveSubscriptionEndpoint($accountId);

        $this->logWebhookSubscription('subscription_started', [
            'connection_id' => $connection->id,
            'provider_account_id' => $accountId,
            'endpoint' => $endpoint,
            'requested_fields' => $subscribedFields,
        ]);

        $response = Http::asForm()
            ->acceptJson()
            ->post($endpoint, [
                'subscribed_fields' => implode(',', $subscribedFields),
                'access_token' => $accessToken,
            ]);

        if (! $response->successful()) {
            $result = [
                'success' => false,
                'endpoint' => $endpoint,
                'requested_fields' => $subscribedFields,
                'status' => $response->status(),
                'error' => $response->json('error.message') ?: $response->body(),
                'verified_fields' => [],
                'missing_fields' => $subscribedFields,
            ];

            $this->recordSubscriptionResult($connection, $result);
            $this->logWebhookSubscription('subscription_failed', [
                'connection_id' => $connection->id,
                'provider_account_id' => $accountId,
                'status' => $result['status'],
                'error' => $result['error'],
                'requested_fields' => $subscribedFields,
            ], 'warning');

            throw new RuntimeException('Instagram webhook subscription failed: ' . $result['error']);
        }

        $verification = $this->fetchSubscription($accountId, $accessToken);
        $verifiedFields = $this->extractVerifiedFields($verification);
        $missingFields = array_values(array_diff($subscribedFields, $verifiedFields));

        $result = [
            'success' => empty($missingFields),
            'endpoint' => $endpoint,
            'requested_fields' => $subscribedFields,
            'status' => $response->status(),
            'response' => $this->sanitizeResponse($response->json() ?? []),
            'verified_fields' => $verifiedFields,
            'missing_fields' => $missingFields,
            'verified_at' => now()->toIso8601String(),
        ];

        $this->recordSubscriptionResult($connection, $result);
        $this->logWebhookSubscription('subscription_completed', [
            'connection_id' => $connection->id,
            'provider_account_id' => $accountId,
            'success' => $result['success'],
            'requested_fields' => $subscribedFields,
            'verified_fields' => $verifiedFields,
            'missing_fields' => $missingFields,
        ], $result['success'] ? 'info' : 'warning');

        return $result;
    }

    protected function fetchSubscription(string $accountId, string $accessToken): array
    {
        $response = Http::acceptJson()->get($this->resolveSubscriptionEndpoint($accountId), [
            'fields' => 'id,name,subscribed_fields',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            return [
                'error' => $response->json('error.message') ?: $response->body(),
                'status' => $response->status(),
            ];
        }

        return $response->json() ?? [];
    }

    protected function extractVerifiedFields(array $payload): array
    {
        return collect(Arr::get($payload, 'data', []))
            ->flatMap(fn ($subscription) => Arr::get($subscription, 'subscribed_fields', []))
            ->map(fn ($field) => trim((string) $field))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function recordSubscriptionResult(ProviderConnection $connection, array $result): void
    {
        $connection->forceFill([
            'meta' => array_merge(is_array($connection->meta) ? $connection->meta : [], [
                'webhook_subscription' => $this->sanitizeResponse($result),
            ]),
        ])->save();
    }

    protected function sanitizeResponse(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (str_contains($normalizedKey, 'token') || str_contains($normalizedKey, 'secret')) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $this->sanitizeResponse($value);
        }

        return $sanitized;
    }

    protected function normalizeFields(array|string $fields): array
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        return collect($fields)
            ->map(fn ($field) => trim((string) $field))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveAccessToken(ProviderConnection $connection): string
    {
        $accessToken = (string) ($connection->oauthTokens()
            ->where('token_type', 'access_token')
            ->where('is_primary', true)
            ->latest('id')
            ->value('access_token') ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('No primary Instagram access token found for webhook subscription.');
        }

        return $accessToken;
    }

    protected function resolveAccountId(ProviderConnection $connection): string
    {
        $accountId = trim((string) $connection->provider_account_id);

        if ($accountId === '') {
            throw new RuntimeException('Instagram provider_account_id is empty for webhook subscription.');
        }

        return $accountId;
    }

    protected function resolveSubscriptionEndpoint(string $accountId): string
    {
        $version = (string) config('services.instagram.graph_version', 'v25.0');

        return "https://graph.instagram.com/{$version}/{$accountId}/subscribed_apps";
    }

    protected function logWebhookSubscription(string $event, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('instagram_webhooks')->{$level}($event, array_merge([
                'graph_version' => config('services.instagram.graph_version'),
                'service' => static::class,
            ], $context));
        } catch (\Throwable) {
            // Subscription logging is diagnostic only.
        }
    }
}
