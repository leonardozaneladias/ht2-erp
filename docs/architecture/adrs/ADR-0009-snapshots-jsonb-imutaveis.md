---
title: 'ADR-0009: Snapshots JSONB imutáveis em entidades transacionais'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0009: Snapshots JSONB imutáveis em entidades transacionais

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Produto | **Tags:** dados, imutabilidade, postgres

## Contexto e problema

Dados mestres do sistema (produtos, tabelas de preço, templates, regras de negócio) mudam com o tempo: preço sobe, política é revista, texto de um termo é atualizado. Entidades transacionais (um pedido confirmado, uma transação paga, um registro emitido) foram criadas **sob regras específicas no momento daquela transação** e não devem mudar retroativamente — sob pena de disputa ("o preço que apareceu na minha confirmação era outro") e perda de auditoria.

Como congelar o estado comercial/regulatório do momento da confirmação sem criar um esquema paralelo de versionamento manual?

## Drivers da decisão

- Invariante de imutabilidade para estados finais (pedido confirmado, transação paga, registro emitido).
- Compliance e auditoria: é preciso poder comprovar o que foi acordado no momento.
- Consulta rara e pontual (não dominante) → JSONB sem índice secundário é aceitável.
- Evitar versionar os mestres com SCD tipo 2 (custo alto e pouco retorno).

## Alternativas consideradas

### Alt 1: Referência dinâmica ao mestre (`pedido.produto_id`)

- Prós: normalização perfeita.
- Contras: mudar `produto.preco` muda retroativamente o valor do pedido antigo. Grave.

### Alt 2: SCD Tipo 2 nos mestres

- Prós: histórico completo.
- Contras: complexidade alta; joins viram pesados; cada mudança vira `UPDATE valid_to + INSERT`.

### Alt 3: Snapshot JSONB imutável na transacional (escolhida)

- Prós: estado comercial "fotografado" no registro transacional; zero impacto em mudanças futuras nos mestres; um único lugar para auditoria ("o que foi combinado"); consulta pontual basta.
- Contras: JSONB não normalizado — não dá para fazer JOIN/agregação no snapshot facilmente; mas isso é exatamente o objetivo (é histórico imutável, não operação).

## Decisão

Entidades transacionais capturam um `snapshot_*` em coluna JSONB **no momento da transição para estado final**:

| Tabela        | Coluna snapshot      | Quando                                                       |
| ------------- | -------------------- | ------------------------------------------------------------ |
| `pedidos`     | `snapshot_comercial` | transição para `confirmado` — preço, desconto, termo aplicado |
| `transacoes`  | `snapshot`           | pagamento confirmado — valor, condição, estado no momento     |

Adicionalmente, um hash do termo aceito (`termo_hash = sha256(termo_html)`) permite prova de integridade quando aplicável.

Regras operacionais:

- **Nunca** alterar snapshot após criado (escrever uma vez, ler muitas).
- **Nunca** consultar snapshot em `WHERE` de queries operacionais (apenas para auditoria/exibição).
- Snapshot é serializado do estado resolvido no momento da action (não de ID mestre).

## Consequências positivas

- Mudar o preço de um produto amanhã não afeta pedidos confirmados no passado.
- Disputa resolvida por `termo_hash` + `snapshot_comercial`.
- Entidades transacionais ficam auto-contidas — o dump de um pedido contém tudo que o explica.
- Zero migration quando um mestre ganha um campo novo.

## Consequências negativas

- Duplicação de dados (preço sai do mestre e vive na transacional). Aceito — é o ponto.
- Não é possível "atualizar termo em pedidos antigos" facilmente. Aceito — é o ponto.
- JSONB cresce o row size. Mitigação: TOAST do Postgres compacta transparentemente.

## Ligações

- ADR-0004 (ULID), ADR-0014 (money em centavos), ADR-0010 (enums)
