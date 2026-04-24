<?php

namespace Tests\Unit;

use App\Jobs\ProcessInstagramWebhookEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
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

    public function test_it_preserves_catalog_product_card_when_instagram_echoes_template_attachment(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Catalog Echo Workspace',
            'slug' => 'catalog-echo-workspace',
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
        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => 'instagram:dm:17841439881436376:customer-igsid',
            'type' => 'dm',
            'title' => 'Instagram DM',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $selfParticipant = $conversation->participants()->create([
            'provider_user_id' => '17841439881436376',
            'display_name' => 'msmsu.ir',
            'role' => 'participant',
            'is_self' => true,
        ]);
        $conversation->participants()->create([
            'provider_user_id' => 'customer-igsid',
            'display_name' => 'Instagram User',
            'role' => 'participant',
            'is_self' => false,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_participant_id' => $selfParticipant->id,
            'provider' => 'instagram',
            'provider_message_id' => 'mid-template-card',
            'direction' => 'outbound',
            'message_type' => 'product_card',
            'text_body' => "Product recommendation\nYellow Notebook",
            'status' => 'sent',
            'sent_at' => now(),
            'meta' => [
                'product_card' => [
                    'title' => 'Yellow Notebook',
                    'description' => 'Original Leadochat product description.',
                    'image_url' => 'https://example.com/notebook.jpg',
                    'product_url' => 'https://example.com/notebook',
                ],
            ],
        ]);

        $event = WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'instagram:17841439881436376:messages:mid-template-card:1776691234',
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => '17841439881436376',
                    'time' => 1776691234,
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => ['id' => '17841439881436376'],
                        'recipient' => ['id' => 'customer-igsid'],
                        'timestamp' => 1776691234,
                        'message' => [
                            'mid' => 'mid-template-card',
                            'attachments' => [
                                [
                                    'type' => 'template',
                                    'payload' => [
                                        'template_type' => 'generic',
                                        'elements' => [
                                            [
                                                'title' => 'Yellow Notebook',
                                                'subtitle' => 'USD 15.00 - Good match.',
                                                'image_url' => 'https://example.com/notebook.jpg',
                                                'default_action' => [
                                                    'type' => 'web_url',
                                                    'url' => 'https://example.com/notebook',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $message->refresh();

        $this->assertSame('processed', $event->refresh()->status);
        $this->assertSame('product_card', $message->message_type);
        $this->assertSame("Product recommendation\nYellow Notebook", $message->text_body);
        $this->assertSame('Yellow Notebook', $message->meta['product_card']['title']);
        $this->assertSame(0, MessageAttachment::query()->where('message_id', $message->id)->count());
    }

    public function test_it_does_not_downgrade_existing_product_card_to_attachment_when_echo_shape_is_ambiguous(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Catalog Echo Fallback Workspace',
            'slug' => 'catalog-echo-fallback-workspace',
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
        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => 'instagram:dm:17841439881436376:customer-igsid',
            'type' => 'dm',
            'title' => 'Instagram DM',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $selfParticipant = $conversation->participants()->create([
            'provider_user_id' => '17841439881436376',
            'display_name' => 'msmsu.ir',
            'role' => 'participant',
            'is_self' => true,
        ]);
        $conversation->participants()->create([
            'provider_user_id' => 'customer-igsid',
            'display_name' => 'Instagram User',
            'role' => 'participant',
            'is_self' => false,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_participant_id' => $selfParticipant->id,
            'provider' => 'instagram',
            'provider_message_id' => 'mid-ambiguous-card',
            'direction' => 'outbound',
            'message_type' => 'product_card',
            'text_body' => "Product recommendation\nYellow Notebook",
            'status' => 'sent',
            'sent_at' => now(),
            'meta' => [
                'delivery_mode' => 'instagram_service_catalog_product_template',
                'product_card' => [
                    'title' => 'Yellow Notebook',
                    'description' => 'Original Leadochat product description.',
                    'image_url' => 'https://example.com/notebook.jpg',
                    'product_url' => 'https://example.com/notebook',
                ],
            ],
        ]);

        MessageAttachment::create([
            'message_id' => $message->id,
            'attachment_type' => 'image',
            'url' => 'https://example.com/old-attachment.jpg',
            'sort_order' => 0,
        ]);

        $event = WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'instagram:17841439881436376:messages:mid-ambiguous-card:1776691234',
            'status' => 'received',
            'source' => 'webhook',
            'headers' => [],
            'payload' => [
                'object' => 'instagram',
                'entry' => [
                    'id' => '17841439881436376',
                    'time' => 1776691234,
                ],
                'change' => [
                    'field' => 'messages',
                    'value' => [
                        'sender' => ['id' => '17841439881436376'],
                        'recipient' => ['id' => 'customer-igsid'],
                        'timestamp' => 1776691234,
                        'message' => [
                            'mid' => 'mid-ambiguous-card',
                            'attachments' => [
                                [
                                    'type' => 'image',
                                    'payload' => [
                                        'url' => 'https://example.com/notebook-rendered.jpg',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        (new ProcessInstagramWebhookEvent($event->id))->handle();

        $message->refresh();

        $this->assertSame('processed', $event->refresh()->status);
        $this->assertSame('product_card', $message->message_type);
        $this->assertSame("Product recommendation\nYellow Notebook", $message->text_body);
        $this->assertSame('Yellow Notebook', $message->meta['product_card']['title']);
        $this->assertSame(0, MessageAttachment::query()->where('message_id', $message->id)->count());
    }
}
