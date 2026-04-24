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

    public function sendGenericTemplate(
        ProviderConnection $connection,
        string $recipientId,
        array $elements,
        array $options = []
    ): array {
        $recipientId = trim($recipientId);

        if ($recipientId === '') {
            throw new RuntimeException('Instagram recipient id cannot be empty for generic templates.');
        }

        $elements = $this->normalizeGenericTemplateElements($elements);

        if ($elements === []) {
            throw new RuntimeException('Instagram generic template must include at least one element.');
        }

        $payload = [
            'recipient' => [
                'id' => $recipientId,
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements,
                    ],
                ],
            ],
            'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
        ];

        if (!empty($options['tag'])) {
            $payload['tag'] = $options['tag'];
        }

        return $this->performSend($connection, $payload, 'generic_template');
    }

    public function sendReaction(
        ProviderConnection $connection,
        string $recipientId,
        string $providerMessageId,
        string $reaction = 'love',
        string $action = 'react'
    ): array {
        $recipientId = trim($recipientId);
        $providerMessageId = trim($providerMessageId);
        $reaction = trim($reaction) ?: 'love';
        $action = $action === 'unreact' ? 'unreact' : 'react';

        if ($recipientId === '') {
            throw new RuntimeException('Instagram recipient id cannot be empty for message reaction.');
        }

        if ($providerMessageId === '') {
            throw new RuntimeException('Instagram provider message id cannot be empty for message reaction.');
        }

        $payload = [
            'recipient' => [
                'id' => $recipientId,
            ],
            'sender_action' => $action,
            'payload' => [
                'message_id' => $providerMessageId,
                'reaction' => $reaction,
            ],
        ];

        return $this->performSend($connection, $payload, 'reaction');
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

    protected function normalizeGenericTemplateElements(array $elements): array
    {
        $normalized = [];

        foreach (array_slice($elements, 0, 10) as $element) {
            if (! is_array($element)) {
                continue;
            }

            $title = $this->trimTemplateText((string) ($element['title'] ?? ''), 80);

            if ($title === '') {
                continue;
            }

            $item = [
                'title' => $title,
            ];

            $subtitle = $this->trimTemplateText((string) ($element['subtitle'] ?? ''), 80);
            if ($subtitle !== '') {
                $item['subtitle'] = $subtitle;
            }

            $imageUrl = trim((string) ($element['image_url'] ?? ''));
            if ($imageUrl !== '') {
                $item['image_url'] = $imageUrl;
            }

            $defaultAction = $this->normalizeTemplateWebUrlAction($element['default_action'] ?? null);
            if ($defaultAction !== null) {
                $item['default_action'] = $defaultAction;
            }

            $buttons = $this->normalizeTemplateButtons($element['buttons'] ?? []);
            if ($buttons !== []) {
                $item['buttons'] = $buttons;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    protected function normalizeTemplateButtons(mixed $buttons): array
    {
        if (! is_array($buttons)) {
            return [];
        }

        $normalized = [];

        foreach (array_slice($buttons, 0, 3) as $button) {
            if (! is_array($button)) {
                continue;
            }

            $type = trim((string) ($button['type'] ?? ''));
            $title = $this->trimTemplateText((string) ($button['title'] ?? ''), 20);

            if ($type === '' || $title === '') {
                continue;
            }

            if ($type === 'web_url') {
                $url = trim((string) ($button['url'] ?? ''));

                if ($url === '') {
                    continue;
                }

                $normalized[] = [
                    'type' => 'web_url',
                    'url' => $url,
                    'title' => $title,
                ];
            }
        }

        return $normalized;
    }

    protected function normalizeTemplateWebUrlAction(mixed $action): ?array
    {
        if (! is_array($action)) {
            return null;
        }

        $url = trim((string) ($action['url'] ?? ''));

        if ($url === '') {
            return null;
        }

        return [
            'type' => 'web_url',
            'url' => $url,
        ];
    }

    protected function trimTemplateText(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?: '');

        if ($value === '' || mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 3))) . '...';
    }
}
