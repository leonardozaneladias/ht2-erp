# Context Map

Este repositório é multi-context. Além do índice, ele carrega a **linguagem da
plataforma** — os termos que atravessam todos os contextos e descrevem como o
sistema é montado e distribuído.

## Linguagem da plataforma

**Core**:
O conjunto de pacotes `ht2ml/*` que forma a plataforma compartilhada.
_Avoid_: base, boilerplate

**Produto**:
Uma aplicação que instala o core e resolve um domínio de negócio próprio.
Um ERP, um sistema de restaurante e um CRM são todos produtos; nenhum deles
vive dentro da plataforma (ADR-0019).
_Avoid_: projeto derivado, aplicação derivada

**Instância**:
Um deploy de um produto para um cliente específico.
_Avoid_: cliente, projeto do cliente

**Skeleton**:
O ponto de partida de um produto novo, obtido por `composer create-project`.
_Avoid_: template, clone da base

**Módulo**:
Área de negócio com superfície administrativa própria: ao menos uma permissão e
ao menos uma rota ou item de menu. Identificado por uma chave kebab estável
(`rh`, `escola`). Pode viver dentro do produto ou viajar num pacote (ADR-0021).
_Avoid_: feature, submódulo, área

**Recurso**:
Uma entidade com seu CRUD, dentro de um módulo. Identificado por uma chave no
plural (`alunos`). É o que `make:recurso` gera.
_Avoid_: entidade, tela, CRUD

**Área de acesso**:
A gaveta do catálogo de permissões — a divisão que a matriz de acesso usa para
agrupar ~200 permissões em algo navegável. Por convenção 1:1 com um módulo, mas
não por invariante: `tabelas_auxiliares` atravessa pacotes por natureza.
_Avoid_: módulo de acesso, grupo de permissões

**Seção de menu**:
A gaveta de primeiro nível da sidebar. Por convenção 1:1 com um módulo. Dentro
dela, **grupo** é a subdivisão (submenu) — apresentação pura, sem rota nem
permissão própria.
_Avoid_: categoria, menu pai

**Extensão**:
O envelope: um pacote que carrega um módulo (**extensão-módulo**) ou só código
sem UI (**extensão-biblioteca**). Um pacote de módulo carrega exatamente um
módulo, e a chave é derivável do nome do pacote (`ht2ml/extensao-rh` → `rh`).
Diz-se "o módulo RH, distribuído no pacote `ht2ml/extensao-rh`".
_Avoid_: plugin, módulo-pacote, módulo empacotado

**Pacote**:
A forma de distribuição — o artefato Composer. Core, extensões e skeleton são
todos pacotes; "pacote" descreve como a coisa viaja, não o que ela é.
_Avoid_: biblioteca, dependência

**Submódulo**: não existe. É (i) um **recurso**, se for entidade com CRUD, ou
(ii) um segundo **módulo** que declara dependência do primeiro.

## Contextos

- [Core](./app/CONTEXT.md): plataforma compartilhada — acesso, contexto, auditoria, aparência
- RH (`packages/extensao-rh/`): departamento pessoal — funcionários e departamentos. Glossário ainda não escrito.

> **O vocabulário específico de ERP não é glosado, por decisão.** Documento
> numerado e referência fiscal pertencem a produtos de ERP, não à plataforma,
> e saem de `app/` para extensões (ADR-0019). Registra-se
> aqui apenas a linguagem que sobrevive à extração. A ausência é deliberada.

## Relações

- **Core → RH**: o RH consome o contexto ativo, o registro de permissões e o menu do core.
- **A dependência é de mão única, nos dois sentidos** (ADR-0022): o produto e a extensão nunca editam o core, e o core nunca conhece extensão alguma — nem por classe, nem por literal de string. Dois guards no CI (`tests/Arch/CoreNaoConheceExtensaoTest.php`). Quando o core precisa de algo que só uma extensão sabe fazer, o corte é no contrato.

## ADRs

Decisões do sistema ficam em [`docs/architecture/adrs/`](./docs/architecture/adrs/) —
não em `docs/adr/`, que é o caminho que os templates genéricos assumem.
