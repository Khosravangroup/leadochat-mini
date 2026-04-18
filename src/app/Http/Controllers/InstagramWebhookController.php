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
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

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

            $changes = collect($entry['changes'] ?? []);

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
        $secret = (string) config('services.instagram.app_secret');

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

        return ProviderConnection::query()
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereIn('provider_account_id', $candidates->all())
            ->latest('id')
            ->first();
    }

    protected function buildProviderEventId(array $payload, array $entry, array $change): string
    {
        $parts = [
            (string) ($payload['object'] ?? 'instagram'),
            (string) ($entry['id'] ?? 'entry'),
            (string) ($change['field'] ?? 'change'),
            (string) (Arr::get($change, 'value.mid') ?? Arr::get($change, 'value.message.mid') ?? Arr::get($change, 'value.comment_id') ?? Arr::get($change, 'value.id') ?? Arr::get($change, 'value.post_id') ?? ''),
            (string) ($entry['time'] ?? now()->timestamp),
        ];

        return implode(':', $parts);
    }
}
