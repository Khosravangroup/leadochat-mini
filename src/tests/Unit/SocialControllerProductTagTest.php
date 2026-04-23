<?php

namespace Tests\Unit;

use App\Http\Controllers\SocialController;
use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class SocialControllerProductTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_selected_catalog_products_with_visual_coordinates(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Visual Product Tag Workspace',
            'slug' => 'visual-product-tag-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-123',
            'provider_account_name' => 'leadochat_shop',
            'status' => 'connected',
        ]);
        $catalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Visual Tag Catalog',
            'status' => 'active',
        ]);
        $product = CatalogProduct::create([
            'catalog_id' => $catalog->id,
            'external_product_id' => 'META-SKU-123',
            'sku' => 'SKU-123',
            'title' => 'Tagged Product',
            'is_active' => true,
            'meta_sync_status' => 'queued',
        ]);

        $reflection = new ReflectionClass(SocialController::class);
        $method = $reflection->getMethod('resolvePostProductTags');
        $method->setAccessible(true);

        $tags = $method->invoke(new SocialController(), $workspace->id, $connection->id, [
            [
                'product_id' => $product->id,
                'x' => 0.32,
                'y' => 0.71,
            ],
        ], 'IMAGE');

        $this->assertSame([
            [
                'product_id' => 'META-SKU-123',
                'x' => 0.32,
                'y' => 0.71,
            ],
        ], $tags);
    }
}
