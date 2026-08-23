<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Extensão Fiscal BR — CNAE, CFOP e NCM.
 *
 * Saíram do core porque são classificação fiscal brasileira: só sistemas
 * fiscais precisam deles (ADR-0019). Nenhum dos três tinha consumidor fora do
 * próprio CRUD, o que fez desta a extração mais barata do repositório — e por
 * isso ela serve de piloto do mecanismo.
 */
final class FiscalBrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fiscal-br.php', 'fiscal-br');

        // Registrado no register() para que o callback exista antes do carregamento
        // de routes/admin.php — o require roda DENTRO do grupo admin do core, então
        // as rotas do módulo herdam o prefixo /admin, o name "admin." e o middleware.
        //
        // require, e não loadRoutesFrom(): o callback fica num registry estático que
        // sobrevive à troca de instância de app entre testes, e loadRoutesFrom() lê
        // $this->app — que, vindo de uma instância já descartada, estoura
        // "Target class [files] does not exist".
        ModuleRegistry::routes(function (): void {
            $rotas = __DIR__ . '/../routes/admin.php';

            if (is_file($rotas)) {
                require $rotas;
            }
        });

        // Catálogos mantidos por CSV: entram no db:seed E na lista do
        // `referencia:sync`, que é o passo de deploy.
        ModuleRegistry::catalogoDeReferencia('cnaes', Database\Seeders\CnaeSeeder::class);
        ModuleRegistry::catalogoDeReferencia('cfops', Database\Seeders\CfopSeeder::class);
        ModuleRegistry::catalogoDeReferencia('ncms', Database\Seeders\NcmSeeder::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'fiscal-br');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/fiscal-br.php' => config_path('fiscal-br.php'),
        ], 'fiscal-br-config');

        ModuleRegistry::permissoes(
            (string) config('fiscal-br.modulo_acesso', 'tabelas_auxiliares'),
            (array) config('fiscal-br.permissoes', []),
        );
        ModuleRegistry::itensDeMenu(
            (string) config('fiscal-br.secao_menu', 'tabelas-auxiliares'),
            (array) config('fiscal-br.menu', []),
        );

        \Livewire\Livewire::component('fiscal-br.cnaes.index', Livewire\IndexCnae::class);
        \Livewire\Livewire::component('fiscal-br.cnaes.form', Livewire\FormCnae::class);
        \Livewire\Livewire::component('fiscal-br.cnaes.cnae-table', Livewire\CnaeTable::class);
        \Livewire\Livewire::component('fiscal-br.cfops.index', Livewire\IndexCfop::class);
        \Livewire\Livewire::component('fiscal-br.cfops.form', Livewire\FormCfop::class);
        \Livewire\Livewire::component('fiscal-br.cfops.cfop-table', Livewire\CfopTable::class);
        \Livewire\Livewire::component('fiscal-br.ncms.index', Livewire\IndexNcm::class);
        \Livewire\Livewire::component('fiscal-br.ncms.form', Livewire\FormNcm::class);
        \Livewire\Livewire::component('fiscal-br.ncms.ncm-table', Livewire\NcmTable::class);

        Gate::policy(Models\Cnae::class, Policies\CnaePolicy::class);
        Gate::policy(Models\Cfop::class, Policies\CfopPolicy::class);
        Gate::policy(Models\Ncm::class, Policies\NcmPolicy::class);
    }
}
