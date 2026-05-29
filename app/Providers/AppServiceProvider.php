<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AdminUser;
use App\Policies\AdminUserPolicy;
use App\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Paginator::defaultView('vendor.pagination.inspinia');
        Paginator::defaultSimpleView('vendor.pagination.simple-inspinia');

        Gate::policy(AdminUser::class, AdminUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function ($user): ?bool {
            if ($user instanceof AdminUser && $user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });
    }
}
