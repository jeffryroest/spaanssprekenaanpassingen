<?php

namespace App\Providers;

use App\Enums\ContentPermission;
use App\Models\User;
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
        foreach (ContentPermission::cases() as $permission) {
            Gate::define(
                "content-studio.{$permission->value}",
                fn (User $user): bool => $user->hasContentPermission($permission),
            );
        }
    }
}
