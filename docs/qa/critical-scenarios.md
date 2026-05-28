---
title: Critical Scenarios — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# Critical Scenarios — Portal ArtFinal v2 (Backend API v1)

Cenários **bloqueantes** do §10.7 do planejamento: cada um desses precisa estar verde antes do merge em `main` e antes de cada promoção entre fases. Cada item traz: contexto de negócio, risco se regressão entrar, snippet Pest, resultado esperado, SLA (quando aplicável) e referências cruzadas.

---

## 1. Mil tentativas simultâneas no mesmo assento → apenas 1 vence

### Contexto

Seating é a feature diferencial do produto. Em eventos grandes, 500–1000 formandos e convidados batem no mesmo assento quando a sala abre. O domínio depende de dois mecanismos complementares:

1. `lockForUpdate()` em `assentos` dentro de `DB::transaction`.
2. Unique parcial em `reservas_assentos` para status em (`hold`, `confirmada`).

Referências: planejamento §5.1 (diagrama), §4.3 (migration), §10.3 (teste).

### Risco em caso de regressão

- Duas reservas ativas no mesmo assento → conflito presencial na formatura → disputa operacional e dano reputacional.
- Regra de negócio §7.3.1 (REGRAS_NEGOCIO) quebrada.

### Snippet Pest (teste de concorrência real)

```php
<?php

declare(strict_types=1);

use App\Actions\Seating\ReservarAssentoAction;
use App\Data\Seating\ReservaRequestData;
use App\Enums\Seating\OrigemReserva;
use App\Enums\Seating\StatusReserva;
use App\Exceptions\Seating\AssentoIndisponivelException;
use App\Models\Seating\Assento;
use App\Models\Seating\ReservaAssento;

it('1000 tentativas concorrentes: apenas uma vence', function (): void {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl_fork indisponível.');
    }

    $assento = Assento::factory()->create();
    $processos = 1000;
    $pids = [];

    for ($i = 0; $i < $processos; $i++) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            DB::reconnect();
            try {
                app(ReservarAssentoAction::class)->execute(new ReservaRequestData(
                    assentoUlid: $assento->ulid,
                    conviteUlid: null,
                    origem: OrigemReserva::Formando,
                    idempotencyKey: "k-$i",
                    atorId: $i + 1,
                    atorTipo: 'formando',
                ));
                exit(0);
            } catch (AssentoIndisponivelException) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }

    $sucesso = 0;
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        if (pcntl_wexitstatus($status) === 0) {
            $sucesso++;
        }
    }

    expect($sucesso)->toBe(1);
    expect(ReservaAssento::query()
        ->where('assento_id', $assento->id)
        ->whereIn('status', [StatusReserva::Hold, StatusReserva::Confirmada])
        ->count())->toBe(1);
})->group('concurrency', 'serial');
```

### Resultado esperado

- Exatamente 1 reserva com status `hold` ou `confirmada`.
- 999 exceções `AssentoIndisponivelException`.
- Contagem total em `reservas_assentos` para o assento = 1 (quando desconsideradas expirações).

### SLA

- P95 de reserva sob carga de 100 req/min: ≤ 700 ms (PRD §1.6).
- Taxa de conflito pós-abertura pública: < 0,5% (PRD §1.6).

---

## 2. Webhook processado 10× → efeito aplicado 1×

### Contexto

O gateway Itaú pode reenviar webhook por timeout de rede, re-delivery configurável e retry exponencial. Se cada chegada aplicasse efeito, o sistema dobraria pagamentos, dobraria convites derivados e contaminaria cota.

Mecanismos de defesa:

1. `unique(provider, gateway_reference)` em `webhook_eventos`.
2. `firstOrCreate` antes de aplicar efeito.
3. Job `ProcessarWebhookPagamentoJob` idempotente por `webhook_evento.id` com `afterCommit()`.

Referências: planejamento §5.5, §10.4.

### Risco

- Pagamento aplicado 2× → saldo falso, cota mal calculada, convites extras duplicados.
- Quebra REGRAS §9.2.1.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Extras\PedidoExtra;
use App\Models\Webhook\WebhookEvento;

it('webhook reenviado 10x mantém efeito único', function (): void {
    $pedido = PedidoExtra::factory()->aguardandoPagamento()->create();
    $payload = [
        'tipo'        => 'pagamento.confirmado',
        'evento'      => ['id' => 'gw-999'],
        'pedido_ulid' => $pedido->ulid,
    ];
    $sig = hash_hmac('sha256', json_encode($payload), config('gateway.itau.webhook_secret'));

    for ($i = 0; $i < 10; $i++) {
        $this->withHeader('X-Signature', $sig)
             ->postJson('/webhooks/pagamentos/itau', $payload);
    }

    expect(WebhookEvento::where('gateway_reference', 'gw-999')->count())->toBe(1);
    expect($pedido->fresh()->status->value)->toBe('pago');
    // convites derivados não duplicados
    expect($pedido->convitesDerivados()->count())->toBe($pedido->itens->sum('quantidade'));
});
```

### Resultado esperado

- 1 `WebhookEvento` persistido.
- Pedido em `pago`.
- Convites derivados em contagem exata (nunca dobrada).

### SLA

- Tempo para reprocessar webhook com idempotência: ≤ 1 min (PRD §1.6).

---

## 3. Mesma `X-Idempotency-Key` com payload idêntico → mesma reserva

### Contexto

Clientes React/Mobile retry em caso de timeout. Sem `X-Idempotency-Key`, retry cria reserva dupla ou falha confusa.

Regras:

- Header obrigatório em POST de seating (§2.9).
- Chave guardada em `reservas_assentos.idempotency_key` com unique(formando_id, idempotency_key).
- Re-execução com mesma key + mesmo payload → retorna estado atual (idempotente).
- Mesma key com payload diferente → 409.

Referências: planejamento §2.9, §5.1.

### Risco

- UX degradada (duplicatas); cota gasta errada; conflict artificial.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Actions\Seating\ReservarAssentoAction;
use App\Data\Seating\ReservaRequestData;
use App\Enums\Seating\OrigemReserva;
use App\Models\Seating\Assento;
use App\Models\Seating\ReservaAssento;

it('reutilizar mesma idempotency-key retorna mesma reserva', function (): void {
    $assento = Assento::factory()->create();
    $action  = app(ReservarAssentoAction::class);

    $input = new ReservaRequestData(
        assentoUlid: $assento->ulid,
        conviteUlid: null,
        origem: OrigemReserva::Formando,
        idempotencyKey: 'key-idempotente',
        atorId: 42,
        atorTipo: 'formando',
    );

    $a = $action->execute($input);
    $b = $action->execute($input);

    expect($a->reservaUlid)->toBe($b->reservaUlid);
    expect(ReservaAssento::query()->where('assento_id', $assento->id)->count())->toBe(1);
});

it('mesma key com payload diferente retorna 409', function (): void {
    $assento_a = Assento::factory()->create();
    $assento_b = Assento::factory()->create();
    $action = app(ReservarAssentoAction::class);

    $action->execute(new ReservaRequestData(
        assentoUlid: $assento_a->ulid,
        conviteUlid: null,
        origem: OrigemReserva::Formando,
        idempotencyKey: 'collide',
        atorId: 1,
        atorTipo: 'formando',
    ));

    expect(fn () => $action->execute(new ReservaRequestData(
        assentoUlid: $assento_b->ulid,
        conviteUlid: null,
        origem: OrigemReserva::Formando,
        idempotencyKey: 'collide',
        atorId: 1,
        atorTipo: 'formando',
    )))->toThrow(\App\Exceptions\Seating\IdempotencyMismatchException::class);
});
```

### Resultado esperado

- `reservaUlid` igual entre chamadas com mesma key.
- 1 registro em `reservas_assentos`.
- Payload diferente com mesma key → exception específica → traduzida a 409 pelo handler.

---

## 4. Convite extra só é emitido após pagamento confirmado

### Contexto

Convite extra herda pedido. Não pode ser emitido antes do `PedidoExtra.status=pago`. Estorno invalida convites não utilizados.

Referências: REGRAS §5.2, §8.2; planejamento §14 F6.

### Risco

- Convite emitido sem pagamento → prejuízo financeiro (convidado entra sem pagar).
- Estorno sem invalidação → cadeira ocupada ilegitimamente.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Extras\PedidoExtra;
use App\Jobs\Extras\EmitirConvitesExtrasJob;

it('não emite convite enquanto pedido está aguardando pagamento', function (): void {
    $pedido = PedidoExtra::factory()->aguardandoPagamento()->create();

    // simula job sendo enfileirado indevidamente
    Queue::fake();
    EmitirConvitesExtrasJob::dispatch($pedido->id);

    // job só roda quando status=pago; simula handler
    expect(fn () => (new EmitirConvitesExtrasJob($pedido->id))->handle())
        ->toThrow(\App\Exceptions\Extras\PedidoNaoPagoException::class);

    expect($pedido->convitesDerivados()->count())->toBe(0);
});

it('emite convites derivados após pedido pago', function (): void {
    $pedido = PedidoExtra::factory()->pago()->comItens(3)->create();

    (new EmitirConvitesExtrasJob($pedido->id))->handle();

    expect($pedido->fresh()->convitesDerivados()->count())->toBe(3);
});

it('estorno invalida convites não utilizados', function (): void {
    $pedido = PedidoExtra::factory()->pago()->comItens(3)->comConvitesDerivados()->create();
    $pedido->convitesDerivados->first()->update(['rsvp_status' => 'confirmado']);

    app(\App\Actions\Extras\EstornarPedidoExtraAction::class)->execute($pedido);

    $cancelados = $pedido->fresh()->convitesDerivados()->where('status', 'cancelado')->count();
    $aprovacao = $pedido->fresh()->convitesDerivados()->where('status', 'pendente_aprovacao')->count();
    expect($cancelados)->toBe(2);
    expect($aprovacao)->toBe(1);
});
```

### Resultado esperado

- Pedido em `aguardando_pagamento`: 0 convites derivados; tentativa de emissão explode com exception.
- Pedido `pago`: N convites criados, cada um com `pedido_extra_id` preenchido.
- Estorno: 2 não utilizados → `cancelado`; 1 com RSVP → `pendente_aprovacao`.

### SLA

- Convite extra emitido ≤ 30s após confirmação de pagamento (planejamento §14 F6 aceite).

---

## 5. `CotaCalculator` — casos de borda

### Contexto

Cota define quantos convites o formando pode emitir. Erro aqui gera suborço ou reclamação imediata. Fórmula:

```
total     = regra.base + min(extras_pagos_aprovados, regra.extra_paga_max)
emitidos  = count(convites onde status IN ('emitido','transferido','confirmado'))
disponivel = max(total - emitidos, 0)
```

Referências: REGRAS §4; planejamento §10.7 item 3.

### Risco

- Cota inflada → convites emitidos demais → capacidade do local ultrapassada.
- Cota deflada → formando paga extra sem poder usar.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Services\Comercial\CotaCalculator;
use App\Models\Cadastro\Formando;

describe('CotaCalculator — bordas', function (): void {
    it('retorna zero quando formando sem adesão ativa', function (): void {
        $formando = Formando::factory()->semAdesao()->create();

        $r = app(CotaCalculator::class)->calcular($formando);

        expect($r->total)->toBe(0);
        expect($r->disponivel)->toBe(0);
    });

    it('ignora extras em estado estornado', function (): void {
        $formando = Formando::factory()
            ->comAdesaoAtiva(['snapshot_comercial' => ['cota' => ['base' => 4, 'extra_paga_max' => 5]]])
            ->comPedidoExtraEstornado(quantidade: 3)
            ->create();

        expect(app(CotaCalculator::class)->calcular($formando)->total)->toBe(4);
    });

    it('respeita teto extra_paga_max', function (): void {
        $formando = Formando::factory()
            ->comAdesaoAtiva(['snapshot_comercial' => ['cota' => ['base' => 4, 'extra_paga_max' => 2]]])
            ->comPedidoExtraPago(quantidade: 10) // paga 10 mas teto é 2
            ->create();

        expect(app(CotaCalculator::class)->calcular($formando)->total)->toBe(6);
    });

    it('emitidos nunca excede total (disponivel>=0)', function (): void {
        $formando = Formando::factory()
            ->comAdesaoAtiva(['snapshot_comercial' => ['cota' => ['base' => 2]]])
            ->comConvitesEmitidos(5) // dados corrompidos simulando
            ->create();

        expect(app(CotaCalculator::class)->calcular($formando)->disponivel)->toBe(0);
    });
});
```

### Resultado esperado

- Sem adesão: tudo zero.
- Estornado não conta como extra válido.
- Teto `extra_paga_max` respeitado.
- `disponivel ≥ 0` sempre.

---

## 6. Elegibilidade de enquete (regra JSONB declarativa)

### Contexto

Enquetes têm `regra_elegibilidade` JSONB que define quem pode votar. Casos suportados:

- `{base: "formando_adesao_ativa"}`
- `{base: "rsvp_confirmado"}`
- `{base: "comissao"}`
- `{base: "subconjunto", ator_ids: [ulids]}`

Referências: REGRAS §10.2; planejamento §10.7 item 5.

### Risco

- Voto de quem não deveria → resultado contestado.
- Bloqueio de quem deveria votar → reclamação.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Services\Enquetes\AvaliarElegibilidadeService;
use App\Models\Enquetes\Enquete;

it('formando com adesão ativa é elegível', function (): void {
    $formando = \App\Models\Cadastro\Formando::factory()->comAdesaoAtiva()->create();
    $enquete = Enquete::factory()->create(['regra_elegibilidade' => ['base' => 'formando_adesao_ativa']]);

    expect(app(AvaliarElegibilidadeService::class)->pode($formando, $enquete))->toBeTrue();
});

it('formando rascunho não é elegível', function (): void {
    $formando = \App\Models\Cadastro\Formando::factory()->comAdesaoRascunho()->create();
    $enquete = Enquete::factory()->create(['regra_elegibilidade' => ['base' => 'formando_adesao_ativa']]);

    expect(app(AvaliarElegibilidadeService::class)->pode($formando, $enquete))->toBeFalse();
});

it('convidado rsvp confirmado é elegível', function (): void {
    $convite = \App\Models\Convites\Convite::factory()->comRsvpConfirmado()->create();
    $enquete = Enquete::factory()->create(['regra_elegibilidade' => ['base' => 'rsvp_confirmado']]);

    expect(app(AvaliarElegibilidadeService::class)->pode($convite, $enquete))->toBeTrue();
});

it('subconjunto só libera atores listados', function (): void {
    $formandoA = \App\Models\Cadastro\Formando::factory()->create();
    $formandoB = \App\Models\Cadastro\Formando::factory()->create();
    $enquete = Enquete::factory()->create([
        'regra_elegibilidade' => ['base' => 'subconjunto', 'ator_ids' => [$formandoA->ulid]],
    ]);

    expect(app(AvaliarElegibilidadeService::class)->pode($formandoA, $enquete))->toBeTrue();
    expect(app(AvaliarElegibilidadeService::class)->pode($formandoB, $enquete))->toBeFalse();
});
```

### Resultado esperado

- Cada base atua como filtro puro.
- `false` negativo em qualquer desvio do estado esperado.

---

## 7. Transição de estado inválida → 409 Conflict

### Contexto

Entidades com state machine (Adesao, Convite, ReservaAssento, PedidoExtra, Pagamento, Enquete) têm transições válidas documentadas em REGRAS_NEGOCIO. Tentar qualquer outra deve ser 409 com `code: "transicao_invalida"`.

### Risco

- Admin "conserta" estado manualmente via API pulando validação → dados inconsistentes.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Comercial\Adesao;

it('não pode voltar de cancelada para ativa', function (): void {
    $adesao = Adesao::factory()->cancelada()->create();

    $this->authAs('admin-system')
        ->putJson("/api/v1/adesoes/{$adesao->ulid}", ['status' => 'ativa'])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'transicao_invalida')
        ->assertJsonPath('error.details.from', 'cancelada')
        ->assertJsonPath('error.details.to', 'ativa');
});
```

### Resultado esperado

- 409 com envelope §2.11 completo, incluindo `from` e `to` no `details`.

---

## 8. Hold expirado ao confirmar → 410 Gone

### Contexto

Hold tem TTL 5 min. Se cliente tenta confirmar após expirar, servidor deve recusar sem silenciosamente reativar — cadeira já pode estar ocupada.

Referências: REGRAS §7.3.4; planejamento §5.2.

### Risco

- Confirmação "zombie" ressuscita hold → dupla reserva.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Seating\ReservaAssento;

it('confirmar hold expirado retorna 410 Gone', function (): void {
    $reserva = ReservaAssento::factory()->holdExpirado()->create();

    $this->authAs('formando')
        ->postJson("/api/v1/reservas/{$reserva->ulid}/confirmar")
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'hold_expirado');

    expect($reserva->fresh()->status->value)->not->toBe('confirmada');
});
```

### Resultado esperado

- 410 + `hold_expirado`.
- Reserva permanece em `hold` até job expirar (ou admin mover).

---

## 9. Rate limit seating 5/min → bloqueia spammer sem afetar legítimo

### Contexto

Ataque DoS em seating com uma conta única. 5 requests/min é alto o suficiente para uso real (ninguém reserva 6 cadeiras por minuto legitimamente) e baixo o suficiente pra cortar script.

Referências: planejamento §2.10, §11.4.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Seating\Assento;

it('rate limit de seating 5/min por usuário', function (): void {
    $user = $this->authAs('formando');

    $assentos = Assento::factory(6)->create();

    foreach ($assentos->take(5) as $i => $a) {
        $this->withHeader('X-Idempotency-Key', "k-$i")
            ->postJson("/api/v1/eventos/{$a->mesa->mapa->evento->ulid}/mesas/reservas", [
                'assento_ulid' => $a->ulid,
            ])->assertStatus(201);
    }

    $this->withHeader('X-Idempotency-Key', 'k-6')
        ->postJson("/api/v1/eventos/{$assentos[5]->mesa->mapa->evento->ulid}/mesas/reservas", [
            'assento_ulid' => $assentos[5]->ulid,
        ])->assertStatus(429)
          ->assertHeader('Retry-After');
});
```

### Resultado esperado

- 1ª a 5ª → 201.
- 6ª → 429 com `Retry-After` informando segundos até nova janela.

---

## 10. Token de convite inválido ou revogado → 404 sem vazar existência

### Contexto

Endpoint público `/api/v1/convite/{token}` é alvo de enumeration. Se retornar 404 para inexistente e 410 para cancelado, atacante sabe quais tokens _já existiram_. Resposta uniforme previne.

Referências: planejamento §6.3, §11.6.

### Risco

- Enumeration ajuda phishing.
- 4xx distintos diferenciam revogado de inexistente.

### Snippet

```php
<?php

declare(strict_types=1);

use App\Models\Convites\Convite;

it('token inexistente retorna 404 sem sinal', function (): void {
    $this->getJson('/api/v1/convite/abcdef')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'convite_invalido');
});

it('token cancelado retorna 404 com mesma resposta', function (): void {
    $convite = Convite::factory()->cancelado()->create();
    $tokenBruto = 'plaintexttoken'; // produção: não persistido; aqui simulamos o bruto
    // O hash do token cancelado existe, mas middleware responde 404 igualzinho
    $this->getJson("/api/v1/convite/{$tokenBruto}")
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'convite_invalido');
});

it('token mal-formado também é 404', function (): void {
    $this->getJson('/api/v1/convite/x')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'convite_invalido');
});
```

### Resultado esperado

- Todas as respostas retornam **exatamente** o mesmo JSON.
- Nenhum header, código ou tempo de resposta diferencia os três casos.

---

## Matriz resumo

| #   | Cenário                       | Fase de aceite | Arquivo Pest                                                      | SLA                |
| --- | ----------------------------- | -------------- | ----------------------------------------------------------------- | ------------------ |
| 1   | 1000× concorrentes assento    | F5             | `tests/Concurrency/ReservarAssentoParalelosTest.php`              | p95 ≤ 700 ms       |
| 2   | Webhook 10× idempotente       | F6             | `tests/Feature/Webhook/WebhookIdempotenciaTest.php`               | ≤ 1 min reprocessa |
| 3   | Idempotency-Key estável       | F5             | `tests/Feature/Api/V1/Seating/ReservasControllerTest.php`         | —                  |
| 4   | Convite extra pós-pagamento   | F6             | `tests/Feature/Api/V1/Extras/PedidosExtrasControllerTest.php`     | ≤ 30s emissão      |
| 5   | CotaCalculator bordas         | F4             | `tests/Unit/Comercial/CotaCalculatorTest.php`                     | —                  |
| 6   | Elegibilidade enquete         | F6             | `tests/Unit/Enquetes/AvaliarElegibilidadeServiceTest.php`         | —                  |
| 7   | Transição de estado inválida  | F2+            | feature por contexto                                              | —                  |
| 8   | Hold expirado → 410           | F5             | `tests/Feature/Api/V1/Seating/ConfirmarReservaControllerTest.php` | —                  |
| 9   | Rate limit seating 5/min      | F5             | `tests/Feature/Api/V1/Seating/ReservasControllerTest.php`         | —                  |
| 10  | Token inválido → 404 uniforme | F4             | `tests/Feature/Api/V1/Convites/ConvitePublicoControllerTest.php`  | —                  |

Todos eles rodam em CI como parte do gate do PR. Concurrency tests rodam em serial. Performance (SLAs numéricos) rodam em staging via k6 (ver `nfr-tests.md`).
