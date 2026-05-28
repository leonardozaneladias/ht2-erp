---
titulo: Modelo de Dados — Portal ArtFinal v2 (API v1)
versao: 1.0.0
data: 2026-04-17
status: Proposta técnica pronta para execução em F1
audiencia: Engenharia Laravel, DBA, QA, SRE
base:
    - docs/prd/PLANEJAMENTO_BACKEND_APIV1.md (seções 4, 5, 13, Apêndice C)
    - docs/prd/PRD_v4.md (seções 1–4)
    - docs/prd/REGRAS_NEGOCIO.md
idioma: pt-BR
---

# Modelo de Dados — Portal ArtFinal v2

Este documento descreve a totalidade das tabelas do MVP da plataforma, organizadas em **bounded contexts** (blocos A–H). Para cada tabela estão listados: propósito, campos (tipo PostgreSQL 16, nullable, default, descrição, CHECKs), FKs com política de delete, índices (inclusive únicos parciais), enums aceitos, relacionamentos Eloquent, estratégia de snapshot JSONB quando aplicável e um exemplo real de linha em JSON.

## Convenções gerais

- **PostgreSQL 16.** Toda tabela possui `id BIGSERIAL PRIMARY KEY` (interno, nunca exposto) e `ulid CHAR(26) UNIQUE` (público, usado em rotas, API e logs).
- **Timezones.** Todo campo temporal é `TIMESTAMPTZ`. Timestamps padrão: `created_at`, `updated_at` via `timestampsTz()`.
- **Valores monetários.** Armazenados em `INTEGER` (centavos). Nunca `NUMERIC`, `FLOAT` ou `DOUBLE`.
- **Chaves estrangeiras.** `BIGINT`, `ON DELETE RESTRICT` por padrão. Exceções documentadas (por exemplo, `ON DELETE SET NULL` em referências opcionais).
- **Enums.** Materializados em PHP (`Backed Enum`). No banco aparecem como `VARCHAR(20|30)` + `CHECK` constraint com a lista de valores válidos. Defesa em profundidade. **Índice único de todos os enums do projeto:** [`docs/architecture/enums-roadmap.md`](../architecture/enums-roadmap.md).
- **Snapshots.** Dados comerciais, regras e configurações imutáveis ficam em colunas `JSONB` (`snapshot_comercial`, `snapshot_regra`, `payload`, `config_json`, `regra_elegibilidade`).
- **Soft delete.** Proibido em entidades transacionais (`adesoes`, `pagamentos`, `convites`, `reservas_assentos`, `pedidos_extras`, `votos`, `webhook_eventos`, `activity_log`). Usa-se **estado** (ver enums). Permitido em `produtos`, `pacotes`, `templates_notificacao`, `enquetes` (quando rascunho).
- **Tracing.** Colunas `correlation_id CHAR(26)` em `convites`, `rsvp_historico`, `reservas_assentos`, `pedidos_extras`, `pagamentos`, `webhook_eventos` — preenchidas no primeiro contato externo e propagadas entre jobs/listeners.
- **Índices.** Criados para toda FK, campos de filtro frequente, campos de sort padrão e combinações usadas em dashboard.
- **Append-only.** `activity_log` (via `spatie/laravel-activitylog`) e `webhook_eventos` nunca recebem `UPDATE` exceto nos campos de status de processamento.

---

## Blocos por bounded context

- [Bloco A — Identidade e Acesso](#bloco-a--identidade-e-acesso-f1)
- [Bloco B — Cadastro estrutural](#bloco-b--cadastro-estrutural-f1f2)
- [Bloco C — Comercial e Adesão](#bloco-c--comercial-e-adesão-f2)
- [Bloco D — Convites e RSVP](#bloco-d--convites-e-rsvp-f4)
- [Bloco E — Seating](#bloco-e--seating-f5)
- [Bloco F — Extras e Pagamentos Operacionais](#bloco-f--extras-e-pagamentos-operacionais-f6)
- [Bloco G — Engajamento (Enquetes)](#bloco-g--engajamento-enquetes-f6)
- [Bloco H — Comunicação](#bloco-h--comunicação-f4f6)
- [Infraestrutura transversal](#infraestrutura-transversal)

---

## Bloco A — Identidade e Acesso (F1)

Bounded context: `Acesso`. Propósito: autenticação e autorização. Tokens Sanctum vivem aqui. Permissões via `spatie/laravel-permission` em tabelas próprias.

### A.1 `admin_users`

**Propósito.** Usuário do backoffice. Login via guard `admin`, sessão baseada em cookie.

| Campo               | Tipo           | Nullable | Default | Descrição                |
| ------------------- | -------------- | -------- | ------- | ------------------------ |
| `id`                | `BIGSERIAL`    | não      | —       | Chave primária interna   |
| `ulid`              | `CHAR(26)`     | não      | —       | Identificador público    |
| `nome`              | `VARCHAR(120)` | não      | —       | Nome completo            |
| `email`             | `VARCHAR(150)` | não      | —       | Email único, login       |
| `email_verified_at` | `TIMESTAMPTZ`  | sim      | null    | Marcado após confirmação |
| `password`          | `VARCHAR(255)` | não      | —       | Hash Bcrypt/Argon2id     |
| `ativo`             | `BOOLEAN`      | não      | `true`  | Flag de bloqueio lógico  |
| `mfa_secret`        | `VARCHAR(128)` | sim      | null    | TOTP opcional            |
| `ultimo_login_at`   | `TIMESTAMPTZ`  | sim      | null    | Auditoria rápida         |
| `remember_token`    | `VARCHAR(100)` | sim      | null    | Laravel remember-me      |
| `created_at`        | `TIMESTAMPTZ`  | não      | `now()` | —                        |
| `updated_at`        | `TIMESTAMPTZ`  | não      | `now()` | —                        |

**FKs.** nenhuma (tabela raiz de identidade).

**Índices.**

- `UNIQUE (ulid)`
- `UNIQUE (email)`
- `INDEX (ativo)`

**CHECK.** nenhum.

**Enums.** nenhum.

**Relacionamentos.**

- `HasMany` → `activity_log` (via `causer_id` polimórfico).
- `BelongsToMany` → `roles` (Spatie: `model_has_roles`, guard `admin`).

**Exemplo JSON.**

```json
{
    "id": 1,
    "ulid": "01J5K2N7QMHV1FJZ8H0PR3RV9C",
    "nome": "Ana Oliveira",
    "email": "ana.oliveira@artfinal.com.br",
    "email_verified_at": "2026-04-10T13:42:00-03:00",
    "password": "$2y$12$....",
    "ativo": true,
    "mfa_secret": null,
    "ultimo_login_at": "2026-04-17T09:15:00-03:00"
}
```

### A.2 `portal_users`

**Propósito.** Usuário do Portal (formando ou comissão). Autenticado via Sanctum (cookie SPA ou Bearer mobile). `tipo` diferencia papel.

| Campo                       | Tipo           | Nullable | Default      | Descrição                                            |
| --------------------------- | -------------- | -------- | ------------ | ---------------------------------------------------- |
| `id`                        | `BIGSERIAL`    | não      | —            | —                                                    |
| `ulid`                      | `CHAR(26)`     | não      | —            | —                                                    |
| `nome`                      | `VARCHAR(120)` | não      | —            | —                                                    |
| `email`                     | `VARCHAR(150)` | não      | —            | Único                                                |
| `cpf`                       | `VARCHAR(14)`  | sim      | null         | Formato `000.000.000-00`                             |
| `telefone`                  | `VARCHAR(30)`  | sim      | null         | E.164                                                |
| `password`                  | `VARCHAR(255)` | não      | —            | Hash                                                 |
| `email_verified_at`         | `TIMESTAMPTZ`  | sim      | null         | —                                                    |
| `tipo`                      | `VARCHAR(20)`  | não      | `'formando'` | `formando` \| `comissao` \| `responsavel_financeiro` |
| `ativo`                     | `BOOLEAN`      | não      | `true`       | —                                                    |
| `ultimo_login_at`           | `TIMESTAMPTZ`  | sim      | null         | —                                                    |
| `remember_token`            | `VARCHAR(100)` | sim      | null         | —                                                    |
| `created_at` / `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                                    |

**FKs.** nenhuma (alinha com `formandos` via `HasMany` no sentido inverso).

**Índices.**

- `UNIQUE (ulid)`, `UNIQUE (email)`, `UNIQUE (cpf)` (parcial: `WHERE cpf IS NOT NULL`)
- `INDEX (tipo)`
- `INDEX (ativo)`

**CHECK.** `tipo IN ('formando','comissao','responsavel_financeiro')`.

**Enums.** `TipoPortalUser`.

**Relacionamentos.**

- `HasMany` → `formandos`
- `MorphMany` → `personal_access_tokens`
- `BelongsToMany` → `roles` (Spatie, guard `sanctum`)

**Exemplo JSON.**

```json
{
    "id": 42,
    "ulid": "01J5K3B5GTYV8E2F1W0M8P2XQA",
    "nome": "Mariana Souza",
    "email": "mariana@usp.br",
    "cpf": "123.456.789-00",
    "telefone": "+55 11 99876-5432",
    "tipo": "formando",
    "ativo": true,
    "ultimo_login_at": "2026-04-16T21:07:00-03:00"
}
```

### A.3 `comissao_users`

**Propósito.** Perfil especializado de membro da comissão. No MVP pode ser um `portal_user` com `tipo='comissao'` e role `comissao`; tabela mantida para extensão (metas, atribuições específicas) e evitar breaking change futuro.

| Campo                       | Tipo          | Nullable | Default    | Descrição                                                   |
| --------------------------- | ------------- | -------- | ---------- | ----------------------------------------------------------- |
| `id`                        | `BIGSERIAL`   | não      | —          | —                                                           |
| `ulid`                      | `CHAR(26)`    | não      | —          | —                                                           |
| `portal_user_id`            | `BIGINT`      | não      | —          | FK → `portal_users(id)`                                     |
| `turma_id`                  | `BIGINT`      | não      | —          | FK → `turmas(id)`                                           |
| `cargo`                     | `VARCHAR(30)` | não      | `'membro'` | `presidente`, `vice`, `financeiro`, `comunicacao`, `membro` |
| `ativo`                     | `BOOLEAN`     | não      | `true`     | —                                                           |
| `created_at` / `updated_at` | `TIMESTAMPTZ` | não      | `now()`    | —                                                           |

**FKs.** `portal_user_id` → `portal_users(id)` ON DELETE RESTRICT. `turma_id` → `turmas(id)` ON DELETE RESTRICT.

**Índices.**

- `UNIQUE (portal_user_id, turma_id)` — comissão única por turma
- `INDEX (turma_id, cargo)`

**CHECK.** `cargo IN ('presidente','vice','financeiro','comunicacao','membro')`.

### A.4 `convidado_access_tokens`

**Propósito.** Tokens de acesso temporário de convidado (fluxo tokenizado). **Apenas o `token_hash` é persistido.** Uso: RSVP público, eventual seleção de assento pelo convidado.

| Campo                       | Tipo          | Nullable | Default | Descrição                             |
| --------------------------- | ------------- | -------- | ------- | ------------------------------------- |
| `id`                        | `BIGSERIAL`   | não      | —       | —                                     |
| `ulid`                      | `CHAR(26)`    | não      | —       | —                                     |
| `convite_id`                | `BIGINT`      | não      | —       | FK → `convites(id)` ON DELETE CASCADE |
| `token_hash`                | `CHAR(64)`    | não      | —       | `sha256(token_bruto)`                 |
| `expires_at`                | `TIMESTAMPTZ` | não      | —       | TTL (default 30 dias)                 |
| `last_used_at`              | `TIMESTAMPTZ` | sim      | null    | Último uso bem-sucedido               |
| `last_ip`                   | `INET`        | sim      | null    | Último IP (mascarado em logs)         |
| `created_at` / `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —                                     |

**Índices.** `UNIQUE (token_hash)`, `INDEX (convite_id)`, `INDEX (expires_at)`.

### A.5 `personal_access_tokens`

**Propósito.** Tabela padrão do Laravel Sanctum. Gera tokens por `device_name` para mobile. `abilities` como JSON.

| Campo                       | Tipo           | Nullable | Default | Descrição           |
| --------------------------- | -------------- | -------- | ------- | ------------------- |
| `id`                        | `BIGSERIAL`    | não      | —       | —                   |
| `tokenable_type`            | `VARCHAR(255)` | não      | —       | Classe do modelo    |
| `tokenable_id`              | `BIGINT`       | não      | —       | FK polimórfica      |
| `name`                      | `VARCHAR(255)` | não      | —       | `device_name`       |
| `token`                     | `CHAR(64)`     | não      | —       | Hash                |
| `abilities`                 | `TEXT`         | sim      | null    | JSON de habilidades |
| `last_used_at`              | `TIMESTAMPTZ`  | sim      | null    | —                   |
| `expires_at`                | `TIMESTAMPTZ`  | sim      | null    | Opcional            |
| `created_at` / `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                   |

**Índices.** `UNIQUE (token)`, `INDEX (tokenable_type, tokenable_id)`.

### A.6 Tabelas Spatie Permission (referência)

- `roles (id, name, guard_name, created_at, updated_at)` — `UNIQUE (name, guard_name)`
- `permissions (id, name, guard_name, created_at, updated_at)` — `UNIQUE (name, guard_name)`
- `model_has_roles (role_id, model_type, model_id)` — PK composta
- `model_has_permissions (permission_id, model_type, model_id)` — PK composta
- `role_has_permissions (permission_id, role_id)` — PK composta

Guards reconhecidos: `web`, `admin`, `sanctum`.

---

## Bloco B — Cadastro estrutural (F1/F2)

Bounded context: `Cadastro`. Modela a hierarquia comercial e acadêmica.

### B.1 `organizacoes`

**Propósito.** Organizadora (tenant lógico). Todo dado agrega por organização.

| Campo                      | Tipo           | Nullable | Default | Descrição                              |
| -------------------------- | -------------- | -------- | ------- | -------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                      |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                      |
| `nome`                     | `VARCHAR(180)` | não      | —       | Razão social                           |
| `nome_fantasia`            | `VARCHAR(180)` | sim      | null    | —                                      |
| `cnpj`                     | `VARCHAR(18)`  | sim      | null    | `00.000.000/0000-00`                   |
| `email_contato`            | `VARCHAR(150)` | sim      | null    | —                                      |
| `telefone_contato`         | `VARCHAR(30)`  | sim      | null    | —                                      |
| `logo_path`                | `VARCHAR(255)` | sim      | null    | Caminho no disco privado               |
| `ativa`                    | `BOOLEAN`      | não      | `true`  | Flag operacional                       |
| `config_json`              | `JSONB`        | não      | `'{}'`  | Overrides (branding, templates padrão) |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                      |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (cnpj)` (parcial `WHERE cnpj IS NOT NULL`), `INDEX (ativa)`.

**Relacionamentos.** `HasMany` → `instituicoes`, `eventos` (indireto), `admin_users` (via `organization_admin` — futuro).

**Exemplo JSON.**

```json
{
    "id": 1,
    "ulid": "01J5K4ORG00000000000000000",
    "nome": "ArtFinal Produções Ltda.",
    "cnpj": "12.345.678/0001-90",
    "email_contato": "contato@artfinal.com.br",
    "ativa": true,
    "config_json": { "timezone_default": "America/Sao_Paulo" }
}
```

### B.2 `instituicoes`

**Propósito.** Instituição de ensino (universidade, colégio).

| Campo                      | Tipo           | Nullable | Default | Descrição                                  |
| -------------------------- | -------------- | -------- | ------- | ------------------------------------------ |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                          |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                          |
| `organizacao_id`           | `BIGINT`       | não      | —       | FK → `organizacoes(id)` ON DELETE RESTRICT |
| `nome`                     | `VARCHAR(180)` | não      | —       | —                                          |
| `sigla`                    | `VARCHAR(20)`  | sim      | null    | USP, PUC                                   |
| `cidade`                   | `VARCHAR(120)` | sim      | null    | —                                          |
| `uf`                       | `CHAR(2)`      | sim      | null    | —                                          |
| `ativa`                    | `BOOLEAN`      | não      | `true`  | —                                          |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                          |

**Índices.** `UNIQUE (ulid)`, `INDEX (organizacao_id, ativa)`, `INDEX (uf)`.

**Relacionamentos.** `BelongsTo` → `organizacao`; `HasMany` → `cursos`, `turmas` (indireto).

### B.3 `cursos`

**Propósito.** Curso oferecido pela instituição.

| Campo                      | Tipo           | Nullable | Default | Descrição                                  |
| -------------------------- | -------------- | -------- | ------- | ------------------------------------------ |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                          |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                          |
| `instituicao_id`           | `BIGINT`       | não      | —       | FK → `instituicoes(id)` ON DELETE RESTRICT |
| `nome`                     | `VARCHAR(180)` | não      | —       | —                                          |
| `codigo`                   | `VARCHAR(30)`  | sim      | null    | Código interno da instituição              |
| `modalidade`               | `VARCHAR(30)`  | sim      | null    | `presencial`, `ead`, `hibrido`             |
| `ativo`                    | `BOOLEAN`      | não      | `true`  | —                                          |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                          |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (instituicao_id, nome)`, `INDEX (instituicao_id, ativo)`.

### B.4 `turmas`

**Propósito.** Turma formanda (unidade operacional). **Inversão de hierarquia (2026-04-23):** turma
pertence a um contrato (`contrato_id` NOT NULL). Uma turma é a combinação concreta **curso + ano + semestre**
dentro de um contrato; um contrato pode agrupar múltiplas turmas (ex.: Medicina 2026-1 + 2026-2 sob a
mesma formatura). O `codigo_acesso` público do wizard **não vive mais aqui** — foi migrado para `contratos`.

| Campo                      | Tipo           | Nullable | Default   | Descrição                                                        |
| -------------------------- | -------------- | -------- | --------- | ---------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —         | —                                                                |
| `ulid`                     | `CHAR(26)`     | não      | —         | —                                                                |
| `contrato_id`              | `BIGINT`       | não      | —         | FK → `contratos(id)` RESTRICT (**inversão** — novo vínculo raiz) |
| `instituicao_id`           | `BIGINT`       | não      | —         | FK → `instituicoes(id)` RESTRICT                                 |
| `curso_id`                 | `BIGINT`       | não      | —         | FK → `cursos(id)` RESTRICT                                       |
| `codigo`                   | `VARCHAR(30)`  | não      | —         | Ex: `ENG-CIVIL-2026-1`                                           |
| `nome`                     | `VARCHAR(120)` | não      | —         | —                                                                |
| `ano_formatura`            | `SMALLINT`     | não      | —         | `2026`                                                           |
| `semestre_formatura`       | `SMALLINT`     | sim      | null      | `1` ou `2`                                                       |
| `qtd_formandos_estimada`   | `INTEGER`      | não      | `0`       | Base para cotas                                                  |
| `status`                   | `VARCHAR(20)`  | não      | `'ativa'` | `ativa`, `arquivada`, `concluida`                                |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`   | —                                                                |

**Removido desta tabela (migrado para `contratos`).** `codigo_acesso`, `adesao_publica_ativa`.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (curso_id, codigo)`, `INDEX (contrato_id, status)`, `INDEX (instituicao_id, status)`, `INDEX (ano_formatura)`.

**CHECK.** `status IN ('ativa','arquivada','concluida')`, `semestre_formatura IS NULL OR semestre_formatura IN (1,2)`.

### B.4b `contratos`

**Propósito.** Agregado raiz do domínio de adesão (inversão de hierarquia 2026-04-23 — ver SPEC-F-001).
Acordo comercial entre organizadora e instituição; contém pacotes, programações, descontos, regras de
responsáveis e **uma ou mais turmas**. O `codigo_acesso` humano-legível usado pelo wizard público de
adesão (SPEC-010) vive aqui.

| Campo                              | Tipo           | Nullable | Default       | Descrição                                                                                                      |
| ---------------------------------- | -------------- | -------- | ------------- | -------------------------------------------------------------------------------------------------------------- |
| `id`                               | `BIGSERIAL`    | não      | —             | —                                                                                                              |
| `ulid`                             | `CHAR(26)`     | não      | —             | Exposto em rotas/API                                                                                           |
| `categoria`                        | `VARCHAR(30)`  | não      | `'formatura'` | Enum extensível (`formatura` no MVP)                                                                           |
| `evento_id`                        | `BIGINT`       | sim      | null          | FK → `eventos(id)` SET NULL (vinculação tardia)                                                                |
| `nome`                             | `VARCHAR(150)` | não      | —             | Ex.: "Formatura Medicina USP 2026"                                                                             |
| `status`                           | `VARCHAR(20)`  | não      | `'ativo'`     | `rascunho`, `ativo`, `encerrado`, `cancelado`                                                                  |
| `codigo_acesso`                    | `VARCHAR(32)`  | não      | —             | **UNIQUE global**, CITEXT, índice funcional `UPPER(codigo_acesso)`, regex `^[A-Z0-9-]{4,32}$` (acesso público) |
| `adesao_publica_ativa`             | `BOOLEAN`      | não      | `TRUE`        | Kill-switch do fluxo público                                                                                   |
| `meta_formandos`                   | `INTEGER`      | sim      | null          | Meta comercial                                                                                                 |
| `data_inicio`                      | `DATE`         | sim      | null          | Abertura do período de adesão                                                                                  |
| `data_fim_adesao`                  | `DATE`         | sim      | null          | Encerramento do período de adesão                                                                              |
| `exige_responsavel_cadastro`       | `BOOLEAN`      | não      | `false`       | —                                                                                                              |
| `exige_responsavel_financeiro`     | `BOOLEAN`      | não      | `true`        | —                                                                                                              |
| `permite_formando_resp_cadastro`   | `BOOLEAN`      | não      | `true`        | (se ≥18)                                                                                                       |
| `permite_formando_resp_financeiro` | `BOOLEAN`      | não      | `true`        | (se ≥18)                                                                                                       |
| `observacoes`                      | `TEXT`         | sim      | null          | —                                                                                                              |
| `deleted_at`                       | `TIMESTAMPTZ`  | sim      | null          | Soft delete                                                                                                    |
| `created_at`, `updated_at`         | `TIMESTAMPTZ`  | não      | `now()`       | —                                                                                                              |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (codigo_acesso)` (global), índice funcional `CREATE INDEX ON contratos (UPPER(codigo_acesso))` para lookup case-insensitive em PostgreSQL, `INDEX (status)`, `INDEX (evento_id)`.

**CHECK.** `status IN ('rascunho','ativo','encerrado','cancelado')`, `categoria IN ('formatura')` (abrível no futuro), `codigo_acesso ~ '^[A-Z0-9-]{4,32}$'`.

**Relacionamentos.** `HasMany` turmas, pacotes, adesoes, condicoes_pagamento; `BelongsTo` evento (nullable).

### B.5 `eventos`

**Propósito.** Evento concreto (colação, baile, formal, aftermovie). Âncora operacional de janelas (RSVP, seating, extras).

| Campo                      | Tipo           | Nullable | Default               | Descrição                                                                                 |
| -------------------------- | -------------- | -------- | --------------------- | ----------------------------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —                     | —                                                                                         |
| `ulid`                     | `CHAR(26)`     | não      | —                     | —                                                                                         |
| `organizacao_id`           | `BIGINT`       | não      | —                     | FK RESTRICT                                                                               |
| `slug`                     | `VARCHAR(80)`  | não      | —                     | URL-friendly                                                                              |
| `nome`                     | `VARCHAR(180)` | não      | —                     | —                                                                                         |
| `tipo`                     | `VARCHAR(30)`  | não      | —                     | `colacao`, `baile`, `formal`, `aftermovie`, `outro`                                       |
| `status`                   | `VARCHAR(20)`  | não      | `'rascunho'`          | `rascunho`, `publicado`, `encerrado`, `cancelado`                                         |
| `data_evento`              | `TIMESTAMPTZ`  | não      | —                     | Início                                                                                    |
| `duracao_minutos`          | `INTEGER`      | sim      | null                  | —                                                                                         |
| `timezone`                 | `VARCHAR(40)`  | não      | `'America/Sao_Paulo'` | IANA                                                                                      |
| `local_nome`               | `VARCHAR(180)` | sim      | null                  | —                                                                                         |
| `local_endereco`           | `TEXT`         | sim      | null                  | —                                                                                         |
| `abre_rsvp_at`             | `TIMESTAMPTZ`  | sim      | null                  | Janela de RSVP                                                                            |
| `fecha_rsvp_at`            | `TIMESTAMPTZ`  | sim      | null                  | —                                                                                         |
| `abre_mesas_at`            | `TIMESTAMPTZ`  | sim      | null                  | Janela de seating                                                                         |
| `fecha_mesas_at`           | `TIMESTAMPTZ`  | sim      | null                  | —                                                                                         |
| `abre_extras_at`           | `TIMESTAMPTZ`  | sim      | null                  | Janela de compra de extras                                                                |
| `fecha_extras_at`          | `TIMESTAMPTZ`  | sim      | null                  | —                                                                                         |
| `capa_path`                | `VARCHAR(255)` | sim      | null                  | Imagem em disco privado                                                                   |
| `config_json`              | `JSONB`        | não      | `'{}'`                | Overrides (hold TTL, política cancelamento, mensagens, retencao_dias, dress_code, regras) |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`               | —                                                                                         |

**Índices.**

- `UNIQUE (ulid)`, `UNIQUE (slug)`
- `INDEX (organizacao_id, status)`
- `INDEX (data_evento)`
- `INDEX (status, data_evento)`

**CHECK.** `status IN ('rascunho','publicado','encerrado','cancelado')`, `tipo IN ('colacao','baile','formal','aftermovie','outro')`, `fecha_rsvp_at IS NULL OR fecha_rsvp_at > abre_rsvp_at`.

**Snapshot JSONB (`config_json`).**

```json
{
    "hold_ttl_seconds": 300,
    "permite_transferencia_convite": true,
    "permite_extras_convidado": false,
    "retencao_dias": 90,
    "dress_code": "black tie",
    "mensagens": {
        "boas_vindas": "Confirme sua presença..."
    }
}
```

**Relacionamentos.** `BelongsTo` organizacao; `BelongsToMany` turmas (via `turma_evento`); `HasMany` lotes_convites, convites, mapas_mesas, produtos_extras, enquetes.

### B.6 `turma_evento`

**Propósito.** Tabela pivô N:N entre turmas e eventos. Uma turma pode participar de múltiplos eventos (colação + formal); um evento pode ter múltiplas turmas (colação coletiva).

| Campo                      | Tipo          | Nullable | Default | Descrição                                   |
| -------------------------- | ------------- | -------- | ------- | ------------------------------------------- |
| `turma_id`                 | `BIGINT`      | não      | —       | FK `turmas(id)` CASCADE                     |
| `evento_id`                | `BIGINT`      | não      | —       | FK `eventos(id)` CASCADE                    |
| `meta_formandos`           | `INTEGER`     | não      | `0`     | Meta comercial para esta turma neste evento |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —                                           |

**PK composta.** `(turma_id, evento_id)`.

**Índices.** `INDEX (evento_id)`.

### B.7 `formandos`

**Propósito.** Indivíduo formando em uma turma, possivelmente com `portal_user_id` ligado. A mesma pessoa pode ter um registro `formando` por turma.

| Campo                          | Tipo           | Nullable | Default      | Descrição                                     |
| ------------------------------ | -------------- | -------- | ------------ | --------------------------------------------- |
| `id`                           | `BIGSERIAL`    | não      | —            | —                                             |
| `ulid`                         | `CHAR(26)`     | não      | —            | —                                             |
| `turma_id`                     | `BIGINT`       | não      | —            | FK RESTRICT                                   |
| `portal_user_id`               | `BIGINT`       | sim      | null         | FK → `portal_users(id)` SET NULL              |
| `nome`                         | `VARCHAR(120)` | não      | —            | —                                             |
| `cpf`                          | `VARCHAR(14)`  | sim      | null         | —                                             |
| `email`                        | `VARCHAR(150)` | sim      | null         | —                                             |
| `telefone`                     | `VARCHAR(30)`  | sim      | null         | —                                             |
| `matricula`                    | `VARCHAR(40)`  | sim      | null         | Código acadêmico                              |
| `responsavel_financeiro_nome`  | `VARCHAR(120)` | sim      | null         | —                                             |
| `responsavel_financeiro_email` | `VARCHAR(150)` | sim      | null         | —                                             |
| `status`                       | `VARCHAR(20)`  | não      | `'elegivel'` | `elegivel`, `inativo`, `formado`, `desligado` |
| `created_at`, `updated_at`     | `TIMESTAMPTZ`  | não      | `now()`      | —                                             |

**Índices.**

- `UNIQUE (ulid)`, `UNIQUE (turma_id, cpf)` (parcial cpf not null)
- `INDEX (portal_user_id)`, `INDEX (turma_id, status)`

**CHECK.** `status IN ('elegivel','inativo','formado','desligado')`.

**Relacionamentos.** `BelongsTo` turma, portal_user; `HasMany` adesoes, convites, pedidos_extras, reservas_assentos.

**Exemplo JSON.**

```json
{
    "id": 101,
    "ulid": "01J5K5FORMANDO0001XXX",
    "turma_id": 5,
    "portal_user_id": 42,
    "nome": "Mariana Souza",
    "cpf": "123.456.789-00",
    "email": "mariana@usp.br",
    "matricula": "20231234",
    "responsavel_financeiro_nome": "José Souza",
    "responsavel_financeiro_email": "jose@souza.com",
    "status": "elegivel"
}
```

---

## Bloco C — Comercial e Adesão (F2)

Bounded context: `Comercial`. Pacotes, produtos, adesões, parcelas e pagamentos. **Snapshots imutáveis em `adesoes.snapshot_comercial`** no momento da confirmação.

### C.1 `pacotes`

**Propósito.** Composição comercial ofertada dentro de um contrato. A coluna `categoria` distingue
pacotes de **formatura** (exibidos no wizard público — SPEC-010) de pacotes **extras** (convites adicionais,
mesas premium, combos pós-adesão — só visíveis no portal autenticado).

| Campo                      | Tipo           | Nullable | Default       | Descrição                                                                                     |
| -------------------------- | -------------- | -------- | ------------- | --------------------------------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —             | —                                                                                             |
| `ulid`                     | `CHAR(26)`     | não      | —             | —                                                                                             |
| `turma_id`                 | `BIGINT`       | não      | —             | FK RESTRICT                                                                                   |
| `nome`                     | `VARCHAR(120)` | não      | —             | —                                                                                             |
| `descricao`                | `TEXT`         | sim      | null          | —                                                                                             |
| `preco_base_centavos`      | `INTEGER`      | não      | —             | Preço cheio                                                                                   |
| `qtd_parcelas_max`         | `SMALLINT`     | não      | `1`           | —                                                                                             |
| `categoria`                | `VARCHAR(30)`  | não      | `'formatura'` | Valores permitidos: `'formatura'` (wizard público), `'extra'` (portal autenticado pós-adesão) |
| `status`                   | `VARCHAR(20)`  | não      | `'ativo'`     | `ativo`, `inativo`                                                                            |
| `vigencia_inicio`          | `DATE`         | sim      | null          | —                                                                                             |
| `vigencia_fim`             | `DATE`         | sim      | null          | —                                                                                             |
| `deleted_at`               | `TIMESTAMPTZ`  | sim      | null          | Soft delete                                                                                   |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`       | —                                                                                             |

**Índices.** `UNIQUE (ulid)`, `INDEX (turma_id, status)`, `INDEX (turma_id, categoria, status)`, `INDEX (vigencia_inicio, vigencia_fim)`.

**CHECK.** `preco_base_centavos >= 0`, `qtd_parcelas_max BETWEEN 1 AND 60`, `categoria IN ('formatura','extra')`.

### C.2 `produtos`

**Propósito.** Item comercializável avulso ou compositor de pacote. Produtos **não-extras** (colação, baile etc. que compõem pacote). Para produtos vendidos à parte após adesão ver `produtos_extras`.

| Campo                      | Tipo           | Nullable | Default | Descrição                                              |
| -------------------------- | -------------- | -------- | ------- | ------------------------------------------------------ |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                                      |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                                      |
| `pacote_id`                | `BIGINT`       | sim      | null    | FK → `pacotes(id)` ON DELETE SET NULL                  |
| `turma_id`                 | `BIGINT`       | não      | —       | Escopo                                                 |
| `nome`                     | `VARCHAR(120)` | não      | —       | —                                                      |
| `descricao`                | `TEXT`         | sim      | null    | —                                                      |
| `tipo`                     | `VARCHAR(30)`  | não      | —       | `colacao`, `baile`, `formal`, `foto`, `video`, `outro` |
| `preco_centavos`           | `INTEGER`      | não      | `0`     | Preço de referência                                    |
| `is_obrigatorio`           | `BOOLEAN`      | não      | `false` | Compõe pacote obrigatoriamente                         |
| `ordem`                    | `SMALLINT`     | não      | `0`     | Para exibição                                          |
| `ativo`                    | `BOOLEAN`      | não      | `true`  | —                                                      |
| `deleted_at`               | `TIMESTAMPTZ`  | sim      | null    | Soft delete                                            |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                                      |

**Índices.** `UNIQUE (ulid)`, `INDEX (pacote_id)`, `INDEX (turma_id, ativo)`.

### C.3 `adesoes`

**Propósito.** Adesão comercial do formando dentro de um contrato, amarrada a uma turma específica
(curso+ano+semestre). Snapshot comercial congelado na confirmação. Apenas UMA adesão ativa por
formando × contrato. **Inversão 2026-04-23:** `evento_id` foi removido (derivado via `contrato.evento_id`);
`contrato_id` e `turma_id` são obrigatórios; `portal_user_id` passa a **nullable** para suportar o fluxo
público de SPEC-010 (wizard sem autenticação prévia).

| Campo                      | Tipo          | Nullable | Default         | Descrição                                                                                         |
| -------------------------- | ------------- | -------- | --------------- | ------------------------------------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —               | —                                                                                                 |
| `ulid`                     | `CHAR(26)`    | não      | —               | —                                                                                                 |
| `contrato_id`              | `BIGINT`      | não      | —               | FK → `contratos(id)` RESTRICT (**novo** — agregado raiz)                                          |
| `turma_id`                 | `BIGINT`      | não      | —               | FK → `turmas(id)` RESTRICT (escolha do formando no wizard: curso + período)                       |
| `formando_id`              | `BIGINT`      | não      | —               | FK RESTRICT                                                                                       |
| `portal_user_id`           | `BIGINT`      | sim      | null            | FK → `portal_users(id)` SET NULL — nullable para fluxo público (SPEC-010); preenchido após auth   |
| `pacote_id`                | `BIGINT`      | não      | —               | FK RESTRICT                                                                                       |
| `status`                   | `VARCHAR(25)` | não      | `'rascunho'`    | `rascunho`, `pendente_pagamento`, `ativa`, `cancelada`, `inadimplente`, `concluida`               |
| `origem_adesao`            | `VARCHAR(30)` | não      | `'autenticada'` | `'autenticada'` (portal logado) \| `'publica'` (wizard SPEC-010)                                  |
| `draft_token_hash`         | `CHAR(64)`    | sim      | null            | `sha256(jti)` do draft_token JWT usado no wizard público (auditoria; nunca armazenar o jti bruto) |
| `valor_total_centavos`     | `INTEGER`     | não      | —               | Após desconto                                                                                     |
| `valor_entrada_centavos`   | `INTEGER`     | não      | `0`             | —                                                                                                 |
| `qtd_parcelas`             | `SMALLINT`    | não      | —               | —                                                                                                 |
| `snapshot_comercial`       | `JSONB`       | não      | `'{}'`          | Congelado na confirmação                                                                          |
| `termo_hash`               | `CHAR(64)`    | sim      | null            | `sha256(termo_html)`                                                                              |
| `aceito_em`                | `TIMESTAMPTZ` | sim      | null            | —                                                                                                 |
| `confirmada_at`            | `TIMESTAMPTZ` | sim      | null            | —                                                                                                 |
| `cancelada_at`             | `TIMESTAMPTZ` | sim      | null            | —                                                                                                 |
| `motivo_cancelamento`      | `TEXT`        | sim      | null            | —                                                                                                 |
| `correlation_id`           | `CHAR(26)`    | sim      | null            | Tracing                                                                                           |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()`         | —                                                                                                 |

**Removido desta tabela.** `evento_id` (derivado via `contrato.evento_id`; consulte-o quando precisar do
evento operacional — convites, mesas, extras dependem do evento concreto).

**Índices.**

- `UNIQUE (ulid)`
- `INDEX (contrato_id, status)`, `INDEX (turma_id, status)`, `INDEX (formando_id, status)`, `INDEX (portal_user_id)`
- **UNIQUE PARCIAL** `(formando_id, contrato_id) WHERE status IN ('pendente_pagamento','ativa')` → `adesoes_ativa_por_formando_contrato`
- `INDEX (origem_adesao)` (análise de funil público vs autenticado)

**CHECK.** `status IN ('rascunho','pendente_pagamento','ativa','cancelada','inadimplente','concluida')`, `origem_adesao IN ('autenticada','publica')`, `valor_total_centavos >= 0`, `qtd_parcelas BETWEEN 1 AND 60`.

**Snapshot JSONB (`snapshot_comercial`).**

```json
{
    "pacote": {
        "ulid": "01J5K7PKG...",
        "nome": "Pacote Formatura Completa 2026",
        "preco_base_centavos": 890000,
        "desconto_aplicado_centavos": 50000
    },
    "produtos": [
        { "ulid": "01J5K7PROD1", "nome": "Baile", "qtd": 1, "preco_centavos": 450000 },
        { "ulid": "01J5K7PROD2", "nome": "Foto & Vídeo", "qtd": 1, "preco_centavos": 390000 }
    ],
    "termo_html_ref": "01J5K7TERMO2026",
    "condicoes_pagamento": { "parcelas": 12, "juros_am_bps": 0 }
}
```

**Relacionamentos.** `BelongsTo` contrato, turma, formando, pacote, portal_user (nullable); `HasMany` adesao_produtos, parcelas. Evento é acessado indiretamente via `adesao.contrato.evento`.

### C.4 `adesao_produtos`

**Propósito.** Produtos incluídos na adesão (cópia do pacote + extras avulsos).

| Campo                      | Tipo          | Nullable | Default | Descrição   |
| -------------------------- | ------------- | -------- | ------- | ----------- |
| `id`                       | `BIGSERIAL`   | não      | —       | —           |
| `adesao_id`                | `BIGINT`      | não      | —       | FK CASCADE  |
| `produto_id`               | `BIGINT`      | não      | —       | FK RESTRICT |
| `qtd`                      | `SMALLINT`    | não      | `1`     | —           |
| `preco_unitario_centavos`  | `INTEGER`     | não      | —       | Snapshot    |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —           |

**Índices.** `UNIQUE (adesao_id, produto_id)`, `INDEX (produto_id)`.

### C.5 `parcelas`

**Propósito.** Parcela a cobrar do formando.

| Campo                      | Tipo          | Nullable | Default      | Descrição                                               |
| -------------------------- | ------------- | -------- | ------------ | ------------------------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —            | —                                                       |
| `ulid`                     | `CHAR(26)`    | não      | —            | —                                                       |
| `adesao_id`                | `BIGINT`      | não      | —            | FK CASCADE                                              |
| `numero`                   | `SMALLINT`    | não      | —            | 1..N                                                    |
| `valor_centavos`           | `INTEGER`     | não      | —            | —                                                       |
| `vencimento`               | `DATE`        | não      | —            | —                                                       |
| `status`                   | `VARCHAR(20)` | não      | `'pendente'` | `pendente`, `paga`, `vencida`, `cancelada`, `estornada` |
| `pago_em`                  | `TIMESTAMPTZ` | sim      | null         | —                                                       |
| `metodo_preferido`         | `VARCHAR(20)` | sim      | null         | `boleto`, `pix`, `cartao`                               |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()`      | —                                                       |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (adesao_id, numero)`, `INDEX (status, vencimento)`.

**CHECK.** `status IN ('pendente','paga','vencida','cancelada','estornada')`, `valor_centavos > 0`.

### C.6 `pagamentos`

**Propósito.** Tentativa de cobrança/pagamento ligada a uma parcela ou pedido extra. **Sem dados de cartão.** Apenas `gateway_reference`.

| Campo                      | Tipo           | Nullable | Default      | Descrição                                                                 |
| -------------------------- | -------------- | -------- | ------------ | ------------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —            | —                                                                         |
| `ulid`                     | `CHAR(26)`     | não      | —            | —                                                                         |
| `parcela_id`               | `BIGINT`       | sim      | null         | FK RESTRICT                                                               |
| `pedido_extra_id`          | `BIGINT`       | sim      | null         | FK RESTRICT                                                               |
| `provider`                 | `VARCHAR(30)`  | não      | —            | `itau`, `mock`                                                            |
| `metodo`                   | `VARCHAR(20)`  | não      | —            | `boleto`, `pix`, `cartao`                                                 |
| `gateway_reference`        | `VARCHAR(120)` | não      | —            | ID no provedor                                                            |
| `status`                   | `VARCHAR(20)`  | não      | `'iniciado'` | `iniciado`, `pendente`, `autorizado`, `confirmado`, `estornado`, `falhou` |
| `valor_centavos`           | `INTEGER`      | não      | —            | —                                                                         |
| `iniciado_at`              | `TIMESTAMPTZ`  | não      | `now()`      | —                                                                         |
| `confirmado_at`            | `TIMESTAMPTZ`  | sim      | null         | —                                                                         |
| `estornado_at`             | `TIMESTAMPTZ`  | sim      | null         | —                                                                         |
| `correlation_id`           | `CHAR(26)`     | sim      | null         | —                                                                         |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                                                         |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (provider, gateway_reference)`, `INDEX (parcela_id)`, `INDEX (pedido_extra_id)`, `INDEX (status, iniciado_at)`.

**CHECK.** `(parcela_id IS NOT NULL) OR (pedido_extra_id IS NOT NULL)`, `status IN ('iniciado','pendente','autorizado','confirmado','estornado','falhou')`, `metodo IN ('boleto','pix','cartao')`.

**Exemplo JSON.**

```json
{
    "ulid": "01J5KPAGTO12345",
    "parcela_id": 12,
    "pedido_extra_id": null,
    "provider": "itau",
    "metodo": "pix",
    "gateway_reference": "PIX-20260417-7894512",
    "status": "confirmado",
    "valor_centavos": 74083,
    "iniciado_at": "2026-04-17T10:00:00-03:00",
    "confirmado_at": "2026-04-17T10:01:12-03:00"
}
```

---

## Bloco D — Convites e RSVP (F4)

Bounded context: `Convites`. Emissão em lote, token criptográfico, RSVP público.

### D.1 `cotas_regras`

**Propósito.** Política de cota de convites por evento (fórmula declarativa). Permite calcular cota por formando com base em pacote, produto adquirido, adicional pago.

| Campo                      | Tipo           | Nullable | Default | Descrição                                            |
| -------------------------- | -------------- | -------- | ------- | ---------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                                    |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                                    |
| `evento_id`                | `BIGINT`       | não      | —       | FK RESTRICT                                          |
| `nome`                     | `VARCHAR(120)` | não      | —       | —                                                    |
| `cota_base`                | `SMALLINT`     | não      | `0`     | Convites grátis                                      |
| `permite_transferir`       | `BOOLEAN`      | não      | `true`  | —                                                    |
| `permite_comprar_extras`   | `BOOLEAN`      | não      | `true`  | —                                                    |
| `max_extras_por_formando`  | `SMALLINT`     | não      | `0`     | 0 = ilimitado                                        |
| `regra_json`               | `JSONB`        | não      | `'{}'`  | Regras declarativas extra (por pacote, produto etc.) |
| `ativa`                    | `BOOLEAN`      | não      | `true`  | —                                                    |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                                    |

**Índices.** `UNIQUE (ulid)`, `INDEX (evento_id, ativa)`.

**Snapshot JSONB (`regra_json`).**

```json
{
    "por_pacote": { "01J5K7PKG...": { "cota_adicional": 2 } },
    "por_produto": { "01J5K7PROD1": { "cota_adicional": 1 } }
}
```

### D.2 `lotes_convites`

**Propósito.** Lote de emissão em massa (controle operacional e auditoria). Cada lote agrega N convites.

| Campo                      | Tipo          | Nullable | Default      | Descrição                                        |
| -------------------------- | ------------- | -------- | ------------ | ------------------------------------------------ |
| `id`                       | `BIGSERIAL`   | não      | —            | —                                                |
| `ulid`                     | `CHAR(26)`    | não      | —            | —                                                |
| `evento_id`                | `BIGINT`      | não      | —            | FK RESTRICT                                      |
| `formando_id`              | `BIGINT`      | sim      | null         | Se emissão for do formando                       |
| `origem`                   | `VARCHAR(20)` | não      | —            | `cota`, `extra`, `cortesia`, `staff`             |
| `qtd_solicitada`           | `INTEGER`     | não      | —            | —                                                |
| `qtd_emitida`              | `INTEGER`     | não      | `0`          | —                                                |
| `status`                   | `VARCHAR(20)` | não      | `'pendente'` | `pendente`, `processando`, `concluido`, `falhou` |
| `idempotency_key`          | `VARCHAR(80)` | não      | —            | Chave do request                                 |
| `payload_json`             | `JSONB`       | não      | `'{}'`       | Lista de convidados enviada                      |
| `iniciado_at`              | `TIMESTAMPTZ` | sim      | null         | —                                                |
| `concluido_at`             | `TIMESTAMPTZ` | sim      | null         | —                                                |
| `erro_ultimo`              | `TEXT`        | sim      | null         | —                                                |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()`      | —                                                |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (idempotency_key)`, `INDEX (evento_id, status)`, `INDEX (formando_id, status)`.

**CHECK.** `status IN ('pendente','processando','concluido','falhou')`, `origem IN ('cota','extra','cortesia','staff')`.

### D.3 `convites`

**Propósito.** Convite individual emitido. Token criptográfico (64 chars, ~256 bits de entropia) — **apenas hash persistido**.

| Campo                      | Tipo           | Nullable | Default      | Descrição                                                                                             |
| -------------------------- | -------------- | -------- | ------------ | ----------------------------------------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —            | —                                                                                                     |
| `ulid`                     | `CHAR(26)`     | não      | —            | —                                                                                                     |
| `evento_id`                | `BIGINT`       | não      | —            | FK RESTRICT                                                                                           |
| `formando_id`              | `BIGINT`       | não      | —            | FK RESTRICT — dono                                                                                    |
| `lote_id`                  | `BIGINT`       | sim      | null         | FK SET NULL                                                                                           |
| `pedido_extra_id`          | `BIGINT`       | sim      | null         | FK SET NULL (convite veio de compra extra)                                                            |
| `codigo`                   | `VARCHAR(24)`  | não      | —            | Legível, curto, não enumerável                                                                        |
| `token_hash`               | `CHAR(64)`     | não      | —            | `sha256(token_bruto)`                                                                                 |
| `tipo`                     | `VARCHAR(20)`  | não      | —            | `nominal`, `transferivel`, `cortesia`, `staff`, `extra`                                               |
| `status`                   | `VARCHAR(20)`  | não      | `'rascunho'` | `rascunho`, `emitido`, `enviado`, `visualizado`, `confirmado`, `recusado`, `cancelado`, `inutilizado` |
| `is_extra`                 | `BOOLEAN`      | não      | `false`      | —                                                                                                     |
| `convidado_nome`           | `VARCHAR(150)` | sim      | null         | —                                                                                                     |
| `convidado_email`          | `VARCHAR(150)` | sim      | null         | —                                                                                                     |
| `convidado_telefone`       | `VARCHAR(30)`  | sim      | null         | —                                                                                                     |
| `entregue_at`              | `TIMESTAMPTZ`  | sim      | null         | —                                                                                                     |
| `visualizado_at`           | `TIMESTAMPTZ`  | sim      | null         | —                                                                                                     |
| `confirmado_at`            | `TIMESTAMPTZ`  | sim      | null         | —                                                                                                     |
| `cancelado_at`             | `TIMESTAMPTZ`  | sim      | null         | —                                                                                                     |
| `snapshot_regra`           | `JSONB`        | sim      | null         | Cota, template no momento da emissão                                                                  |
| `correlation_id`           | `CHAR(26)`     | sim      | null         | Tracing                                                                                               |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                                                                                     |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (codigo)`, `UNIQUE (token_hash)`, `INDEX (evento_id, status)`, `INDEX (formando_id, status)`, `INDEX (lote_id)`, `INDEX (pedido_extra_id)`.

**CHECK.** `status IN ('rascunho','emitido','enviado','visualizado','confirmado','recusado','cancelado','inutilizado')`, `tipo IN ('nominal','transferivel','cortesia','staff','extra')`.

**Snapshot JSONB.**

```json
{
    "cota_regra_ulid": "01J5KQOT...",
    "template_id": 7,
    "politica_cancelamento": "ate_72h_antes",
    "emitido_por": { "tipo": "formando", "ulid": "01J5KFORM..." }
}
```

**Relacionamentos.** `BelongsTo` evento, formando, lote; `HasMany` rsvp_historico, convidado_access_tokens, reservas_assentos (via convite_id).

### D.4 `rsvp_historico`

**Propósito.** Append-only de todas as transições de RSVP do convite (histórico completo de confirmações, recusas, alterações).

| Campo            | Tipo           | Nullable | Default | Descrição                                        |
| ---------------- | -------------- | -------- | ------- | ------------------------------------------------ |
| `id`             | `BIGSERIAL`    | não      | —       | —                                                |
| `ulid`           | `CHAR(26)`     | não      | —       | —                                                |
| `convite_id`     | `BIGINT`       | não      | —       | FK CASCADE                                       |
| `status`         | `VARCHAR(20)`  | não      | —       | `confirmado`, `recusado`, `pendente`, `alterado` |
| `origem`         | `VARCHAR(20)`  | não      | —       | `token`, `formando`, `admin`, `comissao`         |
| `ator_tipo`      | `VARCHAR(30)`  | sim      | null    | —                                                |
| `ator_id`        | `BIGINT`       | sim      | null    | —                                                |
| `ip`             | `INET`         | sim      | null    | Mascarado em logs                                |
| `user_agent`     | `VARCHAR(255)` | sim      | null    | —                                                |
| `observacao`     | `TEXT`         | sim      | null    | —                                                |
| `correlation_id` | `CHAR(26)`     | sim      | null    | —                                                |
| `ocorreu_em`     | `TIMESTAMPTZ`  | não      | `now()` | —                                                |
| `created_at`     | `TIMESTAMPTZ`  | não      | `now()` | (sem updated_at — append-only)                   |

**Índices.** `UNIQUE (ulid)`, `INDEX (convite_id, ocorreu_em)`, `INDEX (ator_tipo, ator_id)`.

**CHECK.** `status IN ('confirmado','recusado','pendente','alterado')`, `origem IN ('token','formando','admin','comissao')`.

---

## Bloco E — Seating (F5)

Bounded context: `Seating`. Fase crítica: concorrência, unique parcial + lock Redis + transação.

### E.1 `mapas_mesas`

**Propósito.** Mapa de mesas de um evento (pode ser único ou múltiplo). Desenho geral do salão.

| Campo                      | Tipo           | Nullable | Default      | Descrição                            |
| -------------------------- | -------------- | -------- | ------------ | ------------------------------------ |
| `id`                       | `BIGSERIAL`    | não      | —            | —                                    |
| `ulid`                     | `CHAR(26)`     | não      | —            | —                                    |
| `evento_id`                | `BIGINT`       | não      | —            | FK RESTRICT                          |
| `nome`                     | `VARCHAR(120)` | não      | —            | —                                    |
| `descricao`                | `TEXT`         | sim      | null         | —                                    |
| `status`                   | `VARCHAR(20)`  | não      | `'rascunho'` | `rascunho`, `publicado`, `encerrado` |
| `layout_json`              | `JSONB`        | não      | `'{}'`       | SVG/coordenadas do desenho           |
| `publicado_at`             | `TIMESTAMPTZ`  | sim      | null         | —                                    |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                    |

**Índices.** `UNIQUE (ulid)`, `INDEX (evento_id, status)`.

**CHECK.** `status IN ('rascunho','publicado','encerrado')`.

### E.2 `setores`

**Propósito.** Subdivisão do mapa (VIP, geral, família, staff).

| Campo                      | Tipo          | Nullable | Default | Descrição  |
| -------------------------- | ------------- | -------- | ------- | ---------- |
| `id`                       | `BIGSERIAL`   | não      | —       | —          |
| `ulid`                     | `CHAR(26)`    | não      | —       | —          |
| `mapa_id`                  | `BIGINT`      | não      | —       | FK CASCADE |
| `nome`                     | `VARCHAR(80)` | não      | —       | —          |
| `cor_hex`                  | `CHAR(7)`     | sim      | null    | `#RRGGBB`  |
| `prioridade`               | `SMALLINT`    | não      | `0`     | Para UX    |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —          |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (mapa_id, nome)`.

### E.3 `mesas`

**Propósito.** Mesa física.

| Campo                      | Tipo          | Nullable | Default   | Descrição                              |
| -------------------------- | ------------- | -------- | --------- | -------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —         | —                                      |
| `ulid`                     | `CHAR(26)`    | não      | —         | —                                      |
| `setor_id`                 | `BIGINT`      | não      | —         | FK CASCADE                             |
| `evento_id`                | `BIGINT`      | não      | —         | Denormalizado para query (FK RESTRICT) |
| `numero`                   | `VARCHAR(10)` | não      | —         | Rótulo humano                          |
| `capacidade`               | `SMALLINT`    | não      | `8`       | —                                      |
| `coordenadas_json`         | `JSONB`       | sim      | null      | `{x, y, rotacao}`                      |
| `status`                   | `VARCHAR(20)` | não      | `'ativa'` | `ativa`, `bloqueada`                   |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()`   | —                                      |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (setor_id, numero)`, `INDEX (evento_id, status)`.

**CHECK.** `status IN ('ativa','bloqueada')`, `capacidade BETWEEN 1 AND 30`.

### E.4 `assentos`

**Propósito.** Assento individual dentro de uma mesa.

| Campo                      | Tipo           | Nullable | Default        | Descrição                 |
| -------------------------- | -------------- | -------- | -------------- | ------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —              | —                         |
| `ulid`                     | `CHAR(26)`     | não      | —              | —                         |
| `mesa_id`                  | `BIGINT`       | não      | —              | FK CASCADE                |
| `numero`                   | `SMALLINT`     | não      | —              | 1..capacidade             |
| `posicao_json`             | `JSONB`        | sim      | null           | `{x, y}` relativo à mesa  |
| `status`                   | `VARCHAR(20)`  | não      | `'disponivel'` | `disponivel`, `bloqueado` |
| `motivo_bloqueio`          | `VARCHAR(120)` | sim      | null           | —                         |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`        | —                         |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (mesa_id, numero)`, `INDEX (mesa_id, status)`.

**CHECK.** `status IN ('disponivel','bloqueado')`, `numero BETWEEN 1 AND 30`.

### E.5 `reservas_assentos` _(crítica)_

**Propósito.** Reserva de assento com estados `hold → confirmada` ou `cancelada/expirada`. Único por assento via **index parcial**.

| Campo                      | Tipo          | Nullable | Default | Descrição                                                  |
| -------------------------- | ------------- | -------- | ------- | ---------------------------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —       | —                                                          |
| `ulid`                     | `CHAR(26)`    | não      | —       | —                                                          |
| `evento_id`                | `BIGINT`      | não      | —       | FK RESTRICT                                                |
| `mesa_id`                  | `BIGINT`      | não      | —       | FK RESTRICT                                                |
| `assento_id`               | `BIGINT`      | não      | —       | FK RESTRICT                                                |
| `convite_id`               | `BIGINT`      | sim      | null    | FK SET NULL                                                |
| `formando_id`              | `BIGINT`      | sim      | null    | FK SET NULL                                                |
| `status`                   | `VARCHAR(20)` | não      | —       | `hold`, `confirmada`, `cancelada`, `expirada`, `bloqueada` |
| `origem`                   | `VARCHAR(20)` | não      | —       | `formando`, `comissao`, `admin`, `operacao`                |
| `idempotency_key`          | `VARCHAR(64)` | não      | —       | —                                                          |
| `hold_expires_at`          | `TIMESTAMPTZ` | sim      | null    | Apenas com status=hold                                     |
| `confirmado_at`            | `TIMESTAMPTZ` | sim      | null    | —                                                          |
| `cancelado_at`             | `TIMESTAMPTZ` | sim      | null    | —                                                          |
| `cancelado_por_tipo`       | `VARCHAR(30)` | sim      | null    | —                                                          |
| `cancelado_por_id`         | `BIGINT`      | sim      | null    | —                                                          |
| `cancelamento_motivo`      | `TEXT`        | sim      | null    | —                                                          |
| `correlation_id`           | `CHAR(26)`    | sim      | null    | —                                                          |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —                                                          |

**Índices.**

- `UNIQUE (ulid)`
- `UNIQUE (idempotency_key)`
- `INDEX (evento_id, status)`, `INDEX (mesa_id, status)`
- `INDEX (hold_expires_at)` — drive do `ExpirarHoldsJob`
- **UNIQUE PARCIAL** `(assento_id) WHERE status IN ('hold','confirmada')` → `reservas_assentos_ativa_por_assento`

**CHECKs.**

```sql
CHECK (status IN ('hold','confirmada','cancelada','expirada','bloqueada'))
CHECK (
  (status = 'hold' AND hold_expires_at IS NOT NULL AND confirmado_at IS NULL)
  OR (status = 'confirmada' AND confirmado_at IS NOT NULL)
  OR (status IN ('cancelada','expirada','bloqueada'))
) -- reservas_assentos_hold_consistente
```

**Exemplo JSON.**

```json
{
    "ulid": "01J5KRES00001",
    "evento_id": 3,
    "mesa_id": 18,
    "assento_id": 142,
    "convite_id": 901,
    "formando_id": 101,
    "status": "hold",
    "origem": "formando",
    "idempotency_key": "res-2026-04-17-abc123",
    "hold_expires_at": "2026-04-17T12:05:00-03:00",
    "confirmado_at": null
}
```

### E.6 `reservas_historico`

**Propósito.** Append-only: cada transição de reserva (criação, confirmação, cancelamento, troca).

| Campo            | Tipo          | Nullable | Default | Descrição                                                                                     |
| ---------------- | ------------- | -------- | ------- | --------------------------------------------------------------------------------------------- |
| `id`             | `BIGSERIAL`   | não      | —       | —                                                                                             |
| `reserva_id`     | `BIGINT`      | não      | —       | FK CASCADE                                                                                    |
| `de_status`      | `VARCHAR(20)` | sim      | null    | —                                                                                             |
| `para_status`    | `VARCHAR(20)` | não      | —       | —                                                                                             |
| `ator_tipo`      | `VARCHAR(30)` | sim      | null    | —                                                                                             |
| `ator_id`        | `BIGINT`      | sim      | null    | —                                                                                             |
| `motivo`         | `VARCHAR(40)` | sim      | null    | `criacao`, `confirmacao`, `troca`, `expiracao`, `cancelamento_formando`, `cancelamento_admin` |
| `correlation_id` | `CHAR(26)`    | sim      | null    | —                                                                                             |
| `ocorreu_em`     | `TIMESTAMPTZ` | não      | `now()` | —                                                                                             |
| `created_at`     | `TIMESTAMPTZ` | não      | `now()` | —                                                                                             |

**Índices.** `INDEX (reserva_id, ocorreu_em)`, `INDEX (para_status)`.

---

## Bloco F — Extras e Pagamentos Operacionais (F6)

Bounded context: `Extras`. Convite extra, kit extra, upgrade.

### F.1 `produtos_extras`

**Propósito.** Catálogo de produtos vendidos fora do pacote (convite extra, kit brinde, upgrade de mesa VIP). Com controle de estoque simples.

| Campo                      | Tipo           | Nullable | Default    | Descrição                                       |
| -------------------------- | -------------- | -------- | ---------- | ----------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —          | —                                               |
| `ulid`                     | `CHAR(26)`     | não      | —          | —                                               |
| `evento_id`                | `BIGINT`       | não      | —          | FK RESTRICT                                     |
| `nome`                     | `VARCHAR(120)` | não      | —          | —                                               |
| `descricao`                | `TEXT`         | sim      | null       | —                                               |
| `tipo`                     | `VARCHAR(30)`  | não      | —          | `convite_extra`, `kit`, `upgrade_mesa`, `outro` |
| `preco_centavos`           | `INTEGER`      | não      | —          | —                                               |
| `estoque_tipo`             | `VARCHAR(20)`  | não      | `'finito'` | `finito`, `infinito`                            |
| `estoque_qtd`              | `INTEGER`      | sim      | null       | Quantidade total; null se infinito              |
| `estoque_reservado`        | `INTEGER`      | não      | `0`        | Atual em pedidos não pagos                      |
| `max_por_formando`         | `SMALLINT`     | não      | `0`        | 0 = ilimitado                                   |
| `disponivel_de`            | `TIMESTAMPTZ`  | sim      | null       | —                                               |
| `disponivel_ate`           | `TIMESTAMPTZ`  | sim      | null       | —                                               |
| `ativo`                    | `BOOLEAN`      | não      | `true`     | —                                               |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`    | —                                               |

**Índices.** `UNIQUE (ulid)`, `INDEX (evento_id, ativo)`, `INDEX (tipo)`.

**CHECK.** `estoque_tipo IN ('finito','infinito')`, `preco_centavos >= 0`, `tipo IN ('convite_extra','kit','upgrade_mesa','outro')`, `(estoque_tipo = 'infinito') OR (estoque_qtd IS NOT NULL AND estoque_qtd >= 0)`.

### F.2 `pedidos_extras`

**Propósito.** Pedido do formando para aquisição de extras. Estado transita `pendente_pagamento → pago → fulfilled` ou `cancelado`/`estornado`.

| Campo                      | Tipo          | Nullable | Default                | Descrição                                                           |
| -------------------------- | ------------- | -------- | ---------------------- | ------------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —                      | —                                                                   |
| `ulid`                     | `CHAR(26)`    | não      | —                      | —                                                                   |
| `evento_id`                | `BIGINT`      | não      | —                      | FK RESTRICT                                                         |
| `formando_id`              | `BIGINT`      | não      | —                      | FK RESTRICT                                                         |
| `status`                   | `VARCHAR(25)` | não      | `'pendente_pagamento'` | `pendente_pagamento`, `pago`, `cancelado`, `estornado`, `fulfilled` |
| `valor_total_centavos`     | `INTEGER`     | não      | —                      | —                                                                   |
| `idempotency_key`          | `VARCHAR(80)` | não      | —                      | —                                                                   |
| `snapshot_pedido`          | `JSONB`       | não      | `'{}'`                 | Congelado na criação                                                |
| `pago_at`                  | `TIMESTAMPTZ` | sim      | null                   | —                                                                   |
| `cancelado_at`             | `TIMESTAMPTZ` | sim      | null                   | —                                                                   |
| `estornado_at`             | `TIMESTAMPTZ` | sim      | null                   | —                                                                   |
| `correlation_id`           | `CHAR(26)`    | sim      | null                   | —                                                                   |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()`                | —                                                                   |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (idempotency_key)`, `INDEX (formando_id, status)`, `INDEX (evento_id, status)`.

**CHECK.** `status IN ('pendente_pagamento','pago','cancelado','estornado','fulfilled')`.

### F.3 `pedido_extra_itens`

**Propósito.** Itens do pedido extra (snapshot de preço).

| Campo                      | Tipo          | Nullable | Default | Descrição                       |
| -------------------------- | ------------- | -------- | ------- | ------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —       | —                               |
| `pedido_extra_id`          | `BIGINT`      | não      | —       | FK CASCADE                      |
| `produto_extra_id`         | `BIGINT`      | não      | —       | FK RESTRICT                     |
| `qtd`                      | `SMALLINT`    | não      | `1`     | —                               |
| `preco_unitario_centavos`  | `INTEGER`     | não      | —       | Snapshot                        |
| `subtotal_centavos`        | `INTEGER`     | não      | —       | `qtd * preco_unitario_centavos` |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —                               |

**Índices.** `INDEX (pedido_extra_id)`, `INDEX (produto_extra_id)`.

### F.4 `webhook_eventos`

**Propósito.** Registro idempotente de todo webhook externo recebido. `UNIQUE (provider, gateway_reference)` garante que o mesmo evento nunca é aplicado duas vezes. Append-only para os campos de payload; mutável apenas em status/processado_at/tentativas.

| Campo                      | Tipo           | Nullable | Default      | Descrição                                        |
| -------------------------- | -------------- | -------- | ------------ | ------------------------------------------------ |
| `id`                       | `BIGSERIAL`    | não      | —            | —                                                |
| `provider`                 | `VARCHAR(30)`  | não      | —            | `itau`, `mock`                                   |
| `evento_tipo`              | `VARCHAR(60)`  | não      | —            | `pagamento.confirmado`, `pagamento.estornado`    |
| `gateway_reference`        | `VARCHAR(120)` | não      | —            | ID único no provedor                             |
| `payload`                  | `JSONB`        | não      | —            | Bruto                                            |
| `assinatura_valida_hash`   | `VARCHAR(128)` | sim      | null         | hash da assinatura recebida                      |
| `status`                   | `VARCHAR(20)`  | não      | `'recebido'` | `recebido`, `processado`, `falhou`, `descartado` |
| `tentativas`               | `INTEGER`      | não      | `0`          | —                                                |
| `ultimo_erro`              | `TEXT`         | sim      | null         | —                                                |
| `correlation_id`           | `CHAR(26)`     | sim      | null         | —                                                |
| `recebido_at`              | `TIMESTAMPTZ`  | não      | `now()`      | —                                                |
| `processado_at`            | `TIMESTAMPTZ`  | sim      | null         | —                                                |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                                |

**Índices.** `UNIQUE (provider, gateway_reference)`, `INDEX (status, recebido_at)`, `INDEX (evento_tipo)`.

**CHECK.** `status IN ('recebido','processado','falhou','descartado')`.

---

## Bloco G — Engajamento (Enquetes) (F6)

Bounded context: `Enquetes`.

### G.1 `enquetes`

**Propósito.** Enquete/votação do evento (temáticas: cores, playlist, pauta). Publicável em janela definida.

| Campo                      | Tipo           | Nullable | Default      | Descrição                                                       |
| -------------------------- | -------------- | -------- | ------------ | --------------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —            | —                                                               |
| `ulid`                     | `CHAR(26)`     | não      | —            | —                                                               |
| `evento_id`                | `BIGINT`       | não      | —            | FK RESTRICT                                                     |
| `titulo`                   | `VARCHAR(180)` | não      | —            | —                                                               |
| `descricao`                | `TEXT`         | sim      | null         | —                                                               |
| `tipo`                     | `VARCHAR(20)`  | não      | —            | `escolha_unica`, `escolha_multipla`, `ordenacao`, `texto_livre` |
| `status`                   | `VARCHAR(20)`  | não      | `'rascunho'` | `rascunho`, `publicada`, `encerrada`, `cancelada`               |
| `permite_edicao`           | `BOOLEAN`      | não      | `false`      | Ator pode trocar o voto                                         |
| `mostra_resultado_parcial` | `BOOLEAN`      | não      | `true`       | —                                                               |
| `abre_at`                  | `TIMESTAMPTZ`  | sim      | null         | —                                                               |
| `fecha_at`                 | `TIMESTAMPTZ`  | sim      | null         | —                                                               |
| `regra_elegibilidade`      | `JSONB`        | não      | `'{}'`       | Declarativa                                                     |
| `deleted_at`               | `TIMESTAMPTZ`  | sim      | null         | Soft delete em rascunho                                         |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`      | —                                                               |

**Índices.** `UNIQUE (ulid)`, `INDEX (evento_id, status)`, `INDEX (status, abre_at)`.

**CHECK.** `tipo IN ('escolha_unica','escolha_multipla','ordenacao','texto_livre')`, `status IN ('rascunho','publicada','encerrada','cancelada')`.

**Snapshot JSONB (`regra_elegibilidade`).**

```json
{
    "perfis_permitidos": ["formando", "comissao"],
    "turmas_alvo": ["01J5KTURMA..."],
    "rsvp_confirmado_obrigatorio": false,
    "adesao_ativa_obrigatoria": true
}
```

### G.2 `opcoes_enquete`

**Propósito.** Opção disponível em uma enquete.

| Campo                      | Tipo           | Nullable | Default | Descrição  |
| -------------------------- | -------------- | -------- | ------- | ---------- |
| `id`                       | `BIGSERIAL`    | não      | —       | —          |
| `ulid`                     | `CHAR(26)`     | não      | —       | —          |
| `enquete_id`               | `BIGINT`       | não      | —       | FK CASCADE |
| `titulo`                   | `VARCHAR(180)` | não      | —       | —          |
| `ordem`                    | `SMALLINT`     | não      | `0`     | —          |
| `ativa`                    | `BOOLEAN`      | não      | `true`  | —          |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —          |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (enquete_id, titulo)`, `INDEX (enquete_id, ordem)`.

### G.3 `votos`

**Propósito.** Voto de um ator em uma enquete/opção. `UNIQUE (enquete_id, ator_tipo, ator_id, opcao_id)` e — quando `permite_edicao=false` — upsert proibido (erro 409).

| Campo                      | Tipo          | Nullable | Default | Descrição                                |
| -------------------------- | ------------- | -------- | ------- | ---------------------------------------- |
| `id`                       | `BIGSERIAL`   | não      | —       | —                                        |
| `ulid`                     | `CHAR(26)`    | não      | —       | —                                        |
| `enquete_id`               | `BIGINT`      | não      | —       | FK CASCADE                               |
| `opcao_id`                 | `BIGINT`      | sim      | null    | FK RESTRICT (null se `tipo=texto_livre`) |
| `ator_tipo`                | `VARCHAR(30)` | não      | —       | `formando`, `comissao`, `admin`          |
| `ator_id`                  | `BIGINT`      | não      | —       | —                                        |
| `peso`                     | `SMALLINT`    | não      | `1`     | Para ordenação                           |
| `texto_livre`              | `TEXT`        | sim      | null    | —                                        |
| `correlation_id`           | `CHAR(26)`    | sim      | null    | —                                        |
| `created_at`, `updated_at` | `TIMESTAMPTZ` | não      | `now()` | —                                        |

**Índices.**

- `UNIQUE (ulid)`
- `UNIQUE (enquete_id, ator_tipo, ator_id, opcao_id)`
- `INDEX (enquete_id, opcao_id)`, `INDEX (ator_tipo, ator_id)`

---

## Bloco H — Comunicação (F4/F6)

Bounded context: `Comunicacao`. Templates, envios e entregas.

### H.1 `templates_notificacao`

**Propósito.** Template reutilizável (e-mail, push, SMS). Suporta versionamento simples via `deleted_at`.

| Campo                      | Tipo           | Nullable | Default | Descrição                                                  |
| -------------------------- | -------------- | -------- | ------- | ---------------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                                          |
| `ulid`                     | `CHAR(26)`     | não      | —       | —                                                          |
| `organizacao_id`           | `BIGINT`       | sim      | null    | Null = global                                              |
| `codigo`                   | `VARCHAR(80)`  | não      | —       | `convite_emitido`, `rsvp_reminder`, `pagamento_confirmado` |
| `canal`                    | `VARCHAR(20)`  | não      | —       | `email`, `push`, `sms`, `in_app`                           |
| `assunto`                  | `VARCHAR(180)` | sim      | null    | Para email                                                 |
| `corpo_template`           | `TEXT`         | não      | —       | Blade-like/Markdown                                        |
| `variaveis`                | `JSONB`        | não      | `'{}'`  | Schema de variáveis esperadas                              |
| `ativo`                    | `BOOLEAN`      | não      | `true`  | —                                                          |
| `deleted_at`               | `TIMESTAMPTZ`  | sim      | null    | —                                                          |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                                          |

**Índices.** `UNIQUE (ulid)`, `UNIQUE (organizacao_id, codigo, canal)`.

**CHECK.** `canal IN ('email','push','sms','in_app')`.

### H.2 `notificacoes`

**Propósito.** Registro de notificação disparada (1 por envio). Estado `fila → enviada → falhou`.

| Campo                      | Tipo           | Nullable | Default  | Descrição                                             |
| -------------------------- | -------------- | -------- | -------- | ----------------------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —        | —                                                     |
| `ulid`                     | `CHAR(26)`     | não      | —        | —                                                     |
| `evento_id`                | `BIGINT`       | sim      | null     | FK SET NULL                                           |
| `template_id`              | `BIGINT`       | não      | —        | FK RESTRICT                                           |
| `destinatario_tipo`        | `VARCHAR(30)`  | não      | —        | `formando`, `convite`, `admin`, `comissao`, `externo` |
| `destinatario_id`          | `BIGINT`       | sim      | null     | —                                                     |
| `canal`                    | `VARCHAR(20)`  | não      | —        | —                                                     |
| `assunto`                  | `VARCHAR(180)` | sim      | null     | —                                                     |
| `payload_json`             | `JSONB`        | não      | `'{}'`   | Variáveis renderizadas                                |
| `status`                   | `VARCHAR(20)`  | não      | `'fila'` | `fila`, `enviada`, `falhou`, `cancelada`              |
| `tentativas`               | `INTEGER`      | não      | `0`      | —                                                     |
| `erro_ultimo`              | `TEXT`         | sim      | null     | —                                                     |
| `enviada_at`               | `TIMESTAMPTZ`  | sim      | null     | —                                                     |
| `correlation_id`           | `CHAR(26)`     | sim      | null     | —                                                     |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()`  | —                                                     |

**Índices.** `UNIQUE (ulid)`, `INDEX (status, created_at)`, `INDEX (destinatario_tipo, destinatario_id)`, `INDEX (template_id)`.

### H.3 `notificacao_entregas`

**Propósito.** Entrega e acompanhamento (delivered/open/click/bounce).

| Campo                | Tipo           | Nullable | Default | Descrição                                                           |
| -------------------- | -------------- | -------- | ------- | ------------------------------------------------------------------- |
| `id`                 | `BIGSERIAL`    | não      | —       | —                                                                   |
| `notificacao_id`     | `BIGINT`       | não      | —       | FK CASCADE                                                          |
| `provedor`           | `VARCHAR(30)`  | não      | —       | `mailgun`, `ses`, `smtp`, `expo`, `twilio`                          |
| `provedor_reference` | `VARCHAR(120)` | sim      | null    | —                                                                   |
| `evento`             | `VARCHAR(30)`  | não      | —       | `delivered`, `opened`, `clicked`, `bounced`, `spam`, `unsubscribed` |
| `meta_json`          | `JSONB`        | não      | `'{}'`  | Detalhes do provedor                                                |
| `ocorreu_em`         | `TIMESTAMPTZ`  | não      | `now()` | —                                                                   |
| `created_at`         | `TIMESTAMPTZ`  | não      | `now()` | (sem updated_at)                                                    |

**Índices.** `INDEX (notificacao_id, ocorreu_em)`, `INDEX (evento, ocorreu_em)`.

---

## Infraestrutura transversal

### I.1 `activity_log` (spatie/laravel-activitylog)

**Propósito.** Auditoria append-only. Nunca `DELETE`. Usado em: `Adesao`, `Convite`, `Pagamento`, `ReservaAssento`, `PedidoExtra`, `Voto`, `AdminUser` (login/logout).

| Campo                      | Tipo           | Nullable | Default | Descrição                               |
| -------------------------- | -------------- | -------- | ------- | --------------------------------------- |
| `id`                       | `BIGSERIAL`    | não      | —       | —                                       |
| `log_name`                 | `VARCHAR(120)` | sim      | null    | Bucket (convites, pagamentos)           |
| `description`              | `TEXT`         | não      | —       | —                                       |
| `subject_type`             | `VARCHAR(255)` | sim      | null    | —                                       |
| `subject_id`               | `BIGINT`       | sim      | null    | —                                       |
| `causer_type`              | `VARCHAR(255)` | sim      | null    | —                                       |
| `causer_id`                | `BIGINT`       | sim      | null    | —                                       |
| `properties`               | `JSONB`        | sim      | null    | before/after                            |
| `batch_uuid`               | `UUID`         | sim      | null    | —                                       |
| `event`                    | `VARCHAR(60)`  | sim      | null    | `created`, `updated`, `deleted`, custom |
| `correlation_id`           | `CHAR(26)`     | sim      | null    | —                                       |
| `created_at`, `updated_at` | `TIMESTAMPTZ`  | não      | `now()` | —                                       |

**Índices.** `INDEX (log_name, created_at)`, `INDEX (subject_type, subject_id)`, `INDEX (causer_type, causer_id)`, `INDEX (event)`.

### I.2 `jobs` (Laravel queue driver "database" como fallback de dev)

Utilizado somente em `local` se Redis estiver indisponível. Em `staging/production`, driver padrão é `redis`.

### I.3 `failed_jobs`

Tabela padrão do Laravel Horizon. DLQ natural.

### I.4 `cache` (Redis — não materializada como tabela)

TTLs relevantes:

- `evento:{ulid}:config` — 30 min
- `evento:{id}:contadores:rsvp` — 60s
- `evento:{id}:mapa:leitura` — 5 min (event-driven invalidation)
- `enquete:{id}:resultado:publico` — 1 min
- `lookup:produtos:evento:{id}` — 30 min
- `permissions:user:{id}` — 10 min

Locks Redis utilizados em operações críticas:

- `seating:assento:{ulid}` — TTL 10s, block 3s
- `pagamento:reconciliacao:{provider}` — singleton

---

## Resumo quantitativo (37 tabelas MVP)

| Bloco                     | Tabelas                                                                                                                              | Fase  |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ | ----- |
| A — Identidade e Acesso   | `admin_users`, `portal_users`, `comissao_users`, `convidado_access_tokens`, `personal_access_tokens` (+ Spatie roles/permissions ×5) | F1    |
| B — Cadastro estrutural   | `organizacoes`, `instituicoes`, `cursos`, `turmas`, `eventos`, `turma_evento`, `formandos`                                           | F1/F2 |
| C — Comercial e Adesão    | `pacotes`, `produtos`, `adesoes`, `adesao_produtos`, `parcelas`, `pagamentos`                                                        | F2    |
| D — Convites e RSVP       | `cotas_regras`, `lotes_convites`, `convites`, `rsvp_historico`                                                                       | F4    |
| E — Seating               | `mapas_mesas`, `setores`, `mesas`, `assentos`, `reservas_assentos`, `reservas_historico`                                             | F5    |
| F — Extras/Pagamentos op. | `produtos_extras`, `pedidos_extras`, `pedido_extra_itens`, `webhook_eventos`                                                         | F6    |
| G — Engajamento           | `enquetes`, `opcoes_enquete`, `votos`                                                                                                | F6    |
| H — Comunicação           | `templates_notificacao`, `notificacoes`, `notificacao_entregas`                                                                      | F4/F6 |
| Transversal               | `activity_log`, `jobs`, `failed_jobs`                                                                                                | todas |

**Observabilidade de dados.**

- Colunas `correlation_id` atravessando blocos C, D, E, F permitem reconstrução de jornada por `UNION ALL` — ver `PLANEJAMENTO_BACKEND_APIV1.md` §12.4.
- Unique parciais previnem conflitos em seating e em adesões ativas.
- Snapshot JSONB em `adesoes.snapshot_comercial`, `convites.snapshot_regra`, `pedidos_extras.snapshot_pedido`, `enquetes.regra_elegibilidade`, `eventos.config_json` garante imutabilidade temporal de decisões comerciais/operacionais.

---

## Cross-references com API

| Tabela                                | Endpoints principais (ver `docs/api/api-contract.md`)                                                     |
| ------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `portal_users`                        | `POST /auth/login`, `POST /auth/logout`, `GET /me`                                                        |
| `eventos`                             | `GET /eventos/{ulid}`                                                                                     |
| `adesoes`                             | `GET /me/adesoes`, `GET /me/extrato`                                                                      |
| `convites`                            | `GET /eventos/{ev}/convites`, `POST /eventos/{ev}/convites`, `PATCH /eventos/{ev}/convites/{c}`, `DELETE` |
| `lotes_convites`                      | `POST /eventos/{ev}/convites/lotes`, `GET /eventos/{ev}/convites/lotes/{lote}`                            |
| `rsvp_historico`                      | `POST /convite/{token}/rsvp`, `GET /convite/{token}`                                                      |
| `mapas_mesas`, `mesas`, `assentos`    | `GET /eventos/{ev}/mesas/mapa`                                                                            |
| `reservas_assentos`                   | `POST /eventos/{ev}/mesas/reservas`, `POST /.../confirmar`, `DELETE`, `POST /.../trocar`                  |
| `produtos_extras`                     | `GET /eventos/{ev}/extras/catalogo`                                                                       |
| `pedidos_extras`                      | `POST /eventos/{ev}/extras/pedidos`, `GET /eventos/{ev}/extras/pedidos/{p}`                               |
| `pagamentos`                          | `POST /pagamentos/intents`, `GET /pagamentos/{p}`                                                         |
| `webhook_eventos`                     | `POST /webhooks/pagamentos/{provider}`                                                                    |
| `enquetes`, `opcoes_enquete`, `votos` | `GET /eventos/{ev}/enquetes`, `GET .../{enq}`, `POST .../{enq}/votos`                                     |

---

**Fim do documento.** Ver também:

- `docs/data/er-diagram.md` — diagramas Mermaid
- `docs/data/migrations-plan.md` — ordem e dependências
- `docs/data/domain-glossary.md` — glossário PT-BR
