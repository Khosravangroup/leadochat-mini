<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Owned workspaces</h2>
        <p class="mt-1 text-sm text-gray-600">
            Your account cannot be deleted while it owns a workspace. Transfer ownership to an existing member, or explicitly delete the workspace and all of its data.
        </p>
    </header>

    @if (session('status') === 'workspace-ownership-transferred')
        <p class="text-sm font-medium text-green-700">Workspace ownership transferred.</p>
    @elseif (session('status') === 'workspace-deleted')
        <p class="text-sm font-medium text-green-700">Workspace and its scoped data were deleted.</p>
    @endif

    @foreach ($ownedWorkspaces as $ownedWorkspace)
        <div class="space-y-5 rounded-lg border border-gray-200 p-5">
            <div>
                <div class="font-semibold text-gray-900">{{ $ownedWorkspace->name }}</div>
                <div class="mt-1 text-sm text-gray-600">Identifier: <strong>{{ $ownedWorkspace->slug }}</strong></div>
            </div>

            <form method="POST" action="{{ route('profile.workspaces.transfer', $ownedWorkspace) }}" class="space-y-3">
                @csrf
                <h3 class="font-medium text-gray-900">Transfer ownership</h3>
                <p class="text-sm text-gray-600">The selected member becomes owner. Your role changes to member.</p>

                <select name="new_owner_id" class="block w-full rounded-md border-gray-300" required>
                    <option value="">Select an existing member</option>
                    @foreach ($ownedWorkspace->members->where('id', '!=', auth()->id()) as $member)
                        <option value="{{ $member->id }}">{{ $member->name }} — {{ $member->email }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->workspaceTransfer->get('new_owner_id')" />

                <x-text-input name="password" type="password" class="block w-full" placeholder="Current password" required />
                <x-input-error :messages="$errors->workspaceTransfer->get('password')" />

                <x-primary-button>Transfer ownership</x-primary-button>
            </form>

            <form method="POST" action="{{ route('profile.workspaces.destroy', $ownedWorkspace) }}" class="space-y-3 border-t border-red-100 pt-5">
                @csrf
                @method('delete')
                <h3 class="font-medium text-red-800">Permanently delete workspace</h3>
                <p class="text-sm text-red-700">
                    This intentionally cascades to conversations, messages, attachments, catalogs, connections, and other workspace data. Restore requires a verified backup.
                </p>

                <x-text-input name="workspace_identifier" type="text" class="block w-full" placeholder="Type {{ $ownedWorkspace->slug }}" required />
                <x-input-error :messages="$errors->workspaceDeletion->get('workspace_identifier')" />

                <x-text-input name="password" type="password" class="block w-full" placeholder="Current password" required />
                <x-input-error :messages="$errors->workspaceDeletion->get('password')" />

                <x-danger-button>Delete workspace permanently</x-danger-button>
            </form>
        </div>
    @endforeach
</section>
