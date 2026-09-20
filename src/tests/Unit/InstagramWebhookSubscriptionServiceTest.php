<?php

namespace Tests\Unit;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Instagram\InstagramWebhookSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class InstagramWebhookSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_subscribes_connected_account_to_configured_webhook_fields(): void
    {
        config([
            'services.instagram.graph_version' => 'v25.0',
            'services.instagram.webhook_subscribed_fields' => ['messages', 'comments'],
        ]);

        Http::fake([
            'https://graph.instagram.com/v25.0/test-instagram-account/subscribed_apps*' => Http::sequence()
                ->push(['success' => true], 200)
                ->push([
                    'data' => [
                        [
                            'id' => '18100290062319518',
                            'subscribed_fields' => ['messages', 'comments'],
                        ],
                    ],
                ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'test-instagram-account',
            'provider_account_name' => 'sechenov.ir',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-access-token',
            'is_primary' => true,
        ]);

        $result = app(InstagramWebhookSubscriptionService::class)
            ->ensureSubscribed($connection);

        $this->assertTrue($result['success']);
        $this->assertSame(['messages', 'comments'], $result['verified_fields']);
        $this->assertSame([], $result['missing_fields']);

        $connection->refresh();

        $this->assertTrue($connection->meta['webhook_subscription']['success']);
        $this->assertSame(
            ['messages', 'comments'],
            $connection->meta['webhook_subscription']['verified_fields']
        );

        Http::assertSent(function (Request $request) {
            parse_str($request->body(), $body);

            return $request->method() === 'POST'
                && $request->url() === 'https://graph.instagram.com/v25.0/test-instagram-account/subscribed_apps'
                && ($body['subscribed_fields'] ?? null) === 'messages,comments'
                && ($body['access_token'] ?? null) === 'test-access-token';
        });
    }

    public function test_failed_subscription_redacts_access_token_from_error_evidence(): void
    {
        config([
            'services.instagram.graph_version' => 'v25.0',
            'services.instagram.webhook_subscribed_fields' => ['messages'],
        ]);

        Http::fake([
            'https://graph.instagram.com/v25.0/test-instagram-account/subscribed_apps*' => Http::response([
                'error' => [
                    'message' => 'Provider echoed sensitive-access-token in the failure.',
                ],
            ], 400),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Test Workspace',
            'slug' => 'failed-subscription-workspace',
        ]);
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'test-instagram-account',
            'provider_account_name' => 'Test account',
            'status' => 'connected',
        ]);
        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'sensitive-access-token',
            'is_primary' => true,
        ]);

        try {
            app(InstagramWebhookSubscriptionService::class)->ensureSubscribed($connection);
            $this->fail('Expected webhook subscription failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('sensitive-access-token', $exception->getMessage());
            $this->assertStringContainsString('[redacted]', $exception->getMessage());
        }

        $storedMeta = json_encode($connection->fresh()->meta, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('sensitive-access-token', $storedMeta);
        $this->assertStringContainsString('[redacted]', $storedMeta);
    }
}
