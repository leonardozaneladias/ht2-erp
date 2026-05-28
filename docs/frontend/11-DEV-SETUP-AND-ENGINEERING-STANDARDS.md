---
title: 'Dev Setup e Padrões de Engenharia — Portal SPA React'
module: frontend
doc_type: dev-setup
version: 1.0.0
status: ativo
owner: tech-lead-frontend
audience: [dev-frontend, tech-lead, devops, new-joiner]
last_updated: 2026-04-18
related:
    - ./00-README-INDEX.md
    - ./05-FRONTEND-SAD.md
    - ./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md
    - ./10-QA-TEST-STRATEGY.md
    - ./12-RUNBOOK-FRONTEND.md
    - ./13-FRONTEND-IMPLEMENTATION-ROADMAP.md
    - ../devops/dev-setup.md
    - ../devops/engineering-standards.md
    - ../prd/PLANEJAMENTO_FRONTEND_REACT.md
    - ../../CLAUDE.md
---

# Dev Setup e Padrões de Engenharia — Portal SPA React

> **Escopo:** tudo que um desenvolvedor frontend precisa para sair do zero ao primeiro commit produtivo no SPA React do Portal ArtFinal. Complementa `docs/devops/dev-setup.md` (foco Laravel/infra) trazendo especificidades do SPA em `resources/spa/`.

---

## Sumário

1. [Pré-requisitos](#1-pré-requisitos)
2. [Setup local inicial](#2-setup-local-inicial)
3. [Dependências NPM do SPA](#3-dependências-npm-do-spa)
4. [Estrutura de pastas](#4-estrutura-de-pastas)
5. [Padrões de branch](#5-padrões-de-branch)
6. [Convenções de pastas e naming](#6-convenções-de-pastas-e-naming)
7. [Lint, typecheck e test](#7-lint-typecheck-e-test)
8. [Codegen OpenAPI](#8-codegen-openapi)
9. [Fluxo de CI](#9-fluxo-de-ci)
10. [Padrões de Hooks / Components / Stores](#10-padrões-de-hooks--components--stores)
11. [Padrões de documentação](#11-padrões-de-documentação)
12. [Anti-patterns proibidos](#12-anti-patterns-proibidos)
13. [Definition of Ready](#13-definition-of-ready)
14. [Definition of Done](#14-definition-of-done)
15. [Tooling: editor setup](#15-tooling-editor-setup)
16. [Onboarding — primeiros 2 dias](#16-onboarding--primeiros-2-dias)
17. [Anexos](#17-anexos)

---

## 1. Pré-requisitos

| Ferramenta   | Versão mínima | Como instalar (macOS)                        |
| ------------ | ------------- | -------------------------------------------- |
| **Node.js**  | 20.x LTS      | `brew install node@20` ou `nvm install 20`   |
| **npm**      | 10.x          | vem com Node 20                              |
| **PHP**      | 8.4           | `brew install php@8.4`                       |
| **Composer** | 2.7+          | `brew install composer`                      |
| **Docker**   | 4.x           | Docker Desktop                               |
| **Git**      | 2.40+         | `brew install git`                           |
| **make**     | qualquer      | vem com Xcode CLT (`xcode-select --install`) |
| **Laradock** | branch atual  | clonado como submódulo (ver INFRA.md)        |

Containers Laradock em uso: `nginx`, `php-fpm`, `workspace`, `postgres`, `redis`, `mailpit`, `pgadmin`.

**Linux/WSL2:** `apt install nodejs npm php8.4 composer git make docker.io`.

**Windows puro:** não suportado. Usar WSL2 Ubuntu 22.04+.

### 1.1 Verificar versões

```bash
node -v       # v20.x
npm -v        # 10.x
php -v        # 8.4.x
composer -V
docker -v
```

### 1.2 Hardware recomendado

- 16 GB RAM (mínimo 8 GB; SPA + Laradock consomem ~4 GB)
- SSD (Docker em HDD é sofrido)
- macOS: Apple Silicon preferido (containers nativos arm64)

---

## 2. Setup local inicial

Passo a passo para **primeira clonagem**:

```bash
# 1. Clonar o repositório
git clone git@github.com:artfinal/portalartfinal-v2.git
cd portalartfinal-v2

# 2. Subir Laradock (containers backend)
make up
# equivalente a:
#   cd laradock && cp env-example .env && docker-compose up -d nginx postgres redis workspace mailpit

# 3. Entrar no container workspace para comandos PHP
make bash
# dentro do container:
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link

# 4. Migrations + seeds
php artisan migrate --seed
exit  # sair do container

# 5. Instalar dependências do admin (Blade + Tailwind)
npm install

# 6. Instalar dependências do SPA React
cd resources/spa
npm install
cd ../..

# 7. Gerar tipos do OpenAPI
cd resources/spa
npm run codegen
cd ../..

# 8. Subir servidor SPA dev (em 2º terminal)
cd resources/spa
npm run dev   # Vite em http://localhost:5173 (HMR)

# 9. Em 3º terminal: servir aplicação
#    Laradock nginx já serve http://localhost — o shell SPA (spa.blade.php)
#    lê o manifest do Vite dev.

# 10. Validar
open http://localhost           # SPA shell
open http://localhost/horizon   # fila
open http://localhost:5050      # pgAdmin
open http://localhost:8125      # Mailpit
```

### 2.1 Variáveis de ambiente

`.env` da raiz (Laravel):

```bash
APP_URL=http://localhost
FRONTEND_URL=http://localhost:5173
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173
CORS_ALLOWED_ORIGINS=http://localhost,http://localhost:5173
```

`resources/spa/.env` (Vite):

```bash
VITE_API_URL=http://localhost
VITE_APP_ENV=development
VITE_SENTRY_DSN=       # F7+
```

### 2.2 Verificação de sanidade (smoke manual)

```bash
# 1) CSRF cookie
curl -i http://localhost/sanctum/csrf-cookie
# deve retornar 204 + Set-Cookie XSRF-TOKEN

# 2) Login
curl -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'X-Request-Id: smoke-test' \
  -c cookies.txt \
  -d '{"email":"testa@portalart.app","senha":"senha12345"}'

# 3) Me
curl -b cookies.txt http://localhost/api/v1/me
# deve retornar { data: { user: {...} } }

# 4) SPA carrega
curl -s http://localhost/portal/home | grep 'id="root"'
```

---

## 3. Dependências NPM do SPA

O `resources/spa/package.json` **é separado** do `package.json` da raiz (que serve Inspinia admin).

### 3.1 `dependencies`

```json
{
    "dependencies": {
        "react": "^19.0.0",
        "react-dom": "^19.0.0",
        "@tanstack/react-router": "^1.58.0",
        "@tanstack/react-query": "^5.51.0",
        "@tanstack/react-query-devtools": "^5.51.0",
        "zustand": "^5.0.0",
        "react-hook-form": "^7.52.0",
        "@hookform/resolvers": "^3.9.0",
        "zod": "^4.0.0",
        "axios": "^1.7.0",
        "tamagui": "^2.0.0",
        "@tamagui/core": "^2.0.0",
        "@tamagui/config": "^2.0.0",
        "@tamagui/lucide-icons": "^2.0.0",
        "ulid": "^2.3.0",
        "date-fns": "^4.0.0",
        "date-fns-tz": "^3.2.0"
    }
}
```

### 3.2 `devDependencies`

```json
{
    "devDependencies": {
        "typescript": "^5.5.0",
        "vite": "^7.0.0",
        "@vitejs/plugin-react": "^4.3.0",
        "@tanstack/router-vite-plugin": "^1.58.0",
        "@tanstack/router-devtools": "^1.58.0",
        "openapi-typescript": "^7.4.0",

        "vitest": "^2.0.0",
        "@vitest/coverage-v8": "^2.0.0",
        "@testing-library/react": "^16.0.0",
        "@testing-library/jest-dom": "^6.4.0",
        "@testing-library/user-event": "^14.5.0",
        "jsdom": "^24.1.0",
        "msw": "^2.3.0",
        "jest-axe": "^9.0.0",

        "@playwright/test": "^1.47.0",
        "@axe-core/playwright": "^4.9.0",
        "@lhci/cli": "^0.13.0",

        "eslint": "^10.0.0",
        "@typescript-eslint/eslint-plugin": "^8.0.0",
        "@typescript-eslint/parser": "^8.0.0",
        "eslint-plugin-react": "^7.35.0",
        "eslint-plugin-react-hooks": "^5.0.0",
        "eslint-plugin-jsx-a11y": "^6.10.0",
        "eslint-plugin-import": "^2.30.0",
        "eslint-config-prettier": "^9.1.0",

        "prettier": "^3.3.0"
    }
}
```

### 3.3 Scripts npm

```json
{
    "scripts": {
        "dev": "vite",
        "build": "tsc --noEmit && vite build",
        "preview": "vite preview",

        "codegen": "openapi-typescript ../../docs/api/openapi-skeleton.yaml -o src/api/types.gen.ts",
        "codegen:check": "npm run codegen && git diff --exit-code src/api/types.gen.ts",

        "lint": "eslint .",
        "lint:fix": "eslint . --fix",
        "typecheck": "tsc --noEmit",
        "format": "prettier --write \"src/**/*.{ts,tsx,css,md,json}\"",
        "format:check": "prettier --check \"src/**/*.{ts,tsx,css,md,json}\"",

        "test": "vitest run",
        "test:watch": "vitest",
        "test:coverage": "vitest run --coverage",
        "test:a11y": "vitest run --grep 'a11y'",

        "test:e2e": "playwright test",
        "test:e2e:ui": "playwright test --ui",
        "test:e2e:core": "playwright test --grep 'E2E-00[1346]'",

        "smoke": "playwright test tests/smoke --project=chromium-desktop --reporter=line",

        "lhci": "lhci autorun",

        "quality": "npm run lint && npm run typecheck && npm run codegen:check && npm run test"
    }
}
```

### 3.4 Por que não usar npm do admin?

O admin Inspinia tem Tailwind v4 + Alpine + plugins incompatíveis (Choices.js, Inputmask, SortableJS) — a árvore de deps confunde. O SPA tem build/HMR independentes, `vite.config.ts` próprio e lockfile próprio. Evita colisão de versões e acelera CI.

---

## 4. Estrutura de pastas

```
resources/spa/
├── package.json
├── package-lock.json
├── tsconfig.json
├── vite.config.ts
├── playwright.config.ts
├── vitest.config.ts
├── tamagui.config.ts
├── .eslintrc.cjs
├── .prettierrc
├── .gitignore
├── index.html                       ← entrypoint dev (Vite)
├── public/                          ← assets estáticos (favicon)
├── src/
│   ├── main.tsx                     ← boot: ReactDOM.createRoot + Providers
│   ├── app/
│   │   ├── providers.tsx            ← QueryClient, Tamagui, Router
│   │   ├── query-client.ts
│   │   └── router.ts                ← routeTree gerado + createRouter
│   ├── api/
│   │   ├── client.ts                ← Axios instance + 4 interceptors
│   │   ├── endpoints.ts             ← mapping path → fn (opcional)
│   │   ├── errors.ts                ← ApiError class + guard
│   │   └── types.gen.ts             ← gerado por openapi-typescript
│   ├── components/
│   │   ├── auth/
│   │   │   ├── LoginForm.tsx
│   │   │   └── RequireAuth.tsx
│   │   ├── financeiro/
│   │   ├── mesas/
│   │   ├── convites/
│   │   ├── ui/                      ← primitivos Tamagui customizados
│   │   └── layout/
│   │       ├── PortalLayout.tsx
│   │       └── PortalNav.tsx
│   ├── forms/
│   │   ├── login.schema.ts
│   │   ├── adesao-etapa-1.schema.ts
│   │   └── ...
│   ├── hooks/
│   │   ├── use-login.ts             ← useMutation
│   │   ├── use-me.ts                ← useQuery
│   │   ├── use-extrato.ts           ← useInfiniteQuery cursor
│   │   ├── use-mesas.ts
│   │   ├── use-hold-timer.ts        ← puro, testável isolado
│   │   └── ...
│   ├── lib/
│   │   ├── money.ts
│   │   ├── date.ts
│   │   ├── ulid.ts
│   │   ├── idempotency.ts
│   │   ├── cursor.ts
│   │   ├── slug.ts
│   │   └── errors.ts
│   ├── routes/                      ← TanStack Router file-based
│   │   ├── __root.tsx
│   │   ├── index.tsx                ← /
│   │   ├── login.tsx                ← /login
│   │   ├── portal/
│   │   │   ├── _layout.tsx          ← wrapping layout protegido
│   │   │   ├── home.tsx
│   │   │   ├── financeiro.tsx
│   │   │   ├── pagamento.$parcela_ulid.tsx
│   │   │   ├── convites.tsx
│   │   │   ├── mesas.tsx
│   │   │   ├── extras.tsx
│   │   │   ├── enquetes.tsx
│   │   │   ├── perfil.tsx
│   │   │   └── adesao.$step.tsx     ← wizard
│   │   └── rsvp.$token.tsx          ← público
│   ├── stores/
│   │   ├── auth-store.ts
│   │   ├── wizard-store.ts          ← sessionStorage persist
│   │   └── hold-store.ts
│   └── telemetry/
│       └── web-vitals.ts            ← F7+
└── tests/
    ├── setup.ts
    ├── mocks/
    │   ├── server.ts
    │   └── handlers.ts
    ├── fixtures/
    ├── utils/
    │   └── render-with-providers.tsx
    └── e2e/
        ├── pages/
        ├── fixtures/
        ├── setup/
        └── *.spec.ts
```

### 4.1 Princípios de organização

- **Uma feature por pasta em `components/`**: não misturar `financeiro` com `mesas`
- **Um hook por arquivo em `hooks/`**: barril (`index.ts`) opcional
- **Rotas espelham URL**: `/portal/pagamento/:parcela_ulid` → `routes/portal/pagamento.$parcela_ulid.tsx`
- **Stores pequenas**: cada uma resolve 1 problema
- **Libs puras**: sem import de React / TanStack

---

## 5. Padrões de branch

### 5.1 Branches protegidas

- `main` — produção; merge só via PR aprovado + CI verde
- `staging` — homologação; tracking automático de `main` em releases

### 5.2 Branches de trabalho

| Prefixo             | Uso                            | Exemplo                                  |
| ------------------- | ------------------------------ | ---------------------------------------- |
| `feature/paf-NN-*`  | Nova funcionalidade            | `feature/paf-42-wizard-etapa-3-pacote`   |
| `bugfix/paf-NN-*`   | Correção de bug existente      | `bugfix/paf-88-hold-timer-drift`         |
| `hotfix/paf-NN-*`   | Correção urgente em prod       | `hotfix/paf-102-login-loop-419`          |
| `chore/paf-NN-*`    | Infra, deps, config            | `chore/paf-10-upgrade-vite-7`            |
| `docs/paf-NN-*`     | Apenas documentação            | `docs/paf-5-planejamento-backend`        |
| `refactor/paf-NN-*` | Refactor sem mudança funcional | `refactor/paf-77-extract-use-hold-timer` |

`paf-NN` é o ID da issue no Plane (ex: `PAF-42`).

### 5.3 Conventional Commits

```
<tipo>(<escopo>): <descrição em PT-BR, imperativo, minúscula>
```

Tipos permitidos: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.

Escopos comuns no SPA: `auth`, `wizard`, `financeiro`, `pagamento`, `mesas`, `convites`, `rsvp`, `enquetes`, `ui`, `api`, `infra`.

**Exemplos:**

```
feat(wizard): implementar etapa 3 com seleção de pacote
fix(pagamento): tratar 409 idempotency-conflict com toast
refactor(mesas): extrair useHoldTimer para hook puro
docs(frontend): adicionar plano de testes E2E
test(auth): cobrir 401 em interceptor
chore(deps): upgrade TanStack Query para 5.51
```

Corpo opcional em PT-BR, rodapé com refs: `PAF-42`, `Closes PAF-42`.

### 5.4 Tamanho de PR

- Alvo: **≤ 400 linhas alteradas** (fora deps/generated)
- Se > 400: quebrar em 2 PRs com ordem explícita
- Sem commits `wip` no main

---

## 6. Convenções de pastas e naming

### 6.1 Arquivos

| Tipo              | Convenção                | Exemplo                                       |
| ----------------- | ------------------------ | --------------------------------------------- |
| Componente React  | `PascalCase.tsx`         | `LoginForm.tsx`, `WizardProgress.tsx`         |
| Rota (file-based) | kebab com `$` para param | `pagamento.$parcela_ulid.tsx`                 |
| Hook              | `use-<recurso>.ts`       | `use-extrato.ts`, `use-hold-timer.ts`         |
| Store             | `<nome>-store.ts`        | `auth-store.ts`, `wizard-store.ts`            |
| Schema Zod        | `<feature>.schema.ts`    | `login.schema.ts`, `emitir-convite.schema.ts` |
| Lib               | `kebab-case.ts`          | `money.ts`, `idempotency.ts`                  |
| Types gerados     | `types.gen.ts`           | (não editar manualmente)                      |
| Tipos locais      | `<feature>.types.ts`     | `mesas.types.ts`                              |
| Teste             | `<arquivo>.test.ts[x]`   | `money.test.ts`, `LoginForm.test.tsx`         |
| E2E               | `<fluxo>.spec.ts`        | `wizard-adesao.spec.ts`                       |
| Page Object       | `<Rota>Page.ts`          | `LoginPage.ts`, `MesasPage.ts`                |

### 6.2 Símbolos

| Tipo              | Convenção         | Exemplo                                             |
| ----------------- | ----------------- | --------------------------------------------------- |
| Componente        | `PascalCase`      | `export function LoginForm() {}`                    |
| Hook              | `useCamelCase`    | `export function useExtrato() {}`                   |
| Função            | `camelCase` verbo | `formatBRL`, `getOrCreateIdempotencyKey`            |
| Constante literal | `SCREAMING_SNAKE` | `IDEMPOTENCY_TTL_MS`                                |
| Constante objeto  | `camelCase`       | `defaultQueryOptions`                               |
| Tipo / Interface  | `PascalCase`      | `UserDTO`, `ExtratoItem`                            |
| Enum (evitar)     | `PascalCase`      | (preferir union literal `type Status = 'a' \| 'b'`) |

### 6.3 Tipos

- **Evitar `interface` pura** — preferir `type` para aliases de objeto (consistência)
- **Union literal** no lugar de enums quando possível
- **DTO do backend** — importado de `types.gen.ts`, nunca redefinido
- **ViewModel local** — prefixo `V` ou sufixo `ViewModel` para diferenciar

### 6.4 Imports

Ordem (ESLint plugin-import):

1. React / libs externas
2. Libs internas absolutas (`@/...`)
3. Relativos
4. CSS

Com separação por linha em branco.

---

## 7. Lint, typecheck e test

### 7.1 ESLint — regras-chave

```js
// .eslintrc.cjs (resumo)
module.exports = {
    parser: '@typescript-eslint/parser',
    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended-type-checked',
        'plugin:@typescript-eslint/stylistic-type-checked',
        'plugin:react/recommended',
        'plugin:react/jsx-runtime',
        'plugin:react-hooks/recommended',
        'plugin:jsx-a11y/recommended',
        'plugin:import/recommended',
        'plugin:import/typescript',
        'prettier',
    ],
    rules: {
        '@typescript-eslint/no-explicit-any': 'error',
        '@typescript-eslint/consistent-type-imports': 'error',
        '@typescript-eslint/no-floating-promises': 'error',
        'react-hooks/exhaustive-deps': 'error',
        'react/prop-types': 'off',
        'no-console': ['error', { allow: ['warn', 'error'] }],
        'import/order': [
            'error',
            {
                groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
                'newlines-between': 'always',
            },
        ],
    },
};
```

**Rodar:** `npm run lint` → `npm run lint:fix` para auto-fix.

### 7.2 TypeScript — strictness

```jsonc
// tsconfig.json
{
    "compilerOptions": {
        "target": "ES2022",
        "lib": ["ES2022", "DOM", "DOM.Iterable"],
        "module": "ESNext",
        "moduleResolution": "Bundler",
        "jsx": "react-jsx",
        "strict": true,
        "noUncheckedIndexedAccess": true,
        "noImplicitOverride": true,
        "noFallthroughCasesInSwitch": true,
        "exactOptionalPropertyTypes": true,
        "forceConsistentCasingInFileNames": true,
        "resolveJsonModule": true,
        "isolatedModules": true,
        "skipLibCheck": true,
        "noEmit": true,
        "baseUrl": ".",
        "paths": { "@/*": ["src/*"] },
    },
    "include": ["src", "tests"],
}
```

**Rodar:** `npm run typecheck`.

### 7.3 Prettier

```json
// .prettierrc
{
    "semi": true,
    "singleQuote": true,
    "trailingComma": "all",
    "printWidth": 100,
    "tabWidth": 2,
    "arrowParens": "always",
    "plugins": []
}
```

**Rodar:** `npm run format`.

### 7.4 Suite de qualidade

```bash
npm run quality
# = lint + typecheck + codegen:check + test
```

Roda em ~2 min local e é **gate de PR**.

### 7.5 Pré-commit (Husky)

`.husky/pre-commit`:

```bash
#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"
cd resources/spa
npx lint-staged
```

`package.json` (raiz do SPA):

```json
{
    "lint-staged": {
        "*.{ts,tsx}": ["eslint --fix", "prettier --write"],
        "*.{css,json,md}": ["prettier --write"]
    }
}
```

---

## 8. Codegen OpenAPI

### 8.1 Princípio

**types.gen.ts é fonte de verdade para contratos de API.** Nunca escrever tipos de DTO à mão se o endpoint existe no `openapi-skeleton.yaml`.

### 8.2 Comando

```bash
cd resources/spa
npm run codegen
# = openapi-typescript ../../docs/api/openapi-skeleton.yaml -o src/api/types.gen.ts
```

Gera `src/api/types.gen.ts` com:

- `type paths` — mapa `'/api/v1/me': { get: { responses: {...} } }`
- `type components` — schemas (`User`, `ExtratoItem`, `Convite`…)
- `type operations` — operationId → request/response

### 8.3 Consumo

```ts
// src/api/client.ts
import type { paths, components } from './types.gen';

type UserDTO = components['schemas']['User'];
type MeResponse = paths['/api/v1/me']['get']['responses']['200']['content']['application/json'];

export async function fetchMe(): Promise<UserDTO> {
    const r = await api.get<MeResponse>('/api/v1/me');
    return r.data.data.user;
}
```

### 8.4 `codegen:check` no CI

```bash
npm run codegen:check
# roda codegen + git diff --exit-code src/api/types.gen.ts
# falha se alguém alterou spec e esqueceu de regerar
```

### 8.5 Hook de pre-commit (opcional)

Se `openapi-skeleton.yaml` foi alterado no commit, rodar `codegen` automaticamente:

```bash
# .husky/pre-commit (trecho)
if git diff --cached --name-only | grep -q 'openapi-skeleton.yaml'; then
  cd resources/spa && npm run codegen && git add src/api/types.gen.ts
fi
```

### 8.6 Cuidados

- `types.gen.ts` é **gerado, não editar**
- Adicionar em `.gitattributes` como `linguist-generated=true`
- Excluir do coverage (§9 do doc de QA)
- Commit sempre que regerar

---

## 9. Fluxo de CI

### 9.1 Pipelines

```
┌─────────────┐
│ Pull Req.   │─┐
└─────────────┘ │
                ├─► Quality (lint + typecheck + codegen + unit + int)  [obrigatório]
                ├─► Build SPA                                            [obrigatório]
                ├─► E2E core (se label:critical)                         [obrigatório-condicional]
                └─► Lighthouse (se mudou rota)                           [opcional]

┌─────────────┐
│ Merge main  │─┐
└─────────────┘ │
                ├─► Build produção
                ├─► Deploy staging
                ├─► Smoke staging                                        [gate]
                ├─► E2E suite completa                                   [bloqueia se red]
                └─► Lighthouse CI                                        [bloqueia se red]

┌─────────────┐
│ Nightly 02h │─┐
└─────────────┘ │
                ├─► E2E 4 browsers
                ├─► A11y todas rotas
                └─► Report para Slack/Linear
```

### 9.2 GitHub Actions — exemplo mínimo

```yaml
# .github/workflows/spa-quality.yml
name: SPA Quality

on:
    pull_request:
        paths: ['resources/spa/**', 'docs/api/openapi-skeleton.yaml']
    push:
        branches: [main]

jobs:
    quality:
        runs-on: ubuntu-latest
        defaults: { run: { working-directory: resources/spa } }
        steps:
            - uses: actions/checkout@v4
            - uses: actions/setup-node@v4
              with:
                  node-version: '20'
                  cache: 'npm'
                  cache-dependency-path: resources/spa/package-lock.json
            - run: npm ci
            - run: npm run codegen:check
            - run: npm run lint
            - run: npm run typecheck
            - run: npm run test -- --coverage
            - uses: actions/upload-artifact@v4
              if: always()
              with:
                  name: coverage-${{ github.sha }}
                  path: resources/spa/coverage

    build:
        needs: quality
        runs-on: ubuntu-latest
        defaults: { run: { working-directory: resources/spa } }
        steps:
            - uses: actions/checkout@v4
            - uses: actions/setup-node@v4
              with: { node-version: '20', cache: 'npm' }
            - run: npm ci
            - run: npm run build
            - uses: actions/upload-artifact@v4
              with:
                  name: spa-dist-${{ github.sha }}
                  path: resources/spa/dist
```

### 9.3 Cache

- `node_modules` via `setup-node` cache
- Playwright browsers: `~/.cache/ms-playwright`
- Vendor Composer para jobs Laravel

### 9.4 Artifacts

| Artifact                  | Retenção | Quando         |
| ------------------------- | -------- | -------------- |
| `coverage-<sha>`          | 14 dias  | todo PR        |
| `spa-dist-<sha>`          | 7 dias   | todo PR + main |
| `playwright-report-<sha>` | 30 dias  | E2E falhou     |
| `lighthouse-report-<sha>` | 30 dias  | merge main     |

### 9.5 Secrets e variáveis

```
FRONTEND_URL=https://staging.portalart.app
VITE_API_URL=https://staging.portalart.app
E2E_BASE_URL=https://staging.portalart.app
E2E_DB_SEED_TOKEN=***
LHCI_GITHUB_APP_TOKEN=***
```

---

## 10. Padrões de Hooks / Components / Stores

### 10.1 Hook de query (TanStack)

```ts
// src/hooks/use-extrato.ts
import { useInfiniteQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import type { paths } from '@/api/types.gen';

type ExtratoResp = paths['/api/v1/me/extrato']['get']['responses']['200']['content']['application/json'];

export function useExtrato() {
    return useInfiniteQuery({
        queryKey: ['me', 'extrato'],
        queryFn: async ({ pageParam }) => {
            const r = await api.get<ExtratoResp>('/api/v1/me/extrato', {
                params: { cursor: pageParam, limit: 20 },
            });
            return r.data;
        },
        initialPageParam: null as string | null,
        getNextPageParam: (last) => last.meta.cursor.next_cursor,
        staleTime: 30_000,
    });
}
```

**Regras:**

- `queryKey` tipado como tuple hierárquica (`['me', 'extrato']`, `['mesas', ulid, 'mapa']`)
- `queryFn` sempre async, sempre via `api` (Axios)
- `staleTime` explícito — nunca usar default sem pensar
- `getNextPageParam` retorna `null` quando acabou (bloqueia fetch)

### 10.2 Hook de mutation

```ts
// src/hooks/use-confirmar-assento.ts
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/api/client';
import { getOrCreateIdempotencyKey } from '@/lib/idempotency';

export function useConfirmarAssento(eventoUlid: string) {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: { hold_id: string; cadeira_id: string }) => {
            const idempotencyKey = getOrCreateIdempotencyKey(`confirmar:${payload.hold_id}`);
            const r = await api.post(`/api/v1/eventos/${eventoUlid}/mesas/confirmar`, payload, {
                headers: { 'X-Idempotency-Key': idempotencyKey },
            });
            return r.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['mesas', eventoUlid] });
            qc.invalidateQueries({ queryKey: ['me', 'convites'] });
        },
    });
}
```

**Regras:**

- Mutation **sempre** com `onSuccess` que invalida queries relacionadas
- Idempotency key **derivada do contexto semântico** (hold_id), não aleatória por clique
- Retornar tipo do DTO do servidor

### 10.3 Componente — consumir hook

```tsx
// src/components/mesas/ConfirmarButton.tsx
import { useConfirmarAssento } from '@/hooks/use-confirmar-assento';
import { Button } from 'tamagui';

export function ConfirmarButton({
    eventoUlid,
    holdId,
    cadeiraId,
}: {
    eventoUlid: string;
    holdId: string;
    cadeiraId: string;
}) {
    const { mutate, isPending, error } = useConfirmarAssento(eventoUlid);
    return (
        <>
            <Button disabled={isPending} onPress={() => mutate({ hold_id: holdId, cadeira_id: cadeiraId })}>
                {isPending ? 'Confirmando…' : 'Confirmar assento'}
            </Button>
            {error && <div role="alert">{error.message}</div>}
        </>
    );
}
```

**Regras:**

- Componente **nunca** faz `fetch` direto — usa hook
- Componente **nunca** tem lógica de negócio — delega
- Estado de loading/error **sempre** vem do hook

### 10.4 Schema + RHF

```tsx
// src/components/auth/LoginForm.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { loginSchema, type LoginInput } from '@/forms/login.schema';
import { useLogin } from '@/hooks/use-login';

export function LoginForm({ onSuccess }: { onSuccess: () => void }) {
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<LoginInput>({
        resolver: zodResolver(loginSchema),
    });
    const { mutateAsync, error } = useLogin();

    return (
        <form
            onSubmit={handleSubmit(async (data) => {
                await mutateAsync(data);
                onSuccess();
            })}
        >
            <label htmlFor="email">E-mail</label>
            <input id="email" {...register('email')} aria-invalid={!!errors.email} />
            {errors.email && <span role="alert">{errors.email.message}</span>}

            <label htmlFor="senha">Senha</label>
            <input id="senha" type="password" {...register('senha')} aria-invalid={!!errors.senha} />
            {errors.senha && <span role="alert">{errors.senha.message}</span>}

            <button type="submit" disabled={isSubmitting}>
                Entrar
            </button>
            {error && <div role="alert">{error.message}</div>}
        </form>
    );
}
```

### 10.5 Store Zustand

```ts
// src/stores/auth-store.ts
import { create } from 'zustand';
import type { components } from '@/api/types.gen';

type User = components['schemas']['User'];

type AuthState = {
    user: User | null;
    isAuthenticated: boolean;
    login: (user: User) => void;
    logout: () => void;
};

export const useAuthStore = create<AuthState>((set) => ({
    user: null,
    isAuthenticated: false,
    login: (user) => set({ user, isAuthenticated: true }),
    logout: () => set({ user: null, isAuthenticated: false }),
}));

// Selector para performance
export const selectUser = (s: AuthState) => s.user;
export const selectIsAuthenticated = (s: AuthState) => s.isAuthenticated;
```

**Regras:**

- Estado **mínimo**; derivações via selectors
- **Nunca** servir dados do servidor em store — isso é TanStack Query
- Persist só quando necessário (wizard → `sessionStorage`)
- Selectors exportados quando hooks consomem fatias

---

## 11. Padrões de documentação

### 11.1 JSDoc

Somente em **APIs públicas de libs** (`src/lib/*`):

```ts
/**
 * Converte centavos em string BRL formatada.
 * @param cents — valor em centavos (inteiro ≥ 0)
 * @returns string no formato "R$ 1.500,99"
 * @throws se cents for negativo
 */
export function formatBRL(cents: number): string {
    // ...
}
```

**Não usar JSDoc em:**

- Componentes React (o nome + props já documentam)
- Hooks (idem)
- Funções privadas (mover para comentário inline se necessário)

### 11.2 Comentários inline

- Apenas para explicar **"por quê"** quando não óbvio
- Proibido comentar **"o quê"** (o código já diz)
- Linkar ADR quando aplicável: `// Ver ADR-0007: hold timer no servidor`

### 11.3 README por módulo grande

Opcional. Se a pasta `components/mesas/` tem > 10 arquivos, vale um `README.md` com:

- Diagrama de dependência (mermaid)
- Estados principais (disponível, em hold, confirmado)
- Referências ao technical design

### 11.4 ADR (Architecture Decision Records)

Decisões **não-triviais** (escolha de lib, padrão novo) viram ADR em `docs/frontend/06-ADR/`:

```
NNNN-titulo-curto.md
```

Template em `.agents/skills/adr-skill/`. Cross-ref obrigatório a partir do doc de arquitetura.

---

## 12. Anti-patterns proibidos

> Tudo listado aqui é **rejeitável em code review**. Sem exceções.

### 12.1 Rede e dados

```
❌ fetch()                              — usar api (Axios instance)
❌ axios.create() em cada hook          — usar api singleton
❌ useState para server state           — usar TanStack Query
❌ useEffect para buscar dados          — usar useQuery
❌ Múltiplas instâncias de QueryClient  — singleton global em providers
❌ localStorage para sessão/idempotency — usar sessionStorage
❌ localStorage.setItem('token', ...)   — Sanctum web usa cookie
❌ Cache in-memory ad-hoc               — usar TanStack Query cache
```

### 12.2 Tipos

```
❌ any                                  — usar unknown + narrow, ou tipo gerado
❌ @ts-ignore / @ts-expect-error        — corrigir o tipo
❌ Tipos manuais para DTO               — usar types.gen.ts
❌ as Type                              — usar guards quando inseguro
❌ interface para alias de objeto       — usar type
```

### 12.3 Paginação

```
❌ ?page=N&per_page=K (offset)          — usar cursor
❌ Offset pagination no frontend        — useInfiniteQuery com cursor
❌ Acumular dados em useState           — TanStack cache gerencia
```

### 12.4 Identidade

```
❌ BIGINT incremental em URL            — usar ULID
❌ Expor ID interno em URL              — usar ULID público
```

### 12.5 Valores monetários

```
❌ Number como BRL                      — centavos inteiros
❌ parseFloat('1.500,99')               — usar parseToCents (lib/money)
❌ (cents / 100).toFixed(2)             — usar formatBRL
```

### 12.6 Hold e idempotência

```
❌ setTimeout(5*60*1000) local-only     — usar hold_expires_at do servidor + tick
❌ Math.random() como idempotency key   — usar getOrCreateIdempotencyKey(context)
❌ Key reutilizada entre contextos      — uma key por ação semântica
❌ Key em localStorage                  — sessionStorage (TTL de aba)
```

### 12.7 Roteamento

```
❌ react-router                         — TanStack Router é o padrão
❌ window.location.href = ...           — router.navigate()
❌ páginas Blade no portal              — tudo via SPA (exceto /horizon, /pulse, admin)
❌ Múltiplos entrypoints Vite SPA       — só main.tsx
```

### 12.8 Componentes

```
❌ Componente > 300 linhas              — quebrar em filhos
❌ Props > 10 entradas                  — agrupar em objeto ou repensar
❌ Lógica de negócio em render          — mover para hook
❌ Efeito sem dependency array          — exhaustive-deps obrigatório
❌ Chamar hook condicionalmente         — Rules of Hooks
```

### 12.9 Design system

```
❌ Tamagui + shadcn juntos              — Tamagui é o escolhido (ver ADR)
❌ CSS custom (arquivos .css soltos)    — tokens Tamagui
❌ inline style={{...}}                 — usar props Tamagui (ou className semântico)
❌ !important no CSS                    — nunca
```

### 12.10 Estado e stores

```
❌ Zustand para server state            — TanStack Query
❌ Persist full store em localStorage   — só o necessário; preferir sessionStorage
❌ Store com 50+ campos                 — quebrar em stores menores
❌ Selector sem memoização (objeto)     — usar shallow ou selector por campo
```

### 12.11 Testes

```
❌ Teste que passa sem rodar o código   — sem asserção = sem valor
❌ Mockar store próprio                 — testar o real
❌ Mockar TanStack Query                — MSW intercepta rede
❌ sleep(5000) em teste                 — waitFor
❌ Teste dependendo de teste anterior   — isolamento obrigatório
```

### 12.12 Miscelânea

```
❌ console.log em produção              — remover antes de commit
❌ Comentário TODO sem ticket           — criar issue no Plane
❌ Código comentado                     — apagar; git tem histórico
❌ Variável em inglês de negócio        — $formando, $parcela, não $student/$installment
❌ Commit sem Conventional Commits      — revisor rejeita
```

---

## 13. Definition of Ready

Uma história **só entra em sprint** quando:

- [ ] **AC (acceptance criteria)** claro em PT-BR, em formato Given/When/Then ou checklist
- [ ] **Dependências de backend** mapeadas e **entregues** (ou com owner e data)
- [ ] **Design** disponível (Figma ou spec textual com fallback)
- [ ] **Endpoints** referenciados com operationId ou path
- [ ] **Tipos** em `types.gen.ts` atualizados se endpoint novo (ou tarefa de codegen na mesma história)
- [ ] **Estimativa** em Story Points
- [ ] **Impacto** em outros módulos identificado
- [ ] **Critério de teste** esboçado (E2E, a11y, perf se aplica)

Se algum item falta, o item volta para grooming.

---

## 14. Definition of Done

Uma história **só fecha** quando:

- [ ] Código **merged em main**
- [ ] **Unit + integration** passando, cobertura sem regressão > 2 p.p.
- [ ] **E2E happy path** passando (se feature crítica — wizard, pagamento, mesas, auth)
- [ ] **TypeScript** sem warnings
- [ ] **ESLint + Prettier** limpos
- [ ] **Codegen** em dia
- [ ] **A11y AA** sem violações nas páginas tocadas
- [ ] **Lighthouse** mantido (se rota pública ou mudança global)
- [ ] **Documentação** atualizada: ADR se decisão nova, module.md se arquitetura mudou
- [ ] **Revisão aprovada** por pelo menos 1 par
- [ ] **Issue no Plane** marcada como `Done` com evidências (link PR + screenshot)
- [ ] **Changelog** atualizado em `docs/api/api-CHANGELOG.md` se contrato mudou

---

## 15. Tooling: editor setup

### 15.1 VS Code (recomendado)

Extensões **obrigatórias**:

- `dbaeumer.vscode-eslint`
- `esbenp.prettier-vscode`
- `bradlc.vscode-tailwindcss`
- `yoavbls.pretty-ts-errors`
- `usernamehw.errorlens`
- `tanstack.router-tools` (TanStack Router devtools)
- `ms-playwright.playwright`

Extensões **sugeridas**:

- `streetsidesoftware.code-spell-checker` + dicionário pt-BR
- `github.copilot` ou `sourcegraph.cody` (opcional)
- `ms-azuretools.vscode-docker`

### 15.2 `.vscode/settings.json`

```jsonc
{
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.codeActionsOnSave": {
        "source.fixAll.eslint": "explicit",
        "source.organizeImports": "explicit",
    },
    "eslint.workingDirectories": ["resources/spa"],
    "typescript.tsdk": "resources/spa/node_modules/typescript/lib",
    "typescript.enablePromptUseWorkspaceTsdk": true,
    "tailwindCSS.experimental.classRegex": [["cva\\(([^)]*)\\)", "[\"'`]([^\"'`]*).*?[\"'`]"]],
    "files.exclude": { "**/node_modules": true, "**/dist": true },
}
```

### 15.3 `.vscode/extensions.json`

```json
{
    "recommendations": [
        "dbaeumer.vscode-eslint",
        "esbenp.prettier-vscode",
        "bradlc.vscode-tailwindcss",
        "yoavbls.pretty-ts-errors",
        "usernamehw.errorlens",
        "tanstack.router-tools",
        "ms-playwright.playwright"
    ]
}
```

### 15.4 `.editorconfig` (raiz do repo)

```ini
root = true

[*]
end_of_line = lf
insert_final_newline = true
charset = utf-8
indent_style = space
indent_size = 2
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false
```

### 15.5 Outros editores

- **JetBrains WebStorm** — ESLint + Prettier nativos; habilitar "Run eslint --fix on save"
- **Neovim** — LSP: `typescript-tools.nvim`, `null-ls.nvim` com eslint_d + prettier
- **Zed** — suporte nativo

---

## 16. Onboarding — primeiros 2 dias

### Dia 1 — setup

1. **Manhã**
    - Clonar repo, ler CLAUDE.md
    - Ler 00-README-INDEX do frontend
    - Seguir `docs/frontend/11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md` §2 (este arquivo)
    - Validar smoke manual (§2.2)

2. **Tarde**
    - Ler SAD (`05-FRONTEND-SAD.md`)
    - Ler Technical Design dos módulos críticos (`09-*`)
    - Rodar `npm run dev` + abrir `/login` e fazer login manual
    - Abrir um componente pequeno (`LoginForm`) e entender: RHF → Zod → hook mutation → Axios → MSW em teste

### Dia 2 — primeira contribuição

1. **Manhã**
    - Escolher issue `good-first-issue` no Plane
    - Criar branch `feature/paf-NN-...`
    - Escrever teste falho primeiro (TDD leve)

2. **Tarde**
    - Implementar
    - Rodar `npm run quality`
    - Abrir PR; self-review

### Materiais

- Slack canal: `#spa-frontend`
- Office hours tech lead: ter/qui 14h-15h
- Pair programming opcional primeira semana

---

## 17. Anexos

### 17.1 Checklist "primeiro PR"

- [ ] Branch nomeada `feature/paf-NN-descrição`
- [ ] Commits seguem Conventional Commits PT-BR
- [ ] `npm run quality` passa local
- [ ] PR tem descrição com "o que" + "por quê" + "como testar"
- [ ] Screenshots/GIFs se mudou UI
- [ ] Link para issue do Plane
- [ ] Labels corretos (`spa`, `auth`/`wizard`/..., `critical` se aplicável)
- [ ] Revisor designado

### 17.2 Checklist "adicionar novo endpoint"

1. [ ] Atualizar `docs/api/openapi-skeleton.yaml`
2. [ ] Backend implementa (request → response → teste)
3. [ ] `cd resources/spa && npm run codegen`
4. [ ] Commitar `types.gen.ts`
5. [ ] Criar/ajustar hook TanStack Query
6. [ ] Teste integration com MSW
7. [ ] Consumir no componente
8. [ ] Atualizar `docs/api/api-CHANGELOG.md`

### 17.3 Comandos diários mais usados

```bash
# Dev loop
cd resources/spa && npm run dev

# Antes de commit
npm run quality

# Regerar tipos
npm run codegen

# Teste em watch
npm run test:watch

# E2E interativo
npm run test:e2e:ui

# Lint fix
npm run lint:fix

# Limpar cache
rm -rf node_modules dist .vite && npm ci
```

### 17.4 Links úteis

- [SAD](./05-FRONTEND-SAD.md)
- [Technical Design](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- [QA Strategy](./10-QA-TEST-STRATEGY.md)
- [Runbook](./12-RUNBOOK-FRONTEND.md)
- [Roadmap](./13-FRONTEND-IMPLEMENTATION-ROADMAP.md)
- [Open Questions](./14-OPEN-QUESTIONS-AND-BACKEND-BLOCKERS.md)
- [Planejamento FE](../prd/PLANEJAMENTO_FRONTEND_REACT.md)
- [API contract](../api/api-contract.md)
- [OpenAPI skeleton](../api/openapi-skeleton.yaml)

### 17.5 Glossário

| Termo                 | Definição                                                                         |
| --------------------- | --------------------------------------------------------------------------------- |
| **SPA**               | Single Page Application — React 19 em `resources/spa/`                            |
| **Shell**             | `spa.blade.php` — única página Blade do portal, hospeda o bundle Vite             |
| **Catch-all**         | Rota Laravel `/{any}` que sempre devolve o shell SPA (fora `/api/*` e reservadas) |
| **Codegen**           | Geração automática de `types.gen.ts` a partir de `openapi-skeleton.yaml`          |
| **Cursor pagination** | `next_cursor` opaque; sem page/offset                                             |
| **Idempotency key**   | UUID v7 derivado do contexto semântico; enviado em `X-Idempotency-Key`            |
| **Hold**              | Reserva temporária de assento no servidor (TTL 5 min) durante a escolha           |
| **ULID**              | Identificador 26 chars, monotônico, expõe em URLs (no lugar de BIGINT)            |

### 17.6 Histórico

| Data       | Versão | Autor        | Mudança                                                 |
| ---------- | ------ | ------------ | ------------------------------------------------------- |
| 2026-04-18 | 1.0.0  | Agente QA/FE | Versão inicial — setup, padrões, anti-patterns, DoR/DoD |
