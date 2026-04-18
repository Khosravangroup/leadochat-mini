<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-slate-900">
            Leadochat Mini
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-slate-700 md:flex">
            <a href="{{ route('features') }}" class="hover:text-slate-900">Features</a>
            <a href="{{ route('about') }}" class="hover:text-slate-900">About</a>
            <a href="{{ route('privacy-policy') }}" class="hover:text-slate-900">Privacy Policy</a>
            <a href="{{ route('data-deletion') }}" class="hover:text-slate-900">Data Deletion</a>
            <a href="{{ route('contact') }}" class="hover:text-slate-900">Contact</a>
        </nav>

        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Login
            </a>
            <a href="{{ route('register') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Register
            </a>
        </div>
    </div>
</header>
