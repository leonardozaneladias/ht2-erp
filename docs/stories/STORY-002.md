# Configurar guards de autenticação e Sanctum

**ID:** STORY-002  
**Epic:** F1-E1 — Setup & Configuração  
**Priority:** Must Have  
**Story Points:** 2  
**Status:** Not Started  
**Skills:** `laravel-best-practices`, `laravel-security`

## User Story

Como **desenvolvedor do Portal ArtFinal**
Quero **ter os guards `admin`, `portal` e `sanctum` configurados corretamente, com CORS e modelos stub disponíveis**
Para que **nenhuma rota, middleware ou test de autenticação precise redefinir guard ou provider — bastando referenciar o guard correto**

## Acceptance Criteria

- [ ] `config/auth.php` contém guard `admin` com driver `session` e provider `admins` (model `App\Models\AdminUser`)
- [ ] `config/auth.php` contém guard `portal` com driver `session` e provider `portals` (model `App\Models\PortalUser`)
- [ ] `config/auth.php` contém guard `sanctum` implícito via `config/sanctum.php` (não declarado em `auth.guards` — Sanctum gerencia internamente)
- [ ] `config/sanctum.php` tem `stateful` incluindo `localhost`, `localhost:3000`, `127.0.0.1`, `127.0.0.1:3000` e a variável `SANCTUM_STATEFUL_DOMAINS`
- [ ] `config/sanctum.php` tem `expiration` lida de `SANCTUM_TOKEN_EXPIRATION` (default `null` — sem expiração por padrão, configurável por ambiente)
- [ ] `config/cors.php` tem `allowed_origins` lendo de `CORS_ALLOWED_ORIGINS` (separado por vírgula), com fallback para `['http://localhost:3000']`
- [ ] `config/cors.php` tem `supports_credentials` = `true` (necessário para SPA Sanctum com cookies)
- [ ] `app/Models/AdminUser.php` existe com `$table = 'admin_users'`, `$guard_name = 'admin'`, trait `HasRoles` (spatie), tipo de retorno `casts(): array` e `declare(strict_types=1)`
- [ ] `app/Models/PortalUser.php` existe com `$table = 'portal_users'`, sem trait `HasRoles` (portal não usa ACL de roles nesta fase), tipo de retorno `casts(): array` e `declare(strict_types=1)`
- [ ] Nenhuma migration de `admin_users` ou `portal_users` é criada nesta story (reservado para F1-E4)
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros neste arquivo

## Technical Notes

### Arquivos a criar/modificar

- `config/auth.php` — já contém guards `admin` e `portal` (verificar e validar, não sobrescrever se já correto)
- `config/sanctum.php` — atualizar `stateful` e `expiration` conforme critérios acima
- `config/cors.php` — atualizar `allowed_origins` e garantir `supports_credentials = true`
- `app/Models/AdminUser.php` — adicionar `$guard_name = 'admin'` e revisar para conformidade com os critérios
- `app/Models/PortalUser.php` — criar/revisar classe conforme critérios
- `.env.example` — adicionar `SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000` e `SANCTUM_TOKEN_EXPIRATION=` e `CORS_ALLOWED_ORIGINS=http://localhost:3000`

### Observações técnicas

- O `$guard_name` em `AdminUser` é necessário para o `spatie/laravel-permission` saber qual guard usar ao verificar roles/permissions. Sem ele, o Spatie usa o guard default (`web`), causando `Guard [web] does not exist for model [AdminUser]`.
- O `PortalUser` deliberadamente **não** usa `HasRoles` nesta fase — formandos não têm controle de ACL granular, só autenticação. Se futuramente precisar de roles no portal, esta story deverá ser revisada.
- O driver `sanctum` para guards API não precisa ser declarado explicitamente em `config/auth.php` — o `EnsureFrontendRequestsAreStateful` middleware do Sanctum e o guard `sanctum` são registrados automaticamente pelo `SanctumServiceProvider`.
- `supports_credentials = true` no CORS é **obrigatório** para o fluxo de cookies de sessão SPA (Sanctum stateful). Sem isso, o browser descarta o cookie de sessão.
- Não confundir `SANCTUM_TOKEN_EXPIRATION` (tokens de API — minutos) com `SESSION_LIFETIME` (sessão web — minutos). São configurações independentes.

## Dependencies

- **Blocked by:** STORY-001 (Sanctum deve estar instalado)
- **Blocks:** STORY-004 (AuthServiceProvider depende dos guards configurados)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY002` verde
- [ ] Teste unitário: `Auth::guard('admin')->getProvider()->getModel()` retorna `App\Models\AdminUser`
- [ ] Teste unitário: `Auth::guard('portal')->getProvider()->getModel()` retorna `App\Models\PortalUser`
- [ ] Teste de configuração: `config('sanctum.stateful')` contém `localhost`
- [ ] Teste de configuração: `config('cors.supports_credentials')` retorna `true`
