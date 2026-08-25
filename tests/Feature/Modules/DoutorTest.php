<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\ModuleRegistry;

/*
|--------------------------------------------------------------------------
| ht2ml:doutor — a convenção vira pergunta com resposta binária
|--------------------------------------------------------------------------
|
| Cada caso aqui planta UMA violação e exige que o comando reprove com uma
| mensagem que diga o que fazer. Um comando de diagnóstico que só sabe dizer
| "tudo certo" é pior que não ter comando: ele dá confiança sem base.
|
*/

afterEach(fn () => ModuleRegistry::flush());

it('passa no repositório como ele está', function (): void {
    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('Tudo fecha')
        ->assertExitCode(0);
});

it('reprova permissões numa área que não existe', function (): void {
    config(['access.modules.escola' => ['escola.alunos.listar' => ['label' => 'Listar alunos']]]);

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain("não existe em config('access.areas')")
        ->assertExitCode(1);
});

it('reprova item de menu apontando para grupo que ninguém declara', function (): void {
    config(['admin-menu.0.items.0.grupo' => 'grupo-fantasma']);

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('grupo-fantasma')
        ->assertExitCode(1);
});

it('reprova item de menu exigindo permissão fora do catálogo', function (): void {
    config(['admin-menu.0.items.0.permission' => 'escola.alunos.listar']);

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('O gate nega para todo mundo exceto super-admin')
        ->assertExitCode(1);
});

it('reprova item de menu apontando para rota inexistente', function (): void {
    config(['admin-menu.0.items.0.route' => 'admin.escola.alunos.index']);

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('a página inteira cai')
        ->assertExitCode(1);
});

it('reprova ícone fora da lista curada, no item e no grupo', function (): void {
    config([
        'admin-menu.0.items.0.icon' => 'tabler--nao-existe',
        'admin-menu.0.grupos' => ['grupo-x' => ['label' => 'X', 'icone' => 'tabler--tambem-nao']],
    ]);

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('tabler--nao-existe')
        ->expectsOutputToContain('tabler--tambem-nao')
        ->assertExitCode(1);
});

it('reporta as contribuições que o registry descartou no boot', function (): void {
    // Em produção estas só chegavam ao log. Aqui ganham superfície de comando.
    app()->detectEnvironment(fn (): string => 'production');

    ModuleRegistry::permissoes('area-fantasma', ['x.listar' => ['label' => 'X']]);
    ModuleRegistry::aplicarContribuicoes();

    $this->artisan('ht2ml:doutor')
        ->expectsOutputToContain('contribuição descartada')
        ->expectsOutputToContain('area-fantasma')
        ->assertExitCode(1);
});

it('emite JSON com o mesmo veredito, para o CI consumir', function (): void {
    config(['access.modules.escola' => ['escola.alunos.listar' => ['label' => 'Listar alunos']]]);

    $this->artisan('ht2ml:doutor --json')->assertExitCode(1);
});
