<?php

namespace App\Services\Meta\Commerce;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\ConversationProductShare;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\SocialStory;
use App\Models\WebhookEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MetaAppReviewEvidenceService
{
    public function generateForConnection(
        ProviderConnection $connection,
        array $diagnostics,
        array $commercePacket
    ): array {
        $workspace = $connection->workspace;
        $connectionMeta = is_array($connection->meta ?? null) ? $connection->meta : [];
        $commerceMeta = is_array($connectionMeta['meta_commerce'] ?? null) ? $connectionMeta['meta_commerce'] : [];
        $account = is_array($diagnostics['account'] ?? null) ? $diagnostics['account'] : [];
        $review = is_array($diagnostics['review'] ?? null) ? $diagnostics['review'] : [];
        $reviewPacketSummary = is_array($commercePacket['summary'] ?? null) ? $commercePacket['summary'] : [];
        $reviewPacketEvidence = is_array($commercePacket['review_evidence'] ?? null) ? $commercePacket['review_evidence'] : [];
        $reviewPacketOrders = is_array($commercePacket['orders'] ?? null) ? $commercePacket['orders'] : [];
        $reviewPacketPromotions = is_array($commercePacket['promotions'] ?? null) ? $commercePacket['promotions'] : [];
        $reviewPacketNotes = is_array($commercePacket['review_notes'] ?? null) ? $commercePacket['review_notes'] : [];

        $conversationQuery = Conversation::query()
            ->where('workspace_id', $workspace?->id)
            ->where('provider_connection_id', $connection->id);

        $messageQuery = Message::query()
            ->whereHas('conversation', fn ($query) => $query
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id));

        $attachmentCollection = MessageAttachment::query()
            ->whereHas('message.conversation', fn ($query) => $query
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id))
            ->get();

        $messageMetaCollection = Message::query()
            ->whereHas('conversation', fn ($query) => $query
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id))
            ->get(['id', 'message_type', 'direction', 'meta']);

        $customerParticipantQuery = ConversationParticipant::query()
            ->where('is_self', false)
            ->whereHas('conversation', fn ($query) => $query
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id));

        $productShareQuery = ConversationProductShare::query()
            ->whereHas('conversation', fn ($query) => $query
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id));

        $postCollection = SocialPost::query()
            ->where('workspace_id', $workspace?->id)
            ->where('provider_connection_id', $connection->id)
            ->where('provider', 'instagram')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->get();

        $commentQuery = SocialComment::query()
            ->where('workspace_id', $workspace?->id)
            ->where('provider_connection_id', $connection->id)
            ->where('provider', 'instagram');

        $storyCollection = SocialStory::query()
            ->where('workspace_id', $workspace?->id)
            ->where('provider_connection_id', $connection->id)
            ->where('provider', 'instagram')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->get();

        $webhookCollection = WebhookEvent::query()
            ->where('workspace_id', $workspace?->id)
            ->where('provider_connection_id', $connection->id)
            ->where('provider', 'instagram')
            ->orderByDesc('id')
            ->get();

        $inbox = [
            'conversation_count' => (clone $conversationQuery)->count(),
            'assigned_conversation_count' => (clone $conversationQuery)->whereNotNull('assigned_user_id')->count(),
            'unread_conversation_count' => (clone $conversationQuery)->where('unread_count', '>', 0)->count(),
            'archived_conversation_count' => (clone $conversationQuery)->where('is_archived', true)->count(),
            'message_count' => (clone $messageQuery)->count(),
            'inbound_message_count' => (clone $messageQuery)->where('direction', 'inbound')->count(),
            'outbound_message_count' => (clone $messageQuery)->where('direction', 'outbound')->count(),
            'media_message_count' => $messageMetaCollection
                ->filter(fn (Message $message) => ($message->message_type ?? 'text') !== 'text')
                ->count(),
            'attachment_count' => $attachmentCollection->count(),
            'attachment_types' => $attachmentCollection
                ->groupBy(fn (MessageAttachment $attachment) => (string) ($attachment->attachment_type ?: 'unknown'))
                ->map(fn (Collection $items) => $items->count())
                ->sortKeys()
                ->all(),
            'customer_profile_count' => (clone $customerParticipantQuery)->count(),
            'customer_profile_with_avatar_count' => (clone $customerParticipantQuery)->whereNotNull('avatar_url')->count(),
            'customer_profile_with_handle_count' => (clone $customerParticipantQuery)->whereNotNull('handle')->count(),
            'product_share_count' => (clone $productShareQuery)->count(),
            'story_reply_message_count' => $messageMetaCollection
                ->filter(fn (Message $message) => filled(data_get($message->meta, 'story_id')))
                ->count(),
            'comment_context_message_count' => $messageMetaCollection
                ->filter(fn (Message $message) => filled(data_get($message->meta, 'social_comment_id'))
                    || filled(data_get($message->meta, 'source_social_comment_id'))
                    || filled(data_get($message->meta, 'provider_comment_id'))
                    || filled(data_get($message->meta, 'source_provider_comment_id')))
                ->count(),
            'reaction_message_count' => $messageMetaCollection
                ->filter(fn (Message $message) => filled(data_get($message->meta, 'agent_reaction'))
                    || filled(data_get($message->meta, 'customer_reaction')))
                ->count(),
            'recent_conversations' => (clone $conversationQuery)
                ->with(['customerParticipant', 'assignedAgent', 'latestMessage'])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (Conversation $conversation) => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'status' => $conversation->status,
                    'unread_count' => (int) $conversation->unread_count,
                    'is_archived' => (bool) $conversation->is_archived,
                    'last_message_preview' => $conversation->last_message_preview,
                    'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
                    'customer_name' => $conversation->customerParticipant?->display_name,
                    'customer_handle' => $conversation->customerParticipant?->handle,
                    'assigned_agent' => $conversation->assignedAgent?->name,
                ])
                ->values()
                ->all(),
            'recent_messages' => (clone $messageQuery)
                ->with(['conversation.customerParticipant', 'senderParticipant', 'attachments'])
                ->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(fn (Message $message) => [
                    'id' => $message->id,
                    'direction' => $message->direction,
                    'message_type' => $message->message_type,
                    'text_preview' => Str::limit((string) ($message->text_body ?: $message->caption ?: ''), 120),
                    'attachment_count' => $message->attachments->count(),
                    'conversation_id' => $message->conversation_id,
                    'conversation_title' => $message->conversation?->title,
                    'customer_name' => $message->conversation?->customerParticipant?->display_name,
                    'sender_name' => $message->senderParticipant?->display_name,
                    'sent_at' => optional($message->sent_at)->toIso8601String(),
                    'received_at' => optional($message->received_at)->toIso8601String(),
                    'has_story_context' => filled(data_get($message->meta, 'story_id')),
                    'has_comment_context' => filled(data_get($message->meta, 'social_comment_id'))
                        || filled(data_get($message->meta, 'source_social_comment_id'))
                        || filled(data_get($message->meta, 'provider_comment_id'))
                        || filled(data_get($message->meta, 'source_provider_comment_id')),
                    'has_reaction' => filled(data_get($message->meta, 'agent_reaction'))
                        || filled(data_get($message->meta, 'customer_reaction')),
                ])
                ->values()
                ->all(),
        ];

        $social = [
            'post_count' => $postCollection->count(),
            'product_tagged_post_count' => $postCollection
                ->filter(fn (SocialPost $post) => count((array) data_get($post->raw, 'product_tags', [])) > 0)
                ->count(),
            'published_post_count' => $postCollection
                ->whereNotIn('status', ['deleted', 'removed'])
                ->count(),
            'post_like_total' => $postCollection->sum(fn (SocialPost $post) => (int) $post->like_count),
            'post_comment_total' => $postCollection->sum(fn (SocialPost $post) => (int) $post->comments_count),
            'comment_count' => (clone $commentQuery)->count(),
            'hidden_comment_count' => (clone $commentQuery)->where('is_hidden', true)->count(),
            'public_reply_count' => (clone $commentQuery)->whereNotNull('replied_publicly_at')->count(),
            'dm_reply_count' => (clone $commentQuery)->whereNotNull('replied_via_dm_at')->count(),
            'story_count' => $storyCollection->count(),
            'active_story_count' => $storyCollection
                ->filter(fn (SocialStory $story) => ($story->status ?? null) !== 'archived'
                    && ($story->status ?? null) !== 'removed')
                ->count(),
            'archived_story_count' => $storyCollection->where('status', 'archived')->count(),
            'removed_story_count' => $storyCollection->where('status', 'removed')->count(),
            'story_video_count' => $storyCollection
                ->filter(function (SocialStory $story): bool {
                    $mediaType = (string) (data_get($story->raw, 'media_type')
                        ?: data_get($story->raw, 'remote_story.media_type')
                        ?: '');

                    return strtoupper($mediaType) === 'VIDEO';
                })
                ->count(),
            'recent_posts' => $postCollection
                ->take(5)
                ->map(fn (SocialPost $post) => [
                    'id' => $post->id,
                    'provider_media_id' => $post->provider_media_id,
                    'media_type' => $post->media_type,
                    'status' => $post->status,
                    'caption' => Str::limit((string) $post->caption, 120),
                    'permalink' => $post->permalink,
                    'like_count' => (int) $post->like_count,
                    'comments_count' => (int) $post->comments_count,
                    'posted_at' => optional($post->posted_at)->toIso8601String(),
                    'product_tag_count' => count((array) data_get($post->raw, 'product_tags', [])),
                ])
                ->values()
                ->all(),
            'recent_comments' => (clone $commentQuery)
                ->with('socialPost')
                ->orderByDesc('commented_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(fn (SocialComment $comment) => [
                    'id' => $comment->id,
                    'provider_comment_id' => $comment->provider_comment_id,
                    'username' => $comment->username,
                    'text' => Str::limit((string) $comment->text, 140),
                    'status' => $comment->status,
                    'is_hidden' => (bool) $comment->is_hidden,
                    'replied_publicly_at' => optional($comment->replied_publicly_at)->toIso8601String(),
                    'replied_via_dm_at' => optional($comment->replied_via_dm_at)->toIso8601String(),
                    'social_post_id' => $comment->social_post_id,
                    'post_media_id' => $comment->socialPost?->provider_media_id,
                    'commented_at' => optional($comment->commented_at)->toIso8601String(),
                ])
                ->values()
                ->all(),
            'recent_stories' => $storyCollection
                ->take(5)
                ->map(function (SocialStory $story): array {
                    $mediaType = (string) (data_get($story->raw, 'media_type')
                        ?: data_get($story->raw, 'remote_story.media_type')
                        ?: '');

                    return [
                        'id' => $story->id,
                        'provider_story_id' => $story->provider_story_id,
                        'media_type' => $mediaType !== '' ? $mediaType : null,
                        'status' => $story->status,
                        'posted_at' => optional($story->posted_at)->toIso8601String(),
                        'expires_at' => optional($story->expires_at)->toIso8601String(),
                    ];
                })
                ->values()
                ->all(),
        ];

        $webhooks = [
            'event_count' => $webhookCollection->count(),
            'processed_count' => $webhookCollection->filter(fn (WebhookEvent $event) => filled($event->processed_at))->count(),
            'failed_count' => $webhookCollection->where('status', 'failed')->count(),
            'pending_count' => $webhookCollection->filter(fn (WebhookEvent $event) => ! filled($event->processed_at)
                && ($event->status ?? null) !== 'failed')->count(),
            'event_types' => $webhookCollection
                ->groupBy(fn (WebhookEvent $event) => (string) ($event->event_type ?: 'unknown'))
                ->map(fn (Collection $items) => $items->count())
                ->sortKeys()
                ->all(),
            'recent_events' => $webhookCollection
                ->take(8)
                ->map(fn (WebhookEvent $event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'status' => $event->status,
                    'source' => $event->source,
                    'provider_event_id' => $event->provider_event_id,
                    'processed_at' => optional($event->processed_at)->toIso8601String(),
                    'last_error' => $event->last_error,
                    'created_at' => optional($event->created_at)->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];

        $commerce = [
            'catalog_count' => (int) ($commerceMeta['catalog_count'] ?? data_get($commercePacket, 'catalogs.discovered_count', 0)),
            'active_product_count' => (int) data_get($commercePacket, 'local_shop.active_product_count', 0),
            'product_set_count' => (int) data_get($commercePacket, 'local_shop.product_set_count', 0),
            'collection_count' => (int) data_get($commercePacket, 'local_shop.collection_count', 0),
            'order_count' => (int) ($reviewPacketOrders['order_count'] ?? 0),
            'snapshot_count' => (int) ($reviewPacketOrders['snapshot_count'] ?? 0),
            'promotion_campaign_count' => (int) ($reviewPacketPromotions['campaign_count'] ?? 0),
            'prepared_promotion_campaign_count' => (int) ($reviewPacketPromotions['prepared_campaign_count'] ?? 0),
        ];

        $evidenceItems = [
            $this->makeEvidenceItem(
                'commerce_review_readiness',
                'Commerce review readiness',
                match ($reviewPacketSummary['status'] ?? 'needs_attention') {
                    'ready' => 'ok',
                    'blocked' => 'fail',
                    default => 'warn',
                },
                $reviewPacketSummary['headline'] ?? ($review['headline'] ?? 'Generate the commerce review packet to capture current readiness.')
            ),
            $this->makeEvidenceItem(
                'live_webhook_processing',
                'Live webhook processing',
                $webhooks['event_count'] === 0
                    ? 'warn'
                    : ($webhooks['processed_count'] > 0 ? 'ok' : 'warn'),
                $webhooks['event_count'] === 0
                    ? 'No Instagram webhook events are stored yet.'
                    : ('Processed ' . $webhooks['processed_count'] . ' event(s), failed ' . $webhooks['failed_count'] . ', pending ' . $webhooks['pending_count'] . '.')
            ),
            $this->makeEvidenceItem(
                'inbox_threads',
                'Inbox conversations and DMs',
                $inbox['conversation_count'] > 0 && $inbox['message_count'] > 0 ? 'ok' : 'warn',
                'Conversations: ' . $inbox['conversation_count']
                    . ', messages: ' . $inbox['message_count']
                    . ', inbound: ' . $inbox['inbound_message_count']
                    . ', outbound: ' . $inbox['outbound_message_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'customer_profiles',
                'Customer profile enrichment',
                $inbox['customer_profile_count'] > 0
                    && $inbox['customer_profile_with_avatar_count'] > 0
                    && $inbox['customer_profile_with_handle_count'] > 0 ? 'ok' : 'warn',
                'Profiles: ' . $inbox['customer_profile_count']
                    . ', with avatar: ' . $inbox['customer_profile_with_avatar_count']
                    . ', with handle: ' . $inbox['customer_profile_with_handle_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'media_and_catalog_shares',
                'Media and catalog shares in inbox',
                $inbox['attachment_count'] > 0 || $inbox['product_share_count'] > 0 ? 'ok' : 'warn',
                'Attachments: ' . $inbox['attachment_count']
                    . ', media messages: ' . $inbox['media_message_count']
                    . ', product shares: ' . $inbox['product_share_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'social_posts_and_comments',
                'Posts, comments, and moderation',
                $social['post_count'] > 0 && $social['comment_count'] > 0 ? 'ok' : 'warn',
                'Posts: ' . $social['post_count']
                    . ', comments: ' . $social['comment_count']
                    . ', hidden comments: ' . $social['hidden_comment_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'comment_reply_via_dm',
                'Comment reply via DM proof',
                $social['dm_reply_count'] > 0 && $inbox['comment_context_message_count'] > 0 ? 'ok' : 'warn',
                'DM replies from comments: ' . $social['dm_reply_count']
                    . ', inbox messages with comment context: ' . $inbox['comment_context_message_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'story_publish_and_replies',
                'Stories and story replies',
                $social['story_count'] > 0 && $inbox['story_reply_message_count'] > 0 ? 'ok' : 'warn',
                'Stories: ' . $social['story_count']
                    . ', archived stories: ' . $social['archived_story_count']
                    . ', story reply messages: ' . $inbox['story_reply_message_count'] . '.'
            ),
            $this->makeEvidenceItem(
                'orders_and_promotions',
                'Orders and promotion proof',
                ($commerce['order_count'] > 0 && $commerce['snapshot_count'] > 0)
                    || ($commerce['promotion_campaign_count'] > 0 && $commerce['prepared_promotion_campaign_count'] > 0)
                    ? 'ok'
                    : 'warn',
                'Orders: ' . $commerce['order_count']
                    . ', snapshots: ' . $commerce['snapshot_count']
                    . ', campaigns: ' . $commerce['promotion_campaign_count']
                    . ', prepared campaigns: ' . $commerce['prepared_promotion_campaign_count'] . '.'
            ),
        ];

        $nextActions = collect((array) ($reviewPacketEvidence['next_actions'] ?? []))
            ->filter(fn ($item) => is_array($item) && ! empty($item['title']))
            ->values();

        if ($webhooks['event_count'] === 0) {
            $nextActions->push([
                'key' => 'webhook_events',
                'title' => 'Trigger a live webhook event',
                'summary' => 'Send a real DM, comment, or story reply so the final evidence packet includes stored webhook processing proof.',
            ]);
        }

        if ($inbox['conversation_count'] === 0 || $inbox['message_count'] === 0) {
            $nextActions->push([
                'key' => 'inbox_activity',
                'title' => 'Create one live inbox conversation',
                'summary' => 'Send and receive at least one Instagram DM so the inbox flow has real review evidence.',
            ]);
        }

        if ($social['post_count'] === 0 || $social['comment_count'] === 0) {
            $nextActions->push([
                'key' => 'social_activity',
                'title' => 'Collect live post and comment activity',
                'summary' => 'Sync at least one post and one comment so social moderation and reply flows can be demonstrated live.',
            ]);
        }

        if ($social['story_count'] === 0) {
            $nextActions->push([
                'key' => 'stories',
                'title' => 'Publish one Instagram story',
                'summary' => 'A live story makes it easier to prove story publishing, archives, and story-reply flows during review.',
            ]);
        }

        if ($inbox['product_share_count'] === 0) {
            $nextActions->push([
                'key' => 'catalog_shares',
                'title' => 'Send one catalog product in inbox',
                'summary' => 'Share a synced product card in a live conversation so catalog messaging is visible in the review packet.',
            ]);
        }

        $nextActions = $nextActions
            ->unique(fn (array $item) => (string) ($item['key'] ?? $item['title'] ?? ''))
            ->values()
            ->all();

        $blockers = collect($evidenceItems)
            ->where('status', 'fail')
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'summary' => $item['summary'],
            ])
            ->merge(collect((array) ($reviewPacketEvidence['blockers'] ?? [])))
            ->values()
            ->all();

        $warnings = collect($evidenceItems)
            ->where('status', 'warn')
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'summary' => $item['summary'],
            ])
            ->merge(collect((array) ($reviewPacketEvidence['warnings'] ?? [])))
            ->values()
            ->all();

        $counts = [
            'ok' => collect($evidenceItems)->where('status', 'ok')->count(),
            'warn' => collect($evidenceItems)->where('status', 'warn')->count(),
            'fail' => collect($evidenceItems)->where('status', 'fail')->count(),
            'total' => count($evidenceItems),
        ];

        $status = match (true) {
            $counts['fail'] > 0 => 'blocked',
            $counts['warn'] > 0 => 'needs_attention',
            default => 'ready',
        };

        $headline = match ($status) {
            'blocked' => 'The app-review evidence packet is blocked until the failing proof areas are fixed.',
            'needs_attention' => 'Most proof is in place, but a few live product areas still need better evidence before submission.',
            default => 'This Instagram connection has end-to-end product evidence ready for App Review.',
        };

        return [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'provider' => 'instagram',
            'workspace_id' => $connection->workspace_id,
            'provider_connection_id' => $connection->id,
            'account' => [
                'provider_account_id' => $connection->provider_account_id,
                'provider_account_name' => $connection->provider_account_name,
                'username' => $account['username'] ?? null,
                'name' => $account['name'] ?? null,
                'ig_id' => $account['ig_id'] ?? null,
                'review_status' => $account['shopping_review_status'] ?? null,
            ],
            'summary' => [
                'status' => $status,
                'headline' => $headline,
                'counts' => $counts,
            ],
            'coverage' => [
                'inbox' => $inbox,
                'social' => $social,
                'webhooks' => $webhooks,
                'commerce' => $commerce,
            ],
            'evidence' => [
                'items' => $evidenceItems,
                'blockers' => $blockers,
                'warnings' => $warnings,
                'next_actions' => $nextActions,
            ],
            'recent_activity' => [
                'conversations' => $inbox['recent_conversations'],
                'messages' => $inbox['recent_messages'],
                'posts' => $social['recent_posts'],
                'comments' => $social['recent_comments'],
                'stories' => $social['recent_stories'],
                'orders' => array_values((array) ($reviewPacketOrders['recent_orders'] ?? [])),
                'campaigns' => array_values((array) ($reviewPacketPromotions['recent_campaigns'] ?? [])),
                'webhook_events' => $webhooks['recent_events'],
            ],
            'demo_script' => $this->demoScript($connection, $inbox, $social, $webhooks, $commerce),
            'review_notes' => [
                'requested_scopes' => array_values((array) ($reviewPacketNotes['requested_scopes'] ?? [])),
                'submission_focus' => [
                    'Instagram messaging and shared inbox flows',
                    'Comment moderation and reply-via-DM context',
                    'Story publishing, archives, and story replies',
                    'Catalog sync, product tags, product sets, and collections',
                    'Orders, snapshots, and promotion preparation',
                    'Webhook processing and realtime event proof',
                ],
            ],
            'commerce_review_packet' => [
                'generated_at' => $commercePacket['generated_at'] ?? null,
                'status' => $reviewPacketSummary['status'] ?? null,
                'headline' => $reviewPacketSummary['headline'] ?? null,
            ],
        ];
    }

    public function exportFilename(ProviderConnection $connection, array $packet): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string) ($connection->provider_account_name ?: $connection->provider_account_id));
        $slug = trim((string) $slug, '-');
        $slug = $slug !== '' ? strtolower($slug) : 'instagram-account';
        $timestamp = now()->format('Ymd-His');

        return "meta-app-review-evidence-{$slug}-{$timestamp}.json";
    }

    protected function demoScript(
        ProviderConnection $connection,
        array $inbox,
        array $social,
        array $webhooks,
        array $commerce
    ): array {
        return [
            [
                'step' => 1,
                'title' => 'Show the connected Instagram account',
                'summary' => 'Open Commerce settings and show the connected Instagram account ' . ($connection->provider_account_name ?: $connection->provider_account_id) . '.',
            ],
            [
                'step' => 2,
                'title' => 'Open the shared inbox',
                'summary' => 'Show ' . $inbox['conversation_count'] . ' conversation(s), ' . $inbox['message_count'] . ' message(s), and the customer profile context beside the active chat.',
            ],
            [
                'step' => 3,
                'title' => 'Prove media and catalog messaging',
                'summary' => 'Open a conversation with media attachments or a catalog product share to show rich-message handling inside the inbox.',
            ],
            [
                'step' => 4,
                'title' => 'Show comment moderation and DM context',
                'summary' => 'Show ' . $social['comment_count'] . ' comment(s), including public replies and reply-via-DM flows connected back to the inbox.',
            ],
            [
                'step' => 5,
                'title' => 'Show post and story publishing',
                'summary' => 'Open posts and stories to demonstrate publishing, archives, product tags, and story replies.',
            ],
            [
                'step' => 6,
                'title' => 'Show webhook and realtime proof',
                'summary' => 'Show ' . $webhooks['processed_count'] . ' processed webhook event(s) and explain that these drive realtime inbox and social updates.',
            ],
            [
                'step' => 7,
                'title' => 'Open commerce structures',
                'summary' => 'Show ' . $commerce['catalog_count'] . ' catalog(s), ' . $commerce['product_set_count'] . ' product set(s), and ' . $commerce['collection_count'] . ' collection(s).',
            ],
            [
                'step' => 8,
                'title' => 'Show orders and promotions',
                'summary' => 'Open the order ledger and promotion campaigns to show ' . $commerce['order_count'] . ' order(s) and ' . $commerce['promotion_campaign_count'] . ' campaign(s).',
            ],
            [
                'step' => 9,
                'title' => 'Export the final evidence packet',
                'summary' => 'Download the App Review evidence JSON as the final saved snapshot for submission prep.',
            ],
        ];
    }

    protected function makeEvidenceItem(string $key, string $label, string $status, string $summary): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'summary' => $summary,
        ];
    }
}
