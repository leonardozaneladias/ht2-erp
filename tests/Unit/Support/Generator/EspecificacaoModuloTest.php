<?php

declare(strict_types=1);

use App\Support\Generator\CampoModulo;
use App\Support\Generator\EspecificacaoModulo;

/**
 * @param  list<string>  $tokens
 */
function specDeTokens(array $tokens): EspecificacaoModulo
{
    return new EspecificacaoModulo(
        'Exemplo',
        array_map(static fn (string $t): CampoModulo => CampoModulo::deToken($t), $tokens),
    );
}

it('sem aba(...) gera card único, não abas', function (): void {
    $spec = specDeTokens(['nome:string', 'email:email']);

    expect($spec->temAbas())->toBeFalse();

    $body = $spec->formBody();
    expect($body)->toContain('<x-shared.card title="Dados">')
        ->and($body)->not->toContain('x-shared.tab-nav');
});

it('com aba(...) gera tab-nav + tab-body conectados', function (): void {
    $spec = specDeTokens(['nome:string:aba(Dados)', 'email:email:aba(Contato)']);

    expect($spec->temAbas())->toBeTrue();

    $body = $spec->formBody();
    expect($body)->toContain('<x-shared.tab-nav>')
        ->and($body)->toContain('<x-shared.tab-body')
        ->and($body)->toContain('id="aba-dados"')
        ->and($body)->toContain('id="aba-contato"');
});

it('agrupa por aba na ordem e joga o status sem aba na última', function (): void {
    $spec = specDeTokens(['nome:string:aba(Dados)', 'email:email:aba(Contato)']);
    $abas = $spec->abas();

    expect(array_keys($abas))->toBe(['Dados', 'Contato']);

    $nomesUltima = array_map(static fn (CampoModulo $c): string => $c->nome, $abas['Contato']);
    expect($nomesUltima)->toContain('status');
});

it('campos regulares sem aba caem na primeira aba', function (): void {
    $spec = specDeTokens(['nome:string:aba(Dados)', 'apelido:string', 'email:email:aba(Contato)']);

    $nomesPrimeira = array_map(static fn (CampoModulo $c): string => $c->nome, $spec->abas()['Dados']);
    expect($nomesPrimeira)->toContain('apelido');
});

it('marca a primeira aba/painel como active e injeta has-error por aba', function (): void {
    $spec = specDeTokens(['nome:string:aba(Dados)', 'email:email:aba(Contato)']);
    $body = $spec->formBody();

    expect($body)->toContain('id="aba-dados" active')
        ->and($body)->toContain("\$errors->hasAny(['nome'])");
});

it('sanitiza richtext nullable via helper $html no DTO', function (): void {
    $spec = specDeTokens(['descricao:richtext:nullable']);

    expect($spec->dtoUsaHtmlHelper())->toBeTrue()
        ->and($spec->dtoFromArray())->toContain("\$html('descricao')");
});

it('sanitiza richtext não-nullable inline com HtmlSanitizer', function (): void {
    $spec = specDeTokens(['descricao:richtext']);

    expect($spec->dtoUsaHtmlHelper())->toBeFalse()
        ->and($spec->dtoFromArray())->toContain('HtmlSanitizer::clean');
});

it('formata date/datetime no mount load e anota @property Carbon', function (): void {
    $spec = specDeTokens(['data_inicio:date', 'publicado_em:datetime:nullable']);
    $mount = $spec->formMountLoad();

    // Não-nullable: sem nullsafe (evita nullsafe.neverNull no PHPStan).
    expect($mount)->toContain("\$registro->data_inicio->format('Y-m-d');")
        ->and($mount)->toContain("\$registro->publicado_em?->format('Y-m-d H:i');")
        ->and($spec->modelDateProperties())->toContain('@property \Illuminate\Support\Carbon $data_inicio')
        ->and($spec->modelDateProperties())->toContain('@property \Illuminate\Support\Carbon|null $publicado_em');
});

it('anota @var list<string> na prop multiselect do formulário', function (): void {
    expect(specDeTokens(['tags:multiselect(a|b|c)'])->formProps())->toContain('@var list<string>');
});

it('--tenant injeta o filtro multi-empresa na Table', function (): void {
    $spec = new EspecificacaoModulo('Exemplo', [CampoModulo::deToken('nome:string')], tenant: true);
    $tokens = $spec->tokens();

    expect($tokens['__USE_MULTI_EMPRESA__'])->toContain('use App\Livewire\Concerns\FiltraPorMultiEmpresa;')
        ->and($tokens['__TRAIT_MULTI_EMPRESA__'])->toBe('use FiltraPorMultiEmpresa;')
        ->and($tokens['__PERMISSAO_LISTAGEM__'])->toContain("return 'exemplos.listar';")
        ->and($tokens['__DS_OPEN__'])->toBe('$this->aplicarEscopoMultiEmpresa(')
        ->and($tokens['__DS_CLOSE__'])->toBe(')')
        ->and($tokens['__FIELDS_OPEN__'])->toBe('$this->camposMultiEmpresa(')
        ->and($tokens['__COLUNAS_MULTI_EMPRESA__'])->toBe('...$this->colunasMultiEmpresa(),')
        ->and($tokens['__FILTROS_MULTI_EMPRESA__'])->toBe('...$this->filtrosMultiEmpresa(),');
});

it('sem --tenant, os tokens multi-empresa ficam vazios', function (): void {
    $tokens = specDeTokens(['nome:string'])->tokens();

    expect($tokens['__USE_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__TRAIT_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__PERMISSAO_LISTAGEM__'])->toBe('')
        ->and($tokens['__DS_OPEN__'])->toBe('')
        ->and($tokens['__DS_CLOSE__'])->toBe('')
        ->and($tokens['__COLUNAS_MULTI_EMPRESA__'])->toBe('');
});
