<?php

namespace App\Providers;

use App\Models\User;
use App\Support\WorkspaceAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define(
            WorkspaceAccess::ACCESS,
            fn (User $user): bool => WorkspaceAccess::allows($user, WorkspaceAccess::ACCESS)
        );

        Gate::define(
            WorkspaceAccess::MANAGE,
            fn (User $user): bool => WorkspaceAccess::allows($user, WorkspaceAccess::MANAGE)
        );
    }
}
