<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkspaceMemberVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_created_member_remains_unverified_and_receives_verification_email(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Member Verification Workspace',
            'slug' => 'member-verification-workspace',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($owner)
            ->post(route('settings.team.create'), [
                'name' => 'New Agent',
                'email' => 'new-agent@example.test',
                'password' => 'Valid-password-123!',
                'password_confirmation' => 'Valid-password-123!',
            ])
            ->assertRedirect(route('settings.index', ['section' => 'team']));

        $member = User::query()->where('email', 'new-agent@example.test')->firstOrFail();

        $this->assertNull($member->email_verified_at);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'agent',
        ]);
        Notification::assertSentTo($member, VerifyEmail::class);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
