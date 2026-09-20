<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_account_deletion_is_blocked_while_owned_workspace_exists(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();

        $this->actingAs($owner)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'workspace')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($owner->fresh());
        $this->assertNotNull($workspace->fresh());
    }

    public function test_ownership_transfer_requires_current_password_and_an_existing_member(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $member = $this->attachMember($workspace);
        $outsider = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('profile.workspaces.transfer', $workspace), [
                'password' => 'wrong-password',
                'new_owner_id' => $member->id,
            ])
            ->assertSessionHasErrorsIn('workspaceTransfer', 'password');

        $this->actingAs($owner)
            ->post(route('profile.workspaces.transfer', $workspace), [
                'password' => 'password',
                'new_owner_id' => $outsider->id,
            ])
            ->assertSessionHasErrorsIn('workspaceTransfer', 'new_owner_id');

        $this->assertSame($owner->id, $workspace->fresh()->owner_id);
    }

    public function test_owner_can_transfer_workspace_then_delete_only_their_account(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $member = $this->attachMember($workspace, 'agent');

        $this->actingAs($owner)
            ->post(route('profile.workspaces.transfer', $workspace), [
                'password' => 'password',
                'new_owner_id' => $member->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame($member->id, $workspace->fresh()->owner_id);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'member',
        ]);
        $this->assertDatabaseHas('workspace_audit_events', [
            'workspace_id' => $workspace->id,
            'actor_user_id' => $owner->id,
            'event' => 'ownership_transferred',
        ]);

        $this->actingAs($owner)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($owner->fresh());
        $this->assertSame($member->id, $workspace->fresh()->owner_id);
    }

    public function test_workspace_deletion_requires_exact_slug_and_preserves_audit_evidence(): void
    {
        [$owner, $workspace] = $this->createWorkspaceOwner();
        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'owner-deletion-test',
            'provider_account_name' => 'Owner deletion test',
            'status' => 'connected',
        ]);
        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'local',
            'provider_conversation_id' => 'owner-deletion-conversation',
            'type' => 'direct',
            'title' => 'Owner deletion conversation',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $this->actingAs($owner)
            ->delete(route('profile.workspaces.destroy', $workspace), [
                'password' => 'password',
                'workspace_identifier' => 'wrong-slug',
            ])
            ->assertSessionHasErrorsIn('workspaceDeletion', 'workspace_identifier');

        $this->assertNotNull($workspace->fresh());

        $this->actingAs($owner)
            ->delete(route('profile.workspaces.destroy', $workspace), [
                'password' => 'password',
                'workspace_identifier' => $workspace->slug,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($owner->fresh());
        $this->assertNull($workspace->fresh());
        $this->assertNull($connection->fresh());
        $this->assertNull($conversation->fresh());
        $this->assertDatabaseHas('workspace_audit_events', [
            'workspace_id' => $workspace->id,
            'actor_user_id' => $owner->id,
            'event' => 'workspace_deleted',
        ]);
    }

    public function test_foreign_workspace_ownership_actions_are_hidden(): void
    {
        [$owner] = $this->createWorkspaceOwner();
        [, $foreignWorkspace] = $this->createWorkspaceOwner();

        $this->actingAs($owner)
            ->post(route('profile.workspaces.transfer', $foreignWorkspace), [
                'password' => 'password',
                'new_owner_id' => $owner->id,
            ])
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete(route('profile.workspaces.destroy', $foreignWorkspace), [
                'password' => 'password',
                'workspace_identifier' => $foreignWorkspace->slug,
            ])
            ->assertNotFound();
    }

    public function test_every_owned_workspace_must_be_resolved_before_account_deletion(): void
    {
        [$owner, $firstWorkspace] = $this->createWorkspaceOwner();
        $secondWorkspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Second Owned Workspace',
            'slug' => 'second-owned-workspace',
        ]);
        $secondWorkspace->members()->attach($owner->id, ['role' => 'owner']);
        $member = $this->attachMember($firstWorkspace);

        $this->actingAs($owner)->post(route('profile.workspaces.transfer', $firstWorkspace), [
            'password' => 'password',
            'new_owner_id' => $member->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'workspace');

        $this->assertNotNull($owner->fresh());
        $this->assertNotNull($secondWorkspace->fresh());
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function createWorkspaceOwner(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Owned Workspace',
            'slug' => 'owned-workspace-'.str()->random(8),
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $workspace];
    }

    private function attachMember(Workspace $workspace, string $role = 'member'): User
    {
        $member = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => $role]);

        return $member;
    }
}
