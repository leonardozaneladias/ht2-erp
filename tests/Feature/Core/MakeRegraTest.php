<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| make:regra
|--------------------------------------------------------------------------
|
| O `make:rule` do Laravel só escreve em app/Rules/: não conhece pacote nem
| módulo. Num produto que instala a plataforma por Composer, a regra de domínio
| precisa nascer ao lado do domínio.
|
| O que este teste guarda, além de "o arquivo foi criado", é a DIREÇÃO em que a
| regra falha antes de alguém implementá-la. Uma regra recém-gerada que aceita
| tudo passa a valer em toda tela que declarar o campo e não dá sinal nenhum —
| descobre-se quando alguém nota que o campo nunca validou nada. Por isso ela
| nasce recusando, e é isto que precisa continuar verdade.
|
*/

afterEach(function (): void {
    File::delete([
        base_path('app/Rules/RegraDeTeste.php'),
        base_path('tests/Unit/Rules/RegraDeTesteTest.php'),
    ]);

    // Só os arquivos. Apagar o diretório vazio parecia limpeza e era estrago:
    // File::files() ignora dotfiles, então app/Rules/ — que o repositório mantém
    // com um .gitkeep — parecia vazio, e a suíte apagava um arquivo rastreado.
});

it('gera a regra e o teste dela no produto', function (): void {
    $this->artisan('make:regra', ['nome' => 'RegraDeTeste'])->assertSuccessful();

    expect(File::isFile(base_path('app/Rules/RegraDeTeste.php')))->toBeTrue()
        ->and(File::isFile(base_path('tests/Unit/Rules/RegraDeTesteTest.php')))->toBeTrue();
});

it('a regra recém-gerada RECUSA, para não passar despercebida', function (): void {
    $this->artisan('make:regra', ['nome' => 'RegraDeTeste'])->assertSuccessful();

    require_once base_path('app/Rules/RegraDeTeste.php');

    /** @var Illuminate\Contracts\Validation\ValidationRule $regra */
    $regra = new ('App\\Rules\\RegraDeTeste')();

    $validador = Validator::make(['campo' => 'qualquer coisa'], ['campo' => [$regra]]);

    expect($validador->fails())->toBeTrue()
        ->and($validador->errors()->first('campo'))->toBe('O campo não é válido.');
});

it('o teste gerado nasce como todo, para não quebrar a suíte', function (): void {
    $this->artisan('make:regra', ['nome' => 'RegraDeTeste'])->assertSuccessful();

    // ->todo() é o que permite a regra nascer recusando tudo SEM deixar a suíte
    // vermelha: os dois casos não rodam até alguém escrevê-los.
    expect((string) File::get(base_path('tests/Unit/Rules/RegraDeTesteTest.php')))
        ->toContain('->todo(');
});

it('recusa sobrescrever sem --force', function (): void {
    $this->artisan('make:regra', ['nome' => 'RegraDeTeste'])->assertSuccessful();

    $this->artisan('make:regra', ['nome' => 'RegraDeTeste'])
        ->assertFailed()
        ->expectsOutputToContain('--force');
});

it('exige que o módulo exista, e diz como criá-lo', function (): void {
    $this->artisan('make:regra', ['nome' => 'RegraDeTeste', '--modulo' => 'inexistente'])
        ->assertFailed()
        ->expectsOutputToContain('php artisan make:modulo inexistente');
});
