<?php

namespace Tests\Feature;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InstagramInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_account_insights_using_instagram_login_token(): void
    {
        [$owner, $connection] = $this->connectedAccount();
        Http::fake([
            'https://graph.instagram.com/v25.0/17840000000000001/insights*' => Http::response([
                'data' => [
                    ['name' => 'reach', 'total_value' => ['value' => 42]],
                    ['name' => 'views', 'total_value' => ['value' => 75]],
                    ['name' => 'total_interactions', 'total_value' => ['value' => 0]],
                ],
            ]),
        ]);

        $this->actingAs($owner)
            ->get(route('social.instagram.insights', ['instagram_account' => $connection->id]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Account performance')
            ->assertSee('42')
            ->assertSee('75')
            ->assertDontSee('synthetic-instagram-token');

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://graph.instagram.com/v25.0/17840000000000001/insights?')
            && $request->hasHeader('Authorization', 'Bearer synthetic-instagram-token')
            && $request['metric'] === 'reach,views,total_interactions'
            && $request['metric_type'] === 'total_value');
    }

    public function test_cross_workspace_connection_cannot_be_used_for_insights(): void
    {
        [$owner] = $this->connectedAccount();
        [, $foreignConnection] = $this->connectedAccount('other');
        Http::fake();

        $this->actingAs($owner)
            ->get(route('social.instagram.insights', ['instagram_account' => $foreignConnection->id]))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_regular_workspace_member_cannot_read_insights(): void
    {
        [$owner, $connection] = $this->connectedAccount();
        $member = User::factory()->create();
        $connection->workspace->members()->attach($member->id, ['role' => 'agent']);
        Http::fake();

        $this->actingAs($member)
            ->get(route('social.instagram.insights', ['instagram_account' => $connection->id]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_expired_token_is_not_sent_to_meta(): void
    {
        [$owner, $connection] = $this->connectedAccount();
        $connection->oauthTokens()->update(['expires_at' => now()->subMinute()]);
        Http::fake();

        $this->actingAs($owner)
            ->get(route('social.instagram.insights', ['instagram_account' => $connection->id]))
            ->assertOk()
            ->assertSee('Instagram insights could not be loaded');

        Http::assertNothingSent();
    }

    public function test_missing_values_are_not_reported_as_zero(): void
    {
        [$owner, $connection] = $this->connectedAccount();
        Http::fake([
            'https://graph.instagram.com/*' => Http::response(['data' => [
                ['name' => 'reach', 'total_value' => ['value' => 8]],
            ]]),
        ]);

        $this->actingAs($owner)
            ->get(route('social.instagram.insights', ['instagram_account' => $connection->id]))
            ->assertOk()
            ->assertSee('Unavailable');

    }

    public function test_provider_errors_do_not_expose_secrets(): void
    {
        [$owner, $connection] = $this->connectedAccount();
        Log::spy();
        Http::fake([
            'https://graph.instagram.com/*' => Http::response(['error' => ['message' => 'synthetic-instagram-token']], 403),
        ]);

        $this->actingAs($owner)
            ->get(route('social.instagram.insights', ['instagram_account' => $connection->id]))
            ->assertOk()
            ->assertSee('Instagram insights could not be loaded')
            ->assertDontSee('synthetic-instagram-token');

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context) => $message === 'Instagram insights request failed.'
                && $context['provider_connection_id'] === $connection->id
                && $context['exception'] === \RuntimeException::class
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'synthetic-instagram-token')
        );
    }

    private function connectedAccount(string $suffix = 'owner'): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Insights '.$suffix,
            'slug' => 'insights-'.$suffix,
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => $suffix === 'owner' ? '17840000000000001' : '17840000000000002',
            'provider_account_name' => 'insights_'.$suffix,
            'status' => 'connected',
        ]);
        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'synthetic-instagram-token',
            'expires_at' => now()->addDay(),
            'is_primary' => true,
        ]);

        return [$owner, $connection];
    }
}
