---
title: Critérios de Aceite BDD — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# Critérios de Aceite BDD — Portal ArtFinal v2 (Backend API v1)

Cenários em estilo Gherkin/BDD **em PT-BR** por bounded context. Cada critério é escrito como **pré-condição → evento → resultado esperado**, com dados de teste concretos, prioridade (MoSCoW) e critério de falha.

## Convenções

- **ID**: `AC-<CONTEXTO>-<NNN>` — ex.: `AC-SEA-007`.
- **Prioridade**: `must` (bloqueia release), `should` (bloqueia fase), `could` (não bloqueia).
- **Given/When/Then** em PT-BR; tokens técnicos em inglês (`200`, `X-Idempotency-Key`, `POST /api/v1/...`).
- **Dados de teste** ancorados em factories e states nomeados (§7 `test-plan.md`).
- **Critério de falha** é o oposto do resultado esperado; exemplifica a regressão a evitar.

## Índice

1. [Identidade e Acesso](#1-identidade-e-acesso) — 10 cenários
2. [Cadastro](#2-cadastro) — 10 cenários
3. [Comercial e Adesão](#3-comercial-e-adesão) — 16 cenários
4. [Convites](#4-convites) — 10 cenários
5. [RSVP](#5-rsvp) — 10 cenários
6. [Seating](#6-seating) — 20 cenários
7. [Extras](#7-extras) — 10 cenários
8. [Pagamentos e Webhook](#8-pagamentos-e-webhook) — 15 cenários
9. [Enquetes](#9-enquetes) — 10 cenários
10. [Comunicação](#10-comunicação) — 5 cenários

---

## 1. Identidade e Acesso

### AC-AUTH-001 — Login SPA bem-sucedido do formando

- **Prioridade:** must
- **Pré-condições:** existe `PortalUser` com `email=maria@exemplo.com` e senha conhecida; guard `sanctum` configurado; CSRF cookie disponível.
- **Dado que** o cliente obteve o `XSRF-TOKEN` via `GET /sanctum/csrf-cookie`
- **Quando** envia `POST /api/v1/auth/login` com `{email, password, mode: "spa"}`
- **Então** recebe `200 OK`, cookie `laravel_session` HttpOnly, Secure, SameSite=lax
- **E** o corpo contém `{status: "ok", user: {id: <ulid>, email}}`
- **Dados de teste:** `PortalUser::factory()->create(['email' => 'maria@exemplo.com'])`.
- **Resultado esperado:** sessão criada, requests subsequentes autorizadas.
- **Critério de falha:** 200 sem cookie de sessão, ou 422 (credenciais válidas devem retornar 200/401, nunca 422).

### AC-AUTH-002 — Login mobile retorna token Bearer

- **Prioridade:** must
- **Dado que** o cliente mobile envia `POST /api/v1/auth/login` com `{email, password, mode: "token", device_name: "iPhone-Maria"}`
- **Quando** as credenciais são válidas
- **Então** recebe `200 OK` com `{access_token: "<plain>", abilities: [...], user: {...}}`
- **E** o token persistido em `personal_access_tokens` tem `name="iPhone-Maria"` e `abilities` derivadas de `HasRoles` Spatie.
- **Critério de falha:** token persistido em plain text; 200 sem `access_token`.

### AC-AUTH-003 — Credenciais inválidas retornam 401

- **Prioridade:** must
- **Dado que** `PortalUser` tem senha `segredo123`
- **Quando** login envia `{password: "errado"}`
- **Então** retorna `401 Unauthenticated` com envelope §2.11 `{error: {code: "unauthenticated", message: "Credenciais inválidas."}}`
- **Critério de falha:** 422 (erro semântico errado), ou 200 (falha crítica de AuthN).

### AC-AUTH-004 — `mode` ausente é 422, não default silencioso

- **Prioridade:** must
- **Dado que** request não envia `mode`
- **Quando** chega no `LoginController`
- **Então** `FormRequest` falha com `422` e `details.fields.mode = "O modo é obrigatório."`
- **Critério de falha:** sistema assume `mode=spa` por default — viola contrato explícito §6.2.

### AC-AUTH-005 — Rate limit de login 5/min por email+IP

- **Prioridade:** must
- **Dado que** mesmo email+IP fez 5 tentativas falhas em 60s
- **Quando** envia a 6ª
- **Então** retorna `429 Too Many Requests` com header `Retry-After`
- **E** envelope §2.11 `{error: {code: "rate_limited"}}`
- **Critério de falha:** 6ª tentativa passa → vulnerável a brute-force.

### AC-AUTH-006 — `GET /api/v1/me` sem token retorna 401

- **Prioridade:** must
- **Quando** request sem cookie nem Authorization
- **Então** `401 Unauthenticated` com envelope §2.11
- **Critério de falha:** 200 com user anônimo (vazamento).

### AC-AUTH-007 — `GET /api/v1/me` com Sanctum retorna perfil

- **Prioridade:** must
- **Dado que** token válido do ator `Maria`
- **Quando** chama `GET /api/v1/me`
- **Então** `200 OK` com `{id, nome, email, turma: {...}, abilities: [...]}`
- **Critério de falha:** `200` sem `abilities` → UI fica incapaz de decidir permissões.

### AC-AUTH-008 — Logout invalida token mobile

- **Prioridade:** must
- **Dado que** token `abc.xyz` válido
- **Quando** chama `POST /api/v1/auth/logout` com Bearer `abc.xyz`
- **Então** `204 No Content` e o token é removido de `personal_access_tokens`
- **E** request subsequente com mesmo token retorna `401`.
- **Critério de falha:** token continua válido → falha de logout.

### AC-AUTH-009 — Papel Spatie usa guard correto (`sanctum`)

- **Prioridade:** must
- **Dado que** `PortalUser` tem `$guard_name = 'sanctum'`
- **Quando** chama `$user->hasRole('formando')`
- **Então** retorna `true` se a role foi atribuída no guard `sanctum`
- **Critério de falha:** retorna `false` silenciosamente por olhar guard `web` (bug documentado §6.1).

### AC-AUTH-010 — Admin não consegue acessar endpoint do formando

- **Prioridade:** must
- **Dado que** ator autenticado é `AdminUser` (guard `admin`)
- **Quando** tenta `GET /api/v1/formando/dashboard`
- **Então** `401` ou `403` — nunca `200`.
- **Critério de falha:** cross-guard leak — admin vê dados de formando.

---

## 2. Cadastro

### AC-CAD-001 — Admin cria organização

- **Prioridade:** must
- **Dado que** ator autenticado com permission `organizacoes.create`
- **Quando** envia `POST /api/v1/organizacoes` com `{nome: "ArtFinal LTDA", cnpj: "<válido>"}`
- **Então** `201 Created` com `{id: <ulid>, nome, cnpj}` e Location header.
- **E** registro persistido com `ulid`, `ativo=true`, `created_at`.
- **Critério de falha:** 201 sem `ulid` ou sem Location.

### AC-CAD-002 — CNPJ inválido retorna 422

- **Prioridade:** must
- **Quando** envia CNPJ `00.000.000/0000-00`
- **Então** `422` com `details.fields.cnpj = "CNPJ inválido."` (validador `pt-br-validator`).
- **Critério de falha:** 201 com CNPJ falso → contamina relatórios.

### AC-CAD-003 — Instituição sob organização inativa é bloqueada

- **Prioridade:** must
- **Dado que** `Organizacao.ativo=false`
- **Quando** tenta criar `Instituicao` vinculada
- **Então** `409 Conflict` com `{error: {code: "organizacao_inativa"}}`.
- **Critério de falha:** 201 mesmo com parent inativo → inconsistência hierárquica.

### AC-CAD-004 — Curso duplicado na mesma instituição é 409

- **Prioridade:** should
- **Dado que** já existe `Curso(nome="Engenharia")` em `Instituicao=X`
- **Quando** tenta criar outro com mesmo nome no mesmo pai
- **Então** `409 Conflict` com `code: "curso_duplicado"`.

### AC-CAD-005 — Turma exige código único

- **Prioridade:** must
- **Dado que** existe `Turma(codigo="ENG-2025-A")`
- **Quando** POST tenta criar outra com mesmo código
- **Então** `409` com `field=codigo`.

### AC-CAD-006 — Admin cria evento vinculando múltiplas turmas (N:N)

- **Prioridade:** must
- **Quando** POST `{nome, data_evento, turmas: [ulid_a, ulid_b]}`
- **Então** `201` com `turmas` na response; `TurmaEvento` pivot populado.

### AC-CAD-007 — Formando sem portal_user criado = rascunho

- **Prioridade:** should
- **Quando** admin cria formando sem convidar
- **Então** registro com `portal_user_id=null`, `status=rascunho`.
- **E** formando não consegue logar.

### AC-CAD-008 — Soft-delete via inativação, nunca DELETE

- **Prioridade:** must
- **Quando** admin inativa `Curso`
- **Então** `ativo=false` persistido; registro **não** deletado.
- **E** GET lista não inclui por default, mas `?filter[ativo]=all` inclui.
- **Critério de falha:** `DELETE FROM cursos` executado → viola regra de soft-delete PRD.

### AC-CAD-009 — Cota define regra JSONB estruturada

- **Prioridade:** must
- **Quando** admin cria cota com `regra_jsonb = {base: 4, extra_paga_max: 2}`
- **Então** persistido em coluna JSONB; lido de volta com mesmo schema.
- **E** `CotaCalculator` aceita a estrutura.

### AC-CAD-010 — Listagem paginada usa JSON:API style

- **Prioridade:** must
- **Quando** GET `/api/v1/organizacoes?page[size]=20&page[number]=2`
- **Então** `200` com `{data: [...], meta: {total, per_page, current_page}, links: {first, last, next, prev}}`.

---

## 3. Comercial e Adesão

### AC-COM-001 — Formando inicia adesão em estado `rascunho`

- **Prioridade:** must
- **Dado que** ator é formando sem adesão no evento
- **Quando** POST `/api/v1/eventos/{ulid}/adesoes` com pacote escolhido
- **Então** `201 Created` com adesão em `status=rascunho`.

### AC-COM-002 — Adesão pendente_pagamento grava snapshot comercial

- **Prioridade:** must
- **Quando** formando confirma adesão
- **Então** snapshot JSONB grava `{pacote: {...}, produtos: [...], termo_hash, preco_centavos, politica_cancelamento}`.
- **E** mudança posterior no pacote **não** altera o snapshot.
- **Critério de falha:** snapshot referencia FK dinâmica e muda junto com catálogo.

### AC-COM-003 — Adesão ativa só após pagamento confirmado

- **Prioridade:** must
- **Dado que** adesão em `pendente_pagamento`
- **Quando** webhook de pagamento confirma parcela de entrada
- **Então** `status=ativa`, gera `AdesaoAtivada` event.

### AC-COM-004 — Uma adesão ativa por formando+evento (unique parcial)

- **Prioridade:** must
- **Dado que** formando tem adesão `ativa` em evento X
- **Quando** tenta criar outra no mesmo evento
- **Então** `409 Conflict` com `code: "adesao_existente"`.
- **Critério de falha:** banco aceita duas — viola §Apêndice C unique parcial.

### AC-COM-005 — Histórico múltiplo por formando é permitido

- **Prioridade:** should
- **Dado que** formando teve adesão `cancelada` no evento Y (passado)
- **Quando** cria nova adesão no evento Z (presente)
- **Então** `201` — unique parcial só bloqueia estados ativo/pendente.

### AC-COM-006 — Adesão cancelada libera cota

- **Prioridade:** must
- **Quando** adesão passa para `cancelada`
- **Então** `CotaCalculator` recalcula e reduz convites emitidos desta adesão.

### AC-COM-007 — Adesão inadimplente bloqueia novas compras de extras

- **Prioridade:** should
- **Dado que** formando com 2+ parcelas em atraso
- **Quando** tenta POST `/api/v1/pedidos-extras`
- **Então** `422` com `code: "inadimplente"`.

### AC-COM-008 — CotaCalculator — formando ativo sem extras

- **Prioridade:** must
- **Dado que** regra `{base: 4}`, formando `ativo`, nenhum extra pago
- **Quando** chama `CotaCalculator::calcular($formando)`
- **Então** retorna `{total: 4, emitidos: 0, disponivel: 4}`.

### AC-COM-009 — CotaCalculator — com extras pagos

- **Prioridade:** must
- **Dado que** regra `{base: 4, extra_paga_max: 2}`, 1 extra pago
- **Quando** calcula
- **Então** `{total: 5, emitidos: 0, disponivel: 5}`.

### AC-COM-010 — CotaCalculator — com emissões

- **Prioridade:** must
- **Dado que** total=4, emitidos=3
- **Quando** calcula
- **Então** `{total:4, emitidos:3, disponivel:1}`.

### AC-COM-011 — CotaCalculator — borda: cota zerada

- **Prioridade:** must
- **Quando** emitidos = total
- **Então** `disponivel=0`; tentativa de emitir novo convite falha com `cota_esgotada`.

### AC-COM-012 — Parcelamento calculado em centavos

- **Prioridade:** must
- **Dado que** `valor_total_centavos=250000` (R$2.500,00), 5 parcelas
- **Quando** gera parcelamento
- **Então** cada parcela tem `valor_centavos=50000`; soma = 250000 (nunca float).

### AC-COM-013 — Parcela vencida atualiza status automaticamente

- **Prioridade:** should
- **Dado que** parcela com `vencimento < hoje` e `status=pendente`
- **Quando** job scheduled `MarcarParcelasVencidasJob` roda
- **Então** status vira `vencido` e evento `ParcelaVencida` disparado.

### AC-COM-014 — Cancelamento com política de retenção

- **Prioridade:** should
- **Dado que** snapshot grava `politica_cancelamento: {retencao: 20}`
- **Quando** formando cancela
- **Então** 20% do valor pago fica retido; diferença agendada para estorno.

### AC-COM-015 — Transição de estado inválida é 409

- **Prioridade:** must
- **Dado que** adesão em `cancelada`
- **Quando** admin tenta `PUT ativa`
- **Então** `409 Conflict` com `code: "transicao_invalida", from: "cancelada", to: "ativa"`.

### AC-COM-016 — Audit log grava toda transição de adesão

- **Prioridade:** must
- **Quando** adesão passa rascunho → pendente_pagamento → ativa
- **Então** 2 registros em `activity_log` com `before/after/ator/timestamp`.

---

## 4. Convites

### AC-CNV-001 — Emissão individual gera token criptográfico

- **Prioridade:** must
- **Dado que** formando com `disponivel ≥ 1`
- **Quando** POST `/api/v1/convites` com dados do convidado
- **Então** `201` com `{id: <ulid>, codigo, url_convite}`; token **bruto** aparece **apenas** na URL.
- **E** banco grava `token_hash = sha256(token_bruto)`.
- **Critério de falha:** banco grava token bruto, ou token usa `Str::random` (baixa entropia).

### AC-CNV-002 — Emissão em lote de 500 convites ≤ 60s

- **Prioridade:** must
- **Dado que** formando com cota 500
- **Quando** POST `/api/v1/convites/lote` com 500 convidados
- **Então** job dispatch em `chunk(500)`; 500 registros persistidos em ≤ 60s.
- **E** cada convite tem token único.

### AC-CNV-003 — Cota esgotada bloqueia emissão

- **Prioridade:** must
- **Dado que** `disponivel=0`
- **Quando** POST novo convite
- **Então** `422` com `code: "cota_esgotada"`.

### AC-CNV-004 — Cancelamento revoga token

- **Prioridade:** must
- **Quando** admin cancela convite
- **Então** `status=cancelado`; GET `/api/v1/convite/{token}` retorna `404` (nunca `410` — não vazar existência).

### AC-CNV-005 — Transferência preserva RSVP histórico

- **Prioridade:** should
- **Dado que** convite com RSVP `confirmado` por João
- **Quando** admin transfere para Maria
- **Então** convite mantém histórico de João em `rsvp_historico`; RSVP atual zerado; Maria recebe novo link.

### AC-CNV-006 — Convite extra herda pedido de origem

- **Prioridade:** must
- **Dado que** `PedidoExtra` pago gera 3 convites
- **Quando** convites emitidos derivados
- **Então** cada um tem `pedido_extra_id` apontando para o pedido.

### AC-CNV-007 — Convite extra após estorno: invalida não utilizado

- **Prioridade:** must
- **Dado que** pedido pago gerou 3 convites, 1 com RSVP confirmado
- **Quando** pedido é estornado
- **Então** 2 não utilizados viram `cancelado`; o confirmado exige aprovação manual.

### AC-CNV-008 — Pagamento confirmado gera convite derivado ≤ 30s

- **Prioridade:** must
- **Dado que** webhook aplica `pedido_extra.pago`
- **Quando** job `EmitirConvitesExtrasJob` roda
- **Então** convites emitidos em ≤ 30s.

### AC-CNV-009 — Endpoint público do token não expõe dados internos

- **Prioridade:** must
- **Quando** GET `/api/v1/convite/{token}` com token válido
- **Então** `200` com apenas `{evento: {nome, data}, formando: {nome}, convidado: {nome}, status}`.
- **E** nunca retorna `id`, `ulid`, `token_hash`, `cpf`, `valor_pago`.

### AC-CNV-010 — Token inválido → 404 sem dica

- **Prioridade:** must
- **Quando** GET `/api/v1/convite/abc123` (token inexistente)
- **Então** `404` com mesma mensagem genérica que um token cancelado.
- **Critério de falha:** 410 para cancelado + 404 para inexistente → oráculo para atacante.

---

## 5. RSVP

### AC-RSVP-001 — Convidado confirma presença via token

- **Prioridade:** must
- **Dado que** convite `emitido` com token válido
- **Quando** POST `/api/v1/convite/{token}/rsvp` com `{resposta: "confirmado"}`
- **Então** `200` com `{status: "confirmado"}`; registro em `rsvp_historico` com ator=convite.

### AC-RSVP-002 — RSVP recusado aceita motivo opcional

- **Prioridade:** should
- **Quando** POST com `{resposta: "recusado", motivo: "compromisso prévio"}`
- **Então** `200`; `motivo` gravado em `rsvp_historico.metadata`.

### AC-RSVP-003 — Token expirado retorna 404

- **Prioridade:** must
- **Dado que** convite com `expires_at < now()`
- **Quando** POST rsvp
- **Então** `404` sem vazar existência.

### AC-RSVP-004 — Token revogado retorna 404

- **Prioridade:** must
- **Dado que** convite com `status=cancelado`
- **Quando** POST rsvp
- **Então** `404`.

### AC-RSVP-005 — Token mal-formado retorna 404

- **Prioridade:** must
- **Quando** GET `/api/v1/convite/XYZ` (string curta)
- **Então** `404` — nunca `422`.

### AC-RSVP-006 — Alteração de RSVP antes da janela fechar é permitida

- **Prioridade:** must
- **Dado que** evento com `janela_rsvp_fim > now()`
- **Quando** convidado muda de `confirmado` para `recusado`
- **Então** `200`; novo registro em `rsvp_historico`; antigo **não** é deletado.

### AC-RSVP-007 — Alteração após janela fechar é 409

- **Prioridade:** must
- **Dado que** `janela_rsvp_fim < now()`
- **Quando** tenta mudar
- **Então** `409` com `code: "janela_fechada"`.

### AC-RSVP-008 — RSVP pendente não bloqueia seating se evento permite

- **Prioridade:** should
- **Dado que** evento com `exige_rsvp_para_seating=false`
- **Quando** convidado pendente tenta reservar assento
- **Então** `201`.

### AC-RSVP-009 — RSVP exige confirmação para seating quando configurado

- **Prioridade:** must
- **Dado que** evento com `exige_rsvp_para_seating=true`
- **Quando** convidado pendente tenta reservar assento
- **Então** `403` com `code: "rsvp_pendente"`.

### AC-RSVP-010 — Rate limit `convite` 10/min por IP

- **Prioridade:** must
- **Dado que** 10 RSVP num IP em 60s
- **Quando** 11º request
- **Então** `429`.

---

## 6. Seating

### AC-SEA-001 — Reserva de assento livre gera hold de 5 min

- **Prioridade:** must
- **Dado que** assento livre, formando autenticado, `X-Idempotency-Key: K1`
- **Quando** POST `/api/v1/eventos/{ulid}/mesas/reservas` com `assento_ulid`
- **Então** `201` com `{status: "hold", hold_expires_at: now+5min}`.
- **E** registro em `reservas_assentos` com status=hold.

### AC-SEA-002 — 2 reservas ativas simultâneas no mesmo assento é impossível

- **Prioridade:** must
- **Dado que** assento já tem reserva ativa de outro ator
- **Quando** POST tenta nova
- **Então** `409 Conflict` com `code: "assento_indisponivel"`.

### AC-SEA-003 — 1000 tentativas simultâneas: apenas 1 vence

- **Prioridade:** must
- **Dado que** 1000 atores disparam POST ao mesmo assento via `pcntl_fork`
- **Quando** o teste de concorrência executa
- **Então** exatamente 1 recebe `201`; 999 recebem `409`.
- **E** banco tem exatamente 1 registro de reserva.
- **Critério de falha:** 2+ reservas ativas — bug crítico.

### AC-SEA-004 — Mesma `X-Idempotency-Key` retorna mesma reserva

- **Prioridade:** must
- **Dado que** request 1 criou reserva com key `K-abc`
- **Quando** request 2 envia exatamente o mesmo payload + key `K-abc`
- **Então** `200` ou `201` com o **mesmo** `reserva_ulid` do request 1.
- **E** banco tem 1 reserva.

### AC-SEA-005 — Idempotency-Key com payload diferente retorna 409

- **Prioridade:** must
- **Dado que** key `K-abc` usada com `assento_ulid=A`
- **Quando** request envia `K-abc` com `assento_ulid=B`
- **Então** `409` com `code: "idempotency_key_mismatch"`.

### AC-SEA-006 — Hold expira automaticamente em 5 min

- **Prioridade:** must
- **Dado que** hold criado há 6 min
- **Quando** `ExpirarHoldsJob` roda (a cada 1 min)
- **Então** reserva vira `expirada`; evento `HoldExpirado` disparado; assento livre de novo.

### AC-SEA-007 — Confirmar hold expirado é 410 Gone

- **Prioridade:** must
- **Dado que** hold com `hold_expires_at < now()`
- **Quando** POST `/api/v1/reservas/{ulid}/confirmar`
- **Então** `410 Gone` com `code: "hold_expirado"`.

### AC-SEA-008 — Confirmar hold válido vira `confirmada`

- **Prioridade:** must
- **Dado que** hold ativo
- **Quando** POST confirmar
- **Então** `200`; status=`confirmada`; `hold_expires_at=null`; `confirmado_at=now()`.

### AC-SEA-009 — Troca de assento libera antigo e reserva novo em transação

- **Prioridade:** must
- **Dado que** formando tem reserva confirmada em A
- **Quando** POST `/api/v1/reservas/{ulid}/trocar` com `destino=B`
- **Então** A vira `cancelada`; B vira `confirmada`; tudo em uma transação.
- **E** se B indisponível → rollback, A volta a `confirmada`.

### AC-SEA-010 — Troca bilateral (A↔B): sem deadlock

- **Prioridade:** must
- **Dado que** formando X em A, formando Y em B, disparam troca simultânea
- **Quando** ambos executam
- **Então** ordem fixa `assento_id` ASC evita deadlock; um vence, outro reexecuta.

### AC-SEA-011 — Rate limit seating 5/min por usuário

- **Prioridade:** must
- **Dado que** usuário fez 5 reservas em 60s
- **Quando** 6ª
- **Então** `429`.

### AC-SEA-012 — Admin bloqueia mesa

- **Prioridade:** must
- **Quando** admin POST `/api/v1/mesas/{ulid}/bloquear`
- **Então** todos os assentos da mesa viram `bloqueada`; reservas ativas exigem decisão admin (override).

### AC-SEA-013 — Bloquear mesa com reservas ativas exige motivo

- **Prioridade:** should
- **Dado que** mesa com 3 reservas confirmadas
- **Quando** admin bloqueia sem motivo
- **Então** `422` com `field: motivo`.
- **E** com motivo → `200`; reservas viram `cancelada` com motivo gravado em audit.

### AC-SEA-014 — Mapa cacheia delta, invalida em evento

- **Prioridade:** should
- **Dado que** GET `/api/v1/eventos/{ulid}/mapa` retorna cache
- **Quando** `AssentoReservado` é disparado
- **Então** cache invalidado; próxima GET recomputa.

### AC-SEA-015 — Reserva sem `X-Idempotency-Key` retorna 400

- **Prioridade:** must
- **Quando** POST reserva sem header
- **Então** `400` com `code: "missing_idempotency_key"` (middleware `IdempotencyKeyGuard`).

### AC-SEA-016 — Assento inexistente é 404

- **Prioridade:** must
- **Quando** POST com `assento_ulid` inexistente
- **Então** `404`.

### AC-SEA-017 — Assento em mesa bloqueada é 409

- **Prioridade:** must
- **Quando** POST em assento cuja mesa está `bloqueada`
- **Então** `409 code: "mesa_bloqueada"`.

### AC-SEA-018 — Comissão não pode editar mapa sem permission

- **Prioridade:** must
- **Dado que** `ComissaoUser` sem `mapa.edit`
- **Quando** tenta `PUT /api/v1/mesas/{ulid}`
- **Então** `403`.

### AC-SEA-019 — Cancelamento de convite libera reserva associada

- **Prioridade:** must
- **Dado que** convite com reserva confirmada
- **Quando** admin cancela convite
- **Então** reserva vira `cancelada`; audit log grava.

### AC-SEA-020 — Delta do mapa propaga via WS/push em ≤ 3s

- **Prioridade:** should
- **Dado que** reserva criada
- **Quando** evento `AssentoReservado` processado
- **Então** canal Reverb/WebSocket publica delta; clientes recebem em ≤ 3s.

---

## 7. Extras

### AC-EXT-001 — Formando cria pedido extra em `rascunho`

- **Prioridade:** must
- **Quando** POST `/api/v1/pedidos-extras` com `{itens: [...]}`
- **Então** `201` com `status=rascunho`.

### AC-EXT-002 — Pedido requer aprovação quando catálogo marca `exige_aprovacao=true`

- **Prioridade:** must
- **Dado que** produto extra `X` com `exige_aprovacao=true`
- **Quando** pedido submetido
- **Então** `status=pendente_aprovacao`; admin notificado.

### AC-EXT-003 — Aprovação gera cobrança

- **Prioridade:** must
- **Quando** admin aprova
- **Então** `status=aguardando_pagamento`; URL de pagamento gerada.

### AC-EXT-004 — Rejeição exige motivo

- **Prioridade:** must
- **Quando** admin rejeita sem motivo
- **Então** `422`.

### AC-EXT-005 — Estoque por evento esgotado bloqueia pedido

- **Prioridade:** must
- **Dado que** produto com estoque 10, já vendidos 10
- **Quando** POST novo
- **Então** `422 code: "estoque_esgotado"`.

### AC-EXT-006 — Estoque por formando esgotado bloqueia

- **Prioridade:** must
- **Dado que** produto com `max_por_formando=2`, formando já comprou 2
- **Quando** tenta 3º
- **Então** `422 code: "max_por_formando"`.

### AC-EXT-007 — Pedido pago gera convites derivados

- **Prioridade:** must
- **Quando** webhook confirma pagamento
- **Então** `EmitirConvitesExtrasJob` dispatched; convites criados em ≤ 30s.

### AC-EXT-008 — Estorno invalida convites não utilizados

- **Prioridade:** must
- **Dado que** pedido pago gerou 3 convites, 0 com RSVP
- **Quando** admin estorna
- **Então** 3 convites viram `cancelado`.

### AC-EXT-009 — Pagamento manual exige justificativa

- **Prioridade:** must
- **Quando** admin faz baixa manual sem `justificativa`
- **Então** `422`.

### AC-EXT-010 — Snapshot de pedido imutável após pago

- **Prioridade:** must
- **Dado que** pedido `pago`
- **Quando** admin tenta editar item
- **Então** `409 code: "snapshot_imutavel"`.

---

## 8. Pagamentos e Webhook

### AC-PAG-001 — Webhook Itaú com HMAC válida aceita

- **Prioridade:** must
- **Dado que** payload com `X-Signature=hmac_sha256(body, secret)`
- **Quando** POST `/webhooks/pagamentos/itau`
- **Então** `202 Accepted` com `{status: "accepted"}`.
- **E** registro em `webhook_eventos` com `status=recebido`.

### AC-PAG-002 — HMAC inválida retorna 401 sem side-effect

- **Prioridade:** must
- **Dado que** `X-Signature=invalida`
- **Quando** POST
- **Então** `401`; `webhook_eventos` **sem** novo registro.
- **Critério de falha:** aceita com assinatura errada → bypass total.

### AC-PAG-003 — Mesmo `gateway_reference` processado 10× tem efeito 1×

- **Prioridade:** must
- **Dado que** webhook com `evento.id=gw-123`
- **Quando** é reenviado 10× em sequência
- **Então** `firstOrCreate` cria 1 registro; pedido transitiona para `pago` 1× apenas.
- **E** `webhook_eventos.count() == 1`.

### AC-PAG-004 — Replay > 24h é descartado

- **Prioridade:** must
- **Dado que** webhook com `recebido_at < now() - 24h` (simulado via data do payload)
- **Quando** POST
- **Então** `202 ignorado` com `status=replay_descartado`.

### AC-PAG-005 — Payload malformado é 422

- **Prioridade:** must
- **Quando** POST com body vazio ou JSON inválido
- **Então** `422` ou `400`; nunca 500.

### AC-PAG-006 — Segundo processamento retorna `already_processed`

- **Prioridade:** must
- **Dado que** evento `gw-456` já `processado`
- **Quando** POST mesmo payload
- **Então** `200` com `{status: "already_processed"}`.

### AC-PAG-007 — Pagamento `pago` atualiza parcela e adesão

- **Prioridade:** must
- **Quando** webhook confirma entrada
- **Então** `Parcela.status=pago`, `Adesao.status=ativa` (se era pendente).

### AC-PAG-008 — Pagamento `falhou` mantém adesão pendente

- **Prioridade:** must
- **Quando** webhook com `tipo=pagamento.falhou`
- **Então** `Parcela.status=vencido/pendente`; adesão não transiciona.

### AC-PAG-009 — Estorno dispara evento `PagamentoEstornado`

- **Prioridade:** must
- **Quando** webhook com `tipo=pagamento.estornado`
- **Então** evento domínio disparado; listeners atualizam pedido extra e convites.

### AC-PAG-010 — Reconciliação detecta divergência

- **Prioridade:** should
- **Dado que** pagamento `pendente` há > 60 min
- **Quando** `ReconciliarPagamentosJob` roda
- **Então** consulta gateway; se divergente, dispara como fosse webhook (não aplica efeito direto).

### AC-PAG-011 — Rate limit webhook 600/min por IP

- **Prioridade:** should
- **Dado que** IP dispara 600 requests em 60s
- **Quando** 601º
- **Então** `429`.

### AC-PAG-012 — Idempotência global via `(provider, gateway_reference)` unique

- **Prioridade:** must
- **Quando** tenta inserir duplicata
- **Então** constraint viola → captured; retorna `already_processed`.

### AC-PAG-013 — Dispatch do job pós-commit (afterCommit)

- **Prioridade:** must
- **Dado que** job `ProcessarWebhookPagamentoJob` despachado dentro da transação
- **Quando** transação roll-back por qualquer motivo
- **Então** job **nunca** executa.
- **Critério de falha:** job roda antes do commit → aplica efeito num evento que não existe.

### AC-PAG-014 — Job falho vai pra DLQ após N tentativas

- **Prioridade:** should
- **Dado que** job com `tries=3` falhando
- **Quando** exceder 3
- **Então** job vai pra `failed_jobs`; alerta dispara.

### AC-PAG-015 — Token/segredo de gateway nunca aparece em log

- **Prioridade:** must
- **Dado que** webhook chega
- **Quando** CorrelationProcessor serializa
- **Então** body log mascara `secret`, `token`; CPF com `***.***`.

---

## 9. Enquetes

### AC-ENQ-001 — Criar enquete com regra de elegibilidade JSONB

- **Prioridade:** must
- **Quando** admin POST `{tipo: "simples", regra_elegibilidade: {base: "rsvp_confirmado"}}`
- **Então** `201`; regra persistida.

### AC-ENQ-002 — Elegibilidade — formando com adesão ativa

- **Prioridade:** must
- **Dado que** enquete com `regra: {base: "formando_adesao_ativa"}`
- **Quando** formando `ativa` tenta votar
- **Então** `200 allowed`; `rascunho` → `403`.

### AC-ENQ-003 — Elegibilidade — convidado com RSVP confirmado

- **Prioridade:** must
- **Dado que** regra `base: "rsvp_confirmado"`
- **Quando** convidado pendente tenta votar
- **Então** `403`.

### AC-ENQ-004 — Voto único quando `permite_edicao=false`

- **Prioridade:** must
- **Dado que** enquete fechou edição
- **Quando** segundo voto do mesmo ator
- **Então** `409 code: "voto_ja_registrado"`.

### AC-ENQ-005 — Voto editável atualiza via upsert

- **Prioridade:** must
- **Dado que** `permite_edicao=true`
- **Quando** ator vota de novo
- **Então** `200`; voto sobrescrito; `voto_original` preservado no histórico.

### AC-ENQ-006 — Unique (enquete_id, ator_tipo, ator_id)

- **Prioridade:** must
- **Quando** tenta inserir duplicata (sem edição)
- **Então** constraint viola → 409.

### AC-ENQ-007 — Voto fora da janela é rejeitado

- **Prioridade:** must
- **Dado que** `janela_fim < now()`
- **Quando** POST voto
- **Então** `409 code: "janela_fechada"`.

### AC-ENQ-008 — Rate limit voto 3/min por usuário

- **Prioridade:** must
- **Quando** 4º voto
- **Então** `429`.

### AC-ENQ-009 — Resultado secreto esconde ator

- **Prioridade:** must
- **Dado que** enquete com `secreto=true`
- **Quando** admin GET resultados
- **Então** payload com contagens mas sem `ator_id`/`ator_tipo`.

### AC-ENQ-010 — Ranqueamento futuro — tipo inválido é 422

- **Prioridade:** could
- **Dado que** MVP só suporta `simples` e `multipla`
- **Quando** POST com `tipo=ranqueamento`
- **Então** `422`.

---

## 10. Comunicação

### AC-NOT-001 — Convite emitido dispara `EnviarConviteEmailJob`

- **Prioridade:** must
- **Quando** action `EmitirConviteAction` executa
- **Então** `Queue::assertPushed(EnviarConviteEmailJob::class)` com convite_id certo.

### AC-NOT-002 — Reenvio de notificação é auditado

- **Prioridade:** should
- **Quando** admin reenvia email
- **Então** `activity_log` grava `ator, convite_id, timestamp, canal`.

### AC-NOT-003 — E-mail nunca contém token em claro no subject

- **Prioridade:** must
- **Quando** template é renderizado
- **Então** token bruto aparece apenas no link; nunca em subject, preview, footer.

### AC-NOT-004 — Notificação não substitui estado de domínio

- **Prioridade:** must
- **Dado que** envio de email falha
- **Quando** job retry esgota
- **Então** `Convite.status` **permanece** `emitido`; apenas `activity_log` grava falha.
- **Critério de falha:** email falho reverte status do convite — viola REGRAS §12.2.1.

### AC-NOT-005 — Canal varia por perfil (futuro SMS/WhatsApp)

- **Prioridade:** could
- **Dado que** `PortalUser.canal_pref=sms`
- **Quando** notificação dispara
- **Então** adapter escolhe canal SMS (quando implementado); hoje fallback pra email.
