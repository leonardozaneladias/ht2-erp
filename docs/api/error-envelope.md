---
title: Envelope de Erro — API v1
version: 1.0.0
date: 2026-04-18
status: draft
---

# Envelope de Erro — API v1

Toda resposta de erro de `api/v1` e `webhooks/*` segue um envelope único. Sem isso, clientes React/RN tratariam N formatos distintos e a evolução do backend seria breaking por qualquer ajuste.

> Fonte: `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2.11.

## 1. Schema do envelope

```json
{
    "error": "<ErrorKey>",
    "message": "<texto legível em PT-BR>",
    "details": { "fields": { "<campo>": ["<mensagem>"] } },
    "request_id": "01J5K2N7QMHV1FJZ8H0PR3RV9C",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

- `error` — string constante identificando a classe do erro (usada por clientes para branch lógico).
- `message` — texto amigável. Em produção, para 5xx, substituído por `"Erro interno. Veja request_id nos logs."`.
- `details` — objeto opcional. Em `ValidationError` traz `fields[<nome>] = [<msgs>]`. Em outros casos, pode ser `null`.
- `request_id` — copiado do header `X-Request-Id`. Sempre presente.
- `timestamp` — `now()->toIso8601String()`.

## 2. Mapa canônico `Throwable → HTTP code → error key`

| Exceção                                                                | HTTP | `error`               | Semântica                                                   |
| ---------------------------------------------------------------------- | ---- | --------------------- | ----------------------------------------------------------- |
| `Illuminate\Auth\AuthenticationException`                              | 401  | `Unauthenticated`     | Ausência/invalidez de credencial (token Sanctum ou sessão). |
| `Illuminate\Auth\Access\AuthorizationException`                        | 403  | `Forbidden`           | Autenticado, mas Policy nega.                               |
| `Illuminate\Validation\ValidationException`                            | 422  | `ValidationError`     | Payload sintaticamente válido, mas falha em regras.         |
| `Illuminate\Database\Eloquent\ModelNotFoundException`                  | 404  | `NotFound`            | Route model binding falhou (ULID não existe).               |
| `Symfony\Component\HttpKernel\Exception\NotFoundHttpException`         | 404  | `NotFound`            | Rota não existe.                                            |
| `Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException` | 405  | `MethodNotAllowed`    | Método HTTP incorreto.                                      |
| `Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException`  | 429  | `RateLimitExceeded`   | Rate limit estourado.                                       |
| `App\Exceptions\Domain\DomainException`                                | 409  | `DomainError`         | Erro de regra de domínio genérico.                          |
| `App\Exceptions\Domain\InvariantViolationException`                    | 409  | `InvariantViolation`  | Transição de estado ilegal.                                 |
| `App\Exceptions\Seating\AssentoIndisponivelException`                  | 409  | `AssentoIndisponivel` | Outro já reservou o assento.                                |
| `App\Exceptions\Seating\HoldExpiradoException`                         | 410  | `HoldExpirado`        | Hold venceu antes de confirmar.                             |
| `App\Exceptions\Cota\CotaEsgotadaException`                            | 409  | `CotaEsgotada`        | Saldo de cota insuficiente.                                 |
| `App\Exceptions\Pagamento\WebhookInvalidoException`                    | 400  | `WebhookInvalido`     | Assinatura HMAC inválida ou payload malformado.             |
| `App\Exceptions\Pagamento\GatewayIndisponivelException`                | 502  | `GatewayIndisponivel` | Gateway respondeu erro ou timeout.                          |
| `App\Exceptions\Pagamento\PagamentoDuplicadoException`                 | 409  | `PagamentoDuplicado`  | Mesmo `gateway_reference` já aplicado.                      |
| `App\Exceptions\Api\IdempotencyConflictException`                      | 409  | `IdempotencyConflict` | Key reutilizada com payload diferente.                      |
| `App\Exceptions\Api\EndpointSunsetException`                           | 410  | `EndpointSunset`      | Endpoint após `Sunset` declarado em §2.3.                   |
| `Illuminate\Http\Exceptions\PostTooLargeException`                     | 413  | `PayloadTooLarge`     | Request body acima do limite.                               |
| `Illuminate\Http\Exceptions\ThrottleRequestsException`                 | 429  | `RateLimitExceeded`   | Variante do throttle middleware nativo.                     |
| Falha em circuit breaker / dependência externa                         | 503  | `ServiceUnavailable`  | Retentável com `Retry-After`.                               |
| `Throwable` não tratado                                                | 500  | `InternalServerError` | Bug. Apenas `request_id` no body em prod.                   |

## 3. Implementação no handler global

Conforme `PLANEJAMENTO_BACKEND_APIV1.md` §2.11:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\Throwable $e, Request $request) {
        if (! $request->is('api/*', 'webhooks/*')) {
            return null; // web/admin mantêm comportamento padrão
        }

        [$code, $errorKey] = match (true) {
            $e instanceof AuthenticationException                              => [401, 'Unauthenticated'],
            $e instanceof AuthorizationException                               => [403, 'Forbidden'],
            $e instanceof ValidationException                                  => [422, 'ValidationError'],
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException                                => [404, 'NotFound'],
            $e instanceof TooManyRequestsHttpException                         => [429, 'RateLimitExceeded'],
            $e instanceof InvariantViolationException                          => [409, 'InvariantViolation'],
            $e instanceof AssentoIndisponivelException                         => [409, 'AssentoIndisponivel'],
            $e instanceof HoldExpiradoException                                => [410, 'HoldExpirado'],
            $e instanceof CotaEsgotadaException                                => [409, 'CotaEsgotada'],
            $e instanceof WebhookInvalidoException                             => [400, 'WebhookInvalido'],
            default                                                            => [500, 'InternalServerError'],
        };

        return response()->json([
            'error'      => $errorKey,
            'message'    => app()->isProduction() && $code === 500
                ? 'Erro interno. Veja request_id nos logs.'
                : $e->getMessage(),
            'details'    => $e instanceof ValidationException ? ['fields' => $e->errors()] : null,
            'request_id' => $request->header('X-Request-Id'),
            'timestamp'  => now()->toIso8601String(),
        ], $code);
    });
});
```

## 4. Exemplos de resposta por erro

### 4.1 401 Unauthenticated

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json
X-Request-Id: 01J5K2N7QMHV1FJZ8H0PR3RV9C

{
  "error": "Unauthenticated",
  "message": "Unauthenticated.",
  "details": null,
  "request_id": "01J5K2N7QMHV1FJZ8H0PR3RV9C",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.2 403 Forbidden

```http
HTTP/1.1 403 Forbidden
{
  "error": "Forbidden",
  "message": "This action is unauthorized.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.3 422 ValidationError

```http
HTTP/1.1 422 Unprocessable Entity
{
  "error": "ValidationError",
  "message": "The assento ulid field is required.",
  "details": {
    "fields": {
      "assento_ulid": ["O campo assento ulid é obrigatório."],
      "origem":       ["O campo origem deve ter um dos valores: formando, comissao, admin, operacao."]
    }
  },
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.4 409 AssentoIndisponivel

```http
HTTP/1.1 409 Conflict
{
  "error": "AssentoIndisponivel",
  "message": "Assento 01J5K... já possui reserva ativa.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.5 410 HoldExpirado

```http
HTTP/1.1 410 Gone
{
  "error": "HoldExpirado",
  "message": "O hold da reserva 01J5... expirou. Recomece a escolha.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.6 409 CotaEsgotada

```http
HTTP/1.1 409 Conflict
{
  "error": "CotaEsgotada",
  "message": "Sua cota de convites do tipo 'base' foi esgotada (4/4 utilizados).",
  "details": {
    "cota": { "tipo": "base", "utilizados": 4, "limite": 4 }
  },
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.7 409 IdempotencyConflict

```http
HTTP/1.1 409 Conflict
{
  "error": "IdempotencyConflict",
  "message": "idempotency_key reutilizada com payload diferente.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.8 429 RateLimitExceeded

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 42
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0

{
  "error": "RateLimitExceeded",
  "message": "Limite de requisições excedido. Tente novamente em 42s.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.9 500 InternalServerError

```http
HTTP/1.1 500 Internal Server Error
{
  "error": "InternalServerError",
  "message": "Erro interno. Veja request_id nos logs.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.10 400 WebhookInvalido

```http
HTTP/1.1 400 Bad Request
{
  "error": "WebhookInvalido",
  "message": "assinatura HMAC divergente",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

### 4.11 410 EndpointSunset

```http
HTTP/1.1 410 Gone
Deprecation: true
Sunset: Wed, 31 Dec 2026 23:59:59 GMT
Link: <https://api.portalartfinal.com.br/api/v2/recurso>; rel="successor-version"

{
  "error": "EndpointSunset",
  "message": "Endpoint removido. Consulte successor-version.",
  "details": null,
  "request_id": "01J5...",
  "timestamp": "2026-04-17T14:32:11Z"
}
```

## 5. Headers obrigatórios em erro

Toda resposta de erro inclui:

- `Content-Type: application/json; charset=utf-8`
- `X-Request-Id: <ulid>` (espelho do header de entrada ou gerado)
- `X-Correlation-Id: <ulid>` (quando a operação já havia estabelecido correlação)

Em 429:

- `Retry-After: <segundos>`
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`

Em 410 após Sunset:

- `Deprecation: true`
- `Sunset: <http-date>`
- `Link: <url>; rel="successor-version"`

Em 503:

- `Retry-After: <segundos>` (recomendado).

## 6. Como o frontend deve tratar

### 6.1 Regras gerais

1. **Branch por `error` (chave estável), nunca por `message`** — mensagens podem ser traduzidas ou reescritas sem incrementar versão.
2. **Sempre expor `request_id` em UI técnica** (console, toast de erro) para facilitar suporte.
3. **401 em rota autenticada** → limpar token local, redirecionar para login.
4. **403** → mostrar "sem permissão" sem expor estrutura do sistema.
5. **404** pode ocorrer mesmo com recurso existente quando Policy esconde — tratar como "não encontrado".
6. **409** em `IdempotencyConflict` → erro de aplicação, não retry com mesmo payload; cliente refez escolha errada.
7. **409** em `AssentoIndisponivel` → UI recarrega mapa (reservation flow inválido).
8. **410 HoldExpirado** → reiniciar fluxo de seleção.
9. **422** → mapear `details.fields[<campo>]` em mensagens inline do formulário.
10. **429** → respeitar `Retry-After`. Delay exponencial se o header ausente.
11. **5xx** → retry com backoff exponencial (pelo menos em `GET` idempotente); para `POST`, só retry se header `Retry-After` estiver presente.

### 6.2 Exemplo TS (pseudo)

```ts
type ApiError = {
    error: string;
    message: string;
    details: { fields?: Record<string, string[]> } | null;
    request_id: string;
    timestamp: string;
};

function tratar(err: ApiError, status: number) {
    switch (err.error) {
        case 'Unauthenticated':
            return authStore.logoutAndRedirect();
        case 'Forbidden':
            return toast.error('Você não tem permissão para esta ação.');
        case 'ValidationError':
            return form.setErrors(err.details?.fields ?? {});
        case 'AssentoIndisponivel':
            return seatingStore.refreshMapa({ toastMsg: 'Assento tomado, escolha outro.' });
        case 'HoldExpirado':
            return seatingStore.recomecarSelecao();
        case 'CotaEsgotada':
            return toast.warning(err.message);
        case 'IdempotencyConflict':
            return log.error('chave reutilizada; gerando nova e revisando UX');
        case 'RateLimitExceeded':
            return schedulerRetry(retryAfterHeader);
        case 'EndpointSunset':
            return clientUpdateBanner.show(successorVersionUrl);
        default:
            return toast.error(`Erro (${err.error}). Id: ${err.request_id}`);
    }
}
```

## 7. Anti-patterns proibidos

1. ❌ Envelope customizado em um controller específico. Sempre o handler global.
2. ❌ `return response()->json(['msg' => ...])` em uma Action. Actions lançam exceções.
3. ❌ `abort(422)` sem `ValidationException`. Use `throw ValidationException::withMessages([...])`.
4. ❌ Dados sensíveis em `message` (CPF completo, senha, stack trace em prod).
5. ❌ `error` em inglês técnico sem entrada na tabela §2. Toda chave nova exige PR atualizando este documento.

## 8. Cobertura de teste obrigatória

Em Pest feature tests (ver `PLANEJAMENTO_BACKEND_APIV1.md` Apêndice A §16):

- `GET /api/v1/me` sem auth retorna 401 com envelope.
- `POST /api/v1/me/adesoes` sem policy retorna 403 com envelope.
- `POST` com payload inválido retorna 422 com `details.fields`.
- `POST /api/v1/eventos/.../mesas/reservas` em assento tomado retorna 409 `AssentoIndisponivel`.
- `POST .../confirmar` após 5min retorna 410 `HoldExpirado`.
- Webhook com assinatura inválida retorna 400 `WebhookInvalido`.
- Rota sunset retorna 410 `EndpointSunset` com os 3 headers de deprecação.
