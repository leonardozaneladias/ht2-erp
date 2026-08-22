<?php

declare(strict_types=1);

namespace HT2ERP\Rh;

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

        $this->contribuirPermissoes();
        $this->contribuirMenu();

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

    /**
     * Agrega as permissões do módulo ao catálogo de acesso para que access:sync,
     * a matriz de acesso e o seeder as enxerguem.
     */
    private function contribuirPermissoes(): void
    {
        /** @var array<string, array{label: string, descricao: string}> $permissoes */
        $permissoes = (array) config('rh.permissoes', []);

        if ($permissoes === []) {
            return;
        }

        // array_replace_recursive, e não array_merge_recursive: com `config:cache`
        // a config é fotografada JÁ mesclada, então este boot roda de novo sobre
        // o próprio resultado. O merge recursivo funde valores iguais e transforma
        // 'label' => 'X' em 'label' => ['X', 'X']; o replace é idempotente.
        config(['access.modules' => array_replace_recursive(
            (array) config('access.modules', []),
            ['negocio' => $permissoes],
        )]);
    }

    /**
     * Agrega os itens de menu do módulo à seção "Negócio" da sidebar.
     */
    private function contribuirMenu(): void
    {
        /** @var list<array<string, mixed>> $itens */
        $itens = (array) config('rh.menu', []);

        if ($itens === []) {
            return;
        }

        /** @var list<array<string, mixed>> $menu */
        $menu = (array) config('admin-menu', []);

        foreach ($menu as $i => $secao) {
            if (($secao['key'] ?? null) === 'negocio') {
                // Ignora o que já está na seção: com `config:cache` os itens do
                // pacote já foram gravados no cache, e sem este filtro eles
                // entrariam uma segunda vez a cada boot.
                $presentes = array_column($secao['items'] ?? [], 'key');
                $novos = array_values(array_filter(
                    $itens,
                    static fn (array $item): bool => ! in_array($item['key'] ?? null, $presentes, true),
                ));

                $menu[$i]['items'] = [...($secao['items'] ?? []), ...$novos];
            }
        }

        config(['admin-menu' => $menu]);
    }
}
