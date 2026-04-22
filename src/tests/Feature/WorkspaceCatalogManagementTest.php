<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WorkspaceCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_update_and_toggle_catalog_product(): void
    {
        [$user, $catalog] = $this->makeWorkspaceCatalog();

        $product = CatalogProduct::create([
            'catalog_id' => $catalog->id,
            'sku' => 'OLD-001',
            'title' => 'Old Product',
            'description' => 'Old description',
            'price' => 10,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->patch(route('settings.catalogs.products.update', $product), [
            'title' => 'Updated Product',
            'sku' => 'NEW-001',
            'description' => 'Updated description',
            'price' => 19.95,
            'currency' => 'eur',
            'image_url' => 'https://example.com/products/updated.jpg',
            'product_url' => 'https://example.com/products/updated-product',
            'availability' => 'preorder',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('settings.index', ['section' => 'catalogs']));

        $product->refresh();

        $this->assertSame('Updated Product', $product->title);
        $this->assertSame('NEW-001', $product->sku);
        $this->assertSame('EUR', $product->currency);
        $this->assertSame('preorder', $product->availability);
        $this->assertFalse($product->is_active);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->patch(route('settings.catalogs.products.status', $product));

        $response->assertRedirect(route('settings.index', ['section' => 'catalogs']));

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_agent_can_import_catalog_products_from_csv_and_update_matching_skus(): void
    {
        [$user, $catalog] = $this->makeWorkspaceCatalog();

        CatalogProduct::create([
            'catalog_id' => $catalog->id,
            'sku' => 'SKU-001',
            'title' => 'Existing Product',
            'price' => 5,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $csv = implode("\n", [
            'title,sku,description,price,currency,image_url,product_url,availability,is_active',
            'Updated CSV Product,SKU-001,Updated by import,25,USD,https://example.com/one.jpg,https://example.com/one,in_stock,true',
            'New CSV Product,SKU-002,Created by import,30,EUR,https://example.com/two.jpg,https://example.com/two,out of stock,false',
            ',SKU-003,Missing title,1,USD,,,in_stock,true',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.catalogs.products.import', $catalog), [
            'products_csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ]);

        $response->assertRedirect(route('settings.index', ['section' => 'catalogs']));

        $this->assertSame(2, $catalog->products()->count());

        $updated = $catalog->products()->where('sku', 'SKU-001')->firstOrFail();
        $created = $catalog->products()->where('sku', 'SKU-002')->firstOrFail();

        $this->assertSame('Updated CSV Product', $updated->title);
        $this->assertSame('25.00', $updated->price);
        $this->assertSame('New CSV Product', $created->title);
        $this->assertSame('out_of_stock', $created->availability);
        $this->assertFalse($created->is_active);
    }

    protected function makeWorkspaceCatalog(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Catalog Management Workspace',
            'slug' => 'catalog-management-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $catalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'source' => 'leadochat',
            'name' => 'Store Catalog',
            'status' => 'active',
        ]);

        return [$user, $catalog];
    }
}
