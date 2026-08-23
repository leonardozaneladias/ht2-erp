<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Referencia\FonteDeCargos;
use App\Contracts\Referencia\FonteDeMunicipios;
use App\Contracts\Referencia\FonteDeUnidadesFederativas;
use App\Http\Middleware\AdminAuthenticate;
use App\Policies\RolePolicy;
use App\Services\Admin\AccessResolver;
use App\Services\Admin\Referencia\CatalogoDeLocalidades;
use App\Support\Documents\GeradorNumeroDocumento;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\PermissionGrant;
use HT2ML\Core\Services\Admin\Settings\SettingsRuntimeApplier;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Auth\Notifications\ResetPassword;
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
        $this->app->singleton(\HT2ML\Core\Support\Tenancy\TenantContext::class);
        $this->app->singleton(ImpersonationContext::class);
        $this->app->singleton(GeradorNumeroDocumento::class);
    }

    public function boot(): void
    {
        $this->registrarCatalogos();

        Model::preventLazyLoading(! app()->isProduction());

        // Contribuições das extensões (permissões e menu). Roda aqui, e não no
        // provider de cada extensão, porque os providers de pacote registram
        // antes deste — então neste ponto todas já declararam.
        ModuleRegistry::aplicarContribuicoes();

        // Aplica as configurações persistidas (idioma, e-mail, sessão) ao config()
        // em runtime. Tolerante a falhas durante a 1ª instalação/migração.
        app(SettingsRuntimeApplier::class)->apply();

        Paginator::defaultView('vendor.pagination.inspinia');
        Paginator::defaultSimpleView('vendor.pagination.simple-inspinia');

        // AdminUser, Empresa e PermissionGrant são registrados pelo
        // CoreServiceProvider: policy de model do core viaja com o model.
        Gate::policy(Role::class, RolePolicy::class);

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

        // Pulse integrado ao RBAC do admin (mesmo modelo do Horizon): a permissão
        // `sistema.pulse` decide o acesso; super-admin passa pelo bypass.
        Gate::define('viewPulse', function ($user = null): bool {
            $admin = auth('admin')->user();

            return $admin instanceof AdminUser && $admin->can('sistema.pulse');
        });

        // Carimba empresa/filial do tenant ativo e, durante personificação, quem
        // está por trás (impersonado_por). Ponto único de "contexto → activity_log".
        Activity::creating(app(\HT2ML\Core\Support\Audit\CarimbarContextoNaAtividade::class));

        // A notificação padrão de reset de senha monta a URL com route('password.reset'),
        // que não existe aqui (a rota é admin.password.reset). Redireciona a construção
        // da URL para a rota admin (token na URI, e-mail na query).
        ResetPassword::createUrlUsing(static fn (\Illuminate\Contracts\Auth\CanResetPassword $notifiable, string $token): string => route(
            'admin.password.reset',
            ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()],
        ));
    }

    /**
     * Fontes de catálogo de localidades.
     *
     * Incondicional, e é assim de propósito: o ADR-0020 fixou que estes
     * catálogos ficam no core. Quem consumir os contratos pode resolvê-los
     * direto, sem checar app()->bound().
     */
    private function registrarCatalogos(): void
    {
        $this->app->singleton(FonteDeUnidadesFederativas::class, CatalogoDeLocalidades::class);
        $this->app->singleton(FonteDeMunicipios::class, CatalogoDeLocalidades::class);
        $this->app->singleton(FonteDeCargos::class, CatalogoDeLocalidades::class);
    }
}
