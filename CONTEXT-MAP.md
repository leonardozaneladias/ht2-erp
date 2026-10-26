# Context Map

Este repositório é multi-context. Além do índice, ele carrega a **linguagem da
plataforma** — os termos que atravessam todos os contextos e descrevem como o
sistema é montado e distribuído.

## Linguagem da plataforma

**Core**:
O pacote `ht2ml/core` (em `packages/core/`), que forma a plataforma compartilhada.
As extensões também usam o vendor `ht2ml/`, mas não são core.
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
O ponto de partida de um produto novo (`ht2ml/skeleton`), obtido por
`composer create-project` (ADR-0017).
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
Diz-se "o módulo RH, distribuído no pacote `ht2ml/extensao-rh`". O tipo fica em
`extra.ht2ml.tipo` do `composer.json` (`modulo` ou `biblioteca`). Hoje são
extensões-módulo `rh`, `fiscal-br` e `exemplo-demo`, e extensões-biblioteca
`documentos`, `api` e `ia`.
_Avoid_: plugin, módulo-pacote, módulo empacotado

**Pacote**:
A forma de distribuição — o artefato Composer. Core, extensões e skeleton são
todos pacotes; "pacote" descreve como a coisa viaja, não o que ela é.
_Avoid_: biblioteca, dependência

**Escopo de rota**:
O canal por onde a rota de um módulo entra: `Admin` (painel autenticado), `Publico`
(stack web sem login), `Webhook` (sem sessão, sob `/webhooks`) ou `Api` (grupo `api`,
sob `/api`). O core decide grupo e prefixo; o módulo decide o resto (ADR-0023).
_Avoid_: tipo de rota, contexto de rota

**Canal**:
Um ponto do core por onde uma extensão declara uma contribuição (rota, seeder,
permissão, área, seção, grupo, item de menu, catálogo) sem editar o core. Todos passam
pelo `ModuleRegistry`. Faltar canal é a causa de toda violação da mão única (ADR-0022).
_Avoid_: hook, gancho

**Submódulo**: não existe. É (i) um **recurso**, se for entidade com CRUD, ou
(ii) um segundo **módulo** que declara dependência do primeiro.

## Contextos

- [Core](./app/CONTEXT.md): plataforma compartilhada — acesso, contexto, auditoria, aparência. O glossário está em `app/CONTEXT.md`; o código, em `packages/core/`.
- RH (`packages/extensao-rh/`): departamento pessoal — funcionários e departamentos. Glossário ainda não escrito.
- Fiscal BR (`packages/extensao-fiscal-br/`): catálogos de classificação fiscal (CNAE, CFOP, NCM). Sem glossário próprio.
- Documentos (`packages/extensao-documentos/`): numeração sequencial de documentos, biblioteca sem tela. Sem glossário próprio.
- Exemplo demo (`packages/extensao-exemplo-demo/`): vitrine do design system; não é domínio de negócio.
- API (`ht2ml/extensao-api`) e IA (`ht2ml/extensao-ia`): bibliotecas de infraestrutura, sem domínio próprio (ADR-0023, ADR-0024).

> **O vocabulário específico de ERP não é glosado, por decisão.** Documento
> numerado e referência fiscal pertencem a produtos de ERP, não à plataforma, e
> já saíram para extensões (`ht2ml/extensao-documentos` e `ht2ml/extensao-fiscal-br`,
> ADR-0019). Registra-se aqui apenas a linguagem da plataforma. A ausência é deliberada.

## Relações

- **Core → RH, Fiscal BR, Exemplo demo**: consomem o contexto ativo, o registro de permissões e o menu do core.
- **Core → Documentos**: a tabela de sequências referencia `empresas` do core.
- **API e IA não dependem do core.** Um módulo com API pendura as rotas no canal `EscopoDeRota::Api` do core e usa a `extensao-api` para o contrato; um módulo que usa IA tipa contra os contratos da `extensao-ia`.
- **A dependência é de mão única, nos dois sentidos** (ADR-0022): o produto e a extensão nunca editam o core, e o core nunca conhece extensão alguma — nem por classe, nem por literal de string. Dois guards no CI (`tests/Arch/CoreNaoConheceExtensaoTest.php`). Quando o core precisa de algo que só uma extensão sabe fazer, o corte é no contrato.

## ADRs

Decisões do sistema ficam em [`docs/architecture/adrs/`](./docs/architecture/adrs/) —
não em `docs/adr/`, que é o caminho que os templates genéricos assumem.
