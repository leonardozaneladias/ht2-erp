---
title: SPEC-002 — Wizard de Adesão (fluxo autenticado)
version: 2.0.0
date: 2026-04-23
status: needs-rewrite
feature_id: SPEC-002
fase: F3 (core — wizard principal do portal)
story_points: 20
depends_on: [SPEC-001, SPEC-F-001, SPEC-F-002, SPEC-F-004, SPEC-F-005]
unlocks: [SPEC-003, SPEC-004, SPEC-006, SPEC-007, SPEC-008]
---

> **[REESCRITA PENDENTE — 2026-04-23]** Este documento foi parcialmente invalidado pela inversão da hierarquia
> Contrato↔Turma em [SPEC-F-001 v0.3.0](foundation/SPEC-F-001-contrato-e-turma.md) e pelo novo fluxo público
> em [SPEC-010 v2.0.0](SPEC-010-adesao-publica-codigo-contrato.md). Até a reescrita completa, considere as
> seguintes **mudanças obrigatórias** aplicáveis também ao fluxo autenticado:
>
> - **Nova etapa — "Escolher curso + período"**: seleção de turma dentro do contrato (lista de turmas do contrato, uma por curso × ano × semestre). Obrigatória antes do pacote. Quando contrato tem apenas 1 turma, a UI pode pular (seleção automática).
> - **Nova etapa — "Escolher pacote formatura"**: filtro `categoria='formatura'`; exatamente 1 seleção. Pacotes `categoria='extra'` ficam fora do wizard e só aparecem em fluxos pós-adesão (convites, mesas).
> - `evento_ulid` → `contrato_ulid` em **todas** as rotas, DTOs, FormRequests, schemas Zod.
> - `turma_ulid` passa a ser **obrigatório** no payload de criação/simulação.
> - `pacote_ulid` continua obrigatório, mas com validação adicional de categoria.
> - `adesoes.portal_user_id` é nullable (fluxo público SPEC-010 sem auth prévia).
> - Novos campos persistidos: `draft_token_hash`, `origem_adesao` (enum: `autenticada` | `publica_codigo_contrato`).
> - Responsáveis: dois responsáveis separados (cadastro + financeiro, ver SPEC-F-002).
> - Condições de pagamento por contrato (SPEC-F-005), aplicadas via `condicoes_pagamento` + regras por método:
>     - PIX 1x com desconto (bloqueado em "demais parcelas")
>     - Boleto 1–12x com desconto escalonado
>     - Cartão 2–12x (sem 1x) com desconto escalonado
>
> Contexto de governança: [`docs/META/PROJECT-STATUS.md`](../META/PROJECT-STATUS.md) — `status: desenvolvimento`.
> Plano de implementação: [`2026-04-19-adesao-publica-codigo-contrato-plan.md`](../superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md).

---

# SPEC-002 — Wizard de Adesão (7 etapas)

> **Spec unificada backend + frontend.** Esta é a feature central do Portal do Formando: conduz o formando por 7 etapas desde a confirmação de dados pessoais até a criação da adesão e início do pagamento inicial.
> Fontes: [09-TECHNICAL-DESIGN-CRITICAL-MODULES.md §2](../frontend/09-TECHNICAL-DESIGN-CRITICAL-MODULES.md) · [07-DATA-CONTRACTS-AND-VIEW-MODELS.md](../frontend/07-DATA-CONTRACTS-AND-VIEW-MODELS.md) · [api-contract.md §2,§8](../api/api-contract.md) · [PLANEJAMENTO_FRONTEND_REACT.md §6,§8](../prd/PLANEJAMENTO_FRONTEND_REACT.md)

---

## 0. Resumo executivo

O formando autenticado acessa `/portal/adesao/1` e é conduzido por 7 etapas sequenciais: **dados pessoais → responsável financeiro → seleção de pacote → plano de pagamento → termos e condições → revisão geral → confirmação**. O estado é persistido em `sessionStorage` via `wizard-store` (nunca `localStorage` — os dados contêm CPF e informações financeiras). O commit final na etapa 7 cria a adesão (`POST /adesoes`) e inicia o pagamento inicial (`POST /pagamentos/intents`), ambos com `X-Idempotency-Key` para garantir que duplo submit não duplica registros. Se o formando já possui adesão ativa, o wizard é ignorado e ele é redirecionado direto para `/portal/home`. Taxa de conclusão do funnel é acompanhada por Sentry breadcrumbs por etapa.

---

## 1. Visão da feature

### 1.1 Jornada macro

```mermaid
flowchart LR
    A[/portal/home] -->|sem adesão| B[/portal/adesao/1]
    A -->|com adesão ativa| Z[/portal/home — dashboard]
    B -->|step 1 ok| C[Etapa 2 — Responsável]
    C -->|próprio formando| D[Etapa 3 — Pacote]
    C -->|dados de terceiro| D
    D -->|pacote selecionado| E[Etapa 4 — Plano]
    E -->|simulação ok| F[Etapa 5 — Termos]
    F -->|termos aceitos| G[Etapa 6 — Revisão]
    G -->|confirma| H[Etapa 7 — Confirmação]
    H -->|POST /adesoes 201| I[POST /pagamentos/intents]
    I -->|201| J[/portal/pagamento/:id]
    H -->|409 AdesaoJaExistente| Z
    H -->|rede cai| K[toast + estado preservado]
    K -->|retry| H
```

### 1.2 Atores

| Ator                   | Ação                                                                         |
| ---------------------- | ---------------------------------------------------------------------------- |
| Formando               | Percorre o wizard e confirma sua adesão ao evento/pacote (jornada primária). |
| Responsável financeiro | Pode ser o próprio formando (checkbox etapa 2) ou terceiro (nome/CPF/email). |
| Mobile F8 (futuro)     | Consome os mesmos endpoints com `mode: 'token'` — wizard em React Native.    |
| Operação/backoffice    | Fora de escopo desta spec — a operação acompanha via admin Blade.            |

### 1.3 Valor

- Converte interesse em adesão formalizada com contrato e primeiro pagamento.
- Desacopla coleta de dados em etapas atômicas, reduzindo abandono.
- Idempotência garante consistência financeira mesmo com rede instável.
- Persistência por etapa em `sessionStorage` permite retomada após refresh.

### 1.4 Escopo

**In:** 7 etapas do wizard, simulação de parcelamento, commit atômico adesão + pagamento, idempotência, retomada de sessão, skip se já tem adesão ativa.

**Out:** edição de adesão já confirmada (tela `/portal/home`), cancelamento pós-confirmação (fluxo separado), parcelamento com reajuste por índice (pós-MVP), emissão de convites (SPEC-007).

---

## 2. Contrato da API

### 2.1 `GET /api/v1/me/adesoes` — verificar adesão existente

- **Route name:** `api.v1.me.adesoes`
- **Middlewares:** `auth:sanctum` + `throttle:api`
- **Uso:** chamado no `beforeLoad` de `/portal/adesao/$step`; se retornar adesão `ativa`, redirecionar para `/portal/home`.

**Query params:**

- `filter[status]` — `ativa` (default ao verificar skip do wizard)
- `filter[evento_id]` — ULID do evento vinculado ao formando

**Response 200:**

```json
{
    "data": [
        {
            "id": "01J...",
            "status": "ativa",
            "evento": { "id": "01J...", "slug": "baile-med-usp-2026" },
            "pacote": { "id": "01J...", "nome": "Premium" },
            "valor_total_centavos": 1500000,
            "qtd_parcelas": 10,
            "confirmada_at": "2026-02-10T14:00:00-03:00",
            "parcelas_resumo": { "total": 10, "pagas": 3, "pendentes": 7, "vencidas": 0 },
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/me/adesoes/01J...",
                "extrato": "https://api.portalartfinal.com.br/api/v1/me/extrato?filter[adesao_id]=01J...",
                "cancelar": null
            }
        }
    ],
    "meta": { "per_page": 50, "next_cursor": null, "prev_cursor": null },
    "links": { "self": "...", "next": null, "prev": null }
}
```

**Erros:**

- `401 Unauthenticated` — sessão expirada; interceptor redireciona para `/login`.

---

### 2.2 `GET /api/v1/me/eventos` — listar eventos disponíveis

- **Route name:** `api.v1.me.eventos`
- **Middlewares:** `auth:sanctum` + `throttle:api`
- **Uso:** etapa 1, para confirmar o evento vinculado à turma do formando.

**Response 200:** shape conforme `api-contract.md §2.1` — lista de eventos com `id`, `slug`, `nome`, `status`, `janelas`, `links`.

---

### 2.3 `GET /api/v1/eventos/{ulid}/pacotes` — listar pacotes do evento (GAP G1)

- **Route name:** `api.v1.pacotes.index`
- **Middlewares:** `auth:sanctum` + `throttle:api`
- **Status:** ❓ endpoint a especificar — gap G1 documentado em `08-API-INTEGRATION-CONTRACT.md`

**Query params:**

- `filter[ativo]` — `true` (default; exibe apenas pacotes publicados)
- `sort=preco_centavos`

**Response 200:**

```json
{
    "data": [
        {
            "id": "01J...",
            "nome": "Pacote Premium",
            "descricao": "Jantar + open bar + foto",
            "preco_centavos": 1500000,
            "beneficios": ["Jantar completo", "Open bar", "Sessão de fotos"],
            "cotas_convite": { "base": 4, "transferivel": 2 },
            "ativo": true,
            "links": { "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../pacotes/01J..." }
        }
    ],
    "meta": { "per_page": 50, "next_cursor": null, "prev_cursor": null },
    "links": { "self": "...", "next": null, "prev": null }
}
```

**Erros:**

- `401 Unauthenticated`
- `403 Forbidden` — formando sem vínculo com o evento
- `404 NotFound` — evento não existe

---

### 2.4 `POST /api/v1/adesoes/simular` — simular parcelamento (GAP G2)

- **Route name:** `api.v1.adesoes.simular`
- **Middlewares:** `auth:sanctum` + `throttle:api`
- **Idempotência:** não exigida (simulação é idempotente por natureza)
- **Status:** ❓ endpoint a especificar — gap G2

**Request:**

```json
{
    "evento_ulid": "01J...",
    "pacote_ulid": "01J...",
    "qtd_parcelas": 10,
    "metodo_primeira_parcela": "pix",
    "metodo_demais": "boleto"
}
```

**Validação:**

- `evento_ulid` → `required|string|size:26`
- `pacote_ulid` → `required|string|size:26`
- `qtd_parcelas` → `required|integer|min:1|max:12`
- `metodo_primeira_parcela` → `required|in:pix,boleto,cartao`
- `metodo_demais` → `required|in:boleto,cartao`

**Response 200:**

```json
{
    "data": {
        "valor_total_centavos": 1500000,
        "qtd_parcelas": 10,
        "valor_primeira_centavos": 150000,
        "valor_demais_centavos": 150000,
        "metodo_primeira_parcela": "pix",
        "metodo_demais": "boleto",
        "parcelas": [
            {
                "numero": 1,
                "valor_centavos": 150000,
                "metodo": "pix",
                "vencimento_exemplo": "2026-03-05"
            },
            {
                "numero": 2,
                "valor_centavos": 150000,
                "metodo": "boleto",
                "vencimento_exemplo": "2026-04-05"
            }
        ]
    }
}
```

**Erros:**

- `422 ValidationError` — campos inválidos
- `404 NotFound` — pacote não pertence ao evento
- `429 RateLimitExceeded` — simulações em excesso

---

### 2.5 `POST /api/v1/adesoes` — criar adesão (commit final, etapa 7)

- **Route name:** `api.v1.adesoes.store`
- **Middlewares:** `auth:sanctum` + `idempotent` + `throttle:api`
- **Idempotência:** `X-Idempotency-Key` **obrigatório** (header)
- **Status:** ❓ endpoint a especificar — gap G3

**Request:**

```json
{
    "evento_ulid": "01J...",
    "turma_ulid": "01J...",
    "pacote_ulid": "01J...",
    "plano": {
        "qtd_parcelas": 10,
        "metodo_primeira_parcela": "pix",
        "metodo_demais": "boleto",
        "data_vencimento_dia": 5
    },
    "responsavel": {
        "mesmo_formando": false,
        "nome": "Maria Oliveira",
        "cpf": "123.456.789-09",
        "email": "maria@email.com",
        "telefone": "+55 11 98765-4321"
    },
    "aceitou_termos": true,
    "termos_versao": "v2026-01"
}
```

**Headers obrigatórios:**

```
X-Idempotency-Key: <ulid | uuid ≤ 80 chars>
X-Request-Id: <ulid>
```

**Validação:**

- `evento_ulid` → `required|string|size:26`
- `turma_ulid` → `required|string|size:26`
- `pacote_ulid` → `required|string|size:26`
- `plano.qtd_parcelas` → `required|integer|min:1|max:12`
- `plano.metodo_primeira_parcela` → `required|in:pix,boleto,cartao`
- `plano.metodo_demais` → `required|in:boleto,cartao`
- `plano.data_vencimento_dia` → `required|in:1,5,10,15,20,25`
- `responsavel.mesmo_formando` → `required|boolean`
- `responsavel.nome` → `required_if:responsavel.mesmo_formando,false|string|max:150`
- `responsavel.cpf` → `required_if:responsavel.mesmo_formando,false|cpf` (pacote `laravellegends/pt-br-validator`)
- `responsavel.email` → `required_if:responsavel.mesmo_formando,false|email|max:150`
- `aceitou_termos` → `required|accepted`
- `termos_versao` → `required|string|max:20`

**Response 201 + `Location` header:**

```json
{
    "data": {
        "id": "01J...",
        "status": "pendente_pagamento",
        "evento": { "id": "01J...", "slug": "baile-med-usp-2026" },
        "pacote": { "id": "01J...", "nome": "Pacote Premium" },
        "valor_total_centavos": 1500000,
        "qtd_parcelas": 10,
        "confirmada_at": null,
        "parcelas_resumo": { "total": 10, "pagas": 0, "pendentes": 10, "vencidas": 0 },
        "responsavel": {
            "mesmo_formando": false,
            "nome": "Maria Oliveira",
            "email": "maria@email.com"
        },
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/me/adesoes/01J...",
            "extrato": "https://api.portalartfinal.com.br/api/v1/me/extrato?filter[adesao_id]=01J...",
            "cancelar": "https://api.portalartfinal.com.br/api/v1/me/adesoes/01J..."
        }
    }
}
```

**Erros:**

- `409 AdesaoJaExistente` — formando já tem adesão ativa neste evento → FE redireciona para `/portal/home`
- `409 IdempotencyConflict` — chave idempotente em conflito (request ainda processando) → aguardar + retry
- `409 InvariantViolation` — pacote descontinuado, prazo encerrado → FE volta para etapa 3
- `422 ValidationError` — campos inválidos com `details.fields`
- `429 RateLimitExceeded`

---

### 2.6 `POST /api/v1/pagamentos/intents` — iniciar pagamento inicial

- **Route name:** `api.v1.pagamentos.intents.store`
- **Middlewares:** `auth:sanctum` + `idempotent` + `throttle:api`
- **Idempotência:** `X-Idempotency-Key` **obrigatório**
- **Chamado imediatamente após** `POST /adesoes` com sucesso (etapa 7)

**Request:**

```json
{
    "adesao_ulid": "01J...",
    "parcela_numero": 1,
    "metodo": "pix"
}
```

**Response 201:**

```json
{
    "data": {
        "id": "01J...",
        "status": "pendente",
        "metodo": "pix",
        "valor_centavos": 150000,
        "pix": {
            "qr_code": "00020126...",
            "qr_code_imagem_base64": "data:image/png;base64,...",
            "expira_em": "2026-02-10T15:00:00-03:00"
        },
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/pagamentos/01J...",
            "adesao": "https://api.portalartfinal.com.br/api/v1/me/adesoes/01J..."
        }
    }
}
```

**Erros:**

- `409 IdempotencyConflict`
- `422 ValidationError`

---

### 2.7 Headers obrigatórios (wizard)

| Header              | Direção | Uso                                                              |
| ------------------- | ------- | ---------------------------------------------------------------- |
| `X-Request-Id`      | req/res | Correlação de logs (ULID). Gerado pelo cliente.                  |
| `X-XSRF-TOKEN`      | req     | Cookie `XSRF-TOKEN` (Axios injeta automaticamente).              |
| `X-Idempotency-Key` | req     | **Obrigatório** em `POST /adesoes` e `POST /pagamentos/intents`. |
| `Content-Type`      | req     | `application/json`                                               |
| `Accept`            | req     | `application/json`                                               |

---

## 3. Backend — Laravel 13

### 3.1 Arquivos a criar/modificar

| Arquivo                                              | Ação      | Responsabilidade                                             |
| ---------------------------------------------------- | --------- | ------------------------------------------------------------ | ------ | --------- | ------------ | ---------- |
| `database/migrations/YYYY_create_adesoes_table.php`  | Criar     | Tabela `adesoes` com ULID, status, FKs, snapshot JSON.       |
| `database/migrations/YYYY_create_parcelas_table.php` | Criar     | Tabela `parcelas` com valor em centavos, status, vencimento. |
| `app/Models/Adesao.php`                              | Criar     | Model com casts, relacionamentos, HasUlid.                   |
| `app/Models/Parcela.php`                             | Criar     | Model com enums `StatusParcela`.                             |
| `app/Enums/StatusAdesao.php`                         | Criar     | `PENDENTE_PAGAMENTO                                          | ATIVA  | CANCELADA | INADIMPLENTE | CONCLUIDA` |
| `app/Enums/StatusParcela.php`                        | Criar     | `PENDENTE                                                    | PAGO   | VENCIDO   | CANCELADO`   |
| `app/Enums/MetodoPagamento.php`                      | Criar     | `PIX                                                         | BOLETO | CARTAO`   |
| `app/Http/Controllers/Api/V1/AdesaoController.php`   | Criar     | `index()`, `simular()`, `store()`.                           |
| `app/Http/Requests/Api/V1/SimularAdesaoRequest.php`  | Criar     | Validação da simulação.                                      |
| `app/Http/Requests/Api/V1/StoreAdesaoRequest.php`    | Criar     | Validação completa do commit.                                |
| `app/Http/Resources/V1/AdesaoResource.php`           | Criar     | Serialização da adesão com links HATEOAS.                    |
| `app/Http/Resources/V1/ParcelaResource.php`          | Criar     | Serialização das parcelas.                                   |
| `app/Http/Resources/V1/SimulacaoResource.php`        | Criar     | Serialização da simulação de parcelamento.                   |
| `app/Actions/Adesoes/CriarAdesaoAction.php`          | Criar     | Lógica atômica de criação de adesão + parcelas.              |
| `app/Actions/Adesoes/SimularParcelamentoAction.php`  | Criar     | Cálculo de parcelas a partir de pacote + plano.              |
| `app/Http/Controllers/Api/V1/PacoteController.php`   | Criar     | `index()` — lista pacotes de um evento.                      |
| `app/Http/Resources/V1/PacoteResource.php`           | Criar     | Serialização do pacote.                                      |
| `routes/api/v1.php`                                  | Modificar | Registrar rotas de adesão e pacotes.                         |
| `app/Http/Middleware/EnsureIdempotency.php`          | Criar     | Valida e aplica X-Idempotency-Key via Redis.                 |
| `tests/Feature/Api/V1/Adesao/AdesaoStoreTest.php`    | Criar     | 8 cenários Pest.                                             |
| `tests/Feature/Api/V1/Adesao/AdesaoSimularTest.php`  | Criar     | 4 cenários Pest.                                             |

---

### 3.2 Migrations

#### 3.2.1 `create_adesoes_table`

```php
Schema::create('adesoes', function (Blueprint $table) {
    $table->id();
    $table->ulid('ulid')->unique();
    $table->foreignId('portal_user_id')->constrained('portal_users');
    $table->foreignId('evento_id')->constrained('eventos');
    $table->foreignId('turma_id')->constrained('turmas');
    $table->foreignId('pacote_id')->constrained('pacotes');
    $table->string('status', 30)->default('pendente_pagamento'); // Enum StatusAdesao
    $table->integer('valor_total_centavos');
    $table->smallInteger('qtd_parcelas');
    $table->string('metodo_primeira_parcela', 20);
    $table->string('metodo_demais', 20);
    $table->tinyInteger('data_vencimento_dia');
    // Responsável financeiro
    $table->boolean('responsavel_mesmo_formando')->default(true);
    $table->string('responsavel_nome', 150)->nullable();
    $table->string('responsavel_cpf', 14)->nullable();
    $table->string('responsavel_email', 150)->nullable();
    $table->string('responsavel_telefone', 30)->nullable();
    // Aceite de termos
    $table->boolean('aceitou_termos')->default(false);
    $table->string('termos_versao', 20)->nullable();
    $table->timestamp('termos_aceitos_at')->nullable();
    // Snapshot imutável do pacote no momento da adesão
    $table->jsonb('pacote_snapshot')->nullable();
    $table->timestamp('confirmada_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['portal_user_id', 'status']);
    $table->index(['evento_id', 'status']);
    $table->index('ulid');
});
```

#### 3.2.2 `create_parcelas_table`

```php
Schema::create('parcelas', function (Blueprint $table) {
    $table->id();
    $table->ulid('ulid')->unique();
    $table->foreignId('adesao_id')->constrained('adesoes');
    $table->smallInteger('numero'); // 1 a 12
    $table->integer('valor_centavos');
    $table->string('metodo', 20);
    $table->string('status', 20)->default('pendente'); // Enum StatusParcela
    $table->date('data_vencimento');
    $table->timestamp('pago_at')->nullable();
    $table->timestamps();

    $table->index(['adesao_id', 'status']);
    $table->index(['data_vencimento', 'status']);
    $table->index('ulid');
});
```

---

### 3.3 Enums

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum StatusAdesao: string
{
    case PENDENTE_PAGAMENTO = 'pendente_pagamento';
    case ATIVA              = 'ativa';
    case CANCELADA          = 'cancelada';
    case INADIMPLENTE       = 'inadimplente';
    case CONCLUIDA          = 'concluida';

    public function label(): string
    {
        return match($this) {
            self::PENDENTE_PAGAMENTO => 'Aguardando pagamento',
            self::ATIVA              => 'Ativa',
            self::CANCELADA          => 'Cancelada',
            self::INADIMPLENTE       => 'Inadimplente',
            self::CONCLUIDA          => 'Concluída',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDENTE_PAGAMENTO => 'yellow',
            self::ATIVA              => 'green',
            self::CANCELADA          => 'gray',
            self::INADIMPLENTE       => 'red',
            self::CONCLUIDA          => 'blue',
        };
    }
}
```

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum StatusParcela: string
{
    case PENDENTE   = 'pendente';
    case PAGO       = 'pago';
    case VENCIDO    = 'vencido';
    case CANCELADO  = 'cancelado';

    public function label(): string
    {
        return match($this) {
            self::PENDENTE  => 'Pendente',
            self::PAGO      => 'Pago',
            self::VENCIDO   => 'Vencido',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDENTE  => 'yellow',
            self::PAGO      => 'green',
            self::VENCIDO   => 'red',
            self::CANCELADO => 'gray',
        };
    }
}
```

---

### 3.4 `AdesaoController` — esqueleto

```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Adesoes\CriarAdesaoAction;
use App\Actions\Adesoes\SimularParcelamentoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SimularAdesaoRequest;
use App\Http\Requests\Api\V1\StoreAdesaoRequest;
use App\Http\Resources\V1\AdesaoResource;
use App\Http\Resources\V1\SimulacaoResource;
use App\Models\Adesao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdesaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $adesoes = Adesao::query()
            ->where('portal_user_id', $request->user()->id)
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->when($request->filled('filter.evento_id'), fn ($q) => $q->whereHas('evento', fn ($e) => $e->where('ulid', $request->input('filter.evento_id'))))
            ->with(['evento', 'pacote'])
            ->orderByDesc('created_at')
            ->cursorPaginate(perPage: 50);

        return AdesaoResource::collection($adesoes);
    }

    public function simular(SimularAdesaoRequest $request): SimulacaoResource
    {
        $simulacao = app(SimularParcelamentoAction::class)->execute($request->validated());

        return new SimulacaoResource($simulacao);
    }

    public function store(StoreAdesaoRequest $request): AdesaoResource
    {
        $adesao = app(CriarAdesaoAction::class)->execute(
            user: $request->user(),
            data: $request->validated(),
        );

        return (new AdesaoResource($adesao))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.me.adesoes.show', $adesao->ulid));
    }
}
```

---

### 3.5 `SimularParcelamentoAction`

```php
<?php
declare(strict_types=1);

namespace App\Actions\Adesoes;

use App\DTOs\SimulacaoParcelamentoDTO;
use App\Enums\MetodoPagamento;
use App\Models\Pacote;
use Carbon\Carbon;

final class SimularParcelamentoAction
{
    /**
     * @param array{
     *   evento_ulid: string,
     *   pacote_ulid: string,
     *   qtd_parcelas: int,
     *   metodo_primeira_parcela: string,
     *   metodo_demais: string
     * } $data
     */
    public function execute(array $data): SimulacaoParcelamentoDTO
    {
        $pacote = Pacote::where('ulid', $data['pacote_ulid'])
            ->whereHas('evento', fn ($q) => $q->where('ulid', $data['evento_ulid']))
            ->firstOrFail();

        $valorTotal      = $pacote->preco_centavos;
        $qtdParcelas     = $data['qtd_parcelas'];
        $valorParcela    = intdiv($valorTotal, $qtdParcelas);
        $valorUltima     = $valorTotal - ($valorParcela * ($qtdParcelas - 1));
        $metodoPrimeira  = MetodoPagamento::from($data['metodo_primeira_parcela']);
        $metodoDemais    = MetodoPagamento::from($data['metodo_demais']);

        $parcelas = [];
        for ($i = 1; $i <= $qtdParcelas; $i++) {
            $parcelas[] = [
                'numero'           => $i,
                'valor_centavos'   => $i === $qtdParcelas ? $valorUltima : $valorParcela,
                'metodo'           => $i === 1 ? $metodoPrimeira->value : $metodoDemais->value,
                'vencimento_exemplo' => Carbon::today()->addMonths($i - 1)->format('Y-m-d'),
            ];
        }

        return new SimulacaoParcelamentoDTO(
            valorTotalCentavos:        $valorTotal,
            qtdParcelas:               $qtdParcelas,
            valorPrimeiraCentavos:     $valorParcela,
            valorDemaisCentavos:       $valorParcela,
            metodoPrimeiraParcela:     $metodoPrimeira,
            metodoDemais:              $metodoDemais,
            parcelas:                  $parcelas,
        );
    }
}
```

---

### 3.6 `CriarAdesaoAction`

```php
<?php
declare(strict_types=1);

namespace App\Actions\Adesoes;

use App\Enums\StatusAdesao;
use App\Enums\StatusParcela;
use App\Events\Adesoes\AdesaoCriada;
use App\Exceptions\AdesaoJaExistenteException;
use App\Models\Adesao;
use App\Models\PortalUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class CriarAdesaoAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(PortalUser $user, array $data): Adesao
    {
        // Verificar se já existe adesão ativa no evento
        $jaExiste = Adesao::query()
            ->where('portal_user_id', $user->id)
            ->whereHas('evento', fn ($q) => $q->where('ulid', $data['evento_ulid']))
            ->whereNotIn('status', [StatusAdesao::CANCELADA->value])
            ->exists();

        if ($jaExiste) {
            throw new AdesaoJaExistenteException('Formando já possui adesão ativa neste evento.');
        }

        return DB::transaction(function () use ($user, $data) {
            $pacote = \App\Models\Pacote::where('ulid', $data['pacote_ulid'])->firstOrFail();
            $evento = \App\Models\Evento::where('ulid', $data['evento_ulid'])->firstOrFail();
            $turma  = \App\Models\Turma::where('ulid', $data['turma_ulid'])->firstOrFail();

            $valorTotal   = $pacote->preco_centavos;
            $qtdParcelas  = $data['plano']['qtd_parcelas'];
            $valorParcela = intdiv($valorTotal, $qtdParcelas);
            $valorUltima  = $valorTotal - ($valorParcela * ($qtdParcelas - 1));
            $dia          = $data['plano']['data_vencimento_dia'];

            $adesao = Adesao::create([
                'portal_user_id'             => $user->id,
                'evento_id'                  => $evento->id,
                'turma_id'                   => $turma->id,
                'pacote_id'                  => $pacote->id,
                'status'                     => StatusAdesao::PENDENTE_PAGAMENTO,
                'valor_total_centavos'       => $valorTotal,
                'qtd_parcelas'               => $qtdParcelas,
                'metodo_primeira_parcela'    => $data['plano']['metodo_primeira_parcela'],
                'metodo_demais'              => $data['plano']['metodo_demais'],
                'data_vencimento_dia'        => $dia,
                'responsavel_mesmo_formando' => $data['responsavel']['mesmo_formando'],
                'responsavel_nome'           => $data['responsavel']['nome'] ?? null,
                'responsavel_cpf'            => $data['responsavel']['cpf'] ?? null,
                'responsavel_email'          => $data['responsavel']['email'] ?? null,
                'responsavel_telefone'       => $data['responsavel']['telefone'] ?? null,
                'aceitou_termos'             => $data['aceitou_termos'],
                'termos_versao'              => $data['termos_versao'],
                'termos_aceitos_at'          => now(),
                'pacote_snapshot'            => $pacote->toSnapshotArray(),
            ]);

            // Criar parcelas
            for ($i = 1; $i <= $qtdParcelas; $i++) {
                $adesao->parcelas()->create([
                    'numero'          => $i,
                    'valor_centavos'  => $i === $qtdParcelas ? $valorUltima : $valorParcela,
                    'metodo'          => $i === 1 ? $data['plano']['metodo_primeira_parcela'] : $data['plano']['metodo_demais'],
                    'status'          => StatusParcela::PENDENTE,
                    'data_vencimento' => Carbon::today()->setDay($dia)->addMonths($i - 1),
                ]);
            }

            event(new AdesaoCriada($adesao));

            return $adesao->fresh(['evento', 'pacote', 'parcelas']);
        });
    }
}
```

---

### 3.7 `StoreAdesaoRequest`

```php
<?php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAdesaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evento_ulid'                      => ['required', 'string', 'size:26'],
            'turma_ulid'                       => ['required', 'string', 'size:26'],
            'pacote_ulid'                      => ['required', 'string', 'size:26'],
            'plano.qtd_parcelas'               => ['required', 'integer', 'min:1', 'max:12'],
            'plano.metodo_primeira_parcela'    => ['required', 'in:pix,boleto,cartao'],
            'plano.metodo_demais'              => ['required', 'in:boleto,cartao'],
            'plano.data_vencimento_dia'        => ['required', 'in:1,5,10,15,20,25'],
            'responsavel.mesmo_formando'       => ['required', 'boolean'],
            'responsavel.nome'                 => ['required_if:responsavel.mesmo_formando,false', 'nullable', 'string', 'max:150'],
            'responsavel.cpf'                  => ['required_if:responsavel.mesmo_formando,false', 'nullable', 'cpf'],
            'responsavel.email'                => ['required_if:responsavel.mesmo_formando,false', 'nullable', 'email', 'max:150'],
            'responsavel.telefone'             => ['nullable', 'string', 'max:30'],
            'aceitou_termos'                   => ['required', 'accepted'],
            'termos_versao'                    => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'evento_ulid.required'                   => 'Evento obrigatório.',
            'pacote_ulid.required'                   => 'Selecione um pacote.',
            'plano.qtd_parcelas.min'                 => 'Mínimo 1 parcela.',
            'plano.qtd_parcelas.max'                 => 'Máximo 12 parcelas.',
            'plano.metodo_primeira_parcela.in'       => 'Método de pagamento inválido.',
            'responsavel.cpf.cpf'                    => 'CPF do responsável inválido.',
            'aceitou_termos.accepted'                => 'Você deve aceitar os termos para prosseguir.',
        ];
    }
}
```

---

### 3.8 Testes Pest (mínimo 12 cenários obrigatórios)

```php
// tests/Feature/Api/V1/Adesao/AdesaoStoreTest.php

it('cria adesão com responsável mesmo formando', function () {
    $user = PortalUser::factory()->comFormandoAtivo()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->withHeader('X-Idempotency-Key', fake()->uuid())
        ->postJson('/api/v1/adesoes', dadosAdesaoValidos(['responsavel' => ['mesmo_formando' => true]]));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pendente_pagamento')
        ->assertHeader('Location');
});

it('cria adesão com responsável terceiro com CPF válido', function () {
    $user = PortalUser::factory()->comFormandoAtivo()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->withHeader('X-Idempotency-Key', fake()->uuid())
        ->postJson('/api/v1/adesoes', dadosAdesaoValidos([
            'responsavel' => ['mesmo_formando' => false, 'nome' => 'Maria Silva', 'cpf' => '529.982.247-25', 'email' => 'maria@x.com'],
        ]));

    $response->assertCreated()
        ->assertJsonPath('data.responsavel.nome', 'Maria Silva');
});

it('retorna 409 AdesaoJaExistente se formando já tem adesão ativa', function () {
    $user   = PortalUser::factory()->comAdesaoAtiva()->create();

    $this->actingAs($user, 'sanctum')
        ->withHeader('X-Idempotency-Key', fake()->uuid())
        ->postJson('/api/v1/adesoes', dadosAdesaoValidos())
        ->assertStatus(409)
        ->assertJsonPath('error', 'AdesaoJaExistente');
});

it('idempotência: duplo submit com mesma chave não duplica adesão', function () {
    $user = PortalUser::factory()->comFormandoAtivo()->create();
    $key  = fake()->uuid();

    $payload = dadosAdesaoValidos();

    $this->actingAs($user, 'sanctum')->withHeader('X-Idempotency-Key', $key)->postJson('/api/v1/adesoes', $payload)->assertCreated();
    $this->actingAs($user, 'sanctum')->withHeader('X-Idempotency-Key', $key)->postJson('/api/v1/adesoes', $payload)->assertCreated(); // mesmo 201

    expect(Adesao::where('portal_user_id', $user->id)->count())->toBe(1);
});

it('retorna 422 quando aceitou_termos é false', function () {
    $user = PortalUser::factory()->comFormandoAtivo()->create();

    $this->actingAs($user, 'sanctum')
        ->withHeader('X-Idempotency-Key', fake()->uuid())
        ->postJson('/api/v1/adesoes', dadosAdesaoValidos(['aceitou_termos' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('aceitou_termos');
});

it('retorna 422 quando qtd_parcelas maior que 12', function () { /* ... */ });

it('retorna 422 quando responsavel.cpf inválido (mod 11)', function () { /* ... */ });

it('retorna 401 sem autenticação', function () {
    $this->postJson('/api/v1/adesoes', dadosAdesaoValidos())
        ->assertUnauthorized();
});

// tests/Feature/Api/V1/Adesao/AdesaoSimularTest.php

it('simula parcelamento e retorna tabela de parcelas', function () {
    $user   = PortalUser::factory()->comFormandoAtivo()->create();
    $pacote = Pacote::factory()->withPreco(1500000)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/adesoes/simular', [
            'evento_ulid'           => $pacote->evento->ulid,
            'pacote_ulid'           => $pacote->ulid,
            'qtd_parcelas'          => 10,
            'metodo_primeira_parcela' => 'pix',
            'metodo_demais'         => 'boleto',
        ])
        ->assertOk()
        ->assertJsonCount(10, 'data.parcelas')
        ->assertJsonPath('data.valor_total_centavos', 1500000);
});

it('retorna 404 quando pacote não pertence ao evento', function () { /* ... */ });

it('retorna 422 quando qtd_parcelas zero', function () { /* ... */ });

it('retorna 429 quando rate limit estourado', function () { /* ... */ });
```

---

## 4. Frontend — React 19 SPA

### 4.1 Arquivos a criar/modificar

| Arquivo                                                      | Ação  | Responsabilidade                                                |
| ------------------------------------------------------------ | ----- | --------------------------------------------------------------- |
| `resources/spa/src/stores/wizard-store.ts`                   | Criar | Estado do wizard com `persist` em `sessionStorage`.             |
| `resources/spa/src/api/hooks/use-adesao.ts`                  | Criar | `useMeAdesoes`, `usePacotes`, `useSimulacao`, `useCriarAdesao`. |
| `resources/spa/src/components/wizard/wizard-shell.tsx`       | Criar | Layout container + renderiza etapa ativa.                       |
| `resources/spa/src/components/wizard/wizard-progress.tsx`    | Criar | Stepper visual com 7 passos.                                    |
| `resources/spa/src/components/wizard/step-1-personal.tsx`    | Criar | Formulário dados pessoais (CPF, telefone, turma).               |
| `resources/spa/src/components/wizard/step-2-responsavel.tsx` | Criar | Formulário responsável financeiro (checkbox + campos).          |
| `resources/spa/src/components/wizard/step-3-pacote.tsx`      | Criar | Lista de pacotes com seleção.                                   |
| `resources/spa/src/components/wizard/step-4-plano.tsx`       | Criar | Seletor de parcelas + simulação + tabela de parcelas.           |
| `resources/spa/src/components/wizard/step-5-termos.tsx`      | Criar | Visualizador de termos + checkbox de aceite.                    |
| `resources/spa/src/components/wizard/step-6-revisao.tsx`     | Criar | Read-only de todos os dados acumulados.                         |
| `resources/spa/src/components/wizard/step-7-confirm.tsx`     | Criar | Commit adesão + pagamento intent + redirect.                    |
| `resources/spa/src/forms/adesao/step-1.schema.ts`            | Criar | Zod schema etapa 1 (CPF com validação mod 11).                  |
| `resources/spa/src/forms/adesao/step-2.schema.ts`            | Criar | Zod schema etapa 2 (responsável condicional).                   |
| `resources/spa/src/forms/adesao/step-3.schema.ts`            | Criar | Zod schema etapa 3 (pacote selecionado).                        |
| `resources/spa/src/forms/adesao/step-4.schema.ts`            | Criar | Zod schema etapa 4 (plano de pagamento).                        |
| `resources/spa/src/forms/adesao/step-5.schema.ts`            | Criar | Zod schema etapa 5 (termos aceitos).                            |
| `resources/spa/src/forms/adesao/step-6.schema.ts`            | Criar | Zod schema etapa 6 (revisão confirmada).                        |
| `resources/spa/src/forms/adesao/step-7.schema.ts`            | Criar | Zod schema etapa 7 (confirmação final, sem campos extras).      |
| `resources/spa/src/routes/portal/adesao/$step.tsx`           | Criar | Rota file-based com `parseParams` + `beforeLoad` guard.         |
| `resources/spa/tests/unit/wizard-store.test.ts`              | Criar | 6 testes Vitest para reducers e migração.                       |
| `resources/spa/tests/integration/wizard-steps.test.tsx`      | Criar | 7 testes RTL + MSW (um por etapa).                              |
| `resources/spa/tests/e2e/wizard-adesao.spec.ts`              | Criar | Happy path completo + cenário de retomada.                      |

---

### 4.2 `stores/wizard-store.ts`

```typescript
// resources/spa/src/stores/wizard-store.ts
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import type { MetodoPagamento } from '@/api/types.gen';

export type WizardStep = 1 | 2 | 3 | 4 | 5 | 6 | 7;

export interface WizardFormData {
    step1?: {
        cpf: string;
        telefone: string;
        data_nascimento: string;
        turma_ulid: string;
    };
    step2?: {
        responsavel_mesmo: boolean;
        responsavel?: {
            nome: string;
            cpf: string;
            email: string;
            telefone: string;
        };
    };
    step3?: { pacote_ulid: string };
    step4?: {
        qtd_parcelas: number;
        metodo_primeira_parcela: MetodoPagamento;
        metodo_demais: MetodoPagamento;
        data_vencimento_dia: 1 | 5 | 10 | 15 | 20 | 25;
    };
    step5?: { aceitou_termos: boolean; aceitou_em: string; termos_versao: string };
    step6?: { revisado: boolean };
    step7?: { pagamento_intent_id: string };
}

interface WizardState {
    currentStep: WizardStep;
    formData: WizardFormData;
    adesaoUlid: string | null;
    pagamentoIntentId: string | null;

    setStep: (s: WizardStep) => void;
    next: () => void;
    prev: () => void;
    setStepData: <K extends keyof WizardFormData>(step: K, data: WizardFormData[K]) => void;
    setAdesaoUlid: (id: string) => void;
    setPagamentoIntentId: (id: string) => void;
    reset: () => void;
}

export const useWizardStore = create<WizardState>()(
    persist(
        (set, get) => ({
            currentStep: 1,
            formData: {},
            adesaoUlid: null,
            pagamentoIntentId: null,

            setStep: (s) => set({ currentStep: s }),
            next: () =>
                set((st) => ({
                    currentStep: Math.min(7, st.currentStep + 1) as WizardStep,
                })),
            prev: () =>
                set((st) => ({
                    currentStep: Math.max(1, st.currentStep - 1) as WizardStep,
                })),
            setStepData: (step, data) => set((st) => ({ formData: { ...st.formData, [step]: data } })),
            setAdesaoUlid: (id) => set({ adesaoUlid: id }),
            setPagamentoIntentId: (id) => set({ pagamentoIntentId: id }),
            reset: () =>
                set({
                    currentStep: 1,
                    formData: {},
                    adesaoUlid: null,
                    pagamentoIntentId: null,
                }),
        }),
        {
            name: 'wizard-adesao',
            // OBRIGATÓRIO: sessionStorage, nunca localStorage (dados sensíveis: CPF)
            storage: createJSONStorage(() => sessionStorage),
            version: 1,
            migrate: () => ({
                currentStep: 1 as WizardStep,
                formData: {},
                adesaoUlid: null,
                pagamentoIntentId: null,
            }),
        },
    ),
);
```

---

### 4.3 `api/hooks/use-adesao.ts`

```typescript
// resources/spa/src/api/hooks/use-adesao.ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '../client';
import { getIdempotencyKey, clearIdempotencyKey } from '@/lib/idempotency';
import type { AdesaoDto, SimulacaoDto, PacoteDto } from '../types.gen';

export const adesaoKeys = {
    meAdesoes: (filtros?: Record<string, string>) => ['me', 'adesoes', filtros] as const,
    pacotes: (eventoUlid: string) => ['pacotes', eventoUlid] as const,
};

// --- Listagem de adesões (verifica se skip o wizard) ---
export function useMeAdesoes(filtros?: { status?: string; evento_id?: string }) {
    return useQuery({
        queryKey: adesaoKeys.meAdesoes(filtros),
        queryFn: async () => {
            const { data } = await api.get<{ data: AdesaoDto[]; meta: unknown }>('/me/adesoes', {
                params: {
                    'filter[status]': filtros?.status,
                    'filter[evento_id]': filtros?.evento_id,
                },
            });
            return data;
        },
        staleTime: 30_000,
    });
}

// --- Pacotes do evento (etapa 3) ---
export function usePacotes(eventoUlid: string | null) {
    return useQuery({
        queryKey: adesaoKeys.pacotes(eventoUlid ?? ''),
        enabled: !!eventoUlid,
        queryFn: async () => {
            const { data } = await api.get<{ data: PacoteDto[] }>(`/eventos/${eventoUlid}/pacotes`, {
                params: { 'filter[ativo]': true, sort: 'preco_centavos' },
            });
            return data.data;
        },
        staleTime: 10 * 60_000, // pacotes mudam raramente
    });
}

// --- Simulação de parcelamento (etapa 4) ---
interface SimularInput {
    evento_ulid: string;
    pacote_ulid: string;
    qtd_parcelas: number;
    metodo_primeira_parcela: string;
    metodo_demais: string;
}

export function useSimulacao() {
    return useMutation({
        mutationFn: async (input: SimularInput) => {
            const { data } = await api.post<{ data: SimulacaoDto }>('/adesoes/simular', input);
            return data.data;
        },
    });
}

// --- Criar adesão (commit etapa 7) ---
interface CriarAdesaoInput {
    evento_ulid: string;
    turma_ulid: string;
    pacote_ulid: string;
    plano: {
        qtd_parcelas: number;
        metodo_primeira_parcela: string;
        metodo_demais: string;
        data_vencimento_dia: number;
    };
    responsavel: {
        mesmo_formando: boolean;
        nome?: string;
        cpf?: string;
        email?: string;
        telefone?: string;
    };
    aceitou_termos: boolean;
    termos_versao: string;
}

export function useCriarAdesao() {
    const qc = useQueryClient();

    return useMutation({
        mutationFn: async (input: CriarAdesaoInput) => {
            const scope = `adesao:criar:${input.evento_ulid}:${input.pacote_ulid}`;
            const key = getIdempotencyKey(scope);

            const { data } = await api.post<{ data: AdesaoDto }>('/adesoes', input, {
                headers: { 'X-Idempotency-Key': key },
            });

            clearIdempotencyKey(scope);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['me', 'adesoes'] });
            qc.invalidateQueries({ queryKey: ['extrato'] });
        },
    });
}

// --- Criar pagamento intent (após commit adesão) ---
interface CriarIntentInput {
    adesao_ulid: string;
    parcela_numero: number;
    metodo: string;
}

export function useCriarPagamentoIntent() {
    return useMutation({
        mutationFn: async (input: CriarIntentInput) => {
            const scope = `pagamento:intent:${input.adesao_ulid}:${input.parcela_numero}`;
            const key = getIdempotencyKey(scope);

            const { data } = await api.post<{ data: { id: string; status: string; pix?: unknown } }>(
                '/pagamentos/intents',
                input,
                { headers: { 'X-Idempotency-Key': key } },
            );

            clearIdempotencyKey(scope);
            return data.data;
        },
    });
}
```

---

### 4.4 Zod schemas por etapa

```typescript
// resources/spa/src/forms/adesao/step-1.schema.ts
import { z } from 'zod';

// Validação CPF mod 11 (algoritmo completo)
function validarCpf(cpf: string): boolean {
    const nums = cpf.replace(/\D/g, '');
    if (nums.length !== 11 || /^(\d)\1+$/.test(nums)) return false;
    const soma1 = nums
        .slice(0, 9)
        .split('')
        .reduce((acc, d, i) => acc + +d * (10 - i), 0);
    const dig1 = soma1 % 11 < 2 ? 0 : 11 - (soma1 % 11);
    if (+nums[9] !== dig1) return false;
    const soma2 = nums
        .slice(0, 10)
        .split('')
        .reduce((acc, d, i) => acc + +d * (11 - i), 0);
    const dig2 = soma2 % 11 < 2 ? 0 : 11 - (soma2 % 11);
    return +nums[10] === dig2;
}

export const step1Schema = z.object({
    cpf: z
        .string({ required_error: 'CPF é obrigatório.' })
        .regex(/^\d{3}\.\d{3}\.\d{3}-\d{2}$/, 'Formato inválido. Use: 000.000.000-00.')
        .refine(validarCpf, 'CPF inválido.'),
    telefone: z
        .string({ required_error: 'Telefone é obrigatório.' })
        .min(10, 'Telefone muito curto.')
        .max(30, 'Telefone muito longo.'),
    data_nascimento: z
        .string({ required_error: 'Data de nascimento é obrigatória.' })
        .regex(/^\d{4}-\d{2}-\d{2}$/, 'Formato inválido. Use: AAAA-MM-DD.'),
    turma_ulid: z
        .string({ required_error: 'Turma não identificada. Entre em contato com a organização.' })
        .length(26, 'Identificador de turma inválido.'),
});
export type Step1Data = z.infer<typeof step1Schema>;
```

```typescript
// resources/spa/src/forms/adesao/step-2.schema.ts
import { z } from 'zod';

const responsavelSchema = z.object({
    nome: z.string({ required_error: 'Nome do responsável é obrigatório.' }).max(150),
    cpf: z
        .string({ required_error: 'CPF do responsável é obrigatório.' })
        .regex(/^\d{3}\.\d{3}\.\d{3}-\d{2}$/, 'Formato inválido.'),
    email: z.string({ required_error: 'E-mail do responsável é obrigatório.' }).email('E-mail inválido.'),
    telefone: z.string().max(30).optional(),
});

export const step2Schema = z.discriminatedUnion('responsavel_mesmo', [
    z.object({ responsavel_mesmo: z.literal(true) }),
    z.object({ responsavel_mesmo: z.literal(false), responsavel: responsavelSchema }),
]);
export type Step2Data = z.infer<typeof step2Schema>;
```

```typescript
// resources/spa/src/forms/adesao/step-4.schema.ts
import { z } from 'zod';

export const step4Schema = z.object({
    qtd_parcelas: z
        .number({ required_error: 'Selecione o número de parcelas.' })
        .int('Deve ser um número inteiro.')
        .min(1, 'Mínimo 1 parcela.')
        .max(12, 'Máximo 12 parcelas.'),
    metodo_primeira_parcela: z.enum(['pix', 'boleto', 'cartao'], {
        required_error: 'Selecione o método da primeira parcela.',
        invalid_type_error: 'Método inválido.',
    }),
    metodo_demais: z.enum(['boleto', 'cartao'], {
        required_error: 'Selecione o método das demais parcelas.',
    }),
    data_vencimento_dia: z.union(
        [z.literal(1), z.literal(5), z.literal(10), z.literal(15), z.literal(20), z.literal(25)],
        { required_error: 'Selecione o dia de vencimento.' },
    ),
});
export type Step4Data = z.infer<typeof step4Schema>;
```

```typescript
// resources/spa/src/forms/adesao/step-5.schema.ts
import { z } from 'zod';

export const step5Schema = z.object({
    aceitou_termos: z.literal(true, {
        errorMap: () => ({ message: 'Você deve aceitar os termos para prosseguir.' }),
    }),
    termos_versao: z.string().min(1),
    aceitou_em: z.string().datetime(),
});
export type Step5Data = z.infer<typeof step5Schema>;
```

---

### 4.5 `routes/portal/adesao/$step.tsx`

```typescript
// resources/spa/src/routes/portal/adesao/$step.tsx
import { createFileRoute, redirect } from '@tanstack/react-router'
import { useWizardStore } from '@/stores/wizard-store'
import { WizardShell } from '@/components/wizard/wizard-shell'

export const Route = createFileRoute('/portal/adesao/$step')({
  parseParams: ({ step }) => {
    const n = Number.parseInt(step, 10)
    if (![1, 2, 3, 4, 5, 6, 7].includes(n)) {
      throw redirect({ to: '/portal/adesao/$step', params: { step: '1' } })
    }
    return { step: n as 1 | 2 | 3 | 4 | 5 | 6 | 7 }
  },
  beforeLoad: async ({ params }) => {
    // Bloquear acesso a etapas além da atual
    const max = useWizardStore.getState().currentStep
    if (params.step > max) {
      throw redirect({
        to: '/portal/adesao/$step',
        params: { step: String(max) },
      })
    }
  },
  component: function WizardRoute() {
    const { step } = Route.useParams()
    return <WizardShell currentStep={step} />
  },
})
```

---

### 4.6 `components/wizard/wizard-shell.tsx`

```typescript
// resources/spa/src/components/wizard/wizard-shell.tsx
import React from 'react'
import { WizardProgress } from './wizard-progress'
import { Step1Personal } from './step-1-personal'
import { Step2Responsavel } from './step-2-responsavel'
import { Step3Pacote } from './step-3-pacote'
import { Step4Plano } from './step-4-plano'
import { Step5Termos } from './step-5-termos'
import { Step6Revisao } from './step-6-revisao'
import { Step7Confirm } from './step-7-confirm'
import { useWizardStore, type WizardStep } from '@/stores/wizard-store'
import { useNavigate } from '@tanstack/react-router'

interface WizardShellProps {
  currentStep: WizardStep
}

const STEP_LABELS: Record<WizardStep, string> = {
  1: 'Dados pessoais',
  2: 'Responsável financeiro',
  3: 'Pacote',
  4: 'Plano de pagamento',
  5: 'Termos e condições',
  6: 'Revisão',
  7: 'Confirmação',
}

export function WizardShell({ currentStep }: WizardShellProps) {
  const navigate = useNavigate()
  const { next, prev } = useWizardStore()

  const handleNext = () => {
    next()
    navigate({ to: '/portal/adesao/$step', params: { step: String(currentStep + 1) } })
  }

  const handlePrev = () => {
    prev()
    navigate({ to: '/portal/adesao/$step', params: { step: String(currentStep - 1) } })
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <WizardProgress current={currentStep} total={7} labels={STEP_LABELS} />
      <div className="mt-8">
        {currentStep === 1 && <Step1Personal onNext={handleNext} />}
        {currentStep === 2 && <Step2Responsavel onNext={handleNext} onPrev={handlePrev} />}
        {currentStep === 3 && <Step3Pacote onNext={handleNext} onPrev={handlePrev} />}
        {currentStep === 4 && <Step4Plano onNext={handleNext} onPrev={handlePrev} />}
        {currentStep === 5 && <Step5Termos onNext={handleNext} onPrev={handlePrev} />}
        {currentStep === 6 && <Step6Revisao onNext={handleNext} onPrev={handlePrev} />}
        {currentStep === 7 && <Step7Confirm onPrev={handlePrev} />}
      </div>
    </div>
  )
}
```

---

### 4.7 `components/wizard/step-7-confirm.tsx` — commit atômico

```typescript
// resources/spa/src/components/wizard/step-7-confirm.tsx
import React from 'react'
import { useNavigate } from '@tanstack/react-router'
import { toast } from '@/lib/toast'
import { ApiError } from '@/api/errors'
import { useCriarAdesao, useCriarPagamentoIntent } from '@/api/hooks/use-adesao'
import { useWizardStore } from '@/stores/wizard-store'
import { useAuthStore } from '@/stores/auth-store'

interface Props {
  onPrev: () => void
}

export function Step7Confirm({ onPrev }: Props) {
  const navigate = useNavigate()
  const { formData, setAdesaoUlid, setPagamentoIntentId, reset } = useWizardStore()
  const user = useAuthStore((s) => s.user)
  const criarAdesao = useCriarAdesao()
  const criarIntent = useCriarPagamentoIntent()

  const isLoading = criarAdesao.isPending || criarIntent.isPending

  const handleConfirmar = async () => {
    if (!formData.step1 || !formData.step3 || !formData.step4 || !formData.step5 || !user) return

    try {
      // Etapa 7A: criar adesão
      const adesao = await criarAdesao.mutateAsync({
        evento_ulid: user.formandos[0]?.evento.id ?? '',
        turma_ulid: formData.step1.turma_ulid,
        pacote_ulid: formData.step3.pacote_ulid,
        plano: {
          qtd_parcelas: formData.step4.qtd_parcelas,
          metodo_primeira_parcela: formData.step4.metodo_primeira_parcela,
          metodo_demais: formData.step4.metodo_demais,
          data_vencimento_dia: formData.step4.data_vencimento_dia,
        },
        responsavel: formData.step2?.responsavel_mesmo
          ? { mesmo_formando: true }
          : {
              mesmo_formando: false,
              nome: formData.step2?.responsavel?.nome,
              cpf: formData.step2?.responsavel?.cpf,
              email: formData.step2?.responsavel?.email,
              telefone: formData.step2?.responsavel?.telefone,
            },
        aceitou_termos: true,
        termos_versao: formData.step5.termos_versao,
      })

      setAdesaoUlid(adesao.id)

      // Etapa 7B: iniciar pagamento inicial
      const intent = await criarIntent.mutateAsync({
        adesao_ulid: adesao.id,
        parcela_numero: 1,
        metodo: formData.step4.metodo_primeira_parcela,
      })

      setPagamentoIntentId(intent.id)
      reset() // Limpar wizard após sucesso

      toast.success('Adesão confirmada com sucesso!')
      navigate({ to: '/portal/pagamento/$parcela_ulid', params: { parcela_ulid: intent.id } })
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.error === 'AdesaoJaExistente') {
          toast.info('Você já possui uma adesão ativa. Redirecionando...')
          reset()
          navigate({ to: '/portal/home' })
          return
        }
        if (err.error === 'InvariantViolation') {
          toast.error('O pacote selecionado não está mais disponível. Selecione outro.')
          onPrev() // Volta para etapa 3
          return
        }
        if (err.status === 0) {
          toast.error('Falha de rede. Seu progresso foi salvo. Tente novamente.')
          return
        }
      }
      toast.error(`Erro inesperado. Tente novamente.`)
    }
  }

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold text-gray-900">Confirmar adesão</h2>
      <p className="text-gray-600">
        Ao confirmar, sua adesão será registrada e o primeiro pagamento será iniciado.
        Esta ação não pode ser desfeita.
      </p>
      <div className="flex gap-3">
        <button
          type="button"
          onClick={onPrev}
          disabled={isLoading}
          className="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-gray-700 disabled:opacity-50"
        >
          Voltar
        </button>
        <button
          type="button"
          onClick={handleConfirmar}
          disabled={isLoading}
          className="flex-1 rounded-lg bg-blue-600 px-4 py-3 text-white font-medium disabled:opacity-50"
        >
          {isLoading ? 'Processando...' : 'Confirmar adesão'}
        </button>
      </div>
    </div>
  )
}
```

---

### 4.8 Tratamento de erros (por código HTTP + `error`)

| `ApiError.error`      | HTTP | Contexto          | UX                                                                      |
| --------------------- | ---- | ----------------- | ----------------------------------------------------------------------- |
| `AdesaoJaExistente`   | 409  | POST /adesoes     | Toast informativo + reset + redirect `/portal/home`                     |
| `IdempotencyConflict` | 409  | POST mutações     | Limpa chave idempotente; se persistir, reset wizard + toast explicativo |
| `InvariantViolation`  | 409  | POST /adesoes     | Toast + volta para etapa 3 (pacote descontinuado)                       |
| `ValidationError`     | 422  | Qualquer submit   | `setError` inline nos campos via `details.fields`                       |
| `RateLimitExceeded`   | 429  | Simular ou commit | Banner com contagem regressiva; botão desativado por `Retry-After`s     |
| `Unauthenticated`     | 401  | Qualquer hook     | Interceptor global; wizard state preservado em sessionStorage           |
| `ServiceUnavailable`  | 0    | Rede cai          | Toast "Falha de rede. Tente novamente." + estado preservado             |
| `InternalServerError` | 500  | Qualquer          | Toast com `request_id` truncado para suporte                            |

---

## 5. Ordem de implementação (5 Gates)

### 5.1 Gate A — Foundation Backend

1. Criar migrations `adesoes` e `parcelas`.
2. Criar models `Adesao`, `Parcela` com enums `StatusAdesao`, `StatusParcela`, `MetodoPagamento`.
3. Criar relacionamentos: `PortalUser hasMany Adesao`, `Adesao hasMany Parcela`, `Adesao belongsTo Evento`, `Adesao belongsTo Pacote`.
4. Criar factories e seeders de desenvolvimento.
5. Criar migration para tabela `pacotes` se ainda não existe (endpoint GET /pacotes depende dela).

> **Gate A done quando:** `php artisan migrate:fresh --seed` sem erros e `Adesao::factory()->create()` gera registros válidos.

### 5.2 Gate B — Endpoints Backend

6. Criar `PacoteController@index` + `PacoteResource` + rota `GET /eventos/{ulid}/pacotes`.
7. Criar `SimularAdesaoRequest` + `SimularParcelamentoAction` + `AdesaoController@simular`.
8. Criar `StoreAdesaoRequest` + `CriarAdesaoAction` + `AdesaoController@store`.
9. Criar `AdesaoController@index` para `GET /me/adesoes`.
10. Criar `AdesaoResource`, `ParcelaResource`, `SimulacaoResource`.
11. Criar `EnsureIdempotency` middleware e registrá-lo nas rotas idempotentes.
12. Criar exceção `AdesaoJaExistenteException` com handler retornando 409.
13. Escrever os 12 testes Pest.

> **Gate B done quando:** `php artisan test --filter=Adesao` com 12/12 verdes.

### 5.3 Gate C — Frontend Foundation

14. Criar `stores/wizard-store.ts` com `persist` em `sessionStorage`.
15. Criar Zod schemas das 7 etapas em `forms/adesao/step-N.schema.ts`.
16. Criar `api/hooks/use-adesao.ts` com `useMeAdesoes`, `usePacotes`, `useSimulacao`, `useCriarAdesao`, `useCriarPagamentoIntent`.
17. Adicionar MSW handlers para os novos endpoints nos testes.

> **Gate C done quando:** `npm run typecheck` verde + testes unit do wizard-store passam.

### 5.4 Gate D — UI (Componentes e Rota)

18. Criar `components/wizard/wizard-progress.tsx` (stepper visual acessível).
19. Criar `components/wizard/step-1-personal.tsx` até `step-7-confirm.tsx`.
20. Criar `components/wizard/wizard-shell.tsx`.
21. Criar rota `routes/portal/adesao/$step.tsx` com `parseParams` + `beforeLoad`.
22. Adicionar verificação de adesão existente no `beforeLoad` de `/portal/home` ou `/portal/adesao/$step`.
23. Smoke test manual: percorrer todas as 7 etapas em dev.

> **Gate D done quando:** smoke manual 7 etapas completo em Chromium, Firefox e WebKit.

### 5.5 Gate E — Testes

24. Escrever `wizard-store.test.ts` (6 testes Vitest).
25. Escrever `wizard-steps.test.tsx` (7 testes RTL + MSW — um por etapa).
26. Escrever `wizard-adesao.spec.ts` (Playwright: happy path + retomada após reload).
27. CI: `npm run quality` + `php artisan test`.

> **Gate E done quando:** todos os testes verdes + coverage wizard-store ≥ 90% + AdesaoController 100%.

---

## 6. Critérios de aceite (Gherkin PT-BR)

### CA-001 — Formando sem adesão inicia wizard na etapa 1

```gherkin
Dado que sou um formando autenticado sem adesão ativa
Quando acesso "/portal/home"
E clico em "Fazer adesão"
Então sou redirecionado para "/portal/adesao/1"
E o WizardProgress exibe etapa 1 de 7 destacada
E o formulário de dados pessoais está vazio
```

### CA-002 — Formando com adesão ativa ignora wizard

```gherkin
Dado que sou um formando autenticado com adesão no status "ativa"
Quando acesso "/portal/adesao/1"
Então sou redirecionado imediatamente para "/portal/home"
E o wizard não é renderizado
E vejo o dashboard com resumo da minha adesão
```

### CA-003 — Bloqueio de navegação para etapa futura

```gherkin
Dado que completei apenas a etapa 2 do wizard
Quando acesso diretamente "/portal/adesao/5" pela URL
Então sou redirecionado para "/portal/adesao/2"
E vejo a mensagem de que esta etapa ainda não está disponível
```

### CA-004 — Simulação de parcelas atualiza tabela (etapa 4)

```gherkin
Dado que estou na etapa 4 com pacote "Premium" de R$ 15.000,00 selecionado
Quando seleciono 10 parcelas com PIX na primeira e boleto nas demais
E clico em "Simular"
Então a tabela exibe 10 linhas
E a parcela 1 mostra "PIX — R$ 1.500,00"
E as parcelas 2 a 10 mostram "Boleto — R$ 1.500,00"
E o total "R$ 15.000,00" aparece no resumo
```

### CA-005 — Termos e revisão antes de confirmar

```gherkin
Dado que estou na etapa 5 de termos
Quando leio o documento e marco "Aceito os termos e condições"
E clico em "Próximo"
Então avanço para a etapa 6 de revisão
E vejo todos os dados das etapas 1 a 5 em modo somente-leitura
E posso clicar em "Voltar" para retornar à etapa 5
```

### CA-006 — Confirmação cria adesão e inicia pagamento (idempotência)

```gherkin
Dado que estou na etapa 7 com todos os dados preenchidos
Quando clico em "Confirmar adesão"
Então POST /api/v1/adesoes é chamado com X-Idempotency-Key
E POST /api/v1/pagamentos/intents é chamado com X-Idempotency-Key
E sou redirecionado para "/portal/pagamento/{intent_id}"
Quando clico novamente em "Confirmar adesão" acidentalmente (duplo submit)
Então apenas uma adesão é criada no banco
E a chave idempotente impede duplicação
```

### CA-007 — Falha de rede preserva estado do wizard

```gherkin
Dado que estou na etapa 7 prestes a confirmar
Quando a rede cai durante o POST /adesoes
Então vejo o toast "Falha de rede. Tente novamente."
E os dados do formulário permanecem preenchidos
E o wizard-store em sessionStorage contém as informações das etapas 1 a 6
Quando restauro a conexão e clico em "Confirmar adesão" novamente
Então o commit é realizado com sucesso com a mesma X-Idempotency-Key
```

### CA-008 — Retomada após reload na etapa 4

```gherkin
Dado que completei as etapas 1, 2 e 3 e estou na etapa 4
Quando recarrego a página (F5)
Então o wizard restaura exatamente a etapa 4
E os dados das etapas 1, 2 e 3 estão preservados no sessionStorage
E o formulário da etapa 4 está com os valores que havia digitado
```

### CA-009 — CPF inválido bloqueado na etapa 1

```gherkin
Dado que estou na etapa 1
Quando informo o CPF "000.000.000-00" (todos zeros)
E clico em "Próximo"
Então vejo a mensagem inline "CPF inválido." abaixo do campo CPF
E não avanço para a etapa 2
E nenhuma chamada a API é feita
```

### CA-010 — Responsável é o próprio formando (etapa 2 simplificada)

```gherkin
Dado que estou na etapa 2
Quando marco a opção "Sou o próprio responsável financeiro"
Então os campos Nome, CPF, E-mail e Telefone do responsável são ocultados
E clico em "Próximo" sem preencher dados adicionais
Então avanço para a etapa 3 com sucesso
E na revisão (etapa 6) o responsável aparece como "Mesmo formando"
```

---

## 7. Estratégia de testes

| Camada         | Arquivo                                             | Casos                                                                                  |
| -------------- | --------------------------------------------------- | -------------------------------------------------------------------------------------- |
| Unit FE        | `tests/unit/wizard-store.test.ts`                   | `next`, `prev`, `setStepData`, reset, migração v0→v1, sessionStorage.                  |
| Unit FE        | `tests/unit/step-1.schema.test.ts`                  | CPF válido, CPF inválido (todos iguais), CPF inválido (mod 11), telefone curto.        |
| Unit FE        | `tests/unit/step-2.schema.test.ts`                  | Responsável mesmo=true sem campos, responsável=false com CPF inválido.                 |
| Unit FE        | `tests/unit/step-4.schema.test.ts`                  | qtd_parcelas 0, 13, 1, 12; data_vencimento_dia inválida.                               |
| Integration FE | `tests/integration/wizard-steps.test.tsx` + MSW     | Etapa 3 carrega pacotes; etapa 4 simulação atualiza tabela; etapa 7 commit + redirect. |
| Unit BE (Pest) | `tests/Unit/StoreAdesaoRequestTest.php`             | Regras de validação por campo (cpf, aceitou_termos, qtd_parcelas).                     |
| Feature BE     | `tests/Feature/Api/V1/Adesao/AdesaoStoreTest.php`   | 8 cenários (ver §3.8).                                                                 |
| Feature BE     | `tests/Feature/Api/V1/Adesao/AdesaoSimularTest.php` | 4 cenários (ver §3.8).                                                                 |
| E2E            | `tests/e2e/wizard-adesao.spec.ts`                   | CA-001 happy path 7 etapas; CA-008 retomada após reload; CA-003 bloqueio de URL.       |
| Smoke          | `npm run smoke`                                     | `/portal/adesao/1` carrega sem erro de console; `/portal/adesao/8` redireciona.        |

**Coverage alvo:** wizard-store 90% · Step7Confirm 85% · AdesaoController 100% · SimularParcelamentoAction 100% · CriarAdesaoAction 100% · global ≥ 70%.

---

## 8. Blockers + open questions

### 8.1 Blockers Backend

- **B-W1** ❌ — Endpoint `GET /eventos/{ulid}/pacotes` ainda não existe (Gap G1). Bloqueia etapa 3 do wizard. Precisa de: migration `pacotes`, model `Pacote`, `PacoteController`, `PacoteResource`, rota registrada.
- **B-W2** ❌ — Endpoint `POST /adesoes/simular` ainda não existe (Gap G2). Bloqueia etapa 4. Precisa de: `SimularAdesaoRequest`, `SimularParcelamentoAction`, `SimulacaoResource`.
- **B-W3** ❌ — Endpoint `POST /adesoes` ainda não existe (Gap G3). Bloqueia etapa 7. Bloqueia também a verificação de skip wizard.
- **B-W4** ❌ — Middleware `EnsureIdempotency` não existe. Necessário para `POST /adesoes` e `POST /pagamentos/intents`. Implementação via Redis (chave com TTL de 24h).
- **B-W5** ❌ — Exceção `AdesaoJaExistenteException` não existe com handler 409 no envelope padrão.

### 8.2 Open questions

- **OQ-W1** — O campo `termos_versao` é servido pela API ou hardcoded no frontend? _Proposto:_ API serve via `GET /termos/vigente` retornando `{ versao: 'v2026-01', conteudo_url: '...' }`. Sem esse endpoint, o frontend hardcoda a versão no schema Zod.
- **OQ-W2** — Reajuste de parcelas por índice (IGPM/IPCA) entra no MVP ou é pós-MVP? _Proposto:_ pós-MVP; `SimularParcelamentoAction` calcula parcelas iguais por ora.
- **OQ-W3** — O dia de vencimento (1, 5, 10, 15, 20, 25) é configurável por evento ou global? _Proposto:_ global por ora; configuração por evento entra em Sprint de configurações.
- **OQ-W4** — Se o responsável financeiro é o próprio formando, os dados do formando (nome, CPF) são preenchidos automaticamente no payload ou omitidos? _Proposto:_ campo `responsavel.mesmo_formando = true` e os demais campos omitidos; backend preenche a partir do `PortalUser` autenticado.
- **OQ-W5** — Após 409 `AdesaoJaExistente`, devemos invalidar o wizard-store imediatamente? _Proposto:_ sim, chamar `wizardStore.reset()` antes de redirecionar para `/portal/home` para evitar inconsistência na volta.
- **OQ-W6** — O snapshot do pacote (`pacote_snapshot` em JSONB) deve incluir os benefícios textuais ou apenas preço/nome? _Proposto:_ incluir todos os campos relevantes (`nome`, `preco_centavos`, `beneficios`, `cotas_convite`) para garantir imutabilidade contratual.
- **OQ-W7** — Como tratar wizard parcialmente preenchido em sessão diferente (ex.: login em outro dispositivo)? _Proposto:_ wizard-store é por sessionStorage (por aba/sessão); não há sincronização entre dispositivos no MVP.

---

## 9. Matriz de rastreabilidade

| RF ([04-SRS](../frontend/04-FRONTEND-SRS.md)) | Endpoint                            | Hook / Componente FE                        | Teste (BE)                                      | Teste (FE)                                   |
| --------------------------------------------- | ----------------------------------- | ------------------------------------------- | ----------------------------------------------- | -------------------------------------------- |
| RF-010 Verificar adesão existente             | `GET /me/adesoes`                   | `useMeAdesoes` · `beforeLoad` rota          | `AdesaoStoreTest::retorna 409 adesão existente` | `wizard-steps.test::skip wizard se ativa`    |
| RF-011 Listar pacotes do evento               | `GET /eventos/{ulid}/pacotes`       | `usePacotes` · `Step3Pacote`                | (sem teste específico ainda — Gap G1)           | `wizard-steps.test::etapa 3 carrega pacotes` |
| RF-012 Simular parcelamento                   | `POST /adesoes/simular`             | `useSimulacao` · `Step4Plano`               | `AdesaoSimularTest::simula 10 parcelas`         | `wizard-steps.test::etapa 4 atualiza tabela` |
| RF-013 Aceitar termos digitalmente            | —                                   | `Step5Termos` · `step-5.schema`             | `StoreAdesaoRequestTest::aceitou_termos`        | `step-5.schema.test::literal(true) required` |
| RF-014 Revisar dados antes de confirmar       | —                                   | `Step6Revisao` (read-only)                  | —                                               | `wizard-steps.test::etapa 6 read-only`       |
| RF-015 Criar adesão (commit atômico)          | `POST /adesoes`                     | `useCriarAdesao` · `Step7Confirm`           | `AdesaoStoreTest::cria adesão com responsável`  | `wizard-adesao.spec::happy path completo`    |
| RF-016 Idempotência no commit                 | `POST /adesoes` (X-Idempotency-Key) | `getIdempotencyKey` · `useCriarAdesao`      | `AdesaoStoreTest::idempotência duplo submit`    | `wizard-steps.test::etapa 7 duplo clique`    |
| RF-017 Iniciar pagamento inicial              | `POST /pagamentos/intents`          | `useCriarPagamentoIntent` · `Step7Confirm`  | (coberto em SPEC-003)                           | `wizard-adesao.spec::redirect pagamento`     |
| RF-018 Retomada de sessão após reload         | —                                   | `wizard-store` (sessionStorage persist)     | —                                               | `wizard-adesao.spec::retomada etapa 4`       |
| RNF-003 CPF validado client-side (mod 11)     | —                                   | `step-1.schema` · `Step1Personal`           | `StoreAdesaoRequestTest::cpf inválido`          | `step-1.schema.test::CPF mod 11`             |
| RNF-006 Valores monetários em centavos        | `valor_total_centavos` (int)        | `lib/money.ts` · `Step4Plano` (formatação)  | `SimularTest::valor_total_centavos int`         | `wizard-steps.test::formata R$ corretamente` |
| RNF-010 WCAG 2.1 AA — wizard acessível        | —                                   | `WizardProgress` (aria-label, aria-current) | —                                               | `wizard-steps.test::a11y stepper`            |

---

## 10. Cross-refs

**Backend:**

- [api-contract.md §2.2 (GET /me/adesoes)](../api/api-contract.md)
- [api-contract.md §8.1 (POST /pagamentos/intents)](../api/api-contract.md)
- [api-conventions.md (idempotência, cursor pagination, envelope de erro)](../api/api-conventions.md)
- [error-envelope.md (formato 409, 422, 429)](../api/error-envelope.md)
- [PLANEJAMENTO_BACKEND_APIV1.md §2.4 (Guards e middlewares)](../prd/PLANEJAMENTO_BACKEND_APIV1.md)

**Frontend:**

- [09-TECHNICAL-DESIGN-CRITICAL-MODULES.md §2 (Módulo Wizard de Adesão)](../frontend/09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- [07-DATA-CONTRACTS-AND-VIEW-MODELS.md (AdesaoDto, SimulacaoDto, PacoteDto)](../frontend/07-DATA-CONTRACTS-AND-VIEW-MODELS.md)
- [08-API-INTEGRATION-CONTRACT.md §6 (idempotência, lib/idempotency.ts)](../frontend/08-API-INTEGRATION-CONTRACT.md)
- [PLANEJAMENTO_FRONTEND_REACT.md §6 (wizard-store), §8 (Zod schemas)](../prd/PLANEJAMENTO_FRONTEND_REACT.md)
- [04-FRONTEND-SRS.md §3 (RF-010 a RF-018)](../frontend/04-FRONTEND-SRS.md)

**Specs que dependem desta:**

- [SPEC-003 — Financeiro e Pagamento](./SPEC-003-financeiro-pagamento.md) _(a criar)_ — consome `adesaoUlid` gerado aqui
- [SPEC-004 — Extrato e Parcelas](./SPEC-004-extrato-parcelas.md) _(a criar)_ — consome parcelas criadas aqui
- [SPEC-006 — Mapa de Mesas](./SPEC-006-mapa-mesas-seating.md) _(a criar)_ — exige adesão ativa
- [SPEC-007 — Convites e Cotas](./SPEC-007-convites-cotas.md) _(a criar)_ — exige adesão ativa e cotas do pacote
- [SPEC-008 — Extras](./SPEC-008-extras.md) _(a criar)_ — exige adesão ativa

**Spec da qual depende:**

- [SPEC-001 — Autenticação](./SPEC-001-login.md) — `auth-store`, `api/client.ts`, guards de rota
