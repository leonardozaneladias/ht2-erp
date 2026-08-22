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
O HT2 ERP é um produto como qualquer outro, não a plataforma.
_Avoid_: projeto derivado, aplicação derivada

**Instância**:
Um deploy de um produto para um cliente específico.
_Avoid_: cliente, projeto do cliente

**Skeleton**:
O ponto de partida de um produto novo, obtido por `composer create-project`.
_Avoid_: template, clone da base

**Módulo**:
Unidade de funcionalidade de negócio que vive dentro do próprio produto.
_Avoid_: feature, submódulo

**Extensão**:
Unidade de funcionalidade de negócio distribuída como pacote, instalável em
qualquer produto. Um módulo vive no produto; uma extensão atravessa produtos.
_Avoid_: plugin, módulo-pacote, módulo empacotado

**Pacote**:
A forma de distribuição — o artefato Composer. Core, extensões e skeleton são
todos pacotes; "pacote" descreve como a coisa viaja, não o que ela é.
_Avoid_: biblioteca, dependência

## Contextos

- [Core](./app/CONTEXT.md): plataforma compartilhada — acesso, contexto, auditoria, aparência
- RH (`packages/modulo-rh/`): departamento pessoal — funcionários e departamentos. Glossário ainda não escrito.

## Relações

- **Core → RH**: o RH consome o contexto ativo, o registro de permissões e o menu do core; a dependência é de mão única.

## ADRs

Decisões do sistema ficam em [`docs/architecture/adrs/`](./docs/architecture/adrs/) —
não em `docs/adr/`, que é o caminho que os templates genéricos assumem.
