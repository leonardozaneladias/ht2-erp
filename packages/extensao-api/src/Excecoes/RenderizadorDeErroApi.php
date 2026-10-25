<?php

declare(strict_types=1);

namespace HT2ML\Api\Excecoes;

use HT2ML\Api\Http\Middleware\DefinirIdioma;
use HT2ML\Api\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * O envelope de erro da API: toda exceção sob `api.prefixo` sai como
 * `{success: false, message, data: {}, errors}`, no idioma pedido.
 *
 * Registrado pelo ApiServiceProvider no ExceptionHandler, para que o produto
 * não precise copiar 70 linhas para o bootstrap/app.php. Fora do prefixo devolve
 * null e não toca na exceção: o painel e o Livewire seguem o caminho normal.
 *
 * Atenção ao que o Laravel faz ANTES de chegar aqui (Handler::prepareException):
 * `ModelNotFoundException` já virou `NotFoundHttpException`, e
 * `AuthorizationException` já virou `AccessDeniedHttpException`. Um braço para
 * `AuthorizationException` nunca casaria — o 403 cairia no genérico com a
 * mensagem do framework em inglês ("This action is unauthorized.").
 */
final class RenderizadorDeErroApi
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is((string) config('api.prefixo', 'api/*'))) {
            return null;
        }

        // 405 e 404 de rota inexistente nascem antes do grupo `api`, onde o
        // DefinirIdioma roda: sem isto sairiam no idioma default.
        $idioma = DefinirIdioma::idiomaDoHeader($request);

        if ($idioma !== null) {
            app()->setLocale($idioma);
        }

        // A ORDEM DOS BRAÇOS É REGRA: NotFound, AccessDenied, Throttle e
        // MethodNotAllowed são todos HttpExceptionInterface, e o braço genérico
        // precisa vir por último ou engoliria os específicos.
        $resposta = match (true) {
            $e instanceof ValidationException => ApiResponse::erro($e->getMessage(), 422, $e->errors()),
            $e instanceof AuthenticationException => ApiResponse::erro(__('api::mensagens.nao_autenticado'), 401),
            $e instanceof AccessDeniedHttpException => ApiResponse::erro(__('api::mensagens.nao_autorizado'), 403),
            $e instanceof NotFoundHttpException => ApiResponse::erro(__('api::mensagens.nao_encontrado'), 404),
            $e instanceof ThrottleRequestsException => ApiResponse::erro(__('api::mensagens.muitas_tentativas'), 429),
            // A mensagem que o Symfony põe no 405 é feita para log: sempre em
            // inglês, e enumerando os métodos aceitos daquele endereço.
            $e instanceof MethodNotAllowedHttpException => ApiResponse::erro(__('api::mensagens.metodo_nao_permitido'), 405),
            // O canal do `abort(<status>, '<mensagem do produto>')`: aqui a
            // mensagem foi escrita por nós, então ela vale.
            $e instanceof HttpExceptionInterface => ApiResponse::erro(
                $e->getMessage() !== '' ? $e->getMessage() : __('api::mensagens.erro_servidor'),
                $e->getStatusCode(),
            ),
            default => null,
        };

        if ($resposta !== null) {
            return $resposta;
        }

        // Qualquer outra coisa é erro nosso. Em debug, a página de exceção do
        // Laravel continua útil; em produção o app recebe o envelope de sempre,
        // e não um `{"message": ...}` solto que ele não sabe ler. Sem report():
        // o framework já reporta antes de renderizar.
        if ((bool) config('app.debug')) {
            return null;
        }

        return ApiResponse::erro(__('api::mensagens.erro_servidor'), 500);
    }
}
