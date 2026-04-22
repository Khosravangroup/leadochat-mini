<?php

namespace Tests\Unit;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Instagram\InstagramContentService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstagramContentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_product_tags_to_image_post_container_payload(): void
    {
        config([
            'services.instagram.graph_version' => 'v25.0',
        ]);

        Http::fake(function (HttpRequest $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && $url === 'https://graph.instagram.com/v25.0/test-instagram-account/media') {
                return Http::response(['id' => 'container-123'], 200);
            }

            if ($request->method() === 'POST' && $url === 'https://graph.instagram.com/v25.0/test-instagram-account/media_publish') {
                return Http::response(['id' => 'media-123'], 200);
            }

            if ($request->method() === 'GET' && str_starts_with($url, 'https://graph.instagram.com/v25.0/media-123')) {
                return Http::response([
                    'id' => 'media-123',
                    'caption' => 'Tagged catalog post',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://example.com/product-post.jpg',
                    'permalink' => 'https://instagram.com/p/media-123',
                    'timestamp' => now()->toIso8601String(),
                    'like_count' => 0,
                    'comments_count' => 0,
                ], 200);
            }

            return Http::response(['error' => ['message' => 'Unexpected request']], 500);
        });

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Product Tag Workspace',
            'slug' => 'product-tag-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'test-instagram-account',
            'provider_account_name' => 'leadochat_shop',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-access-token',
            'is_primary' => true,
        ]);

        $result = app(InstagramContentService::class)->publishPost($connection, [
            'media_type' => 'IMAGE',
            'media_url' => 'https://example.com/product-post.jpg',
            'caption' => 'Tagged catalog post',
            'product_tags' => [
                [
                    'product_id' => 'SKU-123',
                    'x' => 0.25,
                    'y' => 0.75,
                ],
                [
                    'product_id' => 'SKU-456',
                    'x' => 2,
                    'y' => -1,
                ],
            ],
        ]);

        $this->assertSame([
            [
                'product_id' => 'SKU-123',
                'x' => 0.25,
                'y' => 0.75,
            ],
            [
                'product_id' => 'SKU-456',
                'x' => 1.0,
                'y' => 0.0,
            ],
        ], $result['product_tags']);

        $this->assertJson($result['container_payload']['product_tags']);
        $this->assertEquals($result['product_tags'], json_decode($result['container_payload']['product_tags'], true));
    }
}
