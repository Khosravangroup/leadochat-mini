<nav x-data="{ open: false }" class="lc-app-topbar-wrap">
    @php
        $navAvatarUrl = Auth::user()?->avatar_url ?: ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()?->name ?: 'User') . '&background=e2e8f0&color=334155');
        $currentWorkspace = Auth::user()?->currentWorkspace();
    @endphp
    <div class="lc-app-topbar">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-3">
                <div class="shrink-0 flex items-center ps-4">
                    <a href="{{ route('dashboard') }}" class="lc-app-brand">
                        <x-application-logo class="lc-app-brand-mark" />
                        <span class="lc-app-brand-copy">
                            <span class="lc-app-brand-title">Leadochat Mini</span>
                            <span class="lc-app-brand-subtitle">
                                {{ $currentWorkspace?->name ?? 'Customer operations workspace' }}
                            </span>
                        </span>
                    </a>
                </div>

                <div class="hidden gap-2 sm:ms-6 sm:flex sm:items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('inbox.index')" :active="request()->routeIs('inbox.*')">
                        {{ __('Inbox') }}
                    </x-nav-link>

                    <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                        {{ __('Settings') }}
                    </x-nav-link>

                    <x-nav-link :href="route('social.index')" :active="request()->routeIs('social.*')">
                        {{ __('Social') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-3 sm:pe-4">
                @if ($currentWorkspace)
                    <span class="lc-app-badge muted">
                        {{ $currentWorkspace->slug }}
                    </span>
                @endif

                <x-dropdown align="right" width="48" contentClasses="py-2 bg-white/95 backdrop-blur rounded-md">
                    <x-slot name="trigger">
                        <button class="lc-app-profile-trigger">
                            <img
                                src="{{ $navAvatarUrl }}"
                                alt="{{ Auth::user()->name }}"
                                class="lc-app-avatar"
                            >

                            <div class="text-left">
                                <div class="text-sm font-extrabold leading-tight text-slate-900">{{ Auth::user()->name }}</div>
                                <div class="text-xs font-semibold leading-tight text-slate-500">{{ Auth::user()->email }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center pe-4 sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-500 hover:text-teal-700 hover:bg-teal-50 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-teal-100 sm:hidden">
        <div class="space-y-1 px-3 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('inbox.index')" :active="request()->routeIs('inbox.*')">
                {{ __('Inbox') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                {{ __('Settings') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('social.index')" :active="request()->routeIs('social.*')">
                {{ __('Social') }}
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-teal-100 px-3 py-4">
            <div class="px-1">
                <div class="flex items-center gap-3">
                    <img
                        src="{{ $navAvatarUrl }}"
                        alt="{{ Auth::user()->name }}"
                        class="h-10 w-10 rounded-full border-2 border-amber-200 object-cover"
                    >

                    <div>
                        <div class="font-extrabold text-base text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="font-semibold text-sm text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
