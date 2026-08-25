<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Nenhum filtro fala inglês com o usuário
|--------------------------------------------------------------------------
|
| FilterBoolean do PowerGrid tem `trueLabel = 'Yes'` e `falseLabel = 'No'` como
| default, e a view do filtro os renderiza direto — a tradução pt_BR do pacote
| cobre só o "Todos". Resultado: seis tabelas mostravam um dropdown com Yes/No
| a usuários brasileiros, e o gerador emitia `Filter::boolean()` sem rótulo, de
| modo que cada módulo novo nascia com o mesmo defeito.
|
| Este guard varre TODAS as tabelas do repositório, não uma lista escrita à mão:
| uma tabela nova entra sozinha, e é o único jeito de a correção não caducar.
|
*/

/**
 * @return list<class-string<PowerGridComponent>>
 */
function tabelasPowerGrid(): array
{
    $classes = [];

    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path('packages')),
    );

    foreach ($iterador as $arquivo) {
        if (! $arquivo instanceof SplFileInfo || ! str_ends_with($arquivo->getFilename(), 'Table.php')) {
            continue;
        }

        $codigo = (string) file_get_contents($arquivo->getPathname());

        if (! preg_match('/namespace\s+([^;]+);/', $codigo, $ns)) {
            continue;
        }

        $classe = trim($ns[1]) . '\\' . $arquivo->getBasename('.php');

        if (! class_exists($classe) || ! is_subclass_of($classe, PowerGridComponent::class)) {
            continue;
        }

        // A base declarativa também termina em Table e também estende
        // PowerGridComponent — mas é abstrata e não tem filtros próprios.
        if ((new ReflectionClass($classe))->isAbstract()) {
            continue;
        }

        $classes[] = $classe;
    }

    sort($classes);

    return $classes;
}

it('encontra as tabelas do repositório', function (): void {
    // Meta-checagem: sem ela, um erro na varredura faria o guard abaixo passar
    // sem nunca ter olhado nada.
    expect(tabelasPowerGrid())->toHaveCount(16);
});

it('nenhum filtro booleano mostra Yes/No', function (): void {
    // Autenticado: as tabelas multiempresa consultam as empresas elegíveis do
    // usuário ao montar os filtros, e sem sessão isso aborta com 403.
    $this->seed(HT2ML\Core\Database\Seeders\RolePermissionSeeder::class);
    $admin = criarAdminUser();
    $admin->assignRole('super-admin');
    $this->actingAs($admin, 'admin');

    $emIngles = [];

    foreach (tabelasPowerGrid() as $classe) {
        foreach ((new $classe)->filters() as $filtro) {
            if (($filtro->key ?? null) !== 'boolean') {
                continue;
            }

            if (in_array($filtro->trueLabel, ['Yes', 'No'], true) || in_array($filtro->falseLabel, ['Yes', 'No'], true)) {
                $emIngles[] = sprintf(
                    '%s → %s (%s/%s)',
                    class_basename($classe),
                    $filtro->column,
                    $filtro->trueLabel,
                    $filtro->falseLabel,
                );
            }
        }
    }

    expect($emIngles)->toBe([], implode("\n", [
        'Filtros booleanos com os rótulos padrão do PowerGrid (em inglês):',
        ...$emIngles,
        '',
        "Acrescente ->label('Sim', 'Não') — ou os rótulos do domínio, como",
        "'Ativa'/'Inativa'. Numa RecursoTable isto vem de Campo::booleano().",
    ]));
});
