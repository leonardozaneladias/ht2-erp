<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\ModuleRegistry;
use HT2ML\Rh\RhServiceProvider;

/*
|--------------------------------------------------------------------------
| Contribuição de extensão sob config:cache
|--------------------------------------------------------------------------
|
| Com `config:cache` a configuração é fotografada JÁ mesclada — o comando
| reinicializa a aplicação com o cache limpo, os providers rodam e o resultado
| entra no arquivo serializado. No boot seguinte, a aplicação roda de novo
| sobre o próprio resultado.
|
| Sem idempotência isso duplicava os itens de menu e transformava
| 'label' => 'X' em 'label' => ['X', 'X'] nas permissões — que a matriz de
| acesso então tentava renderizar como string.
|
| Aqui a segunda aplicação é simulada chamando aplicarContribuicoes() de novo.
|
*/

it('não duplica os itens de menu ao aplicar duas vezes', function () {
    $itens = fn (): array => array_column(
        collect(config('admin-menu', []))->firstWhere('key', 'negocio')['items'] ?? [],
        'key',
    );

    $antes = $itens();

    (new RhServiceProvider($this->app))->boot();
    ModuleRegistry::aplicarContribuicoes();

    $depois = $itens();

    expect($depois)->toBe($antes)
        ->and($depois)->toEqual(array_unique($depois));
});

it('não corrompe label e descricao das permissões ao aplicar duas vezes', function () {
    (new RhServiceProvider($this->app))->boot();
    ModuleRegistry::aplicarContribuicoes();

    $permissoes = (array) config('access.modules.negocio', []);

    expect($permissoes)->not->toBeEmpty();

    foreach ((array) config('rh.permissoes', []) as $chave => $_) {
        expect($permissoes)->toHaveKey($chave)
            ->and($permissoes[$chave]['label'])->toBeString()
            ->and($permissoes[$chave]['descricao'])->toBeString();
    }
});

it('recusa módulo de acesso inexistente', function () {
    ModuleRegistry::permissoes('modulo-que-nao-existe', ['x' => ['label' => 'X']]);
})->throws(InvalidArgumentException::class, 'Módulo de acesso desconhecido');

it('respeita o módulo declarado pela extensão', function () {
    ModuleRegistry::flush();
    ModuleRegistry::permissoes(
        HT2ML\Core\Enums\ModuloAcesso::TabelasAuxiliares,
        ['ref.teste.listar' => ['label' => 'Listar teste', 'descricao' => 'Teste.']],
    );
    ModuleRegistry::aplicarContribuicoes();

    expect(config('access.modules.tabelas_auxiliares'))->toHaveKey('ref.teste.listar')
        ->and(config('access.modules.negocio'))->not->toHaveKey('ref.teste.listar');

    ModuleRegistry::flush();
});
