---
title: 'ADR-RH-001: Funcionário como agregado-raiz e vínculo opcional com AdminUser'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-001: Funcionário como agregado-raiz e vínculo opcional com AdminUser

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** modelagem, rh, multi-tenant

> Nomenclatura: o módulo de RH é o pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), **aditivo ao core** ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)) — nunca edita o boilerplate. Schema canônico em [01 — Modelo de Domínio](../01-modelo-de-dominio.md).

## Contexto e problema

O módulo de RH precisa de uma entidade central de pessoa que carregue dados pessoais, contratação, contatos, endereços, dados bancários, dependentes, documentos e a linha do tempo funcional. O cliente também exige **self-service** (o colaborador acessa os próprios dados) e **ACL hierárquica** (o gestor enxerga sua subárvore — ver [ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)). Ambos exigem responder, de forma confiável, "qual funcionário é este usuário logado?".

O core já tem `admin_users` (guard `admin`) como entidade de **login**. Surgem duas tensões:

1. **Onde mora a verdade do funcionário?** Espalhar dados pessoais/contratuais por várias tabelas paralelas (uma para contatos, outra para contratação, sem dono claro) fragmenta as invariantes e dificulta a auditoria LGPD e a transação atômica de eventos (ADR-RH-005).
2. **Como ligar funcionário ao usuário de login** sem assumir que todo `AdminUser` é funcionário (o super-admin, um contador externo, um usuário de integração **não** são) e sem assumir que todo funcionário tem login (a maioria do chão de fábrica **não** acessa o painel)?

A regra de ouro do [ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) impõe a restrição estrutural: o pacote é **aditivo** — não pode editar tabelas, models ou migrations do core (`admin_users` inclusive), sob pena de quebrar o `git merge upstream` que mantém a base atualizável.

## Drivers da decisão

- Uma única **fonte de verdade** para a pessoa, com invariantes e auditoria PII centralizadas ([01 §8 LGPD](../01-modelo-de-dominio.md)).
- Vínculo funcionário↔login que **não** force "1 login = 1 funcionário" (cardinalidade real é 0..1 de ambos os lados).
- **Zero edição do core** — `admin_users` é intocável; a FK e a relação inversa nascem no pacote ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).
- Resolução barata e segura de "quem sou eu" para self-service e para o eixo de ACL do organograma ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)).
- Isolamento multi-tenant: o vínculo é único **por empresa**, não global (o mesmo `AdminUser` pode ser funcionário em empresas distintas no multi-tenant lógico).

## Alternativas consideradas

### Alt 1: Funcionário fragmentado em tabelas paralelas sem agregado-raiz

- Prós: tabelas menores; cada "aspecto" (pessoal, contratual) evolui isolado.
- Contras: sem dono das invariantes; `unique` de CPF/matrícula sem âncora natural; auditoria PII e a transação evento+estado (ADR-RH-005) ficam sem ponto único; N+1 e joins por toda parte para montar a ficha. Rejeitada.

### Alt 2: Tabela de vínculo separada `funcionario_admin_user`

- Prós: explícito; permitiria atributos no vínculo (data de concessão de acesso, etc.).
- Contras: **overkill** para uma cardinalidade 1:1 (0..1). Introduz um join a mais em **todo** check de "quem sou eu" (caminho quente do self-service e do scope de ACL). A unicidade 1:1 vira dois índices únicos numa tabela-ponte em vez de uma coluna. Sem ganho real sobre uma FK nullable. Rejeitada.

### Alt 3: FK no core — `admin_users.funcionario_id`

- Prós: o vínculo "viveria" no usuário; relação direta sem método de pacote.
- Contras: **viola a regra aditiva** do [ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — exige migration e cast no core, acopla o boilerplate ao módulo RH e quebra o `git merge upstream`. Um core sem RH passaria a ter uma coluna órfã. Proibida pela arquitetura.

### Alt 4: Unificar — "AdminUser **é** o Funcionário" (uma só entidade)

- Prós: zero vínculo; "quem sou eu" é trivial.
- Contras: assume que **todo login é funcionário** (falso: super-admin, contador externo, usuário de integração) e que **todo funcionário loga** (falso: a maioria não acessa o painel). Forçaria criar `AdminUser` para cada pessoa do chão de fábrica (com senha, 2FA, etc.), poluindo a base de auth e a ACL. Mistura o conceito de **identidade de acesso** com o de **pessoa contratada**. Rejeitada.

## Decisão

A tabela **`funcionarios` é o agregado-raiz** do módulo. O núcleo (dados pessoais + contratação, eSocial-ready) vive em `funcionarios`; os aspectos com cardinalidade própria são **tabelas-filhas 1:N** sob o mesmo agregado ([01 §3 B1–B6](../01-modelo-de-dominio.md)):

- `funcionario_contatos`, `funcionario_enderecos`, `funcionario_dados_bancarios`, `funcionario_dependentes`, `funcionario_documentos` (binário em `anexos` do core).

Todas herdam `BelongsToEmpresa` (global scope `empresa` + auto-fill) e `SoftDeletes`; PII vai para `atributosNaoAuditados()` por model. As filhas são geridas **dentro** do form do funcionário, sob as permissões de `rh.funcionarios.*` — não geram CRUD/menu próprios.

O **vínculo com o login** é uma FK **1:1 opcional** no agregado:

- Coluna `funcionarios.admin_user_id` `BIGINT NULL`, FK `nullOnDelete`, **no pacote** (`packages/modulo-rh/database/migrations`, via `loadMigrationsFrom`) — o core fica intocado.
- Unicidade por tenant: índice único **parcial** `UNIQUE (empresa_id, admin_user_id) WHERE deleted_at IS NULL` (um login mapeia a no máximo um funcionário **por empresa**; o multi-tenant lógico permite o mesmo `AdminUser` em empresas diferentes).
- A relação inversa `AdminUser::funcionario(): HasOne` é adicionada **por um model do pacote** (macro/método sobre o model do core), **sem migration nem alteração no `admin_users`** — respeitando [ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md).

**Fail-closed** é a postura de segurança do vínculo: quando o usuário logado **não** está vinculado a um funcionário **e não** tem a permissão `rh.funcionarios.ver_todos`, o resultado é **vazio** (nega por omissão), nunca "vê tudo". Isso fecha a porta para usuários de login que não são funcionários (super-admin à parte, que é global por desenho) e ancora o eixo de ACL do organograma ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)).

Greenfield: a coluna `admin_user_id` (e `gestor_id`) nasce já na migration de `funcionarios` (B2), mas sua **mecânica** (resolução "quem sou eu", self-service, subárvore) é o bloco B3 — ver [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md).

## Consequências

**Positivas:**

- Uma fonte de verdade da pessoa: invariantes, `unique` por tenant (CPF/matrícula/`admin_user_id`), auditoria PII e a transação evento+estado (ADR-RH-005) têm âncora natural.
- Separação limpa entre **identidade de acesso** (`AdminUser`, core) e **pessoa contratada** (`Funcionario`, pacote); nem todo login é funcionário, nem todo funcionário loga.
- Core 100% intocado: FK e relação inversa moram no pacote; `git merge upstream` segue limpo ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).
- "Quem sou eu" é uma leitura barata (FK direta, sem join de ponte), sustentando self-service e o scope de ACL.

**Negativas / a gerenciar:**

- A relação `AdminUser::funcionario()` é um método de pacote (não nativa do model do core) — quem lê o core não a vê; documentar no pacote e cobrir com teste de fumaça.
- O agregado `funcionarios` é largo (muitas colunas + várias filhas) — mitigado pelo form multi-aba e por eager-load explícito das filhas (evitar N+1).
- O vínculo único é **por empresa**, não global — o código que resolve o funcionário **deve** sempre considerar a empresa ativa (`TenantContext`); um lookup por `admin_user_id` sem `empresa_id` é bug.
- Vínculo nulo é o caso comum (maioria não loga) — a UI e as policies precisam tratar `funcionario === null` como estado de primeira classe (fail-closed), não como erro.

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — regra aditiva (FK/relação no pacote, core intocado).
- [ADR-RH-003: ACL hierárquica por organograma](ADR-RH-003-acl-hierarquica-organograma.md) — o vínculo ancora o eixo de visibilidade e o fail-closed.
- [ADR-RH-005: Histórico funcional como eventos imutáveis](ADR-RH-005-historico-eventos-imutaveis.md) — a transação evento+estado tem o agregado como dono.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§3 Bloco B, §3 Bloco E, §8 LGPD) · [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md) (mecânica do vínculo e self-service).
