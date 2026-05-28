---
title: NFR Tests — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# NFR Tests — Portal ArtFinal v2 (Backend API v1)

Requisitos não-funcionais com metodologia, ferramental e testes mensuráveis: performance, carga, segurança (OWASP), LGPD, resiliência (chaos) e acessibilidade. Os NFRs são gates de **promoção entre ambientes** (staging → prod), não bloqueiam PR — salvo quando há regressão documentada.

---

## 1. Performance

### 1.1 Metas (PRD v4 §1.6)

| Métrica                                   | Meta     |
| ----------------------------------------- | -------- |
| P95 API listagens críticas                | ≤ 500 ms |
| P95 API reserva de assento                | ≤ 700 ms |
| Tempo reflexão assento entre clientes     | ≤ 3 s    |
| Exportação relatório operacional          | ≤ 30 s   |
| Emissão lote 500 convites                 | ≤ 60 s   |
| Tempo reprocessar webhook c/ idempotência | ≤ 1 min  |

### 1.2 Metodologia — k6

Ambiente: staging (replica de prod com dados anonimizados).

Arquivo: `nfr-tests/k6/seating-reserva.js`

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    scenarios: {
        reserva_assento: {
            executor: 'constant-arrival-rate',
            rate: 100,
            timeUnit: '1m',
            duration: '5m',
            preAllocatedVUs: 50,
            maxVUs: 200,
            tags: { endpoint: 'reservar_assento' },
        },
    },
    thresholds: {
        'http_req_duration{endpoint:reservar_assento}': ['p(95)<700'],
        http_req_failed: ['rate<0.01'],
    },
};

const TOKEN = __ENV.ARTFINAL_TEST_TOKEN;
const EVENTO_ULID = __ENV.EVENTO_ULID;

export default function () {
    const payload = JSON.stringify({
        assento_ulid: randomAssentoUlid(),
    });

    const res = http.post(`${__ENV.BASE}/api/v1/eventos/${EVENTO_ULID}/mesas/reservas`, payload, {
        headers: {
            Authorization: `Bearer ${TOKEN}`,
            'Content-Type': 'application/json',
            'X-Idempotency-Key': `k6-${__VU}-${__ITER}`,
        },
        tags: { endpoint: 'reservar_assento' },
    });

    check(res, {
        'status 201 ou 409': (r) => [201, 409].includes(r.status),
    });

    sleep(0.5);
}
```

Execução:

```bash
k6 run --env BASE=https://staging.portalartfinal.com.br --env ARTFINAL_TEST_TOKEN=... nfr-tests/k6/seating-reserva.js
```

### 1.3 Cenários k6 obrigatórios

| Arquivo                    | Endpoint                       | Rate     | Threshold p95 |
| -------------------------- | ------------------------------ | -------- | ------------- |
| `listagem-eventos.js`      | GET `/api/v1/eventos`          | 1000/min | < 500 ms      |
| `listagem-mapa.js`         | GET `/api/v1/eventos/{u}/mapa` | 1000/min | < 500 ms      |
| `seating-reserva.js`       | POST reservar                  | 100/min  | < 700 ms      |
| `seating-confirmar.js`     | POST confirmar                 | 50/min   | < 500 ms      |
| `emissao-lote-convites.js` | POST lote                      | 10/min   | ciclo ≤ 60s   |
| `webhook-pagamento.js`     | POST webhook                   | 600/min  | < 500 ms      |

### 1.4 Gates

- Cenário falho em threshold bloqueia promoção para prod.
- Se staging degrada por infra (DB CPU > 80%), retestar antes de bloquear.
- Baseline registrado em `docs/qa/baseline-performance-<data>.json`; regressão > 15% abre issue P1.

---

## 2. Carga

### 2.1 Cenários

- **Hot spot seating**: 1.000 atores tentando o mesmo assento — item §1 de `critical-scenarios.md`. Rodado no Pest (concurrency) + k6.
- **Listagem mapa**: 1.000 req/min durante 10 min sem cache.
- **Emissão lote**: 500 convites em paralelo — verificar que job cabe na fila `default` sem saturar.
- **Webhook flood**: 600 webhooks/min simulando back-pressure do gateway.

### 2.2 Observáveis

Durante teste de carga monitorar no Pulse:

- CPU e memória PHP-FPM.
- Queries lentas (> 100ms).
- Cache miss ratio (< 20% para listagens quentes).
- Redis ops/sec.
- Queue depth (deve estabilizar).

---

## 3. Segurança — OWASP Top 10 mapeado

Para cada item: risco, teste automatizado e frequência de varredura.

### 3.1 A01 — Broken Access Control

**Risco**: comissão acessa rotas admin; formando X vê dados de formando Y.

**Testes automatizados**:

```php
<?php

declare(strict_types=1);

use App\Models\Acesso\PortalUser;
use App\Models\Comercial\Adesao;

it('formando não vê adesão de outro formando', function (): void {
    $alice = PortalUser::factory()->create();
    $bob   = PortalUser::factory()->create();
    $adesaoBob = Adesao::factory()->create(['portal_user_id' => $bob->id]);

    $this->actingAs($alice, 'sanctum')
        ->getJson("/api/v1/adesoes/{$adesaoBob->ulid}")
        ->assertForbidden();
});

it('comissão sem permission.extras.approve é bloqueada', function (): void {
    $comissao = PortalUser::factory()->comissao()->create();

    $this->actingAs($comissao, 'sanctum')
        ->postJson('/api/v1/pedidos-extras/aprovar', ['pedido_ulid' => 'x'])
        ->assertForbidden();
});
```

Cobertura: policies 100% (ver `qa-strategy.md` §6).

### 3.2 A02 — Cryptographic Failures

**Risco**: token em plain text, HMAC mal calculada, sha1 vulnerável.

**Testes**:

```php
it('token de convite usa random_bytes com 32 octetos', function (): void {
    $acao = app(\App\Actions\Convites\EmitirConviteAction::class);
    $convite = $acao->execute(/* ... */);

    expect(strlen($convite->tokenBruto))->toBe(64); // 32 bytes hex = 64 chars
    expect(ctype_xdigit($convite->tokenBruto))->toBeTrue();
});

it('token não é persistido em plain text', function (): void {
    \App\Models\Convites\Convite::factory()->create();
    $row = DB::table('convites')->first();

    expect($row->token_hash)->toHaveLength(64); // sha256 hex
    expect(property_exists($row, 'token'))->toBeFalse();
});

it('HMAC webhook rejeita sha1 mesmo se valor bater', function (): void {
    $payload = ['tipo' => 'pagamento.confirmado'];
    $sha1 = sha1('x');

    $this->withHeader('X-Signature', $sha1)
         ->postJson('/webhooks/pagamentos/itau', $payload)
         ->assertUnauthorized();
});
```

### 3.3 A03 — Injection

**Risco**: SQL injection via `whereRaw`, filtro `?sort=<malicioso>`.

**Testes**:

```php
it('filtros JSON:API sanitizam sort', function (): void {
    $this->authAs('formando')
        ->getJson('/api/v1/eventos?sort=;DROP TABLE users')
        ->assertStatus(422);

    expect(DB::table('users')->exists())->toBeTrue();
});

it('string com aspas no nome não quebra query', function (): void {
    $this->authAs('admin-system')
        ->postJson('/api/v1/organizacoes', [
            'nome' => "Rock'n Roll'; DROP TABLE organizacoes; --",
            'cnpj' => '11.222.333/0001-81',
        ])
        ->assertCreated();

    expect(\App\Models\Cadastro\Organizacao::count())->toBe(1);
});
```

Arch test bloqueia `DB::raw` não-parametrizada em controllers.

### 3.4 A04 — Insecure Design

Threat model revisado no início de cada fase:

- **F1**: guards e autenticação.
- **F4**: token de convite, enumeration.
- **F5**: race condition seating.
- **F6**: webhook signature, replay.
- **F7**: LGPD, anonimização.

Output do threat model vira testes; sem teste, sem merge do threat model.

### 3.5 A05 — Security Misconfiguration

**Risco**: headers HTTP fracos; APP_DEBUG=true em prod; CORS `*`.

**Testes**:

```php
it('resposta HTTP inclui headers de segurança', function (): void {
    $response = $this->get('/api/v1/health');

    $response->assertHeader('Strict-Transport-Security')
             ->assertHeader('X-Frame-Options', 'DENY')
             ->assertHeader('X-Content-Type-Options', 'nosniff')
             ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
             ->assertHeader('Content-Security-Policy');
});

it('APP_DEBUG não aparece em response 500 de prod', function (): void {
    config(['app.debug' => false]);
    // simular exception
    $response = $this->getJson('/api/v1/faz-boom');

    expect($response->content())->not->toContain('vendor/')
                                   ->not->toContain('stack trace');
});
```

### 3.6 A07 — Identification and Authentication Failures

- Rate limit login 5/min (AC-AUTH-005).
- Token mobile rotacionável via logout.
- Session regenerate após login (AC-AUTH-001).
- Não permitir reuso de token revogado.

### 3.7 A08 — Software and Data Integrity Failures

- HMAC obrigatório em webhook (AC-PAG-002).
- Assinatura dos jobs via Horizon tags previne poisoning.
- Dependências escaneadas: `composer audit`, `npm audit` em CI (semanal).

### 3.8 A09 — Security Logging and Monitoring Failures

**Teste de mascaramento**:

```php
use Illuminate\Support\Facades\Log;

it('CPF é mascarado em logs', function (): void {
    Log::spy();

    app(\App\Actions\Cadastro\CriarFormandoAction::class)->execute([
        'cpf' => '12345678900',
        // ...
    ]);

    Log::shouldHaveReceived('info')->withArgs(function ($msg, $ctx) {
        return ! str_contains(json_encode($ctx), '12345678900');
    });
});

it('webhook secret nunca aparece em log de erro', function (): void {
    Log::spy();

    try {
        throw new \App\Exceptions\Pagamento\WebhookInvalidoException(config('gateway.itau.webhook_secret'));
    } catch (\Throwable $e) {
        report($e);
    }

    Log::shouldNotHaveReceived('error', fn ($msg, $ctx) => str_contains($msg, 'super_secret_value'));
});
```

### 3.9 A10 — SSRF

**Risco**: URL de webhook outbound controlável por usuário.

**Teste**:

```php
it('bloqueia URL interna em callback', function (): void {
    $response = $this->authAs('admin')
        ->postJson('/api/v1/eventos/integracao', [
            'webhook_url' => 'http://localhost:6379', // redis interno
        ]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'url_destino_invalida');
});
```

### 3.10 Varredura automatizada

- **OWASP ZAP** semanal contra staging. Output como relatório em `.artifacts/zap-<data>.html`.
- **Composer audit**: diário, via cron job em CI.
- **npm audit**: diário.
- **Secret scanning**: GitHub Secret Scanning habilitado + trufflehog pre-commit.

---

## 4. LGPD

### 4.1 Requisitos

- Coleta mínima: apenas campos marcados `required` em produção.
- Direito de exclusão via `DELETE /api/v1/me`.
- Anonimização automática 90 dias pós-evento.
- Retenção de `activity_log` 2 anos; `webhook_eventos` 1 ano.

### 4.2 Testes

```php
<?php

declare(strict_types=1);

use App\Models\Acesso\PortalUser;
use App\Jobs\Lgpd\AnonimizarDadosPosEventoJob;

it('DELETE /api/v1/me soft-deleta e anonimiza usuário', function (): void {
    $user = PortalUser::factory()->create(['email' => 'bye@teste.com']);
    $this->actingAs($user, 'sanctum');

    $this->deleteJson('/api/v1/me', ['confirmacao' => 'SIM'])->assertOk();

    $fresh = PortalUser::withTrashed()->find($user->id);
    expect($fresh->deleted_at)->not->toBeNull();
    expect($fresh->email)->not->toBe('bye@teste.com');
    expect($fresh->email)->toStartWith('anon-');
});

it('job anonimiza convidados 90 dias após evento', function (): void {
    $evento = \App\Models\Cadastro\Evento::factory()->encerrado(diasAtras: 95)->create();
    $convite = \App\Models\Convites\Convite::factory()
        ->for($evento)
        ->create(['nome_convidado' => 'João Silva', 'email_convidado' => 'joao@x.com']);

    (new AnonimizarDadosPosEventoJob())->handle();

    $fresh = $convite->fresh();
    expect($fresh->nome_convidado)->toStartWith('Convidado Anonimizado #');
    expect($fresh->email_convidado)->toBeNull();
});

it('export pseudonimizado não contém CPF completo', function (): void {
    $response = $this->authAs('admin-system')
        ->getJson('/api/v1/relatorios/formandos?formato=csv&anonimizar=true');

    $csv = $response->getContent();
    expect($csv)->not->toMatch('/\d{11}/'); // nenhum CPF puro
    expect($csv)->toMatch('/\d{3}\.\*{3}\.\*{3}-\d{2}/'); // formato mascarado
});
```

### 4.3 Conformidade documental

- Política de privacidade publicada antes de F7.
- DPO apontado formalmente.
- Termo de consentimento grava `termo_hash` em `adesoes.snapshot_comercial`.

---

## 5. Resiliência — Chaos Testing

### 5.1 Cenários

| Cenário                          | Como simular                                                  | Expectativa                                                             |
| -------------------------------- | ------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Redis down                       | `docker stop redis` em staging                                | Sessão continua via fallback DB; cache miss explícito; sem 500          |
| PostgreSQL lento                 | `tc qdisc add delay 200ms` em staging                         | p95 degrada mas não 500; circuit breaker em rota opcional               |
| Gateway Itaú lento (5s response) | `Http::fake` com `Http::response([], 200, headers, delay: 5)` | Saloon timeout em 3s; retry com backoff; job → failed após 3 tentativas |
| Horizon parado                   | `horizon:terminate` em staging                                | Jobs enfileiram; dashboard mostra queue depth crescendo; alerta dispara |
| Partição de rede DB→App          | `iptables DROP` em staging                                    | Transações pendentes rollback; app retoma em ≤ 30s                      |

### 5.2 Gameday

Rodar chaos exercise **1× por fase** (F5, F6, F7) com runbook documentado. Output: postmortem + melhorias de observabilidade.

### 5.3 Circuit breakers

Implementados em:

- `ItauGateway` (Saloon) — 3 falhas consecutivas → abre circuito por 60s.
- Envio de e-mail — falha massiva → reduz throughput.

Testes em `tests/Unit/Services/CircuitBreakerTest.php`.

---

## 6. Acessibilidade (a11y) — Admin

Escopo: admin Livewire. Cliente React/mobile têm testes a11y próprios no frontend.

### 6.1 Checklist WCAG 2.1 AA

- [ ] Todo input tem `<label>` associado.
- [ ] Toda imagem significativa tem `alt`.
- [ ] Contraste texto/fundo ≥ 4.5:1 (verificar com axe-core).
- [ ] Navegação por teclado funcional (Tab/Shift+Tab/Enter).
- [ ] `aria-live` em toasts.
- [ ] `aria-invalid="true"` em campos com erro.
- [ ] Modal tem `aria-modal="true"` e foco preso.

### 6.2 Teste automatizado via axe-core

```php
it('dashboard admin sem violações a11y críticas', function (): void {
    $admin = \App\Models\Acesso\AdminUser::factory()->admin()->create();
    $this->actingAs($admin, 'admin');

    visit('/admin/dashboard')
        ->assertAxeNoViolations(['critical', 'serious']);
});
```

(Assumindo helper `assertAxeNoViolations` via Pest browser + axe-core JS.)

### 6.3 Testes manuais

- 1× por fase com leitor de tela NVDA/VoiceOver.
- Screenshot checklist arquivado em `.artifacts/a11y/<fase>/`.

---

## 7. Matriz NFR → gate

| NFR                 | F1   | F2   | F3   | F4   | F5       | F6   | F7       | F8   |
| ------------------- | ---- | ---- | ---- | ---- | -------- | ---- | -------- | ---- |
| Perf. p95 listagem  | —    | —    | soft | soft | hard     | hard | hard     | hard |
| Perf. p95 reserva   | —    | —    | —    | —    | **hard** | hard | hard     | hard |
| Carga 1000× seating | —    | —    | —    | —    | **hard** | hard | hard     | hard |
| OWASP A01–A10       | soft | soft | soft | hard | hard     | hard | hard     | hard |
| Headers HTTP        | hard | hard | hard | hard | hard     | hard | hard     | hard |
| LGPD anonimização   | —    | —    | —    | —    | —        | —    | **hard** | hard |
| Chaos gameday       | —    | —    | —    | —    | hard     | hard | hard     | —    |
| a11y AA admin       | soft | hard | hard | hard | hard     | hard | hard     | —    |

Legenda: `—` = não aplicável, `soft` = desejável sem bloquear, `hard` = bloqueante.

---

## 8. Ferramental resumo

| Área          | Ferramenta                            | Onde                  |
| ------------- | ------------------------------------- | --------------------- |
| Performance   | k6                                    | `nfr-tests/k6/*.js`   |
| OWASP scan    | OWASP ZAP                             | GitHub Actions weekly |
| SCA           | composer audit, npm audit, trufflehog | CI + pre-commit       |
| a11y          | axe-core + Pest browser               | `tests/Browser/a11y/` |
| Chaos         | tc, iptables, docker                  | runbook manual        |
| Monitoramento | Pulse + Horizon + Sentry              | prod + staging        |
| LGPD          | job AnonimizarDadosPosEventoJob       | schedule weekly       |

---

## 9. Cadência

| Atividade          | Frequência               |
| ------------------ | ------------------------ |
| k6 baseline        | a cada release candidate |
| OWASP ZAP          | semanal em staging       |
| composer/npm audit | diário (CI)              |
| Chaos gameday      | 1× por fase crítica      |
| Pentest externo    | 1× por ano + pré-go-live |
| Revisão LGPD       | trimestral + pré-evento  |
| a11y manual        | 1× por fase              |

---

## 10. Referências

- Planejamento: §11 (segurança), §12 (observabilidade), §13 (snapshots e LGPD).
- PRD v4: §1.6 (métricas).
- Regras: §14 (auditoria).
- `qa-strategy.md`: §7 (ferramental), §11 (gates F1–F8).
