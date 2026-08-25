<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo;

use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Extensão de demonstração — a vitrine viva do design system e do make:modulo.
 *
 * Ela existe para provar o caminho inverso: um produto REMOVE esta extensão.
 * Antes o demo vivia no app com permissões e menu declarados na config do
 * núcleo, atrás de um env('EXEMPLO_DEMO') — o pacote da plataforma descrevendo
 * um módulo que não era dele. Agora quem não quer, não instala.
 */
final class ExemploDemoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/exemplo-demo.php', 'exemplo-demo');

        // Recursos NOVOS deste pacote, pelo builder. Os antigos continuam nas
        // chamadas diretas do boot(): as chaves deles já estão em uso.
        ModuleRegistry::modulo('exemplo-demo')
            ->label('Exemplo Demo')
            ->deConfig('exemplo-demo');

        ModuleRegistry::routes(function (): void {
            $rotas = __DIR__ . '/../routes/admin.php';

            if (is_file($rotas)) {
                require $rotas;
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'exemplo-demo');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        ModuleRegistry::permissoes(
            (string) config('exemplo-demo.modulo_acesso', 'negocio'),
            (array) config('exemplo-demo.permissoes', []),
        );
        ModuleRegistry::itensDeMenu(
            (string) config('exemplo-demo.secao_menu', 'negocio'),
            (array) config('exemplo-demo.menu', []),
        );

        // Fachada qualificada e sem `use Livewire\Livewire`: o import sequestraria
        // o primeiro segmento e Livewire\Exemplos\… viraria Livewire\Livewire\…
        \Livewire\Livewire::addLocation(classNamespace: 'HT2ML\\ExemploDemo\\Livewire');

        Gate::policy(Models\Exemplo::class, Policies\ExemploPolicy::class);
    }
}
