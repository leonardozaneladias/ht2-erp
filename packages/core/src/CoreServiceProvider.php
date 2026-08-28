<?php

declare(strict_types=1);

namespace HT2ML\Core;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
    /** Configs que o núcleo entrega ao app hospedeiro. */
    private const CONFIGS = ['access', 'admin-menu', 'branding', 'extensoes', 'settings'];

    public function register(): void
    {
        // As 5 configs do núcleo. mergeConfigFrom permite ao app hospedeiro
        // sobrescrever chaves publicando a sua própria versão, sem precisar
        // copiar o arquivo inteiro.
        foreach (self::CONFIGS as $nome) {
            $this->mergeConfigFrom(__DIR__ . "/../config/{$nome}.php", $nome);
        }
    }

    public function boot(): void
    {
        $this->publishes(
            collect(self::CONFIGS)
                ->mapWithKeys(fn (string $n): array => [__DIR__ . "/../config/{$n}.php" => config_path("{$n}.php")])
                ->all(),
            'core-config',
        );

        $this->registrarRotas();

        // As 43 migrations do núcleo. loadMigrationsFrom() as inclui no
        // `php artisan migrate` sem publicar nada — o app hospedeiro ganha as
        // tabelas do core só por instalar o pacote. A ordem continua sendo a do
        // timestamp no nome, somando os caminhos registrados, então migrations
        // do app e de extensões se intercalam corretamente.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

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
        $this->registrarComponentesLivewire();

        // Contribuições das extensões (permissões e itens de menu) aplicadas ao
        // config(). Mora aqui, e não no AppServiceProvider do produto, porque um
        // produto que perdesse aquela linha perderia permissões e menu de TODAS
        // as extensões — em silêncio, sem erro, sem tela quebrada. Nada no
        // repositório impedia isso.
        //
        // booted() e não boot() direto: este provider também é de pacote e pode
        // bootar ANTES dos providers das extensões — a ordem dentro do grupo do
        // PackageManifest não é controlável. O booted() dispara depois que todos
        // bootaram, que é exatamente a garantia que o AppServiceProvider obtinha
        // por acidente de posição na lista.
        $this->app->booted(static function (): void {
            Support\Modules\ModuleRegistry::aplicarContribuicoes();
        });

        $this->app->booted(fn () => $this->registrarViewsDoPowerGrid());
    }

    /**
     * Insere as views do PowerGrid sobrescritas pelo núcleo entre as do produto
     * e as do vendor.
     *
     * Quatro views do PowerGrid são reescritas para trocar o <select> nativo
     * pelo x-shared.combobox (filtros pesquisáveis e multi-seleção). Elas viviam
     * em resources/views/vendor/ do app — e por isso já foram COPIADAS para o
     * EduConecta, byte a byte. O terceiro produto esqueceria de copiá-las e
     * perderia os filtros pesquisáveis em silêncio; e um `composer update` do
     * PowerGrid num dos repositórios mudaria o comportamento só ali.
     *
     * A ordem importa e não é obtida por addNamespace()/prependNamespace():
     *  - addNamespace() põe o núcleo DEPOIS do vendor → o override nunca vence;
     *  - prependNamespace() põe o núcleo ANTES do produto → o produto perde a
     *    capacidade de restilizar um filtro sem herdar a manutenção dos quatro.
     * Por isso a lista é reconstruída à mão: produto → núcleo → vendor.
     *
     * Roda em booted() porque o provider do PowerGrid precisa ter registrado o
     * namespace antes; resolver o factory aqui dispara os callAfterResolving
     * pendentes, então os hints já estão completos.
     */
    private function registrarViewsDoPowerGrid(): void
    {
        $finder = View::getFinder();

        if (! $finder instanceof \Illuminate\View\FileViewFinder) {
            return;
        }

        $hints = $finder->getHints()['livewire-powergrid'] ?? [];

        if ($hints === []) {
            return;   // PowerGrid ausente: nada a sobrescrever.
        }

        // "Do produto" = sob um dos view.paths configurados, que é exatamente o
        // critério do ServiceProvider::loadViewsFrom() ao publicar em
        // resources/views/vendor/<namespace>.
        $caminhosDoProduto = (array) config('view.paths', []);

        $doProduto = array_filter(
            $hints,
            static fn (string $hint): bool => (bool) array_filter(
                $caminhosDoProduto,
                static fn (string $base): bool => str_starts_with($hint, rtrim((string) $base, '/') . '/'),
            ),
        );

        $doVendor = array_diff($hints, $doProduto);

        $finder->replaceNamespace('livewire-powergrid', [
            ...array_values($doProduto),
            __DIR__ . '/../resources/views/livewire-powergrid',
            ...array_values($doVendor),
        ]);
    }

    /**
     * As rotas do admin, dentro do grupo `web`.
     *
     * Saíram de bootstrap/app.php para cá: um app que instala ht2ml/core ganha
     * o /admin inteiro sem precisar declarar nada. O guard de cache é o mesmo
     * que o loadRoutesFrom() do Laravel faz — com as rotas em cache, o arquivo
     * não deve ser lido de novo.
     */
    private function registrarRotas(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(__DIR__ . '/../routes/admin.php');
    }

    /**
     * A quarta descoberta automática que morre num pacote — e a única que dá
     * para restaurar em vez de substituir.
     *
     * O Livewire encontra componentes sozinho em app/Livewire, derivando o
     * alias do namespace: App\Livewire\Admin\Auditoria\HistoricoRegistro vira
     * `admin.auditoria.historico-registro`. Dentro de um pacote não há
     * descoberta, e sem isto toda tela do admin morre com
     * "Unable to find component".
     *
     * addLocation() ensina a mesma convenção para o namespace do pacote, então
     * os aliases seguem idênticos e nenhum blade consumidor muda. A alternativa
     * — 64 chamadas Livewire::component() — funcionaria igual, mas exigiria
     * lembrar de acrescentar uma linha a cada componente novo.
     *
     * Ver tests/Feature/Core/ComponentesLivewireDoCoreTest.php.
     */
    private function registrarComponentesLivewire(): void
    {
        Livewire::addLocation(classNamespace: 'HT2ML\\Core\\Livewire');
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
            Console\Commands\DoutorCommand::class,
            Console\Commands\MakeExtensaoCommand::class,
            Console\Commands\MakeModuloCommand::class,
            Console\Commands\MakeRecursoCommand::class,
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
