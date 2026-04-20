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
}
