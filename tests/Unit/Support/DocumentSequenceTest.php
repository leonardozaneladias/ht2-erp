<?php

declare(strict_types=1);

use App\Enums\TipoDocumento;
use App\Models\DocumentSequence;
use App\Support\Documents\GeradorNumeroDocumento;
use HT2ML\Core\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarEmpresa(): Empresa
{
    return Empresa::factory()->create();
}

it('gera o primeiro número formatado conforme o template padrão', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $numero = $gerador->gerar($empresa->id, TipoDocumento::Matricula, 2025);

    expect($numero)->toBe('MAT-2025-000001');
});

it('incrementa sequencialmente para a mesma combinação empresa/tipo/ano', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $n1 = $gerador->gerar($empresa->id, TipoDocumento::Protocolo, 2025);
    $n2 = $gerador->gerar($empresa->id, TipoDocumento::Protocolo, 2025);
    $n3 = $gerador->gerar($empresa->id, TipoDocumento::Protocolo, 2025);

    expect($n1)->toBe('PRT-2025-000001')
        ->and($n2)->toBe('PRT-2025-000002')
        ->and($n3)->toBe('PRT-2025-000003');
});

it('mantém sequências independentes por tipo na mesma empresa e ano', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $mat = $gerador->gerar($empresa->id, TipoDocumento::Matricula, 2025);
    $ctr = $gerador->gerar($empresa->id, TipoDocumento::Contrato, 2025);

    expect($mat)->toBe('MAT-2025-000001')
        ->and($ctr)->toBe('CTR-2025-000001');
});

it('reinicia a sequência para um novo ano', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $gerador->gerar($empresa->id, TipoDocumento::Recibo, 2024);
    $gerador->gerar($empresa->id, TipoDocumento::Recibo, 2024);

    $novoAno = $gerador->gerar($empresa->id, TipoDocumento::Recibo, 2025);

    expect($novoAno)->toBe('RCB-2025-000001');
});

it('isola sequências entre empresas diferentes', function (): void {
    $e1 = criarEmpresa();
    $e2 = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $gerador->gerar($e1->id, TipoDocumento::Protocolo, 2025);
    $gerador->gerar($e1->id, TipoDocumento::Protocolo, 2025);

    $primeiroE2 = $gerador->gerar($e2->id, TipoDocumento::Protocolo, 2025);

    expect($primeiroE2)->toBe('PRT-2025-000001');
});

it('aceita template customizado sobrepondo o padrão', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $numero = $gerador->gerar($empresa->id, TipoDocumento::Contrato, 2025, 'C-{ANO}/{SEQ:4}');

    expect($numero)->toBe('C-2025/0001');
});

it('persiste a sequência corretamente no banco', function (): void {
    $empresa = criarEmpresa();
    $gerador = new GeradorNumeroDocumento;

    $gerador->gerar($empresa->id, TipoDocumento::Matricula, 2025);
    $gerador->gerar($empresa->id, TipoDocumento::Matricula, 2025);

    $seq = DocumentSequence::where('empresa_id', $empresa->id)
        ->where('tipo', TipoDocumento::Matricula->value)
        ->where('ano', 2025)
        ->first();

    expect($seq)->not->toBeNull()
        ->and($seq->ultimo_numero)->toBe(2);
});

it('TipoDocumento retorna label e template corretos para todos os cases', function (TipoDocumento $tipo, string $label, string $template): void {
    expect($tipo->label())->toBe($label)
        ->and($tipo->template())->toBe($template);
})->with([
    [TipoDocumento::Matricula,  'Matrícula', 'MAT-{ANO}-{SEQ:6}'],
    [TipoDocumento::Contrato,   'Contrato',  'CTR-{ANO}-{SEQ:6}'],
    [TipoDocumento::Protocolo,  'Protocolo', 'PRT-{ANO}-{SEQ:6}'],
    [TipoDocumento::Recibo,     'Recibo',    'RCB-{ANO}-{SEQ:6}'],
]);

it('TipoDocumento::options retorna array com value e label para todos os cases', function (): void {
    $options = TipoDocumento::options();

    expect($options)->toHaveCount(4)
        ->and($options[0])->toMatchArray(['value' => 'matricula', 'label' => 'Matrícula']);
});
