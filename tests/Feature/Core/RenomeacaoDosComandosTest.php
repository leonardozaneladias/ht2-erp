<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| A forma antiga falha ensinando a nova
|--------------------------------------------------------------------------
|
| Em 2026-08-28 os dois geradores trocaram de nome (ADR-0021):
|
|   make:modulo Funcionario --module=Rh   ->  make:recurso Funcionario --modulo=rh
|   make:extensao Rh                      ->  make:modulo rh
|
| O perigo não é o comando sumir: é `make:modulo` continuar existindo com OUTRO
| sentido. Quem digitasse a forma antiga ganharia um pacote chamado
| "funcionario" no lugar de dezenove arquivos, e descobriria depois. Por isso a
| grafia antiga é recusada em vez de interpretada — e a recusa precisa dizer
| qual é o comando certo, senão vira só um erro.
|
| Estes testes existem porque a mensagem é a feature. Um refactor que troque o
| texto por "argumento inválido" passa em qualquer outro teste do repositório.
|
*/

it('make:modulo com nome de entidade recusa e aponta o make:recurso', function (): void {
    $this->artisan('make:modulo', ['chave' => 'Funcionario'])
        ->assertFailed()
        ->expectsOutputToContain('mudou de sentido')
        ->expectsOutputToContain('php artisan make:recurso Funcionario');
});

it('make:modulo com as opções do gerador de CRUD recusa, mesmo com a chave certa', function (): void {
    // 'escola' está em kebab e passaria pela primeira checagem; quem denuncia a
    // forma antiga aqui é o --fields, que só o gerador de recurso tem.
    $this->artisan('make:modulo', ['chave' => 'escola', '--fields' => 'nome:string'])
        ->assertFailed()
        ->expectsOutputToContain('make:recurso');
});

it('make:extensao é lápide: recusa e aponta o make:modulo com a chave em kebab', function (): void {
    $this->artisan('make:extensao', ['nome' => 'FiscalBr'])
        ->assertFailed()
        ->expectsOutputToContain('foi removido')
        ->expectsOutputToContain('php artisan make:modulo fiscal-br');
});

it('make:recurso recusa --module e aponta --modulo', function (): void {
    $this->artisan('make:recurso', ['nome' => 'Aluno', '--module' => 'Rh'])
        ->assertFailed()
        ->expectsOutputToContain('--modulo');
});

it('make:recurso exige que o módulo exista, e diz como criá-lo', function (): void {
    $this->artisan('make:recurso', ['nome' => 'Aluno', '--modulo' => 'inexistente'])
        ->assertFailed()
        ->expectsOutputToContain('php artisan make:modulo inexistente');
});
