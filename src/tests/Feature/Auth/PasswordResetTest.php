<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        Notification::fake();
        config()->set('auth.passwords.users.expire', 1);
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->travel(2)->minutes();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])->assertSessionHasErrors('email');

            $this->assertTrue(Hash::check('password', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_token_cannot_be_replayed(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $payload = [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ];

            $this->post('/reset-password', $payload)->assertSessionHasNoErrors();
            $this->post('/reset-password', $payload)->assertSessionHasErrors('email');

            return true;
        });
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/forgot-password', ['email' => $user->email]);
        }

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertTooManyRequests();
    }
}
