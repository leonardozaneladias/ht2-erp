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

it('--tenant traz o trait que liga as seis composições multiempresa de uma vez', function (): void {
    $spec = new EspecificacaoModulo('Exemplo', [CampoModulo::deToken('nome:string')], tenant: true);
    $tokens = $spec->tokens();

    // Antes eram OITO tokens interpolados em pontos diferentes do stub — o
    // escopo do datasource, os campos, as colunas, os filtros e as duas metades
    // da exportação. Emitir sete e esquecer o primeiro produzia uma tela que
    // passava em todos os testes de um tenant só e vazava linhas de outra
    // empresa no dia em que a segunda fosse cadastrada. Agora é um trait: ou
    // vêm as seis, ou não vem nenhuma.
    expect($tokens['__USE_MULTI_EMPRESA__'])->toBe('use HT2ML\Core\Livewire\Grid\RecursoMultiEmpresa;')
        ->and($tokens['__TRAIT_MULTI_EMPRESA__'])->toBe('use RecursoMultiEmpresa;');
});

it('sem --tenant, os tokens multi-empresa ficam vazios', function (): void {
    $tokens = specDeTokens(['nome:string'])->tokens();

    expect($tokens['__USE_MULTI_EMPRESA__'])->toBe('')
        ->and($tokens['__TRAIT_MULTI_EMPRESA__'])->toBe('');
});

it('a permissão de listagem não é mais redigitada pelo gerador', function (): void {
    $spec = new EspecificacaoModulo('Exemplo', [CampoModulo::deToken('nome:string')], tenant: true);

    // A RecursoTable deriva permissaoListagem() de permissaoBase(). Enquanto o
    // gerador emitia o método, ele usava uma SEGUNDA fórmula — snakePlural() —
    // que discordava da primeira em modo pacote: 'departamentos.listar' contra
    // 'rh.departamentos.listar'. Permissão inexistente, seletor de empresas
    // vazio para quem não é super-admin, e nenhum erro na tela.
    expect($spec->tokens())->not->toHaveKey('__PERMISSAO_LISTAGEM__');
});

it('a lista de campos declarativos carrega o tipo, a obrigatoriedade e a unicidade', function (): void {
    $spec = specDeTokens(['nome:string', 'preco:money', 'obs:text', 'sku:string:unique:nullable']);

    $campos = $spec->camposDeclarativos();

    expect($campos)
        ->toContain("Campo::texto('nome', 'Nome')->obrigatorio(),")
        // money → dinheiro: a tela mostra R$ e o filtro é numérico. Antes saía
        // como texto pesquisável, com o valor em centavos crus.
        ->toContain("Campo::dinheiro('preco', 'Preco')->obrigatorio(),")
        // Texto longo não cabe na célula: coluna existe, mas escondida.
        ->toContain("Campo::texto('obs', 'Obs')->ocultoPorPadrao()")
        ->toContain('->unico()')
        // O enum de status entra sempre, e como enum — não como string crua.
        ->toContain("Campo::enum('status', 'Status', StatusExemplo::class)->obrigatorio(),");
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

    // A lixeira sumiu dos tokens da Table de propósito: RecursoTable a deriva
    // do MODEL (`class_uses_recursive` + a interface UsaSoftDeletes), então uma
    // tabela não consegue mais afirmar que tem lixeira sobre um model que não
    // tem. Antes isso era uma flag do gerador, e a tela só quebrava quando
    // alguém clicasse em "Ver lixeira".
    expect($tokens['__MODEL_USE_LIXEIRA__'])->toBe('use HT2ML\Core\Models\Contracts\UsaSoftDeletes;')
        ->and($tokens['__MODEL_IMPLEMENTS_LIXEIRA__'])->toBe(' implements UsaSoftDeletes')
        ->and($tokens['__MODEL_DELETED_AT_PROPERTY__'])->toContain('@property \Illuminate\Support\Carbon|null $deleted_at')
        ->and($tokens['__VERLIXEIRA_PARAM__'])->toBe(", 'verLixeira' => \$this->verLixeira")
        ->and($spec->metodosPolicyLixeira())->toContain('exemplos.restaurar')
        ->and($spec->metodosPolicyLixeira())->toContain('exemplos.excluir_permanente')
        ->and($spec->factoryTrashed())->toContain('public function trashed(): static')
        ->and($spec->testeSoftDelete())->toContain("->call('excluir'");
});

it('sem soft-delete, os tokens de lixeira ficam vazios e o header usa _export-pdf', function (): void {
    $spec = specDeTokens(['nome:string']);
    $tokens = $spec->tokens();

    // Sem SoftDeletes o MODEL não implementa UsaSoftDeletes, e é daí que a
    // RecursoTable conclui que não há lixeira. A view única exibe apenas a
    // metade do "Exportar PDF".
    expect($tokens['__VERLIXEIRA_PARAM__'])->toBe('')
        ->and($tokens['__MODEL_USE_LIXEIRA__'])->toBe('')
        ->and($tokens['__MODEL_IMPLEMENTS_LIXEIRA__'])->toBe('')
        ->and($tokens['__MODEL_DELETED_AT_PROPERTY__'])->toBe('')
        ->and($spec->metodosPolicyLixeira())->toBe('')
        ->and($spec->factoryTrashed())->toBe('')
        ->and($spec->testeSoftDelete())->toBe('');
});
