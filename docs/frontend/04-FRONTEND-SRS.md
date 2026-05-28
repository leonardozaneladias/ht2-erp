---
title: SRS — Software Requirements Specification (Frontend SPA React)
version: 1.0.0
date: 2026-04-18
status: draft
owner: Agente UX/IA/Requisitos Técnicos
target: Frontend SPA React (Portal do Formando)
related:
    - ./03-UX-FLOWS-IA-SCREENS.md
    - ./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md
    - ./06-ADR/ADR-006-polling-5s-mapa-mesas-mvp.md
    - ../api/api-contract.md
    - ../api/error-envelope.md
    - ../api/api-conventions.md
    - ../prd/PLANEJAMENTO_FRONTEND_REACT.md
legend:
    - '✅ confirmado — existe no PRD/API/ADR'
    - '💡 inferido — decisão derivada dos princípios técnicos'
    - '❓ pendente — precisa validação com stakeholder'
id_conventions:
    - 'RF-XXX: Requisito Funcional'
    - 'RNF-XXX: Requisito Não-Funcional'
    - 'REG-XXX: Regra'
    - 'CA-XXX: Critério de Aceite'
---

# SRS — Software Requirements Specification (Frontend SPA React)

> Especificação funcional e não-funcional do SPA React (Portal do Formando). Acompanha o documento de UX/IA [`03-UX-FLOWS-IA-SCREENS.md`](./03-UX-FLOWS-IA-SCREENS.md).
>
> Cada requisito é atômico, rastreável e testável. Nada de fetch nativo, offset, `any`, shadcn ou Blade neste documento.

---

## §0 — Escopo, objetivos e restrições

### 0.1 Escopo

O SPA React (pacote em `resources/spa/`) consome **exclusivamente** a API `/api/v1` do backend Laravel.

Fora do escopo:

- Admin Blade/Livewire (vive em `app/Http/Controllers/Admin` + `resources/views/admin`).
- API externa (gateways Itaú, Docusign) — já encapsulada pelo backend.
- App mobile React Native (versão futura F8).

### 0.2 Objetivos

- Oferecer ao formando um Portal web rápido, acessível, 100% PT-BR, e pronto para mobile-first.
- Reutilizar toda a camada de lógica de negócio já exposta em `/api/v1`.
- Ser **codebase base** para o app mobile (mesmos tipos, mesma arquitetura, mesma stack Tamagui).

### 0.3 Restrições não negociáveis (do PLANEJAMENTO_FRONTEND_REACT)

| #   | Restrição                                                         | Fonte |
| --- | ----------------------------------------------------------------- | ----- |
| 1   | SPA React puro, exceto `spa.blade.php` como shell                 | ✅    |
| 2   | API-first `/api/v1` — nunca acessar banco direto                  | ✅    |
| 3   | TypeScript `strict: true`, `noUncheckedIndexedAccess`, zero `any` | ✅    |
| 4   | Sanctum stateful (cookie) com `withCredentials: true`             | ✅    |
| 5   | 100% PT-BR                                                        | ✅    |
| 6   | ULID em rotas — nunca BIGINT                                      | ✅    |
| 7   | Idempotência em seating e pagamentos via `X-Idempotency-Key`      | ✅    |
| 8   | Hold timer reconciliado com `hold_expires_at` servidor            | ✅    |
| 9   | Cursor pagination — nunca offset                                  | ✅    |
| 10  | openapi-typescript em CI                                          | ✅    |

---

## §1 — Requisitos Funcionais (RF)

> Cada RF tem: ID, nome, descrição, inputs, outputs, pré-condição, pós-condição, erros possíveis, referências e critérios de aceite.

---

### RF-001 — Autenticar via Sanctum cookie

- **Descrição:** permitir que o formando autentique usando email e senha, com sessão via cookie `laravel_session` emitido por Sanctum.
- **Inputs:**
    - `email: string` (formato email)
    - `password: string` (min 8)
- **Outputs:**
    - Cookie de sessão + cookie XSRF-TOKEN.
    - Dados do formando autenticado (via `GET /me`).
- **Pré-condição:** usuário não autenticado.
- **Pós-condição:** `authStore.user` populado; rota atual redireciona para `/portal/home` ou para `?redirect=` da query string.
- **Erros possíveis:**
    - `401 Unauthenticated` → inline `password.setError('Credenciais inválidas')`.
    - `422 ValidationError` → mapear `details.fields` → RHF.
    - `429 RateLimitExceeded` → banner "Tente em Xs"; respeitar `Retry-After`.
- **Referências:** `POST /auth/login` ✅, `GET /me` ✅, `GET /sanctum/csrf-cookie` ✅.

---

### RF-002 — Armazenar estado de autenticação

- **Descrição:** manter o formando autenticado em memória (Zustand) e sincronizar com o backend via `GET /me`.
- **Inputs:** resposta de `/me`.
- **Outputs:** `authStore` com `{user, status: 'authenticated'|'unauthenticated'|'loading'}`.
- **Pré-condição:** —
- **Pós-condição:** guards e hooks têm acesso síncrono ao estado.
- **Erros:** 401 dispara `authStore.clear()` + redirect `/login`.
- **Observações:** não persistir em `localStorage` — estado vem sempre do cookie + `/me` no boot. 💡

---

### RF-003 — Proteger rotas via TanStack Router guard

- **Descrição:** todas as rotas `/portal/*` devem ser guardadas; sem sessão válida → `/login?redirect=<rota>`.
- **Inputs:** rota requisitada + estado `authStore`.
- **Outputs:** renderização permitida ou redirect.
- **Pré-condição:** `authStore.status` decidido (não `loading`).
- **Pós-condição:** rota protegida só renderiza para autenticado.
- **Referências:** `beforeLoad` do TanStack Router v1 (file-based). ✅

---

### RF-004 — Wizard adesão persistido em sessionStorage

- **Descrição:** rascunho de cada etapa persistido em `sessionStorage` sob `adesao:draft`.
- **Inputs:** dados de cada etapa validada.
- **Outputs:** objeto JSON serializado atualizado a cada `next()`.
- **Pré-condição:** formando autenticado + em `/portal/adesao/$step`.
- **Pós-condição:** refresh mantém dados; conclusão limpa draft.
- **Erros:** `SecurityError` de sessionStorage desabilitado → fallback em memória + toast "sem persistência". 💡

---

### RF-005 — Validação por etapa com Zod

- **Descrição:** cada etapa tem schema Zod nomeado; RHF consome via `zodResolver`.
- **Inputs:** dados do form.
- **Outputs:** `data` tipado ou `errors` por campo.
- **Schemas-chave:**
    - `adesaoE1Schema` — cpf (checksum), telefone (DDD+9), nomeCompleto (min 3).
    - `adesaoE2Schema` — turmaUlid (ULID).
    - `adesaoE3Schema` — pacoteUlid (ULID).
    - `adesaoE4Schema` — numeroParcelas (int 1..24).
    - `adesaoE5Schema` — ack boolean.
    - `adesaoE6Schema` — termosAceitos boolean.
    - `adesaoE7Schema` — metodo enum `boleto|pix|cartao`.

---

### RF-006 — Listagem financeiro cursor-paginada

- **Descrição:** consumir `GET /me/extrato` com cursor e renderizar lista infinita.
- **Inputs:** cursor opcional.
- **Outputs:** `{items, next_cursor}`.
- **Hook:** `useInfiniteQuery` com `getNextPageParam: lp => lp.next_cursor ?? undefined`. ✅
- **Proibido:** `page`, `offset`, `perPage`. ✅

---

### RF-007 — Pagamento boleto/PIX/cartão com idempotência

- **Descrição:** criar intent de pagamento via `POST /pagamentos/intents`.
- **Inputs:** `{parcela_ulid, metodo, dados_metodo?}`.
- **Outputs:** `{pagamento_ulid, metadados}` (ex: QR code, linha digitável).
- **Header obrigatório:** `X-Idempotency-Key` (UUID v4 armazenado em `sessionStorage` sob `pagamento:{parcela_ulid}:key`).
- **Erros:**
    - 409 `IdempotencyConflict` → purgar key + regenerar + retry 1x.
    - 422 `ValidationError` → inline por método.
    - 502 `GatewayIndisponivel` → toast + CTA "tentar novamente".

---

### RF-008 — Polling status pagamento

- **Descrição:** `GET /pagamentos/{ulid}` a cada 5s enquanto `status === 'aguardando_pagamento'`.
- **Inputs:** ulid do pagamento.
- **Outputs:** estado mais recente.
- **Regras:**
    - `refetchInterval`: `(query) => query.state.data?.status === 'aguardando_pagamento' ? 5000 : false`. ✅
    - Parar após 30 min de polling ativo; mostrar CTA manual. 💡
    - Ao status final, invalidar `['me','extrato']`. ✅

---

### RF-009 — Emitir convite individual

- **Descrição:** criar um convite único via `POST /eventos/{ulid}/convites`.
- **Inputs:** `{nome_convidado, email?, telefone?}`.
- **Outputs:** `{convite_ulid, token, rsvp_url}`.
- **Erros:** 409 `CotaEsgotada` → toast warning + bloquear form.

---

### RF-010 — Emitir convite em lote com progresso

- **Descrição:** criar múltiplos convites via `POST /eventos/{ulid}/convites/lotes` (idempotente).
- **Inputs:** array `[{nome, email?, telefone?}]`.
- **Outputs:** `{batch_ulid, status: 'processando'}` + polling.
- **Header:** `X-Idempotency-Key` salvo em `sessionStorage`. ✅
- **Polling:** `GET /eventos/{ulid}/convites/lotes/{batch_ulid}` a cada 3s. ❓ (confirmar endpoint).

---

### RF-011 — RSVP público via token mágico

- **Descrição:** permitir que convidado externo acesse `/rsvp/$token` e responda.
- **Inputs:** `token` da URL + resposta (`confirmado`|`recusado`).
- **Outputs:** confirmação persistida.
- **Pré-condição:** token válido (GET retorna 200).
- **Restrições:** rota pública, não usa `authStore`, shell mínimo (sem AppShell). ✅
- **Erros:** 404 `NotFound` / 410 `Gone` → tela amigável.

---

### RF-012 — Mapa de mesas interativo com hold 5min

- **Descrição:** renderizar mapa do evento e permitir seleção de assento com hold temporário.
- **Inputs:** `GET /eventos/{ulid}/mesas/mapa`.
- **Outputs:** SVG/canvas + estado por assento.
- **Interação:** clique em assento livre → `POST .../reservas` (idempotente) → hold ativo.
- **Refetch mapa:** 5s enquanto hold ativo. ✅
- **Referências:** [ADR-006](./06-ADR/ADR-006-polling-5s-mapa-mesas-mvp.md).

---

### RF-013 — Hold timer reconciliado com `hold_expires_at`

- **Descrição:** exibir contagem regressiva usando offset servidor−cliente para evitar drift.
- **Inputs:** `hold_expires_at` (ISO) + `server_time` (da resposta ou header `Date`).
- **Outputs:** contador visível (mm:ss).
- **Regras:**
    - `offset = server_time - Date.now()` calculado na criação do hold.
    - `restante = hold_expires_at - (Date.now() + offset)`.
    - Aos 0s: disparar `HoldExpirado` local + reset da `holdStore`.
    - Refetch do mapa a cada 5s valida coerência. ✅

---

### RF-014 — Confirmar, cancelar ou trocar assento

- **Descrição:** operações CRUD sobre a reserva com hold ativo.
- **Endpoints:**
    - `POST /eventos/{ulid}/mesas/reservas/{ulid}/confirmar` — confirma.
    - `DELETE /eventos/{ulid}/mesas/reservas/{ulid}` — cancela.
    - `POST /eventos/{ulid}/mesas/reservas/{ulid}/trocar` — troca (idempotente).
- **Erros:**
    - 409 `AssentoIndisponivel` → refetch + toast.
    - 410 `HoldExpirado` → reset hold + toast crítico.

---

### RF-015 — Catálogo de extras

- **Descrição:** listar produtos extras do evento.
- **Endpoint:** `GET /eventos/{ulid}/extras/catalogo`.
- **Outputs:** lista com `{ulid, nome, descricao, preco_cents, disponivel}`.
- **UX:** grid responsivo com cards; adicionar ao "carrinho" local (Zustand).

---

### RF-016 — Pedido de extras com pagamento

- **Descrição:** criar pedido e seguir para pagamento.
- **Endpoint:** `POST /eventos/{ulid}/extras/pedidos` (idempotente).
- **Inputs:** itens selecionados `[{extra_ulid, quantidade}]`.
- **Outputs:** `{pedido_ulid, pagamento_intent_ulid}`.
- **Pós-condição:** navegação para `/portal/pagamento/$intent_ulid`.

---

### RF-017 — Votar em enquete dentro da janela

- **Descrição:** registrar voto do formando em enquete aberta.
- **Endpoint:** `POST /eventos/{ulid}/enquetes/{ulid}/votos`.
- **Inputs:** `{opcao_ulid}`.
- **Erros:**
    - 409 `DomainError` (já votou | fora da janela) → toast explicativo + read-only.

---

### RF-018 — Editar perfil

- **Descrição:** permitir que o formando atualize dados pessoais permitidos.
- **Endpoint:** `PATCH /me` (❓ confirmar se existe).
- **Campos editáveis:** telefone, endereço, preferências notificação.
- **Campos read-only:** CPF, email corporativo (vincula conta).

---

### RF-019 — Exibir notificações in-app

- **Descrição:** mostrar toasts para eventos locais e opcionalmente inbox.
- **Canais:** toast (transitório) + sino no header (inbox ❓ MVP).
- **Regras:** fila de no máximo 3 toasts; `aria-live` adequado.

---

### RF-020 — Codegen de tipos da API

- **Descrição:** tipos TypeScript gerados via `openapi-typescript` a partir de `docs/api/openapi-skeleton.yaml`.
- **Processo:** `npm run codegen:api` local e em CI. ✅
- **Local de saída:** `resources/spa/src/api/types.gen.ts`.
- **Proibido:** escrever tipos manuais para contrato de API. ✅

---

### RF-021 — Axios client com interceptors padronizados

- **Descrição:** instância única com `withCredentials: true`, interceptor de CSRF, de 401/419, de request_id.
- **Responsabilidades:**
    - Propagar `X-Request-Id` em headers (em modo debug log no console). ✅
    - Tratar 419 → refazer `GET /sanctum/csrf-cookie` + retentar 1x.
    - Tratar 401 → `authStore.clear()` + redirect `/login`.
    - Adicionar `X-Idempotency-Key` quando fornecido via meta.

---

### RF-022 — Hook `useAuth` reutilizável

- **Descrição:** hook que expõe `{user, login, logout, status}` e sincroniza com `authStore`.
- **Dependências:** `authStore` (Zustand) + Axios client.

---

### RF-023 — Hook `useWizardAdesao`

- **Descrição:** hook que lê/escreve `wizardStore`, valida etapa atual, navega entre etapas.
- **Regras:** não permite pular etapas à frente — apenas voltar.

---

### RF-024 — Hook `usePagamento`

- **Descrição:** encapsula criação de intent, leitura de status, polling e cleanup.
- **Retorna:** `{createIntent, status, data, retry}`.

---

### RF-025 — Hook `useSeating`

- **Descrição:** encapsula leitura do mapa, criação/confirmação/troca/cancelamento de reservas e hold timer.
- **Retorna:** `{mapa, hold, reserve, confirm, cancel, swap, restanteSeconds}`.

---

### RF-026 — ErrorBoundary por rota

- **Descrição:** cada rota de nível 2 (ex: `/portal/mesas`) tem ErrorBoundary próprio com UI PT-BR + CTA retry.
- **Pré-condição:** erro não capturado no React render.
- **Integração:** TanStack Router `errorComponent`. ✅

---

### RF-027 — Internacionalização (PT-BR only no MVP)

- **Descrição:** todas as strings da UI em PT-BR; sem biblioteca de i18n no MVP.
- **Regra:** strings ficam próximas ao componente (colocation) ou em `lib/strings.ts` quando compartilhadas. 💡
- **Evolução:** estrutura permite trocar para `react-i18next` sem refactor visível. ❓ (decisão futura).

---

### RF-028 — Máscaras e formatações

- **Descrição:** CPF, telefone, CEP, moeda (centavos → "R$ 1.234,56"), datas (`dd/MM/yyyy`).
- **Libs aceitas:** Zod + funções puras em `lib/money.ts`, `lib/date.ts`, `lib/cpf.ts`. 💡
- **Proibido:** libs pesadas de máscara (ex: react-input-mask completo); preferir lógica simples + controlled input.

---

### RF-029 — Geração segura de idempotency keys

- **Descrição:** helper em `lib/idempotency.ts` com funções `get(scope)`, `generate(scope)`, `clear(scope)`.
- **Armazenamento:** `sessionStorage`.
- **Formato:** UUID v4.
- **Pré-condição de clear:** apenas após confirmação de sucesso (2xx final) ou erro 409 IdempotencyConflict.

---

### RF-030 — Responsividade pan/zoom no mapa

- **Descrição:** mapa mesas suporta gestos touch (pinch-zoom, pan) em mobile; zoom por scroll em desktop.
- **Lib sugerida:** wrapper próprio sobre `react-zoom-pan-pinch` ou implementação pura com Tamagui. 💡

---

## §2 — Requisitos Não-Funcionais (RNF)

| ID      | Requisito                                                                                          | Métrica / Critério                                                 |
| ------- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| RNF-001 | Lighthouse Performance desktop ≥ 90                                                                | Medido em CI (Playwright + Lighthouse)                             |
| RNF-002 | Lighthouse Performance mobile ≥ 85                                                                 | 3G Fast emulado                                                    |
| RNF-003 | LCP ≤ 2.5s em 3G Fast (mobile)                                                                     | CI automatizado                                                    |
| RNF-004 | TTI ≤ 3.5s em 3G Fast                                                                              | CI automatizado                                                    |
| RNF-005 | Bundle inicial JS ≤ 250KB gzip                                                                     | Vite analyze em CI                                                 |
| RNF-006 | Route-based code splitting obrigatório                                                             | Todas as rotas `/portal/*` via lazy TanStack Router ✅             |
| RNF-007 | WCAG 2.1 AA                                                                                        | Auditoria axe-core em CI (zero violations críticas)                |
| RNF-008 | Suporte: iOS Safari 16+, Android Chrome 100+, Desktop Chrome/Edge/Firefox/Safari ≥ 2 últimas major | Matriz de testes Playwright                                        |
| RNF-009 | Offline leve via TanStack Query staleTime                                                          | Navegação entre telas já visitadas sem rede por ≥ 5min 💡          |
| RNF-010 | Observabilidade: `X-Request-Id` em todos os requests                                               | Sempre presente + logs estruturados                                |
| RNF-011 | Zero erros JS não tratados em produção                                                             | Sentry opcional (F4+) — em DEV, console.error falha o build        |
| RNF-012 | 100% PT-BR                                                                                         | Lint custom ou revisão manual                                      |
| RNF-013 | TypeScript strict zero warnings                                                                    | `tsc --noEmit` passa em CI                                         |
| RNF-014 | ESLint + Prettier: zero violations                                                                 | CI gate                                                            |
| RNF-015 | Cobertura de testes unit ≥ 70%                                                                     | Vitest --coverage                                                  |
| RNF-016 | Cobertura de testes integration ≥ 50%                                                              | RTL + MSW                                                          |
| RNF-017 | E2E cobre 7 fluxos críticos                                                                        | Playwright: login, adesão, pagamento, convite, RSVP, mesas, extras |
| RNF-018 | CSP amigável                                                                                       | `script-src 'self'`; sem inline scripts além do mínimo 💡          |
| RNF-019 | SSR/Prerender: não necessário (SPA autenticado)                                                    | Shell mínimo em `spa.blade.php`                                    |
| RNF-020 | Tempo de build local ≤ 30s em máquina dev padrão                                                   | `npm run build` timed                                              |
| RNF-021 | Nenhum uso de `fetch` nativo                                                                       | ESLint rule `no-restricted-globals: fetch`                         |
| RNF-022 | Nenhum uso de `any`                                                                                | TS strict + `@typescript-eslint/no-explicit-any: error`            |
| RNF-023 | Nenhum componente shadcn/ui                                                                        | Lint custom ou code review                                         |
| RNF-024 | Nenhuma view Blade nova no portal                                                                  | `grep` em CI                                                       |
| RNF-025 | Uso exclusivo de cursor pagination                                                                 | Lint custom: `?page=` proibido nos hooks                           |

---

## §3 — Validações por Formulário

### 3.1 Schemas Zod compartilhados (lib/validators.ts)

```
ulidSchema          = z.string().length(26).regex(/^[0-9A-HJKMNP-TV-Z]{26}$/)
cpfSchema           = z.string().refine(isCpfValido, 'CPF inválido')
telefoneBrSchema    = z.string().regex(/^\(\d{2}\) \d{5}-\d{4}$/, 'Telefone inválido')
emailSchema         = z.string().email('Email inválido')
cepSchema           = z.string().regex(/^\d{5}-\d{3}$/)
moedaCentavosSchema = z.number().int().nonnegative()
```

### 3.2 Tabela de schemas por tela

| Tela/etapa               | Schema nomeado            | Campos principais                           |
| ------------------------ | ------------------------- | ------------------------------------------- |
| Login                    | `loginSchema`             | email, password                             |
| Adesão E1                | `adesaoE1Schema`          | cpf, telefone, nomeCompleto                 |
| Adesão E2                | `adesaoE2Schema`          | turmaUlid                                   |
| Adesão E3                | `adesaoE3Schema`          | pacoteUlid                                  |
| Adesão E4                | `adesaoE4Schema`          | numeroParcelas (1..24)                      |
| Adesão E5                | `adesaoE5Schema`          | ackRevisao (boolean)                        |
| Adesão E6                | `adesaoE6Schema`          | termosAceitos (boolean === true)            |
| Adesão E7                | `adesaoE7Schema`          | metodo enum, dadosMetodo opcional           |
| Pagamento cartão         | `cartaoSchema`            | numero, cvv, validade MM/AA, titular        |
| Convite individual       | `conviteIndividualSchema` | nomeConvidado (min 3), email?, telefone?    |
| Convite lote (parse CSV) | `conviteLoteLinhaSchema`  | nome, email?, telefone?                     |
| RSVP                     | `rsvpSchema`              | presenca enum                               |
| Perfil                   | `perfilSchema`            | telefone, endereco, preferenciasNotificacao |
| Voto enquete             | `votoSchema`              | opcaoUlid (ulidSchema)                      |
| Pedido extras            | `pedidoExtrasSchema`      | itens array min 1                           |

### 3.3 Tratamento de erro 422 `ValidationError`

- Helper `applyApiFieldErrors(form, apiErr)`:
    - Lê `apiErr.details.fields` (objeto `{campo: [msgs]}`).
    - Chama `form.setError(campo, {type:'server', message: msgs[0]})`.
- Campo não mapeado → adicionar em erro geral (banner topo).
- Mensagens sempre em PT-BR (vêm do backend).

---

## §4 — Regras de Permissão e Autenticação (REG)

### REG-001 — Isolamento por formando

- O formando só vê seus próprios dados; o backend garante escopo via policies. Frontend não aplica filtros redundantes.

### REG-002 — Rotas protegidas

- `/portal/*` exige cookie de sessão válido.
- `/rsvp/$token` é público com validação de token no backend.

### REG-003 — Sessão expirada

- Qualquer 401 em request XHR dispara logout local + redirect `/login?redirect=<rota>`.
- Logout preserva deep link no `redirect` query.

### REG-004 — CSRF

- Pre-request no login: `GET /sanctum/csrf-cookie`.
- Interceptor axios lê `XSRF-TOKEN` do cookie e envia em `X-XSRF-TOKEN` automaticamente (axios v1 faz nativo quando `withCredentials: true`). ✅
- 419 CSRF mismatch → refazer csrf-cookie + retentar 1x.

### REG-005 — Rate limiting

- 429 → ler `Retry-After` (segundos) e agendar retry em GETs; em POSTs exibir toast + CTA manual.

---

## §5 — Regras de Navegação (REG)

### REG-006 — Raiz `/`

- Se autenticado → `/portal/home`.
- Se não → `/login`.

### REG-007 — Guard `/portal/*`

- Sem auth → `/login?redirect=<rota>`.
- Com auth → renderiza Outlet.

### REG-008 — Deep link pós-login

- Ao logar, se existir `?redirect=<rota>` e a rota for segura (regex `/^\/portal\//` ou `/^\/rsvp\//`), redirecionar para ela.
- Caso contrário → `/portal/home`.

### REG-009 — Wizard adesão

- Entrada direta em `/portal/adesao/$step` sem pré-requisitos → redirect para etapa mais recente válida em draft.
- Bloquear acesso a `$step` > `maxStepValido` na store.

### REG-010 — Breadcrumb

- Aplicado em módulos com detalhe (ex: `/portal/financeiro > /portal/pagamento/$ulid`).
- Usa nomes em PT-BR.

---

## §6 — Comportamento por rota

| Rota                      | Auth | Queries disparadas                                           | Stores consumidos      | Erros esperados                           |
| ------------------------- | ---- | ------------------------------------------------------------ | ---------------------- | ----------------------------------------- |
| `/login`                  | não  | —                                                            | authStore              | 401, 422, 429                             |
| `/portal/home`            | sim  | `['me']`, `['me','eventos']`, `['me','extrato',cursor:null]` | authStore              | 5xx                                       |
| `/portal/adesao/$step`    | sim  | depende da etapa (turmas, pacotes)                           | authStore, wizardStore | 422, 5xx                                  |
| `/portal/financeiro`      | sim  | `['me','extrato']` infinite                                  | authStore              | 5xx                                       |
| `/portal/pagamento/$ulid` | sim  | `['pagamentos',ulid]` polling 5s                             | authStore              | 422, 409 IdempotencyConflict, 502, 5xx    |
| `/portal/convites`        | sim  | `['me','cotas']`, `['me','convites']` infinite               | authStore              | 409 CotaEsgotada, 5xx                     |
| `/portal/mesas`           | sim  | `['mesas','mapa',eventoUlid]` refetch 5s (hold ativo)        | authStore, holdStore   | 409 AssentoIndisponivel, 410 HoldExpirado |
| `/portal/extras`          | sim  | `['extras','catalogo',eventoUlid]`                           | authStore              | 422, 5xx                                  |
| `/portal/enquetes`        | sim  | `['enquetes',eventoUlid]`                                    | authStore              | 409 DomainError                           |
| `/portal/perfil`          | sim  | `['me']`                                                     | authStore              | 422, 5xx                                  |
| `/rsvp/$token`            | não  | `['convite',token]`                                          | —                      | 404, 410                                  |

---

## §7 — Tratamento de Erro

### 7.1 Mapa `error key` → UX

| error key             | HTTP | UX                                                                                       |
| --------------------- | ---- | ---------------------------------------------------------------------------------------- |
| `Unauthenticated`     | 401  | Interceptor: clear store + redirect `/login`. Sem ErrorBoundary.                         |
| `Forbidden`           | 403  | Toast warning "Você não tem permissão."; não bloquear navegação.                         |
| `NotFound`            | 404  | ErrorBoundary específico ou tela dedicada (ex: RSVP token inválido).                     |
| `MethodNotAllowed`    | 405  | Não deve ocorrer em produção; console.error em dev.                                      |
| `ValidationError`     | 422  | `applyApiFieldErrors` no RHF atual.                                                      |
| `AssentoIndisponivel` | 409  | Toast + `refetch` do mapa.                                                               |
| `HoldExpirado`        | 410  | Toast crítico + `holdStore.reset()` + `refetch`.                                         |
| `CotaEsgotada`        | 409  | Toast warning + bloquear CTA emissão.                                                    |
| `IdempotencyConflict` | 409  | Log + `clear(key)` + regenerar + retry 1x; se repetir, toast fatal.                      |
| `WebhookInvalido`     | 400  | Irrelevante para o frontend (só backend gera).                                           |
| `GatewayIndisponivel` | 502  | Toast error "Gateway indisponível" + CTA "tentar novamente".                             |
| `PagamentoDuplicado`  | 409  | Toast info "pagamento já registrado"; `invalidate` extrato.                              |
| `DomainError`         | 409  | Toast explicativo com `message`.                                                         |
| `InvariantViolation`  | 409  | Toast + `invalidate` do recurso.                                                         |
| `RateLimitExceeded`   | 429  | Ler `Retry-After`; em GET agendar refetch; em POST toast "Aguarde Xs".                   |
| `PayloadTooLarge`     | 413  | Toast error "Arquivo muito grande".                                                      |
| `EndpointSunset`      | 410  | Banner global "atualize a aplicação"; redirect `/login` após refresh. 💡                 |
| `ServiceUnavailable`  | 503  | Toast + retry GET com backoff exp (1s, 2s, 4s, max 3 tentativas).                        |
| `InternalServerError` | 500  | Toast error "Erro inesperado" + `request_id` em dev; POSTs não retentam automaticamente. |

### 7.2 Backoff exponencial (apenas GETs seguros)

- Tentativas: [1s, 2s, 4s] (3 no total).
- Jitter: ± 200ms.
- Não aplicar em POST/PATCH/DELETE (não retriam automaticamente).

### 7.3 ErrorBoundary

- 1 por rota nível 2 (`/portal/mesas`, `/portal/financeiro`, etc).
- Fallback: `ErrorScreen` (componente compartilhado) com título, mensagem, CTA "tentar novamente" e link "voltar à home".
- Em dev: expande `error.stack` + `request_id` atual.

### 7.4 Request IDs

- Toda request gera `X-Request-Id` se backend não impuser; se backend impuser no response, log no console com prefixo `[req_id]`.
- Em telas de erro, em dev mostrar o `request_id` para facilitar suporte.

---

## §8 — Requisitos de Acessibilidade

| ID       | Requisito                                                                         |
| -------- | --------------------------------------------------------------------------------- |
| A11Y-001 | Foco visível com contraste ≥ 3:1 em todos os elementos interativos                |
| A11Y-002 | `<label for=...>` ou `aria-labelledby` em todos os inputs                         |
| A11Y-003 | `aria-invalid="true"` + `aria-describedby` em campos com erro                     |
| A11Y-004 | `aria-live="polite"` em toasts de sucesso/info                                    |
| A11Y-005 | `aria-live="assertive"` em toasts críticos (hold expirado, pagamento falhou)      |
| A11Y-006 | Skip link "Pular para o conteúdo" no topo do AppShell                             |
| A11Y-007 | Modais travam foco (focus trap) e restauram ao fechar                             |
| A11Y-008 | Ordem de tab segue fluxo visual (sem `tabindex > 0`)                              |
| A11Y-009 | Ícones decorativos com `aria-hidden="true"`; ícones informativos com `aria-label` |
| A11Y-010 | Mapa de mesas com alternativa textual (toggle "vista acessível")                  |
| A11Y-011 | Contraste texto ≥ 4.5:1 (texto normal) e ≥ 3:1 (texto grande ≥ 18pt)              |
| A11Y-012 | Botões e targets de toque ≥ 44×44 px em mobile                                    |
| A11Y-013 | Respeitar `prefers-reduced-motion`                                                |
| A11Y-014 | Sem dependência única de cor para significado (sempre ícone+cor+texto)            |
| A11Y-015 | Forms submetíveis via Enter; Esc fecha modais                                     |

---

## §9 — Requisitos de Performance

| ID       | Requisito                                                                                       |
| -------- | ----------------------------------------------------------------------------------------------- |
| PERF-001 | Route-based code splitting obrigatório via TanStack Router lazy                                 |
| PERF-002 | Prefetch em hover de links primários (TanStack Router `preload: 'intent'`) ✅                   |
| PERF-003 | Image lazy loading (`loading="lazy"`) e `decoding="async"` onde aplicável                       |
| PERF-004 | Debounce em inputs de busca (300ms) e filtros (200ms)                                           |
| PERF-005 | Memoização criteriosa (`React.memo`, `useMemo`) apenas em listas ≥ 50 itens ou cálculos pesados |
| PERF-006 | TanStack Query staleTime configurado por recurso (defaults 30s; mapa 0s; financeiro 60s)        |
| PERF-007 | Não bloquear render com chamadas síncronas > 16ms no main thread                                |
| PERF-008 | Bundle chunks: vendor separado; cada rota em chunk próprio                                      |
| PERF-009 | Tree-shaking ativo no Vite                                                                      |
| PERF-010 | Fontes self-hosted com `font-display: swap`                                                     |
| PERF-011 | Ícones via lucide-react importados individualmente (zero barrel import)                         |
| PERF-012 | `React.startTransition` em updates não urgentes (ex: filtros que disparam refetch grande)       |
| PERF-013 | Polling do mapa 5s; polling do pagamento 5s — nunca mais agressivo no MVP                       |
| PERF-014 | `keepPreviousData: true` em paginação de extrato/convites para evitar flicker                   |

---

## §10 — Requisitos de Responsividade

| ID       | Requisito                                                      |
| -------- | -------------------------------------------------------------- |
| RESP-001 | Design mobile-first a partir de 320px                          |
| RESP-002 | Breakpoints Tamagui/Tailwind: sm 480, md 768, lg 1024, xl 1280 |
| RESP-003 | BottomNav em < 1024px; Sidebar em ≥ 1024px                     |
| RESP-004 | Sheet bottom para modais em mobile; Dialog central em desktop  |
| RESP-005 | Tabelas viram cards empilhados em mobile                       |
| RESP-006 | Mapa de mesas suporta gestos touch (pinch-zoom + pan)          |
| RESP-007 | Inputs e botões com altura mínima 44px em mobile               |
| RESP-008 | Header encolhe em scroll down mobile (opcional) 💡             |
| RESP-009 | Imagens `srcset`/`sizes` quando aplicável                      |
| RESP-010 | Suporte a landscape mobile (especial no mapa de mesas)         |

---

## §11 — Requisitos de Observabilidade (frontend)

| ID      | Requisito                                                                                                                             |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| OBS-001 | Toda request/response é loggada em DEV com `[req_id] METHOD URL status duration`                                                      |
| OBS-002 | Erros não capturados em produção → `window.onerror` + `window.onunhandledrejection` capturam e enviam para sink (Sentry opcional F4+) |
| OBS-003 | Logs estruturados: `console.error(msg, {context, requestId, userUlid})`                                                               |
| OBS-004 | Sem dados sensíveis em logs (nunca CPF completo, nunca senhas, nunca tokens)                                                          |
| OBS-005 | Rastreio de eventos críticos (funil adesão): tracking opcional (Segment/Amplitude) em F5+ 💡                                          |
| OBS-006 | Web Vitals (LCP, CLS, INP, FID, TTFB) reportados via `web-vitals` lib em prod                                                         |
| OBS-007 | Health check de boot: `GET /api/v1/health` antes de renderizar shell (opcional) ❓                                                    |

---

## §12 — Critérios de Aceite por Feature

### 12.1 Login

**CA-LOGIN-001 — Login bem-sucedido**

- **Dado** que sou um formando com email `leo@ex.com` e senha `Senha123!`
- **Quando** submeto o form com credenciais corretas
- **Então** o cookie de sessão é setado
- **E** sou redirecionado para `/portal/home`
- **E** vejo meu nome no header

**CA-LOGIN-002 — Credenciais inválidas**

- **Dado** que informo senha errada
- **Quando** submeto o form
- **Então** vejo mensagem "Credenciais inválidas" no campo senha
- **E** permaneço em `/login`
- **E** o contador de tentativas é atualizado pelo backend (429 em caso de excesso)

**CA-LOGIN-003 — Deep link preservado**

- **Dado** que acesso `/portal/mesas` sem sessão
- **Quando** sou redirecionado para `/login?redirect=%2Fportal%2Fmesas`
- **E** faço login com sucesso
- **Então** sou levado a `/portal/mesas`

---

### 12.2 Wizard Adesão

**CA-WIZARD-001 — Persistência de rascunho**

- **Dado** que preenchi a etapa 3 do wizard
- **Quando** fecho a aba e reabro em `/portal/adesao/3`
- **Então** meus dados aparecem preenchidos
- **E** não perco informação.

**CA-WIZARD-002 — Validação de etapa**

- **Dado** que digito CPF inválido na etapa 1
- **Quando** clico "Avançar"
- **Então** vejo mensagem "CPF inválido" no campo CPF
- **E** não avanço para etapa 2.

**CA-WIZARD-003 — Idempotência no pagamento final**

- **Dado** que confirmo o pagamento na etapa 7 e clico duas vezes
- **Quando** o segundo request chega
- **Então** a API responde com o mesmo `pagamento_ulid`
- **E** apenas uma cobrança é gerada.

**CA-WIZARD-004 — Não pular etapas**

- **Dado** que estou na etapa 2
- **Quando** tento acessar `/portal/adesao/5` manualmente
- **Então** sou redirecionado de volta para a etapa 2.

---

### 12.3 Financeiro + Pagamento

**CA-FIN-001 — Listagem cursor-paginada**

- **Dado** que tenho 30 parcelas históricas
- **Quando** abro `/portal/financeiro`
- **Então** vejo as primeiras N (definidas pelo backend)
- **E** ao rolar até o final, mais N são carregadas até `next_cursor == null`.

**CA-PAG-001 — Gerar PIX**

- **Dado** que escolho PIX para uma parcela aberta
- **Quando** confirmo
- **Então** vejo QR code + copia-cola + timer de expiração.

**CA-PAG-002 — Polling status até pago**

- **Dado** que gerei um PIX
- **Quando** o backend confirma o pagamento
- **Então** o polling captura o status e a UI atualiza automaticamente para "Pago"
- **E** sou redirecionado para `/portal/financeiro` com toast de sucesso.

**CA-PAG-003 — IdempotencyConflict tratado**

- **Dado** que houve conflito de key
- **Quando** o cliente recebe 409
- **Então** regenera a key, tenta novamente e sucede
- **E** o usuário não percebe o conflito.

---

### 12.4 Mapa de Mesas

**CA-MESAS-001 — Criar hold**

- **Dado** que abro `/portal/mesas`
- **Quando** clico num assento livre
- **Então** vejo o assento destacado com timer de 5 min
- **E** o refetch do mapa é ativado a cada 5s.

**CA-MESAS-002 — Assento ocupado por outro**

- **Dado** que clico num assento que já ficou ocupado
- **Quando** recebo 409 AssentoIndisponivel
- **Então** vejo toast "Assento ocupado"
- **E** o mapa é refetch-ado automaticamente.

**CA-MESAS-003 — Hold expirado**

- **Dado** que aguardei mais de 5 min sem confirmar
- **Quando** o timer zera
- **Então** vejo toast crítico "Tempo esgotado"
- **E** o estado do hold é resetado
- **E** o assento volta ao status livre no próximo refetch.

**CA-MESAS-004 — Trocar assento mantendo countdown**

- **Dado** que tenho um hold ativo com 2 min restantes
- **Quando** clico em outro assento livre
- **E** o POST .../trocar responde 200
- **Então** o hold é migrado
- **E** os 2 min remanescentes são preservados.

**CA-MESAS-005 — Confirmação bem-sucedida**

- **Dado** que tenho um hold ativo
- **Quando** clico "Confirmar"
- **E** o POST retorna 200
- **Então** vejo o selo "Reservado"
- **E** o timer some
- **E** a idempotency key é limpa.

---

### 12.5 Convites

**CA-CONV-001 — Emissão individual com link copiável**

- **Dado** que tenho cota ≥ 1
- **Quando** emito convite para "João da Silva"
- **Então** recebo modal com link `rsvp/<token>`
- **E** posso copiar ou compartilhar.

**CA-CONV-002 — Cota esgotada**

- **Dado** que minha cota é 0
- **Quando** tento emitir novo convite
- **Então** o CTA aparece desabilitado
- **E** vejo banner "Cota esgotada".

**CA-CONV-003 — Lote idempotente**

- **Dado** que envio 50 convites em lote e a conexão cai
- **Quando** eu refaço o upload
- **Então** o sistema reconhece a idempotency key e retorna o mesmo batch_ulid
- **E** não duplica convites.

---

### 12.6 RSVP Público

**CA-RSVP-001 — Confirmação pública**

- **Dado** que acesso `/rsvp/<token>` sem login
- **Quando** clico "Confirmar"
- **Então** o backend registra `presenca=confirmado`
- **E** vejo tela de sucesso com opção "Adicionar ao calendário".

**CA-RSVP-002 — Token inválido**

- **Dado** que o link é antigo ou foi cancelado
- **Quando** o GET retorna 404/410
- **Então** vejo mensagem amigável "Este link não é mais válido"
- **E** sem CTA para autenticação.

---

### 12.7 Extras

**CA-EXTRAS-001 — Pedido cria intent de pagamento**

- **Dado** que adicionei 2 itens do catálogo
- **Quando** clico "Finalizar"
- **Então** é criado um pedido
- **E** sou redirecionado para `/portal/pagamento/<intent_ulid>`.

**CA-EXTRAS-002 — Pedido idempotente**

- **Dado** que cliquei "Finalizar" e recebi timeout
- **Quando** clico novamente
- **Então** o servidor reconhece a idempotency key e retorna o mesmo pedido
- **E** nenhum pedido duplicado é criado.

---

### 12.8 Enquetes

**CA-ENQ-001 — Voto dentro da janela**

- **Dado** que existe enquete aberta
- **Quando** escolho uma opção e submeto
- **Então** o voto é registrado
- **E** a UI mostra "Você votou em X".

**CA-ENQ-002 — Voto fora da janela**

- **Dado** que a enquete está encerrada
- **Quando** tento submeter voto
- **Então** recebo 409 DomainError
- **E** vejo mensagem "A enquete foi encerrada".

---

### 12.9 Perfil

**CA-PERFIL-001 — Edição de dados**

- **Dado** que altero meu telefone
- **Quando** salvo
- **Então** vejo toast "Dados atualizados"
- **E** o header reflete o novo dado se aplicável.

**CA-PERFIL-002 — CPF read-only**

- **Dado** que visualizo meu perfil
- **Quando** tento editar o CPF
- **Então** o campo está desabilitado
- **E** vejo hint "CPF não pode ser alterado".

---

## §13 — Anti-requisitos (proibido)

| ID       | Proibição                                                                         |
| -------- | --------------------------------------------------------------------------------- |
| ANTI-001 | Usar `fetch` nativo em qualquer lugar                                             |
| ANTI-002 | Uso de `any` ou `@ts-ignore`                                                      |
| ANTI-003 | Consumir API via `?page=` ou `?offset=`                                           |
| ANTI-004 | Criar componentes shadcn/ui                                                       |
| ANTI-005 | Criar views Blade novas no portal                                                 |
| ANTI-006 | Hardcodar endpoints sem passar pelos hooks `useXxx`                               |
| ANTI-007 | Reutilizar `authStore` em `/rsvp/$token`                                          |
| ANTI-008 | Skipar idempotency key em `POST /pagamentos/intents` ou reservas/confirmar/trocar |
| ANTI-009 | Lidar com moedas em float (sempre centavos, int)                                  |
| ANTI-010 | Armazenar credenciais em localStorage                                             |
| ANTI-011 | Pular tipos gerados em favor de tipos manuais para API                            |
| ANTI-012 | `Array#map` sem `key` estável em listas                                           |
| ANTI-013 | Injeção direta de HTML vindo da API sem sanitização explícita via DOMPurify       |

---

## §14 — Cross-references

- UX e fluxos: [`03-UX-FLOWS-IA-SCREENS.md`](./03-UX-FLOWS-IA-SCREENS.md)
- Design técnico: [`09-TECHNICAL-DESIGN-CRITICAL-MODULES.md`](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- Polling mapa: [`ADR-006`](./06-ADR/ADR-006-polling-5s-mapa-mesas-mvp.md)
- Contrato API: [`../api/api-contract.md`](../api/api-contract.md)
- Envelope de erro: [`../api/error-envelope.md`](../api/error-envelope.md)
- Convenções API: [`../api/api-conventions.md`](../api/api-conventions.md)
- Planejamento Frontend: [`../prd/PLANEJAMENTO_FRONTEND_REACT.md`](../prd/PLANEJAMENTO_FRONTEND_REACT.md)

---

## §15 — Open questions consolidadas

| Item                                                                    | Impacto                    | Dono    |
| ----------------------------------------------------------------------- | -------------------------- | ------- |
| Existe `GET /eventos/{ulid}/pacotes` para E3?                           | bloqueante wizard          | Backend |
| Existe `GET /eventos/{ulid}/convites/lotes/{batch_ulid}` para polling?  | bloqueante lote            | Backend |
| Existe `PATCH /me` para edição de perfil?                               | RF-018                     | Backend |
| Existe `GET /me/notificacoes` (inbox)?                                  | RF-019 (inbox persistente) | Backend |
| Existe `GET /api/v1/health` para boot check?                            | OBS-007                    | Backend |
| Janela de polling pagamento (limite 30min proposto)                     | RF-008                     | Produto |
| Permitir alterar RSVP após confirmação?                                 | RF-011                     | Produto |
| Modo escuro no MVP?                                                     | Design                     | Produto |
| i18n futuro — estrutura preparada?                                      | RF-027                     | Produto |
| Rate limits concretos para SPA (cliente precisa saber valores default?) | REG-005                    | Backend |
| Tracking/analytics no MVP (Segment/Amplitude) ou apenas web-vitals?     | OBS-005                    | Produto |
| Web Share API para copiar links de RSVP                                 | RF-009                     | Design  |

---

## §16 — Rastreabilidade (matriz RF × CA × endpoint)

| RF     | Endpoint principal                              | CAs associados                |
| ------ | ----------------------------------------------- | ----------------------------- |
| RF-001 | POST /auth/login, GET /me                       | CA-LOGIN-001..003             |
| RF-004 | —                                               | CA-WIZARD-001                 |
| RF-005 | —                                               | CA-WIZARD-002                 |
| RF-006 | GET /me/extrato                                 | CA-FIN-001                    |
| RF-007 | POST /pagamentos/intents                        | CA-WIZARD-003, CA-PAG-001/003 |
| RF-008 | GET /pagamentos/{ulid}                          | CA-PAG-002                    |
| RF-009 | POST /eventos/{ulid}/convites                   | CA-CONV-001/002               |
| RF-010 | POST /eventos/{ulid}/convites/lotes             | CA-CONV-003                   |
| RF-011 | GET/POST /convite/{token}                       | CA-RSVP-001/002               |
| RF-012 | GET/POST /eventos/{ulid}/mesas/...              | CA-MESAS-001/002              |
| RF-013 | —                                               | CA-MESAS-003                  |
| RF-014 | POST .../confirmar /trocar, DELETE .../reservas | CA-MESAS-004/005              |
| RF-015 | GET /eventos/{ulid}/extras/catalogo             | CA-EXTRAS-001                 |
| RF-016 | POST /eventos/{ulid}/extras/pedidos             | CA-EXTRAS-001/002             |
| RF-017 | POST /eventos/{ulid}/enquetes/{ulid}/votos      | CA-ENQ-001/002                |
| RF-018 | PATCH /me ❓                                    | CA-PERFIL-001/002             |

---

## §17 — Glossário técnico

| Termo                 | Definição                                                               |
| --------------------- | ----------------------------------------------------------------------- |
| `authStore`           | store Zustand com estado de autenticação do formando                    |
| `wizardStore`         | store Zustand com rascunho do wizard adesão (7 etapas)                  |
| `holdStore`           | store Zustand com info do hold atual no mapa (reserva_ulid, expires_at) |
| `queryClient`         | instância única do TanStack Query para cache global                     |
| `idempotency key`     | UUID v4 enviado em `X-Idempotency-Key` em POSTs sensíveis               |
| `cursor pagination`   | paginação via cursor opaco; nunca offset                                |
| `refetchInterval`     | intervalo de polling automático do TanStack Query                       |
| `ErrorBoundary`       | wrapper React que captura erros render-time e exibe fallback            |
| `applyApiFieldErrors` | helper que transforma 422 `details.fields` em `form.setError` do RHF    |
| `TTL`                 | time-to-live; janela de validade de cache, hold, token, etc             |
