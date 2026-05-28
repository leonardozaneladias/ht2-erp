---
title: Glossário de Domínio — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Glossário de Domínio — Portal ArtFinal v2

Lista canônica de termos em PT-BR usados em código, API, documentação e comunicação com produto. Divergências no código devem ser reportadas como bug.

> Fontes. `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md`, `docs/data/data-model.md`, `docs/data/er-diagram.md`.

## Termos de domínio de negócio

### Adesão

Contrato ativo entre um **formando** e um **evento**, mediado por um **pacote** comercial. Nasce em `rascunho`, transiciona para `pendente_pagamento`, `ativa`, `inadimplente`, `concluida` ou `cancelada`. Possui **unique parcial** no banco garantindo no máximo uma adesão "viva" por formando/evento. Congela preço/termos em `snapshot_comercial` no momento da confirmação.

### Ator

Entidade que executa uma ação com efeito de domínio (reserva, voto, emissão de convite). Tem dois atributos obrigatórios: `ator_tipo` (`formando | comissao | admin | operacao`) e `ator_id` (id do usuário ou sistema). Nunca confundir com `causer` do ActivityLog (`ator` é semântica de domínio; `causer` é técnica).

### Append-only

Propriedade de tabela cujos registros, uma vez escritos, não são atualizados (salvo status técnico controlado) nem deletados. Ex.: `activity_log`, `webhook_eventos`, `rsvp_historico`, `reservas_historico`, `notificacao_entregas`. Garante auditoria.

### Assento

Unidade física atômica de ocupação dentro de uma **mesa**. Identificado por `numero` dentro da mesa. Tem `status` global (`livre | bloqueado`) e pode estar associado a uma `reserva_assento` ativa (`hold | confirmada`).

### Bounded Context

Particionamento do domínio em grupos coesos (Identidade, Cadastro, Comercial, Convites, RSVP, Seating, Extras, Engajamento, Comunicação). Refletido em diretórios (`app/Actions/<Contexto>`, `app/Models/<Contexto>`), prefixos de rota e arquivos de migration. Contextos não importam classes uns dos outros sem intermédio de Event/Action pública.

### Comissão

Subtipo de `portal_user` representando membros da comissão de formatura. Autenticam via Sanctum. Recebem role Spatie `comissao` no guard `sanctum`. Nunca herdam `admin.*`. Scopeadas por evento.

### Convite

Instrumento de acesso a um evento emitido por (ou para) um formando. Possui `codigo` curto (legível), `token_hash` (sha256 de um token de 256 bits), `tipo` (`nominal | transferivel | cortesia | staff | extra`) e `status` (`rascunho → emitido → enviado → visualizado → confirmado | recusado | cancelado | inutilizado`). Pode originar `reserva_assento` e, quando `is_extra = true`, deriva de `pedido_extra` pago.

### Convite derivado (ou "convite extra")

Convite cujo `pedido_extra_id` não é nulo. Emitido automaticamente quando `pedido_extra.status = 'pago'`, via `EmitirLoteConvitesAction` disparada por `ConfirmarPagamentoExtraAction`.

### Correlation ID

Identificador `CHAR(26)` propagado entre HTTP ↔ Action ↔ Job ↔ Listener ↔ Webhook. Permite reconstruir o ciclo de vida de uma operação em logs/relatórios. Armazenado nas tabelas transacionais chave (ver `er-diagram.md`). Não confundir com `X-Request-Id` (que vale somente por request HTTP; `correlation_id` persiste).

### Cota

Regra que define quantos convites de cada **tipo** cada formando pode emitir em um evento. Persistida em `cotas_regras`. Exemplo: `{ "tipo": "base", "qtd_por_formando": 4, "permite_transferencia": false }`. O `CotaCalculator` computa saldo em tempo real.

### Enquete

Votação associada a um evento (`unica | multipla | ranking`) com elegibilidade declarativa (`regra_elegibilidade JSONB`). Transiciona `rascunho → aberta → encerrada → arquivada`. Cada voto referencia `enquete_id`, `opcao_id`, `ator_tipo`, `ator_id`.

### Evento

Entidade raiz do domínio operacional. Representa uma formatura real (colação, baile). Possui janelas temporais de RSVP (`abre_rsvp_at`, `fecha_rsvp_at`) e seating (`abre_mesas_at`, `fecha_mesas_at`), `timezone`, `slug` público e `config_json` para overrides operacionais.

### Extra (produto extra / pedido extra)

**Produto extra:** item comercializado fora do pacote base, específico por evento (convite avulso, jantar, transfer). Tem controle de `estoque_tipo` (`ilimitado | finito`).
**Pedido extra:** conjunto de itens selecionados por um formando, com `status` (`rascunho → aguardando_pagamento → pago | cancelado | estornado`). Emite convites derivados quando pago.

### Formando

Entidade que liga um `portal_user` a uma `turma`. Um `portal_user` pode ter múltiplos `formando` (ex.: mesma pessoa em turmas diferentes). Adesões, convites e reservas referenciam `formando_id`, **não** `portal_user_id`.

### Gateway

Provedor externo de cobrança (Itaú, mock/stub). Acessado via `PaymentGatewayContract`. Driver selecionado em runtime por `config('gateway.driver')`.

### Gateway Reference

ID único que o provedor atribui a uma cobrança. Armazenado em `pagamentos.gateway_reference` e, para webhooks, também em `webhook_eventos.gateway_reference`. A combinação `(provider, gateway_reference)` é **UNIQUE** em `webhook_eventos` — base da idempotência dura.

### Guard

Na terminologia Laravel, canal de autenticação separado. Sistema usa quatro: `admin` (cookie/session backoffice), `sanctum` (SPA + mobile, provider `portal_users`), `convite` (custom, resolve por token mágico), `web` (legado, removido em F3).

### HATEOAS

Princípio REST segundo o qual a resposta inclui os links de ações possíveis para o recurso. Adotado de forma **minimalista**: toda Resource retorna chave `links.self`; Resources de state-machine acrescentam ações condicionais (`confirmar`, `cancelar`, etc.) com `null` quando indisponível. Ver `api/api-conventions.md` §HATEOAS.

### Hold

Estado temporário (TTL 5min por default) de uma `reserva_assento` criada mas ainda não confirmada. Conta contra a disponibilidade do assento. Expira automaticamente via `ExpirarHoldsJob` scheduled everyMinute. Após expirar, `status` transiciona para `expirada`.

### Idempotency Key

Header `X-Idempotency-Key` (máx 80 chars) enviado pelo cliente em POSTs sensíveis (reservas, pedidos extras, pagamentos, lote de convites). Middleware `IdempotencyKeyGuard` faz a primeira camada em cache (24h); a Action verifica na segunda camada via coluna `idempotency_key UNIQUE` na tabela de domínio. Se a mesma key chegar com payload diferente → 409.

### Instituição

Entidade ligada a uma `organizacao` (por exemplo: Faculdade X da Universidade Y). Agrega `cursos` e `turmas`.

### Janela (de RSVP, de seating)

Intervalo temporal durante o qual uma ação é permitida. Controladas por `eventos.abre_rsvp_at`/`fecha_rsvp_at` e `eventos.abre_mesas_at`/`fecha_mesas_at`. Policies leem esses campos antes de autorizar reserva/voto/rsvp.

### Lote (de convites)

Agrupamento lógico de convites emitidos juntos. Facilita rollback e relatório. Relacionamento 1-N com `convites`. Emissão massiva passa por `EmitirLoteConvitesAction` (síncrona para ≤100) ou `EmitirLoteConvitesJob` (assíncrona).

### Mesa

Unidade de agrupamento de **assentos** dentro de um **setor** de um **mapa**. Tem `numero` único no evento e `capacidade`.

### Parcela

Subdivisão temporal do valor de uma `adesao`. `UNIQUE (adesao_id, numero)`. Status próprio (`pendente | paga | vencida | cancelada`). Recebe múltiplos `pagamentos` (tentativas).

### Pedido extra

Ver **Extra**.

### Portal User

Conta de acesso do lado "formando" (inclui comissão). Autenticada via Sanctum. Distinto de `admin_user`. Pode ter múltiplos `formando` vinculados.

### RSVP

Resposta do convidado a um convite: "vou" / "não vou" / edita resposta. Registro histórico em `rsvp_historico` (append-only). Origem do evento (`link_magico | portal | admin | sistema`) persistida.

### Setor

Subdivisão visual/lógica de um `mapa_mesa` (ex.: "Setor A", "VIP", "Pista"). Agrupa mesas.

### Snapshot Comercial

Fotografia JSONB dos dados comerciais (preço, desconto, termo, composição do pacote) gravada em `adesoes.snapshot_comercial` no momento da confirmação. Imutável. Nunca consultado via `WHERE`. Base da garantia de que mudança posterior em `produtos`/`pacotes` não afeta adesões existentes.

### Snapshot Regra

Análogo ao snapshot comercial, mas para regras de cota/política em `convites.snapshot_regra`. Congela a regra vigente na emissão.

### Token mágico (de convite)

Token criptográfico (64 chars hex, 256 bits de entropia) gerado com `bin2hex(random_bytes(32))` e enviado ao convidado via e-mail. Apenas o `sha256(token)` é persistido em `convites.token_hash`. O bruto nunca aparece em log, response ou URL de erro.

### Turma

Entidade que reúne formandos do mesmo ciclo de formação. Liga-se a `curso` e `instituicao`. Participa de `eventos` via `turma_evento` (N:N).

### ULID

Identificador `CHAR(26)` (lexicograficamente ordenável, 128 bits). Utilizado como identificador **público** em todas as entidades expostas. IDs numéricos (`BIGSERIAL`) jamais aparecem em URL, token ou resposta da API. Gerado via `App\Support\Ulid::generate()` e atribuído automaticamente pelo trait `HasUlid`.

## Termos técnicos e de arquitetura

### Action

Classe invocável em `app/Actions/<Contexto>/` com método público `execute(Data $dto): Data|void`. Orquestra uma operação de domínio (ex.: `ReservarAssentoAction`). Nunca conhece HTTP.

### CHECK constraint

Regra de integridade declarativa no banco (PostgreSQL) que valida o conteúdo de uma linha. Adotada para duplicar no banco a lista de valores de cada Enum PHP (defesa em profundidade).

### DTO (Data Transfer Object)

Objeto imutável (`readonly`) para transporte entre camadas. Implementado com `spatie/laravel-data`. Substitui arrays genéricos em retornos de Actions.

### Event (domain event)

Evento de domínio disparado após uma Action completar com sucesso (ex.: `AssentoReservado`, `ConvitEmitido`). Ouvidos por Listeners registrados em `DomainEventServiceProvider`. Via Laravel Events.

### FormRequest

Classe em `app/Http/Api/V1/Requests/` responsável por validação + autorização de um endpoint. Nunca validar no Controller.

### Guard

Ver termo acima em "Termos de domínio".

### HATEOAS

Ver termo acima em "Termos de domínio".

### Idempotência

Propriedade de uma operação tal que executá-la N vezes produz o mesmo estado final que executá-la 1 vez. Obrigatória em: reservas de assento, pedidos extras, pagamentos, webhooks, emissão em lote.

### Idempotency Key

Ver termo acima em "Termos de domínio".

### Job (Horizon)

Unidade de trabalho assíncrono em `app/Jobs/`. Executa em fila (`default | notifications | webhooks | exports | critical-seating`) monitorada pelo Horizon. Deve ser idempotente.

### Listener

Reator em `app/Listeners/` a um Event. Orquestra jobs; não contém regra. Delega para Actions quando regra for necessária.

### Magic Link / Magic Token

Ver "Token mágico".

### Middleware

Filtro HTTP em `app/Http/Middleware/`. Middlewares do sistema: `AttachRequestId`, `IdempotencyKeyGuard`, `ResolveConviteToken`, `RateLimitByActor`, `EnsureSanctumAbility`.

### MVP

Produto mínimo viável. Marco executivo ao final de F5; marco comercial ao final de F6.

### Observer (Eloquent)

Classe em `app/Observers/` que escuta eventos de ciclo de vida de um Model (creating, updated, deleted). Usado para auditoria automática e invalidação de cache.

### Policy

Classe em `app/Policies/` que responde "usuário X pode fazer Y sobre Z?". Invocada via `$user->can('reservar', $evento)` ou `$this->authorize('confirmar', $reserva)`.

### Role / Permission (Spatie)

Papel (role) e permissão granular (permission) atribuídos a um user via `spatie/laravel-permission`. Sistema usa guards `admin` e `sanctum`. Comissão nunca recebe permissões `admin.*`.

### Service

Classe em `app/Services/` que encapsula integração técnica (gateway, storage, cálculo de cota, push). Stateless quando possível. Acessada via injeção de dependência.

### State machine

Máquina de estados explícita em um campo `status`. Transições autorizadas via Actions ou métodos do Model. Ex.: `Adesao`, `Convite`, `ReservaAssento`, `PedidoExtra`, `Enquete`, `Pagamento`. Cada transição inválida lança `InvariantViolationException` (HTTP 409).

### Stub (gateway)

Driver fake de `PaymentGatewayContract` para desenvolvimento/testes. Responde payloads determinísticos sem chamada externa. Selecionado quando `config('gateway.driver') = 'stub'`.

### Throwable → HTTP code

Mapa canônico (envelope de erro §2.11) que converte exceção PHP em response padronizada da API. Ver `api/error-envelope.md`.

### ULID

Ver "ULID" acima.

### Unique parcial (partial unique index)

Índice único com cláusula `WHERE` no PostgreSQL. Crítico em: `adesoes_ativa_por_formando_evento`, `reservas_assentos_ativa_por_assento`, `portal_users_cpf_unique`. Permite múltiplos registros "cancelados" sem ferir unicidade do "ativo".

### UPSERT

Insert-or-update atômico. Usado em votos quando `enquete.permite_edicao = true` e em `webhook_eventos` via `firstOrCreate`.

### Webhook

Notificação assíncrona recebida de um provedor externo. Sistema recebe em `POST /webhooks/pagamentos/{provider}` com HMAC. Persiste em `webhook_eventos` via `firstOrCreate` (idempotência) e dispara job para processamento.

### X-Request-Id

Header HTTP adicionado automaticamente pelo middleware `AttachRequestId`. Valor ULID. Permite correlacionar uma request específica aos logs. Não confundir com `correlation_id` (que persiste entre requests).

### X-Correlation-Id

Header HTTP que propaga o `correlation_id` de domínio entre clientes, webhooks e jobs. Quando ausente, é gerado pelo primeiro contato e propagado.

## Abreviações e siglas

| Sigla   | Significado                                                                |
| ------- | -------------------------------------------------------------------------- |
| ACL     | Access Control List (via Spatie Permission)                                |
| ADR     | Architecture Decision Record                                               |
| BC      | Bounded Context                                                            |
| CI      | Continuous Integration                                                     |
| CSRF    | Cross-Site Request Forgery                                                 |
| DTO     | Data Transfer Object                                                       |
| FK      | Foreign Key                                                                |
| HMAC    | Hash-based Message Authentication Code                                     |
| HATEOAS | Hypermedia as the Engine of Application State                              |
| JWT     | JSON Web Token (**não usado no MVP** — sistema usa Sanctum cookies/bearer) |
| LGPD    | Lei Geral de Proteção de Dados                                             |
| MVP     | Minimum Viable Product                                                     |
| N+1     | Anti-pattern de queries Eloquent                                           |
| PK      | Primary Key                                                                |
| REST    | Representational State Transfer                                            |
| RBAC    | Role-Based Access Control                                                  |
| RSVP    | Répondez s'il vous plaît (confirmação de presença)                         |
| SLA     | Service Level Agreement                                                    |
| SPA     | Single Page Application                                                    |
| TDD     | Test-Driven Development                                                    |
| TTL     | Time To Live                                                               |
| TZ      | Timezone                                                                   |
| ULID    | Universally Unique Lexicographically Sortable Identifier                   |
| UX      | User Experience                                                            |

## Convenções de nomenclatura em código

- Código PHP (classes, métodos, variáveis): **inglês** quando termo técnico de framework (`Controller`, `Middleware`, `Job`); **português** quando termo de negócio (`Formando`, `Convite`, `Parcela`).
- Colunas de banco: **snake_case** PT-BR (`valor_total_centavos`, `hold_expires_at`).
- Tabelas: plural em PT-BR (`adesoes`, `convites`, `reservas_assentos`).
- Rotas: kebab-case (`/api/v1/eventos/{ulid}/mesas/reservas`).
- Nomes de rotas: dot notation PT-BR (`api.v1.convite.show`).
- Mensagens ao usuário: PT-BR (`'Assento indisponível. Escolha outro.'`).

## Termos explicitamente proibidos (anti-patterns)

- "Apagar" adesão/convite/reserva → usar "cancelar" (estado, não DELETE).
- "Payment status" em código → usar `StatusPagamento` (Enum PT-BR).
- "Student" em campos/variáveis → usar `formando`.
- "Seat" → usar `assento`.
- "Order" → usar `pedido` ou `adesao` (dependendo do contexto).

> Divergência entre código e este glossário deve ser aberta como issue com label `terminologia` antes de mudar um ou outro. Nomes importam.
