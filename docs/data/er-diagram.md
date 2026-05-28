---
title: Diagramas ER — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Diagramas ER — Portal ArtFinal v2

Este documento apresenta o modelo relacional do MVP organizado em **bounded contexts**. Cada diagrama é independente e foca nas entidades de um contexto, suas cardinalidades e atributos-chave. A visão consolidada no final mostra as dependências entre contextos.

> Fonte de verdade das tabelas: `docs/data/data-model.md`.
> Referência de blocos/fases: `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §4.2 e Apêndice C.

## Convenções dos diagramas

- Apenas atributos chave aparecem (PK, FKs, enums, campos discriminantes). Consulte `data-model.md` para o schema completo.
- `ulid` está presente em todas as entidades transacionais (omitido abaixo para manter legibilidade).
- `snapshot_*` indica coluna `JSONB` imutável pós-confirmação.
- Cardinalidades seguem notação Chen adaptada do Mermaid `erDiagram`:
    - `||--o{` → 1 para muitos (0..N)
    - `||--|{` → 1 para muitos (1..N)
    - `}o--||` → muitos para 1 (opcional)
    - `||--||` → 1 para 1

---

## 1. Contexto — Identidade e Acesso (Bloco A)

Usuários, sessões e tokens. Relação com Spatie Permission resolvida pelo `guard_name`.

```mermaid
erDiagram
    ADMIN_USERS ||--o{ ACTIVITY_LOG : "causou"
    ADMIN_USERS ||--o{ MODEL_HAS_ROLES : "tem_role"
    PORTAL_USERS ||--o{ PERSONAL_ACCESS_TOKENS : "emite"
    PORTAL_USERS ||--o{ FORMANDOS : "possui"
    PORTAL_USERS ||--o{ MODEL_HAS_ROLES : "tem_role"
    ROLES ||--o{ MODEL_HAS_ROLES : "atribuida"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "contem"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "agrupada"
    CONVITES ||--o{ CONVIDADO_ACCESS_TOKENS : "habilita"

    ADMIN_USERS {
        bigserial id PK
        char26 ulid
        varchar email
        varchar nome
        boolean ativo
        timestamptz ultimo_login_at
    }

    PORTAL_USERS {
        bigserial id PK
        char26 ulid
        varchar email
        varchar cpf
        varchar tipo "formando|comissao|responsavel_financeiro"
        boolean ativo
    }

    PERSONAL_ACCESS_TOKENS {
        bigserial id PK
        varchar tokenable_type
        bigint tokenable_id
        varchar name
        varchar token "sha256"
        text abilities "json"
        timestamptz last_used_at
        timestamptz expires_at
    }

    CONVIDADO_ACCESS_TOKENS {
        bigserial id PK
        bigint convite_id FK
        char64 token_hash
        timestamptz expires_at
        timestamptz revogado_at
    }

    ROLES {
        bigserial id PK
        varchar name
        varchar guard_name "admin|sanctum"
    }

    PERMISSIONS {
        bigserial id PK
        varchar name
        varchar guard_name
    }

    MODEL_HAS_ROLES {
        bigint role_id FK
        varchar model_type
        bigint model_id
    }

    ROLE_HAS_PERMISSIONS {
        bigint permission_id FK
        bigint role_id FK
    }

    ACTIVITY_LOG {
        bigserial id PK
        varchar log_name
        varchar description
        varchar subject_type
        bigint subject_id
        varchar causer_type
        bigint causer_id
        jsonb properties
    }
```

**Notas.**

- `portal_users.tipo` é o primeiro filtro de papel. `roles` do Spatie refinam permissões granulares.
- `personal_access_tokens.tokenable` é polimórfico (aponta para `portal_users` ou `admin_users`).
- `convidado_access_tokens` só existe se o convite for exibido em clientes externos (SPA ou mobile autenticando por token mágico); caso contrário o hash vive em `convites.token_hash`.

---

## 2. Contexto — Cadastro Estrutural (Bloco B)

Hierarquia organizacional. `evento` é a entidade raiz de tudo que é operacional.

```mermaid
erDiagram
    ORGANIZACOES ||--o{ INSTITUICOES : "mantem"
    INSTITUICOES ||--o{ CURSOS : "oferece"
    INSTITUICOES ||--o{ TURMAS : "possui"
    CURSOS ||--o{ TURMAS : "origem"
    TURMAS ||--o{ FORMANDOS : "reune"
    PORTAL_USERS ||--o{ FORMANDOS : "encarna"
    EVENTOS ||--o{ TURMA_EVENTO : "participa"
    TURMAS ||--o{ TURMA_EVENTO : "participa"
    EVENTOS ||--o{ ADESOES : "habilita"
    EVENTOS ||--o{ MAPAS_MESAS : "contem"
    EVENTOS ||--o{ LOTES_CONVITES : "emite"
    EVENTOS ||--o{ CONVITES : "agrega"
    EVENTOS ||--o{ PRODUTOS_EXTRAS : "oferece"
    EVENTOS ||--o{ ENQUETES : "publica"

    ORGANIZACOES {
        bigserial id PK
        char26 ulid
        varchar nome
        varchar cnpj
    }

    INSTITUICOES {
        bigserial id PK
        char26 ulid
        bigint organizacao_id FK
        varchar nome
        varchar cidade
        varchar estado
    }

    CURSOS {
        bigserial id PK
        char26 ulid
        bigint instituicao_id FK
        varchar nome
        varchar grau
    }

    TURMAS {
        bigserial id PK
        char26 ulid
        bigint instituicao_id FK
        bigint curso_id FK
        varchar codigo
        smallint ano_ingresso
    }

    EVENTOS {
        bigserial id PK
        char26 ulid
        varchar slug
        varchar nome
        timestamptz data_evento
        varchar timezone
        timestamptz abre_rsvp_at
        timestamptz fecha_rsvp_at
        timestamptz abre_mesas_at
        timestamptz fecha_mesas_at
        varchar status
        jsonb config_json
    }

    TURMA_EVENTO {
        bigint turma_id FK
        bigint evento_id FK
    }

    FORMANDOS {
        bigserial id PK
        char26 ulid
        bigint turma_id FK
        bigint portal_user_id FK
        varchar nome_social
        varchar status
    }
```

**Notas.**

- `eventos.status` é uma máquina de estados (`rascunho` → `publicado` → `encerrado`).
- `eventos.config_json` acumula overrides operacionais (ex.: TTL de hold, política de cancelamento).
- `turma_evento` é N:N — uma turma pode participar de múltiplos eventos (colação + baile), e um evento pode receber várias turmas.
- `formandos` é a ligação "individual × evento/turma"; um `portal_user` pode ter múltiplos formandos (uma pessoa em diferentes turmas ao longo da carreira).

---

## 3. Contexto — Comercial e Adesão (Bloco C)

Produtos, pacotes e adesões. Snapshot `JSONB` congela dados comerciais.

```mermaid
erDiagram
    EVENTOS ||--o{ PACOTES : "comercializa"
    PACOTES ||--o{ PACOTE_PRODUTOS : "compoe"
    PRODUTOS ||--o{ PACOTE_PRODUTOS : "integra"
    FORMANDOS ||--o{ ADESOES : "contrata"
    EVENTOS ||--o{ ADESOES : "referente"
    PACOTES ||--o{ ADESOES : "origem"
    ADESOES ||--o{ ADESAO_PRODUTOS : "inclui"
    PRODUTOS ||--o{ ADESAO_PRODUTOS : "congelado"
    ADESOES ||--|{ PARCELAS : "parcela"
    PARCELAS ||--o{ PAGAMENTOS : "cobranca"

    PACOTES {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        varchar nome
        int preco_centavos
        smallint parcelas_maximo
        boolean ativo
    }

    PRODUTOS {
        bigserial id PK
        char26 ulid
        varchar nome
        varchar categoria
        int preco_centavos
        boolean ativo
    }

    PACOTE_PRODUTOS {
        bigint pacote_id FK
        bigint produto_id FK
        int quantidade
    }

    ADESOES {
        bigserial id PK
        char26 ulid
        bigint formando_id FK
        bigint evento_id FK
        bigint pacote_id FK
        varchar status "rascunho|pendente_pagamento|ativa|cancelada|inadimplente|concluida"
        int valor_total_centavos
        int valor_entrada_centavos
        smallint qtd_parcelas
        jsonb snapshot_comercial
        char64 termo_hash
        timestamptz aceito_em
        timestamptz confirmada_at
    }

    ADESAO_PRODUTOS {
        bigint adesao_id FK
        bigint produto_id FK
        int quantidade
        int preco_unitario_centavos
        jsonb snapshot
    }

    PARCELAS {
        bigserial id PK
        char26 ulid
        bigint adesao_id FK
        smallint numero
        int valor_centavos
        date vencimento
        varchar status "pendente|paga|vencida|cancelada"
    }

    PAGAMENTOS {
        bigserial id PK
        char26 ulid
        bigint parcela_id FK
        varchar provider
        varchar gateway_reference
        varchar status "pendente|autorizado|pago|falhou|estornado"
        int valor_centavos
        timestamptz pago_em
        char26 correlation_id
    }
```

**Notas.**

- `adesoes.snapshot_comercial` contém preço, desconto, termos aceitos e composição do pacote **no momento da confirmação** — nunca consultado por `WHERE`.
- `UNIQUE` parcial em `adesoes (formando_id, evento_id) WHERE status IN ('pendente_pagamento','ativa')` garante adesão ativa única.
- `pagamentos.gateway_reference` é a referência externa do provedor (Itaú, mock); combinada com `provider` forma a unicidade em `webhook_eventos`.

---

## 4. Contexto — Convites e RSVP (Bloco D)

Emissão, entrega e resposta dos convites.

```mermaid
erDiagram
    EVENTOS ||--o{ COTAS_REGRAS : "define"
    EVENTOS ||--o{ LOTES_CONVITES : "agrupa"
    LOTES_CONVITES ||--o{ CONVITES : "integra"
    FORMANDOS ||--o{ CONVITES : "titular"
    PEDIDOS_EXTRAS ||--o{ CONVITES : "origem_extra"
    CONVITES ||--o{ RSVP_HISTORICO : "responde"
    CONVITES ||--o{ RESERVAS_ASSENTOS : "vincula"

    COTAS_REGRAS {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        varchar tipo "base|transferivel|cortesia|staff"
        smallint qtd_por_formando
        boolean permite_transferencia
        jsonb politica
    }

    LOTES_CONVITES {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        smallint lote_numero
        int qtd_total
        varchar status
        timestamptz emitido_at
    }

    CONVITES {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        bigint formando_id FK
        bigint lote_id FK
        bigint pedido_extra_id FK
        varchar codigo "curto, legivel, UNIQUE"
        char64 token_hash "UNIQUE"
        varchar tipo "nominal|transferivel|cortesia|staff|extra"
        varchar status "rascunho|emitido|enviado|visualizado|confirmado|recusado|cancelado|inutilizado"
        boolean is_extra
        varchar convidado_nome
        varchar convidado_email
        varchar convidado_telefone
        timestamptz entregue_at
        timestamptz confirmado_at
        jsonb snapshot_regra
        char26 correlation_id
    }

    RSVP_HISTORICO {
        bigserial id PK
        char26 ulid
        bigint convite_id FK
        varchar status_anterior
        varchar status_novo
        varchar origem "link_magico|portal|admin"
        jsonb payload
        char26 correlation_id
    }
```

**Notas.**

- `convites.token_hash` é `sha256(token_bruto)`; o token bruto nunca é persistido.
- `convites.snapshot_regra` preserva a regra de cota/política vigente na emissão — usada em auditoria e em exibição do convite.
- `rsvp_historico` é append-only: cada mudança de status cria uma linha nova.

---

## 5. Contexto — Seating (Bloco E)

Mapa, mesas, assentos e reservas com controle de concorrência.

```mermaid
erDiagram
    EVENTOS ||--o{ MAPAS_MESAS : "define"
    MAPAS_MESAS ||--o{ SETORES : "segmenta"
    SETORES ||--o{ MESAS : "agrupa"
    MESAS ||--o{ ASSENTOS : "contem"
    ASSENTOS ||--o{ RESERVAS_ASSENTOS : "alvo"
    CONVITES ||--o{ RESERVAS_ASSENTOS : "ocupa"
    FORMANDOS ||--o{ RESERVAS_ASSENTOS : "reservou"
    RESERVAS_ASSENTOS ||--o{ RESERVAS_HISTORICO : "audita"

    MAPAS_MESAS {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        varchar nome
        varchar status
        jsonb layout
    }

    SETORES {
        bigserial id PK
        char26 ulid
        bigint mapa_id FK
        varchar nome
        varchar cor
        smallint ordem
    }

    MESAS {
        bigserial id PK
        char26 ulid
        bigint setor_id FK
        bigint evento_id FK
        varchar numero
        smallint capacidade
    }

    ASSENTOS {
        bigserial id PK
        char26 ulid
        bigint mesa_id FK
        smallint numero
        varchar status "livre|bloqueado"
    }

    RESERVAS_ASSENTOS {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        bigint mesa_id FK
        bigint assento_id FK
        bigint convite_id FK
        bigint formando_id FK
        varchar status "hold|confirmada|cancelada|expirada|bloqueada"
        varchar origem "formando|comissao|admin|operacao"
        varchar idempotency_key
        timestamptz hold_expires_at
        timestamptz confirmado_at
        timestamptz cancelado_at
        char26 correlation_id
    }

    RESERVAS_HISTORICO {
        bigserial id PK
        char26 ulid
        bigint reserva_id FK
        varchar status_anterior
        varchar status_novo
        varchar ator_tipo
        bigint ator_id
        jsonb diff
    }
```

**Notas.**

- `UNIQUE` parcial `reservas_assentos_ativa_por_assento ON (assento_id) WHERE status IN ('hold','confirmada')` impede qualquer cenário de duplicidade.
- `CHECK` `reservas_assentos_hold_consistente` garante coerência entre `status` e campos temporais.
- `reservas_assentos.idempotency_key` é `UNIQUE` — serve como segunda camada da garantia de idempotência (primeira é o middleware `IdempotencyKeyGuard`).

---

## 6. Contexto — Extras e Pagamentos Operacionais (Bloco F)

Catálogo de produtos extras comprados fora do pacote e webhook de gateway.

```mermaid
erDiagram
    EVENTOS ||--o{ PRODUTOS_EXTRAS : "disponibiliza"
    FORMANDOS ||--o{ PEDIDOS_EXTRAS : "solicita"
    EVENTOS ||--o{ PEDIDOS_EXTRAS : "contexto"
    PEDIDOS_EXTRAS ||--|{ PEDIDO_EXTRA_ITENS : "composto_de"
    PRODUTOS_EXTRAS ||--o{ PEDIDO_EXTRA_ITENS : "congelado"
    PEDIDOS_EXTRAS ||--o{ PAGAMENTOS : "cobranca"
    PEDIDOS_EXTRAS ||--o{ CONVITES : "gera_extra"
    WEBHOOK_EVENTOS ||--o{ PAGAMENTOS : "confirma"

    PRODUTOS_EXTRAS {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        varchar nome
        varchar categoria
        int preco_centavos
        varchar estoque_tipo "ilimitado|finito"
        int estoque_qtd
        boolean ativo
    }

    PEDIDOS_EXTRAS {
        bigserial id PK
        char26 ulid
        bigint formando_id FK
        bigint evento_id FK
        varchar status "rascunho|aguardando_pagamento|pago|cancelado|estornado"
        int valor_total_centavos
        varchar idempotency_key
        jsonb snapshot
        char26 correlation_id
    }

    PEDIDO_EXTRA_ITENS {
        bigserial id PK
        bigint pedido_id FK
        bigint produto_extra_id FK
        smallint quantidade
        int preco_unitario_centavos
        jsonb snapshot_produto
    }

    WEBHOOK_EVENTOS {
        bigserial id PK
        varchar provider "itau|mock"
        varchar evento_tipo
        varchar gateway_reference "UNIQUE com provider"
        jsonb payload
        varchar status "recebido|processado|falhou|descartado"
        smallint tentativas
        text ultimo_erro
        timestamptz recebido_at
        timestamptz processado_at
    }
```

**Notas.**

- `pedidos_extras.idempotency_key` é único por formando+evento.
- `webhook_eventos.(provider, gateway_reference)` UNIQUE garante idempotência dura.
- Quando `pedido_extra.status = 'pago'`, `ConfirmarPagamentoExtraAction` dispara `EmitirLoteConvitesAction` internamente — por isso `pedidos_extras ||--o{ convites` (origem_extra).

---

## 7. Contexto — Engajamento (Bloco G)

Enquetes, opções e votos.

```mermaid
erDiagram
    EVENTOS ||--o{ ENQUETES : "promove"
    ENQUETES ||--|{ OPCOES_ENQUETE : "lista"
    ENQUETES ||--o{ VOTOS : "coleta"
    OPCOES_ENQUETE ||--o{ VOTOS : "receptor"
    PORTAL_USERS ||--o{ VOTOS : "vota"

    ENQUETES {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        varchar tipo "unica|multipla|ranking"
        varchar status "rascunho|aberta|encerrada|arquivada"
        timestamptz abre_at
        timestamptz fecha_at
        boolean permite_edicao
        boolean resultado_publico
        jsonb regra_elegibilidade
    }

    OPCOES_ENQUETE {
        bigserial id PK
        char26 ulid
        bigint enquete_id FK
        varchar rotulo
        smallint ordem
        jsonb meta
    }

    VOTOS {
        bigserial id PK
        char26 ulid
        bigint enquete_id FK
        bigint opcao_id FK
        varchar ator_tipo "formando|comissao"
        bigint ator_id
        jsonb payload
        timestamptz registrado_at
    }
```

**Notas.**

- `UNIQUE (enquete_id, ator_tipo, ator_id)` quando `permite_edicao = false`.
- Quando `permite_edicao = true`, o unique é omitido e faz-se `upsert` por (enquete_id, ator_tipo, ator_id, opcao_id).
- `regra_elegibilidade` descreve de forma declarativa quem pode votar (ex.: `"rsvp_confirmado": true, "perfil_min": "formando"`).

---

## 8. Contexto — Comunicação (Bloco H)

Templates, notificações agendadas/enviadas e log de entregas.

```mermaid
erDiagram
    EVENTOS ||--o{ NOTIFICACOES : "originou"
    TEMPLATES_NOTIFICACAO ||--o{ NOTIFICACOES : "renderiza"
    NOTIFICACOES ||--o{ NOTIFICACAO_ENTREGAS : "bounced/delivered"

    TEMPLATES_NOTIFICACAO {
        bigserial id PK
        char26 ulid
        varchar canal "email|push|sms"
        varchar slug
        varchar assunto
        text corpo
        jsonb variaveis_exemplo
        boolean ativo
    }

    NOTIFICACOES {
        bigserial id PK
        char26 ulid
        bigint evento_id FK
        bigint template_id FK
        varchar destinatario_tipo "formando|convidado|comissao|admin"
        bigint destinatario_id
        varchar canal
        varchar status "agendada|enviando|enviada|entregue|falhou"
        jsonb payload
        timestamptz agendada_para
        timestamptz enviada_em
    }

    NOTIFICACAO_ENTREGAS {
        bigserial id PK
        bigint notificacao_id FK
        varchar provider "sendgrid|ses|mailgun|expo"
        varchar provider_id
        varchar status
        jsonb provider_payload
        timestamptz registrado_em
    }
```

**Notas.**

- Notificação é idempotente por `(evento_id, destinatario_tipo, destinatario_id, template_id, chave_dedup)` — chave opcional evitando reenvios.
- `notificacao_entregas` é o log append-only de callbacks do provedor de envio (bounce, open, click).

---

## 9. Visão Consolidada — todos os contextos

Dependências entre contextos. Entidades representativas apenas; nem todos os atributos aparecem.

```mermaid
erDiagram
    ORGANIZACOES ||--o{ INSTITUICOES : "A"
    INSTITUICOES ||--o{ TURMAS : "B"
    TURMAS ||--o{ FORMANDOS : "B"
    EVENTOS ||--o{ TURMA_EVENTO : "B"
    TURMAS ||--o{ TURMA_EVENTO : "B"
    PORTAL_USERS ||--o{ FORMANDOS : "A→B"

    EVENTOS ||--o{ PACOTES : "C"
    PACOTES ||--o{ ADESOES : "C"
    FORMANDOS ||--o{ ADESOES : "C"
    ADESOES ||--|{ PARCELAS : "C"
    PARCELAS ||--o{ PAGAMENTOS : "C"

    EVENTOS ||--o{ LOTES_CONVITES : "D"
    LOTES_CONVITES ||--o{ CONVITES : "D"
    FORMANDOS ||--o{ CONVITES : "D"
    CONVITES ||--o{ RSVP_HISTORICO : "D"

    EVENTOS ||--o{ MAPAS_MESAS : "E"
    MAPAS_MESAS ||--o{ MESAS : "E"
    MESAS ||--o{ ASSENTOS : "E"
    ASSENTOS ||--o{ RESERVAS_ASSENTOS : "E"
    CONVITES ||--o{ RESERVAS_ASSENTOS : "E"

    EVENTOS ||--o{ PRODUTOS_EXTRAS : "F"
    FORMANDOS ||--o{ PEDIDOS_EXTRAS : "F"
    PEDIDOS_EXTRAS ||--|{ PEDIDO_EXTRA_ITENS : "F"
    PEDIDOS_EXTRAS ||--o{ CONVITES : "F→D"
    PEDIDOS_EXTRAS ||--o{ PAGAMENTOS : "F→C"
    WEBHOOK_EVENTOS ||--o{ PAGAMENTOS : "F"

    EVENTOS ||--o{ ENQUETES : "G"
    ENQUETES ||--|{ OPCOES_ENQUETE : "G"
    OPCOES_ENQUETE ||--o{ VOTOS : "G"
    PORTAL_USERS ||--o{ VOTOS : "A→G"

    EVENTOS ||--o{ NOTIFICACOES : "H"
    NOTIFICACOES ||--o{ NOTIFICACAO_ENTREGAS : "H"

    ORGANIZACOES {
        char26 ulid
        varchar nome
    }
    EVENTOS {
        char26 ulid
        varchar slug
        varchar status
    }
    FORMANDOS {
        char26 ulid
        bigint turma_id
        bigint portal_user_id
    }
    PORTAL_USERS {
        char26 ulid
        varchar tipo
    }
    ADESOES {
        char26 ulid
        varchar status
        int valor_total_centavos
    }
    CONVITES {
        char26 ulid
        varchar codigo
        varchar status
    }
    RESERVAS_ASSENTOS {
        char26 ulid
        varchar status
        varchar idempotency_key
    }
    PEDIDOS_EXTRAS {
        char26 ulid
        varchar status
    }
    WEBHOOK_EVENTOS {
        bigserial id
        varchar gateway_reference
        varchar status
    }
    VOTOS {
        char26 ulid
        varchar ator_tipo
        bigint ator_id
    }
```

**Legenda das anotações (letra = bloco/fase):**

- A: Identidade e Acesso (F1)
- B: Cadastro Estrutural (F1/F2)
- C: Comercial e Adesão (F2)
- D: Convites e RSVP (F4)
- E: Seating (F5)
- F: Extras e Pagamentos Operacionais (F6)
- G: Engajamento (F6)
- H: Comunicação (F4/F6)

---

## 10. Linhas de dependência forte (resumo textual)

Complementa os diagramas. Útil para guiar a ordem de criação de migrations (ver `migrations-plan.md`).

| Dependência                                                                  | Tipo                                           | Observação                                                           |
| ---------------------------------------------------------------------------- | ---------------------------------------------- | -------------------------------------------------------------------- |
| `instituicoes.organizacao_id` → `organizacoes.id`                            | FK RESTRICT                                    | Topo da hierarquia.                                                  |
| `turmas.instituicao_id` → `instituicoes.id`                                  | FK RESTRICT                                    |                                                                      |
| `turmas.curso_id` → `cursos.id`                                              | FK RESTRICT                                    |                                                                      |
| `formandos.turma_id` → `turmas.id`                                           | FK RESTRICT                                    |                                                                      |
| `formandos.portal_user_id` → `portal_users.id`                               | FK RESTRICT                                    | 1 PortalUser pode ter muitos Formando (um por turma/evento).         |
| `eventos` não possui FK obrigatória; conecta-se a turmas via `turma_evento`. | —                                              | Permite eventos "inter-turmas" sem ambiguidade.                      |
| `adesoes.formando_id` + `adesoes.evento_id`                                  | FK RESTRICT                                    | UNIQUE parcial ativo.                                                |
| `convites.formando_id`                                                       | FK RESTRICT                                    | Todo convite tem titular formando (mesmo cortesia/staff).            |
| `reservas_assentos.assento_id`                                               | FK RESTRICT                                    | UNIQUE parcial ativo.                                                |
| `pedidos_extras.formando_id`                                                 | FK RESTRICT                                    |                                                                      |
| `pagamentos.parcela_id`                                                      | FK nullable (NULL se cobrança de pedido extra) | Pagamento polimórfico não — separa `parcela_id` e `pedido_extra_id`. |
| `webhook_eventos.(provider, gateway_reference)`                              | UNIQUE                                         | Idempotência dura.                                                   |
| `votos.(enquete_id, ator_tipo, ator_id)`                                     | UNIQUE (condicional)                           | Só quando enquete `permite_edicao = false`.                          |
| `notificacoes` → `templates_notificacao`                                     | FK RESTRICT                                    |                                                                      |

---

## 11. Anti-padrões explicitamente evitados

- Nenhum relacionamento **polimórfico de domínio** (evitamos `morphTo` em entidades financeiras). Pagamento separa `parcela_id` de `pedido_extra_id` por duas colunas FK mutuamente exclusivas.
- Nenhuma cardinalidade N:N "crua" — sempre via tabela pivô nomeada (`turma_evento`, `pacote_produtos`, `adesao_produtos`, `pedido_extra_itens`).
- Nenhuma FK `ON DELETE CASCADE` em entidades transacionais — sempre `RESTRICT` para forçar desativação explícita via estado.

> Consolide sempre com `data-model.md`. Divergência entre este diagrama e o schema real é **bug**; a migration é a autoridade.
