<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Instagram\InstagramTokenExchangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class InstagramOAuthCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_app_review_scope_is_limited_to_supported_instagram_permissions(): void
    {
        $this->assertSame(
            'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish,instagram_business_manage_insights',
            config('services.instagram.scopes')
        );
        $this->assertSame('', config('services.meta.commerce_review_scopes'));
    }

    public function test_configured_scopes_are_recorded_as_requested_until_provider_grant_is_verified(): void
    {
        [$workspace] = $this->createWorkspaceOwner();
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'pending-instagram-account-'.$workspace->id,
            'provider_account_name' => 'Pending Instagram Connection',
            'status' => 'pending_token_exchange',
        ]);

        $method = new ReflectionMethod(InstagramTokenExchangeService::class, 'storeExchangeResult');
        $method->invoke(app(InstagramTokenExchangeService::class), $connection, [
            'access_token' => 'synthetic-token',
            'provider_account_id' => '17841400000000000',
            'provider_account_name' => 'review_account',
            'provider_account_type' => 'instagram_account',
            'scopes' => 'instagram_business_basic,instagram_business_manage_insights',
            'mode' => 'test',
        ]);

        $permissions = ProviderPermission::query()->orderBy('permission')->get();

        $this->assertCount(2, $permissions);
        $this->assertSame(['requested'], $permissions->pluck('status')->unique()->values()->all());
        $this->assertTrue($permissions->every(fn (ProviderPermission $permission) => $permission->granted_at === null));
    }

    public function test_callback_consumes_state_once_and_does_not_retain_oauth_debug_data(): void
    {
        [$workspace, $state] = $this->beginAuthorization();

        $this->mock(InstagramTokenExchangeService::class)
            ->shouldReceive('exchangeAndStore')
            ->once()
            ->andReturnUsing(function (ProviderConnection $connection): array {
                $connection->update([
                    'status' => 'connected',
                    'connected_at' => now(),
                ]);

                return ['status' => 'connected'];
            });

        $callback = route('connections.instagram.callback').'?'.http_build_query([
            'code' => 'synthetic-review-code',
            'state' => $state,
        ]);

        $this->get($callback)
            ->assertRedirect(route('settings.index', ['section' => 'channels']))
            ->assertSessionHas('status', 'Instagram connection completed successfully.')
            ->assertSessionMissing('instagram_oauth_state')
            ->assertSessionMissing('instagram_oauth_workspace_id');

        $connection = ProviderConnection::query()->where('workspace_id', $workspace->id)->firstOrFail();
        $this->assertArrayNotHasKey('callback_state', $connection->meta);
        $this->assertArrayNotHasKey('incoming_state', $connection->meta);
        $this->assertArrayNotHasKey('authorization_url', $connection->meta);
        $this->assertSame('connected', $connection->status);
        $this->assertNotNull($connection->connected_at);

        $this->get($callback)
            ->assertRedirect(route('settings.index', ['section' => 'channels']))
            ->assertSessionHas('error', 'Instagram connection failed because the login state was invalid or expired. Please try again.');

        $this->assertSame(1, ProviderConnection::query()->where('workspace_id', $workspace->id)->count());
    }

    public function test_callback_does_not_claim_success_when_token_exchange_is_incomplete(): void
    {
        [$workspace, $state] = $this->beginAuthorization();

        $this->mock(InstagramTokenExchangeService::class)
            ->shouldReceive('exchangeAndStore')
            ->once()
            ->andReturn(['status' => 'pending']);

        $this->get(route('connections.instagram.callback').'?'.http_build_query([
            'code' => 'synthetic-incomplete-code',
            'state' => $state,
        ]))
            ->assertRedirect(route('settings.index', ['section' => 'channels']))
            ->assertSessionHas('error', 'Instagram connection failed. Please try again.');

        $connection = ProviderConnection::query()->where('workspace_id', $workspace->id)->firstOrFail();
        $this->assertSame('failed_token_exchange', $connection->status);
        $this->assertNull($connection->connected_at);
    }

    public function test_provider_exchange_failure_redirects_without_exposing_provider_details(): void
    {
        [$workspace, $state] = $this->beginAuthorization();

        $this->mock(InstagramTokenExchangeService::class)
            ->shouldReceive('exchangeAndStore')
            ->once()
            ->andThrow(new RuntimeException('synthetic-provider-detail'));

        $this->get(route('connections.instagram.callback').'?'.http_build_query([
            'code' => 'synthetic-failed-code',
            'state' => $state,
        ]))
            ->assertRedirect(route('settings.index', ['section' => 'channels']))
            ->assertSessionHas('error', 'Instagram connection failed. Please try again.')
            ->assertDontSee('synthetic-provider-detail');

        $connection = ProviderConnection::query()->where('workspace_id', $workspace->id)->firstOrFail();
        $this->assertSame('failed_token_exchange', $connection->status);
        $this->assertNull($connection->connected_at);
    }

    public function test_state_cannot_connect_another_manager_workspace(): void
    {
        [, $state] = $this->beginAuthorization();
        $otherManager = User::factory()->create();
        $otherWorkspace = Workspace::create([
            'owner_id' => $otherManager->id,
            'name' => 'Other Review Workspace',
            'slug' => 'other-review-workspace',
        ]);
        $otherWorkspace->members()->attach($otherManager->id, ['role' => 'owner']);

        $this->mock(InstagramTokenExchangeService::class)
            ->shouldNotReceive('exchangeAndStore');

        $this->actingAs($otherManager)
            ->get(route('connections.instagram.callback').'?'.http_build_query([
                'code' => 'synthetic-cross-workspace-code',
                'state' => $state,
            ]))
            ->assertRedirect(route('settings.index', ['section' => 'channels']))
            ->assertSessionHas('error', 'Instagram connection failed because the login state was invalid or expired. Please try again.');

        $this->assertSame(0, ProviderConnection::query()->count());
    }

    private function beginAuthorization(): array
    {
        [$workspace, $user] = $this->createWorkspaceOwner();

        $authorizationResponse = $this->actingAs($user)
            ->get(route('connections.instagram.redirect'));
        $authorizationResponse->assertRedirect();
        parse_str((string) parse_url($authorizationResponse->headers->get('Location'), PHP_URL_QUERY), $query);
        $state = (string) ($query['state'] ?? '');

        $this->assertNotSame('', $state);

        return [$workspace, $state];
    }

    private function createWorkspaceOwner(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Instagram Review Workspace',
            'slug' => 'instagram-review-workspace-'.str()->random(8),
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        return [$workspace, $user];
    }
}
