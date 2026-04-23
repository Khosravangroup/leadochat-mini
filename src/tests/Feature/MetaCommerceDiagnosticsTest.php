<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCommerceDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_meta_commerce_diagnostics_for_connected_instagram_account(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,instagram_business_basic',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-account-456*' => Http::response([
                'id' => 'instagram-account-456',
                'username' => 'mini_shop',
                'name' => 'Mini Shop',
                'ig_id' => '17841439881436376',
                'shopping_product_tag_eligibility' => true,
                'shopping_review_status' => 'approved',
            ], 200),
            'https://graph.facebook.com/v25.0/me/permissions*' => Http::response([
                'data' => [
                    ['permission' => 'business_management', 'status' => 'granted'],
                    ['permission' => 'catalog_management', 'status' => 'granted'],
                    ['permission' => 'instagram_business_basic', 'status' => 'granted'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Meta Commerce Diagnostics Workspace',
            'slug' => 'meta-commerce-diagnostics-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-456',
            'provider_account_name' => 'mini_shop',
            'status' => 'connected',
            'meta' => [
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments', 'message_reactions'],
                    'verified_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-meta-access-token',
            'is_primary' => true,
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'business_management',
            'status' => 'granted',
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'catalog_management',
            'status' => 'granted',
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'instagram_business_basic',
            'status' => 'granted',
        ]);

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Local Source Catalog',
            'status' => 'active',
        ]);

        $product = $sourceCatalog->products()->create([
            'sku' => 'BOOK-001',
            'title' => 'Diagnostics Product',
            'price' => 30,
            'currency' => 'USD',
            'product_url' => 'https://example.com/products/diagnostics',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $marketOverride = $product->marketOverrides()->create([
            'target_country' => 'AE',
            'content_language' => 'fa_IR',
            'price' => 120,
            'currency' => 'AED',
            'checkout_url' => 'https://checkout.example.com/ae/diagnostics',
            'is_active' => true,
        ]);

        $product->offers()->create([
            'name' => 'Diagnostics Offer',
            'status' => 'active',
            'discount_type' => 'fixed_amount',
            'discount_value' => 5,
            'currency' => 'USD',
            'priority' => 1,
            'checkout_url' => 'https://checkout.example.com/offer/diagnostics',
        ]);

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-456',
            'name' => 'Meta Diagnostics Catalog',
            'status' => 'active',
        ]);

        $connection->catalogProductSets()->create([
            'workspace_id' => $workspace->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Diagnostics Set',
            'provider_connection_id' => $connection->id,
        ]);

        $connection->catalogCollections()->create([
            'workspace_id' => $workspace->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Diagnostics Collection',
            'provider_connection_id' => $connection->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.diagnostics', $connection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $connection->refresh();

        $diagnostics = $connection->meta['meta_commerce_diagnostics'];

        $this->assertTrue($diagnostics['ok']);
        $this->assertSame('mini_shop', $diagnostics['account']['username']);
        $this->assertSame('approved', $diagnostics['account']['shopping_review_status']);
        $this->assertSame([], $diagnostics['permissions']['missing']);
        $this->assertSame(['messages', 'comments', 'message_reactions'], $diagnostics['webhook']['verified_fields']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['source_catalog_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['meta_catalog_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['market_override_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['offer_count']);
        $this->assertSame(3, $diagnostics['checkout_urls']['checked_count']);
        $this->assertSame(0, $diagnostics['checkout_urls']['invalid_count']);
        $this->assertSame('permissions', $diagnostics['readiness'][0]['key']);
        $this->assertSame('ok', $diagnostics['readiness'][0]['status']);
    }
}
