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

    protected function performSend(ProviderConnection $connection, array $payload, string $kind): array
    {
        $accessToken = $this->resolveAccessToken($connection);
        $endpoint = $this->resolveMessagesEndpoint($connection);

        if (app()->environment('local')) {
            return [
                'mode' => 'local_debug',
                'kind' => $kind,
                'endpoint' => $endpoint,
                'payload' => $payload,
                'mock_response' => [
                    'recipient_id' => Arr::get($payload, 'recipient.id'),
                    'message_id' => 'local-debug-instagram-msg-' . now()->timestamp,
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

        return "https://graph.facebook.com/v23.0/{$accountId}/messages";
    }
}