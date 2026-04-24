<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CommerceOrder;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaCommerceOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_create_update_and_snapshot_a_test_order(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Commerce Orders Workspace',
            'slug' => 'commerce-orders-workspace',
        ]);

        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-orders',
            'provider_account_name' => 'orders_shop',
            'status' => 'connected',
        ]);

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Orders Source Catalog',
            'status' => 'active',
        ]);

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-orders-catalog',
            'name' => 'Orders Meta Catalog',
            'status' => 'active',
        ]);

        $collection = CatalogCollection::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Orders Collection',
            'status' => 'active',
            'meta_sync_status' => 'not_synced',
        ]);

        $productA = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'sku' => 'ORDER-001',
            'title' => 'Order Product A',
            'price' => 20,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'synced',
        ]);

        $productB = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'sku' => 'ORDER-002',
            'title' => 'Order Product B',
            'price' => 15,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'queued',
        ]);

        $createResponse = $this
            ->actingAs($user)
            ->post(route('settings.commerce.orders.store'), [
                'provider_connection_id' => $connection->id,
                'catalog_id' => $sourceCatalog->id,
                'catalog_collection_id' => $collection->id,
                'product_ids' => [$productA->id, $productB->id],
                'external_order_id' => 'TEST-ORDER-1',
                'customer_reference' => 'customer-1',
                'customer_name' => 'Order Test Customer',
                'customer_email' => 'customer@example.com',
                'source' => 'manual_test',
                'status' => 'placed',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'currency' => 'USD',
                'discount_amount' => 5,
                'tax_amount' => 2,
                'shipping_amount' => 3,
                'notes' => 'Initial test order.',
            ]);

        $createResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $order = CommerceOrder::query()->with(['items', 'snapshots'])->firstOrFail();

        $this->assertSame('TEST-ORDER-1', $order->external_order_id);
        $this->assertTrue($order->is_test);
        $this->assertCount(2, $order->items);
        $this->assertSame('35.00', $order->subtotal_amount);
        $this->assertSame('35.00', $order->total_amount);
        $this->assertCount(1, $order->snapshots);
        $this->assertSame('test_order_created', $order->snapshots->first()->snapshot_type);

        $updateResponse = $this
            ->actingAs($user)
            ->patch(route('settings.commerce.orders.status.update', $order), [
                'status' => 'fulfilled',
                'payment_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'notes' => 'Order fulfilled in test flow.',
            ]);

        $updateResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $snapshotResponse = $this
            ->actingAs($user)
            ->post(route('settings.commerce.orders.snapshots.store', $order), [
                'snapshot_type' => 'review_demo',
                'notes' => 'Manual snapshot for review demo.',
            ]);

        $snapshotResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $order->refresh()->load(['items', 'snapshots']);

        $this->assertSame('fulfilled', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('fulfilled', $order->fulfillment_status);
        $this->assertCount(3, $order->snapshots);
        $this->assertSame('review_demo', $order->snapshots->first()->snapshot_type);
        $this->assertSame('status_updated', $order->snapshots->skip(1)->first()->snapshot_type);
        $this->assertSame('manual_test', $order->source);
    }
}
