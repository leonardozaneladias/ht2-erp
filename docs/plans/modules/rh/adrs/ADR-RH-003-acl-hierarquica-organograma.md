---
title: 'ADR-RH-003: ACL hierárquica por organograma (escopo de visibilidade)'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-003: ACL hierárquica por organograma (escopo de visibilidade)

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** acl, segurança, rh, multi-tenant

> Pacote `ht2ml/extensao-rh` (namespace `HT2ML\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Vínculo funcionário↔login em [ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md). Mecânica completa em [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md).

## Contexto e problema

O cliente exige que um **gestor enxergue seus subordinados** (e os subordinados deles, recursivamente) e mais ninguém — enquanto o RH/diretoria vê todos. Isso é um requisito de **visibilidade de dados** que o RBAC do core (spatie/laravel-permission) **não** cobre: a permissão `rh.funcionarios.listar` diz _se_ o usuário pode listar funcionários, não _quais_. Dois gestores de áreas distintas têm a **mesma** permissão e precisam de **conjuntos diferentes** de linhas.

O core já tem `HT2ML\Core\Services\Admin\HierarchyResolver`, mas ele resolve **hierarquia de papéis RBAC** (quem pode gerir quem, por nível de papel) — conceito ortogonal ao **organograma de pessoas** (quem é subordinado de quem). Reusar a palavra "hierarquia" para os dois colidiria semanticamente e geraria bug por confusão.

A pergunta: como adicionar "o gestor vê sua subárvore" de forma **segura por construção** (difícil de furar), sem editar o core e sem confundir com o eixo RBAC já existente?

## Drivers da decisão

- Segurança **por construção**: o escopo precisa valer em **toda** query do recurso, não depender de cada tela lembrar de filtrar.
- **Ortogonalidade**: visibilidade hierárquica é um terceiro eixo, independente de tenant e de RBAC — `tenant AND rbac AND organograma`.
- **Fail-closed**: na ausência de vínculo ou de permissão de bypass, o resultado é vazio, nunca "tudo".
- Não colidir com `HierarchyResolver` (níveis de papel RBAC): vocabulário próprio.
- Aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)); recursão eficiente sobre `funcionarios.gestor_id` (índice `(empresa_id, gestor_id)` já previsto em [01 §7](../01-modelo-de-dominio.md)).

## Alternativas consideradas

### Alt 1: Filtro na aplicação (Livewire/controller monta o `whereIn` de IDs)

- Prós: simples de começar; nenhuma mágica no model.
- Contras: **inseguro** — cada listagem, export, contador, autocomplete e endpoint precisa lembrar de aplicar o filtro; esquecer um ponto vaza dados de subordinados de outro gestor. A defesa fica espalhada e frágil. Rejeitada como mecanismo primário (o scope no model é a defesa).

### Alt 2: Só RBAC, sem eixo de hierarquia

- Prós: nada novo; usa o que o core já tem.
- Contras: **não atende o requisito** — RBAC diz "pode listar funcionários", não "estes funcionários". Não distingue gestor A de gestor B. Insuficiente.

### Alt 3: Closure table (tabela de fechamento da árvore)

- Prós: leitura de subárvore vira `JOIN` simples (sem CTE); ótima para árvores muito profundas/quentes.
- Contras: **manutenção cara** ao mover ramos — toda mudança de `gestor_id` reescreve N linhas da closure; mais uma tabela e mais um ponto de inconsistência. Custo desproporcional na Fase 1 (organogramas rasos, escrita rara). Fica registrada como **evolução de performance** se o volume exigir.

### Alt 4: Global scope `organograma` com `WITH RECURSIVE` (escolhida)

- Prós: **seguro por construção** — aplicado no model, vale em toda query do recurso por padrão; recursão nativa do Postgres sobre `gestor_id` (índice já existe); aditivo (vive no pacote); bypass explícito por permissão.
- Contras: CTE recursiva exige **teste em Postgres** (não roda igual em SQLite); custo de recursão por request (mitigável por cache de subárvore por requisição); um escape consciente (`withoutGlobalScope('organograma')`) é necessário em relatórios globais legítimos.

## Decisão

Introduzir um **terceiro eixo de acesso, ortogonal**, chamado **organograma**: o acesso a um funcionário é `tenant AND rbac AND organograma`. O eixo é implementado como **global scope** no model, espelhando o padrão do core (`BelongsToEmpresa` usa `addGlobalScope`):

1. **Trait `VisivelNaHierarquia`** no model `Funcionario` registra o global scope `organograma`.
2. **Serviço `EscopoOrganograma`** resolve a **subárvore** do funcionário do usuário logado (de [ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md): "qual funcionário sou eu") via `WITH RECURSIVE` sobre `(empresa_id, gestor_id)` no Postgres, e restringe a query à subárvore **+ a si mesmo**.
3. **Permissão `rh.funcionarios.ver_todos`** desliga o eixo (bypass) — para RH/diretoria; super-admin é global por desenho ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)).
4. **Vocabulário "organograma"** (scope `organograma`, `EscopoOrganograma`, `VisivelNaHierarquia`) é deliberado para **não colidir** com o `HierarchyResolver` do core (níveis de papel RBAC). Os dois eixos coexistem sem ambiguidade de nome.

**Fail-closed** é a regra: quem **não** tem `ver_todos` e **não** está vinculado a um funcionário recebe conjunto **vazio** (nega por omissão) — nunca a base inteira. O bypass é a **exceção explícita** (permissão), não o default.

O escape consciente para relatórios globais legítimos é `Funcionario::withoutGlobalScope('organograma')` (análogo ao `withoutGlobalScope('empresa')` do tenant), sempre sob permissão adequada.

Na **Fase 1** a recursão é resolvida por CTE Postgres (sem closure table). Detecção de **ciclo** ao atribuir gestor (além do CHECK `gestor_id <> id` de nível 1) é validada na Action de atribuição. Mecânica completa, self-service e organograma navegável em [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md).

**Mapa estrutural e escopo da tela.** As **dimensões** do organograma (empresa, filial, departamento auto-hierárquico — que absorve unidade/setor/área —, centro de custo, cargo, função/equipe, gestor) e a justificativa de **por que a base da ACL é a hierarquia de PESSOAS (`gestor_id`) e não de departamentos** estão em [05 §3.2](../05-organograma-acl-hierarquica.md). A **spec de UX** da tela (árvore incremental: expand/collapse, busca, filtros, drag-para-reposicionar via Action anti-ciclo, detecção de vagos/órfãos/sem-responsável; pan/zoom/tela cheia incrementais; canvas rico = evolução) está em [05 §10.1](../05-organograma-acl-hierarquica.md); as **regras e impactos** de mudança estrutural em [05 §13](../05-organograma-acl-hierarquica.md) e a **segurança/auditoria** em [05 §11.2](../05-organograma-acl-hierarquica.md).

## Consequências

**Positivas:**

- Segurança **por construção**: o scope vale em toda query do recurso; uma tela nova já nasce escopada sem código extra. A defesa mora no model, não espalhada na aplicação.
- Eixo ortogonal e nomeado: `tenant AND rbac AND organograma` é explícito e não colide com o `HierarchyResolver` (RBAC) do core.
- Aditivo: vive no pacote, sobre `gestor_id` e o índice `(empresa_id, gestor_id)` já previstos; core intocado ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).
- Bypass auditável por permissão (`ver_todos`), não por flag solta.

**Negativas / a gerenciar:**

- A CTE recursiva **exige testes em Postgres** (não roda em SQLite) — alinhado ao gotcha já conhecido do projeto (FKs/recursão dependem do Postgres real). A suíte de scope deve rodar contra Postgres.
- Custo de recursão por request — mitigável cacheando a subárvore por requisição; particionar/closure table só se o volume exigir (evolução).
- O escape `withoutGlobalScope('organograma')` é poderoso: todo uso deve estar sob permissão e ser revisado, para não virar um vazamento "legítimo".
- Profundidade/ciclos: o CHECK cobre auto-referência nível 1; ciclos profundos dependem da validação na Action — sem ela, a CTE poderia recorrer indevidamente.

## Referências

- [ADR-RH-001: Funcionário como agregado-raiz e vínculo com AdminUser](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md) — resolve "qual funcionário sou eu" e o fail-closed que ancora este eixo.
- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — eixo aditivo no pacote, core intocado.
- `HT2ML\Core\Services\Admin\HierarchyResolver` (core) — hierarquia de **papéis RBAC**, eixo distinto do organograma (origem da escolha de vocabulário).
- `HT2ML\Core\Models\Concerns\BelongsToEmpresa` (core) — padrão de global scope (`addGlobalScope`) espelhado por `VisivelNaHierarquia`.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§3 B1 `gestor_id`, §7 índice `(empresa_id, gestor_id)`) · [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md).
