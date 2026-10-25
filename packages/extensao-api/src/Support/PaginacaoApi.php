<?php

declare(strict_types=1);

namespace HT2ML\Api\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * O tamanho de página da API, num lugar só.
 *
 * O clamp é dos dois lados: `min($pedido, 50)` sozinho deixa `?por_pagina=0` e
 * `?por_pagina=-1` passarem, e nenhum dos dois descreve página nenhuma. Valor
 * não numérico cai no padrão em vez de virar `0` pelo cast.
 */
final class PaginacaoApi
{
    public static function padrao(): int
    {
        return max(1, (int) config('api.paginacao.padrao', 25));
    }

    public static function teto(): int
    {
        return max(self::padrao(), (int) config('api.paginacao.teto', 100));
    }

    public static function porPagina(Request $request): int
    {
        $bruto = $request->query('por_pagina');

        if ((! is_string($bruto) && ! is_int($bruto)) || ! is_numeric($bruto)) {
            return self::padrao();
        }

        return max(1, min((int) $bruto, self::teto()));
    }

    /**
     * A página pedida, **nunca menor que 1**.
     *
     * Rotas paginadas pelo Eloquent não precisam disto — o Laravel já normaliza
     * `?page`. Quem precisa é quem monta o `LengthAwarePaginator` à mão (um feed
     * que mescla duas tabelas, por exemplo): ali a página vira offset de
     * `array_slice`, e offset **negativo** não é erro em PHP — é uma fatia
     * contada a partir do fim. Sem o clamp, `?page=0` devolveria os últimos
     * itens rotulados como página 1.
     */
    public static function pagina(Request $request): int
    {
        $bruto = $request->query('page');

        if ((! is_string($bruto) && ! is_int($bruto)) || ! is_numeric($bruto)) {
            return 1;
        }

        return max(1, (int) $bruto);
    }

    /**
     * @param  LengthAwarePaginator<int, covariant mixed>  $paginador
     * @return array{pagina_atual: int, por_pagina: int, total: int, ultima_pagina: int}
     */
    public static function meta(LengthAwarePaginator $paginador): array
    {
        return [
            'pagina_atual' => $paginador->currentPage(),
            'por_pagina' => $paginador->perPage(),
            'total' => $paginador->total(),
            'ultima_pagina' => $paginador->lastPage(),
        ];
    }
}
