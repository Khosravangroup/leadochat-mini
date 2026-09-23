<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramInsightsService
{
    public function accountSummary(ProviderConnection $connection): array
    {
        if ($connection->provider !== 'instagram' || $connection->status !== 'connected') {
            throw new RuntimeException('A connected Instagram account is required.');
        }

        $accountId = (string) $connection->provider_account_id;
        if (! ctype_digit($accountId)) {
            throw new RuntimeException('A valid Instagram account ID is required.');
        }

        $token = $connection->oauthTokens()
            ->where('token_type', 'access_token')
            ->where('is_primary', true)
            ->latest('id')
            ->first();

        if (! $token || $token->expires_at?->isPast() || ! $token->access_token) {
            throw new RuntimeException('A valid Instagram access token is required.');
        }

        $version = (string) config('services.instagram.graph_version', 'v25.0');
        $endpoint = "https://graph.instagram.com/{$version}/{$accountId}/insights";
        $since = now('UTC')->subDays(7)->startOfDay();
        $until = now('UTC');

        $response = Http::acceptJson()->withToken($token->access_token)->timeout(10)->get($endpoint, [
            'metric' => 'reach,views,total_interactions',
            'period' => 'day',
            'metric_type' => 'total_value',
            'since' => $since->timestamp,
            'until' => $until->timestamp,
        ]);

        if (! $response->successful() || ! is_array($response->json('data'))) {
            throw new RuntimeException('Instagram insights are currently unavailable.');
        }

        $values = array_fill_keys(['reach', 'views', 'total_interactions'], null);
        foreach ($response->json('data') as $item) {
            $name = $item['name'] ?? null;
            $value = $item['total_value']['value'] ?? null;
            if (is_string($name) && array_key_exists($name, $values) && is_numeric($value)) {
                $values[$name] = (int) $value;
            }
        }

        return [
            'values' => $values,
            'since' => $since->toDateString(),
            'until' => $until->toDateString(),
        ];
    }
}
