<?php

declare(strict_types=1);

use HT2ML\Api\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| O openapi.json versionado e o portão que o mantém honesto
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->arquivo = 'storage/framework/testing/openapi-' . uniqid() . '.json';
    config(['api.openapi.arquivo' => $this->arquivo]);

    Route::prefix('api')->middleware('api')->group(function (): void {
        Route::post('/v1/contatos', function (Request $request) {
            $dados = $request->validate(['nome' => ['required', 'string']]);

            return ApiResponse::sucesso($dados, status: 201);
        })->middleware('auth:sanctum');
    });
});

afterEach(function (): void {
    @unlink(base_path($this->arquivo));
});

it('grava o arquivo e o --verificar aceita o que acabou de ser gerado', function (): void {
    $this->artisan('api:openapi')->assertSuccessful();

    expect(base_path($this->arquivo))->toBeFile();

    $this->artisan('api:openapi', ['--verificar' => true])->assertSuccessful();
});

it('o --verificar reprova quando o arquivo não descreve mais a API', function (): void {
    $this->artisan('api:openapi')->assertSuccessful();

    $documento = json_decode((string) file_get_contents(base_path($this->arquivo)), true);
    unset($documento['paths']);
    file_put_contents(base_path($this->arquivo), json_encode($documento));

    $this->artisan('api:openapi', ['--verificar' => true])->assertFailed();
});

it('o --verificar ignora servers, que vem do APP_URL de quem gerou', function (): void {
    $this->artisan('api:openapi')->assertSuccessful();

    $documento = json_decode((string) file_get_contents(base_path($this->arquivo)), true);
    $documento['servers'] = [['url' => 'https://outra-maquina.test/api']];
    file_put_contents(base_path($this->arquivo), json_encode($documento));

    $this->artisan('api:openapi', ['--verificar' => true])->assertSuccessful();
});

it('o --verificar reprova sem arquivo versionado', function (): void {
    $this->artisan('api:openapi', ['--verificar' => true])->assertFailed();
});

it('as respostas de erro documentadas são o envelope, não o formato do Laravel', function (): void {
    $this->artisan('api:openapi')->assertSuccessful();

    $respostas = json_decode((string) file_get_contents(base_path($this->arquivo)), true)['components']['responses'] ?? [];

    expect($respostas)->not->toBeEmpty();

    foreach ($respostas as $nome => $resposta) {
        $schema = $resposta['content']['application/json']['schema'];

        expect($schema['required'] ?? [])->toBe(['success', 'message', 'data', 'errors'], "resposta {$nome}");
    }

    // O `errors` da validação mantém o detalhe por campo que o Scramble inferiu.
    $validacao = collect($respostas)->first(fn (array $r): bool => str_contains(json_encode($r), 'Validation'));
    expect($validacao['content']['application/json']['schema']['properties']['errors']['type'] ?? null)->toBe('object');
});
