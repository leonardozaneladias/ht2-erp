---
title: Convenções da API v1 — Portal ArtFinal
version: 1.0.0
date: 2026-04-18
status: draft
---

# Convenções da API v1

Compêndio das regras transversais da API. Todos os endpoints em `api-contract.md` e o schema OpenAPI em `openapi-skeleton.yaml` assumem estas convenções como baseline — não repetem.

> Fonte: `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2.3 a §2.15.

## 1. Versionamento (§2.3)

### 1.1 Prefixo obrigatório

Toda a API vive em `/api/v1`. Clientes **nunca** consomem rotas sem versão — mesmo a v1 é explícita desde o dia 1.

### 1.2 Regra de mudança

- **Breaking change** → criar `/api/v2`. Cria-se `Http\Api\V2\*` reaproveitando as mesmas Actions/DTOs. A camada de domínio (Actions) é fonte única de verdade.
- **Não-breaking** (novos campos, novos endpoints, novos filtros) → permanece em v1. Campos novos em Resources via `$this->when()` para não quebrar cliente que ignora.

### 1.3 Deprecação (RFC 8594)

Um endpoint deprecado retorna **em todas as respostas**:

```
Deprecation: true
Sunset: Wed, 31 Dec 2026 23:59:59 GMT
Link: <https://api.portalartfinal.com.br/api/v2/recurso>; rel="successor-version"
```

Regras:

- **Notice mínimo:** 90 dias entre o primeiro `Deprecation: true` e a data `Sunset`.
- **Fonte autoritativa:** `docs/api/api-CHANGELOG.md` lista cada endpoint deprecado, motivo, data de sunset e successor.
- **Após sunset:** rota retorna **410 Gone** com envelope §2.11 e `error: 'EndpointSunset'`.

## 2. HATEOAS minimalista (§2.5)

### 2.1 Regras gerais

1. **Toda Resource** retorna chave `links` com no mínimo `{ "self": "<url>" }`.
2. **Resources de state-machine** (`Reserva`, `PedidoExtra`, `Adesao`, `Convite`, `Pagamento`, `Enquete`) retornam ações **condicionais** baseadas no estado atual. Ex.: `confirmar`, `cancelar`, `pagar`, `transferir`, `estornar`. Ação indisponível → `null` (não omitir a chave).
3. **Resources de leitura pura** (catálogos, lookups) só `self`.
4. **Coleções** (`*Resource::collection(...)`) usam `meta.links` de paginação; não duplicam `links` por item além do `self`.

### 2.2 Exemplo — ReservaAssentoResource

```json
{
    "id": "01J5K2N7QMHV1FJZ8H0PR3RV9C",
    "status": "hold",
    "hold_expires_at": "2026-04-17T14:37:11Z",
    "confirmado_at": null,
    "links": {
        "self": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J5K2N7QMHV1FJZ8H0PR3RV9C",
        "confirmar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J5K2N7QMHV1FJZ8H0PR3RV9C/confirmar",
        "cancelar": "https://api.portalartfinal.com.br/api/v1/eventos/01J.../mesas/reservas/01J5K2N7QMHV1FJZ8H0PR3RV9C",
        "trocar": null
    }
}
```

## 3. Paginação (§2.6)

### 3.1 Envelope canônico

Toda listagem retorna o shape abaixo — sem exceção.

```json
{
    "data": [{ "id": "01J5K...", "...": "..." }],
    "meta": {
        "per_page": 50,
        "next_cursor": "eyJpZCI6MTIzfQ",
        "prev_cursor": null
    },
    "links": {
        "self": "https://api.portalartfinal.com.br/api/v1/.../convites?page%5Bcursor%5D=...",
        "next": "https://api.portalartfinal.com.br/api/v1/.../convites?page%5Bcursor%5D=eyJpZCI6MTIzfQ",
        "prev": null
    }
}
```

### 3.2 Quando usar cursor vs offset

- **Cursor-based** (default em listagens grandes/mutáveis): `convites`, `reservas`, `votos`, `pagamentos`, `notificacoes`, `pedidos_extras`.
- **Length-aware offset** (apenas em tabelas pequenas e estáveis): catálogo de produtos, enquetes do evento, setores do mapa. O envelope ganha `meta.total`, `meta.current_page`, `meta.last_page`.

### 3.3 Limites

- `page[size]` — máx 100, default 50.
- Página "0" → 422 com `ValidationError`.
- Cursor inválido → 422.

## 4. ULIDs em bindings (§2.7)

- Toda entidade pública expõe `ulid CHAR(26) UNIQUE`.
- Route model binding resolve pelo ulid: `Route::get('reservas/{reserva:ulid}', ...)`.
- IDs numéricos **nunca** aparecem em URL, token, response ou log.
- Trait `HasUlid` gera automático no `creating`.
- Para ULID inválido (tamanho ≠ 26) → middleware retorna 404 (não 422) para não vazar existência.

## 5. Headers X-\* (§2.8)

| Header                          | Direção | Papel                                                                              | Sempre presente?                         |
| ------------------------------- | ------- | ---------------------------------------------------------------------------------- | ---------------------------------------- |
| `X-Request-Id`                  | req/res | Gerado pelo `AttachRequestId` se ausente. Injeta em logs estruturados.             | Sim (response).                          |
| `X-Correlation-Id`              | req/res | Propagado entre webhooks/jobs. Valor ULID. Preenchido no primeiro contato externo. | Em operações que geram `correlation_id`. |
| `X-Idempotency-Key`             | req     | Obrigatório em `POST` de reservas, pedidos extras, pagamentos, lotes.              | Conforme endpoint.                       |
| `X-API-Deprecation`             | res     | Presente apenas em endpoints deprecados.                                           | Condicional.                             |
| `Authorization`                 | req     | `Bearer <token>` para mobile. SPA usa cookie `laravel_session`.                    | Em rotas `auth:sanctum`.                 |
| `Deprecation`, `Sunset`, `Link` | res     | Conforme RFC 8594 em endpoint deprecado.                                           | Condicional.                             |
| `Retry-After`                   | res     | Em 429 e em 503.                                                                   | Condicional.                             |

## 6. Idempotência (§2.9)

### 6.1 Middleware `IdempotencyKeyGuard`

Aplicado a rotas marcadas com `middleware('idempotent')`. Regras:

1. Header `X-Idempotency-Key` obrigatório; máximo 80 chars; caracteres válidos em ASCII imprimível.
2. Ausente ou vazio → 400 `"X-Idempotency-Key obrigatório"`.
3. Cache Redis TTL 24h: `idem:{user_id}:{route_name}:{key}` → `sha256(method|route_name|body)`.
4. Se key presente + payload diferente → 409 `"idempotency_key reutilizada com payload diferente"`.
5. Se key presente + payload igual → prossegue. A Action também verifica na tabela (segunda camada: coluna `idempotency_key UNIQUE`).

### 6.2 Rotas com `idempotent`

- `POST /eventos/{ulid}/convites/lotes`
- `POST /eventos/{ulid}/mesas/reservas`
- `POST /eventos/{ulid}/mesas/reservas/{ulid}/trocar`
- `POST /eventos/{ulid}/extras/pedidos`
- `POST /pagamentos/intents`

### 6.3 Não obrigatórias, mas recomendadas

- `POST /eventos/{ulid}/convites` (emissão individual).
- `POST /eventos/{ulid}/enquetes/{ulid}/votos` com `permite_edicao = true`.

### 6.4 Segunda camada — banco

Toda tabela que participa de operação idempotente tem coluna `idempotency_key VARCHAR(64) UNIQUE`. A Action usa `firstOrCreate` ou verifica existência antes do insert. Middleware é defesa em camadas, não substituto.

## 7. Rate Limiting por contexto (§2.10)

Registrados em `RateLimiterServiceProvider`. Cada endpoint declara qual limiter usa.

| Limiter        | Limite  | Chave           | Uso                                 |
| -------------- | ------- | --------------- | ----------------------------------- |
| `api` (global) | 120/min | `user_id ?? ip` | Baseline em todo `/api/*`.          |
| `login`        | 5/min   | `email + ip`    | `POST /auth/login`.                 |
| `convite`      | 10/min  | `ip`            | Rotas públicas com `convite.token`. |
| `seating`      | 5/min   | `user_id ?? ip` | Reservas e troca de assento.        |
| `voto`         | 3/min   | `user_id ?? ip` | Registro de voto.                   |
| `webhook`      | 600/min | `ip`            | Webhooks entrantes.                 |

### 7.1 Custom `throttle.actor:<nome>`

Middleware alias que consulta o limiter nomeado. Ex.: `throttle.actor:seating`.

### 7.2 Response 429

```json
{
    "error": "RateLimitExceeded",
    "message": "Limite de requisições excedido. Tente novamente em 42s.",
    "details": null,
    "request_id": "01J...",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

Headers:

```
Retry-After: 42
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0
```

## 8. Status HTTP por padrão (§2.13)

| Padrão de endpoint                            | Sucesso                                   | Erro cliente                                   | Erro servidor |
| --------------------------------------------- | ----------------------------------------- | ---------------------------------------------- | ------------- |
| `GET /<recurso>` (single)                     | 200                                       | 401 / 403 / 404                                | 500           |
| `GET /<recurso>` (list)                       | 200 (pode ser vazia)                      | 401 / 403 / 422 (filtro inválido)              | 500           |
| `POST /<recurso>` síncrono                    | 201 + `Location`                          | 400 / 401 / 403 / 409 / 422 / 429              | 500           |
| `POST /<recurso>` assíncrono                  | 202 + `status_url` no body                | 400 / 401 / 403 / 422                          | 500           |
| `PATCH /<recurso>/{id}`                       | 200 ou 204                                | 401 / 403 / 404 / 409 / 422                    | 500           |
| `PUT /<recurso>/{id}`                         | 200 ou 204                                | 401 / 403 / 404 / 422                          | 500           |
| `DELETE /<recurso>/{id}`                      | 204                                       | 401 / 403 / 404 / 409 (em uso)                 | 500           |
| `POST /<recurso>/{id}/<acao>` (state-machine) | 200 (novo estado)                         | 400 / 401 / 403 / 404 / 409 / 422              | 500           |
| Webhook                                       | 202 (aceito) ou 200 (`already_processed`) | 400 / 401                                      | 500           |
| Reserva de assento                            | 201 (hold criado)                         | 409 (ocupado) / 410 (hold expirou) / 422 / 429 | 500           |

### 8.1 Regras gerais

1. **204** sempre que a resposta não acrescenta informação.
2. **201** inclui header `Location` apontando para o recurso criado.
3. **202** inclui `status_url` no body para polling de jobs assíncronos.
4. **409 vs 422:** 422 é "payload sintaticamente válido mas falha em validação de campo"; 409 é "estado do servidor impede a operação" (assento ocupado, idempotency colisão, duplicado).
5. **410 Gone** apenas para hold expirado e endpoint após sunset.

## 9. Query string JSON:API style (§2.14)

### 9.1 Padrão único

```
GET /api/v1/eventos/{evento}/convites
    ?filter[status]=emitido,confirmado
    &filter[search]=maria
    &sort=-created_at,codigo
    &page[size]=50
    &page[cursor]=eyJpZCI6MTIzfQ
```

### 9.2 Regras

- `filter[<campo>]=<valor>` — múltiplos valores separados por `,` (semântica OR no campo). Múltiplos `filter[*]` combinam em AND entre campos.
- `sort=<campo>` — ascendente; prefixo `-` para descendente; múltiplos separados por `,`.
- `page[size]` — máx 100, default 50.
- `page[cursor]` — opaque token, ignorado se inválido → 422.
- Campos permitidos por endpoint definidos no FormRequest correspondente (`AllowedFilter`, `allowedSorts`).
- Filtro/sort em campo não permitido → **422** com `error: 'ValidationError'` e `details.fields[<param>]`.

### 9.3 Implementação

Usa `spatie/laravel-query-builder`. Exemplo:

```php
QueryBuilder::for(Convite::class)
    ->forEvento($evento)
    ->allowedFilters([
        AllowedFilter::exact('status'),
        AllowedFilter::partial('search', 'convidado_nome'),
    ])
    ->allowedSorts(['created_at', 'codigo', 'status'])
    ->defaultSort('-created_at')
    ->cursorPaginate($request->integer('page.size', 50));
```

## 10. Verbos em URL para state-machine (§2.15)

### 10.1 Decisão ADR

Endpoints do tipo `POST /reservas/{id}/confirmar`, `/trocar`, `/cancelar`, `/aprovar`, `/estornar` ficam **autorizados** quando representam transição de estado em máquina explícita.

### 10.2 Justificativa

- Padrão consagrado em APIs grandes (Stripe, GitHub, Atlassian).
- Alternativa `PATCH /reservas/{id}` com `{status: 'confirmada'}` permite cliente setar qualquer estado, fragilizando regra de servidor.
- Sub-recursos plurais (`POST /reservas/{id}/confirmations`) ficam estranhos em PT-BR e não acrescentam valor.

### 10.3 Restrição

CRUD (criar, atualizar campos, deletar) continua **sem verbo na URL** — usar HTTP method semantics (POST no recurso, PATCH no recurso, DELETE no recurso).

### 10.4 Lista fechada de ações permitidas no MVP

| Recurso          | Ações                                                                      |
| ---------------- | -------------------------------------------------------------------------- |
| `reservas`       | `confirmar`, `trocar`, `cancelar`                                          |
| `pedidos-extras` | `aprovar`, `cancelar`, `estornar`                                          |
| `adesoes`        | `confirmar`, `cancelar`                                                    |
| `convites`       | `reemitir`, `transferir`, `cancelar`                                       |
| `enquetes`       | `publicar`, `encerrar`                                                     |
| `pagamentos`     | `consultar` (idempotente, GET-like via POST p/ requerer `idempotency_key`) |

Qualquer ação fora desta tabela exige nota no PR e atualização desta seção antes do merge.

## 11. Content-Type e Accept

- Request body: `application/json`. Endpoints que aceitam multipart (uploads) explicitados no contrato.
- Response: `application/json; charset=utf-8`.
- Webhook de gateway aceita `application/json` — assinatura HMAC calculada sobre o raw body.

## 12. Autenticação — resumo

| Guard                  | Uso                                | Como envia                                                  | Provedor              |
| ---------------------- | ---------------------------------- | ----------------------------------------------------------- | --------------------- |
| `sanctum` (cookie SPA) | React web                          | Cookie `laravel_session` automático                         | `portal_users`        |
| `sanctum` (bearer)     | Mobile / integrações               | `Authorization: Bearer <token>`                             | `portal_users`        |
| `admin`                | Backoffice                         | Cookie de sessão                                            | `admin_users`         |
| `convite` (custom)     | Convidado externo com token mágico | Middleware `convite.token` resolve `/convite/{token_bruto}` | `convites.token_hash` |

Fluxo SPA detalhado: ver `PLANEJAMENTO_BACKEND_APIV1.md` §6.2.
Token de convite: §6.3.

## 13. Envelope de erro — referência rápida

Sempre o mesmo shape. Detalhes em `error-envelope.md`.

```json
{
    "error": "ValidationError",
    "message": "Dados de entrada inválidos.",
    "details": { "fields": { "email": ["O campo email é obrigatório."] } },
    "request_id": "01J...",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

## 14. Ordenação e filtragem por Resource

Cada Resource declara **explicitamente** em seu FormRequest (ou no controller de listagem) quais filtros e sorts são permitidos. Exemplo em `api-contract.md` para cada endpoint de listagem.

## 15. I18n

- Mensagens em response de erro: PT-BR. Locale do response não é negociável via `Accept-Language` no MVP.
- Horários: ISO 8601 com offset (ex.: `2026-04-17T14:32:11-03:00`) ou `Z` quando UTC. Armazenados em UTC no banco.
- Valores monetários: `int centavos` em todos os payloads. Formatação PT-BR (`R$ 1.500,99`) **só** em renderizações que sirvam humanos (admin, e-mail); API entrega sempre o int.

## 16. Evolução segura

- Novos campos em Resource → adicionar com `$this->when()` se opcional ou não-crítico.
- Remoção de campo → marca deprecação na doc + header `Deprecation: true` no endpoint que ainda usa.
- Mudança de tipo → **breaking**, requer v2.
- Novo endpoint → OK em v1.
- Novo filtro → OK em v1.
- Renomear campo → **breaking**, requer v2 (ou estratégia dual com prefixo).

## 17. Referências cruzadas

- `api-contract.md` — especificação endpoint-por-endpoint.
- `openapi-skeleton.yaml` — schema OpenAPI 3.1.
- `error-envelope.md` — mapa Throwable → HTTP code.
- `integrations.md` — gateway, storage, e-mail, push, Sentry.
- `api-CHANGELOG.md` — política de deprecação e histórico de versões.
