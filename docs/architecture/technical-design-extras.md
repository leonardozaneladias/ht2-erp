---
title: 'Technical Design — Extras (pedido, aprovação, pagamento, emissão derivada)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# Technical Design — Extras (Pedido Extra)

**Status:** Accepted | **Data:** 2026-04-18 | **Contexto:** Compra de produtos extras (convites adicionais, upgrades) e emissão derivada de convites após pagamento | **Tags:** extras, pagamento, emissão-derivada, snapshot

> Desenho técnico do bounded context `Extras`. Referencia ADR-0005 (idempotência), ADR-0008 (state-machine), ADR-0009 (snapshot), ADR-0013 (webhook), ADR-0014 (centavos) e §2.2 (rotas), §3.7 (contratos entre actions), §4.2 bloco F, §F6 do PLANEJAMENTO_BACKEND_APIV1.md.

## 1. Objetivo e invariantes

### 1.1 Objetivo

Permitir que formandos comprem produtos extras (convites adicionais acima da cota base, upgrades de mesa, menus premium) com um fluxo de: **pedido → aprovação (opcional) → pagamento → efeito**. Após pagamento confirmado via webhook, o sistema aplica automaticamente efeitos derivados (ex.: emitir N convites extras com snapshot da regra).

### 1.2 Invariantes duras

1. **Valores em `int` centavos** (ADR-0014) em DTO, banco e gateway.
2. **Snapshot imutável** capturado em `pedidos_extras.snapshot` no momento da transição para `pago` (ADR-0009): preço unitário, condição comercial, estoque no momento.
3. **Emissão derivada é idempotente** — `ConfirmarPagamentoExtraAction` só emite convites se `status != pago` antes da transição.
4. **Máquina de estados explícita** (ADR-0008): `rascunho → aguardando_aprovacao? → aprovado → pago | cancelado | estornado`.
5. **Aprovação pelo admin/comissão é opcional** por evento (config §Apêndice B pergunta 2) — default sem aprovação.
6. **Estoque é transacional**: `ProdutoExtra.estoque_qtd` decrementa em transação única com o pedido.

## 2. Modelo de domínio

### 2.1 Entidades

- `ProdutoExtra` (mestre por evento): nome, descrição, preco_unitario_centavos, estoque_tipo (`ilimitado | limitado_por_evento | limitado_por_formando`), estoque_qtd, ativo.
- `PedidoExtra` (transacional): formando, evento, status, valor_total_centavos, aprovado_por?, pago_at?, estornado_at?, snapshot JSONB.
- `PedidoExtraItem` (itens do pedido): produto_extra_id, qtd, preco_unitario_centavos_snapshot.

### 2.2 Enums (ADR-0010)

- `StatusPedidoExtra`: `Rascunho | AguardandoAprovacao | Aprovado | Pago | Cancelado | Estornado`
- `TipoEstoque`: `Ilimitado | LimitadoPorEvento | LimitadoPorFormando`

## 3. Fluxos — Mermaid

### 3.1 Criar pedido (CriarPedidoExtraAction)

```mermaid
sequenceDiagram
    autonumber
    participant C as Formando (SPA/RN)
    participant Ctl as PedidoExtraController
    participant A as CriarPedidoExtraAction
    participant DB as PostgreSQL
    participant Ev as Event Dispatcher

    C->>Ctl: POST /api/v1/eventos/{evento}/extras/pedidos<br/>X-Idempotency-Key
    Ctl->>A: execute(PedidoExtraData)
    A->>DB: SELECT pedido WHERE idempotency_key=key
    alt já existe
        A-->>Ctl: PedidoExtraResultData (estado atual)
    else novo
        A->>DB: BEGIN
        A->>DB: lockForUpdate em produtos_extras envolvidos
        loop cada item
            A->>A: valida estoque_qtd >= qtd requested
            A->>DB: UPDATE produtos_extras SET estoque_qtd = estoque_qtd - qtd
        end
        A->>A: calcula valor_total_centavos (soma itens)
        A->>DB: INSERT pedidos_extras (status=aprovado ou aguardando_aprovacao, snapshot vazio ainda)
        A->>DB: INSERT pedido_extra_itens (preco_unitario_centavos_snapshot = preço vigente)
        A->>DB: COMMIT
        A->>Ev: PedidoExtraCriado::dispatch
        A-->>Ctl: PedidoExtraResultData
    end
    Ctl-->>C: 201 + PedidoExtraResource
```

### 3.2 Aprovação (opcional — AprovarPedidoExtraAction)

```mermaid
sequenceDiagram
    autonumber
    participant Admin as Admin/Comissão
    participant Ctl as PedidoExtraController (Admin web)
    participant A as AprovarPedidoExtraAction
    participant DB as PostgreSQL

    Admin->>Ctl: POST /admin/extras/pedidos/{pedido}/aprovar
    Ctl->>A: execute(PedidoExtra, Admin)
    A->>DB: BEGIN
    A->>DB: SELECT ... FOR UPDATE WHERE id=pedido.id
    alt status != aguardando_aprovacao
        A-->>Ctl: throw InvariantViolationException (409)
    else ok
        A->>DB: UPDATE SET status=aprovado, aprovado_por_admin_id, aprovado_at=now()
        A->>DB: COMMIT
        A-->>Ctl: PedidoExtra
    end
```

### 3.3 Pagamento (cross-context para Pagamentos)

Formando chama `POST /api/v1/pagamentos/intents` com `referencia=pedido_extra_ulid` (ver technical-design-payments.md §4.1). O gateway dispara webhook de confirmação; `ProcessarWebhookPagamentoAction` decide e chama `ConfirmarPagamentoExtraAction`.

### 3.4 Confirmação de pagamento → emissão derivada (chave do design)

```mermaid
sequenceDiagram
    autonumber
    participant Proc as ProcessarWebhookPagamentoAction
    participant A as ConfirmarPagamentoExtraAction
    participant DB as PostgreSQL
    participant Emit as EmitirLoteConvitesAction
    participant Ev as Event Dispatcher

    Proc->>A: execute(pedido_ulid, webhookEvento)
    A->>DB: BEGIN
    A->>DB: SELECT pedido FOR UPDATE WHERE ulid=pedido_ulid
    alt status = pago (idempotência)
        A->>DB: ROLLBACK (nada a fazer)
        A-->>Proc: já processado (no-op)
    else status IN (aprovado, aguardando_aprovacao quando evento permite bypass)
        A->>A: constroi snapshot JSON (itens, preços vigentes, condição)
        A->>DB: UPDATE pedidos_extras SET status=pago, pago_at=now(), snapshot=JSON
        A->>DB: COMMIT
        A->>Ev: PedidoExtraPago::dispatch(pedido.id)

        Note over A,Emit: Somente se o pedido contém item<br/>do tipo "convite_extra"
        A->>Emit: execute(pedido, qtd_convites_extras)
        Emit->>DB: chunk 500 → INSERT convites (token, snapshot_regra, pedido_extra_id)
        Emit->>Ev: ConviteEmitido::dispatch (várias)
        Note over Ev: listeners disparam EnviarConviteEmailJob
    end
```

### 3.5 Estorno (EstornarPedidoExtraAction)

```mermaid
sequenceDiagram
    autonumber
    participant Proc as ProcessarWebhookPagamentoAction (webhook estorno)
    participant A as EstornarPedidoExtraAction
    participant DB as PostgreSQL
    participant Ev as Event Dispatcher

    Proc->>A: execute(pedido_ulid)
    A->>DB: BEGIN
    A->>DB: SELECT pedido FOR UPDATE
    alt status != pago
        A-->>Proc: throw InvariantViolationException (409)
    else ok
        A->>DB: UPDATE SET status=estornado, estornado_at=now()
        loop cada convite emitido pelo pedido
            A->>DB: UPDATE convites SET status=inutilizado WHERE pedido_extra_id=pedido.id AND status NOT IN (confirmado, cancelado)
        end
        A->>DB: UPDATE produtos_extras SET estoque_qtd = estoque_qtd + qtd_itens (se estoque limitado)
        A->>DB: COMMIT
        A->>Ev: PedidoExtraEstornado::dispatch
    end
```

## 4. Contrato entre actions (§3.7)

```mermaid
graph LR
    CW[ConfirmarWebhook Pagamento] --> CPE[ConfirmarPagamento ExtraAction]
    CPE --> ELC[EmitirLoteConvites Action]
    ELC --> EV[Event: ConviteEmitido]
    EV --> Q[EnviarConvite EmailJob Queue notifications]

    CW2[Webhook Estorno] --> EPE[EstornarPedido ExtraAction]
    EPE --> ICE[Invalidar Convites Emitidos]
    EPE --> RE[Restituir Estoque]
```

**Regra**: `ConfirmarPagamentoExtraAction` é o único consumidor autorizado a chamar `EmitirLoteConvitesAction` automaticamente. Fluxos manuais (admin emite cortesia) chamam `EmitirConviteAction` direto.

## 5. DTOs

```php
final readonly class PedidoExtraData {
    public function __construct(
        public string $eventoUlid,
        public string $formandoUlid,
        public array  $itens,              // [{produto_extra_ulid, qtd}]
        public string $idempotencyKey,
    ) {}
}

final readonly class PedidoExtraResultData {
    public function __construct(
        public string $pedidoUlid,
        public StatusPedidoExtra $status,
        public int $valorTotalCentavos,     // ADR-0014
        public ?string $pagoAt,
        public ?string $estornadoAt,
    ) {}
}
```

## 6. Snapshot de pedido pago (ADR-0009)

Estrutura capturada em `pedidos_extras.snapshot` no momento `status=pago`:

```json
{
    "itens": [
        {
            "produto_extra_ulid": "01J...",
            "nome_snapshot": "Convite Extra VIP",
            "qtd": 3,
            "preco_unitario_centavos_snapshot": 15000,
            "subtotal_centavos": 45000,
            "politica_reembolso": "7 dias"
        }
    ],
    "valor_total_centavos": 45000,
    "modalidade_pagamento": "pix",
    "gateway_reference": "itau-abc-123",
    "termo_hash": "sha256...",
    "snapshot_at": "2026-04-18T14:32:11Z"
}
```

Nunca alterado depois.

## 7. Segurança e autorização

- Policy `PedidoExtraPolicy`:
    - `criar(PortalUser, Evento)`: formando tem relação com evento + janela aberta.
    - `ver(PortalUser, PedidoExtra)`: dono do pedido.
    - `aprovar(AdminUser|ComissaoUser, PedidoExtra)`: permission `extras.approve` (admin) ou `comissao.extras.approve` (apenas se habilitado, §Apêndice B pergunta 2).
- Rate limit default `api: 120/min`.
- Idempotency obrigatório em `POST pedidos` (middleware `idempotent`).
- **Nunca** expor `gateway_reference` em response pública.

## 8. Observabilidade

- Logs: `extras.pedido.criado`, `extras.pedido.aprovado`, `extras.pedido.pago`, `extras.pedido.estornado`, `extras.emissao_derivada.ok`.
- Métricas Pulse: lag entre `pago_at` e `ConviteEmitido` — aceite F6: ≤ 30s.
- Correlation ID propagado de `pagamentos` → `pedidos_extras` → `convites`.

## 9. Rotas (resumo §2.2)

| Método | Rota                                                 | Auth             | Idempotent |
| ------ | ---------------------------------------------------- | ---------------- | ---------- |
| GET    | `/api/v1/eventos/{evento}/extras/catalogo`           | sanctum          | n/a        |
| POST   | `/api/v1/eventos/{evento}/extras/pedidos`            | sanctum + policy | sim        |
| GET    | `/api/v1/eventos/{evento}/extras/pedidos/{pedido}`   | sanctum + policy | n/a        |
| POST   | `/admin/extras/pedidos/{pedido}/aprovar` (Web Admin) | admin + policy   | via CSRF   |

Ações de aprovação/estorno em admin web seguem ADR-0008 (verbos em URL para transições).

## 10. Testes críticos (§10.7)

1. **Pedido extra pago 1× emite convites derivados em ≤ 30s** (aceite F6).
2. **Webhook reprocessado 10× não dobra efeito** — `ConfirmarPagamentoExtraAction` é idempotente.
3. **Estorno** invalida convites ainda não confirmados.
4. **Estoque limitado** — concorrência de 2 pedidos pelo último item → apenas 1 sucede.
5. **Snapshot congela** — mudar preço do `ProdutoExtra` depois não altera snapshot de pedidos pagos.
6. **Aprovação opcional** — quando evento não exige, pedido vai direto de `aprovado` pro pagamento.

## 11. Ligações

- ADR-0005, ADR-0008, ADR-0009, ADR-0013, ADR-0014
- PLANEJAMENTO_BACKEND_APIV1.md §2.2, §3.7, §4.2 (bloco F), §F6, §10.7, §Apêndice B
- SAD arc42 seções "Extras" e "Integração com Pagamentos"
- technical-design-payments.md (upstream: webhook de pagamento)
- technical-design-invitations.md (downstream: convites emitidos)
