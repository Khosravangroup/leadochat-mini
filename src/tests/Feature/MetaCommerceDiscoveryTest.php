<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCommerceDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_discovers_meta_commerce_catalogs_for_connected_instagram_account(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,ads_read,ads_management',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/me/businesses*' => Http::response([
                'data' => [
                    [
                        'id' => 'business-123',
                        'name' => 'Leadochat Test Business',
                        'verification_status' => 'verified',
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/me*' => Http::response([
                'id' => 'meta-user-123',
                'name' => 'Meta User',
            ], 200),
            'https://graph.facebook.com/v25.0/instagram-account-123*' => Http::response([
                'id' => 'instagram-account-123',
                'username' => 'leadochat_shop',
                'shopping_product_tag_eligibility' => true,
                'shopping_review_status' => 'approved',
            ], 200),
            'https://graph.facebook.com/v25.0/business-123/owned_product_catalogs*' => Http::response([
                'data' => [
                    [
                        'id' => 'catalog-123',
                        'name' => 'Main Meta Catalog',
                        'vertical' => 'commerce',
                        'product_count' => 12,
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/business-123/client_product_catalogs*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Meta Commerce Workspace',
            'slug' => 'meta-commerce-workspace',
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

        $response = $this
            ->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->post(route('settings.commerce.sync', $connection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $catalog = Catalog::query()->where('external_catalog_id', 'catalog-123')->firstOrFail();

        $this->assertSame($workspace->id, $catalog->workspace_id);
        $this->assertSame($connection->id, $catalog->provider_connection_id);
        $this->assertSame('meta', $catalog->source);
        $this->assertSame('business-123', $catalog->external_business_id);
        $this->assertSame('discovered', $catalog->meta_sync_status);

        $connection->refresh();

        $this->assertSame(1, $connection->meta['meta_commerce']['catalog_count']);
        $this->assertSame(['business-123'], $connection->meta['meta_commerce']['business_ids']);
        $this->assertSame('catalog-123', $connection->meta['meta_commerce_discovery']['catalogs'][0]['id']);
    }
}
