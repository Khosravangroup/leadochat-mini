<?php

namespace Tests\Unit;

use App\Jobs\ProcessInstagramWebhookEvent;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessInstagramWebhookEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_inbound_messages_with_external_oauth_user_id_as_self_id(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => '17841439881436376',
            'external_oauth_user_id' => '35082347498047063',
            'provider_account_name' => 'msmsu.ir',
            'status' => 'connected',
            'meta' => [
                'identity_payload' => [
                    'id' => '35082347498047063',
                    'user_id' => '17841439881436376',
                    'username' => 'msmsu.ir',
                ],
            ],
        ]);

        $event = WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'instagram:35082347498047063:messages:test-mid:1776691234',
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => '35082347498047063',
                    'time' => 1776691234,
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => ['id' => 'customer-igsid'],
                        'recipient' => ['id' => '35082347498047063'],
                        'timestamp' => 1776691234,
                        'message' => [
                            'mid' => 'test-mid',
                            'message' => 'Hello from the official message field',
                        ],
                    ],
                ],
            ],
        ]);

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $event->refresh();
        $message = Message::query()->first();

        $this->assertSame('processed', $event->status);
        $this->assertNotNull($message);
        $this->assertSame('inbound', $message->direction);
        $this->assertSame('test-mid', $message->provider_message_id);
        $this->assertSame('Hello from the official message field', $message->text_body);
        $this->assertSame('customer-igsid', $event->payload['normalized']['resolved_customer_provider_user_id']);
        $this->assertSame('35082347498047063', $event->payload['normalized']['resolved_self_provider_user_id']);
    }

    public function test_it_classifies_instagram_read_receipts_without_creating_messages(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => '17841439881436376',
            'external_oauth_user_id' => '35082347498047063',
            'provider_account_name' => 'msmsu.ir',
            'status' => 'connected',
        ]);

        $event = WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'instagram:17841439881436376:messages:read-mid:1776692807742',
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => '17841439881436376',
                    'time' => 1776692808369,
                    'messaging' => [
                        [
                            'read' => [
                                'mid' => 'read-mid',
                            ],
                            'timestamp' => 1776692807742,
                        ],
                    ],
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => [],
                        'recipient' => [],
                        'message' => [],
                        'messaging' => [
                            [
                                'read' => [
                                    'mid' => 'read-mid',
                                ],
                                'timestamp' => 1776692807742,
                            ],
                        ],
                        'timestamp' => 1776692807742,
                    ],
                ],
            ],
        ]);

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $event->refresh();

        $this->assertSame('processed', $event->status);
        $this->assertSame('message_read', $event->payload['normalized']['kind']);
        $this->assertSame('read-mid', $event->payload['normalized']['provider_message_id']);
        $this->assertNull($event->payload['processing']['ignored_reason']);
        $this->assertSame(0, Message::query()->count());
    }

    public function test_it_classifies_instagram_message_edit_events_without_creating_messages(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => '17841407535874435',
            'provider_account_name' => 'msmsu.ir',
            'status' => 'connected',
        ]);

        $event = WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'instagram:17841407535874435:messages:edit-mid:1776707541321',
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => '17841407535874435',
                    'time' => 1776707541529,
                    'messaging' => [
                        [
                            'timestamp' => 1776707541321,
                            'message_edit' => [
                                'mid' => 'edit-mid',
                                'num_edit' => 0,
                            ],
                        ],
                    ],
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => [],
                        'recipient' => [],
                        'message' => [],
                        'messaging' => [
                            [
                                'timestamp' => 1776707541321,
                                'message_edit' => [
                                    'mid' => 'edit-mid',
                                    'num_edit' => 0,
                                ],
                            ],
                        ],
                        'timestamp' => 1776707541321,
                    ],
                ],
            ],
        ]);

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $event->refresh();

        $this->assertSame('processed', $event->status);
        $this->assertSame('message_edit', $event->payload['normalized']['kind']);
        $this->assertSame('edit-mid', $event->payload['normalized']['provider_message_id']);
        $this->assertNull($event->payload['processing']['ignored_reason']);
        $this->assertSame(0, Message::query()->count());
    }
}
