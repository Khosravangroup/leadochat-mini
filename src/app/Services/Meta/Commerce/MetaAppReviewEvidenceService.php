<?php

namespace App\Services\Meta\Commerce;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\SocialStory;
use App\Models\WebhookEvent;
use Illuminate\Support\Collection;

class MetaAppReviewEvidenceService
{
    public function generateForConnection(
        ProviderConnection $connection,
        array $diagnostics
    ): array {
        $workspace = $connection->workspace;
        $account = is_array($diagnostics['account'] ?? null) ? $diagnostics['account'] : [];
        $permissions = is_array($diagnostics['permissions'] ?? null) ? $diagnostics['permissions'] : [];

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
        ];

        $social = [
            'post_count' => $postCollection->count(),
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
        ];

        $evidenceItems = [
            $this->makeEvidenceItem(
                'instagram_account_connection',
                'Instagram account connection',
                $connection->status === 'connected' && filled($connection->provider_account_id) ? 'ok' : 'fail',
                $connection->status === 'connected' && filled($connection->provider_account_id)
                    ? 'A connected Instagram account is available for the review walkthrough.'
                    : 'Connect an Instagram professional account before recording the review walkthrough.'
            ),
            $this->makeEvidenceItem(
                'instagram_permission_journeys',
                'Instagram permission API journeys',
                ((array) ($permissions['instagram_requested'] ?? [])) !== []
                    && ((array) ($permissions['instagram_without_demonstrated_api_journey'] ?? [])) === [] ? 'ok' : 'fail',
                ((array) ($permissions['instagram_without_demonstrated_api_journey'] ?? [])) === []
                    ? 'Every configured Instagram permission has a code-backed journey; Meta grants and live behavior still require provider verification.'
                    : 'One or more configured Instagram permissions do not have a code-backed journey.'
            ),
            $this->makeEvidenceItem(
                'live_webhook_processing',
                'Live webhook processing',
                $webhooks['event_count'] === 0
                    ? 'warn'
                    : ($webhooks['processed_count'] > 0 ? 'ok' : 'warn'),
                $webhooks['event_count'] === 0
                    ? 'No Instagram webhook events are stored yet.'
                    : ('Processed '.$webhooks['processed_count'].' event(s), failed '.$webhooks['failed_count'].', pending '.$webhooks['pending_count'].'.')
            ),
            $this->makeEvidenceItem(
                'inbox_threads',
                'Inbox conversations and DMs',
                $inbox['conversation_count'] > 0 && $inbox['message_count'] > 0 ? 'ok' : 'warn',
                'Conversations: '.$inbox['conversation_count']
                    .', messages: '.$inbox['message_count']
                    .', inbound: '.$inbox['inbound_message_count']
                    .', outbound: '.$inbox['outbound_message_count'].'.'
            ),
            $this->makeEvidenceItem(
                'customer_profiles',
                'Customer profile enrichment',
                $inbox['customer_profile_count'] > 0
                    && $inbox['customer_profile_with_avatar_count'] > 0
                    && $inbox['customer_profile_with_handle_count'] > 0 ? 'ok' : 'warn',
                'Profiles: '.$inbox['customer_profile_count']
                    .', with avatar: '.$inbox['customer_profile_with_avatar_count']
                    .', with handle: '.$inbox['customer_profile_with_handle_count'].'.'
            ),
            $this->makeEvidenceItem(
                'media_messages',
                'Media messages in inbox',
                $inbox['attachment_count'] > 0 || $inbox['media_message_count'] > 0 ? 'ok' : 'warn',
                'Attachments: '.$inbox['attachment_count']
                    .', media messages: '.$inbox['media_message_count'].'.'
            ),
            $this->makeEvidenceItem(
                'social_posts_and_comments',
                'Posts, comments, and moderation',
                $social['post_count'] > 0 && $social['comment_count'] > 0 ? 'ok' : 'warn',
                'Posts: '.$social['post_count']
                    .', comments: '.$social['comment_count']
                    .', hidden comments: '.$social['hidden_comment_count'].'.'
            ),
            $this->makeEvidenceItem(
                'comment_reply_via_dm',
                'Comment reply via DM proof',
                $social['dm_reply_count'] > 0 && $inbox['comment_context_message_count'] > 0 ? 'ok' : 'warn',
                'DM replies from comments: '.$social['dm_reply_count']
                    .', inbox messages with comment context: '.$inbox['comment_context_message_count'].'.'
            ),
            $this->makeEvidenceItem(
                'story_publish_and_replies',
                'Stories and story replies',
                $social['story_count'] > 0 && $inbox['story_reply_message_count'] > 0 ? 'ok' : 'warn',
                'Stories: '.$social['story_count']
                    .', archived stories: '.$social['archived_story_count']
                    .', story reply messages: '.$inbox['story_reply_message_count'].'.'
            ),
        ];

        $nextActions = collect();

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
            ->values()
            ->all();

        $warnings = collect($evidenceItems)
            ->where('status', 'warn')
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'summary' => $item['summary'],
            ])
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
            'blocked' => 'The Instagram App Review evidence packet has a local blocker.',
            'needs_attention' => 'The local evidence packet is usable, but live walkthrough evidence is still incomplete.',
            default => 'The local Instagram evidence packet is ready for a live provider walkthrough; Meta grants remain separately verified.',
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
                'username' => $account['username'] ?? $connection->provider_account_name,
                'name' => $account['name'] ?? $connection->provider_account_name,
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
            ],
            'evidence' => [
                'items' => $evidenceItems,
                'blockers' => $blockers,
                'warnings' => $warnings,
                'next_actions' => $nextActions,
            ],
            'demo_script' => $this->demoScript($connection, $inbox, $social, $webhooks),
            'review_notes' => [
                'requested_scopes' => array_values((array) ($permissions['instagram_requested'] ?? [])),
                'submission_focus' => [
                    'Instagram professional account identity',
                    'Instagram messaging and shared inbox flows',
                    'Instagram comment moderation and reply context',
                    'Instagram content publishing',
                    'Instagram account insights',
                ],
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
        array $webhooks
    ): array {
        return [
            [
                'step' => 1,
                'title' => 'Show the connected Instagram account',
                'summary' => 'Open Commerce settings and show the connected Instagram account '.($connection->provider_account_name ?: $connection->provider_account_id).'.',
            ],
            [
                'step' => 2,
                'title' => 'Open the shared inbox',
                'summary' => 'Show '.$inbox['conversation_count'].' conversation(s), '.$inbox['message_count'].' message(s), and the customer profile context beside the active chat.',
            ],
            [
                'step' => 3,
                'title' => 'Prove media messaging',
                'summary' => 'Open a conversation with a media attachment to show rich-message handling inside the inbox.',
            ],
            [
                'step' => 4,
                'title' => 'Show comment moderation and DM context',
                'summary' => 'Show '.$social['comment_count'].' comment(s), including public replies and reply-via-DM flows connected back to the inbox.',
            ],
            [
                'step' => 5,
                'title' => 'Show post and story publishing',
                'summary' => 'Open posts and stories to demonstrate content publishing, archives, and story replies.',
            ],
            [
                'step' => 6,
                'title' => 'Show webhook and realtime proof',
                'summary' => 'Show '.$webhooks['processed_count'].' processed webhook event(s) and explain that these drive realtime inbox and social updates.',
            ],
            [
                'step' => 7,
                'title' => 'Open Instagram insights',
                'summary' => 'Open the account insights page and show reach, views, and total interactions for the connected account.',
            ],
            [
                'step' => 8,
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
