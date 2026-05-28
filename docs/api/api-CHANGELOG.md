---
title: Changelog da API — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Changelog da API

Registro de todas as mudanças observáveis por clientes da API `/api/v1` (e futuras versões). Segue a política de deprecação RFC 8594 conforme `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2.3 e `api-conventions.md` §1.

> **Fonte autoritativa.** Este é o documento citado pela política de deprecação. Qualquer endpoint que retorne `Deprecation: true` e `Sunset: <date>` precisa ter entrada correspondente aqui.

## Formato de entrada

Cada entrada traz:

```
## [<versão>] — <YYYY-MM-DD>

### Added
- Endpoint/campo novo.

### Changed
- Mudança não-breaking.

### Deprecated
- Rota: `METHOD /caminho`
  - Motivo: <por que está sendo aposentada>
  - Sunset: <YYYY-MM-DD (HTTP-date)>
  - Successor: `METHOD /caminho` (versão <v2 | v1>)
  - Migração: <orientação concreta para cliente>

### Removed (após sunset)
- Rota: `METHOD /caminho` — retorna `410 Gone` com `error: EndpointSunset`.

### Security
- Correções sensíveis (sem detalhes explorativos).
```

### Critérios de versionamento

- **Major (v2, v3...)** — breaking change (campo removido/tipo alterado, rota realocada). Implica novo diretório `Http\Api\V2\*` e pelo menos 90 dias de coexistência.
- **Minor (1.x)** — adições não-breaking. Campos novos em Resources, novos endpoints, novos filtros.
- **Patch (1.x.y)** — correções sem mudança de contrato observável.

## Política de Deprecação (resumo operacional)

1. Endpoint deprecado passa a responder com:
    ```
    Deprecation: true
    Sunset: <HTTP-date>
    Link: <url-successor>; rel="successor-version"
    ```
2. **Notice mínimo:** 90 dias entre a primeira resposta `Deprecation: true` e a data `Sunset`.
3. No dia `Sunset`, o endpoint passa a retornar `410 Gone` com envelope padrão `error: 'EndpointSunset'`.
4. Entrada no changelog **obrigatória** antes do merge que introduz a deprecação.
5. Comunicação proativa aos integradores conhecidos via canal acordado (e-mail + release notes).

---

## [1.0.0] — 2026-04-18

### Added

- **API v1 publicada.** Primeira versão estável.
- **Auth** — `POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, `GET /api/v1/me`.
- **Contexto do formando** — `GET /api/v1/me/eventos`, `/me/adesoes`, `/me/convites`, `/me/cotas`, `/me/extrato`.
- **Eventos** — `GET /api/v1/eventos/{ulid}` (leitura pública autenticada).
- **Convites** — CRUD sobre `eventos/{ulid}/convites` + emissão em lote assíncrona via `POST .../lotes`.
- **RSVP via token mágico** — `GET /api/v1/convite/{token}` e `POST /api/v1/convite/{token}/rsvp`.
- **Seating** — `GET .../mesas/mapa`, `POST .../reservas`, `POST .../reservas/{ulid}/confirmar`, `DELETE .../reservas/{ulid}`, `POST .../reservas/{ulid}/trocar`.
- **Extras** — `GET .../extras/catalogo`, `POST .../extras/pedidos`, `GET .../extras/pedidos/{ulid}`.
- **Pagamentos** — `POST /api/v1/pagamentos/intents`, `GET /api/v1/pagamentos/{ulid}`.
- **Enquetes** — `GET`, `POST /votos` em `eventos/{ulid}/enquetes`.
- **Webhooks** — `POST /webhooks/pagamentos/{provider}` com validação HMAC.
- **Envelope de erro único** — RFC-neutral, 21 mapeamentos `Throwable → HTTP`.
- **Paginação cursor-based** — envelope canônico com `meta` e `links` em toda listagem.
- **Documentação OpenAPI** — `GET /docs/api` (UI Stoplight) e `GET /docs/api.json` (spec), gate restrito a admin via Spatie.
- **Limiters nomeados** — `api`, `login`, `convite`, `seating`, `voto`, `webhook`.
- **Idempotência dupla** — middleware `IdempotencyKeyGuard` + coluna `idempotency_key UNIQUE` nas tabelas de domínio.
- **Route model binding por ULID** — `{recurso:ulid}` em todas as rotas de recurso.
- **HATEOAS minimalista** — `links.self` em toda Resource; ações condicionais em Resources de state-machine.

### Changed

- Nada (primeira versão).

### Deprecated

- Nada (primeira versão).

### Removed

- Nada (primeira versão).

### Security

- Tokens de convite persistidos apenas como `sha256(token)`.
- Nenhum dado de cartão armazenado — apenas `gateway_reference`.
- HMAC obrigatório em webhooks (`hash_equals` para evitar timing attack).
- Rate limits por contexto já habilitados (ver §7 de `api-conventions.md`).

---

## Template para próximas entradas

```md
## [1.1.0] — YYYY-MM-DD

### Added

- `GET /api/v1/...` — <descrição>.

### Changed

- Campo `X` adicionado a `Resource Y` via `$this->when()`. Não-breaking.

### Deprecated

- `GET /api/v1/rota-antiga`
    - Motivo: redundância com `GET /api/v1/rota-nova`.
    - Sunset: 2026-XX-XX (HTTP-date: `Mon, 01 XXX 2026 00:00:00 GMT`).
    - Successor: `GET /api/v1/rota-nova` (v1).
    - Migração: trocar URL; response schema idêntico.

### Removed

- (vazio até sunset de alguma rota deprecada)

### Security

- <correção sem detalhes explorativos>
```

## Tabela consolidada de deprecações (visão rápida)

| Rota | Deprecado em | Sunset | Successor | Status  |
| ---- | ------------ | ------ | --------- | ------- |
| —    | —            | —      | —         | (vazio) |

> Quando houver deprecação, mova a entrada aqui **e** mantenha a entrada detalhada no bloco de versão.
