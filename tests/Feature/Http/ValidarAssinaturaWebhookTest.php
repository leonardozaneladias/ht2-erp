<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Assinatura de webhook
|--------------------------------------------------------------------------
|
| Um webhook é uma rota sem autenticação por definição: a assinatura é a única
| coisa entre o mundo e "marcar como pago". Os casos que importam são os que
| fazem a checagem não checar nada — segredo vazio, header ausente, header
| com prefixo de algoritmo.
|
*/

beforeEach(function (): void {
    config(['servicos.teste.segredo' => 'segredo-de-teste']);

    Route::post('/webhooks/teste', fn (): string => 'aceito')
        ->middleware('webhook.assinatura:servicos.teste.segredo');

    Route::post('/webhooks/teste-header', fn (): string => 'aceito')
        ->middleware('webhook.assinatura:servicos.teste.segredo,X-Hub-Signature-256');
});

function assinar(string $corpo, string $segredo = 'segredo-de-teste'): string
{
    return hash_hmac('sha256', $corpo, $segredo);
}

it('aceita o corpo assinado com o segredo da config', function (): void {
    $corpo = '{"evento":"pago","id":42}';

    $this->call('POST', '/webhooks/teste', server: ['HTTP_X_WEBHOOK_SIGNATURE' => assinar($corpo)], content: $corpo)
        ->assertOk()
        ->assertSee('aceito');
});

it('recusa assinatura errada com 401', function (): void {
    $corpo = '{"evento":"pago","id":42}';

    $this->call('POST', '/webhooks/teste', server: ['HTTP_X_WEBHOOK_SIGNATURE' => assinar($corpo, 'outro-segredo')], content: $corpo)
        ->assertUnauthorized();
});

it('recusa requisição sem header de assinatura com 401', function (): void {
    $this->call('POST', '/webhooks/teste', content: '{}')
        ->assertUnauthorized();
});

it('assina o corpo bruto: o mesmo JSON reordenado não passa', function (): void {
    $assinado = '{"id":42,"evento":"pago"}';
    $enviado = '{"evento":"pago","id":42}';

    $this->call('POST', '/webhooks/teste', server: ['HTTP_X_WEBHOOK_SIGNATURE' => assinar($assinado)], content: $enviado)
        ->assertUnauthorized();
});

it('sem segredo configurado recusa tudo com 503 e avisa o operador', function (): void {
    Exceptions::fake();
    config(['servicos.teste.segredo' => '']);

    // Validar contra o segredo vazio daria um HMAC válido: a checagem
    // aceitaria qualquer um que soubesse disso.
    $corpo = '{}';

    $this->call('POST', '/webhooks/teste', server: ['HTTP_X_WEBHOOK_SIGNATURE' => assinar($corpo, '')], content: $corpo)
        ->assertStatus(503);

    Exceptions::assertReported(fn (RuntimeException $e): bool => str_contains($e->getMessage(), 'servicos.teste.segredo'));
});

it('lê o header que a rota declarar, com ou sem prefixo sha256=', function (): void {
    $corpo = '{"acao":"push"}';

    $this->call('POST', '/webhooks/teste-header', server: ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . assinar($corpo)], content: $corpo)
        ->assertOk();

    $this->call('POST', '/webhooks/teste-header', server: ['HTTP_X_HUB_SIGNATURE_256' => assinar($corpo)], content: $corpo)
        ->assertOk();

    // O header padrão não vale numa rota que declarou outro.
    $this->call('POST', '/webhooks/teste-header', server: ['HTTP_X_WEBHOOK_SIGNATURE' => assinar($corpo)], content: $corpo)
        ->assertUnauthorized();
});
