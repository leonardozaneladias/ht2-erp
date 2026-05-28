---
title: Contrato da API v1 — Portal ArtFinal
version: 1.0.0
date: 2026-04-18
status: draft
---

# Contrato da API v1

Especificação completa dos endpoints. Convenções transversais (envelope de erro, paginação, HATEOAS, headers, idempotência, rate limiting, filtros) vivem em `api-conventions.md`. Schemas formais em `openapi-skeleton.yaml`. Envelope de erro em `error-envelope.md`.

> Base URL: `https://api.portalartfinal.com.br/api/v1`.
> Routes PHP: `routes/api/v1.php` (ver `PLANEJAMENTO_BACKEND_APIV1.md` §2.2).
> Todo endpoint tem resposta de erro seguindo o envelope único. Omitido quando óbvio.

## Sumário

- [1. Autenticação](#1-autenticação)
    - [1.1 POST /auth/login](#11-post-authlogin)
    - [1.2 POST /auth/logout](#12-post-authlogout)
    - [1.3 GET /me](#13-get-me)
- [2. Contexto do formando autenticado](#2-contexto-do-formando-autenticado)
    - [2.1 GET /me/eventos](#21-get-meeventos)
    - [2.2 GET /me/adesoes](#22-get-meadesoes)
    - [2.3 GET /me/convites](#23-get-meconvites)
    - [2.4 GET /me/cotas](#24-get-mecotas)
    - [2.5 GET /me/extrato](#25-get-meextrato)
- [3. Eventos](#3-eventos)
    - [3.1 GET /eventos/{ulid}](#31-get-eventosulid)
- [4. Convites](#4-convites)
    - [4.1 GET /eventos/{ulid}/convites](#41-get-eventosulidconvites)
    - [4.2 POST /eventos/{ulid}/convites](#42-post-eventosulidconvites)
    - [4.3 PATCH /eventos/{ulid}/convites/{ulid}](#43-patch-eventosulidconvitesulid)
    - [4.4 DELETE /eventos/{ulid}/convites/{ulid}](#44-delete-eventosulidconvitesulid)
    - [4.5 POST /eventos/{ulid}/convites/lotes](#45-post-eventosulidconviteslotes)
    - [4.6 GET /eventos/{ulid}/convites/lotes/{ulid}](#46-get-eventosulidconviteslotesulid)
- [5. RSVP via token mágico](#5-rsvp-via-token-mágico)
    - [5.1 GET /convite/{token}](#51-get-convitetoken)
    - [5.2 POST /convite/{token}/rsvp](#52-post-convitetokenrsvp)
- [6. Seating](#6-seating)
    - [6.1 GET /eventos/{ulid}/mesas/mapa](#61-get-eventosulidmesasmapa)
    - [6.2 POST /eventos/{ulid}/mesas/reservas](#62-post-eventosulidmesasreservas)
    - [6.3 POST /eventos/{ulid}/mesas/reservas/{ulid}/confirmar](#63-post-eventosulidmesasreservasulidconfirmar)
    - [6.4 DELETE /eventos/{ulid}/mesas/reservas/{ulid}](#64-delete-eventosulidmesasreservasulid)
    - [6.5 POST /eventos/{ulid}/mesas/reservas/{ulid}/trocar](#65-post-eventosulidmesasreservasulidtrocar)
- [7. Extras](#7-extras)
    - [7.1 GET /eventos/{ulid}/extras/catalogo](#71-get-eventosulidextrascatalogo)
    - [7.2 POST /eventos/{ulid}/extras/pedidos](#72-post-eventosulidextraspedidos)
    - [7.3 GET /eventos/{ulid}/extras/pedidos/{ulid}](#73-get-eventosulidextraspedidosulid)
- [8. Pagamentos](#8-pagamentos)
    - [8.1 POST /pagamentos/intents](#81-post-pagamentosintents)
    - [8.2 GET /pagamentos/{ulid}](#82-get-pagamentosulid)
- [9. Enquetes](#9-enquetes)
    - [9.1 GET /eventos/{ulid}/enquetes](#91-get-eventosulidenquetes)
    - [9.2 GET /eventos/{ulid}/enquetes/{ulid}](#92-get-eventosulidenquetesulid)
    - [9.3 POST /eventos/{ulid}/enquetes/{ulid}/votos](#93-post-eventosulidenquetesulidvotos)
- [10. Webhooks](#10-webhooks)
    - [10.1 POST /webhooks/pagamentos/{provider}](#101-post-webhookspagamentosprovider)

---

## Convenções repetidas

Tudo abaixo presume:

- `Content-Type: application/json`.
- Erros seguem `error-envelope.md`.
- Paginação conforme `api-conventions.md` §3.
- Filtros/sorts conforme §9 de conventions.
- Rotas estão sob `auth:sanctum` salvo §5 (público via token mágico) e §10 (webhook).
- `{ulid}` nas URLs é resolvido via Route Model Binding pelo campo `ulid`.

---

## 1. Autenticação

### 1.1 POST /auth/login

**Nome da rota.** `api.v1.auth.login`
**Auth.** Nenhuma.
**Rate limit.** `login` (5/min por `email + ip`).
**Middlewares.** `throttle:login`.

#### Request body

```json
{
    "email": "mariana@usp.br",
    "password": "SenhaSegura#123",
    "mode": "spa",
    "remember": false,
    "device_name": null
}
```

**Regras de validação.**

- `email` — `required|string|email|max:150`.
- `password` — `required|string|min:8|max:128`.
- `mode` — `required|in:spa,token`.
- `remember` — `boolean`.
- `device_name` — `required_if:mode,token|string|max:60`.

#### Response 200 (`mode: spa`)

Cookie `laravel_session` setado (HttpOnly, Secure, SameSite=lax). Body:

```json
{
    "status": "ok",
    "user": {
        "id": "01J5K3B5GTYV8E2F1W0M8P2XQA",
        "email": "mariana@usp.br"
    }
}
```

#### Response 200 (`mode: token`)

```json
{
    "access_token": "1|aBc123...",
    "abilities": ["convites.view", "convites.emitir", "reservar"],
    "user": {
        "id": "01J5K3B5GTYV8E2F1W0M8P2XQA",
        "email": "mariana@usp.br"
    }
}
```

#### Erros

- 401 `Unauthenticated` — credenciais inválidas.
- 422 `ValidationError` — payload inválido.
- 429 `RateLimitExceeded` — limit estourado.

#### Action/Event

- Ação: inline no controller (sem Action de domínio — é pura autenticação).
- Event: `Illuminate\Auth\Events\Login`.

#### Exemplo cURL

```bash
curl -X POST https://api.portalartfinal.com.br/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Request-Id: 01J5..." \
  -d '{"email":"mariana@usp.br","password":"SenhaSegura#123","mode":"token","device_name":"Pixel-8"}'
```

---

### 1.2 POST /auth/logout

**Nome.** `api.v1.auth.logout`
**Auth.** `auth:sanctum` (cookie ou bearer).
**Rate limit.** `api`.

#### Request

Sem body.

#### Response 204

Sem corpo. Revoga o token Sanctum atual (SPA: invalida sessão; bearer: deleta `personal_access_tokens` row).

#### Exemplo cURL

```bash
curl -X POST https://api.portalartfinal.com.br/api/v1/auth/logout \
  -H "Authorization: Bearer 1|aBc..." \
  -H "X-Request-Id: 01J5..."
```

---

### 1.3 GET /me

**Nome.** `api.v1.me`
**Auth.** `auth:sanctum`.
**Rate limit.** `api`.

#### Response 200

```json
{
    "data": {
        "id": "01J5K3B5GTYV8E2F1W0M8P2XQA",
        "nome": "Mariana Souza",
        "email": "mariana@usp.br",
        "tipo": "formando",
        "roles": ["formando"],
        "abilities": ["convites.view", "reservar"],
        "formandos": [
            {
                "id": "01J...",
                "turma": { "id": "01J...", "codigo": "MED-2026" },
                "evento": { "id": "01J...", "slug": "baile-med-usp-2026" }
            }
        ],
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/me",
            "eventos": "https://api.portalartfinal.com.br/api/v1/me/eventos",
            "adesoes": "https://api.portalartfinal.com.br/api/v1/me/adesoes",
            "convites": "https://api.portalartfinal.com.br/api/v1/me/convites"
        }
    }
}
```

---

## 2. Contexto do formando autenticado

Prefixo: `GET /me/*`. Todas sob `auth:sanctum`, rate `api`.

### 2.1 GET /me/eventos

**Nome.** `api.v1.me.eventos`.
Lista eventos nos quais o usuário tem vínculo (formando ou comissão).

#### Query

- `filter[status]` — `publicado | encerrado | todos` (default: `publicado`).
- `page[size]` (default 50, max 100). `page[cursor]`.

#### Response 200

```json
{
    "data": [
        {
            "id": "01J...",
            "slug": "baile-med-usp-2026",
            "nome": "Baile de Formatura Medicina USP 2026",
            "data_evento": "2026-12-12T21:00:00-03:00",
            "status": "publicado",
            "janelas": {
                "abre_rsvp_at": "2026-10-01T00:00:00-03:00",
                "fecha_rsvp_at": "2026-11-30T23:59:59-03:00",
                "abre_mesas_at": "2026-11-01T00:00:00-03:00",
                "fecha_mesas_at": "2026-12-05T23:59:59-03:00"
            },
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J...",
                "mapa": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/mapa",
                "convites": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites"
            }
        }
    ],
    "meta": { "per_page": 50, "next_cursor": null, "prev_cursor": null },
    "links": { "self": "...", "next": null, "prev": null }
}
```

---

### 2.2 GET /me/adesoes

Lista adesões do formando autenticado.

#### Query

- `filter[status]` — `pendente_pagamento | ativa | cancelada | inadimplente | concluida | todos`.
- `filter[evento_id]` — ULID.
- `sort=-created_at,data_evento`.

#### Response 200

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
            "parcelas_resumo": {
                "total": 10,
                "pagas": 3,
                "pendentes": 7,
                "vencidas": 0
            },
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/me/adesoes/01J...",
                "extrato": "https://api.portalartfinal.com.br/api/v1/me/extrato?filter[adesao_id]=01J...",
                "cancelar": null
            }
        }
    ],
    "meta": { "...": "..." },
    "links": { "...": "..." }
}
```

---

### 2.3 GET /me/convites

Lista convites emitidos pelo formando autenticado.

#### Query

- `filter[status]` — `emitido,enviado,confirmado,recusado,cancelado`.
- `filter[tipo]` — `nominal | transferivel | cortesia | staff | extra`.
- `filter[search]` — busca parcial em `convidado_nome` e `codigo`.
- `sort=-created_at,codigo`.

#### Response 200

```json
{
    "data": [
        {
            "id": "01J...",
            "codigo": "ABCDE123",
            "status": "enviado",
            "tipo": "nominal",
            "convidado": {
                "nome": "Carlos Alberto",
                "email": "carlos@example.com",
                "telefone": "+55 11 99876-5432"
            },
            "entregue_at": "2026-10-15T10:20:00-03:00",
            "visualizado_at": null,
            "confirmado_at": null,
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites/01J...",
                "reemitir": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites/01J.../reemitir",
                "transferir": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites/01J.../transferir",
                "cancelar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites/01J..."
            }
        }
    ]
}
```

---

### 2.4 GET /me/cotas

Saldo de cotas do formando autenticado em cada evento vinculado.

#### Response 200

```json
{
    "data": [
        {
            "evento": { "id": "01J...", "slug": "baile-med-usp-2026" },
            "cotas": [
                { "tipo": "base", "limite": 4, "utilizados": 2, "saldo": 2 },
                { "tipo": "transferivel", "limite": 2, "utilizados": 0, "saldo": 2 },
                { "tipo": "extra", "limite": null, "utilizados": 1, "saldo": null }
            ],
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/me/cotas",
                "emitir": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites"
            }
        }
    ]
}
```

---

### 2.5 GET /me/extrato

Extrato financeiro consolidado (adesões, parcelas, pedidos extras).

#### Query

- `filter[adesao_id]` — ULID.
- `filter[periodo_de]=YYYY-MM-DD`, `filter[periodo_ate]=YYYY-MM-DD`.
- `sort=-data_movimento`.

#### Response 200

```json
{
    "data": [
        {
            "id": "01J...",
            "tipo": "parcela_paga",
            "data_movimento": "2026-03-05T00:00:00-03:00",
            "valor_centavos": 150000,
            "descricao": "Parcela 3/10 — Pacote Premium",
            "referencia": { "tipo": "parcela", "id": "01J..." },
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/me/extrato/01J...",
                "comprovante": "https://api.portalartfinal.com.br/api/v1/pagamentos/01J..."
            }
        }
    ]
}
```

---

## 3. Eventos

### 3.1 GET /eventos/{ulid}

**Nome.** `api.v1.eventos.show`.
Mostra detalhes do evento (autenticado; respeita vínculo).

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "slug": "baile-med-usp-2026",
        "nome": "Baile de Formatura Medicina USP 2026",
        "data_evento": "2026-12-12T21:00:00-03:00",
        "status": "publicado",
        "janelas": {
            "abre_rsvp_at": "2026-10-01T00:00:00-03:00",
            "fecha_rsvp_at": "2026-11-30T23:59:59-03:00",
            "abre_mesas_at": "2026-11-01T00:00:00-03:00",
            "fecha_mesas_at": "2026-12-05T23:59:59-03:00"
        },
        "config": {
            "hold_ttl_seconds": 300,
            "permite_troca_assento": true
        },
        "turmas": [{ "id": "01J...", "codigo": "MED-2026" }],
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J...",
            "mapa": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/mapa",
            "convites": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites",
            "enquetes": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../enquetes",
            "extras": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../extras/catalogo"
        }
    }
}
```

#### Erros

- 401 se não autenticado.
- 403 se autenticado mas sem vínculo.
- 404 se evento não existe.

---

## 4. Convites

Prefixo `eventos/{evento:ulid}/convites`.

### 4.1 GET /eventos/{ulid}/convites

**Nome.** `api.v1.convites.index`.
Lista convites do formando no evento. (Admin vê todos, comissão vê do evento, formando vê só os seus.)

#### Query

- `filter[status]`, `filter[tipo]`, `filter[search]`.
- `sort=-created_at,codigo`.
- `page[size]`, `page[cursor]`.

#### Response 200

Mesmo shape de §2.3 (`ConviteResource`).

---

### 4.2 POST /eventos/{ulid}/convites

**Nome.** `api.v1.convites.store`.
**Rate limit.** `throttle.actor:convite` (10/min/user).
**Policy.** `ConvitePolicy::emitir(user, evento)`.

#### Request

```json
{
    "tipo": "nominal",
    "convidado": {
        "nome": "Carlos Alberto",
        "email": "carlos@example.com",
        "telefone": "+55 11 99876-5432"
    },
    "origem_cota": "base"
}
```

**Validação.**

- `tipo` — `required|in:nominal,transferivel,cortesia`.
- `convidado.nome` — `required|string|max:150`.
- `convidado.email` — `required_without:convidado.telefone|email|max:150`.
- `convidado.telefone` — `string|max:30`.
- `origem_cota` — `required|in:base,transferivel,cortesia,staff`.

#### Response 201 + `Location`

```json
{
    "data": {
        "id": "01J...",
        "codigo": "ABCDE123",
        "status": "emitido",
        "tipo": "nominal",
        "convidado": { "...": "..." },
        "links": { "self": "...", "reemitir": null, "cancelar": "..." }
    }
}
```

#### Action/Event

- Action: `App\Actions\Convites\EmitirConviteAction`.
- Event: `ConviteEmitido`.
- Listener: `EnviarEmailConviteAoEmitir` → `EnviarConviteEmailJob`.

#### Erros

- 409 `CotaEsgotada`.
- 422 `ValidationError`.

---

### 4.3 PATCH /eventos/{ulid}/convites/{ulid}

**Nome.** `api.v1.convites.update`.
Atualiza dados do convidado **antes** de `enviado`. Após `enviado`, exige `POST .../transferir`.

#### Request

```json
{
    "convidado": { "nome": "Carlos A. Silva", "email": "carlos.silva@example.com" }
}
```

#### Response 200

`ConviteResource`.

#### Erros

- 409 `InvariantViolation` — convite em status que não permite edição.

---

### 4.4 DELETE /eventos/{ulid}/convites/{ulid}

**Nome.** `api.v1.convites.destroy`.
Cancela o convite (marca `status = cancelado`). Nunca DELETE físico.

#### Response 204

Nenhum body.

#### Action/Event

- Action: `CancelarConviteAction`.
- Event: `ConviteCancelado`.
- Efeito colateral: se tinha `ReservaAssento` ativa, dispara `LiberarAssentoAction`.

---

### 4.5 POST /eventos/{ulid}/convites/lotes

**Nome.** `api.v1.convites.lotes.store`.
**Middlewares.** `idempotent` (X-Idempotency-Key obrigatório).
Emissão em lote assíncrona.

#### Request

```json
{
    "convites": [
        { "tipo": "nominal", "convidado": { "nome": "A", "email": "a@x.com" }, "origem_cota": "base" },
        { "tipo": "nominal", "convidado": { "nome": "B", "email": "b@x.com" }, "origem_cota": "base" }
    ]
}
```

**Validação.**

- `convites` — `required|array|min:1|max:500`.
- Itens herdam regras de §4.2.

#### Response 202

```json
{
    "data": {
        "id": "01J...",
        "status": "processando",
        "qtd_total": 500,
        "qtd_processados": 0,
        "status_url": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../convites/lotes/01J..."
    }
}
```

#### Action/Event

- Action: enfileira `EmitirLoteConvitesJob`.
- Event: `LoteConvitesIniciado`.

---

### 4.6 GET /eventos/{ulid}/convites/lotes/{ulid}

**Nome.** `api.v1.convites.lotes.show`.
Polling do status do lote.

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "status": "concluido",
        "qtd_total": 500,
        "qtd_processados": 500,
        "qtd_falhados": 0,
        "iniciado_at": "2026-10-15T10:00:00-03:00",
        "concluido_at": "2026-10-15T10:00:42-03:00"
    }
}
```

---

## 5. RSVP via token mágico

Público (sem `auth:sanctum`). Resolve token via middleware `convite.token`. Rate limit `convite` (10/min/IP).

### 5.1 GET /convite/{token}

**Nome.** `api.v1.convite.show`.
`token` é o valor bruto (64 hex chars) enviado ao convidado por e-mail. Backend calcula `sha256(token)` e busca `token_hash`.

#### Response 200

```json
{
    "data": {
        "convite": {
            "id": "01J...",
            "codigo": "ABCDE123",
            "tipo": "nominal",
            "status": "enviado",
            "convidado": { "nome": "Carlos Alberto" }
        },
        "evento": {
            "id": "01J...",
            "nome": "Baile de Formatura Medicina USP 2026",
            "data_evento": "2026-12-12T21:00:00-03:00",
            "local": { "nome": "Espaço Royal", "endereco": "Av. ... 1234" }
        },
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/convite/<token>",
            "rsvp": "https://api.portalartfinal.com.br/api/v1/convite/<token>/rsvp"
        }
    }
}
```

#### Erros

- 404 — token inválido (não expõe se "não existe" vs "revogado").
- 429 — rate limit estourado.

---

### 5.2 POST /convite/{token}/rsvp

**Nome.** `api.v1.convite.rsvp.store`.

#### Request

```json
{
    "resposta": "confirmo",
    "nome_confirmado": "Carlos Alberto Silva",
    "observacao": "Intolerância a lactose"
}
```

**Validação.**

- `resposta` — `required|in:confirmo,recuso,tentativa`.
- `nome_confirmado` — `required|string|max:150`.
- `observacao` — `nullable|string|max:500`.

#### Response 200

```json
{
    "data": {
        "convite": {
            "id": "01J...",
            "status": "confirmado",
            "confirmado_at": "2026-10-20T18:30:00-03:00"
        }
    }
}
```

#### Action/Event

- Action: `RegistrarRsvpAction`.
- Event: `RsvpRegistrado`.

#### Erros

- 409 `InvariantViolation` — convite cancelado/inutilizado.
- 422 — resposta inválida.

---

## 6. Seating

Prefixo `eventos/{evento:ulid}/mesas`. Todas sob `auth:sanctum`.

### 6.1 GET /eventos/{ulid}/mesas/mapa

**Nome.** `api.v1.seating.mapa`.
Retorna snapshot completo OU delta via `?since=<iso8601>`.

#### Query

- `since` — ISO 8601. Quando presente, retorna apenas `reservas` com `updated_at > since`.

#### Response 200 (snapshot completo)

```json
{
    "data": {
        "mapa": { "id": "01J...", "nome": "Salão Principal" },
        "setores": [
            {
                "id": "01J...",
                "nome": "Setor A",
                "mesas": [
                    {
                        "id": "01J...",
                        "numero": "12",
                        "capacidade": 8,
                        "assentos": [
                            {
                                "id": "01J...",
                                "numero": 1,
                                "status_runtime": "livre"
                            },
                            {
                                "id": "01J...",
                                "numero": 2,
                                "status_runtime": "confirmada",
                                "reserva_id": "01J..."
                            }
                        ]
                    }
                ]
            }
        ],
        "atualizado_em": "2026-11-10T14:30:22-03:00",
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/mapa",
            "reservar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas"
        }
    }
}
```

#### Response 200 (delta via `?since`)

```json
{
    "data": {
        "deltas": [
            { "assento_id": "01J...", "status_runtime": "hold", "hold_expires_at": "2026-11-10T14:35:22-03:00" },
            { "assento_id": "01J...", "status_runtime": "confirmada", "reserva_id": "01J..." }
        ],
        "atualizado_em": "2026-11-10T14:30:22-03:00"
    }
}
```

---

### 6.2 POST /eventos/{ulid}/mesas/reservas

**Nome.** `api.v1.seating.reservas.store`.
**Middlewares.** `idempotent`, `throttle.actor:seating`.
**Policy.** `ReservaAssentoPolicy::reservar(user, evento)`.

#### Request

```json
{
    "assento_ulid": "01J...",
    "convite_ulid": "01J...",
    "origem": "formando",
    "observacao": "Próximo à família"
}
```

**Headers obrigatórios.** `X-Idempotency-Key: <ulid | uuid | hash ≤ 80 chars>`.

**Validação.**

- `assento_ulid` — `required|string|size:26`.
- `convite_ulid` — `nullable|string|size:26`.
- `origem` — `required|in:formando,comissao,admin,operacao`.
- `observacao` — `nullable|string|max:500`.

#### Response 201 + `Location`

```json
{
    "data": {
        "id": "01J...",
        "status": "hold",
        "mesa": { "id": "01J...", "numero": "12" },
        "assento": { "id": "01J...", "numero": 2 },
        "hold_expires_at": "2026-11-10T14:35:22-03:00",
        "confirmado_at": null,
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J...",
            "confirmar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J.../confirmar",
            "cancelar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J...",
            "trocar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J.../trocar"
        }
    }
}
```

#### Action/Event

- Action: `ReservarAssentoAction`.
- Event: `AssentoReservado`.
- Listener: `InvalidarCacheMapaAoReservar` + `PublicarAtualizacaoMapaJob`.

#### Erros

- 409 `AssentoIndisponivel` — assento já tem reserva ativa.
- 422 `ValidationError`.
- 429 `RateLimitExceeded`.

#### Exemplo cURL

```bash
curl -X POST https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas \
  -H "Authorization: Bearer 1|..." \
  -H "Content-Type: application/json" \
  -H "X-Idempotency-Key: 01J5K2N7QMHV1FJZ8H0PR3RV9C" \
  -H "X-Request-Id: 01J..." \
  -d '{"assento_ulid":"01J...","origem":"formando"}'
```

---

### 6.3 POST /eventos/{ulid}/mesas/reservas/{ulid}/confirmar

**Nome.** `api.v1.seating.reservas.confirmar`.
Converte `hold → confirmada`. Fatal se hold expirou.

#### Request

Sem body.

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "status": "confirmada",
        "hold_expires_at": null,
        "confirmado_at": "2026-11-10T14:33:48-03:00",
        "links": {
            "self": "...",
            "confirmar": null,
            "cancelar": "...",
            "trocar": "..."
        }
    }
}
```

#### Action/Event

- Action: `ConfirmarAssentoAction`.
- Event: `AssentoConfirmado`.

#### Erros

- 410 `HoldExpirado`.
- 409 `InvariantViolation` — status atual não é `hold`.

---

### 6.4 DELETE /eventos/{ulid}/mesas/reservas/{ulid}

**Nome.** `api.v1.seating.reservas.destroy`.
Cancela. Registra motivo se fornecido em query (`?motivo=...`).

#### Response 204

Nenhum body.

#### Action/Event

- Action: `LiberarAssentoAction`.
- Event: `AssentoLiberado`.

---

### 6.5 POST /eventos/{ulid}/mesas/reservas/{ulid}/trocar

**Nome.** `api.v1.seating.reservas.trocar`.
**Middlewares.** `idempotent`.
Troca atômica: libera reserva atual e cria nova em outro assento.

#### Request

```json
{
    "assento_destino_ulid": "01J...",
    "origem": "formando"
}
```

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "status": "hold",
        "mesa": { "id": "01J...", "numero": "14" },
        "assento": { "id": "01J...", "numero": 5 },
        "hold_expires_at": "2026-11-10T14:40:22-03:00",
        "links": { "...": "..." }
    }
}
```

#### Action/Event

- Action: `TrocarAssentoAction` (compõe `LiberarAssentoAction` + `ReservarAssentoAction`).
- Events: `AssentoLiberado` + `AssentoReservado`.

#### Erros

- 409 `AssentoIndisponivel` — destino ocupado.
- 410 `HoldExpirado` — reserva atual venceu.

---

## 7. Extras

### 7.1 GET /eventos/{ulid}/extras/catalogo

**Nome.** `api.v1.extras.catalogo`.

#### Query

- `filter[categoria]`, `filter[disponivel]=true`.

#### Response 200

```json
{
    "data": [
        {
            "id": "01J...",
            "nome": "Jantar extra",
            "categoria": "alimentacao",
            "preco_centavos": 18000,
            "estoque": { "tipo": "finito", "qtd_restante": 42 },
            "descricao": "Prato principal do chef",
            "imagens": [{ "url": "https://.../signed?exp=...", "alt": "Prato" }],
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../extras/catalogo/01J...",
                "pedido": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../extras/pedidos"
            }
        }
    ]
}
```

---

### 7.2 POST /eventos/{ulid}/extras/pedidos

**Nome.** `api.v1.extras.pedidos.store`.
**Middlewares.** `idempotent`.

#### Request

```json
{
    "itens": [
        { "produto_extra_ulid": "01J...", "quantidade": 2 },
        { "produto_extra_ulid": "01J...", "quantidade": 1 }
    ],
    "metodo_pagamento": "pix"
}
```

**Validação.**

- `itens` — `required|array|min:1|max:20`.
- `itens.*.produto_extra_ulid` — `required|string|size:26`.
- `itens.*.quantidade` — `required|integer|min:1|max:10`.
- `metodo_pagamento` — `required|in:boleto,pix,cartao`.

#### Response 201 + `Location`

```json
{
    "data": {
        "id": "01J...",
        "status": "aguardando_pagamento",
        "valor_total_centavos": 36000,
        "itens": [
            {
                "produto": { "id": "01J...", "nome": "Jantar extra" },
                "quantidade": 2,
                "preco_unitario_centavos": 18000
            }
        ],
        "pagamento": {
            "id": "01J...",
            "metodo": "pix",
            "status": "pendente",
            "qrcode": "00020126..."
        },
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../extras/pedidos/01J...",
            "pagar": "https://api.portalartfinal.com.br/api/v1/pagamentos/01J...",
            "cancelar": "..."
        }
    }
}
```

#### Action/Event

- Action: `CriarPedidoExtraAction` + `IniciarPagamentoAction` (encadeadas).
- Events: `PedidoExtraCriado`, `PagamentoIniciado`.

#### Erros

- 409 `EstoqueInsuficiente` (variante de `InvariantViolation`).
- 422 `ValidationError`.

---

### 7.3 GET /eventos/{ulid}/extras/pedidos/{ulid}

**Nome.** `api.v1.extras.pedidos.show`.

#### Response 200

Mesmo shape de §7.2, atualizado com status vigente.

---

## 8. Pagamentos

### 8.1 POST /pagamentos/intents

**Nome.** `api.v1.pagamentos.intents.store`.
**Middlewares.** `idempotent`.
Cria intent de cobrança (usado por fluxos de parcela de adesão ou re-tentativa de pedido extra).

#### Request

```json
{
    "origem_tipo": "parcela",
    "origem_ulid": "01J...",
    "metodo": "boleto"
}
```

**Validação.**

- `origem_tipo` — `required|in:parcela,pedido_extra`.
- `origem_ulid` — `required|string|size:26`.
- `metodo` — `required|in:boleto,pix,cartao`.

#### Response 201

```json
{
    "data": {
        "id": "01J...",
        "status": "pendente",
        "metodo": "boleto",
        "valor_centavos": 150000,
        "boleto": {
            "linha_digitavel": "23793.38128 60024.012345 67890.123456 7 00000150000000",
            "pdf_url": "https://.../signed?exp=..."
        },
        "pix": null,
        "cartao": null,
        "links": {
            "self": "https://api.portalartfinal.com.br/api/v1/pagamentos/01J..."
        }
    }
}
```

#### Action/Event

- Action: `IniciarPagamentoAction`.
- Event: `PagamentoIniciado`.

---

### 8.2 GET /pagamentos/{ulid}

**Nome.** `api.v1.pagamentos.show`.
Consulta estado atual do pagamento.

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "status": "pago",
        "valor_centavos": 150000,
        "pago_em": "2026-03-05T10:32:00-03:00",
        "origem": { "tipo": "parcela", "id": "01J...", "descricao": "Parcela 3/10" },
        "comprovante_url": "https://.../signed?exp=...",
        "links": {
            "self": "..."
        }
    }
}
```

---

## 9. Enquetes

Prefixo `eventos/{evento:ulid}/enquetes`.

### 9.1 GET /eventos/{ulid}/enquetes

**Nome.** `api.v1.enquetes.index`.

#### Query

- `filter[status]` — `rascunho | aberta | encerrada | arquivada | todas`.

#### Response 200

```json
{
    "data": [
        {
            "id": "01J...",
            "titulo": "Tema da festa",
            "tipo": "unica",
            "status": "aberta",
            "janela": {
                "abre_at": "2026-10-01T00:00:00-03:00",
                "fecha_at": "2026-10-31T23:59:59-03:00"
            },
            "permite_edicao": true,
            "ja_votei": false,
            "links": {
                "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../enquetes/01J...",
                "votar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../enquetes/01J.../votos"
            }
        }
    ]
}
```

---

### 9.2 GET /eventos/{ulid}/enquetes/{ulid}

**Nome.** `api.v1.enquetes.show`.

#### Response 200

```json
{
    "data": {
        "id": "01J...",
        "titulo": "Tema da festa",
        "descricao": "Escolha o tema principal.",
        "tipo": "unica",
        "status": "aberta",
        "permite_edicao": true,
        "resultado_publico": false,
        "opcoes": [
            { "id": "01J...", "rotulo": "Anos 80", "ordem": 1 },
            { "id": "01J...", "rotulo": "Carnaval", "ordem": 2 },
            { "id": "01J...", "rotulo": "Elegante", "ordem": 3 }
        ],
        "meu_voto": null,
        "resultado": null,
        "links": { "self": "...", "votar": "..." }
    }
}
```

Quando `resultado_publico = true` e `status = encerrada`, adiciona `resultado` com `{ opcao_id, contagem, percentual }`.

---

### 9.3 POST /eventos/{ulid}/enquetes/{ulid}/votos

**Nome.** `api.v1.enquetes.votos.store`.
**Rate limit.** `throttle.actor:voto` (3/min/user).
**Policy.** `EnquetePolicy::votar(user, enquete)` (verifica elegibilidade).

#### Request

```json
{
    "opcao_ulid": "01J..."
}
```

Para `tipo=multipla`:

```json
{
    "opcoes_ulids": ["01J...", "01J..."]
}
```

**Validação.**

- `opcao_ulid` — `required_if:enquete.tipo,unica|string|size:26`.
- `opcoes_ulids` — `required_if:enquete.tipo,multipla|array|min:1`.

#### Response 201

```json
{
    "data": {
        "id": "01J...",
        "registrado_at": "2026-10-15T14:00:00-03:00",
        "opcao": { "id": "01J...", "rotulo": "Anos 80" },
        "links": { "self": "..." }
    }
}
```

#### Action/Event

- Action: `RegistrarVotoAction`.
- Event: `VotoRegistrado`.

#### Erros

- 409 `InvariantViolation` — enquete encerrada ou usuário inelegível.
- 409 `DomainError` — voto duplicado (quando `permite_edicao=false`).
- 429 `RateLimitExceeded`.

---

## 10. Webhooks

### 10.1 POST /webhooks/pagamentos/{provider}

**Nome.** `webhook.pagamentos.receive`.
**Path regex.** `{provider}` ∈ `itau | mock`.
**Auth.** Nenhuma (validação por HMAC).
**Rate limit.** `webhook` (600/min/IP).
**Middlewares.** `AttachRequestId`, sem CSRF.

#### Request

Header obrigatório: `X-Signature: <hex sha256 hmac>`.
Body exemplo (Itaú):

```json
{
    "tipo": "pagamento.confirmado",
    "evento": { "id": "ITAU-EVT-20260417-0007" },
    "cobranca": { "id": "ITAU-20260417-0001", "status": "pago" },
    "valor_centavos": 150099,
    "pago_em": "2026-04-17T14:32:11-03:00",
    "metadata": { "adesao_ulid": "01J..." }
}
```

**Validação.**

- `evento.id` — obrigatório e não vazio.
- HMAC `X-Signature == hash_hmac('sha256', $rawBody, $secret)`.

#### Response 202 (evento novo, aceito)

```json
{ "status": "accepted" }
```

#### Response 200 (já processado)

```json
{ "status": "already_processed" }
```

#### Response 401 (assinatura inválida)

Envelope padrão:

```json
{
    "error": "invalid signature",
    "message": "assinatura HMAC divergente",
    "details": null,
    "request_id": "01J...",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

#### Action/Event

- Action controller: persiste em `webhook_eventos` via `firstOrCreate`.
- Action domain (async): `ProcessarWebhookPagamentoAction` → delega a `ConfirmarAdesaoAction` / `ConfirmarPagamentoExtraAction` / `EstornarPedidoExtraAction`.
- Job: `ProcessarWebhookPagamentoJob`.
- Event: `PagamentoConfirmado` ou `PagamentoFalhou` conforme `tipo`.

#### Exemplo cURL

```bash
BODY='{"tipo":"pagamento.confirmado","evento":{"id":"ITAU-EVT-0007"}}'
SIG=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" | awk '{print $2}')

curl -X POST https://api.portalartfinal.com.br/webhooks/pagamentos/itau \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIG" \
  -d "$BODY"
```

---

## Anexo A — Matriz Endpoint × Action × Event

| Endpoint                                               | Action                                              | Event(s)                                                         |
| ------------------------------------------------------ | --------------------------------------------------- | ---------------------------------------------------------------- |
| POST `/auth/login`                                     | — (inline)                                          | `Illuminate\Auth\Events\Login`                                   |
| POST `/auth/logout`                                    | —                                                   | `Logout`                                                         |
| GET `/me`                                              | —                                                   | —                                                                |
| GET `/me/eventos`                                      | —                                                   | —                                                                |
| GET `/me/adesoes`                                      | —                                                   | —                                                                |
| GET `/me/convites`                                     | —                                                   | —                                                                |
| GET `/me/cotas`                                        | `CotaCalculator` (service)                          | —                                                                |
| GET `/me/extrato`                                      | —                                                   | —                                                                |
| GET `/eventos/{ulid}`                                  | —                                                   | —                                                                |
| GET `/eventos/{ulid}/convites`                         | —                                                   | —                                                                |
| POST `/eventos/{ulid}/convites`                        | `EmitirConviteAction`                               | `ConviteEmitido`                                                 |
| PATCH `/eventos/{ulid}/convites/{ulid}`                | `AtualizarConviteAction`                            | `ConviteAtualizado`                                              |
| DELETE `/eventos/{ulid}/convites/{ulid}`               | `CancelarConviteAction`                             | `ConviteCancelado`                                               |
| POST `/eventos/{ulid}/convites/lotes`                  | `EmitirLoteConvitesAction` (enfileira job)          | `LoteConvitesIniciado`                                           |
| GET `/eventos/{ulid}/convites/lotes/{ulid}`            | —                                                   | —                                                                |
| GET `/convite/{token}`                                 | —                                                   | —                                                                |
| POST `/convite/{token}/rsvp`                           | `RegistrarRsvpAction`                               | `RsvpRegistrado`                                                 |
| GET `/eventos/{ulid}/mesas/mapa`                       | `DisponibilidadeService`                            | —                                                                |
| POST `/eventos/{ulid}/mesas/reservas`                  | `ReservarAssentoAction`                             | `AssentoReservado`                                               |
| POST `/eventos/{ulid}/mesas/reservas/{ulid}/confirmar` | `ConfirmarAssentoAction`                            | `AssentoConfirmado`                                              |
| DELETE `/eventos/{ulid}/mesas/reservas/{ulid}`         | `LiberarAssentoAction`                              | `AssentoLiberado`                                                |
| POST `/eventos/{ulid}/mesas/reservas/{ulid}/trocar`    | `TrocarAssentoAction`                               | `AssentoLiberado` + `AssentoReservado`                           |
| GET `/eventos/{ulid}/extras/catalogo`                  | —                                                   | —                                                                |
| POST `/eventos/{ulid}/extras/pedidos`                  | `CriarPedidoExtraAction` + `IniciarPagamentoAction` | `PedidoExtraCriado`, `PagamentoIniciado`                         |
| GET `/eventos/{ulid}/extras/pedidos/{ulid}`            | —                                                   | —                                                                |
| POST `/pagamentos/intents`                             | `IniciarPagamentoAction`                            | `PagamentoIniciado`                                              |
| GET `/pagamentos/{ulid}`                               | —                                                   | —                                                                |
| GET `/eventos/{ulid}/enquetes`                         | —                                                   | —                                                                |
| GET `/eventos/{ulid}/enquetes/{ulid}`                  | —                                                   | —                                                                |
| POST `/eventos/{ulid}/enquetes/{ulid}/votos`           | `RegistrarVotoAction`                               | `VotoRegistrado`                                                 |
| POST `/webhooks/pagamentos/{provider}`                 | `ProcessarWebhookPagamentoAction` (async)           | `PagamentoConfirmado` / `PagamentoFalhou` / `PagamentoEstornado` |

## Anexo B — Matriz Endpoint × Policy × Permission

| Endpoint                                    | Policy method                     | Permission Spatie (opcional) |
| ------------------------------------------- | --------------------------------- | ---------------------------- |
| GET `/me/eventos`                           | —                                 | —                            |
| GET `/eventos/{ulid}`                       | `EventoPolicy::view`              | —                            |
| POST `/eventos/{ulid}/convites`             | `ConvitePolicy::emitir`           | `convites.emitir`            |
| DELETE `/.../convites/{ulid}`               | `ConvitePolicy::cancelar`         | —                            |
| POST `/.../mesas/reservas`                  | `ReservaAssentoPolicy::reservar`  | `seating.reservar`           |
| POST `/.../mesas/reservas/{ulid}/confirmar` | `ReservaAssentoPolicy::confirmar` | —                            |
| DELETE `/.../mesas/reservas/{ulid}`         | `ReservaAssentoPolicy::delete`    | —                            |
| POST `/.../extras/pedidos`                  | `PedidoExtraPolicy::criar`        | `extras.comprar`             |
| POST `/pagamentos/intents`                  | `PagamentoPolicy::iniciar`        | —                            |
| POST `/.../enquetes/{ulid}/votos`           | `EnquetePolicy::votar`            | —                            |

## Anexo C — Matriz Endpoint × Rate Limiter

| Endpoint                                 | Limiter                  | Limite               |
| ---------------------------------------- | ------------------------ | -------------------- |
| POST `/auth/login`                       | `login`                  | 5/min por `email+ip` |
| GET `/convite/{token}`                   | `convite`                | 10/min por IP        |
| POST `/convite/{token}/rsvp`             | `convite`                | 10/min por IP        |
| POST `/.../convites` (single)            | `throttle.actor:convite` | 10/min por user      |
| POST `/.../mesas/reservas`               | `throttle.actor:seating` | 5/min por user       |
| POST `/.../mesas/reservas/{ulid}/trocar` | `throttle.actor:seating` | 5/min por user       |
| POST `/.../enquetes/{ulid}/votos`        | `throttle.actor:voto`    | 3/min por user       |
| POST `/webhooks/pagamentos/{provider}`   | `webhook`                | 600/min por IP       |
| Todos demais                             | `api`                    | 120/min por user/ip  |

## Anexo D — Matriz Endpoint × Idempotency Key

| Endpoint                                               | X-Idempotency-Key obrigatório? |
| ------------------------------------------------------ | ------------------------------ |
| POST `/eventos/{ulid}/convites/lotes`                  | **Sim**                        |
| POST `/eventos/{ulid}/mesas/reservas`                  | **Sim**                        |
| POST `/.../mesas/reservas/{ulid}/trocar`               | **Sim**                        |
| POST `/eventos/{ulid}/extras/pedidos`                  | **Sim**                        |
| POST `/pagamentos/intents`                             | **Sim**                        |
| POST `/eventos/{ulid}/convites` (single)               | Recomendado                    |
| POST `/.../enquetes/{ulid}/votos` (com permite_edicao) | Recomendado                    |
| Demais POST                                            | Opcional                       |
