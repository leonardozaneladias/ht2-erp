---
title: 'ADR-0019: A plataforma é abstrata — nenhum produto vive dentro dela'
version: 1.0.0
date: 2026-08-22
status: accepted
---

# ADR-0019: A plataforma é abstrata — nenhum produto vive dentro dela

**Status:** Accepted | **Data:** 2026-08-22 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, fronteira, produtos

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md)): **core** são os pacotes `ht2ml/*`; **produto** é uma aplicação que instala o core; **extensão** é uma unidade de negócio distribuída como pacote.

## Contexto e problema

O repositório nasceu como o produto "HT2 ERP" e foi ganhando reuso: primeiro instâncias por cliente ([ADR-0016](ADR-0016-instancias-por-cliente.md)), depois extensões como pacotes Composer ([ADR-0015](ADR-0015-modulos-pacotes-composer.md)), depois produtos novos a partir de um skeleton ([ADR-0017](ADR-0017-produto-novo-via-skeleton.md)).

Faltava responder o que sobra no meio: **a plataforma é um ERP do qual se derivam outros ERPs, ou uma base sobre a qual se constrói qualquer sistema administrativo?**

A resposta importa porque o repositório não é neutro hoje. Ele carrega documentos numerados, catálogos fiscais brasileiros (IBGE, CNAE, NCM, CFOP), um preset de tema chamado `HT2_ERP` que é o *default* do enum, e uma migration de settings que **grava "HT2 ERP" no banco** de toda instalação. Um sistema de restaurante ou um CRM construído sobre isso herdaria CFOP e NCM sem nunca ter pedido.

## Drivers da decisão

- A plataforma precisa servir ERP, sistema de restaurante, CRM e o que mais aparecer — não apenas variações de ERP.
- Nenhum produto derivado deve herdar vocabulário de negócio que não é seu.
- Nenhum produto derivado deve nascer com o nome de outro produto em lugar nenhum, muito menos no banco.

## Alternativas consideradas

### Alt 1: assumir que a plataforma é o ERP

Rejeitada. É honesta sobre o presente e barata, mas fecha a porta para os produtos que motivam o reuso. Já existe na casa um sistema que não é ERP nem multiempresa e que reimplementou ACL e portal do zero justamente por não ter onde se apoiar.

### Alt 2: plataforma abstrata (escolhida)

O que é de ERP sai para extensões; o que sobra é genérico o bastante para qualquer backoffice.

## Decisão

**A plataforma é abstrata. Nenhum produto vive dentro dela.**

Consequências diretas sobre a fronteira:

- **Sai do core, para extensões**: documentos numerados, catálogos de referência fiscal (IBGE, CNAE, NCM, CFOP), RH — este último já é extensão.
- **Fica no core**: autenticação, perfis e permissões, auditoria, impersonation, settings e aparência, menu, design system, gerador de extensões, e o **multiempresa** — Empresa e Filial servem rede de restaurantes e carteira de CRM tão bem quanto grupo empresarial, e o modo single-tenant do [ADR-0018](ADR-0018-multiempresa-no-core-modo-single-tenant.md) atende quem não precisa deles.
- **Sai a marca**: o preset de tema deixa de se chamar pelo nome de um produto, e a migration de settings deixa de gravar nome de produto no banco.

**A raiz do monorepo passa a ser um app de desenvolvimento e vitrine** — instala o core e as extensões, hospeda a suíte de testes e serve de referência viva. Não é produto, e o nome dele é neutro.

**O produto "HT2 ERP" deixa de existir dentro do repositório.** Ele será remontado, quando houver demanda real, como um produto que instala core e extensões — do mesmo jeito que qualquer outro.

## Consequências

- As extrações deixam de ser faxina e viram **condição da abstração**: enquanto CFOP e NCM estiverem em `app/`, a plataforma não é abstrata, é um ERP com boa arquitetura.
- O nome do repositório e do pacote raiz passam a ser da plataforma (`ht2ml/platform`), não de um produto.
- Perde-se a conveniência de ter o ERP funcionando ali dentro para testar. O app de vitrine cobre isso, instalando as extensões que quiser exercitar.
- Este ADR **não** revoga o ADR-0016: instância de cliente continua sendo clone + re-origin de um produto. O que muda é que o produto não é mais este repositório.

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](ADR-0015-modulos-pacotes-composer.md)
- [ADR-0016: Instâncias por cliente via clone + re-origin](ADR-0016-instancias-por-cliente.md)
- [ADR-0017: Produto novo nasce do skeleton via Composer](ADR-0017-produto-novo-via-skeleton.md)
- [ADR-0018: Multiempresa no core, atrás de um modo single-tenant](ADR-0018-multiempresa-no-core-modo-single-tenant.md)
