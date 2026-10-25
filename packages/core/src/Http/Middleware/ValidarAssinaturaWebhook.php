<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confere a assinatura HMAC-SHA256 de um webhook antes de deixá-lo passar.
 *
 *     Route::post('/pagamentos', ...)
 *         ->middleware('webhook.assinatura:financeiro.webhook_secret');
 *
 *     // header próprio do provedor:
 *         ->middleware('webhook.assinatura:pagamentos.segredo,X-Hub-Signature-256');
 *
 * O primeiro parâmetro é a CHAVE de config que guarda o segredo, nunca o
 * segredo: o segredo vem do .env, e a rota só sabe onde procurá-lo.
 *
 * Subiu do EduConecta, onde nasceu presa à config do módulo financeiro. O core
 * já é dono do canal de webhook (EscopoDeRota::Webhook), e um endpoint sem
 * autenticação é exatamente onde todo módulo precisa da mesma checagem.
 *
 * A assinatura é sobre o corpo BRUTO, não o array decodificado: reconstruir o
 * JSON a partir do payload parseado não reproduz byte a byte o que o provedor
 * assinou (ordem de chaves, espaçamento).
 */
final class ValidarAssinaturaWebhook
{
    public const HEADER_PADRAO = 'X-Webhook-Signature';

    public function handle(Request $request, Closure $next, string $chaveDoSegredo, string $header = self::HEADER_PADRAO): Response
    {
        $segredo = config($chaveDoSegredo);

        // Sem segredo configurado o endpoint recusa TUDO — não valida contra
        // vazio, que produziria um HMAC perfeitamente válido e uma checagem que
        // não checa nada. É 503 e não 401 porque o problema é do servidor, não
        // da requisição; e `report()` para o operador descobrir pelo log, não
        // por um pagamento que o sistema nunca registrou.
        if (! is_string($segredo) || $segredo === '') {
            report(new RuntimeException(sprintf(
                'O segredo do webhook (config "%s") não está configurado: %s está recusando todas as requisições.',
                $chaveDoSegredo,
                $request->path(),
            )));

            abort(503, __('Webhook indisponível.'));
        }

        $assinaturaRecebida = (string) $request->header($header, '');

        // Provedores como o GitHub prefixam o algoritmo (`sha256=<hex>`).
        if (str_starts_with($assinaturaRecebida, 'sha256=')) {
            $assinaturaRecebida = substr($assinaturaRecebida, 7);
        }

        $assinaturaEsperada = hash_hmac('sha256', $request->getContent(), $segredo);

        if ($assinaturaRecebida === '' || ! hash_equals($assinaturaEsperada, $assinaturaRecebida)) {
            abort(401, __('Assinatura inválida.'));
        }

        return $next($request);
    }
}
