<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCommerceStructureSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_sync_product_set_to_meta(): void
    {
        [$user, $metaCatalog, $productSet] = $this->makeCommerceStructure();

        Http::fake([
            'https://graph.facebook.com/v25.0/meta-catalog-123/product_sets' => Http::response([
                'id' => 'meta-product-set-123',
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.product-sets.sync', $productSet));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $productSet->refresh();

        $this->assertSame('meta-product-set-123', $productSet->external_product_set_id);
        $this->assertSame('synced', $productSet->meta_sync_status);
        $this->assertSame($metaCatalog->external_catalog_id, $productSet->meta['last_meta_sync']['external_catalog_id'] ?? null);
        $this->assertStringNotContainsString(
            'test-meta-access-token',
            json_encode($productSet->meta, JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            '[redacted]',
            $productSet->meta['last_meta_sync']['result']['payload']['access_token'] ?? null
        );
        Http::assertSent(function (Request $request): bool {
            parse_str($request->body(), $body);

            return ($body['access_token'] ?? null) === 'test-meta-access-token';
        });
    }

    public function test_agent_can_sync_collection_to_meta(): void
    {
        [$user, $metaCatalog, $productSet] = $this->makeCommerceStructure();
        $productSet->update([
            'external_product_set_id' => 'meta-product-set-123',
            'meta_sync_status' => 'synced',
        ]);

        $collection = CatalogCollection::create([
            'workspace_id' => $metaCatalog->workspace_id,
            'provider_connection_id' => $metaCatalog->provider_connection_id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Featured collection',
            'status' => 'active',
            'meta_sync_status' => 'not_synced',
        ]);
        $collection->productSets()->attach($productSet->id, [
            'sort_order' => 0,
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/meta-catalog-123/collections' => Http::response([
                'id' => 'meta-collection-123',
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.collections.sync', $collection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $collection->refresh();

        $this->assertSame('meta-collection-123', $collection->external_collection_id);
        $this->assertSame('synced', $collection->meta_sync_status);
        $this->assertSame($metaCatalog->external_catalog_id, $collection->meta['last_meta_sync']['external_catalog_id'] ?? null);
        $this->assertStringNotContainsString(
            'test-meta-access-token',
            json_encode($collection->meta, JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            '[redacted]',
            $collection->meta['last_meta_sync']['result']['payload']['access_token'] ?? null
        );
        Http::assertSent(function (Request $request): bool {
            parse_str($request->body(), $body);

            return ($body['access_token'] ?? null) === 'test-meta-access-token';
        });
    }

    protected function makeCommerceStructure(): array
    {
        config([
            'services.meta.graph_version' => 'v25.0',
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Commerce Sync Workspace',
            'slug' => 'commerce-sync-workspace',
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

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-123',
            'name' => 'Meta Shop Catalog',
            'status' => 'active',
        ]);

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Leadochat Source Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'external_product_id' => 'SKU-123',
            'sku' => 'SKU-123',
            'title' => 'Yellow Notebook',
            'price' => 15,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'synced',
            'metadata' => [
                'meta_catalog_sync' => [
                    'meta-catalog-123' => [
                        'retailer_id' => 'SKU-123',
                        'status' => 'queued',
                    ],
                ],
            ],
        ]);

        $productSet = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Featured set',
            'status' => 'active',
            'meta_sync_status' => 'not_synced',
        ]);
        $productSet->products()->attach($product->id, [
            'sort_order' => 0,
        ]);

        return [$user, $metaCatalog, $productSet];
    }
}
