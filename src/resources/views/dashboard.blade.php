<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Workspace overview for Leadochat Mini
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $currentWorkspace = $user?->currentWorkspace();
        $workspaceCount = $user?->workspaces()->count() ?? 0;
        $currentRole = $currentWorkspace?->pivot?->role;
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Logged in user</p>
                    <p class="mt-3 text-2xl font-semibold text-gray-900">
                        {{ $user->name }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $user->email }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Current workspace</p>
                    <p class="mt-3 text-2xl font-semibold text-gray-900">
                        {{ $currentWorkspace?->name ?? 'No workspace' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        Slug: {{ $currentWorkspace?->slug ?? '-' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Role & workspace count</p>
                    <p class="mt-3 text-2xl font-semibold text-gray-900">
                        {{ $currentRole ?? '-' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        Total workspaces: {{ $workspaceCount }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Workspace summary</h3>
                <div class="mt-4 space-y-3 text-sm text-gray-700">
                    <p>
                        <span class="font-semibold text-gray-900">Owner ID:</span>
                        {{ $currentWorkspace?->owner_id ?? '-' }}
                    </p>
                    <p>
                        <span class="font-semibold text-gray-900">Workspace ID:</span>
                        {{ $currentWorkspace?->id ?? '-' }}
                    </p>
                    <p>
                        <span class="font-semibold text-gray-900">Current role:</span>
                        {{ $currentRole ?? '-' }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
