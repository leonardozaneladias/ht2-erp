# Middleware ResolveConviteToken + endpoints auth base (MeController, LoginController)

**ID:** STORY-008
**Epic:** F1-E3 — Camada HTTP (routes + middlewares)
**Priority:** Must Have
**Story Points:** 3
**Status:** Not Started
**Skills:** `laravel-best-practices`, `laravel-security`, `livewire-development`

## User Story

Como **formando acessando o Portal ArtFinal via link de convite**
Quero **que meu token de convite seja resolvido automaticamente ao acessar as rotas de convite**
Para que **a experiência de acesso seja transparente e os endpoints de autenticação base estejam disponíveis e seguros**

## Acceptance Criteria

- [ ] `app/Http/Middleware/ResolveConviteToken.php` existe, extrai `{token}` do parâmetro de rota, busca o `PortalUser` correspondente via `PortalUser::where('convite_token', $token)->firstOrFail()` e injeta o usuário no request via `$request->merge(['_resolved_portal_user' => $portalUser])`
- [ ] `ResolveConviteToken` retorna 404 quando o token não for encontrado (não 500)
- [ ] `app/Http/Controllers/Api/V1/Auth/MeController.php` existe com método `__invoke(Request $request): JsonResponse` que retorna 401 sem Sanctum válido e 200 com dados do usuário autenticado
- [ ] `app/Http/Controllers/Api/V1/Auth/LoginController.php` existe com método `store(LoginRequest $request): JsonResponse` — stub que retorna `['message' => 'Em implementação']` com status 501
- [ ] `app/Http/Requests/Api/V1/Auth/LoginRequest.php` existe com regras de validação para `email` (required|email) e `password` (required|string|min:8)
- [ ] Rota `GET /api/v1/me` registrada em `routes/api/v1.php` apontando para `MeController` com middleware `auth:sanctum`
- [ ] Rota `POST /api/v1/auth/login` registrada apontando para `LoginController@store`
- [ ] `ResolveConviteToken` registrado no alias de middlewares em `bootstrap/app.php` como `convite.token`
- [ ] Rotas do grupo `Convites` usam `->middleware('convite.token')` em `routes/api/v1.php`
- [ ] `GET /api/v1/me` retorna 401 para request sem token Sanctum
- [ ] `GET /api/v1/me` retorna 200 com `{ id, ulid, name, email }` para request com token Sanctum válido
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `app/Http/Middleware/ResolveConviteToken.php` — resolve token de convite da URL, injeta PortalUser no request
- `app/Http/Controllers/Api/V1/Auth/MeController.php` — GET /api/v1/me, retorna usuário autenticado
- `app/Http/Controllers/Api/V1/Auth/LoginController.php` — POST /api/v1/auth/login, stub 501
- `app/Http/Requests/Api/V1/Auth/LoginRequest.php` — FormRequest com validação de email e password
- `routes/api/v1.php` — adicionar rotas me e auth/login nos grupos correspondentes
- `bootstrap/app.php` — registrar alias `convite.token`

### Observações técnicas

**Estrutura de namespaces dos controllers:**

```
app/Http/Controllers/
└── Api/
    └── V1/
        └── Auth/
            ├── MeController.php
            └── LoginController.php
```

**MeController — implementação:**

```php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\Acesso\PortalUser $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id'    => $user->ulid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
```

**ResolveConviteToken:**

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Acesso\PortalUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveConviteToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (! $token) {
            return $next($request);
        }

        $portalUser = PortalUser::where('convite_token', $token)->first();

        if (! $portalUser) {
            abort(404, 'Token de convite inválido ou expirado.');
        }

        $request->merge(['_resolved_portal_user' => $portalUser]);

        return $next($request);
    }
}
```

**Nota:** O campo `convite_token` na tabela `portal_users` será adicionado na STORY-009 ou em migração complementar. Nesta story, o middleware pode ser criado mesmo que o campo não exista ainda — os testes de integração completos dependem de STORY-009.

**Rotas atualizadas em routes/api/v1.php:**

```php
// Me
Route::prefix('me')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/', \App\Http\Controllers\Api\V1\Auth\MeController::class);
});

// Auth
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'store']);
});

// Convites
Route::prefix('convites')->middleware('convite.token')->group(function (): void {
    // TODO: implementar em Fases posteriores
});
```

**ID público na resposta:** sempre usar `ulid`, nunca `id` sequencial.

## Dependencies

- **Blocked by:** STORY-007 (middlewares core devem estar registrados)
- **Blocks:** STORY-011 (models PortalUser precisam existir para o middleware funcionar completamente)

## Testing Requirements

- [ ] Teste feature: `GET /api/v1/me` sem token retorna `{"message": "Unauthenticated."}` com status 401
- [ ] Teste feature: `GET /api/v1/me` com token Sanctum válido retorna 200 com `data.ulid`, `data.name`, `data.email`
- [ ] Teste feature: `GET /api/v1/me` nunca expõe `data.id` (sequencial) na resposta
- [ ] Teste unit: `ResolveConviteToken` retorna 404 para token inexistente
- [ ] Teste unit: `ResolveConviteToken` injeta `_resolved_portal_user` no request para token válido
- [ ] Teste unit: `LoginRequest` falha validação sem `email` e `password`
- [ ] `php artisan test --compact --filter=STORY008` verde
