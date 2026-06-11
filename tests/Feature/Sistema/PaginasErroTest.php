<?php

declare(strict_types=1);

it('renderiza a página 404 customizada para rota inexistente', function () {
    $this->withoutVite();

    $this->get('/rota-que-nao-existe')
        ->assertNotFound()
        ->assertSee('Página não encontrada');
});

it('renderiza as views de erro customizadas sem depender de sessão ou banco', function (string $view, string $texto) {
    $this->withoutVite();

    expect(view("errors.{$view}")->render())->toContain($texto);
})->with([
    ['403', 'Acesso negado'],
    ['404', 'Página não encontrada'],
    ['500', 'Erro interno'],
    ['503', 'Em manutenção'],
]);
