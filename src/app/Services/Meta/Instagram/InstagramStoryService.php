<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramStoryService
{
    public function publishStory(ProviderConnection $connection, array $payload): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $accountId = $this->resolveAccountId($connection);

        $mediaType = (string) ($payload['media_type'] ?? 'IMAGE');
        $mediaUrl = trim((string) ($payload['media_url'] ?? ''));
        $mediaType = strtoupper($mediaType) === 'VIDEO' ? 'VIDEO' : 'IMAGE';

        if ($mediaUrl === '') {
            throw new RuntimeException('Instagram story media_url is required.');
        }

        $graphVersion = $this->resolveGraphVersion();
        $containerEndpoint = "https://graph.instagram.com/{$graphVersion}/{$accountId}/media";
        $publishEndpoint = "https://graph.instagram.com/{$graphVersion}/{$accountId}/media_publish";

        $containerPayload = [
            'media_type' => 'STORIES',
            'access_token' => $accessToken,
        ];

        if ($mediaType === 'VIDEO') {
            $containerPayload['video_url'] = $mediaUrl;
        } else {
            $containerPayload['image_url'] = $mediaUrl;
        }

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'container_endpoint' => $containerEndpoint,
                'publish_endpoint' => $publishEndpoint,
                'container_payload' => $containerPayload,
                'creation_id' => 'local-debug-story-container-' . now()->timestamp,
                'id' => 'local-debug-story-' . now()->timestamp,
                'container_status' => 'FINISHED',
            ];
        }

        $containerResponse = Http::asForm()->post($containerEndpoint, $containerPayload);

        if (! $containerResponse->successful()) {
            throw new RuntimeException('Instagram story container creation failed: ' . $containerResponse->body());
        }

        $creationId = (string) ($containerResponse->json('id') ?? '');

        if ($creationId === '') {
            throw new RuntimeException('Instagram story container creation did not return an id.');
        }

        $containerStatus = $this->waitForContainerReady($creationId, $accessToken, $graphVersion, $mediaType);

        $publishResponse = Http::asForm()->post($publishEndpoint, [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ]);

        if (! $publishResponse->successful()) {
            throw new RuntimeException('Instagram story publish failed: ' . $publishResponse->body());
        }

        return array_merge($publishResponse->json(), [
            'creation_id' => $creationId,
            'container_status' => $containerStatus,
        ]);
    }

    public function syncStories(ProviderConnection $connection, array $options = []): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $accountId = $this->resolveAccountId($connection);
        $graphVersion = $this->resolveGraphVersion();
        $limit = max(1, min((int) ($options['limit'] ?? 25), 50));

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'synced' => 0,
                'stories' => [],
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://graph.instagram.com/{$graphVersion}/{$accountId}/stories", [
                'fields' => 'id,media_type,media_url,thumbnail_url,timestamp,permalink',
                'limit' => $limit,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram story sync failed: ' . $response->body());
        }

        $stories = $response->json('data');

        return [
            'mode' => 'live',
            'synced' => is_array($stories) ? count($stories) : 0,
            'stories' => is_array($stories) ? $stories : [],
        ];
    }

    protected function waitForContainerReady(string $creationId, string $accessToken, string $graphVersion, string $mediaType): ?string
    {
        $statusEndpoint = "https://graph.instagram.com/{$graphVersion}/{$creationId}";
        $lastStatus = null;
        $attempts = $mediaType === 'VIDEO' ? 10 : 2;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($statusEndpoint, [
                    'fields' => 'status_code',
                ]);

            if (! $response->successful()) {
                if ($mediaType === 'IMAGE') {
                    return $lastStatus;
                }

                throw new RuntimeException('Instagram story container status check failed: ' . $response->body());
            }

            $lastStatus = (string) ($response->json('status_code') ?? '');

            if ($lastStatus === 'FINISHED' || $lastStatus === '') {
                return $lastStatus !== '' ? $lastStatus : null;
            }

            if (in_array($lastStatus, ['ERROR', 'EXPIRED'], true)) {
                throw new RuntimeException('Instagram story container failed with status ' . $lastStatus . '.');
            }

            usleep(1500000);
        }

        if ($mediaType === 'VIDEO') {
            throw new RuntimeException('Instagram story video is still processing. Please try again in a moment.');
        }

        return $lastStatus;
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
            throw new RuntimeException('No primary Instagram access token found for this connection.');
        }

        return $accessToken;
    }

    protected function resolveAccountId(ProviderConnection $connection): string
    {
        $accountId = (string) ($connection->provider_account_id ?? '');

        if ($accountId === '') {
            throw new RuntimeException('Instagram provider_account_id is empty.');
        }

        return $accountId;
    }

    protected function resolveGraphVersion(): string
    {
        return (string) config('services.instagram.graph_version', 'v25.0');
    }
}
