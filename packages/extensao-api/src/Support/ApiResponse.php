<?php

declare(strict_types=1);

namespace HT2ML\Api\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * O envelope de toda resposta da API:
 * `{ "success": bool, "message": string, "data": {}, "errors": [] }`.
 *
 * Único ponto que produz esse formato — um endpoint novo nunca monta o array à
 * mão. `data` vazio sai como objeto (`{}`), não `null`: um cliente tipado que
 * lê `data.alguma_coisa` não precisa tratar dois formatos.
 */
final class ApiResponse
{
    /**
     * @param  mixed  $data  array, Arrayable (Resource/Collection) ou null
     */
    public static function sucesso(mixed $data = null, string $mensagem = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $mensagem,
            'data' => $data ?? (object) [],
            'errors' => [],
        ], $status);
    }

    /**
     * Lista paginada: `data.<chave>` com os itens da página e `data.meta` com o
     * contador. Endpoint de lista não escreve `meta` à mão.
     *
     * @param  LengthAwarePaginator<int, covariant mixed>  $paginador
     */
    public static function pagina(string $chave, mixed $itens, LengthAwarePaginator $paginador, string $mensagem = ''): JsonResponse
    {
        return self::sucesso([
            $chave => $itens,
            'meta' => PaginacaoApi::meta($paginador),
        ], $mensagem);
    }

    /**
     * @param  array<string, mixed>|list<string>  $errors
     */
    public static function erro(string $mensagem, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensagem,
            'data' => (object) [],
            'errors' => $errors,
        ], $status);
    }
}
