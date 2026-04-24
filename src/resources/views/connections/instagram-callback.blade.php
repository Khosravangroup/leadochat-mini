<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="lc-app-kicker">Instagram connection</div>
            <h2 class="mt-3 text-3xl font-black leading-tight">
            Instagram Callback Result
            </h2>
        </div>
    </x-slot>

    <div class="lc-app-section">
        <div class="lc-app-container space-y-6">

            <div class="lc-app-card">
                <h3 class="text-xl font-black text-slate-900">Callback status</h3>

                <div class="mt-4">
                    @if ($result['status'] === 'callback_received')
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-extrabold text-emerald-700">
                            Callback received, state validation passed, and token exchange flow was executed.
                        </div>
                    @elseif ($result['status'] === 'invalid_state')
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-extrabold text-red-700">
                            Callback received but state validation failed.
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-extrabold text-amber-700">
                            Callback loaded without authorization code.
                        </div>
                    @endif
                </div>
            </div>

            <div class="lc-app-card">
                <h3 class="text-xl font-black text-slate-900">Callback payload</h3>
                <p class="mt-2 lc-app-meta">
                    Temporary local/staging debug screen for Instagram OAuth callback flow.
                </p>

                <pre class="mt-6 overflow-x-auto rounded-lg bg-slate-950 p-4 text-sm text-emerald-300">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

        </div>
    </div>
</x-app-layout>
