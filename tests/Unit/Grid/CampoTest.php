<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\TipoCampo;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| Um Campo, cinco derivações
|--------------------------------------------------------------------------
|
| Cada caso aqui fixa um dos defeitos que as quatro listas paralelas produziam
| nas telas geradas — booleano como `0`, dinheiro em centavos, busca textual em
| cor hexadecimal, coluna numérica sem filtro. Não são testes de getter: são o
| contrato que impede a volta.
|
*/

/** Model anônimo, para não depender de banco nem de migration. */
function linhaDeTeste(array $atributos): Model
{
    return new class($atributos) extends Model
    {
        protected $guarded = [];
    };
}

it('só texto entra na busca global', function (): void {
    expect(Campo::texto('nome', 'Nome')->coluna()->toLivewire()['searchable'])->toBeTrue()
        ->and(Campo::numero('qtd', 'Qtd')->coluna()->toLivewire()['searchable'])->toBeFalse()
        ->and(Campo::dinheiro('preco', 'Preço')->coluna()->toLivewire()['searchable'])->toBeFalse()
        ->and(Campo::data('em', 'Em')->coluna()->toLivewire()['searchable'])->toBeFalse()
        ->and(Campo::booleano('ativo', 'Ativo')->coluna()->toLivewire()['searchable'])->toBeFalse();
});

it('cada tipo pede o widget de filtro que lhe cabe', function (string $metodo, string $widget): void {
    expect(Campo::$metodo('campo', 'Campo')->filtro()?->key)->toBe($widget);
})->with([
    ['texto', 'input_text'],
    ['numero', 'number'],
    ['dinheiro', 'number'],
    ['data', 'date'],
    ['dataHora', 'datetime'],
    ['booleano', 'boolean'],
]);

it('o filtro booleano fala português', function (): void {
    $filtro = Campo::booleano('ativa', 'Ativa', 'Sim, ativa', 'Não')->filtro();

    expect($filtro->trueLabel)->toBe('Sim, ativa')
        ->and($filtro->falseLabel)->toBe('Não');
});

it('o placeholder desce de caixa sem estragar sigla', function (string $rotulo, string $esperado): void {
    expect(Campo::texto('c', $rotulo)->filtro()?->placeholder)->toBe($esperado);
})->with([
    ['Nome completo', 'Filtrar por nome completo'],
    ['Código ISO2', 'Filtrar por código ISO2'],
    ['ISPB', 'Filtrar por ISPB'],
    ['COMPE', 'Filtrar por COMPE'],
]);

it('dinheiro sai da tela em reais, não em centavos', function (): void {
    $campo = Campo::dinheiro('preco', 'Preço');
    $linha = linhaDeTeste(['preco' => 123456]);

    expect(($campo->formatador())($linha))->toBe('R$ 1.234,56')
        ->and($campo->textoDeExportacao($linha))->toBe('R$ 1.234,56')
        ->and($campo->coluna()->toLivewire()['bodyClass'])->toBe('text-right');
});

it('booleano vira Sim/Não, e o rótulo declarado vale nos dois lugares', function (): void {
    $campo = Campo::booleano('ativa', 'Ativa', 'Ativa', 'Inativa');

    expect(($campo->formatador())(linhaDeTeste(['ativa' => true])))->toBe('Ativa')
        ->and(($campo->formatador())(linhaDeTeste(['ativa' => false])))->toBe('Inativa')
        // Antes: a coluna renderizava 0/1 e o filtro dizia Yes/No.
        ->and($campo->filtro()->trueLabel)->toBe('Ativa');
});

it('o campo derivado não atropela a coluna real, para ordenar e filtrar no banco', function (): void {
    $coluna = Campo::booleano('ativo', 'Ativo')->coluna()->toLivewire();

    expect($coluna['field'])->toBe('ativo_label')
        ->and($coluna['dataField'])->toBe('ativo');
});

it('coluna oculta por padrão continua no seletor', function (): void {
    $coluna = Campo::texto('obs', 'Obs')->ocultoPorPadrao()->coluna()->toLivewire();

    // forceHidden: true a tiraria também do seletor de colunas.
    expect($coluna['hidden'])->toBeTrue()
        ->and($coluna['forceHidden'])->toBeFalse();
});

it('a relação declara o eager load que ninguém precisa lembrar', function (): void {
    expect(Campo::relacao('turma_id', 'Turma', 'turma')->eagerLoad())->toBe('turma')
        ->and(Campo::texto('nome', 'Nome')->eagerLoad())->toBeNull();
});

it('as regras somam o tipo, a obrigatoriedade e o que foi declarado', function (): void {
    expect(Campo::texto('nome', 'Nome')->obrigatorio()->max(120)->regrasDeValidacao())
        ->toBe(['required', 'string', 'max:120'])
        ->and(Campo::dinheiro('preco', 'Preço')->regrasDeValidacao())
        ->toBe(['nullable', 'integer'])
        ->and(Campo::texto('doc', 'Doc')->regra(new HT2ML\Core\Rules\Cpf)->regrasDeValidacao())
        ->toHaveCount(3);
});

it('a fuga por campo tem a última palavra', function (): void {
    $campo = Campo::texto('cor', 'Cor')
        ->comColuna(fn ($coluna) => $coluna->title('Cor da marca'))
        ->paraExportar(fn (Model $linha): string => 'exportado');

    expect($campo->coluna()->toLivewire()['title'])->toBe('Cor da marca')
        ->and($campo->textoDeExportacao(linhaDeTeste(['cor' => '#fff'])))->toBe('exportado');
});

it('o tipo decide, e o enum é exaustivo', function (): void {
    // Se um TipoCampo novo entrar sem decidir filtro, alinhamento e regras,
    // o match nativo lança — este teste é quem faz isso acontecer.
    foreach (TipoCampo::cases() as $tipo) {
        $tipo->filtro();
        $tipo->alinhamento();
        $tipo->regrasBase();
        $tipo->pesquisavelPorPadrao();
        $tipo->usaRotulo();
    }

    expect(TipoCampo::cases())->toHaveCount(10);
});
