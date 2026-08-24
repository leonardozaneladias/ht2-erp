<?php

declare(strict_types=1);

namespace HT2ML\Core;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Entrega o design system e as views do núcleo.
 *
 * O ponto não-óbvio é o anonymousComponentPath() SEM prefixo: com ele, um
 * `<x-shared.button />` escrito no app hospedeiro resolve para o blade que mora
 * dentro do pacote, sem que nenhum consumidor mude uma linha. É o que torna o
 * design system empacotável — a alternativa (namespace `core::`) obrigaria a
 * reescrever todo blade que usa um componente.
 *
 * O app hospedeiro continua vencendo: componentes em resources/views/components
 * têm precedência sobre os do pacote, então dá para sobrescrever um por vez.
 */
final class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components');

        // Views nomeadas do núcleo, sob o namespace `core::`, para quem quiser
        // ser explícito: view('core::livewire.admin.partials.grid-acoes').
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'core');

        // E também SEM prefixo, pelo mesmo motivo do anonymousComponentPath: o
        // #[Layout('components.admin.layout')] do Livewire resolve por caminho
        // de view, não por componente — registrar só os componentes deixaria
        // todo layout de página quebrado. A localização entra DEPOIS da do app,
        // então uma view homônima em resources/views continua vencendo.
        View::addLocation(__DIR__ . '/../resources/views');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/core'),
        ], 'core-views');

        $this->registrarPolicies();
        $this->registrarComandos();
        $this->registrarListeners();
    }

    /**
     * Registro EXPLÍCITO, mesma história das policies e dos comandos.
     *
     * O Laravel descobre listeners sozinho em app/Listeners. Dentro de um
     * pacote não há descoberta: sem esta chamada o histórico de login
     * simplesmente para de ser gravado, sem erro nenhum — e um relatório de
     * segurança fica vazio sem que ninguém perceba.
     *
     * Ver tests/Feature/Core/ListenersDoCoreTest.php.
     */
    private function registrarListeners(): void
    {
        Event::listen(Login::class, Listeners\RegistrarLoginAdmin::class);
    }

    /**
     * Registro EXPLÍCITO, pelo mesmo motivo das policies.
     *
     * O Laravel descobre comandos sozinho em app/Console/Commands. Dentro de um
     * pacote não há descoberta: sem esta chamada os cinco comandos do núcleo
     * — access:sync, access:expirar, referencia:sync, make:modulo e
     * make:extensao — simplesmente somem do artisan, sem erro nenhum.
     *
     * Ver tests/Feature/Core/ComandosDoCoreTest.php, que falha se algum sumir.
     */
    private function registrarComandos(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\Commands\AccessExpirarCommand::class,
            Console\Commands\AccessSyncCommand::class,
            Console\Commands\MakeExtensaoCommand::class,
            Console\Commands\MakeModuloCommand::class,
            Console\Commands\ReferenciaSyncCommand::class,
        ]);
    }

    /**
     * Registro EXPLÍCITO, e é o ponto todo.
     *
     * A descoberta por convenção do Laravel mapeia App\Models\X para
     * App\Policies\XPolicy. Quando Empresa saiu de App\Models para o pacote, a
     * convenção passou a procurar HT2ML\Core\Policies\EmpresaPolicy — que não
     * existia — e a EmpresaPolicy simplesmente deixou de ser aplicada, sem erro
     * nenhum. Um controle de autorização desligado em silêncio, que um único
     * teste pegou.
     *
     * Policy de model do core se declara aqui. Ver
     * tests/Feature/Core/PoliciesDoCoreTest.php, que falha se alguma sumir.
     */
    private function registrarPolicies(): void
    {
        Gate::policy(Models\AdminUser::class, Policies\AdminUserPolicy::class);
        Gate::policy(Models\Empresa::class, Policies\EmpresaPolicy::class);
        Gate::policy(Models\PermissionGrant::class, Policies\PermissionGrantPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, Policies\RolePolicy::class);
    }
}
