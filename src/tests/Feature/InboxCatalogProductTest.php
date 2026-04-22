<?php

namespace Tests\Feature;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\OauthToken;
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

    public function test_instagram_catalog_product_send_uses_generic_template_payload(): void
    {
        Event::fake([WorkspaceRealtimeUpdated::class]);

        $user = User::factory()->create([
            'name' => 'Agent Two',
        ]);

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Instagram Catalog Workspace',
            'slug' => 'instagram-catalog-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'test-instagram-account',
            'provider_account_name' => 'Leadochat',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-access-token',
            'is_primary' => true,
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => 'instagram-conversation-1',
            'type' => 'direct',
            'title' => 'Customer Two',
            'status' => 'active',
            'last_message_at' => now()->subMinute(),
        ]);

        $conversation->participants()->create([
            'provider_user_id' => 'test-instagram-account',
            'display_name' => 'Leadochat',
            'role' => 'business',
            'is_self' => true,
        ]);

        $conversation->participants()->create([
            'provider_user_id' => 'instagram-customer-2',
            'display_name' => 'Customer Two',
            'role' => 'customer',
            'is_self' => false,
        ]);

        $catalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'manual',
            'name' => 'Instagram Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $catalog->id,
            'sku' => 'IG-001',
            'title' => 'Yellow Notebook',
            'description' => 'A compact product card test.',
            'price' => 15,
            'currency' => 'USD',
            'image_url' => 'https://example.com/products/notebook.jpg',
            'product_url' => 'https://example.com/products/yellow-notebook',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->postJson(route('inbox.catalog-products.send', $conversation), [
                'catalog_product_id' => $product->id,
                'note' => 'Good match for this customer.',
            ]);

        $response->assertOk();

        $message = Message::query()->latest('id')->firstOrFail();
        $payload = $message->meta['send_result']['payload'];

        $this->assertSame('product_card', $message->message_type);
        $this->assertSame('instagram_service_catalog_product_template', $message->meta['delivery_mode']);
        $this->assertSame('instagram-customer-2', $payload['recipient']['id']);
        $this->assertSame('template', $payload['message']['attachment']['type']);
        $this->assertSame('generic', $payload['message']['attachment']['payload']['template_type']);
        $this->assertSame('Yellow Notebook', $payload['message']['attachment']['payload']['elements'][0]['title']);
        $this->assertSame('View product', $payload['message']['attachment']['payload']['elements'][0]['buttons'][0]['title']);
    }
}
