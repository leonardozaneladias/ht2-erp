<?php

declare(strict_types=1);

namespace HT2ML\Ia;

use HT2ML\Ia\Contracts\AssistenteInterface;
use HT2ML\Ia\Contracts\ResumidorInterface;
use HT2ML\Ia\Contracts\TradutorInterface;
use HT2ML\Ia\Fakes\FakeAssistente;
use HT2ML\Ia\Fakes\FakeResumidor;
use HT2ML\Ia\Fakes\FakeTradutor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Contratos de IA com fakes ligados por padrão.
 *
 * Os fakes ficam no ar até o produto ligar um provedor real — trocando o
 * binding no próprio AppServiceProvider, que boota depois deste:
 *
 *     $this->app->bind(TradutorInterface::class, AnthropicTradutor::class);
 *
 * É extensão e não core (ADR-0020): nem todo produto usa IA. E contratos aqui,
 * e não dentro de cada módulo, porque um módulo (pacote) não pode importar de
 * App\ — cada um acabava declarando a própria interface e o próprio fake.
 */
final class IaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ia.php', 'ia');

        // bindIf: se o produto já ligou um provedor real antes, ele vence.
        $this->app->bindIf(TradutorInterface::class, FakeTradutor::class);
        $this->app->bindIf(ResumidorInterface::class, FakeResumidor::class);
        $this->app->bindIf(AssistenteInterface::class, FakeAssistente::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([__DIR__ . '/../config/ia.php' => config_path('ia.php')], 'ia-config');

        // Limites por custo, para `throttle:ia` e `throttle:traducao` nas rotas
        // que chamam um provedor. Registrados a cada boot, fora de qualquer
        // guard de route:cache: o cache guarda o nome do limiter, não ele.
        foreach (['ia', 'traducao'] as $nome) {
            RateLimiter::for($nome, static fn (Request $request): Limit => Limit::perMinute((int) config("ia.limites.{$nome}", 10))
                ->by(self::chaveDoChamador($request)));
        }
    }

    /** Tipo + id do usuário, ou o IP: dois modelos autenticáveis com o mesmo id não dividem o balde. */
    public static function chaveDoChamador(Request $request): string
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return 'ip:' . ($request->ip() ?? 'desconhecido');
        }

        $tipo = method_exists($usuario, 'getMorphClass') ? $usuario->getMorphClass() : $usuario::class;

        return $tipo . ':' . $usuario->getAuthIdentifier();
    }
}
