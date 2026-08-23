<?php

declare(strict_types=1);

namespace HT2ML\Core;

use Illuminate\Support\Facades\Blade;
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

        // Views nomeadas do núcleo, sob o namespace `core::`. Diferente dos
        // componentes, aqui o prefixo é desejável: view() recebe string e não
        // há ambiguidade a evitar.
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'core');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/core'),
        ], 'core-views');
    }
}
