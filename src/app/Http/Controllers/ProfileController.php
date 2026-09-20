<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Workspace;
use App\Models\WorkspaceAuditEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'ownedWorkspaces' => $request->user()
                ->ownedWorkspaces()
                ->with(['members' => fn ($query) => $query->orderBy('users.name')->orderBy('users.email')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->hasFile('avatar')) {
            $oldAvatarPath = $user->avatar_path;
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');

            if ($oldAvatarPath) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        }

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->ownedWorkspaces()->exists()) {
            throw ValidationException::withMessages([
                'workspace' => 'Transfer or explicitly delete every owned workspace before deleting your account.',
            ])->errorBag('userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function transferWorkspaceOwnership(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->guardWorkspaceOwner($workspace, $user->id);

        $validated = $request->validateWithBag('workspaceTransfer', [
            'password' => ['required', 'current_password'],
            'new_owner_id' => [
                'required',
                'integer',
                Rule::notIn([$user->id]),
                Rule::exists('workspace_members', 'user_id')
                    ->where(fn ($query) => $query->where('workspace_id', $workspace->id)),
            ],
        ]);

        $newOwnerId = (int) $validated['new_owner_id'];

        DB::transaction(function () use ($workspace, $user, $newOwnerId): void {
            $lockedWorkspace = Workspace::query()->lockForUpdate()->findOrFail($workspace->id);
            $this->guardWorkspaceOwner($lockedWorkspace, $user->id);

            $newOwnerIsMember = DB::table('workspace_members')
                ->where('workspace_id', $lockedWorkspace->id)
                ->where('user_id', $newOwnerId)
                ->lockForUpdate()
                ->exists();

            if (! $newOwnerIsMember) {
                throw ValidationException::withMessages([
                    'new_owner_id' => 'The new owner must be an existing workspace member.',
                ])->errorBag('workspaceTransfer');
            }

            $lockedWorkspace->members()->updateExistingPivot($newOwnerId, ['role' => 'owner']);
            $lockedWorkspace->members()->updateExistingPivot($user->id, ['role' => 'member']);
            $lockedWorkspace->update(['owner_id' => $newOwnerId]);

            WorkspaceAuditEvent::create([
                'workspace_id' => $lockedWorkspace->id,
                'actor_user_id' => $user->id,
                'event' => 'ownership_transferred',
                'metadata' => [
                    'previous_owner_id' => $user->id,
                    'new_owner_id' => $newOwnerId,
                ],
            ]);
        });

        return Redirect::route('profile.edit')->with('status', 'workspace-ownership-transferred');
    }

    public function destroyWorkspace(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->guardWorkspaceOwner($workspace, $user->id);

        $request->validateWithBag('workspaceDeletion', [
            'password' => ['required', 'current_password'],
            'workspace_identifier' => ['required', 'string', Rule::in([$workspace->slug])],
        ]);

        DB::transaction(function () use ($workspace, $user): void {
            $lockedWorkspace = Workspace::query()->lockForUpdate()->findOrFail($workspace->id);
            $this->guardWorkspaceOwner($lockedWorkspace, $user->id);

            WorkspaceAuditEvent::create([
                'workspace_id' => $lockedWorkspace->id,
                'actor_user_id' => $user->id,
                'event' => 'workspace_deleted',
                'metadata' => [
                    'workspace_slug' => $lockedWorkspace->slug,
                ],
            ]);

            $lockedWorkspace->delete();
        });

        return Redirect::route('profile.edit')->with('status', 'workspace-deleted');
    }

    private function guardWorkspaceOwner(Workspace $workspace, int $userId): void
    {
        abort_unless((int) $workspace->owner_id === $userId, 404);
    }
}
