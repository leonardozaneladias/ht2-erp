---
title: Software Requirements Specification (SRS) — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# SRS — Portal ArtFinal v2 (Backend API v1)

> Documento formal de requisitos seguindo a estrutura **IEEE 830-1998** adaptada ao contexto do Portal ArtFinal v2. Reúne requisitos funcionais (RF), requisitos de interface externa, requisitos não-funcionais (performance, segurança, disponibilidade, escalabilidade) e matriz de rastreabilidade entre RF, endpoint e action.
> Fontes: [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) (planejamento executável), [`PRD_v4.md`](../prd/PRD_v4.md), [`REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md), [`PRD_EXPANDED.md`](./PRD_EXPANDED.md).
> Documentos irmãos: [Brief](./PROJECT_BRIEF.md) · [User flows](./user-flows.md) · [Jornadas](./journeys-personas.md) · [Telas macro](./macro-screens.md).

---

## Sumário

- [1. Introdução](#1-introdução)
    - [1.1 Propósito](#11-propósito)
    - [1.2 Escopo](#12-escopo)
    - [1.3 Definições, acrônimos e abreviações](#13-definições-acrônimos-e-abreviações)
    - [1.4 Referências](#14-referências)
    - [1.5 Visão geral do documento](#15-visão-geral-do-documento)
- [2. Descrição Geral](#2-descrição-geral)
    - [2.1 Perspectiva do produto](#21-perspectiva-do-produto)
    - [2.2 Funções do produto](#22-funções-do-produto)
    - [2.3 Classes de usuário](#23-classes-de-usuário)
    - [2.4 Restrições](#24-restrições)
    - [2.5 Suposições e dependências](#25-suposições-e-dependências)
- [3. Requisitos Específicos](#3-requisitos-específicos)
    - [3.1 Requisitos Funcionais](#31-requisitos-funcionais)
    - [3.2 Interfaces externas](#32-interfaces-externas)
    - [3.3 Requisitos de Performance](#33-requisitos-de-performance)
    - [3.4 Requisitos de Segurança](#34-requisitos-de-segurança)
    - [3.5 Disponibilidade](#35-disponibilidade)
    - [3.6 Escalabilidade](#36-escalabilidade)
- [4. Rastreabilidade — Matriz RF ↔ Endpoint ↔ Action](#4-rastreabilidade--matriz-rf--endpoint--action)

---

## 1. Introdução

### 1.1 Propósito

Este SRS descreve, de forma verificável e rastreável, **os requisitos do Backend API v1 do Portal ArtFinal v2**. Sua função é servir como contrato entre produto, engenharia e QA para as fases F1 a F8 do roadmap (ver [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §14). Cada requisito é identificado, descrito, classificado quanto à prioridade (MUST/SHOULD/COULD conforme MoSCoW), referenciado à seção-fonte no planejamento e acompanhado por critério de verificação mensurável.

### 1.2 Escopo

**Dentro do escopo:**

- API REST `api/v1` (Laravel 13 + PHP 8.4) servindo Web SPA (React), Admin interno (Livewire/Blade/Inspinia) e Mobile (React Native, F8).
- Core de domínio modular (`Actions/`, `Data/`, `Enums/`, `Events/`, `Models/`, `Policies/`, `Services/`) independente do HTTP.
- Integrações externas: gateway de pagamento Itaú (com stub), S3/R2 para storage privado, SMTP para e-mail transacional, Expo Push (F8).
- Auditoria append-only via `spatie/laravel-activitylog`, ACL via `spatie/laravel-permission`.
- Concorrência transacional de reservas de assento; webhooks idempotentes com HMAC.

**Fora do escopo (pós-MVP):**

- Mobile Android/iOS nativo (apenas F8 mínima com RN).
- WhatsApp Business API, SMS.
- CRM robusto, BI externo.
- Multi-tenant com múltiplos salões por evento (Apêndice B, Q3).
- Pagamentos recorrentes via assinatura.

### 1.3 Definições, acrônimos e abreviações

| Termo               | Definição                                                                                                      |
| ------------------- | -------------------------------------------------------------------------------------------------------------- |
| Action              | Classe invocável com método `execute(DTO): DTO\|void` contendo uma operação de domínio (§1.1–§3 planejamento). |
| API-first           | Toda funcionalidade é exposta via `api/v1`; admin interno consome actions, não APIs alheias.                   |
| DTO                 | Data Transfer Object, aqui via `spatie/laravel-data`, readonly.                                                |
| Evento (de domínio) | `Illuminate\Foundation\Events\Dispatchable`; notifica outros bounded contexts.                                 |
| Evento (negócio)    | Agregado `eventos` do modelo (formatura, festa). Sempre qualificado pelo contexto.                             |
| Hold                | Estado transitório de reserva de assento (5 min), garantido por UNIQUE parcial.                                |
| HMAC                | Hash-based Message Authentication Code; validação de assinatura de webhook (SHA-256).                          |
| Idempotência        | Propriedade de que uma mesma requisição aplicada múltiplas vezes produz o mesmo efeito.                        |
| JSONB               | Binary JSON no PostgreSQL (usado para snapshots).                                                              |
| LGPD                | Lei Geral de Proteção de Dados (BR 13.709/2018).                                                               |
| MoSCoW              | Framework de priorização: MUST, SHOULD, COULD, WON'T.                                                          |
| RSVP                | "Répondez s'il vous plaît"; resposta de presença do convidado.                                                 |
| SPA                 | Single Page Application (React web).                                                                           |
| ULID                | Universally Unique Lexicographically Sortable Identifier (26 chars).                                           |

### 1.4 Referências

- [`../prd/PRD_v4.md`](../prd/PRD_v4.md) — PRD v4 (produto).
- [`../prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) — Planejamento técnico executável (fonte primária deste SRS).
- [`../prd/REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md) — Regras de negócio.
- [`PRD_EXPANDED.md`](./PRD_EXPANDED.md) — PRD por bounded context.
- [`user-flows.md`](./user-flows.md) — Fluxos com Mermaid.
- [`journeys-personas.md`](./journeys-personas.md) — Personas e jornadas.
- [`macro-screens.md`](./macro-screens.md) — Catálogo de telas.
- RFC 8594 — HTTP `Deprecation`, `Sunset`, `Link` headers.
- OWASP Top 10 (2021).
- IEEE 830-1998 — Recommended Practice for Software Requirements Specifications.

### 1.5 Visão geral do documento

- Seção **2** descreve o produto em alto nível (perspectiva, funções, usuários, restrições, suposições).
- Seção **3.1** lista os **Requisitos Funcionais** agrupados por bounded context (RF-AUTH, RF-CAD, RF-COM, RF-CNV, RF-RSVP, RF-SEA, RF-EXT, RF-PAG, RF-ENQ, RF-NOT, RF-AUD, RF-API, RF-PLT).
- Seções **3.2–3.6** tratam de interfaces externas e requisitos não-funcionais.
- Seção **4** apresenta a **matriz de rastreabilidade**.

---

## 2. Descrição Geral

### 2.1 Perspectiva do produto

O Portal ArtFinal v2 é um monólito modular em Laravel 13 que substitui planilhas e processos fragmentados por uma plataforma digital única. O backend expõe `api/v1` como interface oficial; o Admin em Blade/Livewire consome as mesmas actions (§0.2 planejamento). Infraestrutura: PostgreSQL 16, Redis (cache + filas), Horizon, S3 privado. Gateway de pagamento: Itaú (inicial) com arquitetura agnóstica via `PaymentGatewayContract`.

### 2.2 Funções do produto

1. **Identidade e Acesso.** Login (Admin/Portal/Comissão), token mágico de convidado, ACL granular.
2. **Cadastro Acadêmico e Evento.** Estrutura Organização → Instituição → Curso → Turma → Formando; evento como agregado central.
3. **Comercial e Adesão.** Pacotes, produtos, adesões, parcelas, pagamentos base.
4. **Convites e RSVP.** Emissão unitária e em lote, cota calculada, RSVP via token público.
5. **Seating.** Mapa de mesas, hold 5 min, confirmação transacional, troca, bloqueio.
6. **Extras.** Catálogo, pedidos com aprovação opcional, emissão derivada.
7. **Pagamentos.** Integração gateway, intents, webhooks idempotentes, reconciliação.
8. **Enquetes.** Votação por elegibilidade, cardinalidade configurável.
9. **Comunicação.** E-mail transacional, push (F8), reenvio auditado.
10. **Auditoria e Governança.** `activity_log`, snapshots, LGPD.

### 2.3 Classes de usuário

| Classe                 | Canal                    | Frequência      | Expertise   |
| ---------------------- | ------------------------ | --------------- | ----------- |
| Admin                  | `/admin` (desktop)       | Diária          | Alta        |
| Comissão               | `/admin` (subset)        | Semanal         | Média       |
| Formando               | SPA + Mobile             | Semanal         | Baixa-Média |
| Responsável financeiro | E-mail → link            | Mensal          | Baixa       |
| Convidado              | Link público             | Ocasional       | Baixa       |
| Operação               | Tablet `/admin/operacao` | Noite do evento | Baixa-Média |

Detalhes em [`journeys-personas.md`](./journeys-personas.md).

### 2.4 Restrições

1. **Tecnológicas.** PHP 8.4, Laravel 13, PostgreSQL 16, Redis, Horizon v5, Sanctum v4, Spatie Permission/Activitylog, `declare(strict_types=1)` obrigatório.
2. **Arquiteturais.** Core desacoplado de HTTP (§0.3 planejamento); Controllers finos; FormRequest obrigatória; DTOs readonly.
3. **Regulatórias.** LGPD (Lei 13.709/2018); retenção configurável; anonimização pós-evento.
4. **Operacionais.** Admin e Portal com guards e base de usuários **independentes** (CLAUDE.md §5.1).
5. **Financeiras.** Valores monetários em `INTEGER centavos`; nunca float.
6. **Idioma.** PT-BR para labels, mensagens e nomes de negócio; PHP/nomes de rotas/tabelas em inglês.

### 2.5 Suposições e dependências

1. Gateway Itaú fornecerá endpoint de webhook com HMAC-SHA256.
2. Infraestrutura rodará via Laradock (dev/CI) e Docker em produção.
3. Organizadoras terão acesso ao Admin por e-mail/senha + MFA opcional.
4. Clientes (SPA React) consumirão exclusivamente `api/v1`.
5. Resolução do token de convite: 64 chars hex = 32 bytes random, SHA-256 armazenado.

---

## 3. Requisitos Específicos

Cada RF segue o formato:

| Campo                       | Descrição                              |
| --------------------------- | -------------------------------------- |
| **ID**                      | Identificador único (RF-XXX-NN)        |
| **Título**                  | Nome curto                             |
| **Descrição**               | O que o sistema DEVE fazer             |
| **Prioridade**              | MUST / SHOULD / COULD                  |
| **Fonte**                   | Referência à seção do planejamento/PRD |
| **Critério de verificação** | Como se prova que está pronto          |
| **Dependências**            | IDs de outros RFs                      |

### 3.1 Requisitos Funcionais

#### 3.1.1 Identidade e Acesso (RF-AUTH)

##### RF-AUTH-01 — Autenticação SPA via Sanctum

- **Descrição.** O sistema DEVE autenticar clientes SPA via cookie de sessão emitido após `POST /api/v1/auth/login` com `mode=spa`, precedido por `GET /sanctum/csrf-cookie`.
- **Prioridade.** MUST.
- **Fonte.** §6.2 planejamento.
- **Critério.** Teste Pest Feature: sequência `csrf-cookie → login → me` retorna 200 com `user.id` (ULID) ao enviar credenciais válidas.
- **Dependências.** RF-PLT-01 (bootstrap `statefulApi`).

##### RF-AUTH-02 — Autenticação mobile via token

- **Descrição.** O sistema DEVE emitir `access_token` Bearer ao receber `POST /api/v1/auth/login` com `mode=token` + `device_name` obrigatório, retornando `{access_token, abilities, user}`.
- **Prioridade.** MUST.
- **Fonte.** §6.2 planejamento.
- **Critério.** Teste Feature: login com `mode=token` retorna token válido; `GET /me` com `Authorization: Bearer …` retorna 200.
- **Dependências.** RF-AUTH-01.

##### RF-AUTH-03 — Rate limit de login

- **Descrição.** O sistema DEVE limitar tentativas de login a 5 por minuto por combinação `email+IP`.
- **Prioridade.** MUST.
- **Fonte.** §2.10, §11.4 planejamento.
- **Critério.** 6ª tentativa no mesmo minuto retorna `429 RateLimitExceeded`.
- **Dependências.** RF-PLT-04.

##### RF-AUTH-04 — Token mágico de convite

- **Descrição.** O sistema DEVE gerar token bruto via `bin2hex(random_bytes(32))` (64 hex chars) e persistir apenas `sha256(token)` em `convites.token_hash`.
- **Prioridade.** MUST.
- **Fonte.** §6.3, §11.6 planejamento.
- **Critério.** Coluna `token_hash` UNIQUE; teste confirma que token bruto não aparece em DB após criação.
- **Dependências.** RF-CNV-03.

##### RF-AUTH-05 — Middleware de resolução de convite

- **Descrição.** O sistema DEVE resolver o token em rotas públicas `/convite/{token}` e retornar `404` para token inválido, revogado ou `status in ('cancelado','inutilizado')`.
- **Prioridade.** MUST.
- **Fonte.** §6.3 planejamento.
- **Critério.** Teste: token de 63 chars → 404; token revogado → 404; token válido → 200 com dados do convite.
- **Dependências.** RF-AUTH-04.

##### RF-AUTH-06 — ACL por role/permission

- **Descrição.** O sistema DEVE usar `spatie/laravel-permission` com roles (`admin`, `comissao`, `formando`, `operacao`, `responsavel`) e permissões explícitas, respeitando guards `admin` e `sanctum`.
- **Prioridade.** MUST.
- **Fonte.** §6.1, §6.5 planejamento.
- **Critério.** Modelos autenticáveis definem `$guard_name`; `config/permission.php` com `guard_names = ['web','admin','sanctum']`.
- **Dependências.** —.

##### RF-AUTH-07 — Sanctum abilities para mobile

- **Descrição.** O sistema DEVE atribuir `abilities` ao token criado em `mode=token`, refletindo `getAllPermissions()` do usuário.
- **Prioridade.** MUST.
- **Fonte.** §6.2 planejamento.
- **Critério.** Middleware `EnsureSanctumAbility` bloqueia endpoint quando token não tem ability requerida → 403.
- **Dependências.** RF-AUTH-02, RF-AUTH-06.

##### RF-AUTH-08 — Logout

- **Descrição.** O sistema DEVE invalidar a sessão (SPA) ou revogar o token atual (mobile) em `POST /api/v1/auth/logout`.
- **Prioridade.** MUST.
- **Fonte.** §6 planejamento.
- **Critério.** Após logout, requisições subsequentes retornam 401.
- **Dependências.** RF-AUTH-01, RF-AUTH-02.

##### RF-AUTH-09 — Endpoint `GET /me`

- **Descrição.** O sistema DEVE expor `GET /api/v1/me` que retorna perfil do usuário autenticado com `{id, email, roles, abilities}`.
- **Prioridade.** MUST.
- **Fonte.** §2.2 planejamento.
- **Critério.** 401 sem autenticação; 200 com payload correto autenticado.
- **Dependências.** RF-AUTH-01.

##### RF-AUTH-10 — Revogação admin de tokens

- **Descrição.** O sistema DEVE permitir ao admin revogar todos os tokens de um `PortalUser` via endpoint admin.
- **Prioridade.** SHOULD.
- **Fonte.** §6, RF-SEC OWASP A07.
- **Critério.** Após revogação, token bearer antigo retorna 401.
- **Dependências.** RF-AUTH-06.

---

#### 3.1.2 Cadastro Acadêmico e Evento (RF-CAD)

##### RF-CAD-01 — CRUD Organização

- **Descrição.** O sistema DEVE permitir criar, ler, atualizar e inativar Organização com campos `nome`, `cnpj` (validado), `dados_contato` (JSONB).
- **Prioridade.** MUST. **Fonte.** §2.1 PRD Expandido, §4.2 bloco B planejamento.
- **Critério.** Teste Feature: admin CRUD completo; CNPJ inválido → 422.
- **Dependências.** RF-AUTH-06.

##### RF-CAD-02 — CRUD Instituição

- **Descrição.** CRUD de Instituição vinculada à Organização.
- **Prioridade.** MUST. **Fonte.** §2.1 PRD Expandido.
- **Critério.** Instituição herda `organizacao_id`; FK RESTRICT. **Dep.** RF-CAD-01.

##### RF-CAD-03 — CRUD Curso

- **Descrição.** CRUD de Curso vinculado à Instituição.
- **Prioridade.** MUST. **Fonte.** §2 PRD Expandido.
- **Critério.** Curso com `nome`, `nivel` (Enum: graduacao, posgrad, tecnico). **Dep.** RF-CAD-02.

##### RF-CAD-04 — CRUD Turma

- **Descrição.** CRUD de Turma vinculada a Instituição e Curso.
- **Prioridade.** MUST. **Fonte.** §2 PRD Expandido.
- **Critério.** Turma com `codigo` UNIQUE dentro da instituição, `ano_conclusao`. **Dep.** RF-CAD-02, RF-CAD-03.

##### RF-CAD-05 — CRUD Evento

- **Descrição.** O sistema DEVE permitir criar evento com `nome`, `slug` UNIQUE, `data_evento`, `timezone`, `abre_rsvp_at`, `abre_mesas_at`, `fecha_mesas_at`, `config_json`.
- **Prioridade.** MUST. **Fonte.** §2.1 PRD Expandido, §Apêndice C planejamento.
- **Critério.** Invariante: `abre_rsvp_at ≤ abre_mesas_at ≤ fecha_mesas_at ≤ data_evento`.
- **Dep.** RF-CAD-04.

##### RF-CAD-06 — Vínculo Turma-Evento (pivô)

- **Descrição.** Evento reúne 1..N Turmas via pivô `turma_evento`.
- **Prioridade.** MUST. **Fonte.** §2.3 PRD Expandido.
- **Critério.** UNIQUE(`turma_id`, `evento_id`). **Dep.** RF-CAD-04, RF-CAD-05.

##### RF-CAD-07 — Publicar evento

- **Descrição.** `PublicarEventoAction` transiciona evento de `rascunho` para `publicado` e dispara `EventoPublicado`.
- **Prioridade.** MUST. **Fonte.** §2.4 PRD Expandido.
- **Critério.** `POST /admin/eventos/{id}/publicar` retorna 200; segunda chamada é idempotente. **Dep.** RF-CAD-05.

##### RF-CAD-08 — Atualização de janelas auditada

- **Descrição.** `AtualizarJanelasEventoAction` grava alteração em `activity_log` e dispara `EventoAtualizado`.
- **Prioridade.** MUST. **Fonte.** §2.3 PRD Expandido.
- **Critério.** Após mudança, `activity_log` tem entrada com `attribute_changes`. **Dep.** RF-CAD-05, RF-AUD-01.

##### RF-CAD-09 — Bloqueio de exclusão com dependências

- **Descrição.** O sistema DEVE impedir exclusão de evento com convites, reservas ou pagamentos vinculados.
- **Prioridade.** MUST. **Fonte.** §2.3 PRD Expandido.
- **Critério.** FK `ON DELETE RESTRICT`; tentativa retorna 409. **Dep.** RF-CAD-05.

##### RF-CAD-10 — Cadastro de Formando

- **Descrição.** CRUD de Formando vinculado a Turma e a `PortalUser`.
- **Prioridade.** MUST. **Fonte.** §4.2 bloco B planejamento.
- **Critério.** UNIQUE(`cpf`) quando presente; import em lote via CSV admin. **Dep.** RF-CAD-04, RF-AUTH-06.

##### RF-CAD-11 — Import em lote de formandos

- **Descrição.** Admin DEVE poder importar formandos via CSV com validação linha a linha.
- **Prioridade.** SHOULD. **Fonte.** Jornada Admin §1.1 journeys.
- **Critério.** CSV de 1000 linhas processado em fila `exports` em < 5 min. **Dep.** RF-CAD-10.

---

#### 3.1.3 Comercial e Adesão (RF-COM)

##### RF-COM-01 — CRUD Pacote

- **Descrição.** Admin DEVE criar pacotes com `nome`, `preco_centavos`, `qtd_parcelas_padrao`, `produtos_inclusos[]`, `ativo`.
- **Prioridade.** MUST. **Fonte.** §3 PRD Expandido.
- **Critério.** Pacote inativo não aparece em catálogo público. **Dep.** RF-CAD-05.

##### RF-COM-02 — CRUD Produto

- **Descrição.** CRUD de Produto com `nome`, `preco_centavos`, `tipo`, `ativo`.
- **Prioridade.** MUST. **Fonte.** §3 PRD Expandido.
- **Critério.** Soft-delete permitido em produtos (§4.8 planejamento). **Dep.** —.

##### RF-COM-03 — Criar Adesão

- **Descrição.** `CriarAdesaoAction` cria adesão em `rascunho` e transiciona para `pendente_pagamento` ao gerar parcelas.
- **Prioridade.** MUST. **Fonte.** §3.7 PRD Expandido.
- **Critério.** `POST /api/v1/eventos/{ulid}/adesoes` retorna 201 com `status=pendente_pagamento`. **Dep.** RF-COM-01, RF-CAD-10.

##### RF-COM-04 — Snapshot comercial imutável

- **Descrição.** Adesão DEVE gravar `snapshot_comercial` JSONB (preço, desconto, termo) no momento da transição `ativa`.
- **Prioridade.** MUST. **Fonte.** §3.6 PRD Expandido, §13 planejamento.
- **Critério.** Alterar pacote após adesão ativa não muda `snapshot_comercial`. **Dep.** RF-COM-03.

##### RF-COM-05 — Adesão única ativa por evento

- **Descrição.** UNIQUE parcial garante apenas **uma** adesão em `pendente_pagamento` ou `ativa` por formando+evento.
- **Prioridade.** MUST. **Fonte.** §4.6 planejamento.
- **Critério.** Tentativa de 2ª adesão ativa retorna 409. **Dep.** RF-COM-03.

##### RF-COM-06 — Gerar Parcelas

- **Descrição.** `GerarParcelasAction` calcula parcelas com `sum(parcelas) = valor_total - valor_entrada`.
- **Prioridade.** MUST. **Fonte.** §3.6 PRD Expandido.
- **Critério.** Invariante de soma validado em teste. **Dep.** RF-COM-03.

##### RF-COM-07 — Confirmar Adesão via pagamento

- **Descrição.** `ConfirmarAdesaoAction` transiciona `pendente_pagamento → ativa` após webhook de pagamento confirmar entrada.
- **Prioridade.** MUST. **Fonte.** §3.7 PRD Expandido.
- **Critério.** Ao receber webhook, adesão fica `ativa` e `confirmada_at` preenchido. **Dep.** RF-COM-03, RF-PAG-02.

##### RF-COM-08 — Cancelar Adesão

- **Descrição.** Admin DEVE poder cancelar adesão com justificativa; transiciona para `cancelada`.
- **Prioridade.** MUST. **Fonte.** §3.7 PRD Expandido.
- **Critério.** Cancelamento registrado em `activity_log` com `causer=Admin` e `motivo`. **Dep.** RF-COM-03, RF-AUD-01.

##### RF-COM-09 — Transição `ativa → concluida` automática

- **Descrição.** Quando todas parcelas forem pagas, sistema DEVE transicionar adesão para `concluida`.
- **Prioridade.** SHOULD. **Fonte.** §3.7 PRD Expandido.
- **Critério.** Listener sobre `PagamentoConfirmado` verifica soma. **Dep.** RF-COM-07.

##### RF-COM-10 — Transição `ativa → inadimplente`

- **Descrição.** Job diário marca adesão como `inadimplente` se qualquer parcela vencida há > X dias (config).
- **Prioridade.** SHOULD. **Fonte.** §3.7 PRD Expandido.
- **Critério.** Parcela com `vencimento < now - X` e `status=pendente` → adesão vira `inadimplente`. **Dep.** RF-COM-06.

---

#### 3.1.4 Convites e RSVP (RF-CNV)

##### RF-CNV-01 — Calcular cota

- **Descrição.** `CotaCalculator` retorna `cota_base + cota_bonus + cota_extra - cota_reservada - convites_emitidos_ativos`.
- **Prioridade.** MUST. **Fonte.** §4.3 PRD Expandido.
- **Critério.** Teste unitário com cenários positivos e de borda; invariante `cota >= 0`. **Dep.** RF-COM-07.

##### RF-CNV-02 — Emitir convite unitário

- **Descrição.** `EmitirConviteAction` gera convite com `codigo` UNIQUE + `token_hash`, status `emitido`, snapshot da regra.
- **Prioridade.** MUST. **Fonte.** §4 PRD Expandido, §2 flows.
- **Critério.** `POST /convites` retorna 201; quando cota esgotada → 409 `CotaEsgotada`. **Dep.** RF-CNV-01, RF-AUTH-04.

##### RF-CNV-03 — Código legível

- **Descrição.** Cada convite tem `codigo` humano legível de 24 chars (UPPER alfa-numérico).
- **Prioridade.** MUST. **Fonte.** §4.4 PRD Expandido.
- **Critério.** UNIQUE(`codigo`); teste de colisão em 100k gerações. **Dep.** RF-CNV-02.

##### RF-CNV-04 — Emissão em lote (assíncrona)

- **Descrição.** `POST /convites/lotes` aceita até 500 convites por chamada, retorna `202 Accepted` + `status_url`; job `EmitirLoteConvitesJob` processa em fila `default`.
- **Prioridade.** MUST. **Fonte.** §2.2, §7.3 planejamento.
- **Critério.** 500 convites processados em ≤ 60s em ambiente padrão. **Dep.** RF-CNV-02.

##### RF-CNV-05 — Reemitir convite (novo token)

- **Descrição.** `ReemitirConviteAction` gera novo `token_hash`, invalida anterior, preserva histórico.
- **Prioridade.** MUST. **Fonte.** §4 PRD Expandido.
- **Critério.** Token antigo → 404; novo token → 200. **Dep.** RF-CNV-02.

##### RF-CNV-06 — Transferir convite

- **Descrição.** `TransferirConviteAction` atualiza `convidado_*` sem duplicar convite; **não altera cota**.
- **Prioridade.** MUST. **Fonte.** §4.3 PRD Expandido.
- **Critério.** Cota disponível permanece constante; `activity_log` registra transferência. **Dep.** RF-CNV-02.

##### RF-CNV-07 — Cancelar convite

- **Descrição.** `CancelarConviteAction` transiciona para `cancelado`; se houver reserva ativa, chama `LiberarAssentoAction`.
- **Prioridade.** MUST. **Fonte.** §3.7 planejamento, §4 PRD Expandido.
- **Critério.** Reserva vinculada fica `cancelada`; cota devolvida conforme política. **Dep.** RF-CNV-02, RF-SEA-05.

##### RF-CNV-08 — Rota pública por token

- **Descrição.** `GET /api/v1/convite/{token}` retorna dados do convite (evento, convidado, janela RSVP).
- **Prioridade.** MUST. **Fonte.** §2.2 planejamento.
- **Critério.** Token válido → 200; inválido/revogado → 404 genérico. **Dep.** RF-AUTH-05.

##### RF-CNV-09 — Transição `enviado → visualizado`

- **Descrição.** No primeiro acesso ao token, `visualizado_at = now()`, status vira `visualizado` (se estava `enviado`).
- **Prioridade.** SHOULD. **Fonte.** §4.9 PRD Expandido.
- **Critério.** 2º GET não atualiza `visualizado_at`. **Dep.** RF-CNV-08.

##### RF-CNV-10 — Registrar RSVP

- **Descrição.** `POST /api/v1/convite/{token}/rsvp` aceita `resposta in [confirmado, recusado]`; `RegistrarRsvpAction` grava histórico e atualiza convite.
- **Prioridade.** MUST. **Fonte.** §4.10 PRD Expandido.
- **Critério.** Fora da janela → 409; status transiciona corretamente. **Dep.** RF-CNV-08.

##### RF-CNV-11 — Histórico de RSVP append-only

- **Descrição.** Cada alteração grava linha em `rsvp_historico` com `ator`, `ip`, `user_agent`, `timestamp`.
- **Prioridade.** MUST. **Fonte.** §4.5 PRD Expandido.
- **Critério.** Nenhum UPDATE na tabela; sempre INSERT. **Dep.** RF-CNV-10.

##### RF-CNV-12 — Rate limit RSVP por IP

- **Descrição.** Rota pública limitada a 10 req/min por IP.
- **Prioridade.** MUST. **Fonte.** §2.10 planejamento.
- **Critério.** 11ª req → 429. **Dep.** RF-PLT-04.

##### RF-CNV-13 — Reminder de RSVP

- **Descrição.** Job scheduled `EnviarReminderRsvpJob` envia e-mail a convites `enviado` há > 3 dias sem RSVP.
- **Prioridade.** SHOULD. **Fonte.** §7.3 planejamento.
- **Critério.** Job `notifications` respeita idempotência por convite+data. **Dep.** RF-NOT-02.

##### RF-CNV-14 — Marcar como `inutilizado`

- **Descrição.** No check-in do dia, operação marca convite como `inutilizado` (one-way).
- **Prioridade.** MUST. **Fonte.** §4.9 PRD Expandido.
- **Critério.** Tentativa de voltar a estado anterior → 409. **Dep.** RF-CNV-02.

---

#### 3.1.5 Seating (RF-SEA)

##### RF-SEA-01 — CRUD Mapa/Setor/Mesa/Assento

- **Descrição.** Admin modela mapa hierarquicamente (Mapa → Setor → Mesa → Assento).
- **Prioridade.** MUST. **Fonte.** §5 PRD Expandido.
- **Critério.** Cada nível tem `ulid`, FK RESTRICT para pai. **Dep.** RF-CAD-05.

##### RF-SEA-02 — Reservar assento (hold)

- **Descrição.** `ReservarAssentoAction` cria reserva `hold` com `hold_expires_at = now + 5min`, protegida por Redis lock + unique parcial.
- **Prioridade.** MUST. **Fonte.** §3.5, §5.1 planejamento.
- **Critério.** Em 100 requisições simultâneas, exatamente 1 tem sucesso; 99 retornam 409. **Dep.** RF-SEA-01, RF-PLT-03.

##### RF-SEA-03 — Idempotência X-Idempotency-Key

- **Descrição.** `POST /reservas` exige `X-Idempotency-Key`; key reutilizada com mesmo payload → 201 com reserva anterior; payload diferente → 409.
- **Prioridade.** MUST. **Fonte.** §2.9, §5.6 planejamento.
- **Critério.** Middleware + firstOrCreate por `idempotency_key` no banco. **Dep.** RF-SEA-02, RF-PLT-05.

##### RF-SEA-04 — Confirmar assento

- **Descrição.** `ConfirmarAssentoAction` transiciona `hold → confirmada` se `hold_expires_at > now()`.
- **Prioridade.** MUST. **Fonte.** §5.2 planejamento, §5.8 PRD Expandido.
- **Critério.** Após expirar → 410 `HoldExpirado`. **Dep.** RF-SEA-02.

##### RF-SEA-05 — Liberar assento

- **Descrição.** `LiberarAssentoAction` transiciona reserva ativa para `cancelada` com motivo.
- **Prioridade.** MUST. **Fonte.** §3.7 planejamento.
- **Critério.** UNIQUE parcial é liberado após cancelamento. **Dep.** RF-SEA-02.

##### RF-SEA-06 — Expirar holds automaticamente

- **Descrição.** `ExpirarHoldsJob` (schedule `everyMinute`, fila `critical-seating`) transiciona holds vencidos para `expirada`.
- **Prioridade.** MUST. **Fonte.** §5.4 planejamento.
- **Critério.** Hold com `hold_expires_at < now - 60s` → `expirada` em ≤ 2 min. **Dep.** RF-SEA-02.

##### RF-SEA-07 — Trocar assento sem deadlock

- **Descrição.** `TrocarAssentoAction` libera antigo antes de reservar destino; em caso de falha, rollback completo.
- **Prioridade.** MUST. **Fonte.** §5.3 planejamento.
- **Critério.** Teste: destino ocupado → antiga reserva preservada. **Dep.** RF-SEA-04, RF-SEA-05.

##### RF-SEA-08 — Bloqueio administrativo

- **Descrição.** Admin DEVE bloquear mesa/assento (`status = bloqueada`) via action específica.
- **Prioridade.** MUST. **Fonte.** §5.5 regras de negócio, §5.4 PRD Expandido.
- **Critério.** Assento bloqueado não aparece disponível em `/mapa`. **Dep.** RF-SEA-01.

##### RF-SEA-09 — Rate limit por ator

- **Descrição.** `seating` limiter → 5 reservas/min por usuário.
- **Prioridade.** MUST. **Fonte.** §2.10 planejamento.
- **Critério.** 6ª tentativa → 429. **Dep.** RF-PLT-04.

##### RF-SEA-10 — Mapa — snapshot completo e delta

- **Descrição.** `GET /mesas/mapa` retorna snapshot; `?since=<iso8601>` retorna apenas reservas com `updated_at > since`.
- **Prioridade.** MUST. **Fonte.** §2.2 planejamento.
- **Critério.** Resposta cacheada por 5 min com tag `evento:{id}:mapa`. **Dep.** RF-SEA-01.

##### RF-SEA-11 — Invalidação de cache do mapa

- **Descrição.** Listener `InvalidarCacheMapaAoReservar` invalida tag `evento:{id}:mapa` em `AssentoReservado`, `AssentoConfirmado`, `HoldExpirado`, `AssentoLiberado`.
- **Prioridade.** MUST. **Fonte.** §9.4 planejamento.
- **Critério.** Após reserva, próximo GET retorna dado atualizado. **Dep.** RF-SEA-10.

##### RF-SEA-12 — Unique parcial por assento

- **Descrição.** Schema tem `CREATE UNIQUE INDEX reservas_assentos_ativa_por_assento ON reservas_assentos (assento_id) WHERE status IN ('hold','confirmada')`.
- **Prioridade.** MUST. **Fonte.** §4.3 planejamento.
- **Critério.** Migration publica o índice; teste verifica integridade sob concorrência. **Dep.** RF-SEA-01.

##### RF-SEA-13 — CHECK constraint de consistência

- **Descrição.** CHECK `(status='hold' AND hold_expires_at IS NOT NULL) OR (status='confirmada' AND confirmado_at IS NOT NULL) OR (status IN ('cancelada','expirada','bloqueada'))`.
- **Prioridade.** MUST. **Fonte.** §4.3 planejamento.
- **Critério.** INSERT inconsistente → erro 23514. **Dep.** RF-SEA-12.

##### RF-SEA-14 — Publicação de delta WS/Reverb

- **Descrição.** Após reserva/confirmação/liberação, job `PublicarAtualizacaoMapaJob` publica delta em canal WebSocket do evento.
- **Prioridade.** COULD. **Fonte.** §7.3 planejamento.
- **Critério.** Cliente SPA recebe update em ≤ 1s de p95. **Dep.** RF-SEA-02.

---

#### 3.1.6 Extras (RF-EXT)

##### RF-EXT-01 — CRUD ProdutoExtra

- **Descrição.** Admin cria produto extra com `preco_centavos`, janela, `requer_aprovacao`, `estoque_tipo`, `estoque_qtd`, `elegibilidade_json`.
- **Prioridade.** MUST. **Fonte.** §6 PRD Expandido.
- **Critério.** CRUD completo + snapshot ao vincular a pedido. **Dep.** RF-CAD-05.

##### RF-EXT-02 — Criar Pedido Extra

- **Descrição.** `CriarPedidoExtraAction` valida elegibilidade, estoque, janela e cria pedido idempotente.
- **Prioridade.** MUST. **Fonte.** §6.7 PRD Expandido.
- **Critério.** Estoque esgotado → 409. **Dep.** RF-EXT-01.

##### RF-EXT-03 — Fluxo com aprovação

- **Descrição.** Se `requer_aprovacao=true`, pedido nasce em `pendente_aprovacao`; admin aprova/rejeita.
- **Prioridade.** MUST. **Fonte.** §6.7 PRD Expandido.
- **Critério.** Pedido pendente não abre intent de pagamento. **Dep.** RF-EXT-02.

##### RF-EXT-04 — Confirmar pagamento do extra

- **Descrição.** `ConfirmarPagamentoExtraAction` transiciona para `pago` e chama `EmitirLoteConvitesAction` para convites derivados.
- **Prioridade.** MUST. **Fonte.** §3.7 planejamento.
- **Critério.** N convites `tipo=extra` emitidos com `pedido_extra_id` preenchido. **Dep.** RF-EXT-02, RF-CNV-04, RF-PAG-02.

##### RF-EXT-05 — Estornar pedido

- **Descrição.** `EstornarPedidoExtraAction` marca pedido `estornado` e convites não utilizados → `inutilizado`.
- **Prioridade.** MUST. **Fonte.** §6.7 PRD Expandido.
- **Critério.** Convite já inutilizado (check-in) não pode ser revertido. **Dep.** RF-EXT-04.

##### RF-EXT-06 — Estoque — modalidades

- **Descrição.** Sistema DEVE suportar `ilimitado`, `por_evento`, `por_lote`, `por_formando`.
- **Prioridade.** MUST. **Fonte.** §6.3 PRD Expandido.
- **Critério.** Teste para cada modalidade com decremento correto. **Dep.** RF-EXT-01.

##### RF-EXT-07 — Expiração de pedido não pago

- **Descrição.** Pedido em `aguardando_pagamento` expira em janela configurada (`config.pedido_expira_em_horas`).
- **Prioridade.** SHOULD. **Fonte.** §6.7 PRD Expandido.
- **Critério.** Job diário/hour job transiciona. **Dep.** RF-EXT-02.

---

#### 3.1.7 Pagamentos (RF-PAG)

##### RF-PAG-01 — Iniciar intent

- **Descrição.** `IniciarPagamentoAction` cria cobrança no gateway e retorna `gateway_reference` + dados do método.
- **Prioridade.** MUST. **Fonte.** §8 planejamento.
- **Critério.** `POST /pagamentos/intents` retorna 201 + QR PIX / boleto / link cartão. **Dep.** RF-PLT-06.

##### RF-PAG-02 — Processar webhook idempotente

- **Descrição.** Webhook `POST /webhooks/pagamentos/{provider}` valida HMAC, grava em `webhook_eventos` via `firstOrCreate` por `(provider, gateway_reference)`, dispara job.
- **Prioridade.** MUST. **Fonte.** §5.5, §7 flows.
- **Critério.** Reenvio com mesmo reference retorna `200 already_processed`. **Dep.** RF-PAG-01.

##### RF-PAG-03 — Validação HMAC

- **Descrição.** Assinatura `X-Signature` validada via `hash_hmac('sha256', raw_body, secret)` + `hash_equals`.
- **Prioridade.** MUST. **Fonte.** §8.2 planejamento.
- **Critério.** Payload adulterado → 401. **Dep.** RF-PAG-02.

##### RF-PAG-04 — Retry com backoff

- **Descrição.** `ProcessarWebhookPagamentoJob` tem `tries=5` e backoff `[10,30,90,300,600]`.
- **Prioridade.** MUST. **Fonte.** §7.4 planejamento.
- **Critério.** Falha transitória reprocessa; após 5 falhas → `failed_jobs`. **Dep.** RF-PAG-02.

##### RF-PAG-05 — Reconciliação periódica

- **Descrição.** `ReconciliarPagamentosJob` (a cada 15 min) consulta gateway para pagamentos `pendente/autorizado` há > 60 min e dispara evento interno equivalente a webhook.
- **Prioridade.** SHOULD. **Fonte.** §5.7 planejamento.
- **Critério.** Divergência entre DB e gateway detectada e convergida. **Dep.** RF-PAG-02.

##### RF-PAG-06 — Estado de pagamento

- **Descrição.** Enum `StatusPagamento` com estados `{criado, pendente, autorizado, pago, falhou, cancelado, estornado}`; transições válidas documentadas.
- **Prioridade.** MUST. **Fonte.** §7.7 PRD Expandido.
- **Critério.** Transição ilegal → `InvariantViolationException`. **Dep.** RF-PAG-01.

##### RF-PAG-07 — Nenhum dado de cartão armazenado

- **Descrição.** Apenas `gateway_reference` e tokens do provedor em DB; PAN nunca persiste.
- **Prioridade.** MUST. **Fonte.** §0.10, §11.7 planejamento.
- **Critério.** Schema não tem coluna para número de cartão; teste estático. **Dep.** —.

##### RF-PAG-08 — Rate limit webhook

- **Descrição.** Rota `/webhooks/*` limitada a 600 req/min por IP.
- **Prioridade.** MUST. **Fonte.** §2.10 planejamento.
- **Critério.** 601ª no minuto → 429. **Dep.** RF-PLT-04.

##### RF-PAG-09 — Reprocessamento manual

- **Descrição.** Admin DEVE poder reprocessar webhook a partir de `/admin/pagamentos/webhooks`.
- **Prioridade.** SHOULD. **Fonte.** §5.13 macro-screens.
- **Critério.** Reprocessamento dispara novamente o job idempotentemente. **Dep.** RF-PAG-02.

---

#### 3.1.8 Enquetes (RF-ENQ)

##### RF-ENQ-01 — CRUD Enquete

- **Descrição.** Admin cria enquete com `tipo`, `opcoes[]`, `janela`, `regra_elegibilidade` (JSONB), `permite_edicao`, `resultado_visibilidade`.
- **Prioridade.** MUST. **Fonte.** §8 PRD Expandido.
- **Critério.** Enquete em `rascunho` até `PublicarEnqueteAction`. **Dep.** RF-CAD-05.

##### RF-ENQ-02 — Publicar enquete

- **Descrição.** `PublicarEnqueteAction` transiciona para `publicada` e dispara `EnqueteAberta`.
- **Prioridade.** MUST. **Fonte.** §8.7 PRD Expandido.
- **Critério.** Após publicação, votos passam a ser aceitos. **Dep.** RF-ENQ-01.

##### RF-ENQ-03 — Registrar voto

- **Descrição.** `RegistrarVotoAction` valida janela, elegibilidade, unicidade (quando `permite_edicao=false`).
- **Prioridade.** MUST. **Fonte.** §8.5 PRD Expandido.
- **Critério.** Duplo voto com `permite_edicao=false` → 409. **Dep.** RF-ENQ-02.

##### RF-ENQ-04 — Unicidade por ator

- **Descrição.** UNIQUE(`enquete_id, ator_tipo, ator_id`) quando `permite_edicao=false`.
- **Prioridade.** MUST. **Fonte.** §5.6 planejamento.
- **Critério.** Migration define UNIQUE parcial. **Dep.** RF-ENQ-03.

##### RF-ENQ-05 — Encerrar enquete

- **Descrição.** `EncerrarEnqueteAction` transiciona para `encerrada` manual ou automaticamente no `fecha_at`.
- **Prioridade.** MUST. **Fonte.** §8.7 PRD Expandido.
- **Critério.** Após encerrada, voto → 409. **Dep.** RF-ENQ-02.

##### RF-ENQ-06 — Visibilidade do resultado

- **Descrição.** Configurável: `publico`, `parcial`, `admin_only`.
- **Prioridade.** SHOULD. **Fonte.** §8.3 PRD Expandido.
- **Critério.** Formando em enquete `admin_only` → 403 no campo `resultados`. **Dep.** RF-ENQ-01.

##### RF-ENQ-07 — Rate limit voto

- **Descrição.** 3 req/min por ator.
- **Prioridade.** MUST. **Fonte.** §2.10 planejamento.
- **Critério.** 4ª → 429. **Dep.** RF-PLT-04.

---

#### 3.1.9 Comunicação (RF-NOT)

##### RF-NOT-01 — Template versionado por canal

- **Descrição.** `TemplateNotificacao` com `chave`, `canal`, `versao`, `conteudo`.
- **Prioridade.** MUST. **Fonte.** §9 PRD Expandido.
- **Critério.** Nova versão preserva anterior; render usa versão atual. **Dep.** —.

##### RF-NOT-02 — Envio de convite por e-mail

- **Descrição.** Listener `EnviarEmailConviteAoEmitir` dispara `EnviarConviteEmailJob` (fila `notifications`) após `ConviteEmitido`.
- **Prioridade.** MUST. **Fonte.** §7.3 planejamento.
- **Critério.** `Notificacao` criada e `NotificacaoEntrega` atualizada com status do provedor. **Dep.** RF-CNV-02.

##### RF-NOT-03 — Reenvio manual auditado

- **Descrição.** Admin pode reenviar notificação; cada reenvio gera nova `NotificacaoEntrega`.
- **Prioridade.** SHOULD. **Fonte.** §9.5 PRD Expandido.
- **Critério.** Histórico preserva tentativas anteriores. **Dep.** RF-NOT-02.

##### RF-NOT-04 — Push mobile (F8)

- **Descrição.** `NotificarPushJob` envia push via Expo.
- **Prioridade.** COULD. **Fonte.** §7.3 planejamento.
- **Critério.** Device token registrado; push recebido em < 5s. **Dep.** RF-AUTH-02.

##### RF-NOT-05 — Mascaramento LGPD em notificações

- **Descrição.** Logs de notificação mascaram CPF (`123.***.*89-00`) e nunca registram token bruto.
- **Prioridade.** MUST. **Fonte.** §11.8 planejamento.
- **Critério.** Inspecão de log em teste não encontra padrões sensíveis. **Dep.** —.

---

#### 3.1.10 Auditoria (RF-AUD)

##### RF-AUD-01 — `activity_log` append-only

- **Descrição.** Toda mudança crítica grava em `activity_log` via `spatie/laravel-activitylog`.
- **Prioridade.** MUST. **Fonte.** §0.9, §13 planejamento.
- **Critério.** Nenhum DELETE permitido; trigger ou policy bloqueia. **Dep.** —.

##### RF-AUD-02 — Correlation/Request ID

- **Descrição.** `X-Request-Id` e `X-Correlation-Id` propagados entre request → webhook → job → log.
- **Prioridade.** MUST. **Fonte.** §2.8, §12.4 planejamento.
- **Critério.** Log JSON contém `request_id`; tabelas transacionais têm `correlation_id`. **Dep.** RF-PLT-02.

##### RF-AUD-03 — Snapshots obrigatórios

- **Descrição.** Ao transicionar para estado imutável (`Adesao::ativa`, `Convite::emitido`, `Reserva::confirmada`, `PedidoExtra::pago`), grava snapshot JSONB.
- **Prioridade.** MUST. **Fonte.** §13.2 planejamento.
- **Critério.** Alteração posterior dos dados mestres não muda snapshot. **Dep.** RF-COM-04, RF-CNV-02, RF-SEA-04, RF-EXT-04.

##### RF-AUD-04 — Hash do termo

- **Descrição.** `termo_hash = sha256(termo_html)` armazenado em adesão para comprovação.
- **Prioridade.** MUST. **Fonte.** §13.2 planejamento.
- **Critério.** Hash bate com re-cálculo sobre conteúdo arquivado. **Dep.** RF-COM-04.

##### RF-AUD-05 — Retenção e anonimização

- **Descrição.** Job `AnonimizarDadosPosEventoJob` anonimiza dados pessoais 90 dias após `data_evento`.
- **Prioridade.** MUST. **Fonte.** §13.3, §11.10 planejamento.
- **Critério.** Convidado com `anonimizado_at` tem nome `Convidado Anonimizado #N`, email/telefone NULL. **Dep.** RF-CNV-02.

---

#### 3.1.11 Plataforma (RF-PLT)

##### RF-PLT-01 — Registro de rotas

- **Descrição.** `bootstrap/app.php` registra `routes/api/v1.php` com prefixo `api/v1` e grupo `webhook` separado.
- **Prioridade.** MUST. **Fonte.** §2.1 planejamento.
- **Critério.** Teste verifica route:list contém `api.v1.*` e `webhook.*`. **Dep.** —.

##### RF-PLT-02 — Middleware `AttachRequestId`

- **Descrição.** Injeta `X-Request-Id` (ULID) se ausente; propaga nos logs.
- **Prioridade.** MUST. **Fonte.** §2.8 planejamento.
- **Critério.** Header retornado em toda resposta API/webhook. **Dep.** RF-PLT-01.

##### RF-PLT-03 — Redis lock para seating

- **Descrição.** `Cache::lock("seating:assento:{ulid}", 10)` com `block(3)` antes de transação.
- **Prioridade.** MUST. **Fonte.** §3.5 planejamento.
- **Critério.** Dois processos concorrentes no mesmo assento; apenas um adquire. **Dep.** —.

##### RF-PLT-04 — Rate limiters nomeados

- **Descrição.** `RateLimiterServiceProvider` define `api`, `login`, `convite`, `seating`, `voto`, `webhook`.
- **Prioridade.** MUST. **Fonte.** §2.10 planejamento.
- **Critério.** Cada limiter usa chave correta (email+IP, user, IP). **Dep.** RF-PLT-01.

##### RF-PLT-05 — Middleware `IdempotencyKeyGuard`

- **Descrição.** Exige `X-Idempotency-Key` (≤ 80 chars) em POST de reservas/pedidos/pagamentos/lotes; rejeita colisão com payload diferente em 24h.
- **Prioridade.** MUST. **Fonte.** §2.9 planejamento.
- **Critério.** Cache key inclui `route()->getName()` para evitar colisão cross-rota. **Dep.** RF-PLT-01.

##### RF-PLT-06 — `PaymentGatewayContract` abstrato

- **Descrição.** Interface com `criarCobranca`, `consultar`, `assinaturaValida`; drivers `ItauGateway`, `StubGateway`.
- **Prioridade.** MUST. **Fonte.** §8.1 planejamento.
- **Critério.** Binding em `GatewayServiceProvider` baseado em `config('gateway.driver')`. **Dep.** —.

##### RF-PLT-07 — Filas Horizon

- **Descrição.** Cinco filas (`default`, `notifications`, `webhooks`, `exports`, `critical-seating`) com concurrency e retry documentados em §7 planejamento.
- **Prioridade.** MUST. **Fonte.** §7.1, §7.2 planejamento.
- **Critério.** `config/horizon.php` reproduz supervisores listados. **Dep.** RF-PLT-01.

##### RF-PLT-08 — Prevenção de N+1

- **Descrição.** `Model::preventLazyLoading()` em dev/staging; eager explicit em controllers.
- **Prioridade.** MUST. **Fonte.** §9.5 planejamento.
- **Critério.** Teste Pest levanta exceção em lazy load não intencional. **Dep.** —.

##### RF-PLT-09 — Cache tagueado por evento

- **Descrição.** Usar Redis tags (`evento:{id}`, `mapa`, `rsvp`) para invalidação precisa.
- **Prioridade.** MUST. **Fonte.** §9.1 planejamento.
- **Critério.** Listener de domínio invalida tags corretas. **Dep.** —.

##### RF-PLT-10 — Spec OpenAPI via Scramble

- **Descrição.** `/docs/api.json` retorna spec OpenAPI 3.x; UI em `/docs/api` gated por Admin.
- **Prioridade.** MUST. **Fonte.** §2.12 planejamento.
- **Critério.** Spec válido passa em validador OpenAPI. **Dep.** RF-PLT-01.

##### RF-PLT-11 — Paginação cursor-based

- **Descrição.** Listagens grandes (convites, reservas, votos) usam `cursorPaginate` com envelope `{data, meta, links}`.
- **Prioridade.** MUST. **Fonte.** §2.6 planejamento.
- **Critério.** Resposta respeita shape canônico. **Dep.** —.

##### RF-PLT-12 — Convenção filter/sort/page (JSON:API)

- **Descrição.** Todas as listagens aceitam `filter[<campo>]`, `sort`, `page[size]`, `page[cursor]`; campos permitidos via `spatie/laravel-query-builder`.
- **Prioridade.** MUST. **Fonte.** §2.14 planejamento.
- **Critério.** Filtro inválido → 422. **Dep.** RF-PLT-11.

##### RF-PLT-13 — Envelope de erro único

- **Descrição.** Handler global converte `Throwable` em JSON com `{error, message, details, request_id, timestamp}` conforme §2.11.
- **Prioridade.** MUST. **Fonte.** §2.11 planejamento.
- **Critério.** Tabela de mapeamento `Exception → HTTP code` respeitada em testes. **Dep.** RF-PLT-02.

##### RF-PLT-14 — Route model binding por ULID

- **Descrição.** Trait `HasUlid` define `getRouteKeyName() = 'ulid'`; nenhum ID sequencial em URL pública.
- **Prioridade.** MUST. **Fonte.** §2.7 planejamento.
- **Critério.** `/eventos/{evento:ulid}` resolve por `ulid`; ID numérico → 404. **Dep.** —.

##### RF-PLT-15 — Architecture tests Pest

- **Descrição.** Testes `arch` garantem que `Actions` não usam `Illuminate\Http\*`, controllers são finos, models não importam actions.
- **Prioridade.** MUST. **Fonte.** §1.3, §10.5 planejamento.
- **Critério.** `pest --testsuite=Arch` passa. **Dep.** —.

##### RF-PLT-16 — Formato declare(strict_types)

- **Descrição.** Todo arquivo PHP inicia com `declare(strict_types=1);`.
- **Prioridade.** MUST. **Fonte.** §0.7 planejamento.
- **Critério.** Arch test `toUseStrictTypes()`. **Dep.** —.

##### RF-PLT-17 — Endpoint `GET /up` healthcheck

- **Descrição.** Laravel health check em `/up` responde 200 quando DB + Redis OK.
- **Prioridade.** MUST. **Fonte.** §2.1 planejamento.
- **Critério.** Downtime detectado em ≤ 30s via probe externo. **Dep.** —.

##### RF-PLT-18 — Sentry error tracking

- **Descrição.** `sentry/sentry-laravel` instalado; sample rate 100% exceptions, 10% performance.
- **Prioridade.** MUST. **Fonte.** §12.2 planejamento.
- **Critério.** Exception em produção aparece no projeto Sentry. **Dep.** —.

##### RF-PLT-19 — Pulse dashboards

- **Descrição.** `laravel/pulse` configurado; `/pulse` gated por Admin.
- **Prioridade.** MUST. **Fonte.** §12.2 planejamento.
- **Critério.** Dashboards de slow query, cache hit, jobs, exceptions ativos. **Dep.** —.

##### RF-PLT-20 — Laravel Pint + PHPStan level 6

- **Descrição.** CI roda Pint (format) e PHPStan level 6 sem erros antes de merge.
- **Prioridade.** MUST. **Fonte.** Apêndice A planejamento.
- **Critério.** GitHub Actions pipeline verde. **Dep.** —.

---

#### 3.1.12 Admin API (RF-ADMIN)

> Essas RFs são exposições Admin (Blade/Livewire) que consomem as mesmas actions listadas acima. Listados aqui para rastreabilidade das telas em [`macro-screens.md`](./macro-screens.md).

##### RF-ADMIN-01 — Dashboard KPIs

- **Descrição.** Dashboard com 6 KPIs, gráfico de adesões, fila de aprovações pendentes.
- **Prioridade.** MUST. **Fonte.** §1.2 macro-screens.
- **Critério.** Carrega em < 2s com 10 eventos e 1000 adesões. **Dep.** RF-COM-03, RF-CNV-02, RF-SEA-02.

##### RF-ADMIN-02 — CRUD completo por Livewire

- **Descrição.** Eventos, Pacotes, Produtos, Convites, Mesas, Extras, Enquetes, Usuários.
- **Prioridade.** MUST. **Fonte.** §1.3–§1.17 macro-screens.
- **Critério.** Paridade de features com API para cada domínio. **Dep.** múltiplas.

##### RF-ADMIN-03 — Operação / Check-in

- **Descrição.** Interface tablet para leitura de QR e validação.
- **Prioridade.** MUST. **Fonte.** §1.18 macro-screens.
- **Critério.** Leitura + validação em ≤ 2s; trilha em `activity_log`. **Dep.** RF-CNV-14.

##### RF-ADMIN-04 — Relatórios exportáveis

- **Descrição.** Gera Excel/PDF via fila `exports` com URL S3 assinada.
- **Prioridade.** MUST. **Fonte.** §1.17 macro-screens.
- **Critério.** Export de 10k linhas em ≤ 5 min. **Dep.** RF-PLT-07.

---

### 3.2 Interfaces externas

#### 3.2.1 Interface API REST (consumo por SPA/Mobile)

- **Protocolo.** HTTPS obrigatório em produção; HTTP/2.
- **Base URL.** `https://api.portalartfinal.com.br/api/v1`.
- **Auth.** Bearer (mobile) ou cookie (SPA).
- **Content-Type.** `application/json` em requisições e respostas.
- **Headers obrigatórios.** `X-Request-Id`, `X-Correlation-Id`, `X-Idempotency-Key` (quando aplicável), `Accept-Language: pt-BR` padrão.
- **Versionamento.** Prefixo `api/v1`; mudanças breaking → `api/v2`. Deprecação via RFC 8594.
- **Paginação.** Envelope `{data, meta, links}` (§2.6 planejamento).
- **Erros.** Envelope único §2.11.

#### 3.2.2 Interface Webhook (inbound)

- **URL.** `https://api.portalartfinal.com.br/webhooks/pagamentos/{provider}` (provider regex `itau|mock`).
- **Método.** POST, sem CSRF.
- **Auth.** Header `X-Signature` com HMAC-SHA256 de `raw_body` + `webhook_secret`.
- **Resposta.** `202 Accepted` (novo), `200 already_processed` (duplicado), `400 WebhookInvalido`, `401 Unauthenticated`.
- **Rate limit.** 600 req/min por IP.

#### 3.2.3 Interface Gateway Itaú (outbound)

- **Conector.** `ItauConnector` (Saloon); base_url e token em config.
- **Requests.** `CriarCobrancaRequest`, `ConsultarCobrancaRequest`.
- **Resiliência.** Timeout ≤ 10s; retry 2x com backoff.

#### 3.2.4 Interface Storage S3/R2

- **Disk.** `s3-private` (privado); URL assinada com TTL ≤ 5 min.
- **Uso.** Relatórios gerados, PDFs de comprovante, uploads de capas.

#### 3.2.5 Interface E-mail

- **Provedor.** SMTP configurado (Mailpit em dev, Postmark/Resend em prod).
- **Envio.** `Mail::queue` para fila `notifications`.

#### 3.2.6 Interface Push (F8)

- **Provedor.** Expo Push.
- **Registro.** Endpoint `POST /api/v1/me/push-tokens`.

---

### 3.3 Requisitos de Performance

#### RNF-PRF-01 — P95 de listagens

- **Descrição.** Endpoints de listagem paginada DEVEM ter **P95 ≤ 500ms** no dataset de produção.
- **Prioridade.** MUST. **Fonte.** [`../prd/PERFORMANCE.md`](../prd/PERFORMANCE.md).
- **Critério.** Pulse/Sentry performance metric reporta P95 < 500ms por 7 dias consecutivos.

#### RNF-PRF-02 — P95 de reserva de assento

- **Descrição.** `POST /reservas` DEVE ter **P95 ≤ 700ms** sob carga normal.
- **Prioridade.** MUST. **Fonte.** §14 F5 planejamento (aceite).
- **Critério.** Teste de carga com 100 RPS em 5 min; P95 < 700ms.

#### RNF-PRF-03 — Throughput de reservas

- **Descrição.** Sistema DEVE sustentar ≥ 50 reservas/s com 0% de conflito indevido.
- **Prioridade.** MUST. **Fonte.** §14 F5 planejamento.
- **Critério.** Teste de concorrência simula 1.000 tentativas no mesmo assento; exatamente 1 sucesso.

#### RNF-PRF-04 — Emissão em lote

- **Descrição.** `EmitirLoteConvitesJob` processa 500 convites em ≤ 60s.
- **Prioridade.** MUST. **Fonte.** §14 F4 planejamento.
- **Critério.** Teste real em ambiente padrão.

#### RNF-PRF-05 — Webhook processado em ≤ 30s

- **Descrição.** Tempo entre recebimento do webhook e aplicação do efeito (convites emitidos, adesão ativada) DEVE ser ≤ 30s em P95.
- **Prioridade.** MUST. **Fonte.** §14 F6 planejamento.
- **Critério.** Métrica customizada em Pulse.

#### RNF-PRF-06 — Cache hit ratio

- **Descrição.** Redis cache hit ratio DEVE ser ≥ 70% em endpoints com cache (mapa, catálogo).
- **Prioridade.** SHOULD. **Fonte.** §9 planejamento.
- **Critério.** Dashboard Pulse reporta ratio.

#### RNF-PRF-07 — Tamanho de payload

- **Descrição.** Resposta de listagem com `page[size]=50` DEVE ter ≤ 500 KB.
- **Prioridade.** SHOULD. **Fonte.** decisão de produto.
- **Critério.** Inspeção em CI.

#### RNF-PRF-08 — Tempo de build CI

- **Descrição.** Pipeline completa (Pint + PHPStan + Pest + Prettier) ≤ 10 min.
- **Prioridade.** SHOULD. **Fonte.** decisão engenharia.
- **Critério.** Métrica GitHub Actions.

---

### 3.4 Requisitos de Segurança

Derivados de §11 planejamento, [`../prd/SEGURANCA.md`](../prd/SEGURANCA.md) e OWASP Top 10 (2021).

#### RNF-SEC-01 — OWASP A01 Broken Access Control

- **Descrição.** Toda rota autenticada protegida por middleware + Policy; nunca confiar no frontend.
- **Prioridade.** MUST. **Fonte.** §11.3 planejamento.
- **Critério.** Policies para `Evento`, `Adesao`, `Convite`, `MapaMesa`, `ReservaAssento`, `PedidoExtra`, `Enquete`, `Relatorio`.

#### RNF-SEC-02 — OWASP A02 Cryptographic Failures

- **Descrição.** TLS 1.2+ obrigatório; bcrypt ≥ 12 rounds para senha; HMAC-SHA256 em webhooks; token SHA-256.
- **Prioridade.** MUST. **Fonte.** §11.6, §11.7 planejamento.
- **Critério.** HSTS header ativo; rotina de validação.

#### RNF-SEC-03 — OWASP A03 Injection

- **Descrição.** Apenas Eloquent/QueryBuilder; queries raw exigem bindings explícitos.
- **Prioridade.** MUST. **Fonte.** §11.2 planejamento.
- **Critério.** PHPStan + Psalm detectam concatenação direta.

#### RNF-SEC-04 — OWASP A04 Insecure Design

- **Descrição.** Design-by-contract: invariantes no banco (UNIQUE parcial, CHECK constraints), concorrência por lock + transação, idempotência.
- **Prioridade.** MUST. **Fonte.** §4 e §5 planejamento.
- **Critério.** Testes de concorrência, testes de idempotência.

#### RNF-SEC-05 — OWASP A05 Security Misconfiguration

- **Descrição.** Headers `Strict-Transport-Security`, `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` aplicados.
- **Prioridade.** MUST. **Fonte.** §11.9 planejamento.
- **Critério.** Scanner externo (SSL Labs A+).

#### RNF-SEC-06 — OWASP A07 Identification and Auth Failures

- **Descrição.** Rate limit em login, revogação de tokens, MFA opcional admin, sessão com `HttpOnly`, `Secure`, `SameSite=lax`.
- **Prioridade.** MUST. **Fonte.** §6, §11.4 planejamento.
- **Critério.** RF-AUTH-03 validado.

#### RNF-SEC-07 — OWASP A08 Software and Data Integrity

- **Descrição.** Composer com `composer.lock` commitado; Dependabot/Renovate; verificação de pacotes.
- **Prioridade.** SHOULD. **Fonte.** planejamento operacional.
- **Critério.** Audit Composer em CI.

#### RNF-SEC-08 — OWASP A09 Security Logging and Monitoring

- **Descrição.** Logs estruturados JSON com `request_id`, `actor_*`; Sentry; alertas Slack para anomalias.
- **Prioridade.** MUST. **Fonte.** §11.8, §12 planejamento.
- **Critério.** Alerta disparado em teste de caos.

#### RNF-SEC-09 — OWASP A10 SSRF

- **Descrição.** Chamadas HTTP outbound restritas por allowlist de hosts (gateway, SMTP, Expo, S3); SSRF defense via `guzzle` com validação de URL.
- **Prioridade.** MUST. **Fonte.** decisão engenharia.
- **Critério.** Config de allowlist.

#### RNF-SEC-10 — LGPD — Coleta mínima

- **Descrição.** Apenas campos marcados como `required` pela regra de negócio são coletados.
- **Prioridade.** MUST. **Fonte.** §11.10 planejamento.
- **Critério.** Auditoria de formulários.

#### RNF-SEC-11 — LGPD — Anonimização

- **Descrição.** Dados pessoais de convidado anonimizados 90 dias após evento.
- **Prioridade.** MUST. **Fonte.** RF-AUD-05.
- **Critério.** Registros anonimizados verificáveis.

#### RNF-SEC-12 — LGPD — Direito do titular

- **Descrição.** Endpoint `DELETE /api/v1/me` realiza soft-delete + anonimização.
- **Prioridade.** MUST. **Fonte.** §11.10 planejamento.
- **Critério.** Após request, dados pessoais não retornáveis.

#### RNF-SEC-13 — Upload seguro

- **Descrição.** Validar MIME real, gerar nome server-side, storage privado, TTL ≤ 5 min em URL.
- **Prioridade.** MUST. **Fonte.** §11.7 planejamento.
- **Critério.** Upload de arquivo malicioso (MIME spoof) rejeitado.

#### RNF-SEC-14 — Secrets em vault

- **Descrição.** Nenhum secret em repositório; `.env` gerenciado via HashiCorp Vault, AWS Secrets Manager ou equivalente.
- **Prioridade.** MUST. **Fonte.** §11.8 planejamento + best practices.
- **Critério.** Scan do repo limpo (truffleHog).

---

### 3.5 Disponibilidade

#### RNF-DIS-01 — SLA 99,5%

- **Descrição.** Sistema DEVE estar disponível 99,5% do tempo, medido mensalmente.
- **Prioridade.** MUST. **Fonte.** Apêndice B Q5 planejamento.
- **Critério.** Uptime ≥ 99,5% medido por probe externo (ex.: UptimeRobot).

#### RNF-DIS-02 — Health check

- **Descrição.** Endpoint `/up` responde 200 quando DB + Redis acessíveis.
- **Prioridade.** MUST. **Fonte.** RF-PLT-17.
- **Critério.** Probe a cada 30s.

#### RNF-DIS-03 — Graceful degradation

- **Descrição.** Falha em gateway externo NÃO derruba o sistema; endpoints de pagamento retornam 503 com mensagem clara.
- **Prioridade.** MUST. **Fonte.** §8 planejamento + design.
- **Critério.** Chaos test com gateway offline.

#### RNF-DIS-04 — Backup e restore

- **Descrição.** Backup diário do PostgreSQL (retenção 30 dias); RTO ≤ 4h, RPO ≤ 24h.
- **Prioridade.** MUST. **Fonte.** política de SRE.
- **Critério.** DR drill trimestral.

---

### 3.6 Escalabilidade

#### RNF-ESC-01 — Horizontal (stateless)

- **Descrição.** API DEVE ser stateless (Redis para sessão/fila); suporta N instâncias atrás de load balancer.
- **Prioridade.** MUST. **Fonte.** design.
- **Critério.** Teste com 2 instâncias → operações consistentes.

#### RNF-ESC-02 — Workers Horizon autoscaling

- **Descrição.** Filas com `balance=auto` escalam `minProcesses..maxProcesses` conforme demanda.
- **Prioridade.** MUST. **Fonte.** §7.2 planejamento.
- **Critério.** Supervisor `default` varia de 3 a 20 conforme carga.

#### RNF-ESC-03 — Read replica PostgreSQL (futuro)

- **Descrição.** Arquitetura prepara para leitura em réplica quando necessário (não MVP).
- **Prioridade.** COULD. **Fonte.** planejamento.
- **Critério.** Config suporta leitura/escrita split.

#### RNF-ESC-04 — Limite de convites em lote

- **Descrição.** API aceita no máximo 500 convites por `POST /lotes`; acima disso, múltiplos lotes.
- **Prioridade.** MUST. **Fonte.** §14 F4 planejamento.
- **Critério.** Validação em FormRequest.

#### RNF-ESC-05 — Storage crescente

- **Descrição.** Relatórios e PDFs em S3 com lifecycle policy (glacier após 90 dias).
- **Prioridade.** SHOULD. **Fonte.** operacional.
- **Critério.** Configuração S3 documentada.

---

## 4. Rastreabilidade — Matriz RF ↔ Endpoint ↔ Action

### 4.1 Convenções

- Célula vazia = não se aplica diretamente (RFs de plataforma ou de política não mapeiam endpoint/action 1:1).
- Endpoints listados são os primários; variações (PATCH, DELETE) derivam do mesmo recurso.
- Telas em [`macro-screens.md`](./macro-screens.md) referenciadas pela seção.

### 4.2 Matriz

| RF          | Título curto      | Endpoint principal                              | Action/Serviço                            | Tela       |
| ----------- | ----------------- | ----------------------------------------------- | ----------------------------------------- | ---------- |
| RF-AUTH-01  | Login SPA         | `POST /api/v1/auth/login` (mode=spa)            | `LoginController`                         | §2.1       |
| RF-AUTH-02  | Login mobile      | `POST /api/v1/auth/login` (mode=token)          | `LoginController`                         | §3.1       |
| RF-AUTH-03  | Rate login        | —                                               | `RateLimiterServiceProvider::login`       | §2.1       |
| RF-AUTH-04  | Token mágico      | —                                               | `EmitirConviteAction` + `Ulid`/random     | —          |
| RF-AUTH-05  | Resolve token     | `GET /api/v1/convite/{token}`                   | `ResolveConviteToken` middleware          | §2.7       |
| RF-AUTH-06  | ACL Spatie        | —                                               | `AuthServiceProvider`                     | §1.15      |
| RF-AUTH-07  | Abilities mobile  | —                                               | `EnsureSanctumAbility`                    | §3.1       |
| RF-AUTH-08  | Logout            | `POST /api/v1/auth/logout`                      | `LogoutController`                        | §2.1/§3.1  |
| RF-AUTH-09  | /me               | `GET /api/v1/me`                                | `MeController`                            | §2.2/§3.2  |
| RF-AUTH-10  | Revogação tokens  | `DELETE /admin/users/{id}/tokens`               | Controller admin                          | §1.15      |
| RF-CAD-01   | CRUD Organização  | `/admin/cadastros/organizacoes`                 | Livewire CRUD                             | §1.3       |
| RF-CAD-02   | CRUD Instituição  | `/admin/cadastros/instituicoes`                 | Livewire                                  | §1.3       |
| RF-CAD-03   | CRUD Curso        | `/admin/cadastros/cursos`                       | Livewire                                  | §1.3       |
| RF-CAD-04   | CRUD Turma        | `/admin/cadastros/turmas`                       | Livewire                                  | §1.3       |
| RF-CAD-05   | CRUD Evento       | `/admin/cadastros/eventos`                      | Livewire + `EventoController`             | §1.3/§1.4  |
| RF-CAD-06   | Pivô turma-evento | `PATCH /admin/eventos/{id}/turmas`              | Livewire                                  | §1.4       |
| RF-CAD-07   | Publicar evento   | `POST /admin/eventos/{id}/publicar`             | `PublicarEventoAction`                    | §1.4       |
| RF-CAD-08   | Atualizar janelas | `PATCH /admin/eventos/{id}`                     | `AtualizarJanelasEventoAction`            | §1.4       |
| RF-CAD-09   | Bloqueio delete   | `DELETE /admin/eventos/{id}`                    | Policy + FK                               | §1.3       |
| RF-CAD-10   | CRUD Formando     | `/admin/cadastros/formandos`                    | Livewire                                  | §1.4       |
| RF-CAD-11   | Import CSV        | `POST /admin/formandos/import`                  | Job `ImportarFormandosJob`                | §1.4       |
| RF-COM-01   | CRUD Pacote       | `/admin/comercial/pacotes`                      | Livewire                                  | §1.5       |
| RF-COM-02   | CRUD Produto      | `/admin/comercial/produtos`                     | Livewire                                  | §1.5       |
| RF-COM-03   | Criar Adesão      | `POST /api/v1/eventos/{ulid}/adesoes`           | `CriarAdesaoAction`                       | §2.3       |
| RF-COM-04   | Snapshot          | —                                               | `CriarAdesaoAction::execute`              | §1.7       |
| RF-COM-05   | Única ativa       | —                                               | UNIQUE parcial SQL                        | §1.6       |
| RF-COM-06   | Parcelas          | —                                               | `GerarParcelasAction`                     | §2.4       |
| RF-COM-07   | Confirmar adesão  | — (via webhook)                                 | `ConfirmarAdesaoAction`                   | §1.7       |
| RF-COM-08   | Cancelar          | `POST /admin/adesoes/{id}/cancelar`             | `CancelarAdesaoAction`                    | §1.7       |
| RF-COM-09   | → concluida       | —                                               | Listener `PagamentoConfirmado`            | §1.7       |
| RF-COM-10   | → inadimplente    | —                                               | `VerificarInadimplenciaJob`               | §1.6       |
| RF-CNV-01   | Cota              | `GET /api/v1/me/cotas`                          | `CotaCalculator`                          | §2.6       |
| RF-CNV-02   | Emitir convite    | `POST /api/v1/eventos/{ulid}/convites`          | `EmitirConviteAction`                     | §2.6       |
| RF-CNV-03   | Código            | —                                               | `Str::upper(Str::random(10))` + validador | §2.6       |
| RF-CNV-04   | Lote              | `POST /api/v1/eventos/{ulid}/convites/lotes`    | `EmitirLoteConvitesAction` + Job          | §2.6       |
| RF-CNV-05   | Reemitir          | `POST /admin/convites/{id}/reemitir`            | `ReemitirConviteAction`                   | §1.8       |
| RF-CNV-06   | Transferir        | `POST /admin/convites/{id}/transferir`          | `TransferirConviteAction`                 | §1.8       |
| RF-CNV-07   | Cancelar          | `DELETE /api/v1/eventos/{ulid}/convites/{ulid}` | `CancelarConviteAction`                   | §2.6       |
| RF-CNV-08   | Acesso público    | `GET /api/v1/convite/{token}`                   | `AcessoConviteController`                 | §2.7       |
| RF-CNV-09   | Visualizado       | —                                               | Observer ou action inline                 | §2.7       |
| RF-CNV-10   | RSVP              | `POST /api/v1/convite/{token}/rsvp`             | `RegistrarRsvpAction`                     | §2.7       |
| RF-CNV-11   | Histórico         | —                                               | INSERT em `rsvp_historico`                | §1.8       |
| RF-CNV-12   | Rate RSVP         | —                                               | `throttle:convite`                        | §2.7       |
| RF-CNV-13   | Reminder          | —                                               | `EnviarReminderRsvpJob`                   | §1.8       |
| RF-CNV-14   | Inutilizado       | `POST /admin/operacao/checkin`                  | Action check-in                           | §1.18      |
| RF-SEA-01   | CRUD mapa         | `/admin/seating/mapas/{id}`                     | Livewire                                  | §1.9       |
| RF-SEA-02   | Reservar          | `POST /api/v1/eventos/{ulid}/mesas/reservas`    | `ReservarAssentoAction`                   | §2.8       |
| RF-SEA-03   | Idempotência      | —                                               | `IdempotencyKeyGuard`                     | §2.8       |
| RF-SEA-04   | Confirmar         | `POST /.../reservas/{ulid}/confirmar`           | `ConfirmarAssentoAction`                  | §2.8       |
| RF-SEA-05   | Liberar           | `DELETE /.../reservas/{ulid}`                   | `LiberarAssentoAction`                    | §2.8       |
| RF-SEA-06   | Expirar holds     | — (scheduled)                                   | `ExpirarHoldsJob`                         | §1.10      |
| RF-SEA-07   | Trocar            | `POST /.../reservas/{ulid}/trocar`              | `TrocarAssentoAction`                     | §2.8       |
| RF-SEA-08   | Bloquear          | `POST /admin/seating/mapas/{id}/bloquear`       | Action                                    | §1.10      |
| RF-SEA-09   | Rate seating      | —                                               | `throttle.actor:seating`                  | §2.8       |
| RF-SEA-10   | Mapa / delta      | `GET /.../mesas/mapa?since=…`                   | `MapaController`                          | §2.8       |
| RF-SEA-11   | Invalida cache    | —                                               | Listener                                  | —          |
| RF-SEA-12   | Unique parcial    | —                                               | Migration                                 | —          |
| RF-SEA-13   | CHECK             | —                                               | Migration                                 | —          |
| RF-SEA-14   | WS delta          | —                                               | `PublicarAtualizacaoMapaJob`              | §2.8       |
| RF-EXT-01   | CRUD produto      | `/admin/extras/produtos`                        | Livewire                                  | §1.11      |
| RF-EXT-02   | Criar pedido      | `POST /api/v1/eventos/{ulid}/extras/pedidos`    | `CriarPedidoExtraAction`                  | §2.9       |
| RF-EXT-03   | Aprovação         | `POST /admin/pedidos/{id}/aprovar`              | `AprovarPedidoExtraAction`                | §1.12      |
| RF-EXT-04   | Pagamento extra   | — (via webhook)                                 | `ConfirmarPagamentoExtraAction`           | §1.12      |
| RF-EXT-05   | Estornar          | `POST /admin/pedidos/{id}/estornar`             | `EstornarPedidoExtraAction`               | §1.12      |
| RF-EXT-06   | Estoque           | —                                               | `EstoqueService`                          | §1.11      |
| RF-EXT-07   | Expira pedido     | — (scheduled)                                   | `ExpirarPedidosExtrasJob`                 | §1.12      |
| RF-PAG-01   | Intent            | `POST /api/v1/pagamentos/intents`               | `IniciarPagamentoAction`                  | §2.5       |
| RF-PAG-02   | Webhook           | `POST /webhooks/pagamentos/{provider}`          | `ProcessarWebhookPagamentoAction`         | §1.13      |
| RF-PAG-03   | HMAC              | —                                               | `ItauGateway::assinaturaValida`           | §1.13      |
| RF-PAG-04   | Retry             | —                                               | `ProcessarWebhookPagamentoJob::backoff`   | —          |
| RF-PAG-05   | Reconciliação     | — (scheduled)                                   | `ReconciliarPagamentosJob`                | —          |
| RF-PAG-06   | Status            | —                                               | Enum `StatusPagamento`                    | §1.13      |
| RF-PAG-07   | Sem cartão        | —                                               | Schema                                    | —          |
| RF-PAG-08   | Rate webhook      | —                                               | `throttle:webhook`                        | —          |
| RF-PAG-09   | Reprocessar       | `POST /admin/webhooks/{id}/reprocessar`         | Controller                                | §1.13      |
| RF-ENQ-01   | CRUD enquete      | `/admin/enquetes`                               | Livewire                                  | §1.14      |
| RF-ENQ-02   | Publicar          | `POST /admin/enquetes/{id}/publicar`            | `PublicarEnqueteAction`                   | §1.14      |
| RF-ENQ-03   | Votar             | `POST /api/v1/.../enquetes/{ulid}/votos`        | `RegistrarVotoAction`                     | §2.10      |
| RF-ENQ-04   | Unicidade         | —                                               | UNIQUE parcial                            | —          |
| RF-ENQ-05   | Encerrar          | `POST /admin/enquetes/{id}/encerrar`            | `EncerrarEnqueteAction`                   | §1.14      |
| RF-ENQ-06   | Visibilidade      | —                                               | Resource condicional                      | §2.10      |
| RF-ENQ-07   | Rate voto         | —                                               | `throttle.actor:voto`                     | §2.10      |
| RF-NOT-01   | Templates         | `/admin/comunicacao/templates`                  | Livewire                                  | —          |
| RF-NOT-02   | E-mail convite    | —                                               | `EnviarConviteEmailJob`                   | —          |
| RF-NOT-03   | Reenvio           | `POST /admin/notificacoes/{id}/reenviar`        | Controller                                | —          |
| RF-NOT-04   | Push              | —                                               | `NotificarPushJob`                        | §3.5       |
| RF-NOT-05   | Mask LGPD         | —                                               | `CorrelationProcessor` / Logging          | —          |
| RF-AUD-01   | Activity log      | —                                               | `spatie/activitylog`                      | §1.16      |
| RF-AUD-02   | Correlation       | —                                               | `AttachRequestId` + `CorrelationContext`  | —          |
| RF-AUD-03   | Snapshots         | —                                               | Actions específicas                       | §1.7/§1.8  |
| RF-AUD-04   | Hash termo        | —                                               | `CriarAdesaoAction`                       | §1.7       |
| RF-AUD-05   | Anonimizar        | — (scheduled)                                   | `AnonimizarDadosPosEventoJob`             | —          |
| RF-PLT-01   | Routing           | —                                               | `bootstrap/app.php`                       | —          |
| RF-PLT-02   | Request Id        | —                                               | `AttachRequestId`                         | —          |
| RF-PLT-03   | Redis lock        | —                                               | `Cache::lock`                             | —          |
| RF-PLT-04   | Limiters          | —                                               | `RateLimiterServiceProvider`              | —          |
| RF-PLT-05   | Idempotency       | —                                               | `IdempotencyKeyGuard`                     | —          |
| RF-PLT-06   | Gateway contract  | —                                               | `PaymentGatewayContract`                  | —          |
| RF-PLT-07   | Horizon           | `/horizon`                                      | config/horizon.php                        | —          |
| RF-PLT-08   | N+1               | —                                               | `preventLazyLoading`                      | —          |
| RF-PLT-09   | Cache tags        | —                                               | Listeners                                 | —          |
| RF-PLT-10   | OpenAPI           | `/docs/api.json`                                | `dedoc/scramble`                          | —          |
| RF-PLT-11   | Paginação         | —                                               | `cursorPaginate`                          | —          |
| RF-PLT-12   | Filter/sort       | —                                               | `spatie/laravel-query-builder`            | —          |
| RF-PLT-13   | Envelope erro     | —                                               | `withExceptions` handler                  | —          |
| RF-PLT-14   | ULID binding      | —                                               | `HasUlid` trait                           | —          |
| RF-PLT-15   | Arch tests        | —                                               | `pest arch`                               | —          |
| RF-PLT-16   | strict_types      | —                                               | Arch test                                 | —          |
| RF-PLT-17   | Health            | `GET /up`                                       | Laravel default                           | —          |
| RF-PLT-18   | Sentry            | —                                               | `sentry/sentry-laravel`                   | —          |
| RF-PLT-19   | Pulse             | `/pulse`                                        | `laravel/pulse`                           | —          |
| RF-PLT-20   | Pint/PHPStan      | —                                               | CI pipeline                               | —          |
| RF-ADMIN-01 | Dashboard         | `/admin/dashboard`                              | Livewire KPI                              | §1.2       |
| RF-ADMIN-02 | CRUD admin        | `/admin/*`                                      | Livewire                                  | §1.3–§1.17 |
| RF-ADMIN-03 | Check-in          | `/admin/operacao/checkin`                       | Controller                                | §1.18      |
| RF-ADMIN-04 | Relatórios        | `/admin/relatorios`                             | `GerarRelatorioExcelJob`                  | §1.17      |

### 4.3 Cobertura — RFs por fase do roadmap

| Fase   | RFs críticos                                          |
| ------ | ----------------------------------------------------- |
| **F1** | RF-AUTH-01..05, RF-PLT-01..17, RF-AUD-01..02          |
| **F2** | RF-CAD-01..11, RF-COM-01..02, RF-ADMIN-01..02         |
| **F3** | Consome API; valida RF-PLT-10, RF-AUTH-01             |
| **F4** | RF-CNV-01..14, RF-NOT-01..03                          |
| **F5** | RF-SEA-01..14 (crítico)                               |
| **F6** | RF-EXT-01..07, RF-PAG-01..09, RF-ENQ-01..07           |
| **F7** | RF-SEC-01..14, RNF-DIS-01..04, RF-AUD-05, RF-ADMIN-04 |
| **F8** | RF-AUTH-02, RF-AUTH-07, RF-NOT-04, telas §3.1..§3.6   |

### 4.4 Mapeamento RF ↔ AC (rastreabilidade para QA)

Os **critérios de aceite BDD** em [`qa/acceptance-criteria.md`](../qa/acceptance-criteria.md) seguem o mesmo prefixo de contexto dos RFs. Um `AC-<CTX>-NNN` valida um ou mais `RF-<CTX>-NN` com o mesmo prefixo.

| Prefixo | Contexto                   | Seção SRS              | Seção acceptance-criteria      | RFs (count)     | ACs (count) |
| ------- | -------------------------- | ---------------------- | ------------------------------ | --------------- | ----------- |
| `AUTH`  | Identidade e Acesso        | §3.1.1                 | §1. Identidade e Acesso        | 10              | 10          |
| `CAD`   | Cadastro estrutural        | §3.1.2                 | §2. Cadastro                   | 11              | 10          |
| `COM`   | Comercial e Adesão         | §3.1.3                 | §3. Comercial e Adesão         | 10              | 16          |
| `CNV`   | Convites                   | §3.1.4                 | §4. Convites                   | 14              | 10          |
| `RSVP`  | RSVP (sub-contexto de CNV) | §3.1.4 (RF-CNV-08..14) | §5. RSVP                       | incluído em CNV | 10          |
| `SEA`   | Seating                    | §3.1.5                 | §6. Seating                    | 14              | 20          |
| `EXT`   | Extras                     | §3.1.6                 | §7. Extras                     | 7               | 10          |
| `PAG`   | Pagamentos e Webhook       | §3.1.7                 | §8. Pagamentos e Webhook       | 9               | 15          |
| `ENQ`   | Enquetes e Votação         | §3.1.8                 | §9. Enquetes                   | 7               | 10          |
| `NOT`   | Notificações e Comunicação | §3.1.9                 | §10. Comunicação               | 4               | 5           |
| `AUD`   | Auditoria                  | §3.1.10                | — (meta-RF, sem BDD direto)    | 5               | —           |
| `PLT`   | Plataforma                 | §3.1.11                | — (meta-RF, sem BDD direto)    | 17              | —           |
| `ADMIN` | Admin backoffice           | §3.1.12                | — (validado via macro-screens) | 4               | —           |
| `SEC`   | Segurança transversal      | §3.4                   | coberto por `qa/nfr-tests.md`  | 14              | —           |

**Convenções de rastreabilidade:**

- **Mesmo prefixo nas duas ferramentas** → `grep RF-SEA qa/` retorna os ACs correspondentes e vice-versa.
- **Contextos sem AC** (`AUD`, `PLT`, `ADMIN`, `SEC`) são **meta-requisitos** — verificados via:
    - `PLT` (plataforma): arch tests Pest e validação de infraestrutura (`docs/qa/test-plan.md` §Arch tests).
    - `ADMIN` (backoffice): validado por browser tests sobre as telas de `macro-screens.md`.
    - `AUD` (auditoria): validado por assertivas em `activity_log` nos próprios ACs funcionais.
    - `SEC` (segurança): coberto por `docs/qa/nfr-tests.md` (OWASP A01–A10, LGPD).
- **AC > RF em count** (ex: `COM` 10 RFs → 16 ACs) é esperado — um RF pode ter múltiplos cenários (caminho feliz + exceções + bordas).

> **Renomeações aplicadas em 2026-04-18** para alinhar prefixos (histórico preservado no git): `AC-IDENTITY-*` → `AC-AUTH-*`; `AC-CADASTRO-*` → `AC-CAD-*`; `AC-ADESAO-*` → `AC-COM-*`; `AC-CONVITE-*` → `AC-CNV-*`; `AC-SEATING-*` → `AC-SEA-*`; `AC-ENQUETE-*` → `AC-ENQ-*`; `AC-PAGAMENTO-*` → `AC-PAG-*`; `AC-EXTRA-*` → `AC-EXT-*`; `AC-COM-*` (Comunicação) → `AC-NOT-*` (Notificações).

---

## 5. Apêndices

### 5.1 Critérios de aceite globais (definition of done)

Antes de considerar uma fase concluída:

1. Todos os RFs `MUST` da fase validados via Pest Feature/Unit/Arch.
2. `./vendor/bin/pint --test` passa.
3. `./vendor/bin/phpstan analyse --level=6` sem erros.
4. `php artisan test --compact` verde.
5. OpenAPI spec válida em `/docs/api.json`.
6. Dashboards Pulse + Horizon sem alertas pendentes.
7. Checklist de Segurança §11 planejamento assinado.
8. Documentação correspondente atualizada em `docs/` e `.docs/modules/`.

### 5.2 Ordem de priorização MoSCoW — snapshot

- **MUST (crítico):** todos RF-AUTH, RF-CAD-01..10, RF-COM-01..08, RF-CNV-01..12, RF-SEA-01..13, RF-EXT-01..06, RF-PAG-01..08, RF-ENQ-01..05/07, RF-NOT-01..03/05, RF-AUD-01..05, RF-PLT-01..20, RNF-PRF-01..05, RNF-SEC-01..13, RNF-DIS-01..04.
- **SHOULD:** RF-AUTH-10, RF-CAD-11, RF-COM-09..10, RF-CNV-13, RF-EXT-07, RF-PAG-05, RF-PAG-09, RF-ENQ-06, RF-NOT-03, RNF-PRF-06..08, RNF-SEC-07, RNF-ESC-05.
- **COULD:** RF-SEA-14 (WS push), RF-NOT-04 (push F8), RNF-ESC-03 (read replica).

### 5.3 Questões em aberto (dependência de produto)

Referenciadas no Apêndice B do planejamento:

1. Convidado compra extras direto? (default: não).
2. Comissão aprova extras e trocas? (default: não).
3. Múltiplos mapas por evento? (default: não, MVP).
4. Multi-evento por formando mesmo período? (default: histórico + apenas 1 ativa por evento).
5. SLA > 99,5%? (default: 99,5%).

---

## 6. Referências cruzadas finais

- Fluxos: [`user-flows.md`](./user-flows.md).
- Personas: [`journeys-personas.md`](./journeys-personas.md).
- Telas: [`macro-screens.md`](./macro-screens.md).
- PRD expandido: [`PRD_EXPANDED.md`](./PRD_EXPANDED.md).
- Planejamento técnico: [`../prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md).
- Regras de negócio: [`../prd/REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md).
- PRD v4: [`../prd/PRD_v4.md`](../prd/PRD_v4.md).
