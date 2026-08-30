<?php

declare(strict_types=1);

use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| A inflexão do gerador
|--------------------------------------------------------------------------
|
| O Laravel infleta em inglês por default, e num domínio em português ele erra o
| singular e o plural de um em cada quatro nomes. Medido em doze substantivos de
| um domínio escolar — CINCO saíam errados:
|
|   Materia     -> classe Materium,    tabela materia
|   Nota        -> classe Notum,       tabela nota
|   Frequencia  -> classe Frequencium, tabela frequencia
|   Responsavel -> plural Responsavels
|   Avaliacao   -> plural Avaliacaos
|
| Isso não é cosmético: a classe e a tabela nascem com o nome errado, e renomear
| depois de a tela existir é migração de dados. Num produto com vinte telas,
| cinco delas.
|
| A troca vale só durante a GERAÇÃO. Em runtime o Eloquent continua resolvendo
| como sempre resolveu: os models gerados declaram `$table` explicitamente, e um
| produto já instalado não pode mudar de nome de tabela por causa disto.
|
*/

afterEach(function (): void {
    // O Pluralizer é estático: sem restaurar, o idioma vaza para os testes
    // seguintes e a suíte passa a depender da ordem de execução.
    Pluralizer::useLanguage('english');
});

it('o default do Laravel erra cinco dos doze nomes do domínio', function (): void {
    Pluralizer::useLanguage('english');

    expect(Str::singular('Materia'))->toBe('Materium')
        ->and(Str::singular('Nota'))->toBe('Notum')
        ->and(Str::singular('Frequencia'))->toBe('Frequencium')
        ->and(Str::plural('Responsavel'))->toBe('Responsavels')
        ->and(Str::plural('Avaliacao'))->toBe('Avaliacaos');
});

it('a inflexão em português acerta os cinco', function (): void {
    Pluralizer::useLanguage('portuguese');

    expect(Str::singular('Materia'))->toBe('Materia')
        ->and(Str::plural('Materia'))->toBe('Materias')
        ->and(Str::singular('Nota'))->toBe('Nota')
        ->and(Str::singular('Frequencia'))->toBe('Frequencia')
        ->and(Str::plural('Responsavel'))->toBe('Responsaveis')
        ->and(Str::plural('Avaliacao'))->toBe('Avaliacoes');
});

it('não muda o que já estava certo', function (): void {
    Pluralizer::useLanguage('portuguese');

    // Os nomes que o inglês acertava por coincidência precisam continuar iguais:
    // se mudassem, a troca de idioma renomearia tabelas de telas existentes.
    foreach (['Aluno' => 'Alunos', 'Turma' => 'Turmas', 'Fatura' => 'Faturas',
        'Pagamento' => 'Pagamentos', 'Comunicado' => 'Comunicados',
        'Cardapio' => 'Cardapios', 'Recarga' => 'Recargas'] as $singular => $plural) {
        expect(Str::plural($singular))->toBe($plural)
            ->and(Str::singular($singular))->toBe($singular);
    }
});

it('a plataforma continua em inglês, e a chave existe para o produto decidir', function (): void {
    // O core não escolhe o idioma do domínio de quem o instala. O default é
    // null — inglês, como sempre foi — e um produto em português declara o seu.
    expect(config('extensoes.idioma_inflexao'))->toBeNull();

    $publicado = (string) file_get_contents(
        dirname(__DIR__, 3) . '/packages/core/config/extensoes.php',
    );

    expect($publicado)->toContain("'idioma_inflexao' => null");
});
