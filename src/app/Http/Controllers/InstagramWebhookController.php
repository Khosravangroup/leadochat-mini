<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramWebhookEvent;
use App\Models\WebhookEvent;
use App\Models\ProviderConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class InstagramWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if (
            $mode === 'subscribe' &&
            is_string($token) &&
            hash_equals((string) config('services.instagram.webhook_verify_token'), $token)
        ) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        abort(403, 'Invalid webhook verification request.');
    }

    public function receive(Request $request): JsonResponse
    {
        $this->validateSignature($request);

        $payload = $request->all();
        $entries = collect($payload['entry'] ?? []);
        $createdEventIds = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $changes = collect($entry['changes'] ?? [])
                ->merge($this->buildEntryMessagingChanges($entry));

            if ($changes->isEmpty()) {
                $providerConnection = $this->resolveProviderConnectionFromChange($payload, $entry, []);
                $providerEventId = $this->buildProviderEventId($payload, $entry, []);

                $event = WebhookEvent::query()->firstOrCreate(
                    [
                        'provider' => 'instagram',
                        'provider_event_id' => $providerEventId,
                    ],
                    [
                        'workspace_id' => $providerConnection?->workspace_id,
                        'provider_connection_id' => $providerConnection?->id,
                        'event_type' => 'entry',
                        'object' => $payload['object'] ?? null,
                        'status' => 'received',
                        'source' => 'webhook',
                        'headers' => $request->headers->all(),
                        'payload' => [
                            'object' => $payload['object'] ?? null,
                            'entry' => $entry,
                        ],
                        'last_error' => null,
                        'processed_at' => null,
                    ]
                );

                if ($event->wasRecentlyCreated) {
                    ProcessInstagramWebhookEvent::dispatch($event->id);
                }

                $createdEventIds[] = $event->id;
                continue;
            }

            foreach ($changes as $change) {
                if (!is_array($change)) {
                    continue;
                }

                $providerConnection = $this->resolveProviderConnectionFromChange($payload, $entry, $change);
                $providerEventId = $this->buildProviderEventId($payload, $entry, $change);
                $eventType = (string) ($change['field'] ?? 'unknown');

                $event = WebhookEvent::query()->firstOrCreate(
                    [
                        'provider' => 'instagram',
                        'provider_event_id' => $providerEventId,
                    ],
                    [
                        'workspace_id' => $providerConnection?->workspace_id,
                        'provider_connection_id' => $providerConnection?->id,
                        'event_type' => $eventType,
                        'object' => $payload['object'] ?? null,
                        'status' => 'received',
                        'source' => 'webhook',
                        'headers' => $request->headers->all(),
                        'payload' => [
                            'object' => $payload['object'] ?? null,
                            'entry' => $entry,
                            'change' => $change,
                        ],
                        'last_error' => null,
                        'processed_at' => null,
                    ]
                );

                if ($event->wasRecentlyCreated) {
                    ProcessInstagramWebhookEvent::dispatch($event->id);
                }

                $createdEventIds[] = $event->id;
            }
        }

        return response()->json([
            'status' => 'received',
            'message' => 'Instagram webhook payload stored and queued successfully.',
            'webhook_event_ids' => collect($createdEventIds)->unique()->values()->all(),
        ]);
    }

    protected function validateSignature(Request $request): void
    {
        $secret = (string) config('services.instagram.webhook_app_secret');

        if ($secret === '' || app()->environment('local')) {
            return;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if ($signature === '' || !str_starts_with($signature, 'sha256=')) {
            abort(403, 'Missing or invalid webhook signature header.');
        }

        $rawBody = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Webhook signature validation failed.');
        }
    }

    protected function resolveProviderConnectionFromChange(array $payload, array $entry, array $change): ?ProviderConnection
    {
        $candidates = collect([
            Arr::get($change, 'value.metadata.instagram_account_id'),
            Arr::get($change, 'value.metadata.phone_number_id'),
            Arr::get($change, 'value.recipient.id'),
            Arr::get($change, 'value.messaging.0.recipient.id'),
            Arr::get($change, 'value.messaging.0.sender.id'),
            Arr::get($change, 'value.id'),
            Arr::get($entry, 'id'),
            Arr::get($payload, 'id'),
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $connection = ProviderConnection::query()
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereIn('provider_account_id', $candidates->all())
            ->latest('id')
            ->first();

        if ($connection) {
            return $connection;
        }

        $connectedConnections = ProviderConnection::query()
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->limit(2)
            ->get();

        return $connectedConnections->count() === 1
            ? $connectedConnections->first()
            : null;
    }

    protected function buildProviderEventId(array $payload, array $entry, array $change): string
    {
        $parts = [
            (string) ($payload['object'] ?? 'instagram'),
            (string) ($entry['id'] ?? 'entry'),
            (string) ($change['field'] ?? 'change'),
            (string) (
                Arr::get($change, 'value.mid')
                ?? Arr::get($change, 'value.message.mid')
                ?? Arr::get($change, 'value.message.id')
                ?? Arr::get($change, 'value.messaging.0.message.mid')
                ?? Arr::get($change, 'value.messaging.0.message.id')
                ?? Arr::get($change, 'value.messages.0.mid')
                ?? Arr::get($change, 'value.messages.0.id')
                ?? Arr::get($change, 'value.comment_id')
                ?? Arr::get($change, 'value.id')
                ?? Arr::get($change, 'value.post_id')
                ?? ''
            ),
            (string) (Arr::get($change, 'value.timestamp') ?? Arr::get($change, 'value.messaging.0.timestamp') ?? $entry['time'] ?? now()->timestamp),
        ];

        return implode(':', $parts);
    }

    protected function buildEntryMessagingChanges(array $entry): array
    {
        $messagingItems = Arr::get($entry, 'messaging', []);

        if (!is_array($messagingItems) || empty($messagingItems)) {
            return [];
        }

        return collect($messagingItems)
            ->filter(fn ($messagingItem) => is_array($messagingItem))
            ->map(fn (array $messagingItem) => [
                'field' => 'messages',
                'value' => [
                    'messaging' => [$messagingItem],
                    'sender' => Arr::get($messagingItem, 'sender', []),
                    'recipient' => Arr::get($messagingItem, 'recipient', []),
                    'message' => Arr::get($messagingItem, 'message', []),
                    'timestamp' => Arr::get($messagingItem, 'timestamp') ?? Arr::get($entry, 'time'),
                ],
            ])
            ->values()
            ->all();
    }
}
