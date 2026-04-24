<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProductSet;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaCollectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_create_collection_for_meta_catalog(): void
    {
        [$user, $workspace, $connection, $metaCatalog] = $this->makeCommerceWorkspace();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.collections.store'), [
                'meta_catalog_id' => $metaCatalog->id,
                'name' => 'Top picks',
                'description' => 'Collection for storefront highlights.',
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $collection = CatalogCollection::query()->firstOrFail();

        $this->assertSame($workspace->id, $collection->workspace_id);
        $this->assertSame($connection->id, $collection->provider_connection_id);
        $this->assertSame($metaCatalog->id, $collection->catalog_id);
        $this->assertSame('Top picks', $collection->name);
        $this->assertSame('not_synced', $collection->meta_sync_status);
    }

    public function test_agent_can_assign_product_sets_to_collection(): void
    {
        [$user, $workspace, $connection, $metaCatalog] = $this->makeCommerceWorkspace();

        $productSetOne = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Featured set',
            'status' => 'active',
        ]);

        $productSetTwo = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Seasonal set',
            'status' => 'active',
        ]);

        $otherCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-456',
            'name' => 'Other Meta Catalog',
            'status' => 'active',
        ]);

        $blockedSet = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $otherCatalog->id,
            'name' => 'Blocked set',
            'status' => 'active',
        ]);

        $collection = CatalogCollection::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Collection one',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->patch(route('settings.commerce.collections.product-sets.sync', $collection), [
                'product_set_ids' => [$productSetOne->id, $productSetTwo->id, $blockedSet->id],
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $collection->refresh();

        $this->assertSame([$productSetOne->id, $productSetTwo->id], $collection->productSets()->pluck('catalog_product_sets.id')->all());
        $this->assertSame(2, $collection->productSets()->count());
        $this->assertSame(2, $collection->meta['last_product_set_assignment']['product_set_count'] ?? null);
    }

    protected function makeCommerceWorkspace(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Commerce Collection Workspace',
            'slug' => 'commerce-collection-workspace',
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

        return [$user, $workspace, $connection, $metaCatalog];
    }
}
