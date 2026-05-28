---
title: 'ADR-0009: Snapshots JSONB imutáveis em entidades transacionais'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0009: Snapshots JSONB imutáveis em entidades transacionais

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Produto, Jurídico | **Tags:** dados, imutabilidade, postgres, lgpd

## Contexto e problema

Dados mestres do sistema (produtos, pacotes, cotas_regras, templates_notificacao, mapas) mudam com o tempo: preço sobe, política de cancelamento é revista, texto de termo é atualizado. Entidades transacionais (adesão confirmada, convite emitido, reserva confirmada, pedido extra pago, voto) foram criadas **sob regras específicas no momento daquela transação** e não devem mudar retroativamente — sob pena de disputa jurídica ("o preço que apareceu na minha confirmação era outro") e perda de auditoria.

Como congelar o estado comercial/regulatório do momento da confirmação sem criar um esquema paralelo de versionamento manual?

## Drivers da decisão

- Invariante de imutabilidade para estados finais (adesão ativa, convite emitido, reserva confirmada, pedido extra pago, voto registrado).
- Compliance LGPD + jurídico: consumidor pode pedir hash do termo aceito.
- Consulta rara e pontual (não dominante) → JSONB sem índice secundário é aceitável.
- Evitar versionar `produtos`/`pacotes` com SCD tipo 2 (custo alto e pouco retorno).

## Alternativas consideradas

### Alt 1: Referência dinâmica ao mestre (`adesao.pacote_id`)

- Prós: normalização perfeita.
- Contras: mudar `pacote.preco` muda retroativamente o valor da adesão antiga. Grave.

### Alt 2: SCD Tipo 2 em `produtos` e `pacotes`

- Prós: histórico completo.
- Contras: complexidade alta; joins viram pesados; cada mudança vira `UPDATE valid_to + INSERT`.

### Alt 3: Snapshot JSONB imutável na transacional (escolhida)

- Prós: estado comercial "fotografado" no registro transacional; zero impacto em mudanças futuras nos mestres; um único lugar para auditoria ("o que foi combinado"); consulta pontual basta.
- Contras: JSONB não normalizado — não dá para fazer JOIN/agregação no snapshot facilmente; mas isso é exatamente o objetivo (é histórico imutável, não operação).

## Decisão

Entidades transacionais capturam `snapshot_*` em coluna JSONB **no momento da transição para estado final**:

| Tabela              | Coluna snapshot         | Quando                                                                |
| ------------------- | ----------------------- | --------------------------------------------------------------------- |
| `adesoes`           | `snapshot_comercial`    | transição para `ativa` — preço, desconto, termo, condição             |
| `convites`          | `snapshot_regra`        | emissão — cota, política, template                                    |
| `reservas_assentos` | (dados em memory trail) | confirmação — composição da mesa (via `reservas_historico`)           |
| `pedidos_extras`    | `snapshot`              | pagamento confirmado — preço unitário, condição, estoque no momento   |
| `votos`             | (não necessário)        | unique (`enquete_id`, `ator_tipo`, `ator_id`) já é fotografia do voto |

Adicionalmente, `adesoes.termo_hash = sha256(termo_html)` permite prova de integridade. `convites.snapshot_regra` captura a regra de cota aplicada. `eventos.config_json` (mestre) é JSONB por ser "config declarativo" que varia por evento.

Regras operacionais:

- **Nunca** alterar snapshot após criado (escrever uma vez, ler muitas).
- **Nunca** consultar snapshot em `WHERE` de queries operacionais (apenas para auditoria/exibição).
- Snapshot é serializado do estado resolvido no momento da action (não de ID mestre).

## Consequências positivas

- Mudar o preço de um pacote amanhã não afeta adesões ativas passadas.
- Disputa jurídica resolvida por `termo_hash` + `snapshot_comercial`.
- Entidades transacionais ficam auto-contidas — dump de uma adesão contém tudo que a explica.
- Zero migration quando pacote/produto ganha um campo novo.

## Consequências negativas

- Duplicação de dados (preço sai do mestre e vive na transacional). Aceito — é o ponto.
- Não é possível "atualizar termo em adesões antigas" facilmente. Aceito — é o ponto.
- JSONB cresce o row size. Mitigação: TOAST do Postgres compacta transparentemente.

## Ligações

- §0 princípio 6, §4.6, §4.7, §13 (snapshots e governança) do PLANEJAMENTO_BACKEND_APIV1.md
- Apêndice D anti-pattern #7 (apesar do número: §19 do CLAUDE.md também inclui)
- ADR-0004 (ULID), ADR-0014 (money em centavos), ADR-0010 (enums)
- SAD arc42 seção "Conceitos de corte transversal — Dados e snapshots"
