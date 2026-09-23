<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CommerceOrder;
use App\Models\CommercePromotionCampaign;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Commerce\MetaCommerceDiagnosticsService;
use App\Services\Meta\Commerce\MetaCommerceReviewPacketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCommerceDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_instagram_login_diagnostics_use_instagram_graph_without_claiming_requested_scopes_are_granted(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => '',
            'services.instagram.graph_version' => 'v26.0',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages',
        ]);

        Http::fake([
            'https://graph.instagram.com/me*' => Http::response([
                'user_id' => 'instagram-login-account',
                'username' => 'instagram_login_account',
            ], 200),
            'https://graph.instagram.com/v26.0/instagram-login-account/subscribed_apps*' => Http::response([
                'data' => [[
                    'id' => 'instagram-app',
                    'name' => 'Leadochat Mini',
                    'subscribed_fields' => ['messages', 'comments'],
                ]],
            ], 200),
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Instagram Login tokens are not accepted here.'],
            ], 401),
        ]);

        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Instagram Login Diagnostics Workspace',
            'slug' => 'instagram-login-diagnostics-workspace',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-login-account',
            'provider_account_name' => 'instagram_login_account',
            'status' => 'connected',
            'meta' => [
                'mode' => 'instagram_login',
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments'],
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'instagram-login-token',
            'expires_at' => now()->addDay(),
            'scopes' => 'instagram_business_basic,instagram_business_manage_messages',
            'is_primary' => true,
        ]);

        foreach (['instagram_business_basic', 'instagram_business_manage_messages'] as $permission) {
            ProviderPermission::create([
                'provider_connection_id' => $connection->id,
                'permission' => $permission,
                'status' => 'requested',
            ]);
        }

        Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'instagram-login-catalog',
            'name' => 'Deferred Commerce Catalog',
            'status' => 'active',
        ]);

        $diagnostics = app(MetaCommerceDiagnosticsService::class)
            ->diagnoseForConnection($connection);

        $this->assertSame(200, $diagnostics['checks']['instagram_account']['status']);
        $this->assertSame('https://graph.instagram.com/me', $diagnostics['checks']['instagram_account']['url']);
        $this->assertSame(200, $diagnostics['checks']['subscribed_apps']['status']);
        $this->assertSame('instagram', $diagnostics['graph_api_family']);
        $this->assertSame('v26.0', $diagnostics['graph_version']);
        $this->assertSame(['instagram' => 'v26.0', 'facebook' => 'v25.0'], $diagnostics['graph_versions']);
        $this->assertSame('instagram-login-account', $diagnostics['account']['id']);
        $this->assertSame('instagram_login_account', $diagnostics['account']['username']);
        $this->assertSame(
            'separate_commerce_authorization_required',
            $diagnostics['checks']['catalog_detail:instagram-login-catalog']['source']
        );
        $this->assertFalse($diagnostics['checks']['catalog_detail:instagram-login-catalog']['ok']);
        $this->assertFalse($diagnostics['checks']['catalog_detail:instagram-login-catalog']['live']);
        $this->assertSame('not_applicable', $diagnostics['checks']['catalog_detail:instagram-login-catalog']['outcome']);
        $this->assertNull($diagnostics['checks']['catalog_detail:instagram-login-catalog']['status']);
        $this->assertSame(
            'catalog:instagram-login-catalog',
            $diagnostics['checks']['catalog_detail:instagram-login-catalog']['resource']
        );
        $this->assertSame('not_applicable', $diagnostics['shop']['meta_catalogs'][0]['live_status']);
        $this->assertSame(
            'Live catalog access requires a separately authorized Facebook commerce connection.',
            collect($diagnostics['readiness'])->firstWhere('key', 'catalog_access')['summary']
        );
        $this->assertSame(
            'Authorize Facebook commerce separately',
            collect($diagnostics['review']['next_actions'])->firstWhere('key', 'catalog_access')['title']
        );
        $this->assertSame([], $diagnostics['permissions']['granted']);
        $this->assertSame(
            ['requested'],
            collect($diagnostics['permissions']['local_permissions'])->pluck('status')->unique()->values()->all()
        );

        $packet = app(MetaCommerceReviewPacketService::class)
            ->generateForConnection($connection, $diagnostics);
        $this->assertSame(2, $packet['version']);
        $this->assertSame('instagram', $packet['summary']['graph_api_family']);
        $this->assertSame('v26.0', $packet['summary']['graph_version']);
        $this->assertSame('not_applicable', $packet['catalogs']['live_catalogs'][0]['live_status']);

        $connection->update([
            'meta' => array_merge($connection->meta, ['meta_commerce_diagnostics' => $diagnostics]),
        ]);

        $this->actingAs($owner)
            ->get(route('settings.index', ['section' => 'commerce']))
            ->assertOk()
            ->assertSee('separate Facebook commerce authorization required')
            ->assertDontSee('live check failed');

        Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'https://graph.facebook.com/'));
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://graph.instagram.com/')
            && $request->hasHeader('Authorization', 'Bearer instagram-login-token')
            && ! str_contains($request->url(), 'access_token='));
    }

    public function test_instagram_login_scope_fallback_does_not_treat_stale_local_grants_as_provider_grants(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management',
            'services.instagram.graph_version' => 'v25.0',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages',
        ]);

        Http::fake([
            'https://graph.instagram.com/me*' => Http::response([
                'user_id' => 'scope-fallback-account',
                'username' => 'scope_fallback_account',
            ], 200),
            'https://graph.instagram.com/v25.0/scope-fallback-account/subscribed_apps*' => Http::response([
                'data' => [],
            ], 200),
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'This endpoint must not be called.'],
            ], 500),
        ]);

        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Instagram Scope Fallback Workspace',
            'slug' => 'instagram-scope-fallback-workspace',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'scope-fallback-account',
            'provider_account_name' => 'scope_fallback_account',
            'status' => 'connected',
            'meta' => [
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments'],
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'scope-fallback-token',
            'expires_at' => now()->addDay(),
            'scopes' => ' instagram_business_basic , instagram_business_manage_messages ',
            'is_primary' => true,
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'business_management',
            'status' => 'granted',
            'granted_at' => now()->subDay(),
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'instagram_business_basic',
            'status' => 'requested',
        ]);

        $diagnostics = app(MetaCommerceDiagnosticsService::class)
            ->diagnoseForConnection($connection);

        $this->assertSame('https://graph.instagram.com/me', $diagnostics['checks']['instagram_account']['url']);
        $this->assertSame('local_recorded_permissions', $diagnostics['checks']['granted_permissions']['source']);
        $this->assertFalse($diagnostics['checks']['granted_permissions']['live']);
        $this->assertSame([], $diagnostics['permissions']['granted']);
        $this->assertSame(['business_management'], $diagnostics['permissions']['missing']);
        $this->assertSame([], $diagnostics['channel']['live_subscribed_fields']);
        $this->assertSame([], $diagnostics['webhook']['verified_fields']);
        $this->assertSame(['messages', 'comments'], $diagnostics['webhook']['saved_verified_fields']);
        $this->assertSame(
            'warn',
            collect($diagnostics['readiness'])->firstWhere('key', 'webhook_subscription')['status']
        );
        $this->assertSame(
            ['granted', 'requested'],
            collect($diagnostics['permissions']['local_permissions'])->pluck('status')->unique()->values()->all()
        );

        Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'https://graph.facebook.com/'));
    }

    public function test_instagram_login_diagnostics_fail_closed_when_live_identity_and_subscription_checks_fail(): void
    {
        config([
            'services.meta.commerce_review_scopes' => '',
            'services.instagram.graph_version' => 'v25.0',
            'services.instagram.scopes' => 'instagram_business_basic',
        ]);

        Http::fake([
            'https://graph.instagram.com/me*' => Http::response([
                'error' => ['message' => 'Rejected failure-secret-token'],
            ], 503),
            'https://graph.instagram.com/v25.0/failing-instagram-account/subscribed_apps*' => Http::failedConnection(
                'Connection failed for failure-secret-token'
            ),
        ]);

        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Failing Instagram Diagnostics Workspace',
            'slug' => 'failing-instagram-diagnostics-workspace',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'failing-instagram-account',
            'provider_account_name' => 'saved_account_name',
            'status' => 'connected',
            'meta' => [
                'mode' => 'instagram_login',
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments'],
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'failure-secret-token',
            'expires_at' => now()->addDay(),
            'scopes' => 'instagram_business_basic',
            'is_primary' => true,
        ]);

        $diagnostics = app(MetaCommerceDiagnosticsService::class)
            ->diagnoseForConnection($connection);

        $this->assertSame(503, $diagnostics['checks']['instagram_account']['status']);
        $this->assertSame('failed', $diagnostics['checks']['instagram_account']['outcome']);
        $this->assertNull($diagnostics['checks']['subscribed_apps']['status']);
        $this->assertSame('failed', $diagnostics['checks']['subscribed_apps']['outcome']);
        $this->assertStringNotContainsString('failure-secret-token', $diagnostics['checks']['instagram_account']['error']);
        $this->assertStringNotContainsString('failure-secret-token', $diagnostics['checks']['subscribed_apps']['error']);
        $this->assertSame([], $diagnostics['channel']['live_subscribed_fields']);
        $this->assertSame([], $diagnostics['webhook']['verified_fields']);
        $this->assertSame(['messages', 'comments'], $diagnostics['webhook']['saved_verified_fields']);
        $this->assertSame('fail', collect($diagnostics['readiness'])->firstWhere('key', 'channel_health')['status']);
        $this->assertSame('fail', collect($diagnostics['review']['evidence'])->firstWhere('key', 'instagram_account')['status']);
    }

    public function test_granted_ads_scopes_do_not_claim_a_demonstrable_review_journey(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'ads_read,ads_management',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/me/permissions*' => Http::response([
                'data' => [
                    ['permission' => 'ads_read', 'status' => 'granted'],
                    ['permission' => 'ads_management', 'status' => 'granted'],
                ],
            ], 200),
            'https://graph.facebook.com/*' => Http::response([], 200),
        ]);

        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Review Scope Workspace',
            'slug' => 'review-scope-workspace',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'review-scope-account',
            'provider_account_name' => 'review_scope_account',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'review-scope-test-token',
            'expires_at' => now()->addDay(),
            'is_primary' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('settings.commerce.diagnostics', $connection))
            ->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $diagnostics = $connection->fresh()->meta['meta_commerce_diagnostics'];

        $this->assertSame([], $diagnostics['permissions']['missing']);
        $this->assertSame(
            ['ads_read', 'ads_management'],
            $diagnostics['permissions']['without_demonstrated_api_journey']
        );
        $this->assertSame([], $diagnostics['permissions']['instagram_without_demonstrated_api_journey']);
        $this->assertSame('fail', collect($diagnostics['readiness'])->firstWhere('key', 'scope_feature_coverage')['status']);
        $this->assertSame('ok', collect($diagnostics['readiness'])->firstWhere('key', 'instagram_scope_feature_coverage')['status']);
        $this->assertSame('blocked', $diagnostics['review']['status']);

        $this->actingAs($owner)
            ->post(route('settings.commerce.review-packet.generate', $connection))
            ->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $packet = $connection->fresh()->meta['meta_commerce_review_packet'];
        $this->assertSame('blocked', $packet['summary']['status']);
        $this->assertSame(['ads_read', 'ads_management'], $packet['permissions']['without_demonstrated_api_journey']);
        $this->assertSame([], $packet['permissions']['instagram_without_demonstrated_api_journey']);
    }

    public function test_it_builds_meta_commerce_diagnostics_for_connected_instagram_account(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,instagram_business_basic',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-account-456?*' => Http::response([
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
            'https://graph.facebook.com/v25.0/instagram-account-456/subscribed_apps*' => Http::response([
                'data' => [
                    [
                        'id' => 'app-123',
                        'name' => 'Leadochat Mini',
                        'subscribed_fields' => ['messages', 'comments', 'message_reactions'],
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/meta-catalog-456*' => Http::response([
                'id' => 'meta-catalog-456',
                'name' => 'Meta Diagnostics Catalog',
                'vertical' => 'commerce',
                'product_count' => 14,
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
            'expires_at' => now()->addDay(),
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
        $collection = $connection->catalogCollections()->first();

        $order = CommerceOrder::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $sourceCatalog->id,
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'source' => 'manual_test',
            'currency' => 'USD',
            'subtotal_amount' => 30,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 30,
            'is_test' => true,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'catalog_product_id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'quantity' => 1,
            'currency' => 'USD',
            'unit_price' => 30,
            'total_price' => 30,
            'item_snapshot' => ['title' => $product->title],
        ]);

        $order->snapshots()->create([
            'snapshot_type' => 'test_order_created',
            'status' => 'placed',
            'captured_at' => now(),
            'payload' => ['note' => 'Created for diagnostics test.'],
        ]);

        CommercePromotionCampaign::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_collection_id' => $collection?->id,
            'social_post_id' => null,
            'campaign_type' => 'collection_ad',
            'objective' => 'sales',
            'name' => 'Diagnostics Collection Campaign',
            'status' => 'draft',
            'destination_url' => 'https://example.com/shop/diagnostics',
            'budget_amount' => 100,
            'currency' => 'USD',
            'meta_sync_status' => 'prepared',
            'meta' => [
                'last_prepared_preview' => [
                    'ok' => true,
                ],
            ],
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
        $this->assertFalse($diagnostics['channel']['token_expired']);
        $this->assertSame('instagram_account', $diagnostics['channel']['provider_account_type']);
        $this->assertSame(['messages', 'comments', 'message_reactions'], $diagnostics['channel']['live_subscribed_fields']);
        $this->assertSame(['messages', 'comments', 'message_reactions'], $diagnostics['webhook']['verified_fields']);
        $this->assertSame('Meta Diagnostics Catalog', $diagnostics['shop']['meta_catalogs'][0]['name']);
        $this->assertSame('commerce', $diagnostics['shop']['meta_catalogs'][0]['vertical']);
        $this->assertSame(14, $diagnostics['shop']['meta_catalogs'][0]['product_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['source_catalog_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['meta_catalog_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['market_override_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['offer_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['order_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['test_order_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['order_snapshot_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['promotion_campaign_count']);
        $this->assertSame(1, $diagnostics['shop']['local_stats']['prepared_promotion_campaign_count']);
        $this->assertSame(3, $diagnostics['checkout_urls']['checked_count']);
        $this->assertSame(0, $diagnostics['checkout_urls']['invalid_count']);
        $this->assertSame('ready', $diagnostics['review']['status']);
        $this->assertSame(12, $diagnostics['review']['counts']['ok']);
        $this->assertSame(0, $diagnostics['review']['counts']['warn']);
        $this->assertSame(0, $diagnostics['review']['counts']['fail']);
        $this->assertCount(13, $diagnostics['review']['evidence']);
        $this->assertSame('Instagram business account', $diagnostics['review']['evidence'][0]['label']);
        $this->assertSame('channel_health', $diagnostics['readiness'][0]['key']);
        $this->assertSame('ok', $diagnostics['readiness'][0]['status']);
    }

    public function test_it_marks_review_summary_as_blocked_when_core_commerce_proof_is_missing(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,instagram_business_basic',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-account-999?*' => Http::response([
                'id' => 'instagram-account-999',
                'username' => 'blocked_shop',
                'name' => 'Blocked Shop',
                'ig_id' => '17841439881430000',
                'shopping_product_tag_eligibility' => false,
                'shopping_review_status' => 'pending',
            ], 200),
            'https://graph.facebook.com/v25.0/me/permissions*' => Http::response([
                'data' => [
                    ['permission' => 'business_management', 'status' => 'granted'],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/instagram-account-999/subscribed_apps*' => Http::response([
                'data' => [
                    [
                        'id' => 'app-123',
                        'name' => 'Leadochat Mini',
                        'subscribed_fields' => ['comments'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Blocked Commerce Workspace',
            'slug' => 'blocked-commerce-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-999',
            'provider_account_name' => 'blocked_shop',
            'status' => 'connected',
            'meta' => [
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['comments'],
                    'verified_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'blocked-meta-access-token',
            'expires_at' => now()->addDay(),
            'is_primary' => true,
        ]);

        ProviderPermission::create([
            'provider_connection_id' => $connection->id,
            'permission' => 'business_management',
            'status' => 'granted',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.diagnostics', $connection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $connection->refresh();

        $diagnostics = $connection->meta['meta_commerce_diagnostics'];

        $this->assertFalse($diagnostics['ok']);
        $this->assertSame('blocked', $diagnostics['review']['status']);
        $this->assertSame(2, $diagnostics['review']['counts']['fail']);
        $this->assertNotEmpty($diagnostics['review']['blockers']);
        $this->assertContains('catalog_management', $diagnostics['permissions']['missing']);
        $this->assertContains('instagram_business_basic', $diagnostics['permissions']['missing']);
        $this->assertSame(['comments'], $diagnostics['webhook']['verified_fields']);
        $this->assertContains('permissions', collect($diagnostics['review']['next_actions'])->pluck('key')->all());
        $this->assertContains('catalog_discovery', collect($diagnostics['review']['next_actions'])->pluck('key')->all());
    }

    public function test_it_generates_and_downloads_a_meta_commerce_review_packet(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,instagram_business_basic',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-account-777?*' => Http::response([
                'id' => 'instagram-account-777',
                'username' => 'packet_shop',
                'name' => 'Packet Shop',
                'ig_id' => '17841439881437777',
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
            'https://graph.facebook.com/v25.0/instagram-account-777/subscribed_apps*' => Http::response([
                'data' => [
                    [
                        'id' => 'app-777',
                        'name' => 'Leadochat Mini',
                        'subscribed_fields' => ['messages', 'comments'],
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/meta-catalog-777*' => Http::response([
                'id' => 'meta-catalog-777',
                'name' => 'Packet Catalog',
                'vertical' => 'commerce',
                'product_count' => 9,
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Packet Commerce Workspace',
            'slug' => 'packet-commerce-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-777',
            'provider_account_name' => 'packet_shop',
            'status' => 'connected',
            'meta' => [
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments'],
                    'verified_at' => now()->toIso8601String(),
                ],
                'meta_commerce' => [
                    'business_ids' => ['business-777'],
                    'review_scopes' => ['business_management', 'catalog_management', 'instagram_business_basic'],
                ],
                'meta_commerce_discovery' => [
                    'catalogs' => [
                        ['id' => 'meta-catalog-777', 'name' => 'Packet Catalog'],
                    ],
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'packet-meta-access-token',
            'expires_at' => now()->addDay(),
            'is_primary' => true,
        ]);

        foreach (['business_management', 'catalog_management', 'instagram_business_basic'] as $permission) {
            ProviderPermission::create([
                'provider_connection_id' => $connection->id,
                'permission' => $permission,
                'status' => 'granted',
            ]);
        }

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Packet Source Catalog',
            'status' => 'active',
        ]);

        $product = $sourceCatalog->products()->create([
            'sku' => 'PACKET-001',
            'title' => 'Packet Product',
            'price' => 45,
            'currency' => 'USD',
            'product_url' => 'https://example.com/products/packet',
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $product->marketOverrides()->create([
            'target_country' => 'US',
            'content_language' => 'en_US',
            'price' => 45,
            'currency' => 'USD',
            'checkout_url' => 'https://checkout.example.com/us/packet',
            'is_active' => true,
        ]);

        $product->offers()->create([
            'name' => 'Packet Offer',
            'status' => 'active',
            'discount_type' => 'fixed_amount',
            'discount_value' => 5,
            'currency' => 'USD',
            'priority' => 1,
            'checkout_url' => 'https://checkout.example.com/offer/packet',
        ]);

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-777',
            'name' => 'Packet Catalog',
            'status' => 'active',
        ]);

        $connection->catalogProductSets()->create([
            'workspace_id' => $workspace->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Packet Set',
            'provider_connection_id' => $connection->id,
        ]);

        $connection->catalogCollections()->create([
            'workspace_id' => $workspace->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Packet Collection',
            'provider_connection_id' => $connection->id,
        ]);
        $packetCollection = $connection->catalogCollections()->first();

        $order = CommerceOrder::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $sourceCatalog->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
            'source' => 'manual_test',
            'currency' => 'USD',
            'subtotal_amount' => 45,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 45,
            'is_test' => true,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'catalog_product_id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'quantity' => 1,
            'currency' => 'USD',
            'unit_price' => 45,
            'total_price' => 45,
            'item_snapshot' => ['title' => $product->title],
        ]);

        $order->snapshots()->create([
            'snapshot_type' => 'test_order_created',
            'status' => 'processing',
            'captured_at' => now(),
            'payload' => ['note' => 'Created for packet test.'],
        ]);

        CommercePromotionCampaign::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_collection_id' => $packetCollection?->id,
            'campaign_type' => 'collection_ad',
            'objective' => 'sales',
            'name' => 'Packet Promotion Campaign',
            'status' => 'active',
            'destination_url' => 'https://example.com/shop/packet',
            'budget_amount' => 120,
            'currency' => 'USD',
            'meta_sync_status' => 'prepared',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.review-packet.generate', $connection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $connection->refresh();

        $packet = $connection->meta['meta_commerce_review_packet'];

        $this->assertSame('ready', $packet['summary']['status']);
        $this->assertSame('packet_shop', $packet['account']['username']);
        $this->assertSame(1, $packet['catalogs']['discovered_count']);
        $this->assertSame(1, $packet['orders']['order_count']);
        $this->assertSame(1, $packet['orders']['snapshot_count']);
        $this->assertSame(1, $packet['promotions']['campaign_count']);
        $this->assertSame(1, $packet['promotions']['prepared_campaign_count']);
        $this->assertCount(9, $packet['demo_script']);
        $this->assertSame(1, count($connection->meta['meta_commerce_review_packet_history']));

        $download = $this
            ->actingAs($user)
            ->get(route('settings.commerce.review-packet.download', $connection));

        $download->assertOk();
        $download->assertHeader('content-type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $download->headers->get('content-disposition'));

        $downloadedPacket = json_decode($download->streamedContent(), true);

        $this->assertSame('ready', data_get($downloadedPacket, 'summary.status'));
        $this->assertSame('Packet Catalog', data_get($downloadedPacket, 'catalogs.live_catalogs.0.name'));
        $this->assertSame(1, data_get($downloadedPacket, 'orders.order_count'));
        $this->assertSame(1, data_get($downloadedPacket, 'promotions.campaign_count'));
    }
}
