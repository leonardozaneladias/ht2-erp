---
title: Plano de Migrations — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Plano de Migrations — Portal ArtFinal v2

Plano executável de criação de tabelas, em ordem topológica de dependências de FK. Cada migration está atribuída a uma fase do cronograma (F1–F8) conforme `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §14.

> **Regra de ouro.** Nenhuma migration cruza bounded context. Se uma mudança afeta dois contextos, criar **duas** migrations correlacionadas.

## Convenções dos arquivos de migration

- Nome: `YYYY_MM_DD_HHMMSS_<verbo>_<tabela_ou_campo>_table.php` (ou `..._to_<tabela>_table` em `ALTER`).
- Sempre `declare(strict_types=1);` no topo.
- Toda tabela possui: `id BIGSERIAL`, `ulid CHAR(26) UNIQUE`, `created_at TIMESTAMPTZ`, `updated_at TIMESTAMPTZ`. Use `$table->timestampsTz()`.
- Timezones: `$table->timestampTz(...)`.
- Moeda: `$table->unsignedInteger('valor_*_centavos')`.
- Enums: `$table->string('<campo>', 20)` + `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK (<campo> IN (...))')`.
- JSONB: `$table->jsonb('<campo>')`.
- FK: `$table->foreignId('<x>_id')->constrained('<tabela>')->restrictOnDelete()`.
- `down()` deve ser idempotente — sempre `Schema::dropIfExists(...)`.
- Unique parcial e CHECK complexo via `DB::statement(<<<'SQL' ... SQL)`.
- Rodar em PostgreSQL 16 (não suporta certos índices expression em MySQL — migrations assumem Postgres).

## Ordem de execução consolidada (resumo)

| Ordem | Bloco | Fase | Arquivo                                                                   |
| ----: | ----- | ---- | ------------------------------------------------------------------------- |
|    01 | A     | F1   | `create_admin_users_table` (se ainda não existir)                         |
|    02 | A     | F1   | `extend_admin_users_add_ulid_profile`                                     |
|    03 | A     | F1   | `create_portal_users_table` (ou `extend_portal_users_add_ulid_tipo`)      |
|    04 | A     | F1   | `create_comissao_users_table` _(opcional — fundido em portal_users.tipo)_ |
|    05 | A     | F1   | `create_personal_access_tokens_table` (vendor Sanctum)                    |
|    06 | A     | F1   | `create_convidado_access_tokens_table`                                    |
|    07 | A     | F1   | `create_permission_tables` (vendor spatie/laravel-permission)             |
|    08 | B     | F1   | `create_organizacoes_table`                                               |
|    09 | B     | F1   | `create_instituicoes_table`                                               |
|    10 | B     | F1   | `create_cursos_table`                                                     |
|    11 | B     | F1   | `create_turmas_table`                                                     |
|    12 | B     | F1   | `create_eventos_table`                                                    |
|    13 | B     | F1   | `create_turma_evento_table`                                               |
|    14 | B     | F1   | `create_formandos_table`                                                  |
|    15 | C     | F2   | `create_pacotes_table`                                                    |
|    16 | C     | F2   | `create_produtos_table`                                                   |
|    17 | C     | F2   | `create_pacote_produtos_table`                                            |
|    18 | C     | F2   | `create_adesoes_table`                                                    |
|    19 | C     | F2   | `create_adesao_produtos_table`                                            |
|    20 | C     | F2   | `create_parcelas_table`                                                   |
|    21 | C     | F2   | `create_pagamentos_table`                                                 |
|    22 | D     | F4   | `create_cotas_regras_table`                                               |
|    23 | D     | F4   | `create_lotes_convites_table`                                             |
|    24 | D     | F4   | `create_convites_table`                                                   |
|    25 | D     | F4   | `create_rsvp_historico_table`                                             |
|    26 | E     | F5   | `create_mapas_mesas_table`                                                |
|    27 | E     | F5   | `create_setores_table`                                                    |
|    28 | E     | F5   | `create_mesas_table`                                                      |
|    29 | E     | F5   | `create_assentos_table`                                                   |
|    30 | E     | F5   | `create_reservas_assentos_table`                                          |
|    31 | E     | F5   | `create_reservas_historico_table`                                         |
|    32 | F     | F6   | `create_produtos_extras_table`                                            |
|    33 | F     | F6   | `create_pedidos_extras_table`                                             |
|    34 | F     | F6   | `create_pedido_extra_itens_table`                                         |
|    35 | F     | F6   | `create_webhook_eventos_table`                                            |
|    36 | F     | F6   | `alter_pagamentos_add_pedido_extra_fk`                                    |
|    37 | G     | F6   | `create_enquetes_table`                                                   |
|    38 | G     | F6   | `create_opcoes_enquete_table`                                             |
|    39 | G     | F6   | `create_votos_table`                                                      |
|    40 | H     | F4   | `create_templates_notificacao_table`                                      |
|    41 | H     | F4   | `create_notificacoes_table`                                               |
|    42 | H     | F4   | `create_notificacao_entregas_table`                                       |
|    43 | ∗     | F1   | `create_activity_log_table` (vendor spatie)                               |
|    44 | ∗     | F7   | `alter_tables_add_correlation_id` (se não criado junto)                   |

Arquivos iniciando por `∗` são infraestruturais/transversais.

---

## Bloco A — Identidade e Acesso (F1)

### 01. `YYYY_MM_DD_HHMMSS_create_admin_users_table`

**Fase.** F1.
**Dependências.** Nenhuma.
**Operações.**

- `id BIGSERIAL PK`, `ulid CHAR(26) UNIQUE`
- `nome VARCHAR(120)`, `email VARCHAR(150) UNIQUE`
- `password VARCHAR(255)`, `email_verified_at TIMESTAMPTZ NULL`
- `ativo BOOLEAN DEFAULT true`, `mfa_secret VARCHAR(128) NULL`
- `ultimo_login_at TIMESTAMPTZ NULL`, `remember_token VARCHAR(100) NULL`
- Timestamps TZ.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (email)`, `INDEX (ativo)`.
**CHECK.** nenhum.
**Rollback.** `Schema::dropIfExists('admin_users')`.

### 02. `YYYY_MM_DD_HHMMSS_create_portal_users_table`

**Fase.** F1.
**Dependências.** Nenhuma.
**Operações.**

- Colunas idem `admin_users` +
- `cpf VARCHAR(14) NULL`, `telefone VARCHAR(30) NULL`
- `tipo VARCHAR(20) DEFAULT 'formando'`

**Índices.** `UNIQUE (ulid)`, `UNIQUE (email)`, `UNIQUE (cpf) WHERE cpf IS NOT NULL`, `INDEX (tipo)`, `INDEX (ativo)`.
**CHECK.** `tipo IN ('formando','comissao','responsavel_financeiro')`.
**Custom SQL.**

```sql
CREATE UNIQUE INDEX portal_users_cpf_unique
ON portal_users (cpf)
WHERE cpf IS NOT NULL;

ALTER TABLE portal_users
ADD CONSTRAINT portal_users_tipo_valido
CHECK (tipo IN ('formando','comissao','responsavel_financeiro'));
```

### 03. `YYYY_MM_DD_HHMMSS_create_personal_access_tokens_table`

**Fase.** F1.
**Dependências.** `admin_users`, `portal_users` (polimórfica; não força FK).
**Operações.** Usar migration publicada de Sanctum: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`.
Colunas: `tokenable_type`, `tokenable_id`, `name`, `token CHAR(64) UNIQUE`, `abilities TEXT`, `last_used_at`, `expires_at`.
**Índices.** `INDEX (tokenable_type, tokenable_id)`, `UNIQUE (token)`.

### 04. `YYYY_MM_DD_HHMMSS_create_convidado_access_tokens_table`

**Fase.** F1.
**Dependências.** `convites` (FK) — **atenção:** como essa migration roda em F1 mas `convites` entra em F4, a FK é `nullable` e recebe `constrained` via migration posterior em F4 (`alter_convidado_access_tokens_add_convite_fk`). Alternativa: mover esta migration para o final de F4. **Decisão adotada:** criar sem FK em F1; adicionar FK em F4.

**Operações.**

- `id BIGSERIAL PK`, `ulid CHAR(26) UNIQUE`
- `convite_id BIGINT NOT NULL` (FK criada em F4)
- `token_hash CHAR(64) UNIQUE`
- `expires_at TIMESTAMPTZ NOT NULL`
- `revogado_at TIMESTAMPTZ NULL`
- Timestamps.

**Índices.** `UNIQUE (token_hash)`, `INDEX (expires_at)`.

### 05. `YYYY_MM_DD_HHMMSS_create_permission_tables`

**Fase.** F1.
**Dependências.** Nenhuma (vendor).
**Operações.** `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`.
**Seed obrigatório.** Roles `admin`, `comissao`, `formando` nos guards corretos (`admin`, `sanctum`, `sanctum`).

---

## Bloco B — Cadastro Estrutural (F1)

### 08. `create_organizacoes_table`

**Fase.** F1.
**Operações.** `id`, `ulid`, `nome VARCHAR(150)`, `cnpj VARCHAR(18) UNIQUE`, `ativo BOOLEAN DEFAULT true`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (cnpj)`, `INDEX (ativo)`.

### 09. `create_instituicoes_table`

**Fase.** F1.
**Dependências.** `organizacoes`.
**Operações.** `id`, `ulid`, `organizacao_id FK RESTRICT`, `nome VARCHAR(150)`, `cidade VARCHAR(80)`, `estado CHAR(2)`, `ativo`, timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (organizacao_id)`, `INDEX (estado)`.

### 10. `create_cursos_table`

**Fase.** F1.
**Dependências.** `instituicoes`.
**Operações.** `id`, `ulid`, `instituicao_id FK RESTRICT`, `nome VARCHAR(120)`, `grau VARCHAR(30)`, `ativo`, timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (instituicao_id)`.

**CHECK.** `grau IN ('graduacao','pos','tecnico','mestrado','doutorado','livre')`.

### 11. `create_turmas_table`

**Fase.** F1.
**Dependências.** `instituicoes`, `cursos`.
**Operações.** `id`, `ulid`, `instituicao_id FK`, `curso_id FK`, `codigo VARCHAR(30)`, `ano_ingresso SMALLINT`, `ativo`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (instituicao_id, curso_id, codigo)`, `INDEX (ano_ingresso)`.

### 12. `create_eventos_table`

**Fase.** F1.
**Dependências.** Nenhuma.
**Operações.**

- `id`, `ulid`
- `slug VARCHAR(80) UNIQUE`, `nome VARCHAR(150)`
- `data_evento TIMESTAMPTZ`, `timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo'`
- `abre_rsvp_at TIMESTAMPTZ NULL`, `fecha_rsvp_at TIMESTAMPTZ NULL`
- `abre_mesas_at TIMESTAMPTZ NULL`, `fecha_mesas_at TIMESTAMPTZ NULL`
- `status VARCHAR(20) DEFAULT 'rascunho'`
- `config_json JSONB`
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (slug)`, `INDEX (status)`, `INDEX (data_evento)`.
**CHECK.** `status IN ('rascunho','publicado','encerrado','arquivado')`.

### 13. `create_turma_evento_table`

**Fase.** F1.
**Dependências.** `turmas`, `eventos`.
**Operações.** `turma_id FK`, `evento_id FK`, `created_at`.

**Índices.** `UNIQUE (turma_id, evento_id)`, `INDEX (evento_id)`.

### 14. `create_formandos_table`

**Fase.** F1.
**Dependências.** `turmas`, `portal_users`.
**Operações.**

- `id`, `ulid`
- `turma_id FK RESTRICT`
- `portal_user_id FK RESTRICT`
- `nome_social VARCHAR(120) NULL`
- `status VARCHAR(20) DEFAULT 'ativo'`
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (turma_id, portal_user_id)`, `INDEX (status)`.
**CHECK.** `status IN ('ativo','inativo','transferido','evadido')`.

---

## Bloco C — Comercial e Adesão (F2)

### 15. `create_pacotes_table`

**Fase.** F2.
**Dependências.** `eventos`.
**Operações.**

- `id`, `ulid`
- `evento_id FK RESTRICT`
- `nome VARCHAR(120)`
- `preco_centavos INT UNSIGNED`
- `parcelas_maximo SMALLINT UNSIGNED DEFAULT 1`
- `ativo BOOLEAN DEFAULT true`
- `deleted_at TIMESTAMPTZ NULL` (soft delete permitido)
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (evento_id, ativo)`.

### 16. `create_produtos_table`

**Fase.** F2.
**Dependências.** Nenhuma.
**Operações.** `id`, `ulid`, `nome VARCHAR(120)`, `categoria VARCHAR(40)`, `preco_centavos INT UNSIGNED`, `ativo`, `deleted_at`, timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (categoria, ativo)`.

### 17. `create_pacote_produtos_table`

**Fase.** F2.
**Dependências.** `pacotes`, `produtos`.
**Operações.** `pacote_id FK`, `produto_id FK`, `quantidade SMALLINT UNSIGNED DEFAULT 1`.

**Índices.** `UNIQUE (pacote_id, produto_id)`.

### 18. `create_adesoes_table`

**Fase.** F2.
**Dependências.** `formandos`, `eventos`, `pacotes`.
**Operações.**

- `id`, `ulid`
- `formando_id FK RESTRICT`, `evento_id FK RESTRICT`, `pacote_id FK RESTRICT`
- `status VARCHAR(20)`
- `valor_total_centavos INT UNSIGNED`
- `valor_entrada_centavos INT UNSIGNED DEFAULT 0`
- `qtd_parcelas SMALLINT UNSIGNED`
- `snapshot_comercial JSONB`
- `termo_hash CHAR(64) NULL`
- `aceito_em TIMESTAMPTZ NULL`
- `confirmada_at TIMESTAMPTZ NULL`
- `cancelada_at TIMESTAMPTZ NULL`
- `motivo_cancelamento TEXT NULL`
- `correlation_id CHAR(26) NULL`
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (formando_id, status)`, `INDEX (evento_id, status)`, `INDEX (correlation_id)`.

**Custom SQL.**

```sql
CREATE UNIQUE INDEX adesoes_ativa_por_formando_evento
ON adesoes (formando_id, evento_id)
WHERE status IN ('pendente_pagamento', 'ativa');

ALTER TABLE adesoes
ADD CONSTRAINT adesoes_status_valido
CHECK (status IN ('rascunho','pendente_pagamento','ativa','cancelada','inadimplente','concluida'));
```

**Rollback.** DROP INDEX antes do `dropIfExists`.

### 19. `create_adesao_produtos_table`

**Fase.** F2.
**Dependências.** `adesoes`, `produtos`.
**Operações.** `adesao_id FK`, `produto_id FK`, `quantidade SMALLINT DEFAULT 1`, `preco_unitario_centavos INT UNSIGNED`, `snapshot JSONB`.

**Índices.** `INDEX (adesao_id)`, `UNIQUE (adesao_id, produto_id)`.

### 20. `create_parcelas_table`

**Fase.** F2.
**Dependências.** `adesoes`.
**Operações.**

- `id`, `ulid`
- `adesao_id FK RESTRICT`
- `numero SMALLINT UNSIGNED`
- `valor_centavos INT UNSIGNED`
- `vencimento DATE`
- `status VARCHAR(20) DEFAULT 'pendente'`
- `pago_em TIMESTAMPTZ NULL`
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (adesao_id, numero)`, `INDEX (status, vencimento)`.
**CHECK.** `status IN ('pendente','paga','vencida','cancelada')`.

### 21. `create_pagamentos_table`

**Fase.** F2 (campo `pedido_extra_id` nullable; FK adicionada em F6 — ver migration 36).
**Dependências.** `parcelas`.
**Operações.**

- `id`, `ulid`
- `parcela_id BIGINT NULL` (FK RESTRICT)
- `pedido_extra_id BIGINT NULL` (FK adicionada em F6)
- `provider VARCHAR(30)`
- `gateway_reference VARCHAR(120)`
- `status VARCHAR(20) DEFAULT 'pendente'`
- `valor_centavos INT UNSIGNED`
- `pago_em TIMESTAMPTZ NULL`
- `correlation_id CHAR(26) NULL`
- `payload_confirmacao JSONB NULL`
- Timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (provider, gateway_reference)`, `INDEX (parcela_id, status)`, `INDEX (pedido_extra_id, status)`.
**CHECK.**

- `status IN ('pendente','autorizado','pago','falhou','estornado')`
- `(parcela_id IS NOT NULL) OR (pedido_extra_id IS NOT NULL)` — pagamento sempre tem origem.

---

## Bloco D — Convites e RSVP (F4)

### 22. `create_cotas_regras_table`

**Fase.** F4.
**Dependências.** `eventos`.
**Operações.** `id`, `ulid`, `evento_id FK`, `tipo VARCHAR(20)`, `qtd_por_formando SMALLINT UNSIGNED`, `permite_transferencia BOOLEAN`, `politica JSONB`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (evento_id, tipo)`.
**CHECK.** `tipo IN ('base','transferivel','cortesia','staff','extra')`.

### 23. `create_lotes_convites_table`

**Fase.** F4.
**Dependências.** `eventos`.
**Operações.** `id`, `ulid`, `evento_id FK`, `lote_numero SMALLINT`, `qtd_total INT UNSIGNED`, `status VARCHAR(20)`, `emitido_at TIMESTAMPTZ NULL`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (evento_id, lote_numero)`.

### 24. `create_convites_table`

**Fase.** F4.
**Dependências.** `eventos`, `formandos`, `lotes_convites`, `pedidos_extras` (NOT YET — FK adicionada em migration alter na F6).
**Operações.** ver `data-model.md` e planejamento §4.4.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (codigo)`, `UNIQUE (token_hash)`, `INDEX (evento_id, status)`, `INDEX (formando_id, status)`, `INDEX (correlation_id)`.

**CHECK.**

- `tipo IN ('nominal','transferivel','cortesia','staff','extra')`
- `status IN ('rascunho','emitido','enviado','visualizado','confirmado','recusado','cancelado','inutilizado')`

### 25. `create_rsvp_historico_table`

**Fase.** F4.
**Dependências.** `convites`.
**Operações.** `id`, `ulid`, `convite_id FK`, `status_anterior VARCHAR(30)`, `status_novo VARCHAR(30)`, `origem VARCHAR(20)`, `payload JSONB`, `correlation_id CHAR(26)`, timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (convite_id, created_at)`, `INDEX (correlation_id)`.
**CHECK.** `origem IN ('link_magico','portal','admin','sistema')`.

### 25b. `alter_convidado_access_tokens_add_convite_fk` (correção F1→F4)

**Fase.** F4.
**Operações.** Adiciona FK `convidado_access_tokens.convite_id → convites.id ON DELETE CASCADE`.

---

## Bloco E — Seating (F5)

### 26. `create_mapas_mesas_table`

**Fase.** F5.
**Dependências.** `eventos`.
**Operações.** `id`, `ulid`, `evento_id FK`, `nome VARCHAR(80)`, `status VARCHAR(20)`, `layout JSONB NULL` (preferência layout renderizado), timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (evento_id, nome)`.
**CHECK.** `status IN ('rascunho','publicado','arquivado')`.

### 27. `create_setores_table`

**Fase.** F5.
**Dependências.** `mapas_mesas`.
**Operações.** `id`, `ulid`, `mapa_id FK`, `nome VARCHAR(60)`, `cor VARCHAR(20) NULL`, `ordem SMALLINT DEFAULT 0`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (mapa_id, nome)`.

### 28. `create_mesas_table`

**Fase.** F5.
**Dependências.** `setores`, `eventos`.
**Operações.** `id`, `ulid`, `setor_id FK`, `evento_id FK` (denormalização), `numero VARCHAR(10)`, `capacidade SMALLINT`, `x_coord INT NULL`, `y_coord INT NULL`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (evento_id, numero)`, `INDEX (setor_id)`.

### 29. `create_assentos_table`

**Fase.** F5.
**Dependências.** `mesas`.
**Operações.** `id`, `ulid`, `mesa_id FK`, `numero SMALLINT`, `status VARCHAR(15) DEFAULT 'livre'`, timestamps.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (mesa_id, numero)`.
**CHECK.** `status IN ('livre','bloqueado')`.

### 30. `create_reservas_assentos_table`

**Fase.** F5 (**crítico**).
**Dependências.** `eventos`, `mesas`, `assentos`, `convites`, `formandos`.
**Operações.** ver `data-model.md` e planejamento §4.3.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (idempotency_key)`, `INDEX (evento_id, status)`, `INDEX (mesa_id, status)`, `INDEX (hold_expires_at)`, `INDEX (correlation_id)`.

**Custom SQL.**

```sql
CREATE UNIQUE INDEX reservas_assentos_ativa_por_assento
ON reservas_assentos (assento_id)
WHERE status IN ('hold', 'confirmada');

ALTER TABLE reservas_assentos
ADD CONSTRAINT reservas_assentos_hold_consistente
CHECK (
    (status = 'hold'       AND hold_expires_at IS NOT NULL AND confirmado_at IS NULL)
 OR (status = 'confirmada' AND confirmado_at   IS NOT NULL)
 OR (status IN ('cancelada','expirada','bloqueada'))
);

ALTER TABLE reservas_assentos
ADD CONSTRAINT reservas_assentos_status_valido
CHECK (status IN ('hold','confirmada','cancelada','expirada','bloqueada'));

ALTER TABLE reservas_assentos
ADD CONSTRAINT reservas_assentos_origem_valida
CHECK (origem IN ('formando','comissao','admin','operacao'));
```

**Rollback.** Antes de `dropIfExists`, execute `DROP INDEX IF EXISTS reservas_assentos_ativa_por_assento;` e `ALTER TABLE ... DROP CONSTRAINT IF EXISTS ...` para as três CHECKs. Drop em cascade se necessário para tabela histórico.

### 31. `create_reservas_historico_table`

**Fase.** F5.
**Dependências.** `reservas_assentos`.
**Operações.** `id`, `ulid`, `reserva_id FK`, `status_anterior`, `status_novo`, `ator_tipo VARCHAR(30)`, `ator_id BIGINT NULL`, `diff JSONB`, timestamps.

**Índices.** `INDEX (reserva_id, created_at)`.

---

## Bloco F — Extras e Pagamentos Operacionais (F6)

### 32. `create_produtos_extras_table`

**Fase.** F6.
**Dependências.** `eventos`.
**Operações.** `id`, `ulid`, `evento_id FK`, `nome`, `categoria`, `preco_centavos`, `estoque_tipo VARCHAR(15)`, `estoque_qtd INT NULL`, `ativo`, timestamps.

**CHECK.** `estoque_tipo IN ('ilimitado','finito')` ; `(estoque_tipo = 'finito' AND estoque_qtd IS NOT NULL) OR (estoque_tipo = 'ilimitado' AND estoque_qtd IS NULL)`.

### 33. `create_pedidos_extras_table`

**Fase.** F6.
**Dependências.** `formandos`, `eventos`.
**Operações.** ver `data-model.md`.

**Índices.** `UNIQUE (ulid)`, `UNIQUE (idempotency_key)`, `INDEX (formando_id, status)`, `INDEX (evento_id, status)`, `INDEX (correlation_id)`.

**CHECK.** `status IN ('rascunho','aguardando_pagamento','pago','cancelado','estornado')`.

### 34. `create_pedido_extra_itens_table`

**Fase.** F6.
**Dependências.** `pedidos_extras`, `produtos_extras`.
**Operações.** `id`, `pedido_id FK`, `produto_extra_id FK`, `quantidade`, `preco_unitario_centavos`, `snapshot_produto JSONB`.

**Índices.** `INDEX (pedido_id)`.

### 35. `create_webhook_eventos_table`

**Fase.** F6 (**crítico — idempotência dura**).
**Dependências.** Nenhuma.
**Operações.** ver `data-model.md` e planejamento §4.5.

**Índices.** `UNIQUE (provider, gateway_reference)`, `INDEX (status, recebido_at)`.
**CHECK.** `status IN ('recebido','processado','falhou','descartado')`.

**Append-only.** Observer/Policy deve impedir `DELETE`; `UPDATE` permitido apenas nos campos `status`, `tentativas`, `ultimo_erro`, `processado_at`.

### 36. `alter_pagamentos_add_pedido_extra_fk`

**Fase.** F6.
**Operações.** Adiciona constraint FK em `pagamentos.pedido_extra_id → pedidos_extras.id RESTRICT`.

### 36b. `alter_convites_add_pedido_extra_fk`

**Fase.** F6.
**Operações.** Adiciona FK `convites.pedido_extra_id → pedidos_extras.id NULL ON DELETE SET NULL` (convites derivados de pedido extra pago).

---

## Bloco G — Engajamento / Enquetes (F6)

### 37. `create_enquetes_table`

**Fase.** F6.
**Dependências.** `eventos`.
**Operações.** `id`, `ulid`, `evento_id FK`, `tipo VARCHAR(15)`, `status VARCHAR(15)`, `abre_at TIMESTAMPTZ NULL`, `fecha_at TIMESTAMPTZ NULL`, `permite_edicao BOOLEAN DEFAULT false`, `resultado_publico BOOLEAN DEFAULT false`, `regra_elegibilidade JSONB NULL`, `titulo VARCHAR(150)`, `descricao TEXT NULL`, `deleted_at TIMESTAMPTZ NULL`, timestamps.

**CHECK.** `tipo IN ('unica','multipla','ranking')` ; `status IN ('rascunho','aberta','encerrada','arquivada')`.

### 38. `create_opcoes_enquete_table`

**Fase.** F6.
**Dependências.** `enquetes`.
**Operações.** `id`, `ulid`, `enquete_id FK`, `rotulo VARCHAR(150)`, `ordem SMALLINT DEFAULT 0`, `meta JSONB NULL`, timestamps.

**Índices.** `UNIQUE (enquete_id, ordem)`.

### 39. `create_votos_table`

**Fase.** F6.
**Dependências.** `enquetes`, `opcoes_enquete`.
**Operações.** `id`, `ulid`, `enquete_id FK`, `opcao_id FK`, `ator_tipo VARCHAR(15)`, `ator_id BIGINT`, `payload JSONB NULL`, `registrado_at TIMESTAMPTZ`, timestamps.

**Índices.** `INDEX (enquete_id)`, `INDEX (opcao_id)`, `INDEX (ator_tipo, ator_id)`.

**Custom SQL.**

```sql
-- Unicidade condicional: só quando enquete não permite edição
-- (a aplicação verifica permite_edicao antes de inserir)
CREATE UNIQUE INDEX votos_unico_por_ator
ON votos (enquete_id, ator_tipo, ator_id)
WHERE enquete_id IS NOT NULL;
-- A condição WHERE enquete.permite_edicao = false não é direto possível em UNIQUE;
-- trata-se na camada de Action via UPSERT (permite_edicao=true) ou INSERT (permite_edicao=false).
```

> **Observação.** Postgres não permite subquery em CHECK; a regra "um voto por enquete onde permite_edicao = false" fica na `RegistrarVotoAction`. O UNIQUE acima garante que sem edição, a duplicidade é impossível.

---

## Bloco H — Comunicação (F4)

### 40. `create_templates_notificacao_table`

**Fase.** F4.
**Operações.** `id`, `ulid`, `canal`, `slug VARCHAR(80) UNIQUE`, `assunto`, `corpo TEXT`, `variaveis_exemplo JSONB`, `ativo`, `deleted_at`, timestamps.
**CHECK.** `canal IN ('email','push','sms')`.

### 41. `create_notificacoes_table`

**Fase.** F4.
**Dependências.** `eventos`, `templates_notificacao`.
**Operações.** `id`, `ulid`, `evento_id FK NULL`, `template_id FK RESTRICT`, `destinatario_tipo`, `destinatario_id BIGINT`, `canal`, `status`, `payload JSONB`, `agendada_para TIMESTAMPTZ NULL`, `enviada_em TIMESTAMPTZ NULL`, `chave_dedup VARCHAR(120) NULL`, timestamps.

**Índices.** `UNIQUE (ulid)`, `INDEX (status, agendada_para)`, `UNIQUE (template_id, destinatario_tipo, destinatario_id, chave_dedup) WHERE chave_dedup IS NOT NULL`.

### 42. `create_notificacao_entregas_table`

**Fase.** F4.
**Dependências.** `notificacoes`.
**Operações.** `id`, `notificacao_id FK`, `provider`, `provider_id VARCHAR(120)`, `status`, `provider_payload JSONB`, `registrado_em TIMESTAMPTZ`, timestamps.

**Índices.** `INDEX (notificacao_id, registrado_em)`, `UNIQUE (provider, provider_id)`.

---

## Infraestrutura Transversal

### 43. `create_activity_log_table`

**Fase.** F1.
**Operações.** vendor `spatie/laravel-activitylog` — `php artisan activitylog:install`.
**Append-only.** Observer global impede `UPDATE` e `DELETE` fora do job `AnonimizarDadosPosEventoJob`.

### 44. `alter_tables_add_correlation_id`

**Fase.** F7 — consolidação.
**Operações.** Adiciona `correlation_id CHAR(26) NULL` + `INDEX` nas tabelas que ainda não possuam (caso algumas sejam criadas sem o campo em fases anteriores). Tabelas elegíveis: `convites`, `rsvp_historico`, `reservas_assentos`, `pedidos_extras`, `pagamentos`, `webhook_eventos`, `adesoes`.

---

## Regras específicas de rollback

1. **Drop reverso da ordem de dependência.** Sempre dropar antes a tabela mais "filha". Evita falhas de FK.
2. **Índices parciais e CHECKs.** Removidos via `DB::statement('DROP INDEX IF EXISTS ...')` e `ALTER TABLE ... DROP CONSTRAINT IF EXISTS ...` _antes_ do `Schema::dropIfExists()`.
3. **Vendor migrations.** Sanctum e Spatie já são idempotentes no `down()`; não duplicar lógica.
4. **Migrations em produção.** Após `down`, rodar `php artisan migrate:rollback --step=1` em staging primeiro.
5. **FK adicionada em migration separada (ex.: 36, 36b)** deve ter seu `down()` removendo apenas a FK, não a tabela.

## Regras específicas de performance de migration

1. **Criação de tabelas vazias:** index/constraint criados inline na mesma migration.
2. **Backfill em tabela grande** (quando aparecerem): sempre em migration separada, com `chunkById`, rodando via `php artisan db:run-script` ou job Horizon para não travar deploy. No MVP não há backfill crítico (tabelas começam vazias).
3. **Unique parcial em tabela povoada:** validar duplicidades prévias antes do `CREATE UNIQUE INDEX`. No MVP, criadas sempre em tabela vazia.

## Tabela resumida — CHECKs críticos

| Tabela              | Constraint                                                               | Semântica                                    |
| ------------------- | ------------------------------------------------------------------------ | -------------------------------------------- |
| `portal_users`      | `portal_users_tipo_valido`                                               | Lista fechada de papéis.                     |
| `eventos`           | `eventos_status_valido`                                                  | Máquina de estados do evento.                |
| `adesoes`           | `adesoes_status_valido` + unique parcial                                 | Estado + unicidade ativa.                    |
| `parcelas`          | `parcelas_status_valido`                                                 | Pendente/paga/vencida/cancelada.             |
| `pagamentos`        | `pagamentos_origem_exclusiva`                                            | `parcela_id XOR pedido_extra_id`.            |
| `convites`          | `convites_tipo_valido`, `convites_status_valido`                         | Enums duplicados no banco.                   |
| `reservas_assentos` | 3 constraints (status, hold_consistente, origem) + unique parcial        | **Sem isso, sistema falha em concorrência**. |
| `webhook_eventos`   | `webhook_eventos_status_valido` + unique `(provider, gateway_reference)` | Idempotência dura.                           |
| `votos`             | unique `(enquete_id, ator_tipo, ator_id)`                                | Impede voto duplicado sem edição.            |

## Tabela resumida — índices parciais

| Tabela              | Nome                                  | Condição                                         |
| ------------------- | ------------------------------------- | ------------------------------------------------ |
| `portal_users`      | `portal_users_cpf_unique`             | `WHERE cpf IS NOT NULL`                          |
| `adesoes`           | `adesoes_ativa_por_formando_evento`   | `WHERE status IN ('pendente_pagamento','ativa')` |
| `reservas_assentos` | `reservas_assentos_ativa_por_assento` | `WHERE status IN ('hold','confirmada')`          |
| `notificacoes`      | `notificacoes_dedup`                  | `WHERE chave_dedup IS NOT NULL`                  |

---

## Seeds recomendados

| Seeder                | Fase | Propósito                                                       |
| --------------------- | ---- | --------------------------------------------------------------- |
| `DatabaseSeeder`      | F1   | Orquestrador — chama demais.                                    |
| `PermissionSeeder`    | F1   | Roles/permissions Spatie em guards `admin` e `sanctum`.         |
| `AdminUserSeeder`     | F1   | 1 admin root para desenvolvimento.                              |
| `EventoDemoSeeder`    | F2   | 1 organização + 1 instituição + 2 cursos + 3 turmas + 1 evento. |
| `PacoteProdutoSeeder` | F2   | Catálogo mínimo.                                                |
| `MapaDemoSeeder`      | F5   | 10 mesas × 8 assentos.                                          |
| `EnqueteDemoSeeder`   | F6   | 2 enquetes (ex.: mesa de honra, tema).                          |

Seeder executável só em ambientes não-production (`when: !app()->isProduction()`).

---

## Critérios de pronto (por migration)

Antes de abrir PR que inclua migration:

1. `php artisan migrate` roda limpo em Postgres 16.
2. `php artisan migrate:rollback` volta sem lixo.
3. `php artisan migrate:fresh --seed` executa em < 30s.
4. `phpstan` sem erros no novo código.
5. Factory correspondente criada/atualizada.
6. Pest arch test confirma: modelo possui `use HasUlid`, tem `protected $casts` com enum, `timestamps` ativos.
7. Atualizar `data-model.md` se houve divergência. A **migration é a autoridade final**.

---

## Riscos conhecidos e mitigações

| Risco                                                                                 | Mitigação                                                                                              |
| ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Deadlock em F5 por ordem variável de `lockForUpdate`.                                 | `TrocarAssentoAction` sempre ordena por `assento_id ASC` antes do lock.                                |
| Índice parcial em tabela povoada falha por duplicados.                                | No MVP, tabelas começam vazias; em iterações futuras, validar via `SELECT ... HAVING COUNT > 1` antes. |
| `CHECK (status IN ...)` falha em rollback ao dropar constraint antes da coluna.       | Todos os rollbacks `DROP CONSTRAINT IF EXISTS` antes do `dropColumn`/`dropIfExists`.                   |
| Timestamps sem TZ causam bug em `HoldExpiradoException` em evento com fuso diferente. | Convenção geral: **sempre** `timestampTz()`, nunca `timestamp()`.                                      |
| Emissão em lote de 500 convites excede timeout de migration.                          | Emissão é operação, não migration. Usa `EmitirLoteConvitesJob`.                                        |
