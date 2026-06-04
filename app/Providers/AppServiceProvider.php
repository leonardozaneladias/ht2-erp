<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\AdminAuthenticate;
use App\Models\AdminUser;
use App\Models\PermissionGrant;
use App\Policies\AdminUserPolicy;
use App\Policies\PermissionGrantPolicy;
use App\Policies\RolePolicy;
use App\Services\Admin\AccessResolver;
use App\Services\Admin\Settings\SettingsRuntimeApplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessResolver::class);
        $this->app->singleton(\App\Support\Tenancy\TenantContext::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        // Aplica as configurações persistidas (idioma, e-mail, sessão) ao config()
        // em runtime. Tolerante a falhas durante a 1ª instalação/migração.
        app(SettingsRuntimeApplier::class)->apply();

        Paginator::defaultView('vendor.pagination.inspinia');
        Paginator::defaultSimpleView('vendor.pagination.simple-inspinia');

        Gate::policy(AdminUser::class, AdminUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(PermissionGrant::class, PermissionGrantPolicy::class);

        // Reaplica o guard admin nas requisições de update do Livewire (AJAX).
        // Sem isto, $this->authorize() em métodos de ação resolve o guard padrão
        // (web, vazio) e retorna 403. Só reaplica em componentes cuja rota original
        // já usava admin.auth — a tela de login (sem o middleware) segue livre.
        Livewire::addPersistentMiddleware([AdminAuthenticate::class]);

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
