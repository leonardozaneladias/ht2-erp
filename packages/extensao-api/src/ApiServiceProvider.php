<?php

declare(strict_types=1);

namespace HT2ML\Api;

use Dedoc\Scramble\Scramble;
use HT2ML\Api\Excecoes\RenderizadorDeErroApi;
use HT2ML\Api\Http\Middleware\DefinirIdioma;
use HT2ML\Api\Http\Middleware\ForcarRespostaJson;
use HT2ML\Api\Http\Middleware\GarantirPrincipalAtivo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * A camada comum de toda API REST de um produto HT2ML.
 *
 * Instalar o pacote basta: o grupo `api` passa a negociar JSON e resolver o
 * idioma, toda exceção sob /api sai no envelope, e o limiter `api` existe. As
 * rotas entram pelo canal do core (`ModuloBuilder::rotasDeApi()` ou
 * EscopoDeRota::Api); quem é o usuário da API — e qual guard o autentica — é
 * decisão do módulo.
 *
 * É extensão e não core (ADR-0020): um produto só de painel não tem API, e não
 * deveria carregar o Sanctum por isso.
 */
final class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api.php', 'api');

        // No register(), não no boot(): ver registrarEnvelopeDeErro().
        $this->registrarEnvelopeDeErro();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'api');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([__DIR__ . '/../config/api.php' => config_path('api.php')], 'api-config');

        $this->registrarMiddlewares();
        $this->registrarLimiter();

        // O OpenAPI descreve o envelope de erro de verdade, não o do Laravel.
        Scramble::afterOpenApiGenerated(new OpenApi\DocumentarEnvelope);

        // O Scramble não lê o corpo de classes em vendor/ — e num produto é lá
        // que estas moram. Sem isto, todo `return ApiResponse::sucesso(...)`
        // vira `{"type": "object"}` no OpenAPI, e os schemas dos Resources
        // somem (medido no EduConecta: 26 schemas viraram 9). No monorepo não
        // aparece: o path repository é symlink, e o caminho real não tem vendor/.
        Scramble::infer()->configure()->buildDefinitionsUsingAstFor([
            Support\ApiResponse::class,
            Support\PaginacaoApi::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([Console\OpenApiCommand::class]);
        }
    }

    /** Quem está chamando, para fins de limite: tipo + id do usuário, ou o IP. */
    public static function chaveDoChamador(Request $request): string
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return 'ip:' . ($request->ip() ?? 'desconhecido');
        }

        $tipo = method_exists($usuario, 'getMorphClass') ? $usuario->getMorphClass() : $usuario::class;

        return $tipo . ':' . $usuario->getAuthIdentifier();
    }

    private function registrarMiddlewares(): void
    {
        // Pelo Kernel, não pelo Router: o Kernel HTTP reescreve os grupos do
        // roteador a partir da própria lista (syncMiddlewareToRouter), e um
        // prepend feito só no Router some na primeira requisição.
        //
        // prepend, na ordem inversa da execução: o último prependido roda
        // primeiro. ForcarRespostaJson precisa vir antes de tudo — inclusive do
        // Authenticate, que tem prioridade no framework e é puxado para cima
        // dos outros middlewares de prioridade, mas não acima destes.
        $this->callAfterResolving(HttpKernel::class, static function (HttpKernel $kernel): void {
            if (! $kernel instanceof Kernel) {
                return;
            }

            $kernel->prependMiddlewareToGroup('api', DefinirIdioma::class);
            $kernel->prependMiddlewareToGroup('api', ForcarRespostaJson::class);
        });

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('api.principal-ativo', GarantirPrincipalAtivo::class);
    }

    /**
     * O limiter `api`, que o skeleton pendura no grupo.
     *
     * A chave inclui o TIPO do usuário, não só o id: num produto com dois
     * modelos autenticáveis (um admin e um cliente), o usuário 7 de cada um
     * cairia no mesmo balde — e um gastaria o limite do outro.
     *
     * Fora de qualquer guard de route:cache: o cache guarda o nome do limiter,
     * não o limiter (ver CoreServiceProvider::registrarRotas()). O produto que
     * registrar o próprio RateLimiter::for('api') depois deste vence.
     */
    private function registrarLimiter(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute((int) config('api.limite_por_minuto', 120))
            ->by(self::chaveDoChamador($request)));
    }

    /**
     * O envelope entra no Handler do Laravel, pelo tipo concreto, e cedo.
     *
     * Pelo tipo concreto (Handler) e não pelo contrato (ExceptionHandler): fora
     * do HTTP clássico o contrato pode chegar decorado. O Collision, em
     * console, devolve um adaptador que repassa render() ao Handler de dentro;
     * esperar o contrato e checar `instanceof Handler` via o adaptador e não
     * registrava nada — e um kernel HTTP rodando em console (Octane, um
     * script) respondia o 404 do Laravel, em HTML, sob /api.
     *
     * Cedo (register) porque o Handler pode ser resolvido antes do boot desta
     * extensão; um afterResolving registrado tarde não dispararia mais. O
     * resolved() cobre o caso de ele já existir sem decorador.
     */
    private function registrarEnvelopeDeErro(): void
    {
        $registrar = static function (Handler $handler): void {
            $handler->renderable(new RenderizadorDeErroApi);
        };

        $this->app->afterResolving(Handler::class, $registrar);

        if ($this->app->resolved(ExceptionHandler::class)) {
            $handler = $this->app->make(ExceptionHandler::class);

            if ($handler instanceof Handler) {
                $registrar($handler);
            }
        }
    }
}
