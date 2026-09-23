<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">Instagram Insights</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('social.instagram.posts', $connection ? ['instagram_account' => $connection->id] : []) }}" class="rounded-lg border bg-white px-4 py-2 text-sm">Posts</a>
            @foreach ($connections as $account)
                <a href="{{ route('social.instagram.insights', ['instagram_account' => $account->id]) }}"
                   class="rounded-lg border px-4 py-2 text-sm {{ $connection?->id === $account->id ? 'bg-indigo-50 border-indigo-400' : 'bg-white' }}">
                    {{ $account->provider_account_name ?: 'Instagram account' }}
                </a>
            @endforeach
        </div>

        @if (! $connection)
            <p class="rounded-lg border bg-white p-5">Connect an Instagram professional account to view insights.</p>
        @elseif ($error)
            <p class="rounded-lg border border-red-200 bg-red-50 p-5 text-red-800" role="alert">{{ $error }}</p>
        @else
            <div class="rounded-lg border bg-white p-5">
                <h3 class="font-semibold">Account performance</h3>
                <p class="mt-1 text-sm text-gray-600">{{ $summary['since'] }} to {{ $summary['until'] }} (UTC). Meta may delay insights by up to 48 hours; unavailable metrics are shown as unavailable, not zero.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach (['reach' => 'Reach', 'views' => 'Views', 'total_interactions' => 'Total interactions'] as $key => $label)
                        <div class="rounded-lg border p-4">
                            <div class="text-sm text-gray-600">{{ $label }}</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $summary['values'][$key] === null ? 'Unavailable' : number_format($summary['values'][$key]) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
