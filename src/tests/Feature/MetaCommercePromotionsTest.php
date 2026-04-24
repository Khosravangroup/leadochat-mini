<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\CommercePromotionCampaign;
use App\Models\ProviderConnection;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaCommercePromotionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_create_prepare_and_update_a_promotion_campaign(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Commerce Promotions Workspace',
            'slug' => 'commerce-promotions-workspace',
        ]);

        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-promotions',
            'provider_account_name' => 'promo_shop',
            'status' => 'connected',
        ]);

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-promo-catalog',
            'name' => 'Promotion Catalog',
            'status' => 'active',
        ]);

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Promotion Source Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'sku' => 'PROMO-001',
            'title' => 'Promotion Product',
            'price' => 45,
            'currency' => 'USD',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'synced',
        ]);

        $productSet = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Promotion Set',
            'status' => 'active',
            'meta_sync_status' => 'synced',
        ]);

        $productSet->products()->sync([$product->id => ['sort_order' => 0]]);

        $collection = CatalogCollection::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Promotion Collection',
            'status' => 'active',
            'meta_sync_status' => 'synced',
        ]);

        $collection->productSets()->sync([$productSet->id => ['sort_order' => 0]]);

        $socialPost = SocialPost::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_media_id' => 'ig-post-1',
            'media_type' => 'IMAGE',
            'caption' => 'Product tagged post',
            'permalink' => 'https://instagram.com/p/test',
            'status' => 'active',
            'raw' => [
                'product_tags' => [
                    ['product_id' => 'PROMO-001', 'x' => 0.5, 'y' => 0.5],
                ],
            ],
        ]);

        $createResponse = $this
            ->actingAs($user)
            ->post(route('settings.commerce.promotions.store'), [
                'provider_connection_id' => $connection->id,
                'campaign_type' => 'promoted_post',
                'objective' => 'sales',
                'name' => 'Promote product tagged post',
                'description' => 'Review-safe promoted post campaign.',
                'status' => 'draft',
                'call_to_action' => 'Shop now',
                'destination_url' => 'https://example.com/shop/promo',
                'catalog_collection_id' => $collection->id,
                'social_post_id' => $socialPost->id,
                'budget_amount' => 150,
                'currency' => 'USD',
            ]);

        $createResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $campaign = CommercePromotionCampaign::query()->firstOrFail();

        $this->assertSame('promoted_post', $campaign->campaign_type);
        $this->assertSame('not_prepared', $campaign->meta_sync_status);
        $this->assertSame($socialPost->id, $campaign->social_post_id);

        $prepareResponse = $this
            ->actingAs($user)
            ->post(route('settings.commerce.promotions.prepare', $campaign));

        $prepareResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $campaign->refresh();

        $this->assertSame('prepared', $campaign->meta_sync_status);
        $this->assertNotNull($campaign->meta_synced_at);
        $this->assertTrue((bool) data_get($campaign->meta, 'last_prepared_preview.ok'));
        $this->assertSame(1, data_get($campaign->meta, 'last_prepared_preview.asset_summary.social_post_product_tag_count'));

        $statusResponse = $this
            ->actingAs($user)
            ->patch(route('settings.commerce.promotions.status.update', $campaign), [
                'status' => 'active',
            ]);

        $statusResponse->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $campaign->refresh();

        $this->assertSame('active', $campaign->status);
    }
}
