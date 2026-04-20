<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramMessagingService
{
    public function sendTextMessage(
        ProviderConnection $connection,
        string $recipientId,
        string $text,
        array $options = []
    ): array {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Instagram text message cannot be empty.');
        }

        $payload = [
            'recipient' => [
                'id' => $recipientId,
            ],
            'message' => [
                'text' => $text,
            ],
            'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
        ];

        if (!empty($options['tag'])) {
            $payload['tag'] = $options['tag'];
        }

        return $this->performSend($connection, $payload, 'text');
    }

    public function sendPrivateReplyToComment(
        ProviderConnection $connection,
        string $commentId,
        string $text,
        array $options = []
    ): array {
        $commentId = trim($commentId);
        $text = trim($text);

        if ($commentId === '') {
            throw new RuntimeException('Instagram comment id cannot be empty for private replies.');
        }

        if ($text === '') {
            throw new RuntimeException('Instagram private reply text cannot be empty.');
        }

        $payload = [
            'recipient' => [
                'comment_id' => $commentId,
            ],
            'message' => [
                'text' => $text,
            ],
        ];

        return $this->performSend($connection, $payload, 'comment_private_reply');
    }

    public function sendAttachment(
        ProviderConnection $connection,
        string $recipientId,
        string $attachmentUrl,
        array $options = []
    ): array {
        $attachmentUrl = trim($attachmentUrl);

        if ($attachmentUrl === '') {
            throw new RuntimeException('Instagram attachment URL cannot be empty.');
        }

        $attachmentType = (string) ($options['attachment_type'] ?? 'image');
        $isReusable = (bool) ($options['is_reusable'] ?? false);

        $payload = [
            'recipient' => [
                'id' => $recipientId,
            ],
            'message' => [
                'attachment' => [
                    'type' => $attachmentType,
                    'payload' => [
                        'url' => $attachmentUrl,
                        'is_reusable' => $isReusable,
                    ],
                ],
            ],
            'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
        ];

        if (!empty($options['tag'])) {
            $payload['tag'] = $options['tag'];
        }

        return $this->performSend($connection, $payload, 'attachment');
    }

    public function fetchUserProfile(ProviderConnection $connection, string $instagramScopedUserId): array
    {
        $instagramScopedUserId = trim($instagramScopedUserId);

        if ($instagramScopedUserId === '') {
            throw new RuntimeException('Instagram scoped user id cannot be empty.');
        }

        if (app()->environment('local')) {
            return [
                'id' => $instagramScopedUserId,
                'username' => null,
                'name' => null,
                'profile_pic' => null,
                'mode' => 'local_debug',
            ];
        }

        $response = Http::withToken($this->resolveAccessToken($connection))
            ->acceptJson()
            ->get("https://graph.instagram.com/{$this->resolveGraphVersion()}/{$instagramScopedUserId}", [
                'fields' => 'id,username,name,profile_pic',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram user profile fetch failed: ' . $response->body());
        }

        $profile = $response->json();

        return is_array($profile) ? $profile : [];
    }

    protected function performSend(ProviderConnection $connection, array $payload, string $kind): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $endpoint = $this->resolveMessagesEndpoint($connection);

        if (app()->environment('local')) {
            $recipientId = Arr::get($payload, 'recipient.id')
                ?: (Arr::get($payload, 'recipient.comment_id')
                    ? 'local-debug-comment-author-' . sha1((string) Arr::get($payload, 'recipient.comment_id'))
                    : null);
            $messageId = 'local-debug-instagram-msg-' . now()->timestamp;

            return [
                'mode' => 'local_debug',
                'kind' => $kind,
                'endpoint' => $endpoint,
                'payload' => $payload,
                'recipient_id' => $recipientId,
                'message_id' => $messageId,
                'mock_response' => [
                    'recipient_id' => $recipientId,
                    'message_id' => $messageId,
                ],
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Instagram ' . $kind . ' send failed: ' . $response->body());
        }

        $result = $response->json();

        return [
            'mode' => 'live',
            'kind' => $kind,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'response' => $result,
            'recipient_id' => Arr::get($result, 'recipient_id'),
            'message_id' => Arr::get($result, 'message_id'),
        ];
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

    protected function resolveMessagesEndpoint(ProviderConnection $connection): string
    {
        $accountId = (string) ($connection->provider_account_id ?? '');

        if ($accountId === '') {
            throw new RuntimeException('Instagram provider_account_id is empty.');
        }

        return "https://graph.instagram.com/{$this->resolveGraphVersion()}/{$accountId}/messages";
    }

    protected function resolveGraphVersion(): string
    {
        return (string) config('services.instagram.graph_version', 'v25.0');
    }
}
