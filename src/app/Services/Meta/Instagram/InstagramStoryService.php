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
        $caption = trim((string) ($payload['caption'] ?? ''));

        if ($mediaUrl === '') {
            throw new RuntimeException('Instagram story media_url is required.');
        }

        $graphVersion = $this->resolveGraphVersion();
        $containerEndpoint = "https://graph.instagram.com/{$graphVersion}/{$accountId}/media";
        $publishEndpoint = "https://graph.instagram.com/{$graphVersion}/{$accountId}/media_publish";

        $containerPayload = [
            'media_type' => strtoupper($mediaType),
            'is_stories' => 'true',
            'access_token' => $accessToken,
        ];

        if (strtoupper($mediaType) === 'VIDEO') {
            $containerPayload['video_url'] = $mediaUrl;
        } else {
            $containerPayload['image_url'] = $mediaUrl;
        }

        if ($caption !== '') {
            $containerPayload['caption'] = $caption;
        }

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'container_endpoint' => $containerEndpoint,
                'publish_endpoint' => $publishEndpoint,
                'container_payload' => $containerPayload,
                'creation_id' => 'local-debug-story-container-' . now()->timestamp,
                'id' => 'local-debug-story-' . now()->timestamp,
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

        $publishResponse = Http::asForm()->post($publishEndpoint, [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ]);

        if (! $publishResponse->successful()) {
            throw new RuntimeException('Instagram story publish failed: ' . $publishResponse->body());
        }

        return array_merge($publishResponse->json(), [
            'creation_id' => $creationId,
        ]);
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
