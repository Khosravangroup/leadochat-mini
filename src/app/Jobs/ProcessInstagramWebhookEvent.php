<?php

namespace App\Jobs;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInstagramWebhookEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $webhookEventId
    ) {
    }

    protected function detectNormalizedEventType(array $change): string
    {
        $field = (string) (Arr::get($change, 'field') ?? '');
        $value = Arr::get($change, 'value', []);

        if (in_array($field, ['messages', 'standby'], true)) {
            if (is_array(Arr::get($value, 'messages')) && !empty(Arr::get($value, 'messages'))) {
                return 'message';
            }

            if (is_array(Arr::get($value, 'messaging')) && !empty(Arr::get($value, 'messaging'))) {
                return 'message';
            }

            if (filled(Arr::get($value, 'message'))) {
                return 'message';
            }
        }

        if ($field === 'comments') {
            return 'comment';
        }

        if ($field === 'mentions') {
            return 'mention';
        }

        if ($field === 'story_insights') {
            return 'story_insight';
        }

        return $field !== '' ? $field : 'unknown';
    }

    protected function normalizeMessagePayload(array $change, array $entry): array
    {
        $value = Arr::get($change, 'value', []);
        $messages = Arr::get($value, 'messages', []);
        $messagingItems = Arr::get($value, 'messaging', []);
        $messageNode = is_array($messages) && !empty($messages)
            ? $messages[0]
            : (is_array($messagingItems) && !empty($messagingItems)
                ? Arr::get($messagingItems, '0.message', [])
                : (Arr::get($value, 'message', []) ?: []));

        $attachments = Arr::get($messageNode, 'attachments', []);
        $attachmentItems = collect(is_array($attachments) ? $attachments : [])->map(function ($attachment) {
            $type = (string) (Arr::get($attachment, 'type') ?? 'unknown');
            $payload = Arr::get($attachment, 'payload', []);

            return [
                'type' => $type,
                'url' => Arr::get($payload, 'url'),
                'title' => Arr::get($payload, 'title'),
                'mime_type' => Arr::get($payload, 'mime_type'),
                'raw' => is_array($attachment) ? $attachment : [],
            ];
        })->values()->all();

        $replyTo = Arr::get($messageNode, 'reply_to', []);
        $reaction = Arr::get($messageNode, 'reaction', []);
        $referral = Arr::get($value, 'referral', []);
        $storyContext = Arr::get($messageNode, 'story', []);

        $replyToStoryId = Arr::get($replyTo, 'story.id')
            ?? Arr::get($replyTo, 'story.story_id')
            ?? Arr::get($replyTo, 'story_id')
            ?? Arr::get($storyContext, 'id')
            ?? Arr::get($storyContext, 'story_id')
            ?? Arr::get($referral, 'story.id')
            ?? Arr::get($referral, 'story.story_id')
            ?? Arr::get($referral, 'story_id');

        $messageContextType = null;

        if (is_array($replyTo) && !empty($replyTo)) {
            $messageContextType = 'reply';
        }

        if ($replyToStoryId) {
            $messageContextType = 'story_reply';
        }

        if ($messageContextType === null && is_array($storyContext) && !empty($storyContext)) {
            $messageContextType = 'story_reply';
        }

        if ($messageContextType === null && is_array($referral) && !empty($referral)) {
            $messageContextType = 'referral';
        }

        if ($messageContextType === null && is_array($reaction) && !empty($reaction)) {
            $messageContextType = 'reaction';
        }

        $isStoryReply = $messageContextType === 'story_reply';

        $senderId = (string) (Arr::get($value, 'from.id')
            ?? Arr::get($value, 'sender.id')
            ?? Arr::get($messagingItems, '0.sender.id')
            ?? '');

        $recipientId = (string) (Arr::get($value, 'recipient.id')
            ?? Arr::get($messagingItems, '0.recipient.id')
            ?? Arr::get($entry, 'id')
            ?? '');

        $messageId = (string) (Arr::get($messageNode, 'id')
            ?? Arr::get($messageNode, 'mid')
            ?? Arr::get($value, 'mid')
            ?? Arr::get($value, 'message.mid')
            ?? '');

        $timestamp = Arr::get($messageNode, 'created_time')
            ?? Arr::get($messagingItems, '0.timestamp')
            ?? Arr::get($value, 'timestamp')
            ?? Arr::get($entry, 'time');

        return [
            'kind' => 'message',
            'provider_message_id' => $messageId !== '' ? $messageId : null,
            'sender_id' => $senderId !== '' ? $senderId : null,
            'recipient_id' => $recipientId !== '' ? $recipientId : null,
            'text' => Arr::get($messageNode, 'text'),
            'has_attachments' => !empty($attachmentItems),
            'attachments' => $attachmentItems,
            'sent_at' => $timestamp,
            'message_context_type' => $messageContextType,
            'is_story_reply' => $isStoryReply,
            'story_id' => $replyToStoryId ? (string) $replyToStoryId : null,
            'reply_to' => is_array($replyTo) ? $replyTo : [],
            'referral' => is_array($referral) ? $referral : [],
            'reaction' => is_array($reaction) ? $reaction : [],
            'story_context' => is_array($storyContext) ? $storyContext : [],
            'raw' => [
                'entry' => $entry,
                'change' => $change,
                'value' => is_array($value) ? $value : [],
                'message' => is_array($messageNode) ? $messageNode : [],
            ],
        ];
    }

    protected function normalizeEvent(array $payload, array $entry, array $change): array
    {
        $normalizedType = $this->detectNormalizedEventType($change);

        if ($normalizedType === 'message') {
            return $this->normalizeMessagePayload($change, $entry);
        }

        if ($normalizedType === 'comment') {
            return $this->normalizeCommentPayload($change, $entry);
        }

        return [
            'kind' => $normalizedType,
            'provider_message_id' => null,
            'sender_id' => null,
            'recipient_id' => null,
            'text' => null,
            'has_attachments' => false,
            'attachments' => [],
            'sent_at' => Arr::get($entry, 'time'),
            'raw' => [
                'payload' => $payload,
                'entry' => $entry,
                'change' => $change,
            ],
        ];
    }

    protected function normalizeCommentPayload(array $change, array $entry): array
    {
        $value = Arr::get($change, 'value', []);
        $verb = (string) (Arr::get($value, 'verb') ?? Arr::get($value, 'item') ?? '');
        $providerCommentId = (string) (
            Arr::get($value, 'id')
            ?? Arr::get($value, 'comment_id')
            ?? Arr::get($value, 'comment.id')
            ?? ''
        );
        $providerMediaId = (string) (
            Arr::get($value, 'media.id')
            ?? Arr::get($value, 'media_id')
            ?? Arr::get($value, 'media.media_id')
            ?? Arr::get($value, 'post_id')
            ?? ''
        );
        $providerUserId = (string) (
            Arr::get($value, 'from.id')
            ?? Arr::get($value, 'user.id')
            ?? Arr::get($value, 'sender.id')
            ?? ''
        );
        $username = (string) (
            Arr::get($value, 'from.username')
            ?? Arr::get($value, 'username')
            ?? Arr::get($value, 'user.username')
            ?? ''
        );
        $text = Arr::get($value, 'text') ?? Arr::get($value, 'message');
        $timestamp = Arr::get($value, 'timestamp')
            ?? Arr::get($value, 'created_time')
            ?? Arr::get($entry, 'time');

        return [
            'kind' => 'comment',
            'provider_comment_id' => $providerCommentId !== '' ? $providerCommentId : null,
            'provider_media_id' => $providerMediaId !== '' ? $providerMediaId : null,
            'parent_provider_comment_id' => $this->normalizeNullableString(
                Arr::get($value, 'parent_id')
                ?? Arr::get($value, 'parent_comment_id')
                ?? Arr::get($value, 'parent.comment_id')
            ),
            'provider_user_id' => $providerUserId !== '' ? $providerUserId : null,
            'username' => $username !== '' ? $username : null,
            'text' => is_string($text) ? trim($text) : null,
            'status' => in_array($verb, ['remove', 'delete', 'deleted'], true) ? 'deleted' : 'active',
            'is_hidden' => (bool) (Arr::get($value, 'hidden') ?? Arr::get($value, 'is_hidden') ?? false),
            'commented_at' => $timestamp,
            'raw' => [
                'entry' => $entry,
                'change' => $change,
                'value' => is_array($value) ? $value : [],
            ],
        ];
    }

    protected function detectNormalizedDirection(array $normalized, WebhookEvent $event): string
    {
        $senderId = (string) ($normalized['sender_id'] ?? '');
        $recipientId = (string) ($normalized['recipient_id'] ?? '');
        $accountId = (string) ($event->providerConnection?->provider_account_id ?? '');

        if ($accountId !== '') {
            if ($senderId !== '' && $senderId === $accountId) {
                return 'outbound_or_echo';
            }

            if ($recipientId !== '' && $recipientId === $accountId) {
                return 'inbound';
            }
        }

        return 'unknown';
    }

    protected function normalizeInstagramTimestamp(mixed $value): ?Carbon
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

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function shouldSkipEchoWithoutProviderMessageId(array $normalized): bool
    {
        $direction = (string) ($normalized['direction'] ?? 'unknown');
        $providerMessageId = (string) ($normalized['provider_message_id'] ?? '');

        return $direction === 'outbound_or_echo' && $providerMessageId === '';
    }

    protected function buildFallbackProviderMessageId(Conversation $conversation, array $normalized): ?string
    {
        $direction = (string) ($normalized['direction'] ?? 'unknown');
        $senderId = (string) ($normalized['sender_id'] ?? '');
        $recipientId = (string) ($normalized['recipient_id'] ?? '');
        $text = (string) ($normalized['text'] ?? '');
        $sentAt = $this->normalizeInstagramTimestamp($normalized['sent_at'] ?? null)?->toIso8601String() ?? 'no-time';
        $attachmentSignature = collect($normalized['attachments'] ?? [])
            ->map(fn ($attachment) => (string) (($attachment['type'] ?? 'unknown') . '|' . ($attachment['url'] ?? '') . '|' . ($attachment['title'] ?? '')))
            ->implode(';');

        if ($direction === 'unknown' && $senderId === '' && $recipientId === '' && $text === '' && $attachmentSignature === '') {
            return null;
        }

        return 'instagram:fallback:' . sha1(implode('|', [
            $conversation->id,
            $direction,
            $senderId,
            $recipientId,
            $sentAt,
            $text,
            $attachmentSignature,
        ]));
    }

    protected function resolveParticipantProviderIds(array $normalized, WebhookEvent $event): array
    {
        $direction = (string) ($normalized['direction'] ?? 'unknown');
        $senderId = (string) ($normalized['sender_id'] ?? '');
        $recipientId = (string) ($normalized['recipient_id'] ?? '');
        $accountId = (string) ($event->providerConnection?->provider_account_id ?? '');

        $selfId = $accountId !== '' ? $accountId : null;
        $customerId = null;

        if ($direction === 'inbound') {
            $customerId = $senderId !== '' ? $senderId : null;
            $selfId = $recipientId !== '' ? $recipientId : $selfId;
        } elseif ($direction === 'outbound_or_echo') {
            $customerId = $recipientId !== '' ? $recipientId : null;
            $selfId = $senderId !== '' ? $senderId : $selfId;
        }

        return [
            'self_id' => $selfId,
            'customer_id' => $customerId,
        ];
    }

    protected function buildConversationExternalKey(WebhookEvent $event, array $normalized): ?string
    {
        $participantIds = $this->resolveParticipantProviderIds($normalized, $event);
        $customerId = (string) ($participantIds['customer_id'] ?? '');
        $accountId = (string) ($participantIds['self_id'] ?? $event->providerConnection?->provider_account_id ?? '');

        if ($customerId === '' || $accountId === '') {
            return null;
        }

        return 'instagram:dm:' . Str::lower($accountId) . ':' . Str::lower($customerId);
    }

    protected function resolveConversation(WebhookEvent $event, array $normalized): ?Conversation
    {
        $conversationKey = $this->buildConversationExternalKey($event, $normalized);

        if ($conversationKey === null) {
            return null;
        }

        $conversation = Conversation::query()->firstOrNew([
            'workspace_id' => $event->workspace_id,
            'provider_connection_id' => $event->provider_connection_id,
            'provider' => 'instagram',
            'provider_conversation_id' => $conversationKey,
        ]);

        if (! $conversation->exists) {
            $conversation->type = 'dm';
            $conversation->status = 'open';
            $conversation->title = 'Instagram DM';
            $conversation->avatar_url = null;
        }
        $conversation->last_message_at = $this->normalizeInstagramTimestamp($normalized['sent_at'] ?? null) ?? now();
        $conversation->save();

        return $conversation;
    }

    protected function syncConversationParticipants(Conversation $conversation, WebhookEvent $event, array $normalized): array
    {
        $participantIds = $this->resolveParticipantProviderIds($normalized, $event);
        $selfProviderId = (string) ($participantIds['self_id'] ?? '');
        $customerProviderId = (string) ($participantIds['customer_id'] ?? '');

        $selfParticipant = null;
        if ($selfProviderId !== '') {
            $selfParticipant = ConversationParticipant::query()->firstOrNew([
                'conversation_id' => $conversation->id,
                'provider_user_id' => $selfProviderId,
            ]);

            $selfParticipant->display_name = $event->providerConnection?->provider_account_name ?: 'Instagram Account';
            $selfParticipant->handle = $selfParticipant->handle ?: null;
            $selfParticipant->avatar_url = $selfParticipant->avatar_url ?: null;
            $selfParticipant->role = 'participant';
            $selfParticipant->is_self = true;
            $selfParticipant->save();
        }

        $customerParticipant = null;
        if ($customerProviderId !== '') {
            $customerParticipant = ConversationParticipant::query()->firstOrNew([
                'conversation_id' => $conversation->id,
                'provider_user_id' => $customerProviderId,
            ]);

            $customerParticipant->display_name = $customerParticipant->display_name ?: 'Instagram User';
            $customerParticipant->handle = $customerParticipant->handle ?: null;
            $customerParticipant->avatar_url = $customerParticipant->avatar_url ?: null;
            $customerParticipant->role = 'participant';
            $customerParticipant->is_self = false;
            $customerParticipant->save();
        }

        return [
            'self_participant_id' => $selfParticipant?->id,
            'customer_participant_id' => $customerParticipant?->id,
            'self_provider_user_id' => $selfProviderId !== '' ? $selfProviderId : null,
            'customer_provider_user_id' => $customerProviderId !== '' ? $customerProviderId : null,
        ];
    }

    protected function resolveSenderParticipantId(array $normalized, array $resolvedParticipants): ?int
    {
        $direction = (string) ($normalized['direction'] ?? 'unknown');

        if ($direction === 'inbound') {
            return $resolvedParticipants['customer_participant_id'] ?? null;
        }

        if ($direction === 'outbound_or_echo') {
            return $resolvedParticipants['self_participant_id'] ?? null;
        }

        return null;
    }

    protected function persistNormalizedMessage(Conversation $conversation, array $normalized, array $resolvedParticipants): array
    {
        if ($this->shouldSkipEchoWithoutProviderMessageId($normalized)) {
            return [
                'message_id' => null,
                'attachment_ids' => [],
                'skipped_reason' => 'echo_without_provider_message_id',
            ];
        }

        return DB::transaction(function () use ($conversation, $normalized, $resolvedParticipants) {
            $providerMessageId = (string) ($normalized['provider_message_id'] ?? '');
            $senderParticipantId = $this->resolveSenderParticipantId($normalized, $resolvedParticipants);
            $direction = (string) ($normalized['direction'] ?? 'unknown');
            $textBody = is_string($normalized['text'] ?? null)
                ? trim((string) $normalized['text'])
                : null;
            $attachments = is_array($normalized['attachments'] ?? null) ? $normalized['attachments'] : [];
            $normalizedSentAt = $this->normalizeInstagramTimestamp($normalized['sent_at'] ?? null);
            $messageType = !empty($attachments) ? 'attachment' : 'text';
            $messageContextType = is_string($normalized['message_context_type'] ?? null)
                ? $normalized['message_context_type']
                : null;
            $isStoryReply = (bool) ($normalized['is_story_reply'] ?? false);
            $storyId = is_string($normalized['story_id'] ?? null) && $normalized['story_id'] !== ''
                ? $normalized['story_id']
                : null;

            if ($providerMessageId === '') {
                $providerMessageId = $this->buildFallbackProviderMessageId($conversation, $normalized) ?? '';
            }

            if ($providerMessageId !== '') {
                $message = Message::query()->firstOrNew([
                    'conversation_id' => $conversation->id,
                    'provider' => 'instagram',
                    'provider_message_id' => $providerMessageId,
                ]);
            } else {
                $message = new Message();
                $message->conversation_id = $conversation->id;
            }

            $message->sender_participant_id = $senderParticipantId;
            $message->provider = 'instagram';
            $message->direction = $direction === 'outbound_or_echo' ? 'outbound' : 'inbound';
            $message->message_type = $messageType;
            $message->text_body = $messageType === 'text' && $textBody !== '' ? $textBody : null;
            $message->caption = $messageType === 'attachment' && $textBody !== '' ? $textBody : null;
            $message->status = $direction === 'outbound_or_echo' ? 'sent' : 'delivered';
            $message->sent_at = $normalizedSentAt;
            $message->received_at = $direction === 'inbound' ? ($normalizedSentAt ?? now()) : null;
            $message->meta = array_merge(is_array($message->meta) ? $message->meta : [], [
                'provider' => 'instagram',
                'normalized_kind' => $normalized['kind'] ?? 'message',
                'normalized_direction' => $direction,
                'normalized_sent_at' => $normalizedSentAt?->toIso8601String(),
                'message_context_type' => $messageContextType,
                'is_story_reply' => $isStoryReply,
                'story_id' => $storyId,
                'reply_to' => is_array($normalized['reply_to'] ?? null) ? $normalized['reply_to'] : [],
                'referral' => is_array($normalized['referral'] ?? null) ? $normalized['referral'] : [],
                'reaction' => is_array($normalized['reaction'] ?? null) ? $normalized['reaction'] : [],
                'story_context' => is_array($normalized['story_context'] ?? null) ? $normalized['story_context'] : [],
            ]);
            $message->save();

            $savedAttachmentIds = [];

            foreach ($attachments as $index => $attachment) {
                $raw = is_array($attachment['raw'] ?? null) ? $attachment['raw'] : [];
                $attachmentMeta = [
                    'provider' => 'instagram',
                    'attachment_index' => $index,
                    'message_context_type' => $messageContextType,
                    'is_story_reply' => $isStoryReply,
                    'story_id' => $storyId,
                    'raw' => $raw,
                ];

                $messageAttachment = MessageAttachment::query()->firstOrNew([
                    'message_id' => $message->id,
                    'sort_order' => $index,
                ]);

                $messageAttachment->attachment_type = (string) ($attachment['type'] ?? 'unknown');
                $messageAttachment->url = $attachment['url'] ?? null;
                $messageAttachment->mime_type = $attachment['mime_type'] ?? null;
                $messageAttachment->file_name = $attachment['title'] ?? null;
                $messageAttachment->file_size = null;
                $messageAttachment->duration_seconds = null;
                $messageAttachment->sort_order = $index;
                $messageAttachment->meta = $attachmentMeta;
                $messageAttachment->save();

                $savedAttachmentIds[] = $messageAttachment->id;
            }

            $this->updateConversationSnapshot($conversation, $normalized, $resolvedParticipants);

            return [
                'message_id' => $message->id,
                'attachment_ids' => $savedAttachmentIds,
                'skipped_reason' => null,
            ];
        });
    }

    protected function shouldPersistNormalizedMessage(array $normalized, ?Conversation $resolvedConversation): bool
    {
        return ($normalized['kind'] ?? 'unknown') === 'message'
            && $resolvedConversation instanceof Conversation;
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function resolveSocialPostForComment(WebhookEvent $event, array $normalized): ?SocialPost
    {
        $providerMediaId = (string) ($normalized['provider_media_id'] ?? '');

        if ($providerMediaId === '') {
            return null;
        }

        $post = SocialPost::query()->firstOrNew([
            'provider' => 'instagram',
            'provider_media_id' => $providerMediaId,
        ]);

        $post->workspace_id = $event->workspace_id;
        $post->provider_connection_id = $event->provider_connection_id;
        $post->provider = 'instagram';
        $post->provider_media_id = $providerMediaId;
        $post->media_type = $post->media_type ?: $this->normalizeNullableString(Arr::get($normalized, 'raw.value.media.media_product_type'));
        $post->status = $post->status ?: 'published';
        $post->raw = array_merge(
            is_array($post->raw) ? $post->raw : [],
            [
                'last_comment_webhook_at' => now()->toIso8601String(),
                'last_comment_webhook_value' => Arr::get($normalized, 'raw.value', []),
            ]
        );
        $post->save();

        return $post;
    }

    protected function persistNormalizedComment(WebhookEvent $event, array $normalized): array
    {
        $providerCommentId = (string) ($normalized['provider_comment_id'] ?? '');

        if ($providerCommentId === '') {
            return [
                'comment_id' => null,
                'social_post_id' => null,
                'skipped_reason' => 'missing_provider_comment_id',
            ];
        }

        /** @var ProviderConnection|null $connection */
        $connection = $event->providerConnection;
        if (! $connection) {
            return [
                'comment_id' => null,
                'social_post_id' => null,
                'skipped_reason' => 'provider_connection_not_loaded',
            ];
        }

        return DB::transaction(function () use ($event, $normalized, $providerCommentId) {
            $comment = SocialComment::query()->firstOrNew([
                'provider' => 'instagram',
                'provider_comment_id' => $providerCommentId,
            ]);

            $parentComment = null;
            $parentProviderCommentId = (string) ($normalized['parent_provider_comment_id'] ?? '');
            if ($parentProviderCommentId !== '') {
                $parentComment = SocialComment::query()
                    ->where('provider', 'instagram')
                    ->where('provider_comment_id', $parentProviderCommentId)
                    ->first();

                if (blank($normalized['provider_media_id'] ?? null) && $parentComment?->provider_media_id) {
                    $normalized['provider_media_id'] = $parentComment->provider_media_id;
                }
            }

            $post = $this->resolveSocialPostForComment($event, $normalized)
                ?: $parentComment?->socialPost
                ?: $comment->socialPost;
            $commentedAt = $this->normalizeInstagramTimestamp($normalized['commented_at'] ?? null);
            $existingRaw = is_array($comment->raw) ? $comment->raw : [];

            $comment->workspace_id = $event->workspace_id;
            $comment->provider_connection_id = $event->provider_connection_id;
            $comment->social_post_id = $post?->id ?? $comment->social_post_id;
            $comment->provider = 'instagram';
            $comment->provider_media_id = $normalized['provider_media_id'] ?? $comment->provider_media_id;
            $comment->provider_comment_id = $providerCommentId;
            $comment->parent_provider_comment_id = $normalized['parent_provider_comment_id'] ?? $comment->parent_provider_comment_id;
            $comment->provider_user_id = $normalized['provider_user_id'] ?? $comment->provider_user_id;
            $comment->username = $normalized['username'] ?? $comment->username;
            $comment->text = $normalized['text'] ?? $comment->text;
            $comment->status = $normalized['status'] ?? $comment->status ?? 'active';
            $comment->is_hidden = (bool) ($normalized['is_hidden'] ?? $comment->is_hidden ?? false);
            $comment->commented_at = $commentedAt ?? $comment->commented_at;
            $comment->raw = array_merge(
                $existingRaw,
                Arr::get($normalized, 'raw.value', []),
                [
                    'last_webhook_event_id' => $event->id,
                    'last_webhook_at' => now()->toIso8601String(),
                ]
            );
            $comment->save();

            if ($post) {
                $storedCommentCount = SocialComment::query()
                    ->where('social_post_id', $post->id)
                    ->where('provider', 'instagram')
                    ->where('status', '!=', 'deleted')
                    ->count();

                if ($storedCommentCount > (int) $post->comments_count) {
                    $post->comments_count = $storedCommentCount;
                    $post->save();
                }
            }

            return [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
                'skipped_reason' => null,
            ];
        });
    }

    protected function updateConversationSnapshot(Conversation $conversation, array $normalized, array $resolvedParticipants): void
    {
        $messageTime = $this->normalizeInstagramTimestamp($normalized['sent_at'] ?? null) ?? now();
        $existingLastMessageAt = $conversation->last_message_at instanceof Carbon
            ? $conversation->last_message_at
            : ($conversation->last_message_at ? Carbon::parse($conversation->last_message_at) : null);

        if (! $existingLastMessageAt || $messageTime->greaterThan($existingLastMessageAt)) {
            $conversation->last_message_at = $messageTime;
        }

        $customerParticipantId = $resolvedParticipants['customer_participant_id'] ?? null;
        if ($customerParticipantId) {
            $customerParticipant = ConversationParticipant::find($customerParticipantId);
            if ($customerParticipant) {
                $conversation->title = $customerParticipant->display_name
                    ?: $customerParticipant->handle
                    ?: $customerParticipant->provider_user_id
                    ?: $conversation->title;

                if (! $conversation->avatar_url && $customerParticipant->avatar_url) {
                    $conversation->avatar_url = $customerParticipant->avatar_url;
                }
            }
        }

        if (! $conversation->type) {
            $conversation->type = 'dm';
        }

        if (! $conversation->status || $conversation->status === 'pending') {
            $conversation->status = 'open';
        }

        $conversation->save();
    }

    protected function shouldSkipAlreadyFinalizedEvent(WebhookEvent $event): bool
    {
        return in_array($event->status, ['processed', 'ignored'], true)
            && $event->processed_at !== null;
    }

    protected function broadcastWorkspaceUpdate(WebhookEvent $event, string $domain, string $action, array $payload = []): void
    {
        if (blank($event->workspace_id)) {
            return;
        }

        try {
            event(new WorkspaceRealtimeUpdated((int) $event->workspace_id, $domain, $action, $payload));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function handle(): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event) {
            $this->logWebhookProcessing('processing_skipped', [
                'webhook_event_id' => $this->webhookEventId,
                'reason' => 'event_not_found',
            ], 'warning');

            return;
        }

        if ($this->shouldSkipAlreadyFinalizedEvent($event)) {
            $this->logWebhookProcessing('processing_skipped', [
                'webhook_event_id' => $event->id,
                'provider_event_id' => $event->provider_event_id,
                'status' => $event->status,
                'reason' => 'already_finalized',
            ], 'debug');

            return;
        }

        $this->logWebhookProcessing('processing_started', [
            'webhook_event_id' => $event->id,
            'provider_event_id' => $event->provider_event_id,
            'event_type' => $event->event_type,
            'status' => $event->status,
            'workspace_id' => $event->workspace_id,
            'provider_connection_id' => $event->provider_connection_id,
        ]);

        $event->update([
            'status' => 'processing',
            'last_error' => null,
        ]);

        try {
            $event->loadMissing('providerConnection');

            $payload = is_array($event->payload) ? $event->payload : [];
            $entry = Arr::get($payload, 'entry', []);
            $change = Arr::get($payload, 'change', []);

            $eventType = (string) ($event->event_type ?: Arr::get($change, 'field') ?: 'unknown');
            $object = (string) ($event->object ?: Arr::get($payload, 'object') ?: 'instagram');
            $normalized = $this->normalizeEvent($payload, is_array($entry) ? $entry : [], is_array($change) ? $change : []);
            $normalizedDirection = $this->detectNormalizedDirection($normalized, $event);
            $normalized['direction'] = $normalizedDirection;
            $isMessageEvent = ($normalized['kind'] ?? 'unknown') === 'message';
            $isCommentEvent = ($normalized['kind'] ?? 'unknown') === 'comment';

            $this->logWebhookProcessing('event_normalized', [
                'webhook_event_id' => $event->id,
                'provider_event_id' => $event->provider_event_id,
                'kind' => $normalized['kind'] ?? 'unknown',
                'direction' => $normalizedDirection,
                'provider_message_id' => $normalized['provider_message_id'] ?? null,
                'sender_id' => $normalized['sender_id'] ?? null,
                'recipient_id' => $normalized['recipient_id'] ?? null,
                'text_present' => filled($normalized['text'] ?? null),
                'text_length' => is_string($normalized['text'] ?? null) ? mb_strlen($normalized['text']) : 0,
                'attachment_count' => is_array($normalized['attachments'] ?? null) ? count($normalized['attachments']) : 0,
                'message_context_type' => $normalized['message_context_type'] ?? null,
                'provider_comment_id' => $normalized['provider_comment_id'] ?? null,
                'provider_media_id' => $normalized['provider_media_id'] ?? null,
            ], 'debug');

            $resolvedConversation = null;
            $resolvedParticipants = [
                'self_participant_id' => null,
                'customer_participant_id' => null,
                'self_provider_user_id' => null,
                'customer_provider_user_id' => null,
            ];
            $persistedMessage = ['message_id' => null, 'attachment_ids' => [], 'skipped_reason' => null];
            $persistedComment = ['comment_id' => null, 'social_post_id' => null, 'skipped_reason' => null];

            $shouldIgnore = blank($event->workspace_id) || blank($event->provider_connection_id);
            if (! $shouldIgnore && $isMessageEvent) {
                $resolvedConversation = $this->resolveConversation($event, $normalized);

                if ($resolvedConversation) {
                    $resolvedParticipants = $this->syncConversationParticipants($resolvedConversation, $event, $normalized);
                }

                if ($this->shouldPersistNormalizedMessage($normalized, $resolvedConversation)) {
                    $persistedMessage = $this->persistNormalizedMessage($resolvedConversation, $normalized, $resolvedParticipants);
                }
            }

            if (! $shouldIgnore && $isCommentEvent) {
                $persistedComment = $this->persistNormalizedComment($event, $normalized);
            }

            $ignoredReason = null;
            if ($shouldIgnore) {
                $ignoredReason = 'provider_connection_not_resolved';
            } elseif ($isMessageEvent && ! $resolvedConversation) {
                $ignoredReason = 'conversation_not_resolved';
            } elseif ($isCommentEvent && $persistedComment['skipped_reason']) {
                $ignoredReason = $persistedComment['skipped_reason'];
            }

            $finalStatus = $ignoredReason ? 'ignored' : 'processed';

            $event->update([
                'event_type' => $eventType,
                'object' => $object,
                'status' => $finalStatus,
                'last_error' => null,
                'processed_at' => now(),
                'payload' => array_merge($payload, [
                    'entry' => is_array($entry) ? $entry : [],
                    'change' => is_array($change) ? $change : [],
                    'normalized' => [
                        'kind' => $normalized['kind'] ?? 'unknown',
                        'direction' => $normalizedDirection,
                        'provider_message_id' => $normalized['provider_message_id'] ?? null,
                        'sender_id' => $normalized['sender_id'] ?? null,
                        'recipient_id' => $normalized['recipient_id'] ?? null,
                        'text' => $normalized['text'] ?? null,
                        'has_attachments' => (bool) ($normalized['has_attachments'] ?? false),
                        'attachments' => $normalized['attachments'] ?? [],
                        'sent_at' => $normalized['sent_at'] ?? null,
                        'message_context_type' => $normalized['message_context_type'] ?? null,
                        'is_story_reply' => (bool) ($normalized['is_story_reply'] ?? false),
                        'story_id' => $normalized['story_id'] ?? null,
                        'reply_to' => $normalized['reply_to'] ?? [],
                        'referral' => $normalized['referral'] ?? [],
                        'reaction' => $normalized['reaction'] ?? [],
                        'story_context' => $normalized['story_context'] ?? [],
                        'conversation_key' => $this->buildConversationExternalKey($event, $normalized),
                        'resolved_conversation_id' => $resolvedConversation?->id,
                        'resolved_self_participant_id' => $resolvedParticipants['self_participant_id'],
                        'resolved_customer_participant_id' => $resolvedParticipants['customer_participant_id'],
                        'resolved_self_provider_user_id' => $resolvedParticipants['self_provider_user_id'],
                        'resolved_customer_provider_user_id' => $resolvedParticipants['customer_provider_user_id'],
                        'persisted_message_id' => $persistedMessage['message_id'],
                        'persisted_attachment_ids' => $persistedMessage['attachment_ids'],
                        'persistence_skipped_reason' => $persistedMessage['skipped_reason'],
                        'provider_comment_id' => $normalized['provider_comment_id'] ?? null,
                        'provider_media_id' => $normalized['provider_media_id'] ?? null,
                        'persisted_comment_id' => $persistedComment['comment_id'],
                        'persisted_social_post_id' => $persistedComment['social_post_id'],
                        'comment_persistence_skipped_reason' => $persistedComment['skipped_reason'],
                        'raw' => $normalized['raw'] ?? [],
                    ],
                    'processing' => [
                        'phase' => 'message_persistence',
                        'resolved_workspace_id' => $event->workspace_id,
                        'resolved_provider_connection_id' => $event->provider_connection_id,
                        'ignored_reason' => $ignoredReason,
                        'processed_at' => now()->toDateTimeString(),
                    ],
                ]),
            ]);

            if (! $ignoredReason && $isMessageEvent && $persistedMessage['message_id']) {
                $this->broadcastWorkspaceUpdate($event, 'inbox', 'instagram_message_received', [
                    'conversation_id' => $resolvedConversation?->id,
                    'message_id' => $persistedMessage['message_id'],
                    'direction' => $normalizedDirection,
                ]);
            }

            if (! $ignoredReason && $isCommentEvent && $persistedComment['comment_id']) {
                $this->broadcastWorkspaceUpdate($event, 'social', 'instagram_comment_received', [
                    'social_post_id' => $persistedComment['social_post_id'],
                    'comment_id' => $persistedComment['comment_id'],
                    'provider_media_id' => $normalized['provider_media_id'] ?? null,
                    'provider_comment_id' => $normalized['provider_comment_id'] ?? null,
                ]);
            }

            $this->logWebhookProcessing('processing_completed', [
                'webhook_event_id' => $event->id,
                'provider_event_id' => $event->provider_event_id,
                'final_status' => $finalStatus,
                'ignored_reason' => $ignoredReason,
                'resolved_conversation_id' => $resolvedConversation?->id,
                'persisted_message_id' => $persistedMessage['message_id'],
                'persisted_comment_id' => $persistedComment['comment_id'],
                'persisted_attachment_ids' => $persistedMessage['attachment_ids'],
                'workspace_id' => $event->workspace_id,
                'provider_connection_id' => $event->provider_connection_id,
            ]);
        } catch (\Throwable $exception) {
            $this->logWebhookProcessing('processing_failed', [
                'webhook_event_id' => $event->id,
                'provider_event_id' => $event->provider_event_id,
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ], 'error');

            $event->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            throw $exception;
        }
    }

    protected function logWebhookProcessing(string $event, array $context = [], string $level = 'info'): void
    {
        try {
            Log::channel('instagram_webhooks')->{$level}($event, array_merge([
                'graph_version' => config('services.instagram.graph_version'),
                'job' => static::class,
            ], $context));
        } catch (\Throwable) {
            // Diagnostics must not change webhook processing behavior.
        }
    }
}
