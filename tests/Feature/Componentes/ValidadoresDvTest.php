<?php

declare(strict_types=1);

use App\Rules\Cnpj;
use App\Rules\TituloEleitor;
use HT2ML\Core\Rules\Cpf;
use HT2ML\Core\Rules\Pis;
use Illuminate\Contracts\Validation\ValidationRule;

/*
 * Metade PHP do contrato de dígito verificador. A outra metade
 * (tests/Browser/Admin/ValidadoresDvTest.php) roda ESTE MESMO fixture contra o
 * resources/js/admin/validators.js, no browser.
 *
 * O DV é um checksum público e imutável — duplicá-lo no cliente não é duplicar regra de
 * negócio, é evitar um round-trip para dizer que 111.111.111-11 não existe. O fixture
 * compartilhado é o que impede as duas implementações de divergirem em silêncio: mexeu
 * num dos lados sem mexer no outro, um dos dois testes fica vermelho.
 */

/** @return array<string, array{validos: list<string>, invalidos: list<string>}> */
function fixtureDocumentosDv(): array
{
    /** @var array<string, array{validos: list<string>, invalidos: list<string>}> $dados */
    $dados = json_decode(
        (string) file_get_contents(base_path('tests/Fixtures/documentos-dv.json')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    return $dados;
}

function documentoReprovado(ValidationRule $regra, string $valor): bool
{
    $falhou = false;

    $regra->validate('documento', $valor, function () use (&$falhou): void {
        $falhou = true;
    });

    return $falhou;
}

it('aprova todos os documentos válidos do fixture', function () {
    $fixture = fixtureDocumentosDv();

    $regras = ['cpf' => new Cpf, 'cnpj' => new Cnpj, 'pis' => new Pis, 'titulo_eleitor' => new TituloEleitor];

    foreach ($regras as $tipo => $regra) {
        foreach ($fixture[$tipo]['validos'] as $valor) {
            expect(documentoReprovado($regra, $valor))
                ->toBeFalse("O {$tipo} {$valor} deveria ser aceito pela regra do servidor.");
        }
    }
});

it('reprova todos os documentos inválidos do fixture', function () {
    $fixture = fixtureDocumentosDv();

    $regras = ['cpf' => new Cpf, 'cnpj' => new Cnpj, 'pis' => new Pis, 'titulo_eleitor' => new TituloEleitor];

    foreach ($regras as $tipo => $regra) {
        foreach ($fixture[$tipo]['invalidos'] as $valor) {
            expect(documentoReprovado($regra, $valor))
                ->toBeTrue("O {$tipo} {$valor} deveria ser recusado pela regra do servidor.");
        }
    }
});
