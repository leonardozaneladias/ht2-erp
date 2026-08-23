<?php

declare(strict_types=1);

use App\Models\Anexo;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);
});

function criarAnexoComArquivo(Empresa $empresa): Anexo
{
    Storage::disk('local')->put('anexos/teste.pdf', 'conteudo');

    $anexo = new Anexo([
        'disco' => 'local',
        'caminho' => 'anexos/teste.pdf',
        'nome_original' => 'teste.pdf',
        'mime' => 'application/pdf',
        'tamanho' => 8,
    ]);
    $anexo->anexavel()->associate($empresa);
    $anexo->save();

    return $anexo;
}

it('soft-delete de anexo mantém o arquivo físico (D4)', function () {
    $anexo = criarAnexoComArquivo($this->empresa);

    $anexo->delete();

    expect(Anexo::query()->whereKey($anexo->id)->exists())->toBeFalse()
        ->and(Anexo::withTrashed()->find($anexo->id)->trashed())->toBeTrue()
        ->and(Storage::disk('local')->exists('anexos/teste.pdf'))->toBeTrue();
});

it('force-delete de anexo remove o arquivo físico', function () {
    $anexo = criarAnexoComArquivo($this->empresa);

    $anexo->forceDelete();

    expect(Anexo::withTrashed()->whereKey($anexo->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists('anexos/teste.pdf'))->toBeFalse();
});
