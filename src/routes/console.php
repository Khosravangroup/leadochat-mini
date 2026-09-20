<?php

use App\Models\ProviderConnection;
use App\Services\Meta\Instagram\InstagramWebhookSubscriptionService;
use App\Support\ProviderSecretRedactor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('instagram:webhooks:subscribe {connection? : Provider connection id or Instagram account id}', function (?string $connection = null) {
    $query = ProviderConnection::query()
        ->where('provider', 'instagram')
        ->where('status', 'connected');

    if ($connection) {
        $query->where(function ($builder) use ($connection) {
            $builder->whereKey($connection)
                ->orWhere('provider_account_id', $connection);
        });
    }

    $connections = $query->orderBy('id')->get();

    if ($connections->isEmpty()) {
        $this->warn('No connected Instagram provider connections found.');

        return 1;
    }

    $failed = 0;

    foreach ($connections as $providerConnection) {
        try {
            $result = app(InstagramWebhookSubscriptionService::class)
                ->ensureSubscribed($providerConnection);

            $this->info(json_encode([
                'connection_id' => $providerConnection->id,
                'provider_account_id' => $providerConnection->provider_account_id,
                'success' => (bool) ($result['success'] ?? false),
                'requested_fields' => $result['requested_fields'] ?? [],
                'verified_fields' => $result['verified_fields'] ?? [],
                'missing_fields' => $result['missing_fields'] ?? [],
            ], JSON_UNESCAPED_SLASHES));
        } catch (Throwable $exception) {
            $failed++;

            $this->error(json_encode([
                'connection_id' => $providerConnection->id,
                'provider_account_id' => $providerConnection->provider_account_id,
                'success' => false,
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_SLASHES));
        }
    }

    return $failed > 0 ? 1 : 0;
})->purpose('Ensure connected Instagram accounts are subscribed to configured webhook fields');

Artisan::command('instagram:diagnose-messaging {connection? : Provider connection id or Instagram account id}', function (?string $connection = null) {
    $providerConnection = ProviderConnection::query()
        ->where('provider', 'instagram')
        ->where('status', 'connected')
        ->when($connection, function ($query) use ($connection) {
            $query->where(function ($builder) use ($connection) {
                $builder->whereKey($connection)
                    ->orWhere('provider_account_id', $connection)
                    ->orWhere('external_oauth_user_id', $connection);
            });
        })
        ->latest('id')
        ->first();

    if (! $providerConnection) {
        $this->warn('No connected Instagram provider connection found.');

        return 1;
    }

    $accessToken = (string) ($providerConnection->oauthTokens()
        ->where('token_type', 'access_token')
        ->where('is_primary', true)
        ->latest('id')
        ->first()?->access_token ?? '');

    if ($accessToken === '') {
        $this->warn('No primary Instagram access token found.');

        return 1;
    }

    $version = (string) config('services.instagram.graph_version', 'v25.0');
    $accountIds = collect([
        'provider_account_id' => $providerConnection->provider_account_id,
        'external_oauth_user_id' => $providerConnection->external_oauth_user_id,
        'identity_payload_id' => data_get($providerConnection->meta, 'identity_payload.id'),
        'identity_payload_user_id' => data_get($providerConnection->meta, 'identity_payload.user_id'),
        'short_lived_user_id' => data_get($providerConnection->meta, 'exchange_payload.short_lived.user_id'),
    ])
        ->filter(fn ($value) => filled($value))
        ->map(fn ($value) => (string) $value)
        ->all();

    $diagnostics = [
        'connection' => [
            'id' => $providerConnection->id,
            'workspace_id' => $providerConnection->workspace_id,
            'provider_account_id' => $providerConnection->provider_account_id,
            'external_oauth_user_id' => $providerConnection->external_oauth_user_id,
            'provider_account_name' => $providerConnection->provider_account_name,
            'status' => $providerConnection->status,
        ],
        'config' => [
            'app_url' => config('app.url'),
            'graph_version' => $version,
            'client_id' => config('services.instagram.client_id'),
            'webhook_app_id' => config('services.instagram.webhook_app_id'),
            'webhook_subscribed_fields' => config('services.instagram.webhook_subscribed_fields', []),
        ],
        'account_ids' => $accountIds,
        'checks' => [],
    ];

    $requestJson = function (string $label, string $url, array $query = []) use ($accessToken, &$diagnostics) {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get($url, $query);

        $json = $response->json();
        $diagnostics['checks'][$label] = [
            'url' => $url,
            'query' => $query,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'data_count' => is_array(data_get($json, 'data')) ? count(data_get($json, 'data')) : null,
            'error' => ProviderSecretRedactor::text((string) data_get($json, 'error.message', ''), [$accessToken]),
            'body' => ProviderSecretRedactor::payload(
                $json ?: ['raw' => mb_substr($response->body(), 0, 1000)],
                [$accessToken]
            ),
        ];
    };

    $requestJson('me', "https://graph.instagram.com/{$version}/me", [
        'fields' => 'id,user_id,username,account_type,media_count',
    ]);

    foreach (array_unique($accountIds) as $accountId) {
        $requestJson("subscribed_apps:{$accountId}", "https://graph.instagram.com/{$version}/{$accountId}/subscribed_apps", [
            'fields' => 'id,name,subscribed_fields',
        ]);
    }

    $conversationFields = 'id,updated_time,participants,messages.limit(5){id,message,from,to,created_time}';
    $requestJson('conversations:me', "https://graph.instagram.com/{$version}/me/conversations", [
        'platform' => 'instagram',
        'fields' => $conversationFields,
        'limit' => 10,
    ]);

    foreach (array_unique($accountIds) as $accountId) {
        $requestJson("conversations:{$accountId}", "https://graph.instagram.com/{$version}/{$accountId}/conversations", [
            'platform' => 'instagram',
            'fields' => $conversationFields,
            'limit' => 10,
        ]);
    }

    $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    return 0;
})->purpose('Diagnose Instagram messaging token, subscription, and Conversations API visibility');
