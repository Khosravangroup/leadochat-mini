<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Support\ProviderSecretRedactor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramCommentService
{
    public function fetchMediaComments(ProviderConnection $connection, string $mediaId, array $options = []): array
    {
        $accessToken = $this->resolveAccessToken($connection);

        $fields = $options['fields']
            ?? 'id,text,from{id,username},username,timestamp,parent_id,hidden,like_count,replies{id,text,from{id,username},username,timestamp,parent_id,hidden}';

        $query = [
            'fields' => $fields,
            'limit' => $options['limit'] ?? 50,
            'access_token' => $accessToken,
        ];

        if (! empty($options['after'])) {
            $query['after'] = $options['after'];
        }

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$mediaId}/comments";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'query' => ProviderSecretRedactor::payload($query, [$accessToken]),
                'data' => [
                    [
                        'id' => 'local-debug-comment-1',
                        'text' => 'This is local debug comment 1',
                        'username' => 'debug_user_1',
                        'timestamp' => now()->subMinutes(15)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 1,
                    ],
                    [
                        'id' => 'local-debug-comment-2',
                        'text' => 'This is local debug comment 2',
                        'username' => 'debug_user_2',
                        'timestamp' => now()->subMinutes(14)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 2,
                        'replies' => [
                            'data' => [
                                [
                                    'id' => 'local-debug-comment-2-reply-1',
                                    'text' => 'Local debug reply for comment 2',
                                    'username' => 'debug_reply_user',
                                    'timestamp' => now()->subMinutes(13)->toIso8601String(),
                                    'parent_id' => 'local-debug-comment-2',
                                    'hidden' => false,
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'local-debug-comment-3',
                        'text' => 'This is local debug comment 3',
                        'username' => 'debug_user_3',
                        'timestamp' => now()->subMinutes(12)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-4',
                        'text' => 'This is local debug comment 4',
                        'username' => 'debug_user_4',
                        'timestamp' => now()->subMinutes(11)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-5',
                        'text' => 'This is local debug comment 5',
                        'username' => 'debug_user_5',
                        'timestamp' => now()->subMinutes(10)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-6',
                        'text' => 'This is local debug comment 6',
                        'username' => 'debug_user_6',
                        'timestamp' => now()->subMinutes(9)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-7',
                        'text' => 'This is local debug comment 7',
                        'username' => 'debug_user_7',
                        'timestamp' => now()->subMinutes(8)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-8',
                        'text' => 'This is local debug comment 8',
                        'username' => 'debug_user_8',
                        'timestamp' => now()->subMinutes(7)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-9',
                        'text' => 'This is local debug comment 9',
                        'username' => 'debug_user_9',
                        'timestamp' => now()->subMinutes(6)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-10',
                        'text' => 'This is local debug comment 10',
                        'username' => 'debug_user_10',
                        'timestamp' => now()->subMinutes(5)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-11',
                        'text' => 'This is local debug comment 11',
                        'username' => 'debug_user_11',
                        'timestamp' => now()->subMinutes(4)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-12',
                        'text' => 'This is local debug comment 12',
                        'username' => 'debug_user_12',
                        'timestamp' => now()->subMinutes(3)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-13',
                        'text' => 'This is local debug comment 13',
                        'username' => 'debug_user_13',
                        'timestamp' => now()->subMinutes(2)->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-14',
                        'text' => 'This is local debug comment 14',
                        'username' => 'debug_user_14',
                        'timestamp' => now()->subMinute()->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                    [
                        'id' => 'local-debug-comment-15',
                        'text' => 'This is local debug comment 15',
                        'username' => 'debug_user_15',
                        'timestamp' => now()->toIso8601String(),
                        'parent_id' => null,
                        'hidden' => false,
                        'like_count' => 0,
                    ],
                ],
                'paging' => [],
            ];
        }

        $response = Http::acceptJson()->get($endpoint, $query);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram fetchMediaComments failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$accessToken]
            ));
        }

        return $response->json();
    }

    public function syncMediaComments(
        ProviderConnection $connection,
        SocialPost $socialPost,
        array $options = []
    ): array {
        $feed = $this->fetchMediaComments($connection, $socialPost->provider_media_id, $options);
        $items = collect($feed['data'] ?? []);
        $synced = [];

        DB::transaction(function () use ($connection, $socialPost, $items, &$synced) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $synced[] = $this->upsertComment($connection, $socialPost, $item);

                $replyItems = $item['replies']['data'] ?? [];
                if (is_array($replyItems) && ! empty($replyItems)) {
                    foreach ($replyItems as $replyItem) {
                        if (! is_array($replyItem)) {
                            continue;
                        }

                        $synced[] = $this->upsertComment($connection, $socialPost, $replyItem, $item['id'] ?? null);
                    }
                }
            }
        });

        return [
            'mode' => $feed['mode'] ?? (app()->environment('local') ? 'local_debug' : 'live'),
            'count' => count($synced),
            'items' => $synced,
            'paging' => $feed['paging'] ?? [],
        ];
    }

    public function replyToComment(
        ProviderConnection $connection,
        string $commentId,
        string $text,
        array $options = []
    ): array {
        $accessToken = $this->resolveAccessToken($connection);
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Instagram comment reply text cannot be empty.');
        }

        $payload = [
            'message' => $text,
            'access_token' => $accessToken,
        ];

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$commentId}/replies";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => ProviderSecretRedactor::payload($payload, [$accessToken]),
                'id' => 'local-debug-comment-reply-'.now()->timestamp,
            ];
        }

        $response = Http::asForm()->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram replyToComment failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$accessToken]
            ));
        }

        return $response->json();
    }

    public function hideComment(ProviderConnection $connection, string $commentId): array
    {
        return $this->setCommentHiddenState($connection, $commentId, true);
    }

    public function unhideComment(ProviderConnection $connection, string $commentId): array
    {
        return $this->setCommentHiddenState($connection, $commentId, false);
    }

    public function deleteComment(ProviderConnection $connection, string $commentId): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$commentId}";

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
            throw new RuntimeException('Instagram deleteComment failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$accessToken]
            ));
        }

        return $response->json();
    }

    protected function upsertComment(
        ProviderConnection $connection,
        SocialPost $socialPost,
        array $item,
        ?string $forcedParentId = null
    ): array {
        $providerCommentId = (string) ($item['id'] ?? '');

        if ($providerCommentId === '') {
            throw new RuntimeException('Instagram comment item is missing id.');
        }

        $comment = SocialComment::query()->firstOrNew([
            'provider' => 'instagram',
            'provider_comment_id' => $providerCommentId,
        ]);

        $comment->workspace_id = $connection->workspace_id;
        $comment->provider_connection_id = $connection->id;
        $comment->social_post_id = $socialPost->id;
        $comment->provider = 'instagram';
        $comment->provider_media_id = $socialPost->provider_media_id;
        $comment->provider_comment_id = $providerCommentId;
        $comment->parent_provider_comment_id = $this->normalizeNullableString($forcedParentId ?? ($item['parent_id'] ?? null));
        $comment->provider_user_id = $this->resolveProviderUserId($item, $providerCommentId);
        $comment->username = $this->normalizeNullableString($item['username'] ?? ($item['from']['username'] ?? null));
        $comment->text = $this->normalizeNullableString($item['text'] ?? null);
        $comment->status = $comment->exists && $comment->status === 'deleted'
            ? 'deleted'
            : 'active';
        $comment->is_hidden = $this->resolveHiddenState($comment, (bool) ($item['hidden'] ?? false));
        $comment->commented_at = $this->normalizeTimestamp($item['timestamp'] ?? null);
        $existingRaw = is_array($comment->raw) ? $comment->raw : [];
        $profile = $this->fetchCommentAuthorProfileIfAvailable($connection, $comment, $item, $existingRaw);

        $commentRaw = array_merge(
            [
                'reply_actor_id' => $existingRaw['reply_actor_id'] ?? null,
                'reply_actor_name' => $existingRaw['reply_actor_name'] ?? null,
                'reply_actor_email' => $existingRaw['reply_actor_email'] ?? null,
                'last_public_reply_text' => $existingRaw['last_public_reply_text'] ?? null,
                'last_public_reply_at' => $existingRaw['last_public_reply_at'] ?? null,
                'last_dm_reply_text' => $existingRaw['last_dm_reply_text'] ?? null,
                'last_dm_reply_at' => $existingRaw['last_dm_reply_at'] ?? null,
                'last_dm_reply_inbox_conversation_id' => $existingRaw['last_dm_reply_inbox_conversation_id'] ?? null,
                'last_dm_reply_inbox_message_id' => $existingRaw['last_dm_reply_inbox_message_id'] ?? null,
                'last_dm_reply_recipient_id' => $existingRaw['last_dm_reply_recipient_id'] ?? null,
                'profile_pic' => $profile['profile_pic']
                    ?? $existingRaw['profile_pic']
                    ?? $existingRaw['profile_picture_url']
                    ?? null,
                'profile_picture_url' => $profile['profile_picture_url']
                    ?? $profile['profile_pic']
                    ?? $existingRaw['profile_picture_url']
                    ?? $existingRaw['profile_pic']
                    ?? null,
                'profile_fetch_failed_at' => $profile['profile_fetch_failed_at']
                    ?? $existingRaw['profile_fetch_failed_at']
                    ?? null,
            ],
            $item
        );

        $commentRaw['profile_pic'] = $profile['profile_pic']
            ?? $commentRaw['profile_pic']
            ?? $commentRaw['profile_picture_url']
            ?? null;
        $commentRaw['profile_picture_url'] = $profile['profile_picture_url']
            ?? $profile['profile_pic']
            ?? $commentRaw['profile_picture_url']
            ?? $commentRaw['profile_pic']
            ?? null;
        $commentRaw['profile_fetch_failed_at'] = $profile['profile_fetch_failed_at']
            ?? $commentRaw['profile_fetch_failed_at']
            ?? null;

        $comment->raw = $commentRaw;
        $comment->save();

        return [
            'id' => $comment->id,
            'provider_comment_id' => $comment->provider_comment_id,
            'parent_provider_comment_id' => $comment->parent_provider_comment_id,
            'username' => $comment->username,
            'text' => $comment->text,
            'is_hidden' => $comment->is_hidden,
            'commented_at' => optional($comment->commented_at)?->toDateTimeString(),
        ];
    }

    protected function setCommentHiddenState(ProviderConnection $connection, string $commentId, bool $hidden): array
    {
        $accessToken = $this->resolveAccessToken($connection);

        $payload = [
            'hide' => $hidden ? 'true' : 'false',
            'access_token' => $accessToken,
        ];

        $endpoint = "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$commentId}";

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'endpoint' => $endpoint,
                'payload' => ProviderSecretRedactor::payload($payload, [$accessToken]),
                'success' => true,
            ];
        }

        $response = Http::asForm()->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram setCommentHiddenState failed: '.ProviderSecretRedactor::text(
                $response->body(),
                [$accessToken]
            ));
        }

        return $response->json();
    }

    protected function resolveProviderUserId(array $item, string $providerCommentId): ?string
    {
        $providerUserId = $this->normalizeNullableString($item['from']['id'] ?? null);

        if ($providerUserId !== null) {
            return $providerUserId;
        }

        if (! app()->environment('local')) {
            return null;
        }

        $username = $this->normalizeNullableString($item['username'] ?? ($item['from']['username'] ?? null));

        if ($username !== null) {
            return 'local-debug-comment-author-'.sha1($username);
        }

        return 'local-debug-comment-author-'.sha1($providerCommentId);
    }

    protected function fetchCommentAuthorProfileIfAvailable(
        ProviderConnection $connection,
        SocialComment $comment,
        array $item,
        array $existingRaw
    ): array {
        $existingAvatar = $this->normalizeNullableString(
            $existingRaw['profile_pic']
            ?? $existingRaw['profile_picture_url']
            ?? $item['profile_pic']
            ?? $item['profile_picture_url']
            ?? $item['from']['profile_pic']
            ?? $item['from']['profile_picture_url']
            ?? null
        );

        if ($existingAvatar !== null) {
            return [
                'profile_pic' => $existingAvatar,
                'profile_picture_url' => $existingAvatar,
            ];
        }

        $failedAt = $this->normalizeTimestamp($existingRaw['profile_fetch_failed_at'] ?? null);
        if ($failedAt && $failedAt->greaterThan(now()->subHours(12))) {
            return [
                'profile_fetch_failed_at' => $failedAt->toIso8601String(),
            ];
        }

        $providerUserId = $this->normalizeNullableString($comment->provider_user_id ?? null);

        if ($providerUserId === null) {
            return [];
        }

        if (app()->environment('local')) {
            return [
                'profile_pic' => 'https://ui-avatars.com/api/?name='.urlencode($comment->username ?: 'Instagram user').'&background=e2e8f0&color=334155',
            ];
        }

        try {
            $response = Http::withToken($this->resolveAccessToken($connection))
                ->acceptJson()
                ->get("https://graph.instagram.com/{$this->resolveGraphVersion()}/{$providerUserId}", [
                    'fields' => 'id,username,name,profile_pic',
                ]);

            if (! $response->successful()) {
                return [
                    'profile_fetch_failed_at' => now()->toIso8601String(),
                ];
            }

            $profile = $response->json();
            $avatar = $this->normalizeNullableString($profile['profile_pic'] ?? null);

            return $avatar !== null
                ? [
                    'profile_pic' => $avatar,
                    'profile_picture_url' => $avatar,
                ]
                : [];
        } catch (\Throwable) {
            return [
                'profile_fetch_failed_at' => now()->toIso8601String(),
            ];
        }
    }

    protected function resolveHiddenState(SocialComment $comment, bool $providerHidden): bool
    {
        if (! app()->environment('local')) {
            return $providerHidden;
        }

        return ($comment->exists && (bool) $comment->is_hidden) || $providerHidden;
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

    protected function resolveGraphVersion(): string
    {
        return (string) config('services.instagram.graph_version', 'v25.0');
    }
}
