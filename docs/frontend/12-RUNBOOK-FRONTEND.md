---
title: 'Runbook Operacional — Portal SPA React'
module: frontend
doc_type: runbook
version: 1.0.0
status: ativo
owner: dev-frontend
audience: [dev-frontend, tech-lead, devops, on-call]
last_updated: 2026-04-18
related:
    - ./00-README-INDEX.md
    - ./05-FRONTEND-SAD.md
    - ./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md
    - ./10-QA-TEST-STRATEGY.md
    - ./11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md
    - ./14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md
    - ../devops/runbook-deploy.md
    - ../devops/runbook-operations.md
    - ../devops/monitoring-alerts.md
---

# Runbook Operacional — Portal SPA React

> **O que é:** manual prático de operação do SPA. Inclui subir local, validar auth, regenerar tipos, debugar erros comuns, validar build, publicar deploy e checklist de release. Complementa `docs/devops/runbook-deploy.md` (foco infra Laravel).
>
> **Como usar:** este doc não deve ser lido de ponta a ponta — é consulta. Procure pelo sintoma.

---

## Sumário

1. [Como subir local](#1-como-subir-local)
2. [Como validar auth flow](#2-como-validar-auth-flow)
3. [Como regenerar tipos](#3-como-regenerar-tipos)
4. [Como testar integrações](#4-como-testar-integrações)
5. [Como debugar erros comuns](#5-como-debugar-erros-comuns)
6. [Como validar build](#6-como-validar-build)
7. [Como publicar (deploy)](#7-como-publicar-deploy)
8. [Troubleshooting — tabela sintoma → causa → ação](#8-troubleshooting)
9. [Checklist pré-release](#9-checklist-pré-release)
10. [Monitoramento pós-deploy](#10-monitoramento-pós-deploy)
11. [Comandos úteis](#11-comandos-úteis)
12. [Contatos e escalation](#12-contatos-e-escalation)

---

## 1. Como subir local

### 1.1 Primeira vez

```bash
git clone git@github.com:artfinal/portalartfinal-v2.git
cd portalartfinal-v2

# Backend: containers
make up

# Backend: deps + .env + migrations
make bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
exit

# Admin (Inspinia Blade)
npm install
npm run dev          # terminal 1, se for mexer no admin

# SPA React
cd resources/spa
npm install
npm run codegen
npm run dev          # terminal 2 — Vite em :5173
```

Acessar:

| URL                        | O que é                    |
| -------------------------- | -------------------------- |
| `http://localhost`         | Shell SPA (portal)         |
| `http://localhost/admin`   | Admin Inspinia (Blade)     |
| `http://localhost/horizon` | Dashboard de filas         |
| `http://localhost/pulse`   | Dashboard de monitoramento |
| `http://localhost:8125`    | Mailpit (emails)           |
| `http://localhost:5050`    | pgAdmin                    |

### 1.2 Próximas vezes

```bash
make up
cd resources/spa && npm run dev
```

Em outro terminal, se mexer no Laravel:

```bash
make bash
# ... artisan commands
```

### 1.3 Parar

```bash
# Ctrl+C no Vite
make down              # derruba containers Laradock
```

### 1.4 Alternativas sem Laradock

Se Docker falha, usar host PHP + Postgres local:

```bash
brew services start postgresql@16 redis
php artisan serve       # em :8000
cd resources/spa && npm run dev   # em :5173
```

Ajustar `.env`: `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`, `APP_URL=http://localhost:8000`.

---

## 2. Como validar auth flow

### 2.1 Objetivo

Provar que o ciclo completo **CSRF → login → me → protegido** funciona. Se qualquer passo falhar, consultar §5 (debug).

### 2.2 Passos (DevTools Network aberto)

1. **Abrir DevTools → Application → Cookies → `localhost`**
   Estado esperado inicial: **vazio** ou apenas `laravel_session` sem login.

2. **Acessar `/login`**
    - Deve renderizar o form
    - Console: sem erros
    - Network: só bundle + tipos

3. **Preencher credenciais válidas e clicar Entrar**
    - Observe 3 requests em ordem:

    | #   | Método | URL                    | Status  | Headers importantes                                |
    | --- | ------ | ---------------------- | ------- | -------------------------------------------------- |
    | 1   | GET    | `/sanctum/csrf-cookie` | **204** | `Set-Cookie: XSRF-TOKEN=...; laravel_session=...`  |
    | 2   | POST   | `/api/v1/auth/login`   | **200** | Request: `X-XSRF-TOKEN`, `X-Request-Id`, body JSON |
    | 3   | GET    | `/api/v1/me`           | **200** | Retorna `{ data: { user: {...} } }`                |

4. **Cookies após login**
   Em DevTools → Application → Cookies → `localhost`:
    - `XSRF-TOKEN` ✅
    - `laravel_session` ✅
      Ambos `HttpOnly=false` para XSRF, `HttpOnly=true` para session, `SameSite=Lax`.

5. **Navegação `/portal/home`**
    - Sem redirect para /login
    - Conteúdo carrega, dados do usuário visíveis
    - Network: apenas requests esperados

6. **Recarregar página (F5)**
    - `GET /api/v1/me` dispara (hidratação do store)
    - Usuário permanece logado

7. **Logout**
    - `POST /api/v1/auth/logout` → 204
    - Cookies removidos (ou invalidados server-side)
    - Tentar `/portal/home` → redirect para `/login`

### 2.3 Header `X-Request-Id`

Todo request do SPA leva `X-Request-Id: <ulid>` gerado no interceptor. Verificar em Network → Request Headers. Esse ID aparece também na response e permite correlacionar com logs do backend.

### 2.4 Via terminal (curl)

```bash
# 1) CSRF
curl -i -c cookies.txt http://localhost/sanctum/csrf-cookie
# -> 204 + Set-Cookie

# 2) Extrair XSRF-TOKEN e usar no próximo request
XSRF=$(grep 'XSRF-TOKEN' cookies.txt | awk '{print $7}' | sed 's/%3D/=/g')

# 3) Login
curl -i -b cookies.txt -c cookies.txt \
  -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H "X-XSRF-TOKEN: ${XSRF}" \
  -H "X-Request-Id: smoke-$(date +%s)" \
  -d '{"email":"testa@portalart.app","senha":"senha12345"}'

# 4) Me
curl -i -b cookies.txt http://localhost/api/v1/me
```

### 2.5 Via Vitest smoke

```bash
cd resources/spa
npm run test -- tests/smoke/auth-flow.test.ts
```

---

## 3. Como regenerar tipos

### 3.1 Quando

- Endpoint novo adicionado em `docs/api/openapi-skeleton.yaml`
- Schema alterado (campo novo, campo removido, tipo mudou)
- Erro de TypeScript pedindo campo que você sabe que existe no backend
- CI falhou em `codegen:check`

### 3.2 Comando

```bash
cd resources/spa
npm run codegen
```

Gera/atualiza `src/api/types.gen.ts`.

### 3.3 Validar

```bash
# Diff
git diff src/api/types.gen.ts

# Typecheck
npm run typecheck
```

### 3.4 Commit

```bash
git add src/api/types.gen.ts
git commit -m "chore(api): regenerar types.gen a partir de openapi-skeleton"
```

### 3.5 Troubleshooting do codegen

| Erro                                             | Causa                                 | Solução                                              |
| ------------------------------------------------ | ------------------------------------- | ---------------------------------------------------- |
| `YAMLException: bad indentation`                 | YAML inválido no skeleton             | validar em https://editor.swagger.io                 |
| `Error: Reference not found: '#/components/...'` | `$ref` aponta para schema inexistente | corrigir path ou adicionar schema                    |
| Types gerados vazios                             | spec vazio ou path errado             | verificar `openapi-typescript` recebeu arquivo certo |
| `unexpected token`                               | versão do openapi-typescript antiga   | `npm i -D openapi-typescript@latest`                 |

---

## 4. Como testar integrações

### 4.1 Smoke manual completo (~5 min)

Fluxo happy-path ponta-a-ponta, para validar que tudo está vivo após deploy local:

1. `/login` → logar com `testa@portalart.app` / `senha12345`
2. `/portal/home` → dashboard carrega, cards visíveis
3. `/portal/adesao/1` → wizard renderiza, navegar até etapa 3 (ou fim)
4. `/portal/financeiro` → extrato lista parcelas
5. Abrir parcela → `/portal/pagamento/<ulid>` → criar intent de boleto
6. Pagar boleto (sandbox) → polling mostra "pago"
7. `/portal/convites` → lista cotas
8. Emitir convite → gerar link RSVP
9. Abrir `/rsvp/<token>` em anônimo → confirmar presença
10. `/portal/mesas` → selecionar cadeira → hold timer inicia → confirmar
11. Logout → tentar `/portal/home` → redirect

Se qualquer passo falha, abrir DevTools Network/Console e ir para §5.

### 4.2 Testes automatizados locais

```bash
cd resources/spa

# Unit + Integration
npm run test

# E2E core (auth + wizard + pagamento + mesas)
npm run test:e2e:core

# E2E full (inclui RSVP, convites, enquetes)
npm run test:e2e

# E2E interativo com UI
npm run test:e2e:ui

# Smoke sem login, só renderização das rotas
npm run smoke
```

### 4.3 Validar idempotência manualmente

1. Em `/portal/pagamento/<ulid>`, clicar "Gerar boleto"
2. **Antes** de a response voltar, clicar novamente (F5 rápido ou botão)
3. Resposta idêntica nos 2 cliques → idempotência OK
4. Em DevTools Network, observar `X-Idempotency-Key` **igual** nas 2 requests

### 4.4 Validar 401 → redirect

1. Logado em `/portal/home`
2. Abrir DevTools → Application → Cookies → deletar `laravel_session`
3. Clicar em qualquer link interno
4. Próximo request retorna 401 → interceptor chama `authStore.logout()` → redirect para `/login`

### 4.5 Validar 419 CSRF mismatch

1. Logado
2. Esperar sessão expirar (ou apagar `XSRF-TOKEN`)
3. Fazer uma ação POST (emitir convite)
4. Interceptor retry deve chamar `/sanctum/csrf-cookie` + reenviar o POST
5. Se falha no retry, redirect para `/login` com toast

### 4.6 Validar hold timer

1. `/portal/mesas` → clicar em cadeira disponível
2. Countdown aparece (ex: `04:59`)
3. Esperar 1 min → `03:59`
4. Abrir outra aba → mesma cadeira aparece "reservada" para outra sessão
5. Na aba original, confirmar → OK
6. Ou esperar expirar → countdown zera → cadeira volta a "disponível"

---

## 5. Como debugar erros comuns

### 5.1 CORS preflight failing

**Sintoma:** Network mostra `OPTIONS /api/v1/...` com status `CORS error` ou `401`. Request real nunca dispara.

**Causa:** `config/cors.php` não inclui `X-Request-Id` / `X-Idempotency-Key` em `allowed_headers`, ou `allowed_origins` não tem o domínio do SPA.

**Ação:**

```php
// config/cors.php
return [
  'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
  'allowed_methods' => ['*'],
  'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173'), 'http://localhost'],
  'allowed_origins_patterns' => [],
  'allowed_headers' => [
    'Content-Type',
    'X-Requested-With',
    'X-Request-Id',
    'X-Idempotency-Key',
    'X-XSRF-TOKEN',
    'Authorization',
    'Accept',
  ],
  'exposed_headers' => ['X-Request-Id'],
  'max_age' => 0,
  'supports_credentials' => true,
];
```

`php artisan config:clear` + testar novamente.

### 5.2 419 CSRF token mismatch

**Sintoma:** POST/PUT/DELETE retorna `419 Page Expired`.

**Causas possíveis:**

| Causa                                   | Diagnóstico                                            |
| --------------------------------------- | ------------------------------------------------------ |
| Não chamou `/sanctum/csrf-cookie` antes | Network: não há request a `/sanctum/csrf-cookie`       |
| `XSRF-TOKEN` não está sendo enviado     | Request Headers não tem `X-XSRF-TOKEN`                 |
| Token mal decodificado                  | Header `X-XSRF-TOKEN` tem `%3D` no final (URL encoded) |
| Domain do cookie errado                 | `SESSION_DOMAIN` no .env diferente do domínio acessado |
| Cookie `laravel_session` expirou        | Sessão > `SESSION_LIFETIME` (default 120 min)          |

**Ação:**

1. Verificar que Axios tem `withCredentials: true`
2. Verificar que cookie `XSRF-TOKEN` existe antes do POST
3. Ler cookie e decodeURIComponent:

```ts
function getXsrfToken(): string | null {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}
```

4. Interceptor de response deve capturar 419 e fazer retry após `/sanctum/csrf-cookie` (ver [TD §2](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md))

### 5.3 401 em rota protegida

**Sintoma:** `/api/v1/me` ou outro endpoint `/me/*` retorna 401 logo após login.

**Causas possíveis:**

- Cookie `laravel_session` não sendo enviado (faltou `withCredentials: true`)
- `SANCTUM_STATEFUL_DOMAINS` não inclui o domínio do SPA (`localhost:5173`)
- Cookie tem `SameSite=Strict` quando deveria ser `Lax`
- Sessão expirou (`SESSION_LIFETIME`)

**Ação:**

```bash
# Verificar config
php artisan config:show sanctum.stateful
# deve listar localhost,localhost:5173

# .env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173
SESSION_DOMAIN=localhost
SESSION_SAME_SITE=lax
SESSION_LIFETIME=120
```

Depois: `php artisan config:clear` e relogar.

### 5.4 Vite manifest not found / `Unable to locate file`

**Sintoma:** Laravel retorna `Illuminate\Foundation\ViteException` em HTML.

**Causa:** `npm run build` não rodou, ou modo dev não está ativo.

**Ação (dev):**

```bash
cd resources/spa
npm run dev     # mantém rodando
```

A SPA `spa.blade.php` deve usar `@viteReactRefresh @vite(['resources/spa/src/main.tsx'])`.

**Ação (prod/staging):**

```bash
cd resources/spa
npm run build
# gera dist/ com manifest.json
php artisan view:clear
php artisan config:cache
```

### 5.5 Hold timer dessincronizado

**Sintoma:** countdown mostra 1 min, mas servidor ainda considera válido; ou vice-versa.

**Causa:** cliente usa `Date.now()` local em vez de `hold_expires_at` do servidor.

**Ação:** garantir que `useHoldTimer` recebe o ISO string do servidor:

```ts
// ❌ errado
const [left, setLeft] = useState(5 * 60);
useEffect(() => {
    /* setInterval */
}, []);

// ✅ correto
export function useHoldTimer(expiresAtIso: string) {
    const [now, setNow] = useState(() => Date.now());
    useEffect(() => {
        const id = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(id);
    }, []);
    const expiresMs = new Date(expiresAtIso).getTime();
    return Math.max(0, Math.floor((expiresMs - now) / 1000));
}
```

Ao fazer confirm, se o servidor retornar 410 Gone / expirado, exibir mensagem e reiniciar fluxo.

### 5.6 Type error em `types.gen.ts`

**Sintoma:** `tsc` reclama de um campo que você "sabe" que existe.

**Causa:** `types.gen.ts` desatualizado.

**Ação:**

```bash
npm run codegen
npm run typecheck
```

Se persistir, o spec pode estar desatualizado. Validar com backend e atualizar `openapi-skeleton.yaml`.

### 5.7 TanStack Router 404

**Sintoma:** Acessar `/portal/nova-rota` retorna 404 do router.

**Causa:** arquivo de rota não criado ou `routeTree.gen.ts` não regerado.

**Ação:**

1. Verificar arquivo em `src/routes/portal/nova-rota.tsx`
2. O plugin Vite regenera `routeTree.gen.ts` automaticamente em dev
3. Se não, matar Vite e subir de novo: `npm run dev`
4. Confirmar que `src/app/router.ts` importa o routeTree gerado

### 5.8 Infinite loop em `useEffect`

**Sintoma:** requisição HTTP disparada centenas de vezes por segundo.

**Causa:** `useEffect` com dependências que mudam a cada render (função inline, objeto literal).

**Ação:**

```tsx
// ❌ errado
useEffect(() => {
    fetchData();
}, [{ id: 123 }]);

// ❌ errado — mas escondido
const options = { id: 123 };
useEffect(() => {
    fetchData(options);
}, [options]);

// ✅ correto — dependência primitiva
useEffect(() => {
    fetchData(id);
}, [id]);

// ✅ ainda melhor — usar TanStack Query
const { data } = useQuery({ queryKey: ['x', id], queryFn: () => fetchData(id) });
```

Habilitar ESLint `react-hooks/exhaustive-deps` ajuda a pegar antes.

### 5.9 409 Idempotency-Conflict

**Sintoma:** POST retorna 409 com body indicando conflito de idempotency.

**Causa:** mesma `X-Idempotency-Key` enviada com payload diferente do primeiro request.

**Ação:**

1. Identificar em DevTools Network a key em conflito
2. A key é gerada por contexto semântico — se o usuário mudou valor, o contexto muda
3. Gerar nova key:

```ts
// Regenerar ao mudar pagamento
function renewIdempotencyKey(context: string) {
    sessionStorage.removeItem(`idem:${context}`);
    return getOrCreateIdempotencyKey(context);
}
```

### 5.10 Tamagui não aplica tema em teste

**Sintoma:** RTL mostra componente sem estilos, ou crash `useTheme must be used within TamaguiProvider`.

**Causa:** wrapper de render não tem `TamaguiProvider`.

**Ação:** usar sempre `renderWithProviders` (ver [QA §3.5](./10-QA-TEST-STRATEGY.md)).

### 5.11 MSW não intercepta

**Sintoma:** teste de integração faz request real (ou falha por CORS).

**Causas:**

- Handler não registrado em `server.listen()`
- Request vai para URL não prevista (ex: `http://` vs `https://`, host diferente)
- MSW setup não carregado em `tests/setup.ts`

**Ação:**

1. Adicionar `onUnhandledRequest: 'error'` em `server.listen` — falha explícita
2. Verificar logs: MSW loga `[MSW] Intercepted request`
3. Alinhar URL base em `api/client.ts` com handlers

### 5.12 Cookie HttpOnly não aparece em JavaScript

**Sintoma:** `document.cookie` não lista `laravel_session`.

**Causa:** esperado. Cookies `HttpOnly` são inacessíveis ao JS. Só aparecem em DevTools.

**Ação:** não tentar ler. Confiar que o browser envia automaticamente com `withCredentials`.

### 5.13 Build gera bundle gigante (> 500 KB gzip)

**Sintoma:** `vite build` avisa sobre chunk grande.

**Causa:** import de lib pesada no bundle inicial, ou falta de code-split.

**Ação:**

1. `npm run build -- --mode=analyze` + abrir `stats.html`
2. Lazy load rotas pesadas (wizard, mesas):

```ts
const WizardPage = lazy(() => import('@/routes/portal/adesao.$step'));
```

3. Garantir que `tamagui` usa optimizer e tree-shake

---

## 6. Como validar build

### 6.1 Build local

```bash
cd resources/spa
npm run build
```

Gera `dist/` com:

- `dist/assets/index-<hash>.js`
- `dist/assets/index-<hash>.css`
- `dist/manifest.json` (lido pelo Laravel Vite helper)

### 6.2 Preview da build

```bash
npm run preview     # serve dist/ em :4173
```

Acessar `http://localhost:4173` — útil para testar prod-like sem Laravel.

### 6.3 Build integrado com Laravel

O `vite.config.ts` do SPA precisa expor manifest que o Laravel saiba ler (`@vite(...)`):

```ts
// resources/spa/vite.config.ts (trecho)
export default defineConfig({
    plugins: [react(), TanStackRouterVite()],
    build: {
        outDir: '../../public/spa',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: { input: 'src/main.tsx' },
    },
    base: '/spa/',
});
```

`resources/views/spa.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Portal ArtFinal</title>
    @viteReactRefresh
    @vite (['src/main.tsx'], 'resources/spa')
</head>
<body>
    <div id="root"></div>
</body>
</html>
```

### 6.4 Smoke pós-build

```bash
npm run smoke       # Playwright em todas rotas, sem erro console
```

### 6.5 Verificar tamanho

```bash
du -sh dist/assets/*.js
gzip -c dist/assets/*.js | wc -c   # bytes gzipped
```

Target: initial JS gzip ≤ 250 KB.

---

## 7. Como publicar (deploy)

### 7.1 Ambientes

| Ambiente | URL                             | Branch       | Auto? | Gate                      |
| -------- | ------------------------------- | ------------ | ----- | ------------------------- |
| staging  | `https://staging.portalart.app` | `staging`    | sim   | merge → CI verde → deploy |
| produção | `https://portalart.app`         | `main` + tag | não   | tag manual após smoke OK  |

### 7.2 Pipeline de deploy

```
Merge para main
      │
      ▼
┌────────────────┐
│ CI: quality    │  lint + typecheck + test
└────────┬───────┘
         ▼
┌────────────────┐
│ Build SPA      │  npm run build → artifact spa-dist-<sha>.tar.gz
└────────┬───────┘
         ▼
┌────────────────┐
│ Build Laravel  │  composer install --no-dev --optimize-autoloader
└────────┬───────┘
         ▼
┌────────────────┐
│ Deploy staging │  rsync / SFTP / deploy hook
└────────┬───────┘
         ▼
┌────────────────┐
│ Post-deploy    │  php artisan migrate --force, view:cache, route:cache, config:cache
└────────┬───────┘
         ▼
┌────────────────┐
│ Smoke staging  │  Playwright smoke → OK ou rollback automático
└────────┬───────┘
         ▼
[Tag manual v1.x.y]
         ▼
[Deploy produção — processo idêntico]
```

### 7.3 Comandos de deploy (simplificados)

```bash
# Localmente, gerar build production
cd resources/spa
npm ci
npm run build

# Gera dist/ com manifest.json
# Deploy: subir dist/ + arquivos Laravel

# No servidor de produção:
cd /var/www/portalart
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:cache
php artisan config:cache
php artisan route:cache
php artisan horizon:terminate    # graceful restart
```

### 7.4 Rollback

**Conceitual:** reverter commit + redeploy. SPA é stateless, então o rollback é seguro.

```bash
# Identificar tag anterior
git tag --sort=-creatordate | head -5

# Reverter
git revert <sha-ruim>
git push origin main
# CI redeploya
```

Em **emergência**, trocar artefato:

```bash
# No servidor
ln -sfn /var/www/releases/v1.2.3 /var/www/current
php artisan horizon:terminate
```

### 7.5 Feature flags (F7+)

Plano: integrar `unleash` ou simples store `featureFlags` no Laravel:

```ts
// src/hooks/use-feature-flag.ts
export function useFeatureFlag(flag: string): boolean {
    const { data } = useQuery({ queryKey: ['feature-flags'], staleTime: 5 * 60_000 });
    return Boolean(data?.[flag]);
}
```

No SPA, renderização condicional:

```tsx
{
    useFeatureFlag('enquetes-v2') ? <EnquetesV2 /> : <EnquetesV1 />;
}
```

---

## 8. Troubleshooting

Tabela de consulta rápida.

| Sintoma                                                 | Causa provável                                 | Ação primeira-linha                                      |
| ------------------------------------------------------- | ---------------------------------------------- | -------------------------------------------------------- |
| `ECONNREFUSED http://localhost`                         | Laradock não subiu                             | `make up` + `docker ps`                                  |
| `npm run dev` falha: porta 5173 ocupada                 | Outra instância Vite rodando                   | `lsof -i :5173` + `kill <pid>`                           |
| SPA carrega em branco, console: `Failed to load module` | Caminho do Vite errado, manifest ausente       | `npm run build` ou `npm run dev`                         |
| `/login` 404                                            | Catch-all Laravel não configurado              | Verificar `routes/portal.php` (ver §5.14)                |
| Network: CORS error em preflight                        | `config/cors.php` falta header/origin          | Ver §5.1                                                 |
| 419 em POST                                             | CSRF token ausente/expirado                    | Ver §5.2                                                 |
| 401 em GET /me após login                               | Cookie não enviado / Sanctum stateful errado   | Ver §5.3                                                 |
| ViteException no HTML                                   | Manifest ausente                               | Ver §5.4                                                 |
| Hold timer "pisca" no F5                                | Cliente lê expires_at local                    | Ver §5.5                                                 |
| `types.gen.ts` com erro de sintaxe                      | Codegen antigo ou YAML inválido                | Ver §3.5                                                 |
| Router mostra 404 em rota que existe                    | `routeTree.gen.ts` desatualizado               | `npm run dev` reinicia gerador                           |
| `useEffect` loopa                                       | Dependency array com objeto                    | Ver §5.8                                                 |
| 409 Idempotency-Conflict                                | Key reutilizada em contexto errado             | Ver §5.9                                                 |
| Tamagui crash em teste                                  | Sem TamaguiProvider                            | Ver §5.10                                                |
| MSW não mocka                                           | Handler ausente / URL diferente                | Ver §5.11                                                |
| `document.cookie` sem laravel_session                   | Normal (HttpOnly)                              | Ver §5.12                                                |
| Bundle > 500 KB gzip                                    | Lib grande sem code-split                      | Ver §5.13                                                |
| Testes passam local, falham CI                          | Seed diferente / timezone / flaky              | Reproduzir com `TZ=UTC` + `seed=42`                      |
| Playwright trace vazio                                  | `trace: 'off'` ou teste verde                  | Config `trace: 'on-first-retry'`                         |
| Lighthouse Perf < 90 após mudança                       | Asset novo pesado                              | `npm run build -- --mode=analyze`                        |
| Horizon não processa filas                              | Worker morto                                   | `php artisan horizon:terminate` + restart                |
| Mailpit não recebe emails dev                           | Config `MAIL_MAILER` errada                    | `.env` MAIL_MAILER=smtp MAIL_HOST=mailpit MAIL_PORT=1025 |
| pgAdmin não conecta                                     | Host errado (usar `postgres` dentro de docker) | Host `postgres`, porta `5432`                            |

### 8.1 Laravel catch-all — referência rápida

`routes/portal.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// Webhooks e rotas específicas vêm antes

// Catch-all para SPA — última rota
Route::fallback(function () {
  return view('spa');
});

// OU, mais estrito (apenas fora de /api e /admin):
Route::get('/{any}', fn () => view('spa'))
  ->where('any', '^(?!api|admin|horizon|pulse|sanctum).*$');
```

---

## 9. Checklist pré-release

Antes de aplicar tag `v1.x.y` e deploy produção:

### 9.1 Código

- [ ] Todos os testes verdes (`npm run quality` + `npm run test:e2e`)
- [ ] Cobertura ≥ thresholds (não regrediu)
- [ ] Zero violações a11y AA nas rotas tocadas
- [ ] Lighthouse Perf ≥ 90 em rotas-chave
- [ ] Bundle inicial ≤ 250 KB gzip
- [ ] `codegen:check` verde
- [ ] `CHANGELOG.md` atualizado
- [ ] `docs/api/api-CHANGELOG.md` atualizado (se contrato mudou)

### 9.2 Infra

- [ ] Backend prerequisites do planejamento entregues (CORS, Sanctum, spa.blade, catch-all, Auth endpoints) — ver [14-OPEN-QUESTIONS](./14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md)
- [ ] Migrations rodando em ambiente de teste (`php artisan migrate --pretend`)
- [ ] Filas Horizon estáveis (sem jobs stuck)
- [ ] Variáveis de ambiente staging/prod validadas

### 9.3 Segurança

- [ ] Sem console.log / debug code
- [ ] Sem secrets hardcoded
- [ ] Sentry DSN configurado (F7+)
- [ ] CSP revisado (se aplicável)

### 9.4 Documentação

- [ ] Runbook (este) atualizado com novos erros/soluções
- [ ] README do módulo atualizado
- [ ] ADR registrado se decisão nova

### 9.5 Go/No-Go

- [ ] Tech lead frontend: OK
- [ ] QA Lead: OK
- [ ] DevOps: janela reservada
- [ ] Product: comunicação pronta (se release tem nota)

### 9.6 Tag

```bash
git tag -a v1.2.3 -m "Release v1.2.3 — wizard etapas 4-5 + pagamento boleto"
git push origin v1.2.3
```

---

## 10. Monitoramento pós-deploy

### 10.1 Primeiras 30 min

- **Watchdog:** `/pulse` dashboard aberto
- **Logs backend:** `docker-compose logs -f php-fpm` + filtrar por `level=error`
- **Smoke staging** (se já subiu prod, smoke prod)
- **Sentry** (F7+) zerar contador de novos erros antes do deploy

### 10.2 Web Vitals (F7+)

Configurar `web-vitals`:

```ts
// src/telemetry/web-vitals.ts
import { onCLS, onFID, onLCP, onINP, onTTFB } from 'web-vitals';
import { api } from '@/api/client';

function report(metric: unknown) {
    api.post('/api/v1/telemetry/web-vitals', metric).catch(() => {
        // fire-and-forget
    });
}
onCLS(report);
onFID(report);
onLCP(report);
onINP(report);
onTTFB(report);
```

Dashboard Pulse/Grafana plota p75 por rota.

### 10.3 Correlação frontend-backend

- Frontend gera `X-Request-Id: <ulid>` em cada request
- Backend loga request_id em cada linha
- Suporte pede request_id ao usuário (via footer do erro) → dev busca no Loki/CloudWatch

### 10.4 Alertas sugeridos

| Métrica                | Threshold        | Canal           |
| ---------------------- | ---------------- | --------------- |
| 5xx rate (backend)     | > 1% em 5 min    | Slack #oncall   |
| 419 rate               | > 5% em 10 min   | Slack #oncall   |
| LCP p75                | > 3 s por 15 min | Slack #frontend |
| Console errors uniques | novo símbolo     | Sentry          |
| Build CI duração       | > 10 min         | Slack #devops   |
| Horizon jobs failed    | > 10/min         | Slack #oncall   |

---

## 11. Comandos úteis

### 11.1 Laravel

```bash
php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache
php artisan queue:restart
php artisan horizon:terminate
php artisan migrate --pretend
php artisan migrate:status
php artisan tinker
php artisan about                        # versão, env, drivers
```

### 11.2 SPA

```bash
npm run dev                               # Vite dev + HMR
npm run build                             # produção
npm run preview                           # serve build local

npm run codegen                           # gera types.gen.ts
npm run codegen:check                     # valida sem drift

npm run test                              # vitest run
npm run test:watch                        # vitest watch
npm run test -- --coverage
npm run test -- src/hooks/use-extrato     # filter

npm run test:e2e
npm run test:e2e -- --ui
npm run test:e2e:core
npm run smoke

npm run lint
npm run lint:fix
npm run typecheck
npm run format
npm run quality                           # lint+typecheck+codegen+test
```

### 11.3 Docker / Laradock

```bash
docker ps
docker-compose -f laradock/docker-compose.yml logs -f nginx
docker-compose -f laradock/docker-compose.yml logs -f php-fpm
docker-compose -f laradock/docker-compose.yml restart nginx php-fpm
docker-compose -f laradock/docker-compose.yml down
make up
make down
make bash          # entra em workspace
make fresh         # migrate:fresh --seed
```

### 11.4 Diagnóstico rápido

```bash
# Frontend está servindo?
curl -I http://localhost/

# API responde?
curl -I http://localhost/api/v1/health

# Database ok?
docker exec -it laradock-postgres-1 psql -U default -c 'SELECT 1'

# Redis ok?
docker exec -it laradock-redis-1 redis-cli PING

# Versões instaladas
cd resources/spa && npm ls --depth=0 | head -40
```

### 11.5 Limpeza de cache local

```bash
# Limpeza leve
rm -rf resources/spa/.vite resources/spa/dist

# Limpeza pesada (recriar node_modules)
rm -rf resources/spa/node_modules
cd resources/spa && npm ci

# Cache de Playwright
rm -rf ~/.cache/ms-playwright
npx playwright install --with-deps
```

---

## 12. Contatos e escalation

### 12.1 On-call

| Nível | Papel                   | Canal principal                 |
| ----- | ----------------------- | ------------------------------- |
| L1    | Dev Frontend de plantão | Slack #spa-oncall               |
| L2    | Tech Lead Frontend      | Slack #frontend-core + WhatsApp |
| L3    | CTO / Arquiteto         | WhatsApp                        |

### 12.2 Dependências externas

| Sistema                        | Contato                  | SLA               |
| ------------------------------ | ------------------------ | ----------------- |
| Gateway pagamento (Itaú)       | Suporte empresarial Itaú | horário comercial |
| Provedor e-mail (Postmark/SES) | conforme contrato        | 99.9%             |
| Hosting (Laravel Cloud / AWS)  | suporte do provedor      | conforme plano    |

### 12.3 Escalation matrix

1. Erro fresh em produção: L1 tenta rollback em 15 min
2. Não resolveu: escalar L2
3. Ainda: chamar L3
4. Comunicação externa (cliente): passa por Product

### 12.4 Post-mortem

Todo incidente Sev-1 ou Sev-2 gera post-mortem em `docs/postmortems/YYYY-MM-DD-titulo.md` com:

- Linha do tempo (timestamps)
- Impacto (usuários afetados, duração)
- Causa raiz (5 Whys)
- Ações corretivas (com owner + prazo)

---

## Anexo A — links cruzados

- [SAD](./05-FRONTEND-SAD.md)
- [Technical Design](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- [QA Strategy](./10-QA-TEST-STRATEGY.md)
- [Dev Setup](./11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md)
- [Roadmap](./13-FRONTEND-IMPLEMENTATION-ROADMAP.md)
- [Open Questions](./14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md)
- [Runbook Deploy (infra)](../devops/runbook-deploy.md)
- [Runbook Operations](../devops/runbook-operations.md)
- [Monitoring & Alerts](../devops/monitoring-alerts.md)
- [Security Operations](../devops/security-operations.md)

---

## Histórico

| Data       | Versão | Autor        | Mudança                                                 |
| ---------- | ------ | ------------ | ------------------------------------------------------- |
| 2026-04-18 | 1.0.0  | Agente QA/FE | Versão inicial — subida local, auth, debug, deploy, pós |
