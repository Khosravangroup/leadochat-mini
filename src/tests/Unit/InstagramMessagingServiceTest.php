<?php

namespace Tests\Unit;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Instagram\InstagramMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramMessagingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_generic_template_product_card_payload(): void
    {
        config([
            'services.instagram.graph_version' => 'v25.0',
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Template Test Workspace',
            'slug' => 'template-test-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'test-instagram-account',
            'provider_account_name' => 'leadochat',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-access-token',
            'is_primary' => true,
        ]);

        $result = app(InstagramMessagingService::class)->sendGenericTemplate(
            $connection,
            'customer-123',
            [
                [
                    'title' => 'Green Linen Shirt',
                    'subtitle' => 'USD 49.50 - Ships today.',
                    'image_url' => 'https://example.com/products/shirt.jpg',
                    'default_action' => [
                        'type' => 'web_url',
                        'url' => 'https://example.com/products/green-linen-shirt',
                    ],
                    'buttons' => [
                        [
                            'type' => 'web_url',
                            'url' => 'https://example.com/products/green-linen-shirt',
                            'title' => 'View product',
                        ],
                    ],
                ],
            ],
            [
                'messaging_type' => 'RESPONSE',
            ]
        );

        $this->assertSame('local_debug', $result['mode']);
        $this->assertSame('generic_template', $result['kind']);
        $this->assertSame('https://graph.instagram.com/v25.0/test-instagram-account/messages', $result['endpoint']);

        $payload = $result['payload'];

        $this->assertSame('customer-123', $payload['recipient']['id']);
        $this->assertSame('RESPONSE', $payload['messaging_type']);
        $this->assertSame('template', $payload['message']['attachment']['type']);
        $this->assertSame('generic', $payload['message']['attachment']['payload']['template_type']);
        $this->assertSame('Green Linen Shirt', $payload['message']['attachment']['payload']['elements'][0]['title']);
        $this->assertSame('https://example.com/products/shirt.jpg', $payload['message']['attachment']['payload']['elements'][0]['image_url']);
        $this->assertSame('View product', $payload['message']['attachment']['payload']['elements'][0]['buttons'][0]['title']);
    }
}
