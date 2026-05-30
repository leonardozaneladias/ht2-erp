<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AdminUser;
use App\Policies\AdminUserPolicy;
use App\Policies\RolePolicy;
use App\Services\Admin\AccessResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessResolver::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Paginator::defaultView('vendor.pagination.inspinia');
        Paginator::defaultSimpleView('vendor.pagination.simple-inspinia');

        Gate::policy(AdminUser::class, AdminUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(PermissionGrant::class, PermissionGrantPolicy::class);

        // Resolução central de acesso: super-admin > deny > grant > role.
        // Abilities não-nomeadas (ex.: 'create') recebem null e caem nas Policies.
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof AdminUser) {
                return null;
            }

            return app(AccessResolver::class)->decide($user, $ability);
        });
    }
}
