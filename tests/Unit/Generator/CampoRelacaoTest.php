<?php

declare(strict_types=1);

use HT2ML\Core\Support\Generator\CampoModulo;

/*
|--------------------------------------------------------------------------
| O campo de relação
|--------------------------------------------------------------------------
|
| Antes deste tipo, uma FK era trabalho manual em DEZ arquivos — migration,
| model, DTO, Rules, factory, formulário, ficha, tabela, teste e view. Medido
| num recurso real. Quase toda entidade de um domínio tem FK, então vinte telas
| significavam duzentas edições à mão, cada uma uma chance de divergir: é a
| duplicação de listas paralelas que a base declarativa foi feita para matar,
| reaparecendo um andar acima, no gerador.
|
| O que estes testes guardam são as decisões, não a sintaxe:
|
|   - a tabela vem do MODEL quando ele existe, porque o pluralizador do Laravel
|     é inglês e 'Filial' vira 'filials';
|   - a FK obrigatória nasce NULA na propriedade do formulário, porque não há id
|     neutro — 0 não existe na tabela;
|   - o `exists:` existe porque `integer` sozinho aceita id de outra empresa.
|
*/

it('entende relacao(Model) e deriva método, rótulo e atributo', function (): void {
    $campo = CampoModulo::deToken('turma_id:relacao(Turma)');

    expect($campo->ehRelacao())->toBeTrue()
        ->and($campo->relacaoModel)->toBe('Turma')
        ->and($campo->relacaoMetodo())->toBe('turma')
        ->and($campo->relacaoAtributo)->toBe('nome')
        // "Turma", não "Turma id": o rótulo é o do relacionado.
        ->and($campo->label())->toBe('Turma');
});

it('aceita atributo de exibição e FQCN de outro pacote', function (): void {
    $campo = CampoModulo::deToken('autor_id:relacao(\\HT2ML\\Core\\Models\\AdminUser|email)');

    expect($campo->relacaoModel)->toBe('HT2ML\\Core\\Models\\AdminUser')
        ->and($campo->relacaoAtributo)->toBe('email')
        ->and($campo->relacaoModelCurto())->toBe('AdminUser')
        ->and($campo->relacaoMetodo())->toBe('autor');
});

it('pergunta a tabela ao model quando ele existe', function (): void {
    $campo = CampoModulo::deToken('filial_id:relacao(\\HT2ML\\Core\\Models\\Filial)');

    // O palpite do Laravel seria 'filials' — o pluralizador é inglês. Quem sabe
    // o nome é o próprio model, e a FK apontaria para uma tabela inexistente.
    expect($campo->relacaoTabela())->toBe('filials')
        ->and($campo->relacaoTabela(HT2ML\Core\Models\Filial::class))->toBe('filiais');
});

it('a migration cria FK de verdade, com o comportamento certo no delete', function (): void {
    $obrigatoria = CampoModulo::deToken('turma_id:relacao(Turma)');
    $opcional = CampoModulo::deToken('turma_id:relacao(Turma):nullable');

    // restrictOnDelete numa FK obrigatória: apagar uma turma não pode apagar os
    // alunos dela em silêncio.
    expect($obrigatoria->colunaMigration())
        ->toContain("->constrained('turmas')")
        ->toContain('->restrictOnDelete()')
        ->and($opcional->colunaMigration())
        ->toContain('->nullable()')
        ->toContain('->nullOnDelete()');
});

it('valida com exists, não só com integer', function (): void {
    $campo = CampoModulo::deToken('turma_id:relacao(Turma)');

    // Sem o exists, um id de outra empresa passa na validação e só a FK reclama
    // — se o registro não existir no banco INTEIRO.
    expect($campo->regras())->toBe(["'required'", "'integer'", "'exists:turmas,id'"]);
});

it('a propriedade do formulário é anulável mesmo quando o campo é obrigatório', function (): void {
    $campo = CampoModulo::deToken('turma_id:relacao(Turma)');

    // Não há id neutro: 0 não existe na tabela e '' não é int. O formulário
    // nasce com nada selecionado, e o `required` cobra a escolha.
    expect($campo->tipoPhp())->toBe('?int')
        ->and($campo->defaultPhp())->toBe(' = null')
        ->and($campo->defaultLivewire())->toBe('null');
});

it('a fábrica cria o relacionado em vez de inventar um id', function (): void {
    $campo = CampoModulo::deToken('turma_id:relacao(Turma)');

    // Um id literal passaria no `integer` e morreria na FK — e o teste gerado
    // falharia por um motivo que não tem nada a ver com o que ele verifica.
    expect($campo->fragmentoFactory(relacaoFqcn: 'App\\Models\\Turma'))
        ->toBe("'turma_id' => \\App\\Models\\Turma::factory(),")
        ->and($campo->valorTeste('App\\Models\\Turma'))
        ->toBe('\\App\\Models\\Turma::factory()->create()->id');
});

it('declara Campo::relacao na base, que traz o eager load junto', function (): void {
    $campo = CampoModulo::deToken('turma_id:relacao(Turma|descricao)');

    expect($campo->campoDeclarativo())
        ->toBe("Campo::relacao('turma_id', 'Turma', 'turma', 'descricao')->obrigatorio(),");
});
