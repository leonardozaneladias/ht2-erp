<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| A dependência é de mão única (ADR-0022)
|--------------------------------------------------------------------------
|
| O core não conhece extensão nenhuma. A direção contrária — "o código do
| cliente nunca edita o core" — está escrita em ADR-0015; esta, a inversa,
| aparecia só em prosa, em documentos que a tratavam como já decidida. Sem o
| ADR, este teste seria uma opinião com CI.
|
| São DOIS guards porque há duas formas de violar, e uma delas nenhum arch test
| de namespace pega:
|
|   A1 — referência de classe.  `use HT2ML\Rh\Models\Funcionario` no core.
|   A2 — literal de string.     `'grupo-tab-rh'` dentro de uma Action do core.
|
| A2 é o que aconteceu de verdade: a AplicarMenuPadraoAction hardcodava
| 'grupo-tab-rh', 'ref-cnaes', 'ref-cfops', 'ref-ncms', 'rh-departamentos' e
| 'rh-funcionarios'. Nenhuma linha de `use`, nenhuma classe referenciada — e
| ainda assim o core sabia de cor o nome de duas extensões.
|
*/

/**
 * Namespaces de todo pacote que não é o core.
 *
 * @return list<string>
 */
function namespacesDeExtensao(): array
{
    return array_values(array_filter(
        namespacesDosPacotes(),
        static fn (string $ns): bool => $ns !== 'HT2ML\Core',
    ));
}

/**
 * Chaves que só as extensões conhecem: permissões, itens de menu e grupos
 * declarados nas configs delas, menos tudo que o core já declara nas suas.
 *
 * Derivado, nunca escrito à mão — uma extensão nova entra no guard sozinha.
 *
 * @return list<string>
 */
function chavesExclusivasDeExtensao(): array
{
    $chavesDe = static function (array $config, string $namespace): array {
        $chaves = [
            ...array_keys($config['permissoes'] ?? []),
            ...array_column($config['menu'] ?? [], 'key'),
            ...array_keys($config['grupos'] ?? []),
        ];

        // Formato do ModuloBuilder: a extensão declara só a chave do recurso, e
        // a permissão e a key do menu passam a existir apenas em runtime. Sem
        // esta derivação o guard enfraqueceria a cada extensão migrada — o que
        // era literal na config sairia da lista de chaves proibidas em silêncio,
        // e o core voltaria a poder citar 'rh-funcionarios' sem ninguém ver.
        // A chave do módulo é o nome do arquivo de config, que ADR-0021 fixa
        // como igual à chave declarada e ao `extra.ht2ml.chave` do pacote.
        foreach (array_keys($config['recursos'] ?? []) as $recurso) {
            $chaves[] = "{$namespace}-{$recurso}";

            // Superset de propósito: um recurso 'sem_lixeira' não tem as duas
            // últimas, mas o core não pode citar nenhuma das seis de qualquer
            // forma, e listar a mais nunca deixa um vazamento passar.
            foreach (['listar', 'criar', 'editar', 'deletar', 'restaurar', 'excluir_permanente'] as $acao) {
                $chaves[] = "{$namespace}.{$recurso}.{$acao}";
            }
        }

        // Formato do core: 'modules' => area => permissao => meta; menu é lista
        // de seções com items e grupos.
        foreach ($config['modules'] ?? [] as $area => $permissoes) {
            $chaves[] = (string) $area;
            $chaves = [...$chaves, ...array_keys((array) $permissoes)];
        }

        foreach ($config as $secao) {
            if (! is_array($secao) || ! isset($secao['key'])) {
                continue;
            }

            $chaves[] = (string) $secao['key'];
            $chaves = [
                ...$chaves,
                ...array_column($secao['items'] ?? [], 'key'),
                ...array_keys($secao['grupos'] ?? []),
            ];
        }

        return array_map(strval(...), $chaves);
    };

    $doCore = [];

    foreach (['access', 'admin-menu'] as $nome) {
        $doCore = [...$doCore, ...$chavesDe((array) require base_path("packages/core/config/{$nome}.php"), $nome)];
    }

    $deExtensao = [];

    foreach (glob(base_path('packages/*/config/*.php')) ?: [] as $arquivo) {
        if (str_contains($arquivo, '/packages/core/')) {
            continue;
        }

        $deExtensao = [...$deExtensao, ...$chavesDe((array) require $arquivo, basename($arquivo, '.php'))];
    }

    return array_values(array_unique(array_diff($deExtensao, $doCore)));
}

/**
 * Literais de string presentes no código-fonte de um diretório.
 *
 * token_get_all, não grep: um grep casaria a palavra dentro de um comentário ou
 * de um nome de método, e o alvo aqui é estritamente o literal.
 *
 * @return array<string, list<string>> literal => arquivos onde aparece
 */
function literaisDeString(string $diretorio): array
{
    $literais = [];

    $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($diretorio));

    foreach ($arquivos as $arquivo) {
        if (! $arquivo instanceof SplFileInfo || $arquivo->getExtension() !== 'php') {
            continue;
        }

        $codigo = (string) file_get_contents($arquivo->getPathname());

        foreach (token_get_all($codigo) as $token) {
            if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $valor = trim($token[1], "'\"");
            $literais[$valor][] = str_replace(base_path() . '/', '', $arquivo->getPathname());
        }
    }

    return $literais;
}

arch('A1 — o core não referencia classe de extensão nenhuma')
    ->expect('HT2ML\Core')
    ->not->toUse(namespacesDeExtensao());

it('A2 — o core não cita chave de extensão nem como literal de string', function (): void {
    $chaves = chavesExclusivasDeExtensao();

    // Meta-checagem: sem ela, um bug na derivação (config vazia, glob errado)
    // faria o guard passar sem nunca ter procurado nada.
    // Uma de cada origem: grupo declarado, key derivada de recurso, permissão
    // derivada de recurso, e permissão ainda escrita à mão numa extensão não
    // migrada. Se a derivação parar de funcionar, cai aqui e não no vazio.
    expect($chaves)->toContain(
        'grupo-tab-rh',
        'rh-funcionarios',
        'rh.funcionarios.listar',
        'cnaes.listar',
    );

    $literais = literaisDeString(base_path('packages/core/src'));

    $vazamentos = [];

    foreach (array_intersect($chaves, array_keys($literais)) as $chave) {
        $vazamentos[] = sprintf('%s → %s', $chave, implode(', ', array_unique($literais[$chave])));
    }

    expect($vazamentos)->toBe([], implode("\n", [
        'O core cita chaves que pertencem a extensões:',
        ...$vazamentos,
        '',
        'A extensão declara o que é dela (permissão, item de menu, grupo, ordem)',
        'na própria config. O core aplica sem saber de quem é. Ver ADR-0022.',
    ]));
});
