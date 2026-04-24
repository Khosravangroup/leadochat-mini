<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaProductSetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_create_product_set_for_meta_catalog(): void
    {
        [$user, $workspace, $connection, $metaCatalog] = $this->makeCommerceWorkspace();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.product-sets.store'), [
                'meta_catalog_id' => $metaCatalog->id,
                'name' => 'Featured arrivals',
                'description' => 'Products for the next merch drop.',
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $productSet = CatalogProductSet::query()->firstOrFail();

        $this->assertSame($workspace->id, $productSet->workspace_id);
        $this->assertSame($connection->id, $productSet->provider_connection_id);
        $this->assertSame($metaCatalog->id, $productSet->catalog_id);
        $this->assertSame('Featured arrivals', $productSet->name);
        $this->assertSame('not_synced', $productSet->meta_sync_status);
    }

    public function test_agent_can_assign_synced_products_to_product_set(): void
    {
        [$user, $workspace, $connection, $metaCatalog, $sourceCatalog] = $this->makeCommerceWorkspace(true);

        $allowedProduct = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'external_product_id' => 'META-100',
            'sku' => 'SKU-100',
            'title' => 'Allowed Product',
            'price' => 15,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'queued',
        ]);

        $blockedCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'source' => 'leadochat',
            'name' => 'Other Account Catalog',
            'provider_connection_id' => null,
            'status' => 'active',
        ]);

        $secondAllowedProduct = CatalogProduct::create([
            'catalog_id' => $blockedCatalog->id,
            'external_product_id' => 'META-200',
            'sku' => 'SKU-200',
            'title' => 'Shared Product',
            'price' => 19,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'synced',
        ]);

        $inactiveProduct = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'external_product_id' => 'META-300',
            'sku' => 'SKU-300',
            'title' => 'Inactive Product',
            'price' => 25,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => false,
            'meta_sync_status' => 'queued',
        ]);

        $productSet = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Assigned set',
            'status' => 'active',
            'meta_sync_status' => 'not_synced',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->patch(route('settings.commerce.product-sets.products.sync', $productSet), [
                'product_ids' => [$allowedProduct->id, $secondAllowedProduct->id, $inactiveProduct->id],
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $productSet->refresh();

        $this->assertSame([$allowedProduct->id, $secondAllowedProduct->id], $productSet->products()->pluck('catalog_products.id')->all());
        $this->assertSame(2, $productSet->products()->count());
        $this->assertSame(2, $productSet->meta['last_product_assignment']['product_count'] ?? null);
    }

    protected function makeCommerceWorkspace(bool $withSourceCatalog = false): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Commerce Product Set Workspace',
            'slug' => 'commerce-product-set-workspace',
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

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-123',
            'name' => 'Meta Shop Catalog',
            'status' => 'active',
        ]);

        if (! $withSourceCatalog) {
            return [$user, $workspace, $connection, $metaCatalog];
        }

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Leadochat Shop Catalog',
            'status' => 'active',
        ]);

        return [$user, $workspace, $connection, $metaCatalog, $sourceCatalog];
    }
}
