<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="lc-app-kicker">Connected channels</div>
                <h2 class="mt-3 text-3xl font-black leading-tight">
                    Connection Center
                </h2>
                <p class="mt-2 text-sm" style="color: rgba(255,255,255,.8);">
                    Connect and manage your Meta providers for the current workspace.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="lc-app-section">
        <div class="lc-app-container space-y-8">

            <div class="lc-app-card">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Current workspace</h3>
                        <p class="mt-2 lc-app-meta">
                            These connections belong to the active workspace and drive inbox, social, and commerce behavior.
                        </p>
                    </div>
                    <span class="lc-app-badge">{{ $connections->count() }} saved</span>
                </div>

                <div class="mt-6 lc-app-grid cols-3">
                    <div>
                        <p class="lc-app-stat-label">Workspace name</p>
                        <p class="mt-2 text-lg font-black text-slate-900">
                            {{ $workspace?->name ?? 'No workspace' }}
                        </p>
                    </div>

                    <div>
                        <p class="lc-app-stat-label">Workspace slug</p>
                        <p class="mt-2 text-lg font-black text-slate-900">
                            <span class="lc-app-code">{{ $workspace?->slug ?? '-' }}</span>
                        </p>
                    </div>

                    <div>
                        <p class="lc-app-stat-label">Total connections</p>
                        <p class="mt-2 text-lg font-black text-slate-900">
                            {{ $connections->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-black text-slate-900">Available providers</h3>
                <p class="mt-2 lc-app-meta">
                    These cards are the base for the future Instagram, Facebook, and WhatsApp connect flows.
                </p>

                <div class="mt-6 lc-app-grid cols-3">
                    @foreach ($providerCards as $card)
                        <div class="lc-app-card soft">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-lg font-black text-slate-900">{{ $card['title'] }}</h4>
                                    <p class="mt-2 lc-app-meta">
                                        {{ $card['subtitle'] }}
                                    </p>
                                </div>

                                <span class="lc-app-badge warm">
                                    {{ $card['provider'] }}
                                </span>
                            </div>

                            <div class="mt-6 space-y-2 text-sm text-slate-600">
                                <p>
                                    <span class="font-semibold text-slate-900">Account type:</span>
                                    {{ $card['account_type'] }}
                                </p>
                                <p>
                                    <span class="font-semibold text-slate-900">Status:</span>
                                    Ready to connect
                                </p>
                            </div>

                            <div class="mt-6">
                                @if ($card['provider'] === 'instagram')
                                    <a href="{{ route('connections.instagram.redirect') }}"
                                       style="display:inline-flex;align-items:center;min-height:46px;background:#0f766e;color:#ffffff;padding:0 18px;border-radius:8px;font-weight:900;font-size:14px;text-decoration:none;box-shadow:0 14px 28px rgba(15,118,110,.16);">
                                        Connect Instagram
                                    </a>
                                @else
                                    <div style="display:inline-flex;align-items:center;min-height:46px;background:#fff7ed;color:#9a3412;padding:0 18px;border:1px dashed #fdba74;border-radius:8px;font-weight:900;font-size:14px;">
                                        Connect coming next
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-xl font-black text-slate-900">Saved connections</h3>
                <p class="mt-2 lc-app-meta">
                    This section shows saved provider accounts for the current workspace.
                </p>

                <div class="mt-6 lc-app-table-shell">
                    @if ($connections->isEmpty())
                        <div class="p-6 lc-app-empty">
                            No provider connections found for this workspace yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="lc-app-table">
                                <thead>
                                    <tr>
                                        <th>Provider</th>
                                        <th>Account Type</th>
                                        <th>Account Name</th>
                                        <th>Status</th>
                                        <th>Connection Kind</th>
                                        <th>Connected At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($connections as $connection)
                                        @php
                                            $isPending = $connection->status === 'pending_token_exchange';
                                            $isDemo = data_get($connection->meta, 'demo') === true;
                                        @endphp

                                        <tr>
                                            <td>{{ $connection->provider }}</td>
                                            <td>{{ $connection->provider_account_type }}</td>
                                            <td>{{ $connection->provider_account_name ?? '-' }}</td>
                                            <td>
                                                @if ($isPending)
                                                    <span class="lc-app-badge warm">
                                                        pending_token_exchange
                                                    </span>
                                                @elseif ($connection->status === 'connected')
                                                    <span class="lc-app-badge success">
                                                        connected
                                                    </span>
                                                @else
                                                    <span class="lc-app-badge muted">
                                                        {{ $connection->status }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isPending)
                                                    Pending OAuth Callback
                                                @elseif ($isDemo)
                                                    Demo Seed Data
                                                @else
                                                    Real Connection
                                                @endif
                                            </td>
                                            <td>{{ optional($connection->connected_at)?->toDateTimeString() ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
