<?php

declare(strict_types=1);

use HT2ML\Core\Imports\BaseImport;
use Maatwebsite\Excel\Validators\Failure;

test('BaseImport inicia com zero linhas importadas', function () {
    $import = new class extends BaseImport
    {
        protected function regrasPorColuna(): array
        {
            return [];
        }

        protected function mensagensValidacao(): array
        {
            return [];
        }

        protected function importarLinha(array $row): void {}
    };

    expect($import->getLinhasImportadas())->toBe(0);
});

test('BaseImport inicia sem erros', function () {
    $import = new class extends BaseImport
    {
        protected function regrasPorColuna(): array
        {
            return [];
        }

        protected function mensagensValidacao(): array
        {
            return [];
        }

        protected function importarLinha(array $row): void {}
    };

    expect($import->getErros())->toBeEmpty();
});

test('BaseImport coleta erros de validação via onFailure', function () {
    $import = new class extends BaseImport
    {
        protected function regrasPorColuna(): array
        {
            return [];
        }

        protected function mensagensValidacao(): array
        {
            return [];
        }

        protected function importarLinha(array $row): void {}
    };

    $failure = new Failure(3, 'cpf', ['CPF inválido'], ['cpf' => '000']);
    $import->onFailure($failure);

    $erros = $import->getErros();

    expect($erros)->toHaveCount(1)
        ->and($erros[0]['linha'])->toBe(3)
        ->and($erros[0]['campo'])->toBe('cpf')
        ->and($erros[0]['mensagem'])->toBe('CPF inválido');
});
