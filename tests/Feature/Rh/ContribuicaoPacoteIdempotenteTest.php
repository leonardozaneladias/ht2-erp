<?php

declare(strict_types=1);

use HT2ERP\Rh\RhServiceProvider;

/*
|--------------------------------------------------------------------------
| Contribuição de pacote sob config:cache
|--------------------------------------------------------------------------
|
| Com `config:cache` a configuração é fotografada JÁ mesclada — o comando
| reinicializa a aplicação com o cache limpo, os providers de pacote rodam e o
| resultado do merge entra no arquivo serializado. No boot seguinte, o boot()
| do pacote roda de novo sobre o próprio resultado.
|
| Sem idempotência isso duplicava os itens de menu e transformava
| 'label' => 'X' em 'label' => ['X', 'X'] nas permissões — que a matriz de
| acesso então tentava renderizar como string.
|
| Estes testes simulam a segunda aplicação chamando boot() outra vez.
|
*/

it('não duplica os itens de menu ao contribuir duas vezes', function () {
    $secao = fn (): array => collect(config('admin-menu', []))->firstWhere('key', 'negocio')['items'] ?? [];

    $antes = array_column($secao(), 'key');

    (new RhServiceProvider($this->app))->boot();

    $depois = array_column($secao(), 'key');

    expect($depois)->toBe($antes)
        ->and($depois)->toEqual(array_unique($depois));
});

it('não corrompe label e descricao das permissões ao contribuir duas vezes', function () {
    (new RhServiceProvider($this->app))->boot();

    $permissoes = (array) config('access.modules.negocio', []);

    expect($permissoes)->not->toBeEmpty();

    foreach ((array) config('rh.permissoes', []) as $chave => $_) {
        expect($permissoes)->toHaveKey($chave)
            ->and($permissoes[$chave]['label'])->toBeString()
            ->and($permissoes[$chave]['descricao'])->toBeString();
    }
});
