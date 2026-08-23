<?php

declare(strict_types=1);

namespace HT2ML\Rh;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * ServiceProvider do módulo Rh (HT2 ERP).
 *
 * Integra o pacote ao core sem editar arquivos do boilerplate (ver ADR-0015):
 * - Rotas entram no grupo autenticado /admin via ModuleRegistry (herdam o middleware).
 * - Permissões e itens de menu são contribuídos por merge em config('access.modules')
 *   e config('admin-menu'), lidos de config/rh.php (publicável).
 */
final class RhServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rh.php', 'rh');

        // Registrado no register() para que o callback exista antes do carregamento
        // de routes/admin.php — o require roda DENTRO do grupo admin do core, então
        // as rotas do módulo herdam o prefixo /admin, o name "admin." e o middleware.
        ModuleRegistry::routes(function (): void {
            $rotas = __DIR__ . '/../routes/admin.php';

            if (is_file($rotas)) {
                require $rotas;
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rh');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/rh.php' => config_path('rh.php'),
        ], 'rh-config');

        ModuleRegistry::permissoes(
            (string) config('rh.modulo_acesso', 'negocio'),
            (array) config('rh.permissoes', []),
        );
        ModuleRegistry::itensDeMenu(
            (string) config('rh.secao_menu', 'negocio'),
            (array) config('rh.menu', []),
        );

        \Livewire\Livewire::component('rh.funcionarios.index', Livewire\Funcionarios\IndexFuncionario::class);
        \Livewire\Livewire::component('rh.funcionarios.form', Livewire\Funcionarios\FormFuncionario::class);
        \Livewire\Livewire::component('rh.funcionarios.funcionario-table', Livewire\Funcionarios\FuncionarioTable::class);
        \Illuminate\Support\Facades\Gate::policy(Models\Funcionario::class, Policies\FuncionarioPolicy::class);
        \Livewire\Livewire::component('rh.departamentos.index', Livewire\Departamentos\IndexDepartamento::class);
        \Livewire\Livewire::component('rh.departamentos.form', Livewire\Departamentos\FormDepartamento::class);
        \Livewire\Livewire::component('rh.departamentos.departamento-table', Livewire\Departamentos\DepartamentoTable::class);
        \Illuminate\Support\Facades\Gate::policy(Models\Departamento::class, Policies\DepartamentoPolicy::class);
        // make:modulo registra os componentes Livewire e as policies do módulo acima desta linha
    }
}
