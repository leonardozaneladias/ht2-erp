# ht2ml/extensao-api

A camada comum de uma API REST num produto HT2ML: envelope de resposta, paginação, idioma, envelope de erro, limite de uso, tokens Sanctum e o portão de OpenAPI.

Instalar o pacote já liga tudo. As rotas entram pelo canal de API do core (`ht2ml/core` ≥ 0.5). Quem é o usuário da API, e qual guard o autentica, é decisão do produto.

```bash
composer require ht2ml/extensao-api
php artisan migrate   # personal_access_tokens, se o produto ainda não tiver
```

## O que muda ao instalar

| Onde | O quê |
|---|---|
| Grupo `api` | `ForcarRespostaJson` e `DefinirIdioma` entram **no começo** do grupo. Uma rota de API sem `Accept` responde 401 em JSON, e não um 302 para o login do painel. |
| Exceções sob `api/*` | Todas saem como `{success: false, message, data: {}, errors}`, no idioma pedido: 401, 403, 404, 405, 422, 429, o `abort()` do produto e 500 (fora de debug). |
| Limiter `api` | 120/min por **tipo + id** do usuário, ou por IP. O produto que registrar o próprio `RateLimiter::for('api')` depois vence. |
| Alias `api.principal-ativo` | Barra, na requisição seguinte, o usuário cujo model implementa `PodeSerDesativado` e está inativo. |
| `php artisan api:openapi` | Grava `docs/openapi.json`. Com `--verificar`, falha se o arquivo não descreve mais a API. É o portão de CI. |
| OpenAPI | As respostas de erro documentadas passam a ser o envelope real, e não o formato padrão do Laravel. |

## Rotas

```php
// No ServiceProvider do módulo
ModuleRegistry::modulo('loja')
    ->rotasDeApi(__DIR__ . '/../routes/api.php');   // sob /api, name api.

// routes/api.php do módulo — a versão é do módulo
Route::prefix('v1')->middleware(['auth:sanctum', 'api.principal-ativo'])->group(function () {
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
});
```

## Respostas

```php
use HT2ML\Api\Support\ApiResponse;
use HT2ML\Api\Support\PaginacaoApi;

return ApiResponse::sucesso(new PedidoResource($pedido), status: 201);

$pagina = Pedido::query()->paginate(PaginacaoApi::porPagina($request));
return ApiResponse::pagina('pedidos', PedidoResource::collection($pagina), $pagina);
// data: { pedidos: [...], meta: { pagina_atual, por_pagina, total, ultima_pagina } }
```

Todo Resource estende `HT2ML\Api\Http\Resources\ApiResource`. Nunca se serializa um model cru.

## O usuário da API

O model que autentica pela API usa o `HasApiTokens` do Sanctum e, se fizer sentido, implementa os contratos:

```php
final class Cliente extends Authenticatable implements TemIdiomaPreferido, PodeSerDesativado
{
    use HasApiTokens;

    public function idiomaPreferido(): ?string { return $this->idioma; }   // 'pt' | 'en' | 'es'
    public function estaAtivo(): bool { return $this->status === Status::Ativo; }
}
```

O idioma da resposta segue esta ordem: `Accept-Language`, depois `idiomaPreferido()`, depois o default da aplicação. Para validar a preferência, use `DefinirIdioma::idiomasSuportados()`, que é a mesma lista que o middleware entende.

## Configuração

`php artisan vendor:publish --tag=api-config` publica `config/api.php`, com estas chaves:
- `prefixo`, onde o envelope de erro vale;
- `paginacao.padrao` e `paginacao.teto`;
- `idiomas`;
- `limite_por_minuto`;
- `openapi.arquivo`.

## CI

```yaml
- name: OpenAPI em dia com o código
  run: php artisan api:openapi --verificar
```

O comando precisa de banco migrado: o Scramble inspeciona as tabelas dos models para inferir os campos. No CI, um SQLite basta. Se o export falhar, o comando falha também.

A comparação ignora `servers`, que o Scramble deriva do `APP_URL`. Assim, o arquivo gerado na máquina de quem escreveu e o gerado no CI não divergem por um motivo que não é contrato.

## O que não está aqui (ainda)

- Login, logout e `me` genéricos, e o registro de aparelho (`device_tokens`). Por enquanto são do produto, porque dependem de quem é o usuário.
- Contratos de IA: ficam em `ht2ml/extensao-ia`.
