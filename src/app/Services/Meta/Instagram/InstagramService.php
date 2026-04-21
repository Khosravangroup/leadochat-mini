<?php

namespace App\Services\Meta\Instagram;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\SocialComment;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstagramService
{
    public function __construct(
        protected InstagramLoginService $instagramLoginService,
        protected InstagramMessagingService $instagramMessagingService,
        protected InstagramContentService $instagramContentService,
        protected InstagramCommentService $instagramCommentService,
        protected InstagramStoryService $instagramStoryService,
    ) {
    }

    public function buildLoginUrl(int $workspaceId): string
    {
        return $this->instagramLoginService->buildAuthorizationUrl($workspaceId);
    }

    public function handleCallback(Request $request): array
    {
        return $this->instagramLoginService->handleCallback($request);
    }

    public function sendMessage(
        ProviderConnection $connection,
        string $recipientId,
        string $text,
        array $options = []
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramMessagingService->sendTextMessage(
            $connection,
            $recipientId,
            $text,
            $options
        );
    }

    public function sendAttachment(
        ProviderConnection $connection,
        string $recipientId,
        string $attachmentUrl,
        array $options = []
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramMessagingService->sendAttachment(
            $connection,
            $recipientId,
            $attachmentUrl,
            $options
        );
    }

    public function fetchUserProfile(ProviderConnection $connection, string $instagramScopedUserId): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramMessagingService->fetchUserProfile($connection, $instagramScopedUserId);
    }

    public function sendReaction(
        ProviderConnection $connection,
        string $recipientId,
        string $providerMessageId,
        string $reaction = 'love',
        string $action = 'react'
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramMessagingService->sendReaction(
            $connection,
            $recipientId,
            $providerMessageId,
            $reaction,
            $action
        );
    }

    public function fetchMediaFeed(ProviderConnection $connection, array $options = []): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramContentService->fetchMediaFeed($connection, $options);
    }

    public function syncMediaFeed(ProviderConnection $connection, array $options = []): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramContentService->syncMediaFeed($connection, $options);
    }

    public function fetchMediaDetails(ProviderConnection $connection, string $mediaId, array $options = []): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramContentService->fetchMediaDetails($connection, $mediaId, $options);
    }

    public function fetchMediaComments(ProviderConnection $connection, string $mediaId, array $options = []): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->fetchMediaComments($connection, $mediaId, $options);
    }

    public function syncMediaComments(
        ProviderConnection $connection,
        \App\Models\SocialPost $socialPost,
        array $options = []
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->syncMediaComments($connection, $socialPost, $options);
    }

    public function replyToComment(
        ProviderConnection $connection,
        string $commentId,
        string $text,
        array $options = []
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->replyToComment($connection, $commentId, $text, $options);
    }

    public function hideComment(ProviderConnection $connection, string $commentId): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->hideComment($connection, $commentId);
    }

    public function unhideComment(ProviderConnection $connection, string $commentId): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->unhideComment($connection, $commentId);
    }

    public function deleteComment(ProviderConnection $connection, string $commentId): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramCommentService->deleteComment($connection, $commentId);
    }

    public function deleteMedia(ProviderConnection $connection, string $mediaId): array
    {
        $this->assertInstagramConnection($connection);

        return $this->instagramContentService->deleteMedia($connection, $mediaId);
    }

    public function replyToCommentViaDm(
        ProviderConnection $connection,
        SocialComment $comment,
        string $text,
        array $options = []
    ): array {
        $this->assertInstagramConnection($connection);

        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Instagram DM reply text cannot be empty.');
        }

        $commentId = trim((string) ($comment->provider_comment_id ?? ''));
        if ($commentId === '') {
            throw new RuntimeException('Instagram comment id was not found for DM reply.');
        }

        $sendResult = $this->instagramMessagingService->sendPrivateReplyToComment(
            $connection,
            $commentId,
            $text,
            $options
        );
        $recipientId = $this->resolveCommentDmRecipientId($comment);

        if ($recipientId === null) {
            $resolvedSendRecipientId = $this->resolveSentRecipientId($sendResult);
            if ($resolvedSendRecipientId !== null && ! $this->isInstagramSelfScopedUserId($connection, $resolvedSendRecipientId)) {
                $recipientId = $resolvedSendRecipientId;
            }
        }

        if ($recipientId === null) {
            throw new RuntimeException('Instagram comment private reply did not return a recipient id.');
        }

        $inboxResult = $this->persistCommentDmReplyInInbox(
            $connection,
            $comment,
            $recipientId,
            $text,
            $sendResult,
            is_array($options['agent_meta'] ?? null) ? $options['agent_meta'] : []
        );

        return array_merge($sendResult, [
            'inbox' => $inboxResult,
        ]);
    }

    protected function resolveCommentDmRecipientId(SocialComment $comment): ?string
    {
        $providerUserId = trim((string) ($comment->provider_user_id ?? ''));
        if ($providerUserId !== '') {
            return $providerUserId;
        }

        $rawFromId = trim((string) ($comment->raw['from']['id'] ?? ''));
        if ($rawFromId !== '') {
            return $rawFromId;
        }

        if (app()->environment('local')) {
            $username = trim((string) ($comment->username ?? ($comment->raw['username'] ?? '')));

            if ($username !== '') {
                return 'local-debug-comment-author-' . sha1($username);
            }

            $commentId = trim((string) ($comment->provider_comment_id ?? ''));
            if ($commentId !== '') {
                return 'local-debug-comment-author-' . sha1($commentId);
            }
        }

        return null;
    }

    protected function persistCommentDmReplyInInbox(
        ProviderConnection $connection,
        SocialComment $comment,
        string $recipientId,
        string $text,
        array $sendResult,
        array $agentMeta = []
    ): array {
        $comment->loadMissing('socialPost');
        $socialPost = $comment->socialPost;
        $sentAt = now();

        return DB::transaction(function () use ($connection, $comment, $socialPost, $recipientId, $text, $sendResult, $agentMeta, $sentAt) {
            $conversation = $this->resolveCommentDmConversation($connection, $comment, $recipientId, $sentAt);
            $participants = $this->syncCommentDmParticipants($conversation, $connection, $comment, $recipientId);
            $providerMessageId = $this->resolveSentProviderMessageId($sendResult);
            $messageMeta = array_filter($agentMeta, fn ($value) => $value !== null);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_participant_id' => $participants['self']?->id,
                'reply_to_message_id' => null,
                'provider' => 'instagram',
                'provider_message_id' => $providerMessageId,
                'provider_reply_to_message_id' => $comment->provider_comment_id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'text_body' => $text,
                'caption' => null,
                'status' => 'sent',
                'sent_at' => $sentAt,
                'received_at' => $sentAt,
                'read_at' => null,
                'failed_at' => null,
                'last_error' => null,
                'meta' => array_merge($messageMeta, [
                    'provider' => 'instagram',
                    'delivery_mode' => 'instagram_comment_reply_dm',
                    'send_result' => $sendResult,
                    'social_comment_reply' => true,
                    'message_context_type' => 'comment_reply_dm',
                    'native_private_reply' => true,
                    'social_comment_id' => $comment->id,
                    'social_post_id' => $socialPost?->id,
                    'post_cover_url' => $socialPost?->thumbnail_url ?: $socialPost?->media_url,
                    'provider_media_id' => $comment->provider_media_id,
                    'provider_comment_id' => $comment->provider_comment_id,
                    'comment_author' => $this->resolveCommentAuthorName($comment),
                    'comment_text' => $comment->text,
                    'post_caption' => $socialPost?->caption,
                    'post_permalink' => $socialPost?->permalink,
                    'post_media_type' => $socialPost?->media_type,
                ]),
            ]);

            $conversation->update([
                'last_message_preview' => 'Comment DM reply',
                'last_message_at' => $sentAt,
            ]);

            return [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'provider_conversation_id' => $conversation->provider_conversation_id,
                'recipient_id' => $recipientId,
            ];
        });
    }

    protected function resolveCommentDmConversation(
        ProviderConnection $connection,
        SocialComment $comment,
        string $recipientId,
        \Illuminate\Support\Carbon $sentAt
    ): Conversation {
        $existingConversation = Conversation::query()
            ->where('workspace_id', $connection->workspace_id)
            ->where('provider_connection_id', $connection->id)
            ->where('provider', 'instagram')
            ->whereHas('participants', function ($query) use ($recipientId) {
                $query->where('provider_user_id', $recipientId)
                    ->where('is_self', false);
            })
            ->latest('last_message_at')
            ->latest('id')
            ->first();

        if ($existingConversation) {
            if (! $existingConversation->title) {
                $existingConversation->title = $this->resolveCommentAuthorName($comment) ?: 'Instagram User';
            }

            if (! $existingConversation->type) {
                $existingConversation->type = 'dm';
            }

            if (! $existingConversation->status || in_array($existingConversation->status, ['pending', 'trashed', 'archived'], true)) {
                $existingConversation->status = 'active';
            }

            $existingConversation->is_archived = false;
            $existingConversation->last_message_at = $sentAt;
            $existingConversation->save();

            return $existingConversation;
        }

        $conversationKey = $this->buildCommentDmConversationKey($connection, $recipientId);

        $conversation = Conversation::query()->firstOrNew([
            'workspace_id' => $connection->workspace_id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => $conversationKey,
        ]);

        if (! $conversation->exists) {
            $authorName = $this->resolveCommentAuthorName($comment);

            $conversation->type = 'dm';
            $conversation->status = 'active';
            $conversation->title = $authorName ?: 'Instagram User';
            $conversation->avatar_url = null;
            $conversation->unread_count = 0;
            $conversation->is_archived = false;
            $conversation->is_muted = false;
            $conversation->meta = [
                'platform' => 'instagram',
                'created_from' => 'social_comment_reply_dm',
                'source_social_comment_id' => $comment->id,
                'source_provider_comment_id' => $comment->provider_comment_id,
            ];
        }

        if (! $conversation->title) {
            $conversation->title = $this->resolveCommentAuthorName($comment) ?: 'Instagram User';
        }

        if (! $conversation->type) {
            $conversation->type = 'dm';
        }

        if (! $conversation->status || in_array($conversation->status, ['pending', 'trashed', 'archived'], true)) {
            $conversation->status = 'active';
        }

        $conversation->is_archived = false;
        $conversation->last_message_at = $sentAt;
        $conversation->save();

        return $conversation;
    }

    protected function syncCommentDmParticipants(
        Conversation $conversation,
        ProviderConnection $connection,
        SocialComment $comment,
        string $recipientId
    ): array {
        $selfProviderId = trim((string) ($connection->provider_account_id ?? ''));

        $selfParticipant = null;
        if ($selfProviderId !== '') {
            $selfParticipant = ConversationParticipant::query()->firstOrNew([
                'conversation_id' => $conversation->id,
                'provider_user_id' => $selfProviderId,
            ]);

            $selfParticipant->display_name = $connection->provider_account_name ?: 'Instagram Account';
            $selfParticipant->role = 'participant';
            $selfParticipant->is_self = true;
            $selfParticipant->meta = array_merge(is_array($selfParticipant->meta) ? $selfParticipant->meta : [], [
                'provider_connection_id' => $connection->id,
            ]);
            $selfParticipant->save();
        }

        $customerParticipant = ConversationParticipant::query()->firstOrNew([
            'conversation_id' => $conversation->id,
            'provider_user_id' => $recipientId,
        ]);

        $customerParticipant->display_name = $customerParticipant->display_name
            ?: ($this->resolveCommentAuthorName($comment) ?: 'Instagram User');
        $customerParticipant->handle = $customerParticipant->handle ?: $comment->username;
        $customerParticipant->avatar_url = $customerParticipant->avatar_url ?: $this->resolveCommentAuthorAvatarUrl($comment);
        $customerParticipant->role = 'participant';
        $customerParticipant->is_self = false;
        $customerParticipant->meta = array_merge(is_array($customerParticipant->meta) ? $customerParticipant->meta : [], [
            'source' => 'social_comment_reply_dm',
            'source_social_comment_id' => $comment->id,
            'source_provider_comment_id' => $comment->provider_comment_id,
        ]);
        $customerParticipant->save();

        if (! $conversation->avatar_url && $customerParticipant->avatar_url) {
            $conversation->avatar_url = $customerParticipant->avatar_url;
            $conversation->save();
        }

        return [
            'self' => $selfParticipant,
            'customer' => $customerParticipant,
        ];
    }

    protected function buildCommentDmConversationKey(ProviderConnection $connection, string $recipientId): string
    {
        $accountId = trim((string) ($connection->provider_account_id ?? ''));

        if ($accountId === '') {
            throw new RuntimeException('Instagram provider_account_id is empty.');
        }

        return 'instagram:dm:' . strtolower($accountId) . ':' . strtolower($recipientId);
    }

    protected function resolveSentProviderMessageId(array $sendResult): string
    {
        $providerMessageId = (string) (
            $sendResult['message_id']
            ?? $sendResult['response']['message_id']
            ?? $sendResult['mock_response']['message_id']
            ?? ''
        );

        if ($providerMessageId !== '') {
            return $providerMessageId;
        }

        return 'instagram-comment-dm-' . now()->timestamp . '-' . random_int(1000, 9999);
    }

    protected function resolveSentRecipientId(array $sendResult): ?string
    {
        $recipientId = trim((string) (
            $sendResult['recipient_id']
            ?? $sendResult['response']['recipient_id']
            ?? $sendResult['mock_response']['recipient_id']
            ?? ''
        ));

        return $recipientId !== '' ? $recipientId : null;
    }

    protected function isInstagramSelfScopedUserId(ProviderConnection $connection, string $recipientId): bool
    {
        $recipientId = trim($recipientId);

        if ($recipientId === '') {
            return false;
        }

        $selfIds = collect([
            $connection->provider_account_id,
            $connection->external_oauth_user_id,
            $connection->meta['identity_payload']['id'] ?? null,
            $connection->meta['identity_payload']['user_id'] ?? null,
            $connection->meta['exchange_payload']['short_lived']['user_id'] ?? null,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        return in_array($recipientId, $selfIds, true);
    }

    protected function resolveCommentAuthorName(SocialComment $comment): ?string
    {
        $username = trim((string) ($comment->username ?? ''));

        if ($username !== '') {
            return $username;
        }

        $rawUsername = trim((string) ($comment->raw['username'] ?? ($comment->raw['from']['username'] ?? '')));

        return $rawUsername !== '' ? $rawUsername : null;
    }

    protected function resolveCommentAuthorAvatarUrl(SocialComment $comment): ?string
    {
        $avatarUrl = trim((string) (
            $comment->raw['profile_pic']
            ?? $comment->raw['profile_picture_url']
            ?? $comment->raw['from']['profile_pic']
            ?? $comment->raw['from']['profile_picture_url']
            ?? ''
        ));

        return $avatarUrl !== '' ? $avatarUrl : null;
    }

    protected function trimPreview(string $text): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= 80) {
            return $text;
        }

        return mb_substr($text, 0, 77) . '...';
    }

    public function publishPost(
        ProviderConnection $connection,
        array $payload
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->dispatchPublishingAction('publish_post', $connection, $payload);
    }

    public function publishStory(
        ProviderConnection $connection,
        array $payload
    ): array {
        $this->assertInstagramConnection($connection);

        return $this->instagramStoryService->publishStory($connection, $payload);
    }

    public function handleWebhook(Request $request): array
    {
        return $this->dispatchWebhookAction('handle_webhook', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);
    }

    public function resolveConnectionFromConversation(Conversation $conversation): ?ProviderConnection
    {
        if ($conversation->provider !== 'instagram') {
            return null;
        }

        return $conversation->providerConnection;
    }

    protected function assertInstagramConnection(ProviderConnection $connection): void
    {
        if ($connection->provider !== 'instagram') {
            throw new RuntimeException('The given provider connection is not an Instagram connection.');
        }
    }

    protected function dispatchPublishingAction(string $action, ProviderConnection $connection, array $payload): array
    {
        throw new RuntimeException('Instagram publishing action [' . $action . '] is not implemented yet.');
    }

    protected function dispatchWebhookAction(string $action, array $payload): array
    {
        throw new RuntimeException('Instagram webhook action [' . $action . '] is not implemented yet.');
    }
}
