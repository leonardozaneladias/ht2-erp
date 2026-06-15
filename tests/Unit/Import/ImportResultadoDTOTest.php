<?php

declare(strict_types=1);

use App\DTOs\Admin\ImportResultadoDTO;

test('ImportResultadoDTO armazena dados corretamente', function () {
    $dto = new ImportResultadoDTO(
        totalLinhas: 10,
        linhasImportadas: 8,
        linhasComErro: 2,
        erros: [['linha' => 2, 'campo' => 'cpf', 'mensagem' => 'CPF inválido']],
    );

    expect($dto->totalLinhas)->toBe(10)
        ->and($dto->linhasImportadas)->toBe(8)
        ->and($dto->linhasComErro)->toBe(2)
        ->and($dto->erros)->toHaveCount(1);
});

test('ImportResultadoDTO com zero erros', function () {
    $dto = new ImportResultadoDTO(
        totalLinhas: 5,
        linhasImportadas: 5,
        linhasComErro: 0,
        erros: [],
    );

    expect($dto->erros)->toBeEmpty()
        ->and($dto->linhasComErro)->toBe(0);
});
