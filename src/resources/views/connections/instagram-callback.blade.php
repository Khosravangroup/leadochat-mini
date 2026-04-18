<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Instagram Callback Result
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Callback status</h3>

                <div class="mt-4">
                    @if ($result['status'] === 'callback_received')
                        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                            Callback received, state validation passed, and token exchange flow was executed.
                        </div>
                    @elseif ($result['status'] === 'invalid_state')
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            Callback received but state validation failed.
                        </div>
                    @else
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-semibold text-yellow-700">
                            Callback loaded without authorization code.
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Callback payload</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Temporary local/staging debug screen for Instagram OAuth callback flow.
                </p>

                <pre class="mt-6 overflow-x-auto rounded-xl bg-gray-900 p-4 text-sm text-green-300">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

        </div>
    </div>
</x-app-layout>
