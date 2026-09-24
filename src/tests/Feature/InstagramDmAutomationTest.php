<?php

namespace Tests\Feature;

use App\Jobs\ProcessInstagramWebhookEvent;
use App\Models\Message;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstagramDmAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_per_connection_dm_automation_settings_without_losing_other_metadata(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, [
            'identity_payload' => ['username' => 'demo_account'],
        ]);

        $response = $this->actingAs($owner)->patch(route('settings.automation.instagram.update', $connection), [
            'story_reply_enabled' => '1',
            'story_reply_message' => 'Thanks for replying to our story.',
            'comment_dm_enabled' => '1',
            'comment_dm_message' => 'Thanks for your comment.',
        ]);

        $response->assertRedirect(route('settings.index', ['section' => 'automation']));

        $connection->refresh();

        $this->assertSame('demo_account', $connection->meta['identity_payload']['username']);
        $this->assertTrue($connection->meta['dm_automation']['story_reply']['enabled']);
        $this->assertSame('Thanks for replying to our story.', $connection->meta['dm_automation']['story_reply']['message']);
        $this->assertTrue($connection->meta['dm_automation']['comment_dm']['enabled']);
        $this->assertSame('Thanks for your comment.', $connection->meta['dm_automation']['comment_dm']['message']);
    }

    public function test_enabled_automation_requires_a_message_and_foreign_connections_are_hidden(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, []);

        $this->actingAs($owner)
            ->from(route('settings.index', ['section' => 'automation']))
            ->patch(route('settings.automation.instagram.update', $connection), [
                'story_reply_enabled' => '1',
                'story_reply_message' => '   ',
                'comment_dm_enabled' => '0',
                'comment_dm_message' => '',
            ])
            ->assertRedirect(route('settings.index', ['section' => 'automation']))
            ->assertSessionHasErrors('story_reply_message');

        $this->assertArrayNotHasKey('dm_automation', $connection->fresh()->meta ?? []);

        [, $foreignWorkspace] = $this->createWorkspaceOwner();
        $foreignConnection = $this->createInstagramConnection($foreignWorkspace, [], 'foreign-instagram-account-id');

        $this->actingAs($owner)
            ->patch(route('settings.automation.instagram.update', $foreignConnection), [
                'story_reply_enabled' => '0',
                'comment_dm_enabled' => '0',
            ])
            ->assertNotFound();
    }

    public function test_automation_settings_page_lists_connected_instagram_accounts(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, []);

        $this->actingAs($owner)
            ->get(route('settings.index', ['section' => 'automation']))
            ->assertOk()
            ->assertSee('Send a DM after every story reply')
            ->assertSee('Send a private DM after every new comment')
            ->assertSee(route('settings.automation.instagram.update', $connection), false)
            ->assertDontSee('Automation will be added soon.');
    }

    public function test_automation_is_disabled_by_default_for_new_connections(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, []);
        $this->createAccessToken($connection);
        Http::fake([
            '*' => Http::response([
                'id' => 'customer-igsid',
                'username' => 'customer',
                'name' => 'Customer',
                'profile_pic' => null,
            ]),
        ]);

        $storyReply = $this->createStoryReplyEvent($workspace, $connection, 'default-disabled-story');
        $comment = $this->createCommentEvent($workspace, $connection, 'default-disabled-comment');

        (new ProcessInstagramWebhookEvent($storyReply->id))->handle();
        (new ProcessInstagramWebhookEvent($comment->id))->handle();

        $sendRequests = Http::recorded(fn (Request $request): bool => $request->method() === 'POST');
        $this->assertCount(0, $sendRequests);
        $this->assertDatabaseMissing('messages', [
            'direction' => 'outbound',
        ]);
    }

    public function test_inbound_story_reply_sends_the_configured_dm_once(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, [
            'dm_automation' => [
                'story_reply' => [
                    'enabled' => true,
                    'message' => 'Story reply automation',
                ],
                'comment_dm' => [
                    'enabled' => false,
                    'message' => '',
                ],
            ],
        ]);
        $this->createAccessToken($connection);
        Http::fake([
            '*' => Http::response([
                'recipient_id' => 'customer-igsid',
                'message_id' => 'auto-story-message-id',
            ]),
        ]);

        $event = $this->createStoryReplyEvent($workspace, $connection);

        (new ProcessInstagramWebhookEvent($event->id))->handle();
        $duplicateEvent = $this->createStoryReplyEvent($workspace, $connection, 'story-reply-event-duplicate');
        (new ProcessInstagramWebhookEvent($duplicateEvent->id))->handle();

        $storyAutomationRequests = Http::recorded(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://graph.instagram.com/v25.0/instagram-account-id/messages';
        });
        $this->assertCount(1, $storyAutomationRequests);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.instagram.com/v25.0/instagram-account-id/messages'
                && $request['recipient']['id'] === 'customer-igsid'
                && $request['message']['text'] === 'Story reply automation';
        });

        $message = Message::query()->where('provider_message_id', 'story-reply-mid')->firstOrFail();
        $this->assertSame('sent', $message->meta['dm_automation']['story_reply']['status']);
    }

    public function test_new_comment_sends_the_configured_private_reply_once_and_persists_it_in_the_inbox(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, [
            'dm_automation' => [
                'story_reply' => [
                    'enabled' => false,
                    'message' => '',
                ],
                'comment_dm' => [
                    'enabled' => true,
                    'message' => 'Comment automation',
                ],
            ],
        ]);
        $this->createAccessToken($connection);
        Http::fake([
            '*' => Http::response([
                'recipient_id' => 'commenter-igsid',
                'message_id' => 'auto-comment-message-id',
            ]),
        ]);

        $event = $this->createCommentEvent($workspace, $connection);

        (new ProcessInstagramWebhookEvent($event->id))->handle();
        $duplicateEvent = $this->createCommentEvent($workspace, $connection, 'comment-event-duplicate');
        (new ProcessInstagramWebhookEvent($duplicateEvent->id))->handle();

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.instagram.com/v25.0/instagram-account-id/messages'
                && $request['recipient']['comment_id'] === 'comment-id-1'
                && $request['message']['text'] === 'Comment automation';
        });

        $comment = SocialComment::query()->where('provider_comment_id', 'comment-id-1')->firstOrFail();
        $this->assertNotNull($comment->replied_via_dm_at);
        $this->assertSame('sent', $comment->raw['dm_automation']['comment_dm']['status']);
        $this->assertDatabaseHas('messages', [
            'provider_message_id' => 'auto-comment-message-id',
            'provider_reply_to_message_id' => 'comment-id-1',
            'direction' => 'outbound',
            'text_body' => 'Comment automation',
        ]);
    }

    public function test_regular_messages_deleted_updated_and_self_comments_do_not_trigger_automation(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, [
            'dm_automation' => [
                'story_reply' => [
                    'enabled' => true,
                    'message' => 'Story reply automation',
                ],
                'comment_dm' => [
                    'enabled' => true,
                    'message' => 'Comment automation',
                ],
            ],
        ]);
        $this->createAccessToken($connection);
        Http::fake([
            '*' => Http::response([
                'id' => 'customer-igsid',
                'username' => 'customer',
                'name' => 'Customer',
                'profile_pic' => null,
            ]),
        ]);

        $ordinaryMessage = $this->createStoryReplyEvent(
            $workspace,
            $connection,
            'ordinary-message-event',
            false
        );
        $deletedComment = $this->createCommentEvent(
            $workspace,
            $connection,
            'deleted-comment-event',
            'deleted-comment-id',
            'commenter-igsid',
            'delete'
        );
        $selfComment = $this->createCommentEvent(
            $workspace,
            $connection,
            'self-comment-event',
            'self-comment-id',
            $connection->provider_account_id
        );
        $updatedComment = $this->createCommentEvent(
            $workspace,
            $connection,
            'updated-comment-event',
            'updated-comment-id',
            'commenter-igsid',
            'edit'
        );

        (new ProcessInstagramWebhookEvent($ordinaryMessage->id))->handle();
        (new ProcessInstagramWebhookEvent($deletedComment->id))->handle();
        (new ProcessInstagramWebhookEvent($selfComment->id))->handle();
        (new ProcessInstagramWebhookEvent($updatedComment->id))->handle();

        $sendRequests = Http::recorded(fn (Request $request): bool => $request->method() === 'POST');
        $this->assertCount(0, $sendRequests);
        $this->assertDatabaseMissing('messages', [
            'direction' => 'outbound',
        ]);
    }

    public function test_provider_failure_is_recorded_safely_without_reopening_webhook_processing(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();
        $connection = $this->createInstagramConnection($workspace, [
            'dm_automation' => [
                'story_reply' => [
                    'enabled' => false,
                    'message' => '',
                ],
                'comment_dm' => [
                    'enabled' => true,
                    'message' => 'Comment automation',
                ],
            ],
        ]);
        $this->createAccessToken($connection);
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Provider rejected token test-access-token',
                ],
            ], 500),
        ]);

        $event = $this->createCommentEvent($workspace, $connection, 'failed-comment-event', 'failed-comment-id');

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $this->assertSame('processed', $event->fresh()->status);
        $comment = SocialComment::query()->where('provider_comment_id', 'failed-comment-id')->firstOrFail();
        $this->assertNull($comment->replied_via_dm_at);
        $this->assertSame('failed', $comment->raw['dm_automation']['comment_dm']['status']);
        $this->assertSame('provider_send_failed', $comment->raw['dm_automation']['comment_dm']['error_code']);
        $this->assertStringNotContainsString('test-access-token', json_encode($comment->raw, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function createWorkspaceOwner(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Automation Workspace',
            'slug' => 'automation-workspace-'.str()->random(10),
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $workspace];
    }

    private function createInstagramConnection(
        Workspace $workspace,
        array $meta,
        string $providerAccountId = 'instagram-account-id'
    ): ProviderConnection {
        return ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => $providerAccountId,
            'external_oauth_user_id' => 'instagram-oauth-user-id',
            'provider_account_name' => 'demo_account',
            'status' => 'connected',
            'meta' => $meta,
        ]);
    }

    private function createAccessToken(ProviderConnection $connection): void
    {
        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-access-token',
            'refresh_token' => null,
            'expires_at' => now()->addHour(),
            'scopes' => 'instagram_business_manage_messages,instagram_business_manage_comments',
            'is_primary' => true,
        ]);
    }

    private function createStoryReplyEvent(
        Workspace $workspace,
        ProviderConnection $connection,
        string $providerEventId = 'story-reply-event-1',
        bool $isStoryReply = true
    ): WebhookEvent {
        $message = [
            'mid' => 'story-reply-mid',
            'text' => 'I like this story',
        ];

        if ($isStoryReply) {
            $message['reply_to'] = [
                'story' => ['id' => 'story-id-1'],
            ];
        }

        return WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => $providerEventId,
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => $connection->external_oauth_user_id,
                    'time' => 1776691234,
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => ['id' => 'customer-igsid'],
                        'recipient' => ['id' => $connection->external_oauth_user_id],
                        'timestamp' => 1776691234,
                        'message' => $message,
                    ],
                ],
            ],
        ]);
    }

    private function createCommentEvent(
        Workspace $workspace,
        ProviderConnection $connection,
        string $providerEventId = 'comment-event-1',
        string $providerCommentId = 'comment-id-1',
        string $providerUserId = 'commenter-igsid',
        string $verb = 'add'
    ): WebhookEvent {
        return WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'comments',
            'object' => 'instagram',
            'provider_event_id' => $providerEventId,
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => $connection->external_oauth_user_id,
                    'time' => 1776691234,
                ],
                'change' => [
                    'field' => 'comments',
                    'value' => [
                        'id' => $providerCommentId,
                        'from' => [
                            'id' => $providerUserId,
                            'username' => 'commenter',
                        ],
                        'text' => 'Please send details',
                        'verb' => $verb,
                        'timestamp' => 1776691234,
                    ],
                ],
            ],
        ]);
    }
}
