<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_replayed_verification_link_does_not_dispatch_a_second_verified_event(): void
    {
        $user = User::factory()->unverified()->create();
        Event::fake([Verified::class]);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl)->assertRedirect();
        $this->actingAs($user)->get($verificationUrl)->assertRedirect();

        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_verification_resend_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($user)
                ->post(route('verification.send'))
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertTooManyRequests();

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_unverified_workspace_member_cannot_enter_the_application(): void
    {
        $user = User::factory()->unverified()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Verification Workspace',
            'slug' => 'verification-workspace',
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
