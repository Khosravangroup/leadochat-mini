<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="lc-app-kicker">Workspace overview</div>
                <h2 class="mt-3 text-3xl font-black leading-tight">
                    Dashboard
                </h2>
                <p class="mt-2 text-sm" style="color: rgba(255,255,255,.8);">
                    One place for your workspace, team context, inbox flow, and connected channels.
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

    <div class="lc-app-section">
        <div class="lc-app-container space-y-6">
            <div class="lc-app-hero">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="lc-app-kicker">Leadochat Mini</div>
                        <h1 class="lc-app-title mt-4">
                            Keep your team, channels, and customer context in one focused workspace.
                        </h1>
                        <p class="lc-app-subtitle">
                            Move from inbox to social to settings without losing the thread. This dashboard is the quick pulse for the workspace you are actively operating.
                        </p>
                    </div>

                    <div class="flex items-center gap-4 rounded-lg border border-white/15 bg-white/10 px-4 py-4">
                        <x-application-logo class="h-16 w-16 shrink-0" />
                        <div>
                            <div class="text-lg font-black">Leadochat Mini</div>
                            <div class="text-sm" style="color: rgba(255,255,255,.78);">
                                {{ $currentWorkspace?->name ?? 'No active workspace' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lc-app-grid cols-3">
                <div class="lc-app-card soft">
                    <div class="lc-app-stat-label">Logged in user</div>
                    <div class="mt-3 text-2xl font-black text-slate-900">
                        {{ $user->name }}
                    </div>
                    <p class="mt-2 lc-app-meta">
                        {{ $user->email }}
                    </p>
                </div>

                <div class="lc-app-card soft">
                    <div class="lc-app-stat-label">Current workspace</div>
                    <div class="mt-3 text-2xl font-black text-slate-900">
                        {{ $currentWorkspace?->name ?? 'No workspace' }}
                    </div>
                    <p class="mt-2 lc-app-meta">
                        Slug: <span class="lc-app-code">{{ $currentWorkspace?->slug ?? '-' }}</span>
                    </p>
                </div>

                <div class="lc-app-card soft">
                    <div class="lc-app-stat-label">Role & workspace count</div>
                    <div class="mt-3 text-2xl font-black text-slate-900">
                        {{ $currentRole ?? '-' }}
                    </div>
                    <p class="mt-2 lc-app-meta">
                        Total workspaces: {{ $workspaceCount }}
                    </p>
                </div>
            </div>

            <div class="lc-app-grid cols-2">
                <div class="lc-app-card">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-900">Workspace summary</h3>
                            <p class="mt-2 lc-app-meta">
                                Keep the operational basics visible while moving between inbox, social, and configuration work.
                            </p>
                        </div>
                        <span class="lc-app-badge">Active workspace</span>
                    </div>

                    <div class="mt-6 lc-app-list">
                        <div class="lc-app-list-row">
                            <div class="lc-app-list-key">Owner ID</div>
                            <div class="lc-app-list-value">{{ $currentWorkspace?->owner_id ?? '-' }}</div>
                        </div>
                        <div class="lc-app-list-row">
                            <div class="lc-app-list-key">Workspace ID</div>
                            <div class="lc-app-list-value">{{ $currentWorkspace?->id ?? '-' }}</div>
                        </div>
                        <div class="lc-app-list-row">
                            <div class="lc-app-list-key">Current role</div>
                            <div class="lc-app-list-value">{{ $currentRole ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="lc-app-card">
                    <h3 class="text-xl font-black text-slate-900">Jump back into work</h3>
                    <p class="mt-2 lc-app-meta">
                        The app is designed to feel like one operating surface. These shortcuts keep the high-traffic areas one click away.
                    </p>

                    <div class="lc-app-actions">
                        <a href="{{ route('inbox.index') }}" class="inline-flex items-center rounded-lg bg-teal-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                            Open Inbox
                        </a>
                        <a href="{{ route('social.index') }}" class="inline-flex items-center rounded-lg border border-teal-100 bg-white px-5 py-3 text-sm font-black text-teal-900 transition hover:bg-teal-50">
                            Open Social
                        </a>
                        @can('workspace.manage')
                            <a href="{{ route('settings.index') }}" class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-black text-amber-900 transition hover:bg-amber-100">
                                Open Settings
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
