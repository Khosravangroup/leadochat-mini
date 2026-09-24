<?php

namespace App\Services\Meta\Instagram;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\SocialComment;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstagramDmAutomationService
{
    public function __construct(protected InstagramService $instagramService) {}

    public function handle(WorkspaceRealtimeUpdated $event): void
    {
        if ($event->action === 'instagram_message_received') {
            $this->handleStoryReply($event);

            return;
        }

        if ($event->action === 'instagram_comment_received') {
            $this->handleComment($event);
        }
    }

    protected function handleStoryReply(WorkspaceRealtimeUpdated $event): void
    {
        if (($event->payload['direction'] ?? null) !== 'inbound') {
            return;
        }

        $messageId = filter_var($event->payload['message_id'] ?? null, FILTER_VALIDATE_INT);
        if (! $messageId) {
            return;
        }

        $message = Message::query()
            ->with(['conversation.providerConnection', 'senderParticipant'])
            ->find($messageId);
        $connection = $message?->conversation?->providerConnection;

        if (! $message
            || ! $connection
            || (int) $message->conversation->workspace_id !== $event->workspaceId
            || $message->direction !== 'inbound'
            || ! (bool) ($message->meta['is_story_reply'] ?? false)
            || ! $this->connectionCanSend($connection)
        ) {
            return;
        }

        $automation = $this->automationConfig($connection, 'story_reply');
        $recipientId = trim((string) ($message->senderParticipant?->provider_user_id ?? ''));

        if (! $automation['enabled']
            || $automation['message'] === ''
            || $recipientId === ''
            || $this->isSelfId($connection, $recipientId)
            || ! $this->claimMessage($message->id)
        ) {
            return;
        }

        try {
            $result = $this->instagramService->sendMessage(
                $connection,
                $recipientId,
                $automation['message']
            );

            $this->finishMessage($message->id, 'sent', [
                'provider_message_id' => $this->nullableString($result['message_id'] ?? null),
            ]);
        } catch (Throwable $exception) {
            $this->finishMessage($message->id, 'failed', [
                'error_code' => 'provider_send_failed',
            ]);
            report($exception);
        }
    }

    protected function handleComment(WorkspaceRealtimeUpdated $event): void
    {
        $commentId = filter_var($event->payload['comment_id'] ?? null, FILTER_VALIDATE_INT);
        if (! $commentId) {
            return;
        }

        $comment = SocialComment::query()
            ->with('providerConnection')
            ->find($commentId);
        $connection = $comment?->providerConnection;

        if (! $comment
            || ! $connection
            || (int) $comment->workspace_id !== $event->workspaceId
            || $comment->status !== 'active'
            || $comment->replied_via_dm_at !== null
            || ! $this->isNewComment($comment)
            || ! $this->connectionCanSend($connection)
        ) {
            return;
        }

        $automation = $this->automationConfig($connection, 'comment_dm');
        $authorId = trim((string) ($comment->provider_user_id ?? ($comment->raw['from']['id'] ?? '')));

        if (! $automation['enabled']
            || $automation['message'] === ''
            || $authorId === ''
            || $this->isSelfId($connection, $authorId)
            || ! $this->claimComment($comment->id)
        ) {
            return;
        }

        try {
            $result = $this->instagramService->replyToCommentViaDm(
                $connection,
                $comment->fresh(),
                $automation['message'],
                [
                    'agent_meta' => [
                        'automation' => 'instagram_comment_dm',
                    ],
                ]
            );

            $this->finishComment($comment->id, 'sent', [
                'provider_message_id' => $this->nullableString($result['message_id'] ?? null),
            ], true);
        } catch (Throwable $exception) {
            $this->finishComment($comment->id, 'failed', [
                'error_code' => 'provider_send_failed',
            ]);
            report($exception);
        }
    }

    /**
     * @return array{enabled: bool, message: string}
     */
    protected function automationConfig(ProviderConnection $connection, string $key): array
    {
        $config = $connection->meta['dm_automation'][$key] ?? [];

        return [
            'enabled' => (bool) ($config['enabled'] ?? false),
            'message' => trim((string) ($config['message'] ?? '')),
        ];
    }

    protected function connectionCanSend(ProviderConnection $connection): bool
    {
        return $connection->provider === 'instagram'
            && $connection->status === 'connected';
    }

    protected function isNewComment(SocialComment $comment): bool
    {
        $raw = is_array($comment->raw) ? $comment->raw : [];
        $verb = strtolower(trim((string) ($raw['verb'] ?? '')));

        return $verb === '' || in_array($verb, ['add', 'create', 'created'], true);
    }

    protected function isSelfId(ProviderConnection $connection, string $providerUserId): bool
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];
        $identity = is_array($meta['identity_payload'] ?? null) ? $meta['identity_payload'] : [];
        $selfIds = array_filter([
            $connection->provider_account_id,
            $connection->external_oauth_user_id,
            $identity['id'] ?? null,
            $identity['user_id'] ?? null,
        ], fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '');

        return in_array($providerUserId, array_map(fn (mixed $value): string => trim((string) $value), $selfIds), true);
    }

    protected function claimMessage(int $messageId): bool
    {
        return DB::transaction(function () use ($messageId): bool {
            $message = Message::query()->lockForUpdate()->find($messageId);
            $meta = is_array($message?->meta) ? $message->meta : [];

            if (! $message || isset($meta['dm_automation']['story_reply']['status'])) {
                return false;
            }

            $meta['dm_automation']['story_reply'] = [
                'status' => 'processing',
                'claimed_at' => now()->toIso8601String(),
            ];
            $message->meta = $meta;
            $message->save();

            return true;
        });
    }

    protected function claimComment(int $commentId): bool
    {
        return DB::transaction(function () use ($commentId): bool {
            $comment = SocialComment::query()->lockForUpdate()->find($commentId);
            $raw = is_array($comment?->raw) ? $comment->raw : [];

            if (! $comment
                || $comment->status !== 'active'
                || $comment->replied_via_dm_at !== null
                || ! $this->isNewComment($comment)
                || isset($raw['dm_automation']['comment_dm']['status'])
            ) {
                return false;
            }

            $raw['dm_automation']['comment_dm'] = [
                'status' => 'processing',
                'claimed_at' => now()->toIso8601String(),
            ];
            $comment->raw = $raw;
            $comment->save();

            return true;
        });
    }

    protected function finishMessage(int $messageId, string $status, array $details = []): void
    {
        DB::transaction(function () use ($messageId, $status, $details): void {
            $message = Message::query()->lockForUpdate()->find($messageId);
            if (! $message) {
                return;
            }

            $meta = is_array($message->meta) ? $message->meta : [];
            $meta['dm_automation']['story_reply'] = array_merge(
                is_array($meta['dm_automation']['story_reply'] ?? null) ? $meta['dm_automation']['story_reply'] : [],
                array_filter($details, fn (mixed $value): bool => $value !== null),
                [
                    'status' => $status,
                    'finished_at' => now()->toIso8601String(),
                ]
            );
            $message->meta = $meta;
            $message->save();
        });
    }

    protected function finishComment(int $commentId, string $status, array $details = [], bool $markReplied = false): void
    {
        DB::transaction(function () use ($commentId, $status, $details, $markReplied): void {
            $comment = SocialComment::query()->lockForUpdate()->find($commentId);
            if (! $comment) {
                return;
            }

            $raw = is_array($comment->raw) ? $comment->raw : [];
            $raw['dm_automation']['comment_dm'] = array_merge(
                is_array($raw['dm_automation']['comment_dm'] ?? null) ? $raw['dm_automation']['comment_dm'] : [],
                array_filter($details, fn (mixed $value): bool => $value !== null),
                [
                    'status' => $status,
                    'finished_at' => now()->toIso8601String(),
                ]
            );
            $comment->raw = $raw;

            if ($markReplied) {
                $comment->replied_via_dm_at = now();
            }

            $comment->save();
        });
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
