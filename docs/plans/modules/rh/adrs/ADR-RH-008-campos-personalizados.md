---
title: 'ADR-RH-008: Campos personalizados via JSONB-híbrido (definições + valores em JSONB)'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-008: Campos personalizados via JSONB-híbrido (definições + valores em JSONB)

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** modelagem, rh, extensibilidade, lgpd

> Pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Schema em [01 §A11/§B1/§4.2](../01-modelo-de-dominio.md); mecânica completa em [10](../10-campos-personalizados.md). Decisão de modelagem — **fundação reutilizável**, aplicada ao funcionário na Fase 1.

## Contexto e problema

O cliente pediu **flexibilidade total para ajustar o sistema sem código**. Os catálogos configuráveis ([04](../04-catalogos-configuraveis.md)) resolvem "adicionar linhas" a conceitos existentes (um departamento a mais), mas **não** cobrem "adicionar **campos**" à ficha da pessoa — atributos que o engenheiro não previu e que variam por empresa ("tamanho da camiseta", "matrícula legada", "número do crachá").

Criar **coluna + migration por pedido** não é opção: cada cliente teria um schema diferente, o que **quebra o pacote distribuível** (`modulo-rh` é um produto com várias instalações — [ADR-RH-007](ADR-RH-007-rh-familia-modulos-pacote.md)) e contraria a evolução aditiva controlada ([01 §6](../01-modelo-de-dominio.md)). Precisa-se de um mecanismo em que o **cliente define** os campos pela UI e o sistema os **renderiza, valida, persiste e audita** genericamente — sem deploy.

## Drivers da decisão

- **Configuração pelo cliente, sem código nem migration** — campos nascem/morrem por dados, não por deploy.
- **Leitura barata** — exibir a ficha não pode custar N joins por campo.
- **Governança e validação** — campo tem tipo, obrigatoriedade, opções; a UI e a validação são **dirigidas por dados**, não livres.
- **Isolamento multi-tenant** — campos são por empresa (e por entidade).
- **LGPD** — um campo pode ser sensível (mascaramento + fora de auditoria), resolvido em runtime ([01 §8](../01-modelo-de-dominio.md)).
- **Reuso** — o mecanismo não é específico de funcionário; deve servir a outras entidades (candidato a promoção ao core).

## Alternativas consideradas

### Alt 1 — Coluna + migration por campo

- Prós: tipagem nativa do banco; índices/constraints diretos.
- Contras: **schema divergente por cliente** — inviabiliza o pacote distribuível; cada pedido vira deploy; migrations infinitas. Rejeitada.

### Alt 2 — EAV (entity-attribute-value: uma linha por valor)

- Prós: totalmente dinâmico; "tipável" por coluna de tipo.
- Contras: **explosão de joins** (uma linha por valor por entidade), leitura cara, ORM hostil, agregação/relatório penosos, integridade fraca. O anti-padrão clássico de "banco dentro do banco". Rejeitada.

### Alt 3 — Schemaless puro (só uma coluna JSONB livre, sem definições)

- Prós: zero estrutura; grava qualquer coisa.
- Contras: **sem governança** — nenhuma validação, nenhuma UI dirigida, nenhum controle de tipo/obrigatoriedade/LGPD; vira lixeira de dados. Rejeitada.

### Alt 4 — JSONB-híbrido: definições em tabela + valores em JSONB na entidade (escolhida)

- Prós: **governança** (definições tipadas, tenant) **+ leitura barata** (o valor vem junto com a linha da entidade, sem join) + UI/validação dirigidas por dados + LGPD por definição + **reutilizável** (trait + enum + componente agnósticos).
- Contras: filtro por campo usa operadores JSONB (GIN como evolução); sem FK do banco para os valores (validação na aplicação); chaves órfãs ao apagar definição. Aceitáveis — é o equilíbrio entre EAV e schemaless.

## Decisão

**Adotar o modelo JSONB-híbrido.** Concretamente ([01 §A11/§B1/§4.2](../01-modelo-de-dominio.md), [10](../10-campos-personalizados.md)):

1. **Definições** em `campos_personalizados` — catálogo tenant **meta** (`[E][S][A]`), chaveado por `(empresa_id, entidade, chave)`: `tipo` (enum `TipoCampoPersonalizado`), `opcoes`/`regras` (JSONB), `obrigatorio`, `sensivel`, `grupo`/`ordem`/`ajuda`.
2. **Valores** em coluna `dados_personalizados` **JSONB** na entidade hospedeira (`funcionarios` na Fase 1) — mapa `chave → valor`, cast `array`.
3. **Trait `TemCamposPersonalizados`** — resolve as definições da empresa (cache por request), gera as **regras de validação dinâmicas**, expõe acessores e faz a **redação LGPD** das chaves sensíveis na auditoria.
4. **Enum `TipoCampoPersonalizado`** — fonte única do mapeamento **tipo → componente `x-shared.*`** e **tipo → regra de validação** (sem `if` espalhado).
5. **Componente Livewire genérico** renderiza os campos a partir das definições (`x-dynamic-component`), sem HTML por campo e sem `<select>` nativo.

É **fundação reutilizável**: adotar em outra entidade é adicionar a coluna JSONB + usar o trait + filtrar a tela de definições por `entidade` ([10 §7](../10-campos-personalizados.md)). Marcada como **candidata a promoção ao core** — mesma lógica do [ADR-RH-007](ADR-RH-007-rh-familia-modulos-pacote.md); a promoção em si fica para quando um segundo módulo precisar, sem reabrir esta decisão.

**Faseamento de capacidades (D3) — visão completa, MVP enxuto.** A modelagem comporta um catálogo amplo, mas a Fase 1 entrega só o núcleo:

- **Tipos** — **MVP = 8** (`texto`, `texto_longo`, `numero`, `decimal`, `data`, `booleano`, `select`, `multi_select`); o catálogo-alvo de ~30 ([10 §3](../10-campos-personalizados.md)) entra como **novos `case` do enum** (aditivo, sem migration de schema). **Exclusões conscientes:** `monetario` (dinheiro é centavos `INTEGER` — [ADR-0014](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md); só entra serializando centavos, nunca float em JSONB) e `senha`/segredo (risco LGPD — não se guarda segredo livre em JSONB de cadastro). Tipos com alça extra (`arquivo`/`imagem`/`documento` → `Anexo`; `relacionado` → FK lógica; `calculado` → derivado) são evolução por exigirem mais que JSONB plano.
- **Condicionais** — o **MVP é de campos PLANOS (sem condicionais)**; as regras condicionais (show-if, required-if, opções dependentes, limites por contexto, autofill, bloqueio por status, regras por perfil) são **[Evolução]** com o **desenho pronto** em [10 §2.4](../10-campos-personalizados.md), vivendo no JSONB `regras.condicoes` (zero migration quando entrarem).
- **Propriedades e opções** — o MVP entrega as propriedades essenciais (§2.1) e opções `label/valor/ordem` (§2.3); configs ricas (descrição, valor padrão, máscara, visível, exibir em listagem, opção ativa/padrão) e UX (drag-drop, prévia, duplicar, "onde é usado", "já tem dados?") são evolução — a maioria cabe em `regras` (JSONB) ou colunas aditivas.
- **Mudança de tipo / preservação** — proibida a troca silenciosa de `tipo`/`chave` de um campo com dados; valores preservados por `chave` no JSONB ([10 §2.5](../10-campos-personalizados.md)). Pendência consolidada em [13 PEND-11/PEND-12](../13-rastreabilidade-e-pendencias.md).

## Consequências

**Positivas:**

- Cliente cria campos **sem código/migration/deploy**; o pacote distribuível mantém **schema estável**.
- **Leitura barata** (valor junto da linha) e **governança** (tipo/obrigatoriedade/opções/LGPD por definição).
- Mecanismo **reutilizável** (trait + enum + componente agnósticos de domínio); candidato a core.
- LGPD por campo (`sensivel`) resolvida **dinamicamente** ([01 §8](../01-modelo-de-dominio.md)).

**Negativas / a gerenciar:**

- **Filtro/relatório por campo** usa operadores JSONB (`->>`); índice **GIN** é evolução, não default ([10 §8](../10-campos-personalizados.md)).
- **Sem FK do banco** para os valores — a integridade (opções válidas, tipo) é da aplicação (trait + Rules).
- **Chaves órfãs** ao apagar/renomear definição permanecem no JSONB (preserva histórico); limpeza é evolução.
- **Dinheiro e arquivo** ficam de fora na Fase 1 (evita float em JSONB / binário fora do `Anexo`); entram como tipos novos do enum quando necessário (aditivo).
- Exige **disciplina de validação na aplicação** — uma escrita fora do trait pode gravar JSON inconsistente; toda escrita passa pela Action do agregado ([03 §12](../03-cadastro-pessoa-documentos.md)).

## Referências

- [10 — Campos Personalizados](../10-campos-personalizados.md) — modelo, trait, enum, UI, reuso, faseamento.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§A11 definições, §B1 `dados_personalizados`, §4.2 enum, §10 permissões, §8 LGPD).
- [ADR-RH-007: RH como família de módulos-pacote](ADR-RH-007-rh-familia-modulos-pacote.md) — lógica de "fundação candidata a promoção ao core".
- [ADR-0015: Módulos como pacotes Composer](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — aditividade (coluna/trait no pacote, core intocado).
- [ADR-0014: dinheiro em centavos](../../../../architecture/adrs/ADR-0014-money-integer-centavos.md) — porquê dinheiro fica fora dos tipos JSONB por ora.
