<?php

declare(strict_types=1);

use HT2ML\Core\Support\Generator\CampoModulo;
use HT2ML\Core\Support\Generator\EspecificacaoModulo;

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

it('lê centavos do VO no mount load de money, sem cast (int) do objeto', function (): void {
    $mount = specDeTokens(['preco:money', 'desconto:money:nullable'])->formMountLoad();

    // O MoneyCast devolve um VO: `(int) $registro->preco` era um cast de objeto para int.
    expect($mount)->toContain('$registro->preco->centavos();')
        ->and($mount)->toContain('$registro->desconto?->centavos() ?? 0;')
        ->and($mount)->not->toContain('(int) $registro->preco')
        ->and($mount)->not->toContain('(int) $registro->desconto');

    // `integer` continua com o cast — é escalar, não VO.
    expect(specDeTokens(['quantidade:integer'])->formMountLoad())
        ->toContain('(int) $registro->quantidade;');
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
        ->and($tokens['__FILTROS_MULTI_EMPRESA__'])->toBe('...$this->filtrosMultiEmpresa(),')
        ->and($tokens['__PDF_LINHA_MULTI_EMPRESA__'])->toBe('...$this->linhaMultiEmpresa($registro),')
        ->and($tokens['__PDF_CABECALHOS_MULTI_EMPRESA__'])->toBe('...$this->cabecalhosMultiEmpresa(), ');
});

it('sem --tenant, os tokens multi-empresa ficam vazios', function (): void {
    $tokens = specDeTokens(['nome:string'])->tokens();

    expect($tokens['__USE_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__TRAIT_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__PERMISSAO_LISTAGEM__'])->toBe('')
        ->and($tokens['__DS_OPEN__'])->toBe('')
        ->and($tokens['__DS_CLOSE__'])->toBe('')
        ->and($tokens['__COLUNAS_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__PDF_LINHA_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__PDF_CABECALHOS_MULTI_EMPRESA__'])->toBe('');
});

it('com soft-delete, permissoes adiciona restaurar/excluir_permanente e deletar vira lixeira', function (): void {
    $spec = new EspecificacaoModulo('Exemplo', [CampoModulo::deToken('nome:string')], softDelete: true);
    $perms = $spec->permissoes();

    expect(array_keys($perms))->toBe([
        'exemplos.listar',
        'exemplos.criar',
        'exemplos.editar',
        'exemplos.deletar',
        'exemplos.restaurar',
        'exemplos.excluir_permanente',
    ])
        ->and($perms['exemplos.deletar']['descricao'])->toBe('Mover exemplos para a lixeira.')
        ->and($perms['exemplos.restaurar']['label'])->toBe('Restaurar exemplos')
        ->and($perms['exemplos.excluir_permanente']['descricao'])->toContain('irreversível');
});

it('sem soft-delete, permissoes tem só as 4 base e deletar é remover', function (): void {
    $perms = specDeTokens(['nome:string'])->permissoes();

    expect(array_keys($perms))->toBe([
        'exemplos.listar',
        'exemplos.criar',
        'exemplos.editar',
        'exemplos.deletar',
    ])
        ->and($perms['exemplos.deletar']['descricao'])->toBe('Remover exemplos.');
});

it('com soft-delete, os tokens e blocos de lixeira são preenchidos', function (): void {
    $spec = new EspecificacaoModulo('Exemplo', [CampoModulo::deToken('nome:string')], softDelete: true);
    $tokens = $spec->tokens();

    expect($tokens['__USE_COM_LIXEIRA__'])->toBe('use HT2ML\Core\Livewire\Concerns\ComLixeira;')
        ->and($tokens['__TRAIT_COM_LIXEIRA__'])->toBe('use ComLixeira;')
        ->and($tokens['__DS_LIXEIRA_OPEN__'])->toBe('$this->aplicarLixeira(')
        ->and($tokens['__DS_LIXEIRA_CLOSE__'])->toBe(')')
        // A toolbar do grid virou uma VIEW ÚNICA do core; o gerador não copia mais um
        // `_lixeira-toggle` e um `_export-pdf` por módulo. Ele só declara o prefixo das
        // permissões, de onde a view deriva `{prefixo}.restaurar`.
        ->and($tokens['__PERMISSAO_BASE__'])->toContain('permissaoBase')
        ->and($tokens['__PERMISSAO_BASE__'])->toContain("return 'exemplos';")
        ->and($tokens['__VERLIXEIRA_PARAM__'])->toBe(", 'verLixeira' => \$this->verLixeira")
        ->and($tokens['__MODEL_USE_LIXEIRA__'])->toBe('use HT2ML\Core\Models\Contracts\UsaSoftDeletes;')
        ->and($tokens['__MODEL_IMPLEMENTS_LIXEIRA__'])->toBe(' implements UsaSoftDeletes')
        ->and($tokens['__MODEL_DELETED_AT_PROPERTY__'])->toContain('@property \Illuminate\Support\Carbon|null $deleted_at')
        ->and($spec->metodoModelClassLixeira())->toContain('modelClassLixeira')
        ->and($spec->metodosPolicyLixeira())->toContain('exemplos.restaurar')
        ->and($spec->metodosPolicyLixeira())->toContain('exemplos.excluir_permanente')
        ->and($spec->factoryTrashed())->toContain('public function trashed(): static')
        ->and($spec->testeSoftDelete())->toContain("->call('excluir'");
});

it('sem soft-delete, os tokens de lixeira ficam vazios e o header usa _export-pdf', function (): void {
    $spec = specDeTokens(['nome:string']);
    $tokens = $spec->tokens();

    expect($tokens['__USE_COM_LIXEIRA__'])->toBe('')
        ->and($tokens['__TRAIT_COM_LIXEIRA__'])->toBe('')
        ->and($tokens['__DS_LIXEIRA_OPEN__'])->toBe('')
        ->and($tokens['__DS_LIXEIRA_CLOSE__'])->toBe('')
        // Sem SoftDeletes não há lixeira: o método não é gerado, e a view única exibe
        // apenas a metade do "Exportar PDF".
        ->and($tokens['__PERMISSAO_BASE__'])->toBe('')
        ->and($tokens['__VERLIXEIRA_PARAM__'])->toBe('')
        ->and($tokens['__MODEL_USE_LIXEIRA__'])->toBe('')
        ->and($tokens['__MODEL_IMPLEMENTS_LIXEIRA__'])->toBe('')
        ->and($tokens['__MODEL_DELETED_AT_PROPERTY__'])->toBe('')
        ->and($spec->metodoModelClassLixeira())->toBe('')
        ->and($spec->metodosPolicyLixeira())->toBe('')
        ->and($spec->factoryTrashed())->toBe('')
        ->and($spec->testeSoftDelete())->toBe('');
});
