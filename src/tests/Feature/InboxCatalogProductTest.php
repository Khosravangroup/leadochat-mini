<?php

namespace Tests\Feature;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InboxCatalogProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_send_catalog_product_card_to_conversation(): void
    {
        Event::fake([WorkspaceRealtimeUpdated::class]);

        $user = User::factory()->create([
            'name' => 'Agent One',
        ]);

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Catalog Test Workspace',
            'slug' => 'catalog-test-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'local-shop-account',
            'provider_account_name' => 'Local Shop',
            'status' => 'connected',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'local',
            'provider_conversation_id' => 'local-conversation-1',
            'type' => 'direct',
            'title' => 'Customer One',
            'status' => 'active',
            'last_message_at' => now()->subMinute(),
        ]);

        $conversation->participants()->create([
            'provider_user_id' => 'local-shop-account',
            'display_name' => 'Local Shop',
            'role' => 'business',
            'is_self' => true,
        ]);

        $conversation->participants()->create([
            'provider_user_id' => 'customer-1',
            'display_name' => 'Customer One',
            'role' => 'customer',
            'is_self' => false,
        ]);

        $catalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'manual',
            'name' => 'Spring Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $catalog->id,
            'sku' => 'SPRING-001',
            'title' => 'Green Linen Shirt',
            'description' => 'Lightweight shirt for warm days.',
            'price' => 49.5,
            'currency' => 'USD',
            'image_url' => 'https://example.com/products/shirt.jpg',
            'product_url' => 'https://example.com/products/green-linen-shirt',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->postJson(route('inbox.catalog-products.send', $conversation), [
                'catalog_product_id' => $product->id,
                'note' => 'This one ships today.',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'conversation_id' => $conversation->id,
                'status' => 'sent',
            ]);

        $message = Message::query()->firstOrFail();

        $this->assertSame('outbound', $message->direction);
        $this->assertSame('product_card', $message->message_type);
        $this->assertSame('sent', $message->status);
        $this->assertStringContainsString('Green Linen Shirt', (string) $message->text_body);
        $this->assertSame('Green Linen Shirt', $message->meta['product_card']['title']);
        $this->assertSame('This one ships today.', $message->meta['product_note']);
        $this->assertSame('Agent One', $message->meta['agent_user']['name']);

        $this->assertDatabaseHas('conversation_product_shares', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'catalog_product_id' => $product->id,
            'agent_id' => $user->id,
        ]);

        $conversation->refresh();

        $this->assertSame('Product: Green Linen Shirt', $conversation->last_message_preview);

        Event::assertDispatched(
            WorkspaceRealtimeUpdated::class,
            fn (WorkspaceRealtimeUpdated $event): bool => $event->workspaceId === $workspace->id
                && $event->domain === 'inbox'
                && $event->action === 'catalog_product_sent'
                && $event->payload['message_id'] === $message->id
        );
    }
}
