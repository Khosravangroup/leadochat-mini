<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use App\Models\SocialPost;
use App\Models\SocialPostMedia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramContentService
{
    public function fetchMediaFeed(ProviderConnection $connection, array $options = []): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $accountId = $this->resolveAccountId($connection);

        $fields = $options['fields']
            ?? 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,children{id,media_type,media_url,thumbnail_url,timestamp}';

        $query = [
            'fields' => $fields,
            'limit' => $options['limit'] ?? 24,
            'access_token' => $accessToken,
        ];

        if (!empty($options['after'])) {
            $query['after'] = $options['after'];
        }

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$accountId}/media";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'query' => $query,
                'data' => [
                    [
                        'id' => 'local-debug-media-1',
                        'caption' => 'Local debug Instagram image post',
                        'media_type' => 'IMAGE',
                        'media_url' => null,
                        'thumbnail_url' => null,
                        'permalink' => 'https://instagram.local/debug/media/1',
                        'timestamp' => now()->subMinutes(10)->toIso8601String(),
                        'like_count' => 3,
                        'comments_count' => 1,
                    ],
                    [
                        'id' => 'local-debug-media-2',
                        'caption' => 'Local debug Instagram reel',
                        'media_type' => 'REELS',
                        'media_url' => null,
                        'thumbnail_url' => null,
                        'permalink' => 'https://instagram.local/debug/media/2',
                        'timestamp' => now()->subMinutes(5)->toIso8601String(),
                        'like_count' => 5,
                        'comments_count' => 2,
                    ],
                    [
                        'id' => 'local-debug-media-3',
                        'caption' => 'Local debug Instagram carousel',
                        'media_type' => 'CAROUSEL_ALBUM',
                        'media_url' => null,
                        'thumbnail_url' => null,
                        'permalink' => 'https://instagram.local/debug/media/3',
                        'timestamp' => now()->toIso8601String(),
                        'like_count' => 8,
                        'comments_count' => 4,
                        'children' => [
                            'data' => [
                                [
                                    'id' => 'local-debug-media-3-child-1',
                                    'media_type' => 'IMAGE',
                                    'media_url' => null,
                                    'thumbnail_url' => null,
                                    'timestamp' => now()->toIso8601String(),
                                ],
                                [
                                    'id' => 'local-debug-media-3-child-2',
                                    'media_type' => 'VIDEO',
                                    'media_url' => null,
                                    'thumbnail_url' => null,
                                    'timestamp' => now()->toIso8601String(),
                                ],
                            ],
                        ],
                    ],
                ],
                'paging' => [],
            ];
        }

        $response = Http::acceptJson()->get($endpoint, $query);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram fetchMediaFeed failed: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchMediaDetails(ProviderConnection $connection, string $mediaId, array $options = []): array
    {
        $accessToken = $this->resolveAccessToken($connection);

        $fields = $options['fields']
            ?? 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,children{id,media_type,media_url,thumbnail_url,timestamp}';

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$mediaId}";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'query' => [
                    'fields' => $fields,
                    'access_token' => $accessToken,
                ],
                'data' => null,
            ];
        }

        $response = Http::acceptJson()->get($endpoint, [
            'fields' => $fields,
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram fetchMediaDetails failed: ' . $response->body());
        }

        return $response->json();
    }

    public function deleteMedia(ProviderConnection $connection, string $mediaId): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $mediaId = trim($mediaId);

        if ($mediaId === '') {
            throw new RuntimeException('Instagram media id cannot be empty for deleteMedia.');
        }

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$mediaId}";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'success' => true,
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->delete($endpoint);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram deleteMedia failed: ' . $response->body());
        }

        return $response->json();
    }

    public function syncMediaFeed(ProviderConnection $connection, array $options = []): array
    {
        $feed = $this->fetchMediaFeed($connection, $options);
        $items = collect($feed['data'] ?? []);
        $synced = [];

        DB::transaction(function () use ($connection, $items, &$synced) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $synced[] = $this->upsertSocialPostWithMedia($connection, $item);
            }
        });

        return [
            'mode' => $feed['mode'] ?? (app()->environment('local') ? 'local_debug' : 'live'),
            'count' => count($synced),
            'items' => $synced,
            'paging' => $feed['paging'] ?? [],
        ];
    }

    protected function upsertSocialPostWithMedia(ProviderConnection $connection, array $item): array
    {
        $socialPost = $this->upsertSocialPost($connection, $item);

        $mediaItems = $this->extractMediaItems($item);
        $this->syncSocialPostMediaItems($connection, $socialPost, $mediaItems);
        $this->syncSocialPostCoverMedia($socialPost);

        $socialPost->refresh();

        return [
            'id' => $socialPost->id,
            'provider_media_id' => $socialPost->provider_media_id,
            'media_type' => $socialPost->media_type,
            'caption' => $socialPost->caption,
            'permalink' => $socialPost->permalink,
            'posted_at' => optional($socialPost->posted_at)?->toDateTimeString(),
            'comments_count' => $socialPost->comments_count,
            'like_count' => $socialPost->like_count,
            'media_items_count' => $socialPost->mediaItems()->count(),
            'cover_media_type' => $socialPost->mediaItems()->where('is_cover', true)->value('media_type'),
        ];
    }

    protected function extractMediaItems(array $item): array
    {
        $children = $item['children']['data'] ?? [];

        if (is_array($children) && !empty($children)) {
            $mediaItems = [];

            foreach (array_values($children) as $index => $child) {
                if (!is_array($child)) {
                    continue;
                }

                $mediaItems[] = [
                    'id' => $child['id'] ?? null,
                    'parent_provider_media_id' => $item['id'] ?? null,
                    'media_type' => $child['media_type'] ?? null,
                    'media_url' => $child['media_url'] ?? null,
                    'thumbnail_url' => $child['thumbnail_url'] ?? null,
                    'timestamp' => $child['timestamp'] ?? ($item['timestamp'] ?? null),
                    'position' => $index,
                    'is_cover' => $index === 0,
                    'raw' => $child,
                ];
            }

            return $mediaItems;
        }

        return [[
            'id' => $item['id'] ?? null,
            'parent_provider_media_id' => null,
            'media_type' => $item['media_type'] ?? null,
            'media_url' => $item['media_url'] ?? null,
            'thumbnail_url' => $item['thumbnail_url'] ?? null,
            'timestamp' => $item['timestamp'] ?? null,
            'position' => 0,
            'is_cover' => true,
            'raw' => $item,
        ]];
    }

    protected function syncSocialPostMediaItems(ProviderConnection $connection, SocialPost $socialPost, array $mediaItems): void
    {
        $keepIds = [];

        foreach ($mediaItems as $index => $mediaItem) {
            $providerMediaId = (string) ($mediaItem['id'] ?? '');

            if ($providerMediaId === '') {
                continue;
            }

            $postMedia = SocialPostMedia::query()->firstOrNew([
                'provider' => 'instagram',
                'provider_media_id' => $providerMediaId,
            ]);

            $postMedia->social_post_id = $socialPost->id;
            $postMedia->workspace_id = $connection->workspace_id;
            $postMedia->provider_connection_id = $connection->id;
            $postMedia->provider = 'instagram';
            $postMedia->provider_media_id = $providerMediaId;
            $postMedia->parent_provider_media_id = $this->normalizeNullableString($mediaItem['parent_provider_media_id'] ?? null);
            $postMedia->media_type = $this->normalizeMediaType($mediaItem);
            $postMedia->media_url = $this->normalizeNullableString($mediaItem['media_url'] ?? null);
            $postMedia->thumbnail_url = $this->normalizeNullableString($mediaItem['thumbnail_url'] ?? null);
            $postMedia->position = isset($mediaItem['position']) ? (int) $mediaItem['position'] : $index;
            $postMedia->is_cover = (bool) ($mediaItem['is_cover'] ?? false);
            $postMedia->posted_at = $this->normalizeTimestamp($mediaItem['timestamp'] ?? null);
            $postMedia->raw = is_array($mediaItem['raw'] ?? null) ? $mediaItem['raw'] : $mediaItem;
            $postMedia->save();

            $keepIds[] = $postMedia->id;
        }

        if (!empty($keepIds)) {
            SocialPostMedia::query()
                ->where('social_post_id', $socialPost->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    protected function syncSocialPostCoverMedia(SocialPost $socialPost): void
    {
        $coverMedia = SocialPostMedia::query()
            ->where('social_post_id', $socialPost->id)
            ->orderByDesc('is_cover')
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if (! $coverMedia) {
            return;
        }

        $dirty = false;

        if ($socialPost->media_url === null && $coverMedia->media_url !== null) {
            $socialPost->media_url = $coverMedia->media_url;
            $dirty = true;
        }

        if ($socialPost->thumbnail_url === null && $coverMedia->thumbnail_url !== null) {
            $socialPost->thumbnail_url = $coverMedia->thumbnail_url;
            $dirty = true;
        }

        if ($dirty) {
            $socialPost->save();
        }
    }

    protected function upsertSocialPost(ProviderConnection $connection, array $item): SocialPost
    {
        $providerMediaId = (string) ($item['id'] ?? '');

        if ($providerMediaId === '') {
            throw new RuntimeException('Instagram media item is missing id.');
        }

        $socialPost = SocialPost::query()->firstOrNew([
            'provider' => 'instagram',
            'provider_media_id' => $providerMediaId,
        ]);

        $socialPost->workspace_id = $connection->workspace_id;
        $socialPost->provider_connection_id = $connection->id;
        $socialPost->provider = 'instagram';
        $socialPost->provider_media_id = $providerMediaId;
        $socialPost->media_type = $this->normalizeMediaType($item);
        $socialPost->caption = $this->normalizeNullableString($item['caption'] ?? null);
        $socialPost->permalink = $this->normalizeNullableString($item['permalink'] ?? null);
        $socialPost->media_url = $this->normalizeNullableString($item['media_url'] ?? null);
        $socialPost->thumbnail_url = $this->normalizeNullableString($item['thumbnail_url'] ?? null);
        $socialPost->posted_at = $this->normalizeTimestamp($item['timestamp'] ?? null);
        $socialPost->comments_count = (int) ($item['comments_count'] ?? 0);
        $socialPost->like_count = (int) ($item['like_count'] ?? 0);
        $socialPost->status = 'active';
        $socialPost->raw = $item;
        $socialPost->save();

        return $socialPost;
    }

    protected function normalizeMediaType(array $item): ?string
    {
        $mediaType = $this->normalizeNullableString($item['media_type'] ?? null);

        return $mediaType !== null ? strtoupper($mediaType) : null;
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function normalizeTimestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            $timestamp = (string) $value;

            if (strlen($timestamp) >= 13) {
                return Carbon::createFromTimestampMs((int) $timestamp);
            }

            return Carbon::createFromTimestamp((int) $timestamp);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
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
