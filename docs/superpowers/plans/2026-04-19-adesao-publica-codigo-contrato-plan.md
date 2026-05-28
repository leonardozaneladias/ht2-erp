---
title: Adesão Pública via Código do Contrato — Implementation Plan
version: 2.0.0
date: 2026-04-23
status: draft
owner_role: architect
change_during_sprint: false
supersedes: docs/superpowers/plans/2026-04-19-adesao-publica-codigo-turma-plan.md
---

# Adesão Pública via Código do Contrato — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Este plano substitui o antigo `2026-04-19-adesao-publica-codigo-turma-plan.md` após a decisão arquitetural de 2026-04-23 (inversão Contrato↔Turma e novas etapas de wizard).

**Goal:** Habilitar formandos novos a iniciar adesão via **código público do contrato** (ex: `ARTFINAL-USP-MED-2026`) sem login prévio, incluindo etapas explícitas de **escolha de curso + período** (turma dentro do contrato) e **escolha de pacote da categoria `formatura`**. Formandos existentes retomam via login com pré-preenchimento — cobrindo também o cenário "pais de gêmeos" (1 PortalUser → N Formandos, potencialmente em turmas distintas do mesmo ou de outros contratos).

**Architecture:** Abordagem 1 (extensão mínima do SPEC-002): novo middleware `ResolveAdesaoContext` despacha entre Sanctum autenticado e `PublicoContext` anônimo via JWT HS256 (draft_token, TTL 48h, jti em Redis para revogação). O JWT agora carrega **3 ulids de identidade** (`contrato_ulid`, `turma_ulid`, `pacote_ulid`) + `cpf_hash`, impedindo rebind mid-wizard. Commit atômico na etapa final cria `PortalUser + Formando + Adesão + Parcelas + AceiteTermo` em transação única. Dependência forte de Foundation SPECs: Contrato como agregado raiz (`hasMany Turmas`), `codigo_acesso` no contrato, `pacotes.categoria` enum.

**Tech Stack:** Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis · Sanctum · firebase/php-jwt · Pest 4 · React 19 · TanStack Router v1 · TanStack Query v5 · Zustand v5 · Zod v4 · Playwright · MSW

---

## Spec e contexto de referência

Leitura obrigatória antes de começar:

- [`docs/META/PROJECT-STATUS.md`](../../META/PROJECT-STATUS.md) — governança (`status: desenvolvimento` permite breaking changes).
- [`docs/features/SPEC-010-adesao-publica-codigo-contrato.md`](../../features/SPEC-010-adesao-publica-codigo-contrato.md) — spec alvo.
- [`docs/features/foundation/SPEC-F-001-contrato-e-turma.md`](../../features/foundation/SPEC-F-001-contrato-e-turma.md) — inversão da hierarquia e novo modelo de dados.
- [`docs/features/foundation/SPEC-F-004-programacoes-valor.md`](../../features/foundation/SPEC-F-004-programacoes-valor.md), [`SPEC-F-005`](../../features/foundation/SPEC-F-005-descontos-condicoes.md), [`SPEC-F-006`](../../features/foundation/SPEC-F-006-calculo-parcelas.md) — cálculo.
- [`docs/features/foundation/SPEC-F-010-auth-authz.md`](../../features/foundation/SPEC-F-010-auth-authz.md) — claims JWT.
- [`docs/SPEC-RESTRUCTURE-PLAN.md`](../../SPEC-RESTRUCTURE-PLAN.md) — contexto da reorganização.
- [`docs/superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md`](../specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md) — design doc original.

---

## Nota sobre granularidade

> **Versão concisa.** Este plano consolida as decisões arquiteturais e estrutura por Gates. Os passos TDD detalhados (arquivos PHP/TS específicos, diffs de migration, blocos de teste) **serão expandidos pela squad em sessão de sprint planning dedicada** (primeira atividade ao iniciar o primeiro gate). O plano antigo (turma-orientado) tinha 3681 linhas com passos concretos; esta reescrita preserva a estrutura de gates mas deixa os passos como guias — a inversão de modelo invalidou boa parte do detalhamento anterior e refazer os ~40 blocos de código equivalentes aqui seria retrabalho que trava a aprovação da direção.

---

## Organização em Gates

| Gate           | Conteúdo                                                                               |   SP est. |
| -------------- | -------------------------------------------------------------------------------------- | --------: |
| **Pre-Gate 0** | Dependências de pacotes (Sanctum, JWT, Saloon)                                         |         2 |
| **Gate 1**     | Foundation mínima: Contrato agregado, inversão da hierarquia, `pacotes.categoria`      |        12 |
| **Gate 2**     | Refactor SPEC-002 (`evento_ulid` → `contrato_ulid` + `turma_ulid` explícito)           |         8 |
| **Gate 3**     | Backend público (endpoints, middleware, JWT com 3 ulids, commit atômico)               |        14 |
| **Gate 4**     | Frontend público (rotas, store, novos steps `escolher-turma` + `escolher-pacote`, E2E) |        13 |
| **Gate 5**     | Admin CRUD do código de acesso **no contrato**                                         |         3 |
| **Gate 6**     | Telemetria, feature flag, rollout                                                      |         3 |
| **Total**      |                                                                                        | **55 SP** |

---

## Pre-Gate 0 — Dependências de pacotes

Sem mudança estrutural vs plano anterior; apenas checagem de que os pacotes continuam adequados após a decisão da inversão.

### Task 0.1: Instalar Laravel Sanctum

- [ ] `composer require laravel/sanctum:^4.0`
- [ ] `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [ ] `php artisan migrate` (cria `personal_access_tokens`)
- [ ] Configurar `statefulApi()` em `bootstrap/app.php`
- [ ] Commit: `chore(auth): instalar laravel/sanctum para autenticação API`

### Task 0.2: Instalar firebase/php-jwt

- [ ] `composer require firebase/php-jwt:^6.10`
- [ ] Adicionar em `.env.example`: `DRAFT_TOKEN_SECRET=`, `DRAFT_TOKEN_TTL_SECONDS=172800`, `AUTO_LOGIN_TOKEN_TTL_SECONDS=900`
- [ ] Gerar secret local com `bin2hex(random_bytes(32))` e copiar para `.env`
- [ ] Commit: `chore(auth): instalar firebase/php-jwt para draft_token da adesão pública`

### Task 0.3: Instalar Saloon + plugin Laravel

- [ ] `composer require saloonphp/saloon:^3.0 saloonphp/laravel-plugin:^3.0`
- [ ] `php artisan vendor:publish --tag=saloon-config`
- [ ] Commit: `chore(gateway): instalar saloonphp para clientes HTTP do gateway`

---

## Gate 1 — Foundation mínima (inversão Contrato↔Turma + categoria de pacote)

> **Mudança crítica vs plano antigo:** o plano anterior descrevia `contratos.turma_id` FK. Agora é o oposto: `turmas.contrato_id` FK NOT NULL + `codigo_acesso` e `adesao_publica_ativa` no contrato. **Pacotes ganham coluna `categoria`** (enum `formatura|extra`).

### Task 1.1: Migration `create_contratos_table` (novo schema)

**Mudança vs anterior:** remover `foreignId('turma_id')` do contrato; adicionar `codigo_acesso VARCHAR(32) UNIQUE` e `adesao_publica_ativa BOOLEAN DEFAULT true`.

- [ ] `php artisan make:migration create_contratos_table`
- [ ] Schema: `id`, `ulid`, `codigo_acesso` (nullable, unique, VARCHAR(32)), `adesao_publica_ativa` (bool default true), `categoria` VARCHAR(30), `evento_id` (nullable FK), `nome` VARCHAR(150), `status` VARCHAR(20), `meta_formandos` int nullable, `data_inicio` date, `data_fim_adesao` date, flags de responsáveis, `observacoes` text, `timestamps` + `softDeletes`.
- [ ] Índices: `UNIQUE (codigo_acesso)`, índice funcional `CREATE INDEX contratos_codigo_acesso_upper_idx ON contratos (UPPER(codigo_acesso))`, `INDEX (status)`, `INDEX (ulid)`.
- [ ] Commit: `feat(contratos): criar tabela contratos como agregado raiz com codigo_acesso`

### Task 1.2: Migration `alter_turmas_add_contrato_id` (inversão da hierarquia)

**Mudança vs anterior:** antes a migration adicionava `codigo_acesso` em `turmas`. Agora: **remove** essas colunas de `turmas` e **adiciona** `contrato_id FK NOT NULL`.

- [ ] `php artisan make:migration alter_turmas_invert_hierarchy --table=turmas`
- [ ] No `up()`:
    - `Schema::table('turmas', fn ($t) => $t->dropColumn(['codigo_acesso', 'adesao_publica_ativa']))`;
    - `Schema::table('turmas', fn ($t) => $t->foreignId('contrato_id')->constrained('contratos'))`;
    - `DB::statement('DROP INDEX IF EXISTS turmas_codigo_acesso_upper_idx')`.
- [ ] No `down()`: inverso (readiciona colunas, remove FK).
- [ ] Commit: `feat(turmas): inverter hierarquia para turmas.contrato_id NOT NULL`

### Task 1.3: Migration `alter_pacotes_add_categoria`

- [ ] `php artisan make:migration alter_pacotes_add_categoria --table=pacotes`
- [ ] Adicionar `categoria VARCHAR(30) DEFAULT 'formatura'` + `CHECK (categoria IN ('formatura','extra'))`.
- [ ] Índice composto `(contrato_id, categoria)`.
- [ ] Commit: `feat(pacotes): adicionar categoria enum (formatura|extra)`

### Task 1.4: Migration `alter_adesoes_add_contrato_and_public_fields`

**Mudança vs anterior:** além de `contrato_id`, adicionar **também** `turma_id FK NOT NULL` (a escolha explícita do formando). `evento_id` vai embora.

- [ ] `php artisan make:migration alter_adesoes_add_contrato_and_public_fields --table=adesoes`
- [ ] Ops:
    - `$table->foreignId('portal_user_id')->nullable()->change();` (antes NOT NULL)
    - `$table->foreignId('contrato_id')->constrained('contratos');`
    - `$table->foreignId('turma_id')->constrained('turmas');`
    - `$table->foreignId('pacote_id')->constrained('pacotes');` (se ainda não existir)
    - `$table->dropForeign('adesoes_evento_id_foreign'); $table->dropColumn('evento_id');`
    - `$table->string('draft_token_hash', 64)->nullable();`
    - `$table->string('origem_adesao', 30)->default('autenticada');`
- [ ] Índices: `(contrato_id, status)`, `(turma_id)`, `(pacote_id)`, `(origem_adesao)`.
- [ ] Commit: `feat(adesoes): refactor para contrato_id + turma_id explícitos + metadados públicos`

### Task 1.5: Model `Contrato` (casts + relações corretas)

- [ ] Criar `app/Models/Cadastro/Contrato.php` com `HasUlid`, `LogsActivity`, `SoftDeletes`.
- [ ] `$fillable`: `codigo_acesso`, `adesao_publica_ativa`, `categoria`, `evento_id`, `nome`, `status`, `meta_formandos`, `data_inicio`, `data_fim_adesao`, flags de responsáveis, `observacoes`.
- [ ] Relações: `hasMany(Turma::class)`, `hasMany(Pacote::class)`, `hasMany(Adesao::class)`, `hasMany(CondicaoPagamento::class)`, `belongsTo(Evento::class)`.
- [ ] Scopes: `scopeComCodigo(Builder $q, string $codigo)` com `whereRaw('UPPER(codigo_acesso) = ?', [strtoupper($codigo)])`.
- [ ] Factory + seeder (contrato com 2 turmas em semestres diferentes + 2 pacotes formatura + 1 pacote extra).
- [ ] Testes Pest: gera ULID, defaults corretos, `codigo_acesso` unique case-insensitive, relação com turmas.
- [ ] Commit: `feat(contratos): model Contrato com casts e relações`

### Task 1.6: Model `Turma` (adaptação — inversão)

- [ ] `app/Models/Cadastro/Turma.php`:
    - Remover `codigo_acesso`, `adesao_publica_ativa` do `$fillable`.
    - Adicionar `contrato_id`.
    - Relação: `belongsTo(Contrato::class)`, `belongsTo(Curso::class)`, `hasMany(Adesao::class)`.
    - Accessor `getRotuloAttribute(): string` → `"{$this->curso->nome} {$this->ano_formatura}/{$this->semestre_formatura}"`.
- [ ] Testes: `turma pertence a um contrato` (NOT NULL), `rotulo formatado`.
- [ ] Commit: `feat(turmas): adaptar model para pertencer a contrato`

### Task 1.7: Model `Pacote` (adicionar cast + scope)

- [ ] Adicionar `categoria` ao `$fillable`.
- [ ] Cast para enum `App\Enums\Pacotes\CategoriaPacote`.
- [ ] Scope `scopeFormatura(Builder $q)` → `where('categoria', 'formatura')`.
- [ ] Testes: cast aplicado, scope filtra corretamente.
- [ ] Commit: `feat(pacotes): categoria enum no model + scope formatura`

### Task 1.8: Rate Limiter `adesao-publica`

- [ ] `app/Providers/AppServiceProvider.php` → registrar limiters nomeados: `adesao-publica-show` (10/min/IP), `adesao-publica-iniciar` (5/min/IP), `adesao-publica-simular` (20/min/IP), `adesao-publica-commit` (3/min/IP).
- [ ] Testes feature testando header `Retry-After`.
- [ ] Commit: `feat(rate-limit): limiters nomeados para endpoints públicos de adesão`

### Task 1.9: Verificação Gate 1

- [ ] `php artisan test --filter='Contrato|Turma|Pacote'`
- [ ] `./vendor/bin/pint` + `./vendor/bin/phpstan analyse`
- [ ] `php artisan migrate:fresh --seed` roda sem erro.
- [ ] Smoke: seeder cria contrato com `codigo_acesso`, 2 turmas vinculadas, 2 pacotes `formatura` + 1 `extra`.

---

## Gate 2 — Refactor SPEC-002 (`evento_ulid` → `contrato_ulid` + `turma_ulid`)

**Objetivo:** mover o contexto do wizard autenticado de "evento" para "contrato + turma", absorvendo automaticamente a inversão do Gate 1.

### Task 2.1: Enums `OrigemAdesao` e `TipoSolicitante`

- [ ] Criar `app/Enums/Adesao/OrigemAdesao.php`:
    ```php
    enum OrigemAdesao: string {
        case AUTENTICADA = 'autenticada';
        case PUBLICA_CODIGO_CONTRATO = 'publica_codigo_contrato';
        case ADMIN_MANUAL = 'admin_manual';
    }
    ```
- [ ] Criar `app/Enums/Adesao/TipoSolicitante.php` (`PROPRIO`, `RESPONSAVEL`).
- [ ] Criar `app/Enums/Pacotes/CategoriaPacote.php` (`FORMATURA`, `EXTRA`).
- [ ] Adicionar `labels()` traduzidos.
- [ ] Testes: cada enum tem todos os cases + labels.
- [ ] Commit: `feat(enums): OrigemAdesao, TipoSolicitante, CategoriaPacote`

### Task 2.2: Atualizar Model `Adesao`

- [ ] Adicionar `contrato_id`, `turma_id`, `draft_token_hash`, `origem_adesao` ao `$fillable`.
- [ ] Cast `origem_adesao` para enum.
- [ ] Relações: `belongsTo(Contrato::class)`, `belongsTo(Turma::class)`, `belongsTo(Pacote::class)`.
- [ ] Remover `belongsTo(Evento::class)` direto (continuar via `contrato.evento`).
- [ ] Testes: relações corretas, origem default `autenticada`.
- [ ] Commit: `feat(adesoes): model com contrato_id + turma_id + origem_adesao enum`

### Task 2.3: Atualizar rotas e Controllers/Requests/Resources existentes do SPEC-002

- [ ] Trocar todas assinaturas `evento_ulid` → `contrato_ulid` em `routes/api/v1.php`, Controllers autenticados, FormRequests, Resources.
- [ ] Onde `evento_ulid` era usado para resolver preços/condições, passar a resolver via `contrato` (programações de valor ficam em pacote, mas contexto vem do contrato).
- [ ] Payload do wizard autenticado ganha `turma_ulid` obrigatório a partir da etapa que hoje escolhe pacote (alinhar com SPEC-002 atualizada).
- [ ] Testes feature: todos os testes do SPEC-002 passam após o refactor.
- [ ] Commit: `refactor(wizard): contrato_ulid + turma_ulid no lugar de evento_ulid`

### Task 2.4: Verificação Gate 2

- [ ] `php artisan test --filter=Wizard` passa 100%.
- [ ] Busca no repo não retorna mais uso de `evento_ulid` em contexto de adesão: `rg 'evento_ulid' app/ routes/` = vazio ou só em Evento puro (RSVP/convites).

---

## Gate 3 — Backend público (core da SPEC-010)

### Task 3.1: `DraftTokenService` (JWT HS256 com 3 ulids)

- [ ] Criar `app/Services/Adesao/DraftTokenService.php` com métodos `encode(DraftTokenClaims)` e `decode(string): DraftTokenClaims`.
- [ ] Claims: `sub`, `contrato_ulid`, `turma_ulid`, `pacote_ulid`, `tipo_solicitante`, `cpf_hash`, `iat`, `exp`, `jti`.
- [ ] Revogação via Redis set `draft_token:revoked:{jti}`.
- [ ] Testes Pest Unit:
    - encode+decode roundtrip preserva todas as 3 ulids;
    - assinatura inválida → `DraftTokenInvalidoException`;
    - `exp` passado → `DraftTokenExpiradoException`;
    - jti revogado → `DraftTokenRevogadoException`;
    - secret errado → exception;
    - claim `pacote_ulid` ausente → exception (novo teste);
    - `cpf_hash` é sha256 determinístico (mesmo CPF → mesmo hash).
- [ ] Commit: `feat(adesao): DraftTokenService com claims contrato+turma+pacote`

### Task 3.2: DTO `DraftTokenClaims` (spatie/laravel-data)

- [ ] `app/Data/Adesao/DraftTokenClaims.php` readonly, com `toArray()` produzindo claims no formato esperado.
- [ ] Testes Unit: serialização roundtrip.
- [ ] Commit: `feat(adesao): DraftTokenClaims DTO`

### Task 3.3: Middleware `ResolveAdesaoContext`

- [ ] `app/Http/Middleware/ResolveAdesaoContext.php`:
    - Se Sanctum autenticou → `$request->attributes['adesao_context']` = contexto autenticado.
    - Senão, lê header `X-Adesao-Draft-Token` → `DraftTokenService::decode()` → monta contexto público com `contrato_ulid`, `turma_ulid`, `pacote_ulid`, `cpf_hash`.
- [ ] Middleware `ValidateDraftTokenBindings`: compara payload vs claims; falha qualquer mismatch com `CpfTrocadoMidWizardException` / `TurmaNaoPertenceAoContratoException` / `PacoteNaoPertenceAoContratoException`.
- [ ] Testes feature: token válido autentica, ausência de header em rota pública = 401, revogado = 401, mismatch de contrato/turma/pacote = 422.
- [ ] Commit: `feat(adesao): middleware ResolveAdesaoContext + ValidateDraftTokenBindings`

### Task 3.4: Exceptions de domínio

- [ ] Criar em `app/Exceptions/Adesao/`:
      `MustLoginException` (409), `DraftTokenExpiradoException` (401), `DraftTokenInvalidoException` (401), `DraftTokenRevogadoException` (401), `CpfTrocadoMidWizardException` (422), `TurmaNaoPertenceAoContratoException` (422), `PacoteNaoPertenceAoContratoException` (422), `TermoVersaoDesatualizadaException` (409), `ContratoSemTurmasDisponiveisException` (412), `AdesaoJaExistenteException` (409), `CpfJaRegistradoException` (409).
- [ ] Cada exception renderizável via `render()` com payload `{ error, message, details? }`.
- [ ] Commit: `feat(adesao): exceptions de domínio para fluxo público`

### Task 3.5: `ResolveContratoPorCodigoAction`

- [ ] `app/Actions/Adesao/ResolveContratoPorCodigoAction.php` com `__invoke(string $codigo): ContratoPublicoData`.
- [ ] Consulta: `Contrato::comCodigo($codigo)->with(['turmas.curso', 'pacotes' => fn ($q) => $q->formatura(), 'condicoesPagamento'])->firstOrFail()`.
- [ ] Validações: status ativo, `adesao_publica_ativa`, `data_fim_adesao >= hoje`, turmas não vazias (412 se sim).
- [ ] Retorna `ContratoPublicoData` (com `turmas_disponiveis[]`, `pacotes_formatura[]`, `condicoes_pagamento[]`, `termo_vigente`).
- [ ] Testes Feature cobrem 6 cenários do `ValidarCodigoContratoTest`.
- [ ] Commit: `feat(adesao): ResolveContratoPorCodigoAction`

### Task 3.6: `IniciarAdesaoPublicaAction`

- [ ] `app/Actions/Adesao/IniciarAdesaoPublicaAction.php`:
    - Valida que `turma_ulid` pertence ao `contrato_id`;
    - Valida que `pacote_ulid` pertence ao `contrato_id` E `categoria='formatura'`;
    - Consulta PortalUser por CPF — se existe: lança `MustLoginException` com login_hint mascarado;
    - Se novo: emite `draft_token` via `DraftTokenService` e retorna `{draft_token, expires_at}`.
- [ ] Testes cobrem cenários do `IniciarAdesaoPublicaTest` + `EscolherTurmaTest` + `EscolherPacoteTest`.
- [ ] Commit: `feat(adesao): IniciarAdesaoPublicaAction com validação de turma+pacote`

### Task 3.7: `CommitAdesaoPublicaAction` (transação atômica — 14 passos)

- [ ] `app/Actions/Adesao/CommitAdesaoPublicaAction.php`:
    - Abre `DB::transaction()`.
    - Valida claims vs payload (3 ulids + cpf_hash).
    - Recheck PortalUser, recheck `adesao_publica_ativa`.
    - Cria `PortalUser(status='incompleto')`, `Formando(turma_id=turma_ulid escolhido)`, pivô se necessário.
    - Cria `Adesao(contrato_id, turma_id, pacote_id, origem=PUBLICA_CODIGO_CONTRATO, status=pendente_pagamento)`.
    - Cria `Parcelas` via `CalcularPlanoParcelasAction` (F-006).
    - Cria `AceiteTermo` (F-007).
    - Revoga jti.
    - Emite `auto_login_token` Sanctum (bound IP+UA, 15min).
    - Cria `PagamentoIntent` (F-009).
    - Enfileira jobs `EnviarEmailAtivacaoAdesaoJob` + `ConsolidarTermoPdfJob`.
    - Log auditoria (F-011) com `codigo_contrato_usado`.
- [ ] Testes Feature (14 cenários do `CommitAdesaoPublicaTest`): commit proprio, responsavel, pais de gêmeos (mesmo contrato + contratos distintos), CPF race, idempotência, 3 tipos de mid-wizard mismatch, termo desatualizado, idade <18, ≥18, cupom inválido, rate limit, adesao já existente, contrato desabilitado mid-flow.
- [ ] Commit: `feat(adesao): CommitAdesaoPublicaAction atômico + testes`

### Task 3.8: Controller `AdesaoPublicaController` + FormRequests

- [ ] `app/Http/Controllers/Api/V1/Publico/AdesaoPublicaController.php` com métodos `show`, `iniciar`, `simular`, `commit` (cada um no máximo 5-7 linhas — delega para action).
- [ ] FormRequests:
    - `IniciarAdesaoPublicaRequest` (turma+pacote obrigatórios, CPF válido).
    - `SimularAdesaoPublicaRequest` (regras método↔parcelas: PIX=1x, cartão≥2x, PIX bloqueado em demais).
    - `CommitAdesaoPublicaRequest` (3 ulids + responsáveis + plano + termo).
- [ ] Rotas em `routes/api/v1.php` sob prefix `adesao/publico/{codigo-contrato}` (show, iniciar, simular, commit).
- [ ] Testes feature: controller thin, delegação correta, middleware chain respeitada.
- [ ] Commit: `feat(adesao): AdesaoPublicaController + FormRequests`

### Task 3.9: Verificação Gate 3

- [ ] 100% dos testes Feature e Unit de `app/Actions/Adesao/`, `app/Services/Adesao/`, `app/Http/Controllers/Api/V1/Publico/` passam.
- [ ] `./vendor/bin/phpstan analyse` sem warnings nos arquivos novos.
- [ ] `./vendor/bin/pint` limpa.
- [ ] Cobertura: `CommitAdesaoPublicaAction` e `DraftTokenService` 100%.

---

## Gate 4 — Frontend público (rotas + novos steps + E2E)

### Task 4.1: Store `adesao-publica-store` (Zustand + persist)

- [ ] `resources/spa/src/stores/adesao-publica-store.ts` com campos: `codigo_contrato`, `contrato_ulid`, `turma_ulid`, `pacote_ulid`, `tipo_solicitante`, `draft_token`, `draft_token_exp`, form data por etapa, ações `setContrato`, `setTurma`, `setPacote`, `reset`.
- [ ] Persist em `sessionStorage` (nunca localStorage).
- [ ] Testes unitários: persistência, reset ao trocar de contrato, migração de token expirado.
- [ ] Commit: `feat(adesao-publica): store Zustand com 3 ulids`

### Task 4.2: Axios interceptor para `X-Adesao-Draft-Token`

- [ ] Estender `resources/spa/src/api/client.ts`: se store tem token válido, injeta header; limpa em 401.
- [ ] Commit: `feat(adesao-publica): interceptor injeta draft_token`

### Task 4.3: Hooks TanStack Query

- [ ] `resources/spa/src/api/hooks/use-adesao-publica.ts` com:
    - `useContratoPublico(codigo)` — GET `/publico/{codigo-contrato}`; trata 404/403/412 com mensagens específicas;
    - `useIniciarPublico()` — mutation;
    - `useSimularPublico()` — mutation com refetch em cross-field change;
    - `useCommitPublico()` — mutation com idempotency key gerada;
    - `useAdminCodigoContrato()` — PATCH/DELETE admin.
- [ ] Tipos importados de `api/types.gen.ts` (gerados do OpenAPI).
- [ ] Testes unit: hook states correto.
- [ ] Commit: `feat(adesao-publica): hooks TanStack Query`

### Task 4.4: Rotas `/adesao` e `/adesao/$codigo`

- [ ] `resources/spa/src/routes/adesao/index.tsx` — form de código.
- [ ] `resources/spa/src/routes/adesao/$codigo.tsx` — landing do contrato; renderiza `contrato-landing.tsx`.
- [ ] Ambas públicas (sem auth guard); `<meta name="robots" content="noindex">`.
- [ ] Testes unit: render e navegação.
- [ ] Commit: `feat(adesao-publica): rotas /adesao e /adesao/:codigo`

### Task 4.5: Novos componentes de step (NOVO vs plano antigo)

- [ ] `components/adesao-publica/contrato-landing.tsx` — header, CTA "começar", resumo visual.
- [ ] `components/adesao-publica/escolher-turma-step.tsx` — lista `turmas_disponiveis[]`, seleção única; **skip automático quando `turmas_disponiveis.length === 1`** (OQ-NOVA-1).
- [ ] `components/adesao-publica/escolher-pacote-step.tsx` — lista `pacotes_formatura[]`, seleção única, preço vigente visível.
- [ ] `components/adesao-publica/quem-e-voce-step.tsx` — radio proprio/responsavel.
- [ ] `components/adesao-publica/must-login-dialog.tsx` — 409 MustLogin.
- [ ] `components/adesao-publica/prefill-toast.tsx` — feedback pós-login.
- [ ] Schemas Zod: `codigo.schema.ts`, `escolher-turma.schema.ts`, `escolher-pacote.schema.ts`, `quem-e-voce.schema.ts`, `dados-formando.schema.ts`, `plano-pagamento.schema.ts` (com regras cross-field método↔parcelas).
- [ ] Testes unit dos schemas + testes de integração dos components com MSW.
- [ ] Commit: `feat(adesao-publica): steps escolher-turma + escolher-pacote + demais components`

### Task 4.6: Integração do wizard existente com modo `publico`

- [ ] Estender `components/wizard/wizard-shell.tsx` com prop `mode: 'autenticado' | 'publico'` e mapa de etapas que inclui os novos steps na ordem correta.
- [ ] Conectar store e hooks.
- [ ] Commit: `feat(wizard): modo publico com novos steps`

### Task 4.7: E2E Playwright

- [ ] `tests/e2e/adesao-via-codigo-contrato.spec.ts` com 4 specs:
    - URL direta → escolher turma → escolher pacote → wizard → commit → auto-login → pagamento;
    - Formulário `/adesao` + código digitado → mesmo fluxo;
    - Pais de gêmeos mesmo contrato (2 commits, turmas diferentes);
    - Pais de gêmeos contratos distintos (2 commits, códigos diferentes).
- [ ] `tests/e2e/admin-contrato-codigo-acesso.spec.ts` — admin define, regenera, remove código.
- [ ] Commit: `test(e2e): fluxo público completo adesão por código de contrato`

### Task 4.8: Verificação Gate 4

- [ ] `npm run test` (unit + integration) passa.
- [ ] `npx playwright test` passa.
- [ ] Smoke manual: percorrer fluxo completo em dev seed.

---

## Gate 5 — Admin CRUD do código de acesso **no contrato**

> **Mudança vs plano anterior:** controller era `TurmaCodigoAcessoController`. Agora é `ContratoCodigoAcessoController`.

### Task 5.1: `ContratoCodigoAcessoController` com testes

- [ ] Criar `app/Http/Controllers/Api/V1/Admin/ContratoCodigoAcessoController.php` com métodos `update(PATCH)` e `destroy(DELETE)`.
- [ ] FormRequest com regex `^[A-Z0-9-]{4,32}$` + unique case-insensitive.
- [ ] Suporte a `{ "generate": true }` → gera `ARTFINAL-{INST_ABBR}-{CURSO_ABBR}-{ANO}` com sufixo 4 chars em colisão.
- [ ] Rota `PATCH /api/v1/admin/contratos/{contrato:ulid}/codigo-acesso`, `DELETE /api/v1/admin/contratos/{contrato:ulid}/codigo-acesso`, `PATCH /api/v1/admin/contratos/{contrato:ulid}/adesao-publica`.
- [ ] Policy: só admin com `can:manage,contrato`.
- [ ] Testes Feature (6 cenários do `ContratoCodigoAcessoTest`): define, regenera, remove, unicidade global, apenas admin, regex inválido 422.
- [ ] Commit: `feat(admin): CRUD codigo_acesso no contrato`

### Task 5.2: Verificação Gate 5

- [ ] `php artisan test --filter=ContratoCodigoAcesso` passa.
- [ ] Smoke admin: criar contrato, gerar código, regenerar, remover.

---

## Gate 6 — Telemetria, Feature Flag, Rollout

### Task 6.1: Feature flag `adesao_publica_codigo_contrato_enabled`

- [ ] Config em `config/features.php` + env `FEATURE_ADESAO_PUBLICA_ENABLED=false` default.
- [ ] Middleware `EnsureAdesaoPublicaEnabled` nas rotas públicas.
- [ ] Testes: flag desligada → 404, ligada → comportamento normal.
- [ ] Commit: `feat(adesao-publica): feature flag de rollout`

### Task 6.2: Breadcrumbs Sentry por etapa

- [ ] Em cada endpoint público, registrar breadcrumb `{ category: 'adesao_publica', message: 'step_x', data: {contrato_ulid, turma_ulid, pacote_ulid, ...redacted} }` — CPF sempre redacted.
- [ ] Commit: `feat(observability): breadcrumbs Sentry no fluxo público`

### Task 6.3: Alertas Sentry para 404/enumeration

- [ ] Configurar regra Sentry: IP com > 100 respostas 404 em `/adesao/publico/*` em 1h dispara alerta.
- [ ] Commit: `feat(observability): alerta enumeração de códigos`

### Task 6.4: Documentar rollout

- [ ] Criar `docs/runbooks/rollout-adesao-publica.md` com steps: seed de um contrato real, teste interno, ativação para 1 turma, monitoramento, ampliar.
- [ ] Commit: `docs(runbook): rollout adesão pública via código contrato`

### Task 6.5: Verificação Gate 6

- [ ] Feature flag funciona.
- [ ] Breadcrumbs aparecem em teste manual (Sentry dev).
- [ ] Runbook revisado pelo arquiteto.

---

## Verificação final de integração

Após todos os gates:

```bash
# Zero referências ao nome antigo do SPEC
rg -l "SPEC-010-adesao-publica-codigo-turma" docs/ app/ resources/

# codigo_acesso só em contexto de contrato
rg "codigo_acesso" app/ | rg -v "contrato"   # deve ficar vazio ou só em Model comments

# Todos os testes passam
php artisan test

# Qualidade
./vendor/bin/pint --test
./vendor/bin/phpstan analyse

# Frontend
npm run test
npx playwright test
npm run build
```

---

## Decisões travadas (summary)

1. **Inversão da hierarquia** — `Contrato hasMany Turmas` (novo); código de acesso no contrato.
2. **3 ulids no draft_token** — `contrato_ulid` + `turma_ulid` + `pacote_ulid` + `cpf_hash`. Mutação de qualquer um = 422.
3. **Categoria de pacote no wizard público** — apenas `formatura`; `extra` só no portal autenticado pós-adesão.
4. **Regras por método de pagamento** — PIX=1x com -10%, cartão≥2x, PIX bloqueado em demais; alinhadas com SPEC-F-005/F-006.
5. **Contrato com 1 turma** — UI pula etapa de escolha (skip com pré-seleção visual).
6. **Pais de gêmeos** — suporta mesmo contrato (turmas diferentes) ou contratos distintos.
7. **Breaking changes** — permitidos nesta fase (`PROJECT-STATUS: desenvolvimento`).

---

## Fora de escopo deste plano

- Implementação de categoria `extra` (convites, mesas premium, combos) — backlog para SPEC futura pós-adesão.
- Migração de dados reais — projeto em desenvolvimento, sem dados produtivos.
- Telemetria avançada além do básico (Sentry breadcrumbs) — pode vir em sprint de hardening.

---

> **Nota final.** Este plano é uma **versão concisa de 600 linhas** que consolida as mudanças arquiteturais da decisão de 2026-04-23 (inversão Contrato↔Turma). O plano antigo (turma-orientado, 3681 linhas) continha passos TDD detalhados com blocos de código completos; após a inversão do modelo, esses blocos exigiriam reescrita integral — optamos por preservar a **estrutura de gates e tasks** com descrições claras do resultado esperado, e deixar que a squad expanda os steps TDD **na sessão de sprint planning dedicada ao início de cada gate**. Isso mantém o plano navegável e atualizável sem o overhead de reescrever 3000+ linhas de código de referência que seriam revisados de novo pelo TDD real.
