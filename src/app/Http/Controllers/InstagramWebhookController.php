<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramWebhookEvent;
use App\Models\WebhookEvent;
use App\Models\ProviderConnection;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $tokenMatches = is_string($token) &&
            hash_equals((string) config('services.instagram.webhook_verify_token'), $token);

        $this->logWebhook('verification_request', [
            'mode' => $mode,
            'token_matches' => $tokenMatches,
            'challenge_present' => filled($challenge),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (
            $mode === 'subscribe' &&
            $tokenMatches
        ) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        abort(403, 'Invalid webhook verification request.');
    }

    public function receive(Request $request): JsonResponse
    {
        $this->logWebhook('receive_started', [
            'signature_present' => filled($request->header('X-Hub-Signature-256')),
            'body_sha256' => hash('sha256', $request->getContent()),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'object' => $request->input('object'),
            'entry_count' => is_array($request->input('entry')) ? count($request->input('entry')) : 0,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

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

            $this->logWebhook('entry_parsed', [
                'entry_id' => Arr::get($entry, 'id'),
                'entry_time' => Arr::get($entry, 'time'),
                'change_count' => $changes->count(),
                'raw_change_count' => is_array(Arr::get($entry, 'changes')) ? count(Arr::get($entry, 'changes')) : 0,
                'messaging_count' => is_array(Arr::get($entry, 'messaging')) ? count(Arr::get($entry, 'messaging')) : 0,
                'standby_count' => is_array(Arr::get($entry, 'standby')) ? count(Arr::get($entry, 'standby')) : 0,
            ], 'debug');

            if ($changes->isEmpty()) {
                $candidateIds = $this->resolveProviderConnectionCandidateIds($payload, $entry, []);
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

                $this->logWebhook('event_stored', [
                    'event_id' => $event->id,
                    'event_type' => 'entry',
                    'provider_event_id' => $providerEventId,
                    'was_recently_created' => $event->wasRecentlyCreated,
                    'queued' => $event->wasRecentlyCreated,
                    'provider_connection_id' => $providerConnection?->id,
                    'workspace_id' => $providerConnection?->workspace_id,
                    'candidate_ids' => $candidateIds,
                ]);

                $createdEventIds[] = $event->id;
                continue;
            }

            foreach ($changes as $change) {
                if (!is_array($change)) {
                    continue;
                }

                $candidateIds = $this->resolveProviderConnectionCandidateIds($payload, $entry, $change);
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

                $this->logWebhook('event_stored', [
                    'event_id' => $event->id,
                    'event_type' => $eventType,
                    'provider_event_id' => $providerEventId,
                    'was_recently_created' => $event->wasRecentlyCreated,
                    'queued' => $event->wasRecentlyCreated,
                    'provider_connection_id' => $providerConnection?->id,
                    'workspace_id' => $providerConnection?->workspace_id,
                    'candidate_ids' => $candidateIds,
                    'message_mid' => Arr::get($change, 'value.message.mid')
                        ?? Arr::get($change, 'value.messaging.0.message.mid')
                        ?? Arr::get($change, 'value.messaging.0.read.mid')
                        ?? Arr::get($change, 'value.messaging.0.message_edit.mid')
                        ?? Arr::get($change, 'value.messages.0.mid'),
                    'sender_id' => Arr::get($change, 'value.sender.id')
                        ?? Arr::get($change, 'value.messaging.0.sender.id'),
                    'recipient_id' => Arr::get($change, 'value.recipient.id')
                        ?? Arr::get($change, 'value.messaging.0.recipient.id'),
                ]);

                $createdEventIds[] = $event->id;
            }
        }

        $this->logWebhook('receive_completed', [
            'created_event_ids' => collect($createdEventIds)->unique()->values()->all(),
        ]);

        return response()->json([
            'status' => 'received',
            'message' => 'Instagram webhook payload stored and queued successfully.',
            'webhook_event_ids' => collect($createdEventIds)->unique()->values()->all(),
        ]);
    }

    protected function validateSignature(Request $request): void
    {
        $secretCandidates = $this->resolveSignatureSecretCandidates();

        if ($secretCandidates === [] || app()->environment('local')) {
            $this->logWebhook('signature_skipped', [
                'reason' => $secretCandidates === [] ? 'missing_secret' : 'local_environment',
            ], 'warning');

            return;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if ($signature === '' || !str_starts_with($signature, 'sha256=')) {
            $this->logWebhook('signature_failed', [
                'reason' => 'missing_or_invalid_header',
                'body_sha256' => hash('sha256', $request->getContent()),
            ], 'warning');

            abort(403, 'Missing or invalid webhook signature header.');
        }

        $rawBody = $request->getContent();
        $bodyHash = hash('sha256', $rawBody);

        foreach ($secretCandidates as $label => $secret) {
            $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

            if (hash_equals($expected, $signature)) {
                $this->logWebhook('signature_valid', [
                    'body_sha256' => $bodyHash,
                    'secret_label' => $label,
                ], 'debug');

                return;
            }
        }

        $this->logWebhook('signature_failed', [
            'reason' => 'mismatch',
            'body_sha256' => $bodyHash,
            'candidate_secret_labels' => array_keys($secretCandidates),
        ], 'warning');

        abort(403, 'Webhook signature validation failed.');
    }

    /**
     * @return array<string, string>
     */
    protected function resolveSignatureSecretCandidates(): array
    {
        $rawCandidates = [
            'webhook_app_secret' => config('services.instagram.webhook_app_secret'),
            'app_secret' => config('services.instagram.app_secret'),
            'client_secret' => config('services.instagram.client_secret'),
        ];

        $candidates = [];
        $seen = [];

        foreach ($rawCandidates as $label => $secret) {
            if (! is_string($secret)) {
                continue;
            }

            $secret = trim($secret);

            if ($secret === '') {
                continue;
            }

            $fingerprint = hash('sha256', $secret);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $candidates[$label] = $secret;
            $seen[$fingerprint] = true;
        }

        return $candidates;
    }

    protected function resolveProviderConnectionFromChange(array $payload, array $entry, array $change): ?ProviderConnection
    {
        $candidates = $this->resolveProviderConnectionCandidateIds($payload, $entry, $change);

        if (empty($candidates)) {
            return null;
        }

        $connection = ProviderConnection::query()
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->where(function ($query) use ($candidates) {
                $query->whereIn('provider_account_id', $candidates)
                    ->orWhereIn('external_oauth_user_id', $candidates);
            })
            ->latest('id')
            ->first();

        if ($connection) {
            return $connection;
        }

        $providerMediaId = collect([
            Arr::get($change, 'value.media.id'),
            Arr::get($change, 'value.media_id'),
            Arr::get($change, 'value.media.media_id'),
            Arr::get($change, 'value.post_id'),
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->first();

        if ($providerMediaId) {
            $resolvedPost = SocialPost::query()
                ->where('provider', 'instagram')
                ->where('provider_media_id', $providerMediaId)
                ->where('status', '!=', 'deleted')
                ->latest('id')
                ->first();

            if ($resolvedPost?->provider_connection_id) {
                return ProviderConnection::query()
                    ->where('provider', 'instagram')
                    ->where('status', 'connected')
                    ->whereKey($resolvedPost->provider_connection_id)
                    ->first();
            }
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

    protected function resolveProviderConnectionCandidateIds(array $payload, array $entry, array $change): array
    {
        return collect([
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
            ->values()
            ->all();
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
                ?? Arr::get($change, 'value.messaging.0.read.mid')
                ?? Arr::get($change, 'value.messaging.0.message_edit.mid')
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
        return collect([
            'messages' => Arr::get($entry, 'messaging', []),
            'standby' => Arr::get($entry, 'standby', []),
        ])->flatMap(function ($items, string $field) use ($entry) {
            if (! is_array($items) || empty($items)) {
                return [];
            }

            return collect($items)
                ->filter(fn ($messagingItem) => is_array($messagingItem))
                ->map(fn (array $messagingItem) => [
                    'field' => $field,
                    'value' => [
                        'messaging' => [$messagingItem],
                        'sender' => Arr::get($messagingItem, 'sender', []),
                        'recipient' => Arr::get($messagingItem, 'recipient', []),
                        'message' => Arr::get($messagingItem, 'message', []),
                        'timestamp' => Arr::get($messagingItem, 'timestamp') ?? Arr::get($entry, 'time'),
                    ],
                ]);
        })->values()->all();
    }

    protected function logWebhook(string $event, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('instagram_webhooks')->{$level}($event, array_merge([
                'graph_version' => config('services.instagram.graph_version'),
                'app_url' => config('app.url'),
            ], $context));
        } catch (\Throwable) {
            // Webhook logging must never block Meta's delivery handshake.
        }
    }
}
