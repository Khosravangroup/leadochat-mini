<?php

namespace App\Http\Controllers;

use App\Models\WorkspaceTag;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\WorkspaceDepartment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class WorkspaceSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();
        $section = $request->query('section', 'general');

        $sections = [
            'general' => 'General',
            'tags' => 'Tags',
            'departments' => 'Departments',
            'team' => 'Team',
            'inbox' => 'Inbox',
            'channels' => 'Channels',
            'automation' => 'Automation',
            'ai' => 'AI',
            'billing' => 'Billing',
            'security' => 'Security',
            'advanced' => 'Advanced',
        ];

        if (!array_key_exists($section, $sections)) {
            $section = 'general';
        }

        $workspaceTags = collect();
        $workspaceDepartments = collect();
        $workspaceMembers = collect();
        $providerConnections = collect();
        $providerCards = collect();

        if ($workspace && $section === 'tags') {
            $workspaceTags = WorkspaceTag::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        if ($workspace && $section === 'departments') {
            $workspaceDepartments = WorkspaceDepartment::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        if ($workspace && $section === 'team') {
            $workspaceMembers = $workspace->members()
                ->orderBy('users.name')
                ->orderBy('users.email')
                ->get();
        }

        if ($workspace && $section === 'channels') {
            $providerConnections = $workspace->providerConnections()
                ->latest('id')
                ->get();

            $providerCards = collect([
                [
                    'key' => 'instagram',
                    'title' => 'Instagram',
                    'subtitle' => 'Connect Instagram messaging and account channels for this workspace.',
                    'account_type' => 'Business / Professional',
                    'connect_url' => route('connections.instagram.redirect'),
                    'is_ready' => true,
                ],
                [
                    'key' => 'facebook',
                    'title' => 'Facebook',
                    'subtitle' => 'Connect Facebook pages and messaging channels for this workspace.',
                    'account_type' => 'Page',
                    'connect_url' => null,
                    'is_ready' => false,
                ],
                [
                    'key' => 'whatsapp',
                    'title' => 'WhatsApp',
                    'subtitle' => 'Connect WhatsApp channels and phone-based messaging for this workspace.',
                    'account_type' => 'Phone / Business',
                    'connect_url' => null,
                    'is_ready' => false,
                ],
            ]);
        }

        return view('settings.index', [
            'workspace' => $workspace,
            'section' => $section,
            'sections' => $sections,
            'workspaceTags' => $workspaceTags,
            'workspaceDepartments' => $workspaceDepartments,
            'workspaceMembers' => $workspaceMembers,
            'providerConnections' => $providerConnections,
            'providerCards' => $providerCards,
            'canManageTeam' => (bool) ($workspace && $user),
        ]);
    }

    public function createTeamMember(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (! $workspace) {
            abort(404);
        }

        abort_unless($workspace->members()->whereKey($user->id)->exists(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($workspace, $validated) {
            $member = User::create([
                'name' => trim($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            $workspace->members()->attach($member->id, [
                'role' => 'agent',
            ]);
        });

        return redirect()->route('settings.index', [
            'section' => 'team',
        ])->with('status', 'Team member account created successfully.');
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace->update([
            'name' => trim($validated['name']),
        ]);

        return redirect()->route('settings.index', [
            'section' => 'general',
        ])->with('status', 'Workspace settings saved.');
    }

    public function createDepartment(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $name = trim($validated['name']);

        $existing = WorkspaceDepartment::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Department already exists.',
            ], 422);
        }

        $maxSort = (int) WorkspaceDepartment::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $department = WorkspaceDepartment::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'color' => $validated['color'],
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'ok' => true,
            'department' => $department,
        ]);
    }

    public function deleteDepartment(Request $request, WorkspaceDepartment $department): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $department->workspace_id !== $workspace->id) {
            abort(404);
        }

        $department->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function createTag(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $name = trim($validated['name']);

        $existing = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tag already exists.',
            ], 422);
        }

        $maxSort = (int) WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $tag = WorkspaceTag::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'color' => $validated['color'],
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'ok' => true,
            'tag' => $tag,
        ]);
    }

    public function updateTag(Request $request, WorkspaceTag $tag): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $tag->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ]);

        $name = trim($validated['name']);

        $duplicate = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->where('id', '!=', $tag->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Another tag with this name already exists.',
            ], 422);
        }

        $tag->update([
            'name' => $name,
            'color' => $validated['color'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        return response()->json([
            'ok' => true,
            'tag' => $tag->fresh(),
        ]);
    }

    public function deleteTag(Request $request, WorkspaceTag $tag): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $tag->workspace_id !== $workspace->id) {
            abort(404);
        }

        $tag->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
