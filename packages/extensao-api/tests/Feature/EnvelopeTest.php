<?php

declare(strict_types=1);

use HT2ML\Api\Support\ApiResponse;
use HT2ML\Api\Support\PaginacaoApi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

it('sucesso devolve o envelope com data vazio como objeto, não null', function (): void {
    $json = ApiResponse::sucesso()->getData(true);

    expect($json)->toBe(['success' => true, 'message' => '', 'data' => [], 'errors' => []])
        // getData(true) vira array; no JSON bruto, data é {} e não [] nem null.
        ->and(ApiResponse::sucesso()->getContent())->toContain('"data":{}');
});

it('erro devolve success false, o status pedido e os erros', function (): void {
    $resposta = ApiResponse::erro('Falhou.', 409, ['campo' => ['inválido']]);

    expect($resposta->getStatusCode())->toBe(409)
        ->and($resposta->getData(true))->toBe([
            'success' => false,
            'message' => 'Falhou.',
            'data' => [],
            'errors' => ['campo' => ['inválido']],
        ]);
});

it('pagina põe os itens sob a chave e o contador em data.meta', function (): void {
    $paginador = new LengthAwarePaginator(['a', 'b'], total: 7, perPage: 2, currentPage: 3);

    expect(ApiResponse::pagina('itens', ['a', 'b'], $paginador)->getData(true)['data'])->toBe([
        'itens' => ['a', 'b'],
        'meta' => ['pagina_atual' => 3, 'por_pagina' => 2, 'total' => 7, 'ultima_pagina' => 4],
    ]);
});

it('por_pagina tem clamp dos dois lados e ignora lixo', function (string|int|null $pedido, int $esperado): void {
    $request = Request::create('/', 'GET', $pedido === null ? [] : ['por_pagina' => $pedido]);

    expect(PaginacaoApi::porPagina($request))->toBe($esperado);
})->with([
    'ausente' => [null, 25],
    'normal' => ['10', 10],
    'acima do teto' => ['500', 100],
    'zero' => ['0', 1],
    'negativo' => ['-1', 1],
    'não numérico' => ['abc', 25],
]);

it('a página nunca é menor que 1, para quem monta o paginador à mão', function (string $pedido, int $esperado): void {
    expect(PaginacaoApi::pagina(Request::create('/', 'GET', ['page' => $pedido])))->toBe($esperado);
})->with([
    ['3', 3],
    ['0', 1],
    ['-5', 1],
    ['xyz', 1],
]);

it('padrão e teto vêm da config', function (): void {
    config(['api.paginacao.padrao' => 10, 'api.paginacao.teto' => 30]);

    expect(PaginacaoApi::porPagina(Request::create('/')))->toBe(10)
        ->and(PaginacaoApi::porPagina(Request::create('/', 'GET', ['por_pagina' => '99'])))->toBe(30);
});
