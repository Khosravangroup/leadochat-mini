<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceDepartment;
use App\Models\WorkspaceTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoredXssProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_and_department_endpoints_reject_non_hex_colors_and_blank_names(): void
    {
        [$user, $workspace] = $this->createWorkspaceOwner();

        $tag = WorkspaceTag::create([
            'workspace_id' => $workspace->id,
            'name' => 'Existing tag',
            'color' => '#6366f1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.tags.create'), [
                'name' => 'Unsafe color',
                'color' => 'red;background:red',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color');

        $this->actingAs($user)
            ->postJson(route('settings.departments.create'), [
                'name' => 'Unsafe color',
                'color' => '#fff;display:none',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color');

        $this->actingAs($user)
            ->postJson(route('inbox.tags.workspace.create'), [
                'name' => 'Unsafe color',
                'color' => 'transparent',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color');

        $this->actingAs($user)
            ->postJson(route('settings.tags.update', $tag), [
                'name' => 'Existing tag',
                'color' => 'var(--danger)',
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color');

        $this->actingAs($user)
            ->postJson(route('settings.tags.create'), [
                'name' => " \t\n ",
                'color' => '#6366f1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_legacy_invalid_colors_are_exposed_as_a_safe_fallback(): void
    {
        [, $workspace] = $this->createWorkspaceOwner();

        $now = now();

        $tagId = DB::table('workspace_tags')->insertGetId([
            'workspace_id' => $workspace->id,
            'name' => 'Legacy tag',
            'color' => 'red;background:red',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $departmentId = DB::table('workspace_departments')->insertGetId([
            'workspace_id' => $workspace->id,
            'name' => 'Legacy department',
            'color' => 'url(javascript:x)',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertSame('#6366f1', WorkspaceTag::findOrFail($tagId)->color);
        $this->assertSame('#6366f1', WorkspaceDepartment::findOrFail($departmentId)->color);
    }

    public function test_agent_name_write_endpoints_reject_blank_names(): void
    {
        [$user] = $this->createWorkspaceOwner();

        $this->actingAs($user)
            ->post(route('settings.team.create'), [
                'name' => " \t ",
                'email' => 'new-agent@example.test',
                'password' => 'Valid-password-123!',
                'password_confirmation' => 'Valid-password-123!',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => " \t ",
                'email' => $user->email,
            ])
            ->assertSessionHasErrors('name');

        auth()->logout();

        $this->post(route('register'), [
            'name' => " \t ",
            'email' => 'blank-name@example.test',
            'password' => 'Valid-password-123!',
            'password_confirmation' => 'Valid-password-123!',
        ])->assertSessionHasErrors('name');
    }

    public function test_malicious_names_are_escaped_in_settings_and_inbox_views(): void
    {
        $maliciousTag = '"><img src=x onerror=alert(101)>';
        $maliciousDepartment = '"><svg onload=alert(102)>';
        $maliciousAgent = '"><script>alert(103)</script>';

        [$user, $workspace] = $this->createWorkspaceOwner($maliciousAgent);

        $tag = WorkspaceTag::create([
            'workspace_id' => $workspace->id,
            'name' => $maliciousTag,
            'color' => '#123abc',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $department = WorkspaceDepartment::create([
            'workspace_id' => $workspace->id,
            'name' => $maliciousDepartment,
            'color' => '#abcdef',
            'sort_order' => 1,
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'stored-xss-account',
            'provider_account_name' => 'Stored XSS Test',
            'status' => 'connected',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'department_id' => $department->id,
            'assigned_user_id' => $user->id,
            'provider' => 'local',
            'provider_conversation_id' => 'stored-xss-conversation',
            'type' => 'direct',
            'title' => 'Stored XSS Test',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $conversation->workspaceTags()->attach($tag->id);

        foreach (['tags' => $maliciousTag, 'departments' => $maliciousDepartment, 'team' => $maliciousAgent] as $section => $value) {
            $this->actingAs($user)
                ->get(route('settings.index', ['section' => $section]))
                ->assertOk()
                ->assertDontSee($value, false)
                ->assertSee(e($value), false);
        }

        $this->actingAs($user)
            ->get(route('inbox.show', $conversation))
            ->assertOk()
            ->assertDontSee($maliciousTag, false)
            ->assertDontSee($maliciousDepartment, false)
            ->assertDontSee($maliciousAgent, false)
            ->assertSee(e($maliciousTag), false)
            ->assertSee(e($maliciousDepartment), false)
            ->assertSee(e($maliciousAgent), false);
    }

    public function test_assignment_renderers_do_not_interpolate_untrusted_values_into_inner_html(): void
    {
        $settingsView = file_get_contents(resource_path('views/settings/index.blade.php'));
        $inboxView = file_get_contents(resource_path('views/inbox/index.blade.php'));
        $inboxTagsModule = file_get_contents(resource_path('js/pages/inbox-tags.js'));

        $this->assertIsString($settingsView);
        $this->assertIsString($inboxView);
        $this->assertIsString($inboxTagsModule);

        $this->assertStringNotContainsString('wrapper.innerHTML', $settingsView);
        $this->assertStringNotContainsString('currentDepartmentWrap.innerHTML', $inboxView);
        $this->assertStringNotContainsString('currentAgentWrap.innerHTML', $inboxView);
        $this->assertStringNotContainsString('selectedTagsContainer.innerHTML', $inboxView);
        $this->assertStringNotContainsString('selectedTagsContainer.innerHTML', $inboxTagsModule);
        $this->assertStringNotContainsString('item.innerHTML', $inboxView);
        $this->assertStringNotContainsString('${file.name}', $inboxView);

        $this->assertStringContainsString('label.textContent = String(tag.name', $settingsView);
        $this->assertStringContainsString('chip.textContent = departmentName', $inboxView);
        $this->assertStringContainsString('chip.textContent = agentName', $inboxView);
        $this->assertStringContainsString('chip.textContent = label', $inboxTagsModule);
        $this->assertStringContainsString('name.textContent = file.name', $inboxView);
    }

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function createWorkspaceOwner(string $name = 'Workspace Owner'): array
    {
        $user = User::factory()->create([
            'name' => $name,
        ]);

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Stored XSS Workspace',
            'slug' => 'stored-xss-workspace-'.str()->random(8),
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        return [$user, $workspace];
    }
}
