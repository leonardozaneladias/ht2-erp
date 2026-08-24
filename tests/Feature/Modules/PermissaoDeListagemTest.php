<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa;

/*
 * Toda permissão declarada por uma tabela precisa EXISTIR no catálogo.
 *
 * Defeito real que motivou este teste (corrigido em 2026-08-24):
 * EspecificacaoModulo::metodoPermissaoListagem() montava a permissão como
 * snakePlural().'.listar' enquanto EspecificacaoModulo::permissoes() usava
 * permissaoBase().'.listar' — duas fórmulas para a mesma coisa, discordando em
 * modo pacote. As duas tabelas do RH nasceram exigindo 'departamentos.listar' e
 * 'funcionarios.listar', permissões que o config/rh.php nunca declarou (lá são
 * 'rh.departamentos.listar' e 'rh.funcionarios.listar').
 *
 * O sintoma era invisível porque FALHA FECHADO: empresasElegiveis()
 * (FiltraPorMultiEmpresa) filtra as empresas por essa permissão, então para
 * quem não é super-admin o seletor multiempresa ficava vazio, a seleção era
 * descartada na interseção de empresasSelecionadas(), e a tela caía no escopo da
 * empresa ativa. Nenhum erro, nenhum 403 — só um recurso que não funcionava.
 */

/** @return list<class-string> Tabelas que declaram permissão de listagem. */
function tabelasComPermissaoDeListagem(): array
{
    // dirname(), e não base_path(): o dataset é avaliado ANTES do app bootar,
    // quando o container ainda não expõe basePath().
    $raiz = dirname(__DIR__, 3);

    return collect(array_keys(require $raiz . '/vendor/composer/autoload_classmap.php'))
        ->filter(fn (string $classe): bool => str_ends_with($classe, 'Table'))
        ->filter(function (string $classe): bool {
            if (! class_exists($classe)) {
                return false;
            }

            return in_array(FiltraPorMultiEmpresa::class, class_uses_recursive($classe), true);
        })
        ->values()
        ->all();
}

it('encontra as tabelas que usam o filtro multiempresa', function (): void {
    // Sem esta âncora o teste abaixo passaria com zero casos — verde sem testar
    // nada, que é a falha que este arquivo inteiro existe para evitar.
    expect(tabelasComPermissaoDeListagem())->not->toBeEmpty();
});

it('a permissão de listagem existe no catálogo de acesso', function (string $tabela): void {
    $componente = (new ReflectionClass($tabela))->newInstanceWithoutConstructor();

    $metodo = new ReflectionMethod($tabela, 'permissaoListagem');
    $permissao = (string) $metodo->invoke($componente);

    $catalogo = collect((array) config('access.modules'))
        ->flatMap(fn (array $permissoes): array => array_keys($permissoes))
        ->all();

    // assertContains do PHPUnit, e não expect()->toContain(): o toContain do
    // Pest trata TODO argumento como mais um valor a procurar, então uma
    // mensagem passada ali vira um segundo valor exigido e o teste falha sempre.
    expect($catalogo)->toBeArray();
    test()->assertContains(
        $permissao,
        $catalogo,
        "{$tabela}::permissaoListagem() devolve '{$permissao}', que não existe em config('access.modules'). "
        . 'O filtro multiempresa desta tela nega toda empresa a quem não for super-admin.',
    );
})->with(fn () => tabelasComPermissaoDeListagem());
