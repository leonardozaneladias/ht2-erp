<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Healthcheck para monitoramento/load-balancer de cada instalação cliente.
 *
 * Complementa o `/up` nativo (que só confirma que a aplicação boota) verificando
 * a conectividade de banco e cache. Público e leve, retorna 200 quando saudável
 * e 503 quando algum check falha (para o orquestrador tirar a instância do pool).
 */
final class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checar(static fn (): bool => DB::connection()->getPdo() instanceof PDO),
            'cache' => $this->checar(static function (): bool {
                Cache::put('healthz', '1', 5);

                return Cache::get('healthz') === '1';
            }),
        ];

        $saudavel = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $saudavel ? 'ok' : 'degraded',
            'checks' => array_map(static fn (bool $ok): string => $ok ? 'ok' : 'fail', $checks),
        ], $saudavel ? 200 : 503);
    }

    /**
     * @param  callable(): bool  $teste
     */
    private function checar(callable $teste): bool
    {
        try {
            return $teste();
        } catch (Throwable) {
            return false;
        }
    }
}
