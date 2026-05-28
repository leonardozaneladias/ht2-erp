---
title: 'Open Questions e Backend Blockers — Portal SPA React'
module: frontend
doc_type: open-questions
version: 1.0.0
status: vivo
owner: tech-lead-frontend
audience: [tech-lead, dev-frontend, dev-backend, product, devops]
last_updated: 2026-04-18
related:
    - ./00-README-INDEX.md
    - ./05-FRONTEND-SAD.md
    - ./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md
    - ./10-QA-TEST-STRATEGY.md
    - ./11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md
    - ./12-RUNBOOK-FRONTEND.md
    - ./13-FRONTEND-IMPLEMENTATION-ROADMAP.md
    - ../prd/PLANEJAMENTO_FRONTEND_REACT.md
    - ../api/openapi-skeleton.yaml
    - ../api/api-contract.md
---

# Open Questions e Backend Blockers — Portal SPA React

> **Documento vivo.** Atualize toda vez que uma decisão for tomada, um blocker for resolvido, ou uma nova pergunta surgir. Pelo menos 1×/semana em refinamento.
>
> **Legenda:** ✅ ok/resolvido · ❌ blocker ativo · ❓ aberta/pendente · 🟡 parcial · ⚠️ decidido com risco

---

## Sumário

1. [Introdução](#1-introdução)
2. [Blockers de backend](#2-blockers-de-backend)
3. [Perguntas para backend](#3-perguntas-para-backend)
4. [Perguntas para produto/UX](#4-perguntas-para-produtoux)
5. [Perguntas de design system](#5-perguntas-de-design-system)
6. [Decisões pendentes top-level](#6-decisões-pendentes-top-level)
7. [Gaps de contrato](#7-gaps-de-contrato)
8. [Dependências externas](#8-dependências-externas)
9. [Decisões que DEVEM ser tomadas antes de implementar](#9-decisões-que-devem-ser-tomadas-antes-de-implementar)
10. [Template para registrar decisão tomada](#10-template-para-registrar-decisão-tomada)
11. [Roteiro de sincronização](#11-roteiro-de-sincronização)
12. [Histórico de decisões tomadas](#12-histórico-de-decisões-tomadas)

---

## 1. Introdução

Este documento consolida **tudo** que ainda não está resolvido no front-end do Portal ArtFinal v2 SPA React. Inclui:

- **Blockers de backend** — itens técnicos que bloqueiam o frontend de avançar
- **Perguntas para backend** — contratos que precisam ser confirmados
- **Perguntas para produto/UX** — decisões de experiência e comportamento
- **Perguntas de design system** — escolhas estéticas/técnicas de UI
- **Decisões top-level** — já com default proposto; precisam ratificação
- **Gaps de contrato** — endpoints citados no planejamento, ainda não formalizados
- **Dependências externas** — gateways, emails etc.

**Categorização de prioridade:**

- ❌ **P0 bloqueante**: sem isso, sprint atual para
- 🟡 **P1 urgente**: bloqueia próxima sprint
- ❓ **P2 importante**: precisa ser resolvido até F7
- 🟢 **P3 nice-to-have**: decisão pode ser tardia

Cada item tem:

- **Por que precisa saber** — justifica a urgência
- **Proposta de default** — alternativa para destravar se decisão não vem

---

## 2. Blockers de backend

Itens técnicos onde o **frontend não pode avançar** sem entrega do backend. Nenhum deles deve estar ❌ no início do Pré-F3.

| #   | Item                                                                                                     | Ambiente     | Status | Owner sugerido | Arquivos envolvidos                                                |
| --- | -------------------------------------------------------------------------------------------------------- | ------------ | ------ | -------------- | ------------------------------------------------------------------ |
| B1  | `config/cors.php` publicado (`supports_credentials: true`, `allowed_origins` inclui FRONTEND_URL)        | todos        | ❌     | Dev BE         | `config/cors.php`                                                  |
| B2  | `config/sanctum.php` com stateful domains configurados                                                   | todos        | ❌     | Dev BE         | `config/sanctum.php`, `.env` (`SANCTUM_STATEFUL_DOMAINS`)          |
| B3  | `routes/portal.php` catch-all → `spa.blade.php`                                                          | todos        | ❌     | Dev BE         | `routes/portal.php`                                                |
| B4  | `resources/views/spa.blade.php` criado com `@viteReactRefresh` + `@vite(['resources/spa/src/main.tsx'])` | todos        | ❌     | Dev BE         | `resources/views/spa.blade.php`                                    |
| B5  | `Api\V1\AuthController` com métodos `login`, `me`, `logout`                                              | todos        | ❌     | Dev BE         | `app/Http/Controllers/Api/V1/AuthController.php`, `routes/api.php` |
| B6  | `openapi-skeleton.yaml` completo e estável para todos endpoints do F3                                    | todos        | 🟡     | Tech Lead BE   | `docs/api/openapi-skeleton.yaml`                                   |
| B7  | Middleware global `X-Request-Id` em **100% das rotas** API                                               | todos        | ❓     | Dev BE         | `app/Http/Middleware/RequestId.php`, `bootstrap/app.php`           |
| B8  | Middleware de **Idempotency-Key** em endpoints POST críticos (pagamentos, mesas, convites)               | todos        | ❓     | Dev BE         | `app/Http/Middleware/Idempotency.php`                              |
| B9  | **Error envelope global** (Problem+JSON) em toda resposta 4xx/5xx da API                                 | todos        | 🟡     | Dev BE         | `app/Exceptions/Handler.php`, `docs/api/error-envelope.md`         |
| B10 | Rate limiters registrados (login 5/min, API 60/min)                                                      | todos        | ❓     | Dev BE         | `app/Providers/RouteServiceProvider.php`                           |
| B11 | Endpoints `/me/*` operacionais: `eventos`, `adesoes`, `convites`, `cotas`, `extrato`                     | F3           | ❓     | Dev BE         | vários controllers `Api\V1\Me\*`                                   |
| B12 | Endpoints `/eventos/{ulid}/mesas/*` com **hold atômico no DB** (unique index + transaction)              | F5           | ❌     | Dev BE         | `MesasController`, migration com constraint                        |
| B13 | Endpoints `/pagamentos/*` com idempotência servidor-side                                                 | F3           | ❌     | Dev BE         | `PagamentosController`, `IdempotencyStore`                         |
| B14 | Endpoints `/eventos/{ulid}/enquetes/*` com janela temporal validada no servidor                          | F6           | ❓     | Dev BE         | `EnquetesController`, campos `abre_em` / `fecha_em`                |
| B15 | Endpoint público `/convite/{token}` (sem auth, com rate limit próprio)                                   | F4           | ❓     | Dev BE         | `RsvpController`, `routes/public.php`                              |
| B16 | CORS `allowed_headers` inclui `X-Request-Id` e `X-Idempotency-Key`                                       | todos        | ❌     | Dev BE         | `config/cors.php`                                                  |
| B17 | Sanctum `SESSION_SAME_SITE=lax` (não `strict`)                                                           | todos        | ❓     | Dev BE         | `.env`, `config/session.php`                                       |
| B18 | Tabela `idempotency_keys` com índice único + TTL 24h                                                     | todos        | ❓     | Dev BE         | migration `create_idempotency_keys_table`                          |
| B19 | Endpoint `GET /api/v1/health` (smoke check)                                                              | todos        | ❓     | Dev BE         | `HealthController`                                                 |
| B20 | Endpoint de seed `POST /api/v1/testing/reset` (apenas ambientes não-prod)                                | staging/test | ❓     | Dev BE         | `TestingController`, guard `if (! app()->isProduction())`          |
| B21 | Endpoint `POST /api/v1/testing/advance-time` para testes E2E (hold timer)                                | test         | ❓     | Dev BE         | `TestingController`                                                |

### 2.1 Blockers críticos de Pré-F3 (ordem de ataque)

Sequência recomendada para BE antes do FE começar:

1. B1 + B2 + B16 + B17 — **CORS + Sanctum**. Sem isso, nenhum request cookie-based funciona.
2. B3 + B4 — **Catch-all + spa.blade.php**. Sem isso, SPA nem carrega.
3. B5 — **AuthController**. Sem isso, smoke test não passa.
4. B6 — **OpenAPI skeleton** ao menos em auth. Sem isso, codegen falha.
5. B7 + B8 + B9 — **Middlewares** (request_id, idempotency, error envelope).
6. B10 — Rate limiters.
7. B18 — Tabela idempotency_keys.
8. B19 — Health check.
9. B20 + B21 — Endpoints de teste (opcional se E2E só roda em ambiente limpo).

---

## 3. Perguntas para backend

Contratos que **precisam ser formalizados** no `openapi-skeleton.yaml`. Cada pergunta inclui justificativa e default proposto.

### Q-BE-01 — Shape de `GET /me/extrato`

**Contexto:** frontend (F3 SP 7) precisa renderizar extrato com scroll infinito.
**Pergunta:** o shape segue cursor pagination ou offset?
**Por que precisa saber:** `useInfiniteQuery` tem configuração diferente; cursor exige `next_cursor` opaque.
**Proposta de default (se não decidido):** cursor pagination, envelope:

```json
{
  "data": [ /* ExtratoItem[] */ ],
  "meta": {
    "cursor": { "next_cursor": "eyJpZCI6MTIzfQ==" | null, "limit": 20 }
  }
}
```

### Q-BE-02 — Endpoint para dados da adesão atual

**Contexto:** home e financeiro precisam exibir "Adesão ativa" do formando.
**Pergunta:** existe `GET /api/v1/me/eventos/{ulid}/adesao` ou usar `GET /adesoes/{ulid}` após `GET /me/adesoes`?
**Por que precisa saber:** define quantos requests a home dispara.
**Proposta:** criar `GET /api/v1/me/adesao-ativa` agregado (1 call).

### Q-BE-03 — Polling de pagamento

**Contexto:** após criar intent, SPA precisa saber quando pagamento confirma.
**Perguntas:**

1. Quantos segundos entre polls? (sugestão: **3s**)
2. Timeout após quantos minutos? (sugestão: **10 min**)
3. Resposta 429 em polling agressivo? (se sim, frontend deve respeitar `Retry-After`)

**Proposta:** 3s fixo; frontend respeita `Retry-After` se vier.

### Q-BE-04 — Webhook de pagamento → frontend

**Contexto:** ideal é que frontend atualize sem polling quando backend recebe webhook do gateway.
**Pergunta:** haverá broadcasting (Pusher/Reverb) notificando o frontend? Ou fica só polling?
**Por que precisa saber:** polling é mais simples mas custa mais; broadcasting é UX melhor mas requer infra.
**Proposta:** MVP com polling (F3). Broadcasting como melhoria em F7+.

### Q-BE-05 — Wizard: shape de `GET /eventos/{ulid}/pacotes`

**Contexto:** etapa 3 do wizard lista pacotes disponíveis.
**Pergunta:** o envelope inclui parcelamento simulado? Ou SPA faz outro call após selecionar?
**Por que precisa saber:** UX diferente — inline vs passo separado.
**Proposta:** cada pacote vem com `parcelas_disponiveis: [3, 6, 12]` e SPA calcula localmente (via endpoint auxiliar `POST /parcelamento/simular`).

### Q-BE-06 — Enquetes: janela

**Contexto:** UI precisa indicar "abre em X" ou "fecha em Y".
**Pergunta:** como o backend expõe janela? Campos `abre_em`, `fecha_em`, `status_janela` ("agendada", "aberta", "fechada")?
**Proposta:** backend retorna todos 3 campos; frontend confia em `status_janela` (servidor é fonte).

### Q-BE-07 — Lote de convites

**Contexto:** emitir 10 convites de uma vez.
**Pergunta:** status enum: `pendente`, `processando`, `concluido`, `falhou`? Endpoint `GET /lotes/{ulid}` para polling?
**Proposta:** enum acima; frontend polla `GET /me/convites/lotes/{ulid}` com 2s interval.

### Q-BE-08 — RSVP: TTL do token

**Contexto:** link RSVP enviado por WhatsApp.
**Perguntas:**

1. Token tem TTL? (sugestão: até data do evento + 30 dias)
2. Pode ser usado múltiplas vezes para **atualizar** resposta? (ex: mudar acompanhantes)
3. Rate limit específico? (ex: 10/min por token)

**Proposta:** TTL até fim do evento; update permitido (idempotente); rate 10/min.

### Q-BE-09 — Extras: parcelamento

**Contexto:** formando compra DVD extra no portal.
**Pergunta:** pode parcelar ou é à vista? Reutiliza fluxo de pagamento do financeiro?
**Proposta:** à vista no MVP (F4); parcelamento em F6 se demanda aparecer.

### Q-BE-10 — Troca de assento

**Contexto:** formando quer mudar de cadeira.
**Perguntas:**

1. Limite de trocas? (uma vez? ilimitado?)
2. Requer aprovação admin?
3. Custo extra?

**Proposta:** **1 troca gratuita** por formando; trocas seguintes geram nova parcela (decisão de produto).

### Q-BE-11 — Permite múltiplas abas simultâneas?

**Contexto:** `sessionStorage` **é isolado por aba**. Se usuário abrir /portal/mesas em 2 abas, cada uma tem key de idempotency diferente.
**Pergunta:** comportamento esperado?
**Proposta:** aceitar que é por aba; documentar na UX ("Mantenha uma aba só para operações críticas").

### Q-BE-12 — Shape de `GET /me`

**Contexto:** hidratação inicial do authStore.
**Pergunta:** retorna `{ user }` apenas ou também `{ user, eventos_resumo, notificacoes_count }` agregado?
**Proposta:** só `{ user }`; agregados vêm de endpoints próprios invocados por hooks.

### Q-BE-13 — Histórico de pagamentos

**Contexto:** formando quer ver "onde foram os pagamentos".
**Pergunta:** `GET /me/pagamentos` ou aproveitar `GET /me/extrato`?
**Proposta:** reusar `/me/extrato` filtrando `status=pago`.

### Q-BE-14 — Recuperação de senha

**Contexto:** ainda não mapeado.
**Pergunta:** em F3 ou pode ir para F7?
**Proposta:** F3 com fluxo mínimo (envia email com link); UX completa em F7.

---

## 4. Perguntas para produto/UX

### Q-UX-01 — Wizard: pausar e retomar

**Contexto:** wizard tem 7 etapas longas.
**Pergunta:** usuário pode pausar e voltar mais tarde? Se sim, estado em `sessionStorage` (mesma aba) é suficiente ou precisa persistir no servidor?
**Por que precisa saber:** sessionStorage se perde em nova aba / novo device.
**Proposta:** MVP com sessionStorage apenas; feature "salvar rascunho no servidor" em F7.

### Q-UX-02 — Extrato: filtros padrão

**Contexto:** extrato tem muitas parcelas.
**Pergunta:** mostra só pendentes por padrão? Ou todas com filtro default?
**Proposta:** **todas** por padrão; filtro "Ver apenas pendentes" proeminente.

### Q-UX-03 — Recuperação de senha

**Pergunta:** fluxo esperado no MVP?
**Proposta:** link "Esqueci senha" em `/login` → form → email com token → reset.

### Q-UX-04 — Troca de assento

**Pergunta:** quantas trocas são permitidas? UX mostra "você já trocou 1x"?
**Proposta:** 1 troca grátis; UI mostra contador.

### Q-UX-05 — Notificações in-app

**Contexto:** formando recebe convite confirmado, pagamento aprovado.
**Perguntas:**

1. Push mobile? (F8)
2. Inbox dentro do portal? (F6)
3. Toast + email combinados?

**Proposta:** toast + email (F3-F6); push e inbox em F6+.

### Q-UX-06 — Modo escuro

**Pergunta:** escopo?
**Proposta:** opcional em F7 via toggle; default light.

### Q-UX-07 — Múltiplas abas

**Pergunta:** bloqueamos ou permitimos?
**Proposta:** permitir; avisar em UX crítica.

### Q-UX-08 — Convidado sem email pode confirmar RSVP?

**Contexto:** acompanhante pode não ter email.
**Pergunta:** formulário RSVP pode ser anônimo?
**Proposta:** nome + telefone obrigatórios; email opcional.

### Q-UX-09 — Copy de erros

**Pergunta:** padrão de tom? Empático? Humorado? Formal?
**Proposta:** empático + acionável. "Não foi possível confirmar seu assento. Tente novamente ou entre em contato."

### Q-UX-10 — Campos obrigatórios visualmente

**Pergunta:** `*` vermelho ou "(obrigatório)" textual?
**Proposta:** `*` vermelho próximo ao label (comum e acessível com `aria-required`).

### Q-UX-11 — Upload de avatar

**Perguntas:**

1. Tamanho máximo? (sugestão: 2 MB)
2. Formatos? (jpg, png, webp)
3. Crop na UI? (círculo)

**Proposta:** 2 MB, jpg/png/webp, crop circular pré-upload.

### Q-UX-12 — Confirmação destrutiva

**Pergunta:** trocar assento pede confirmação? Logout pede?
**Proposta:** trocar assento sim (SweetAlert2 ou similar); logout não (ação rotineira).

### Q-UX-13 — Internacionalização

**Pergunta:** portal vai suportar outros idiomas no futuro?
**Proposta:** PT-BR only no MVP; se surgir ES/EN, adicionar i18next em F7+.

### Q-UX-14 — Acessibilidade: foco em mesas (canvas)

**Contexto:** canvas não é acessível por padrão.
**Pergunta:** fornecer fallback (tabela HTML das mesas)?
**Proposta:** sim — tabela acessível como visão alternativa (toggle "modo acessível").

---

## 5. Perguntas de design system

### Q-DS-01 — Tamagui v2 em React 19: maturidade

**Contexto:** Tamagui v2 foi lançado ~2024; SPA usa React 19.
**Pergunta:** combinação é estável? (validar em 2026-04, data deste doc)
**Risco:** bugs de hydration, SSR incompleto.
**Proposta:** POC na Pré-F3 com 3 componentes (Button, Input, Card). Se falhar, fallback para **shadcn-ui** (sem Tamagui).

### Q-DS-02 — Tokens de cor

**Pergunta:** herdar do Inspinia admin (variáveis CSS) ou definir paleta nova?
**Proposta:** definir nova com base em brand do cliente; Inspinia continua só no admin.

### Q-DS-03 — Ícones

**Pergunta:** Lucide, Phosphor, ou Heroicons?
**Critério:** compatibilidade com RN (F8).
**Proposta:** **Lucide** — boa cobertura + suporte nativo via `@tamagui/lucide-icons`.

### Q-DS-04 — Fontes

**Pergunta:** system default, Inter, Geist?
**Proposta:** **Inter** (Google Fonts, self-host) — legível, neutro, mundialmente adotado.

### Q-DS-05 — Breakpoints

**Pergunta:** padrão Tamagui ou custom?
**Proposta:** padrão (`sm: 640px, md: 768px, lg: 1024px, xl: 1280px`). Portal é **mobile-first**; admin é desktop-first (separado).

### Q-DS-06 — Tema escuro

**Pergunta:** implementar desde F3 ou só em F7?
**Proposta:** **F7**. MVP só light.

### Q-DS-07 — Spacing scale

**Pergunta:** múltiplos de 4 ou de 8?
**Proposta:** **4** (Tailwind/Tamagui padrão).

### Q-DS-08 — Shadows

**Pergunta:** estilo utilitário (Tailwind-like) ou semântico (`card`, `modal`, `popover`)?
**Proposta:** semântico — `$shadow.card`, `$shadow.modal`.

### Q-DS-09 — Radius

**Pergunta:** estilo — ponta viva, arredondado suave, arredondado forte?
**Proposta:** arredondado suave (`$radius.base = 8px`, `$radius.md = 12px`).

### Q-DS-10 — Animações

**Pergunta:** usar View Transitions API? Framer Motion?
**Proposta:** View Transitions onde suportado (Chromium); fallback sem animação. Framer Motion fora (peso).

---

## 6. Decisões pendentes top-level

Decisões **arquiteturais** com default já proposto. Status = ⚠️ significa "decidido com risco" — default aceito mas pode ser revisto.

| #   | Decisão                        | Opções                                      | Default proposto         | Status | Revisão em                   |
| --- | ------------------------------ | ------------------------------------------- | ------------------------ | ------ | ---------------------------- |
| D1  | Localização do código React    | monorepo `resources/spa/` vs repo separado  | monorepo                 | ⚠️     | F8 (mobile pode pedir split) |
| D2  | API typing                     | openapi-typescript vs orval                 | openapi-typescript       | ✅     | —                            |
| D3  | Realtime seating               | polling 5s vs WebSocket                     | polling                  | ⚠️     | F7                           |
| D4  | i18n                           | hardcoded PT-BR vs i18next                  | hardcoded                | ⚠️     | F8                           |
| D5  | Design system                  | Tamagui vs shadcn vs Chakra                 | Tamagui                  | ⚠️     | após POC em Pré-F3           |
| D6  | E2E                            | Playwright vs Cypress                       | Playwright               | ✅     | —                            |
| D7  | TS strictness                  | strict vs strict + noUncheckedIndexedAccess | ambos                    | ✅     | —                            |
| D8  | Rota file-based vs code-based  | file-based vs code-based                    | file-based (TanStack v1) | ✅     | —                            |
| D9  | Query devtools em prod         | ligada vs desligada                         | desligada                | ✅     | —                            |
| D10 | Sentry vs LogRocket vs próprio | Sentry                                      | Sentry                   | ⚠️     | F7                           |
| D11 | Package manager                | npm vs pnpm vs yarn                         | npm                      | ✅     | —                            |
| D12 | Bundle format produção         | Vite dev-ssr vs SPA puro                    | SPA puro                 | ✅     | —                            |
| D13 | Hidratação inicial             | SSR vs SPA puro                             | SPA puro                 | ✅     | F7 (se SEO crítico)          |
| D14 | Persistência wizard            | sessionStorage vs localStorage vs server    | sessionStorage           | ⚠️     | F7 (se UX pede)              |
| D15 | Axios vs fetch vs ky           | Axios                                       | Axios                    | ✅     | —                            |
| D16 | Zod vs Yup vs Valibot          | Zod v4                                      | Zod                      | ✅     | —                            |

---

## 7. Gaps de contrato

Endpoints **mencionados no planejamento** mas **ainda não formalizados** em `docs/api/openapi-skeleton.yaml`. Para cada gap, a ação é: **abrir PR no skeleton + validar com backend**.

| #   | Endpoint                                                 | Documentado em | Gap                                              |
| --- | -------------------------------------------------------- | -------------- | ------------------------------------------------ |
| G1  | `GET /api/v1/me`                                         | planejamento   | shape completo não está em openapi ❓            |
| G2  | `GET /api/v1/me/home-summary`                            | —              | endpoint novo, não existe ainda                  |
| G3  | `GET /api/v1/me/extrato`                                 | planejamento   | shape do cursor + filtros ❓                     |
| G4  | `POST /api/v1/eventos/{ulid}/adesoes`                    | planejamento   | shape do body + idempotência ❓                  |
| G5  | `GET /api/v1/eventos/{ulid}/pacotes`                     | —              | endpoint novo                                    |
| G6  | `POST /api/v1/pagamentos/intents`                        | planejamento   | shape do body por método (boleto/PIX/cartão) ❓  |
| G7  | `GET /api/v1/pagamentos/{ulid}`                          | planejamento   | estados possíveis + transições ❓                |
| G8  | `GET /api/v1/me/convites`                                | planejamento   | shape da listagem ❓                             |
| G9  | `POST /api/v1/eventos/{ulid}/convites`                   | planejamento   | shape + flags (WhatsApp, email) ❓               |
| G10 | `POST /api/v1/eventos/{ulid}/convites/{ulid}/transferir` | —              | endpoint novo                                    |
| G11 | `GET /api/v1/convite/{token}` (público)                  | planejamento   | sem auth, rate limit próprio ❓                  |
| G12 | `POST /api/v1/convite/{token}/confirmar`                 | planejamento   | shape do RSVP (acompanhantes, restrições) ❓     |
| G13 | `GET /api/v1/eventos/{ulid}/mesas/mapa`                  | planejamento   | formato JSON (flat vs grupos) ❓                 |
| G14 | `GET /api/v1/eventos/{ulid}/mesas/estado`                | —              | endpoint leve para polling                       |
| G15 | `POST /api/v1/eventos/{ulid}/mesas/holds`                | planejamento   | TTL fixo 5min? Retornar `hold_expires_at` ISO ✅ |
| G16 | `DELETE /api/v1/eventos/{ulid}/mesas/holds/{id}`         | —              | liberar hold manualmente                         |
| G17 | `POST /api/v1/eventos/{ulid}/mesas/confirmar`            | planejamento   | body + idempotency ❓                            |
| G18 | `POST /api/v1/eventos/{ulid}/mesas/trocar`               | —              | atômico (libera + reserva)                       |
| G19 | `GET /api/v1/eventos/{ulid}/enquetes`                    | planejamento   | listar com status_janela ❓                      |
| G20 | `POST /api/v1/eventos/{ulid}/enquetes/{ulid}/votos`      | planejamento   | idempotente ❓                                   |
| G21 | `GET /api/v1/eventos/{ulid}/enquetes/{ulid}/resultados`  | —              | permissões (só após fechada?)                    |
| G22 | `GET /api/v1/me/perfil`                                  | —              | shape do perfil editável                         |
| G23 | `PUT /api/v1/me/perfil`                                  | —              | validações servidor-side                         |
| G24 | `POST /api/v1/me/senha`                                  | —              | requer senha atual + nova                        |
| G25 | `GET /api/v1/eventos/{ulid}/extras/catalogo`             | planejamento   | preço em centavos ✅, imagens URLs ❓            |
| G26 | `POST /api/v1/me/extras/compras`                         | —              | idempotência + parcelamento                      |
| G27 | `GET /api/v1/health`                                     | —              | smoke check                                      |
| G28 | `POST /api/v1/auth/password/forgot`                      | —              | recuperação de senha (F3)                        |
| G29 | `POST /api/v1/auth/password/reset`                       | —              | reset com token                                  |
| G30 | `POST /api/v1/telemetry/web-vitals` (F7)                 | —              | coleta de métricas                               |

---

## 8. Dependências externas

### D-EXT-01 — Gateway de pagamento (Itaú)

- **Sandbox disponível?** ❓ Confirmar credenciais com cliente antes de SP 8.
- **Métodos suportados?** Boleto, PIX, Cartão — confirmar todos têm sandbox.
- **Webhook endpoint?** Backend precisa URL pública (via `ngrok` em dev).
- **Formato de PIX?** QR code vem do gateway como base64 PNG? SVG? String copia-cola?

### D-EXT-02 — Provedor de e-mail transacional

- **Dev:** Mailpit (✅ Laradock).
- **Staging/Prod:** Postmark / SES / Mailgun? **Decisão pendente.**
- **Templates:** Markdown via Laravel Mail ou HTML custom?

### D-EXT-03 — Gateway de SMS (para RSVP)

- **Necessário?** RSVP envia via WhatsApp (wa.me link) ou SMS?
- **Decisão de produto**: wa.me link é suficiente no MVP.

### D-EXT-04 — CDN para assets estáticos

- **Necessário em staging?** Para medir Lighthouse real.
- **Proposta:** Cloudflare CDN na frente do Laravel Cloud.

### D-EXT-05 — Captcha (opcional em RSVP público)

- **Necessário?** Rate limit no backend talvez baste. Captcha em F7 se abuso.

---

## 9. Decisões que DEVEM ser tomadas antes de implementar

Ordem de prioridade com **deadline relativo à fase**.

### 9.1 Bloqueantes para Pré-F3 (esta sprint)

1. ❌ Confirmar blockers B1–B5 (CORS, Sanctum, catch-all, spa.blade, AuthController) serão entregues até fim da sprint
2. ❌ Confirmar B9 (error envelope) — sem isso, interceptor de erro genérico é impossível
3. ❌ Confirmar B16 (CORS allowed_headers) inclui `X-Request-Id` e `X-Idempotency-Key`

### 9.2 Bloqueantes para F3 (SP 4-9)

4. ❓ Confirmar shape de `GET /me/extrato` (Q-BE-01, G3)
5. ❓ Confirmar shape de wizard — `GET /pacotes` (G5) e `POST /adesoes` (G4)
6. ❓ Confirmar fluxo de pagamento — 3 métodos × shape de intent (G6, G7)
7. ❓ Confirmar polling interval + timeout (Q-BE-03)
8. ❓ Confirmar D-EXT-01 sandbox Itaú disponível

### 9.3 Bloqueantes para F4 (SP 10-11)

9. ❓ Confirmar shape `GET /convite/{token}` público (G11, G12)
10. ❓ Confirmar TTL + rate limit do token RSVP (Q-BE-08)

### 9.4 Bloqueantes para F5 (SP 15-17)

11. ❌ Confirmar B12 — hold atômico no DB com índice único
12. ❓ Confirmar shape de `/mesas/mapa` (G13) — JSON flat ou por grupos de mesas
13. ❓ Confirmar TTL do hold = 5 min (se diferente, ajustar UX)
14. ❓ Confirmar idempotency window = 24h em `/mesas/confirmar`
15. ❓ Decisão D3 (polling vs WebSocket) — **ratificar ou mudar**

### 9.5 Bloqueantes para F6 (SP 18-19)

16. ❓ Confirmar janela temporal de enquetes (Q-BE-06)
17. ❓ Decisão sobre tema escuro (Q-UX-06) — escopo F7?

### 9.6 Bloqueantes para F7 (SP 24)

18. ❓ Decisão D10 — ferramenta de observabilidade
19. ❓ Decisão se Sentry é custeado (licença / open-source)

### 9.7 Bloqueantes para F8 (SP 25-26)

20. ❓ Validar Tamagui v2 + RN + Expo SDK 53 — POC na primeira semana
21. ❓ Confirmar ADR-0003 do backend define modelo de token para mobile
22. ❓ Apple Developer account + Google Play Developer account disponíveis

---

## 10. Template para registrar decisão tomada

Quando uma pergunta for resolvida, **não deletar** — mover o item para §12 e usar este template:

```markdown
### Decisão D-XXX — <título curto>

**Data:** YYYY-MM-DD
**Status:** decidido
**Participantes:** <nomes>
**Contexto:** <1-2 parágrafos explicando o problema>
**Opções consideradas:**

- Opção A: ...
- Opção B: ...
  **Decisão:** <opção escolhida, com justificativa>
  **Reflexos:** <docs afetados, migrations, refactor>
  **Revisão prevista:** <data ou "permanente">
  **Referências:** <links para PR, issue, ADR>
```

Se a decisão for significativa arquiteturalmente, **criar ADR** em `docs/frontend/06-ADR/NNNN-titulo.md` e linkar aqui.

---

## 11. Roteiro de sincronização

### 11.1 Semanal (sexta, 30 min)

- Revisar este doc com tech lead FE + tech lead BE + product
- Para cada item aberto: ainda relevante? mudou prioridade? resolvido?
- Para cada item resolvido: mover para §12 com decisão registrada

### 11.2 Diariamente (standup)

- Se algum blocker ❌ surgiu no dia: mencionar no daily
- Se decisão foi tomada: atualizar este doc antes do final do dia

### 11.3 Antes de cada sprint

- Purge de perguntas obsoletas (sprints passadas, escopo fora)
- Adicionar perguntas específicas da sprint entrante em §9
- Alinhar com Plane: cada blocker ❌ tem issue

### 11.4 Antes de release

- Todos os ❌ devem estar ✅
- Todos os ❓ P0/P1 devem estar ✅ ou ⚠️ com mitigação documentada
- Review por tech lead + product

---

## 12. Histórico de decisões tomadas

_Mover aqui itens resolvidos com template do §10._

### Decisão D-001 — Monorepo para SPA React

**Data:** 2026-04-18
**Status:** decidido
**Participantes:** Tech Lead FE, Tech Lead BE, Arquiteto
**Contexto:** SPA React precisa de estrutura própria. Opções: repo separado `portalartfinal-spa` ou pasta `resources/spa/` no monorepo Laravel.
**Opções consideradas:**

- **A — repo separado:** deploy independente, CI mais rápido, mas custo de autenticar 2 codebases e versionar API em paralelo.
- **B — monorepo com `resources/spa/`:** simplifica dev local, CI único, shell servido pelo Laravel.
  **Decisão:** monorepo (opção B).
  **Reflexos:** `resources/spa/` com `package.json` próprio; `vite.config.ts` separado; CI roda jobs de ambos em paralelo via matrix.
  **Revisão prevista:** F8 (mobile pode justificar split para workspace npm com `mobile/` + `web/`).
  **Referências:** [Planejamento FE §2](../prd/PLANEJAMENTO_FRONTEND_REACT.md)

### Decisão D-002 — openapi-typescript para codegen

**Data:** 2026-04-18
**Status:** decidido
**Contexto:** escolher ferramenta para gerar tipos TypeScript do OpenAPI.
**Opções:**

- **A — openapi-typescript:** gera tipos puros (`paths`, `components`). Leve, zero runtime.
- **B — orval:** gera hooks TanStack Query + tipos. Mais poderoso mas opinionado.
  **Decisão:** openapi-typescript. Hooks escritos à mão → mais controle + menos mágica.
  **Reflexos:** script `npm run codegen`; arquivo gerado `src/api/types.gen.ts`.
  **Referências:** [Dev Setup §8](./11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md)

### Decisão D-003 — TanStack Router v1 file-based

**Data:** 2026-04-18
**Status:** decidido
**Contexto:** roteamento React.
**Opções:** React Router v6, TanStack Router v1, Wouter.
**Decisão:** TanStack Router v1 file-based. Razões: typesafe, integração nativa com TanStack Query, suporte a search params tipados, devtools ótimas.
**Reflexos:** rotas em `src/routes/` com convenção file-based; `routeTree.gen.ts` gerado pelo Vite plugin.
**Referências:** [SAD](./05-FRONTEND-SAD.md)

### Decisão D-004 — Playwright em vez de Cypress

**Data:** 2026-04-18
**Status:** decidido
**Contexto:** E2E testing.
**Decisão:** Playwright. Razões: multi-browser nativo (chromium, webkit, firefox), tracing superior, API moderna, suporte a mobile emulation.
**Reflexos:** suite em `tests/e2e/`; config em `playwright.config.ts`.
**Referências:** [QA Strategy §4](./10-QA-TEST-STRATEGY.md)

### Decisão D-005 — ULID em URLs públicas

**Data:** 2026-04-18 (herdado do backend)
**Status:** decidido
**Contexto:** identificadores em URLs (`/portal/pagamento/<id>`).
**Decisão:** ULID (26 chars) em vez de BIGINT incremental. Razões: não vaza cardinalidade, ordenável por tempo, compatível com URLs.
**Reflexos:** rotas `$parcela_ulid` em TanStack Router; `types.gen.ts` tipa como `string`.
**Referências:** [ADR backend correspondente]

---

## Anexos

### A.1 Template para nova pergunta

Quando surgir uma pergunta nova:

```markdown
### Q-<CAT>-NN — <título curto>

**Contexto:** <1 parágrafo>
**Pergunta:** <uma pergunta clara e respondível>
**Por que precisa saber:** <qual impacto técnico / UX / scope>
**Proposta de default:** <o que fazer se decisão demorar>
**Prioridade:** P0 / P1 / P2 / P3
**Bloqueia:** <sprint ou módulo>
```

Categorias: `BE` (backend), `UX` (produto), `DS` (design system), `INFRA`, `LEGAL`.

### A.2 Links relacionados

- [SAD](./05-FRONTEND-SAD.md)
- [Technical Design](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md)
- [QA Strategy](./10-QA-TEST-STRATEGY.md)
- [Dev Setup](./11-DEV-SETUP-AND-ENGINEERING-STANDARDS.md)
- [Runbook](./12-RUNBOOK-FRONTEND.md)
- [Roadmap](./13-FRONTEND-IMPLEMENTATION-ROADMAP.md)
- [Planejamento FE](../prd/PLANEJAMENTO_FRONTEND_REACT.md) — Apêndices A e B
- [Planejamento BE](../prd/PLANEJAMENTO_BACKEND_APIV1.md) — §11
- [OpenAPI skeleton](../api/openapi-skeleton.yaml)
- [API contract](../api/api-contract.md)
- [API conventions](../api/api-conventions.md)
- [Error envelope](../api/error-envelope.md)

### A.3 Histórico deste documento

| Data       | Versão | Autor        | Mudança                                                             |
| ---------- | ------ | ------------ | ------------------------------------------------------------------- |
| 2026-04-18 | 1.0.0  | Agente QA/FE | Versão inicial — 21 blockers, 14 Qs BE, 14 Qs UX, 10 Qs DS, 30 gaps |
