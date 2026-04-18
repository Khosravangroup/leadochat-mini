<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Connection Center
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Connect and manage your Meta providers for the current workspace.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Current workspace</h3>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Workspace name</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $workspace?->name ?? 'No workspace' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Workspace slug</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $workspace?->slug ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Total connections</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $connections->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900">Available providers</h3>
                <p class="mt-1 text-sm text-gray-500">
                    These cards are the base for the future Instagram, Facebook, and WhatsApp connect flows.
                </p>

                <div class="mt-6 grid gap-6 lg:grid-cols-3">
                    @foreach ($providerCards as $card)
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $card['title'] }}</h4>
                                    <p class="mt-2 text-sm leading-6 text-gray-600">
                                        {{ $card['subtitle'] }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    {{ $card['provider'] }}
                                </span>
                            </div>

                            <div class="mt-6 space-y-2 text-sm text-gray-600">
                                <p>
                                    <span class="font-semibold text-gray-900">Account type:</span>
                                    {{ $card['account_type'] }}
                                </p>
                                <p>
                                    <span class="font-semibold text-gray-900">Status:</span>
                                    Ready to connect
                                </p>
                            </div>

                            <div class="mt-6">
                                @if ($card['provider'] === 'instagram')
                                    <a href="{{ route('connections.instagram.redirect') }}"
                                       style="display:inline-block;background:#111827;color:#ffffff;padding:12px 18px;border-radius:12px;font-weight:700;font-size:14px;text-decoration:none;">
                                        Connect Instagram
                                    </a>
                                @else
                                    <div style="display:inline-block;background:#e5e7eb;color:#111827;padding:12px 18px;border:1px dashed #9ca3af;border-radius:12px;font-weight:700;font-size:14px;">
                                        Connect coming next
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900">Saved connections</h3>
                <p class="mt-1 text-sm text-gray-500">
                    This section shows saved provider accounts for the current workspace.
                </p>

                <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    @if ($connections->isEmpty())
                        <div class="p-6 text-sm text-gray-600">
                            No provider connections found for this workspace yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Provider</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Account Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Account Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Connection Kind</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Connected At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($connections as $connection)
                                        @php
                                            $isPending = $connection->status === 'pending_token_exchange';
                                            $isDemo = data_get($connection->meta, 'demo') === true;
                                        @endphp

                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $connection->provider }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $connection->provider_account_type }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $connection->provider_account_name ?? '-' }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                @if ($isPending)
                                                    <span class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">
                                                        pending_token_exchange
                                                    </span>
                                                @elseif ($connection->status === 'connected')
                                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                                        connected
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                        {{ $connection->status }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                @if ($isPending)
                                                    Pending OAuth Callback
                                                @elseif ($isDemo)
                                                    Demo Seed Data
                                                @else
                                                    Real Connection
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ optional($connection->connected_at)?->toDateTimeString() ?? '-' }}</td>
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
