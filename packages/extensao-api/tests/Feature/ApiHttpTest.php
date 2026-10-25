<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fixtures/UsuarioDeApi.php';

use HT2ML\Api\Http\Middleware\DefinirIdioma;
use HT2ML\Api\Support\ApiResponse;
use HT2ML\Api\Tests\Fixtures\UsuarioDeApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| O que um app recebe de uma rota de API
|--------------------------------------------------------------------------
|
| Rotas sob /api, no grupo `api` — o mesmo que o canal EscopoDeRota::Api usa.
| Cada caso aqui é uma resposta que um app já recebeu errada em algum produto:
| 302 para o HTML do login, 405 em inglês, 403 com a mensagem do framework.
|
*/

beforeEach(function (): void {
    // As mensagens abaixo são as de pt_BR. O Request::create() do Symfony
    // manda `Accept-Language: en-us` em toda requisição de teste, e o header é
    // a fonte de maior precedência — sem fixá-lo aqui, tudo sairia em inglês.
    config(['app.locale' => 'pt_BR']);
    $this->withHeader('Accept-Language', 'pt-BR');

    Route::prefix('api')->middleware('api')->group(function (): void {
        Route::get('/livre', fn () => ApiResponse::sucesso(['locale' => app()->getLocale()]));
        Route::get('/protegida', fn () => ApiResponse::sucesso(['ok' => true]))->middleware('auth:sanctum');
        Route::get('/ativa', fn () => ApiResponse::sucesso(['ok' => true]))->middleware(['auth:sanctum', 'api.principal-ativo']);
        Route::get('/proibida', fn () => throw new AuthorizationException);
        Route::get('/sumida', fn () => throw new ModelNotFoundException);
        Route::post('/so-post', fn () => ApiResponse::sucesso());
        Route::get('/abortada', fn () => abort(409, 'Conflito escrito pelo produto.'));
        Route::get('/quebrada', fn () => throw new RuntimeException('detalhe interno'));
        Route::post('/validada', fn (Illuminate\Http\Request $r) => ApiResponse::sucesso($r->validate(['nome' => 'required'])));
    });
});

it('sem Accept e sem token devolve 401 em JSON, não 302 para o login do painel', function (): void {
    $this->get('/api/protegida')
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Não autenticado.', 'data' => [], 'errors' => []]);
});

it('403 sai no envelope e traduzido, apesar do prepareException do Laravel', function (): void {
    $this->getJson('/api/proibida')
        ->assertForbidden()
        ->assertJson(['success' => false, 'message' => 'Não autorizado.']);
});

it('404 de model sai no envelope', function (): void {
    $this->getJson('/api/sumida')
        ->assertNotFound()
        ->assertJson(['success' => false, 'message' => 'Registro não encontrado.']);
});

it('405 sai com a mensagem do produto, não a enumeração do Symfony', function (): void {
    $this->getJson('/api/so-post')
        ->assertStatus(405)
        ->assertJson(['success' => false, 'message' => 'Método não permitido para este endereço.']);
});

it('405 e 404 de rota inexistente saem no idioma do header, mesmo nascendo antes do grupo api', function (): void {
    $this->getJson('/api/so-post', ['Accept-Language' => 'es-AR,es;q=0.9'])
        ->assertJson(['message' => 'Método no permitido para esta dirección.']);

    $this->getJson('/api/nao-existe', ['Accept-Language' => 'en'])
        ->assertNotFound()
        ->assertJson(['message' => 'Record not found.']);
});

it('abort() com mensagem do produto preserva status e mensagem', function (): void {
    $this->getJson('/api/abortada')
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => 'Conflito escrito pelo produto.']);
});

it('422 traz os erros por campo dentro do envelope', function (): void {
    $this->postJson('/api/validada', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['nome']]);
});

it('500 fora de debug sai no envelope, sem vazar o detalhe', function (): void {
    config(['app.debug' => false]);

    $this->getJson('/api/quebrada')
        ->assertStatus(500)
        ->assertJson(['success' => false, 'message' => 'Erro interno. Tente novamente mais tarde.'])
        ->assertDontSee('detalhe interno');
});

it('fora de /api o envelope não se mete', function (): void {
    Route::get('/fora', fn () => throw new ModelNotFoundException);

    $resposta = $this->getJson('/fora')->assertNotFound();

    expect($resposta->json())->not->toHaveKey('success');
});

it('o idioma vem do header, depois da preferência do usuário, depois do default', function (): void {
    // Sem header: o default da aplicação.
    $this->withHeader('Accept-Language', '');
    expect($this->getJson('/api/livre')->json('data.locale'))->toBe('pt_BR');

    expect($this->getJson('/api/livre', ['Accept-Language' => 'en'])->json('data.locale'))->toBe('en');

    // Sem header, com usuário: a preferência salva.
    Sanctum::actingAs(UsuarioDeApi::com(1, idioma: 'es'));
    expect($this->getJson('/api/livre')->json('data.locale'))->toBe('es');

    // O header ganha da preferência salva.
    expect($this->getJson('/api/livre', ['Accept-Language' => 'en-US'])->json('data.locale'))->toBe('en');
});

it('idioma fora da lista é ignorado, e a lista é a da config', function (): void {
    expect($this->getJson('/api/livre', ['Accept-Language' => 'fr'])->json('data.locale'))->toBe('pt_BR')
        ->and(DefinirIdioma::idiomasSuportados())->toBe(['pt', 'pt_BR', 'en', 'es']);
});

it('usuário desativado é barrado na requisição seguinte, no envelope', function (): void {
    Sanctum::actingAs(UsuarioDeApi::com(2, ativo: false));

    $this->getJson('/api/ativa')
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Conta desativada.', 'data' => [], 'errors' => []]);

    Sanctum::actingAs(UsuarioDeApi::com(3));

    $this->getJson('/api/ativa')->assertOk();
});
