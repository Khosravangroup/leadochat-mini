<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCatalogProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_leadochat_products_to_discovered_meta_catalog(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/meta-catalog-123/batch' => Http::response([
                'handles' => ['test-meta-catalog-batch-handle'],
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Meta Product Sync Workspace',
            'slug' => 'meta-product-sync-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-123',
            'provider_account_name' => 'leadochat_shop',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-meta-access-token',
            'is_primary' => true,
        ]);

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Leadochat Source Catalog',
            'status' => 'active',
        ]);

        $targetCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-123',
            'external_business_id' => 'business-123',
            'name' => 'Meta Target Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'sku' => 'SKU-123',
            'title' => 'Yellow Notebook',
            'description' => 'A compact notebook for catalog sync.',
            'price' => 15,
            'currency' => 'USD',
            'image_url' => 'https://example.com/notebook.jpg',
            'product_url' => 'https://example.com/notebook',
            'availability' => 'in_stock',
            'is_active' => true,
            'metadata' => [
                'brand' => 'Leadochat Test',
                'inventory' => 7,
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.catalogs.products.sync', $targetCatalog), [
                'source_catalog_id' => $sourceCatalog->id,
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $product->refresh();
        $targetCatalog->refresh();

        $this->assertSame('SKU-123', $product->external_product_id);
        $this->assertSame('queued', $product->meta_sync_status);
        $this->assertSame('queued', $targetCatalog->meta_sync_status);
        $syncResult = $targetCatalog->meta['last_product_sync']['result'];
        $requests = json_decode($syncResult['payload']['requests'] ?? '[]', true);
        $first = $requests[0] ?? [];

        $batchHandle = (string) $targetCatalog->meta['last_product_sync']['batch_handle'];
        $this->assertTrue(
            $batchHandle === 'test-meta-catalog-batch-handle'
            || str_starts_with($batchHandle, 'local-debug-meta-catalog-batch-')
        );
        $this->assertSame('https://graph.facebook.com/v25.0/meta-catalog-123/batch', $syncResult['endpoint']);
        $this->assertSame('UPDATE', $first['method']);
        $this->assertSame('SKU-123', $first['retailer_id']);
        $this->assertSame('Yellow Notebook', $first['data']['name']);
        $this->assertSame('in stock', $first['data']['availability']);
        $this->assertSame('15.00 USD', $first['data']['price']);
        $this->assertSame('Leadochat Test', $first['data']['brand']);
        $this->assertSame(7, $first['data']['inventory']);
    }
}
