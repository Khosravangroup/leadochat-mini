<x-layouts.public :title="'Leadochat Mini — Home'">
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-20">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Meta Social Management MVP</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 md:text-6xl">
                    Manage Instagram, Facebook, and WhatsApp from one academic MVP.
                </h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">
                    Leadochat Mini is a university project focused on Meta messaging, publishing, comment management,
                    and review-ready product flows using official APIs.
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Get Started
                    </a>
                    <a href="{{ route('features') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Explore Features
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto grid max-w-7xl gap-6 px-6 py-16 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Unified Inbox</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Bring Instagram, Facebook Messenger, and WhatsApp conversations into one clean interface.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Social Studio</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Work with posts, comments, stories, and publishing flows in a feed-like management screen.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Meta Review Ready</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Prepare a real product shell with privacy, deletion, diagnostics, and reviewer-friendly flows.
                </p>
            </div>
        </div>
    </section>
</x-layouts.public>
