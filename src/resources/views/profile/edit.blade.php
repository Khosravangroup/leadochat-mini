<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="lc-app-kicker">Account settings</div>
            <h2 class="mt-3 text-3xl font-black leading-tight">{{ __('Profile') }}</h2>
            <p class="mt-2 text-sm" style="color: rgba(255,255,255,.8);">
                Update your identity, security settings, and profile image with the same Leadochat Mini visual system.
            </p>
        </div>
    </x-slot>

    <div class="lc-app-section">
        <div class="lc-app-container space-y-6">
            <div class="lc-app-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="lc-app-card">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if ($ownedWorkspaces->isNotEmpty())
                <div class="lc-app-card">
                    <div class="max-w-3xl">
                        @include('profile.partials.workspace-ownership-form')
                    </div>
                </div>
            @endif

            <div class="lc-app-card">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
