<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Conversation;
use App\Models\ProviderConnection;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_role_capability_matrix_enforces_owner_and_member_boundaries(): void
    {
        [$owner, $workspace] = $this->createWorkspaceUser('owner');
        $agent = $this->attachUser($workspace, 'agent');
        $member = $this->attachUser($workspace, 'member');
        $unknownRole = $this->attachUser($workspace, 'unexpected-role');

        $this->assertTrue(Gate::forUser($owner)->allows('workspace.access'));
        $this->assertTrue(Gate::forUser($owner)->allows('workspace.manage'));

        foreach ([$agent, $member] as $operator) {
            $this->assertTrue(Gate::forUser($operator)->allows('workspace.access'));
            $this->assertFalse(Gate::forUser($operator)->allows('workspace.manage'));
        }

        $this->assertFalse(Gate::forUser($unknownRole)->allows('workspace.access'));
        $this->assertFalse(Gate::forUser($unknownRole)->allows('workspace.manage'));
    }

    public function test_every_workspace_route_group_declares_the_required_capability_middleware(): void
    {
        $accessRoutes = [
            'dashboard',
            'social.index',
            'social.instagram.index',
            'social.instagram.posts',
            'social.instagram.realtime.posts',
            'social.instagram.comments',
            'social.instagram.stories',
            'inbox.index',
            'inbox.show',
            'inbox.realtime.snapshot',
            'inbox.messages.store',
            'inbox.catalog-products.send',
            'inbox.messages.reaction',
            'inbox.messages.voice',
            'inbox.note.save',
            'inbox.tags.workspace.list',
            'inbox.tags.workspace.create',
            'inbox.tags.conversation.save',
            'inbox.department.save',
            'inbox.agent.save',
            'inbox.archive',
            'inbox.unarchive',
            'inbox.trash',
            'inbox.restore',
            'attachments.show',
        ];

        foreach ($accessRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertContains('can:workspace.access', $route->gatherMiddleware(), "Route [{$routeName}] lacks workspace access middleware.");
        }

        $privilegedRoutes = [
            'connections.index',
            'connections.instagram.redirect',
            'connections.instagram.callback',
            'social.instagram.posts.publish',
            'social.instagram.posts.delete',
            'social.instagram.stories.publish',
            'social.instagram.stories.delete',
            'social.instagram.comments.reply',
            'social.instagram.comments.reply_dm',
            'social.instagram.comments.hide',
            'social.instagram.comments.unhide',
            'social.instagram.comments.delete',
            'inbox.tags.workspace.create',
        ];

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with((string) $route->getName(), 'settings.')) {
                $privilegedRoutes[] = $route->getName();
            }
        }

        foreach (array_unique($privilegedRoutes) as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertContains('can:workspace.manage', $route->gatherMiddleware(), "Route [{$routeName}] lacks workspace management middleware.");
        }
    }

    public function test_ordinary_workspace_roles_cannot_use_owner_routes_but_can_operate_the_inbox(): void
    {
        [, $workspace] = $this->createWorkspaceUser('owner');

        foreach (['agent', 'member'] as $role) {
            $user = $this->attachUser($workspace, $role);

            $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
            $this->actingAs($user)->get(route('connections.index'))->assertForbidden();
            $this->actingAs($user)->postJson(route('social.instagram.posts.publish'))->assertForbidden();
            $this->actingAs($user)->postJson(route('inbox.tags.workspace.create'), [
                'name' => 'Blocked tag',
                'color' => '#6366f1',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('inbox.index'))->assertOk();
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee(route('settings.index'), false);
        }
    }

    public function test_unknown_workspace_roles_cannot_access_workspace_application_routes(): void
    {
        [, $workspace] = $this->createWorkspaceUser('owner');
        $user = $this->attachUser($workspace, 'unexpected-role');

        $this->actingAs($user)->get(route('inbox.index'))->assertForbidden();
    }

    public function test_owner_can_access_workspace_management_routes(): void
    {
        [$owner] = $this->createWorkspaceUser('owner');

        $this->actingAs($owner)->get(route('settings.index'))->assertOk();
        $this->actingAs($owner)->get(route('connections.index'))->assertOk();
        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('settings.index'), false);
    }

    public function test_app_review_panel_does_not_present_local_groundwork_as_verified_provider_proof(): void
    {
        [$owner] = $this->createWorkspaceUser('owner');

        $this->actingAs($owner)
            ->get(route('settings.index', ['section' => 'commerce']))
            ->assertOk()
            ->assertSee('Not part of the current Instagram App Review submission')
            ->assertSee('Evidence tools available; provider proof still required')
            ->assertDontSee('Run discovery')
            ->assertDontSee('Run diagnostics')
            ->assertDontSee('Build commerce groundwork packet')
            ->assertDontSee('<span class="ws-commerce-pill ok">Commerce proof</span>', false)
            ->assertDontSee('<span class="ws-commerce-pill ok">Collection ads</span>', false);
    }

    public function test_foreign_workspace_objects_are_hidden_across_protected_route_groups(): void
    {
        [$owner] = $this->createWorkspaceUser('owner');
        [, $foreignWorkspace] = $this->createWorkspaceUser('owner');

        $foreignConnection = ProviderConnection::create([
            'workspace_id' => $foreignWorkspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'foreign-instagram-account',
            'provider_account_name' => 'Foreign Instagram',
            'status' => 'connected',
        ]);

        $foreignConversation = Conversation::create([
            'workspace_id' => $foreignWorkspace->id,
            'provider_connection_id' => $foreignConnection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => 'foreign-conversation',
            'type' => 'direct',
            'title' => 'Foreign conversation',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $foreignTag = WorkspaceTag::create([
            'workspace_id' => $foreignWorkspace->id,
            'name' => 'Foreign tag',
            'color' => '#6366f1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $foreignCatalog = Catalog::create([
            'workspace_id' => $foreignWorkspace->id,
            'provider_connection_id' => $foreignConnection->id,
            'source' => 'manual',
            'name' => 'Foreign catalog',
            'status' => 'active',
        ]);

        $foreignPost = SocialPost::create([
            'workspace_id' => $foreignWorkspace->id,
            'provider_connection_id' => $foreignConnection->id,
            'provider' => 'instagram',
            'provider_media_id' => 'foreign-media',
            'media_type' => 'IMAGE',
            'status' => 'published',
        ]);

        $this->actingAs($owner)->get(route('inbox.show', $foreignConversation))->assertNotFound();
        $this->actingAs($owner)->deleteJson(route('settings.tags.delete', $foreignTag))->assertNotFound();
        $this->actingAs($owner)->post(route('settings.catalogs.products.store', $foreignCatalog))->assertNotFound();
        $this->actingAs($owner)->post(route('settings.commerce.sync', $foreignConnection))->assertNotFound();
        $this->actingAs($owner)->delete(route('social.instagram.posts.delete', $foreignPost))->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function createWorkspaceUser(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Authorization Workspace '.str()->random(6),
            'slug' => 'authorization-workspace-'.str()->random(10),
        ]);

        $workspace->members()->attach($user->id, ['role' => $role]);

        return [$user, $workspace];
    }

    private function attachUser(Workspace $workspace, string $role): User
    {
        $user = User::factory()->create();
        $workspace->members()->attach($user->id, ['role' => $role]);

        return $user;
    }
}
