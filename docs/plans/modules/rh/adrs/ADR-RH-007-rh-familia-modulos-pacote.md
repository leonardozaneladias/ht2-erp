---
title: 'ADR-RH-007: RH como família de módulos-pacote (núcleo + satélites)'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-007: RH como família de módulos-pacote (núcleo + satélites)

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** arquitetura, modularidade, produto, rh

> Pacote `ht2ml/extensao-rh` (namespace `HT2ML\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Visão de produto em [00 §1.4](../00-prd.md); eixos satélites em [09 §8](../09-roadmap-fases.md). **Esta decisão é de produto/arquitetura de longo prazo — não amplia a Fase 1.**

## Contexto e problema

O produto mira um RH **muito completo**: além do eixo Departamento Pessoal → Folha → eSocial → Ponto (as 6 fases de [09](../09-roadmap-fases.md)), há eixos estratégicos vizinhos — **SST** (saúde e segurança), **Benefícios**, **Recrutamento & Seleção (ATS)**, **Treinamento & Desenvolvimento**, **Avaliação de Desempenho**.

A pergunta é **como organizar** essa ambição sem cair em dois extremos ruins:

- **Monólito**: um único `extensao-rh` que abarca tudo — incha, mistura domínios de cadência e equipe diferentes, e impede ativar/vender por partes conforme o cliente.
- **Fragmentação**: vários módulos independentes, cada um com **seu próprio** cadastro de pessoa — quebra o agregado-raiz `funcionarios` ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)), duplica a verdade da pessoa, e estilhaça tenancy/ACL/auditoria.

Precisa-se de uma fronteira: **o que é do núcleo** (a pessoa e o que está transacionalmente acoplado a ela) **vs. o que é satélite** (eixos com domínio próprio que apenas **referenciam** a pessoa).

## Drivers da decisão

- **Coesão do núcleo**: o funcionário é agregado-raiz fortemente acoplado ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)); o organograma/ACL ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)) e a transação evento+estado ([ADR-RH-005](ADR-RH-005-historico-eventos-imutaveis.md)) dependem dele.
- **Modularidade comercial**: ativar/vender por eixo, conforme a necessidade do cliente.
- **Manutenção independente** por eixo (equipe, cadência, release próprios).
- **Aditividade** ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)): nenhum módulo edita o core; satélites também não editam o núcleo.
- **Uma fonte da verdade da pessoa**: satélites **referenciam** `funcionarios`, não a recriam.

## Alternativas consideradas

### Alt 1 — Monólito `extensao-rh` com tudo

- Prós: um pacote só; sem gestão de dependências entre módulos.
- Contras: incha sem limite; acopla domínios de cadência distinta (folha vs. recrutamento); impede ativação modular por cliente; um bug em ATS arrisca a folha. Rejeitada.

### Alt 2 — Módulos independentes, cada um com seu cadastro de pessoa

- Prós: pacotes pequenos e desacoplados.
- Contras: **duplica/fragmenta** o agregado `funcionarios` — contraria frontalmente [ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md); N fontes da verdade da pessoa; ACL/tenancy/auditoria replicadas e divergentes. Rejeitada.

### Alt 3 — Núcleo coeso + satélites que dependem dele (escolhida)

- Prós: núcleo é a **fonte única da pessoa**; satélites reusam `funcionarios`/organograma/`anexos`; cada eixo evolui isolado; ativação por cliente; tudo aditivo ao core.
- Contras: introduz **gestão de dependências entre pacotes** (versionar `extensao-rh` como dependência; estabilizar o contrato de API/eventos do núcleo). Aceitável — é o preço da modularidade.

## Decisão

**O RH é uma família de módulos-pacote: um núcleo + satélites.**

1. **Núcleo — `ht2ml/extensao-rh`.** Dono do agregado `funcionarios` ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)), do organograma/ACL ([ADR-RH-003](ADR-RH-003-acl-hierarquica-organograma.md)) e do histórico ([ADR-RH-005](ADR-RH-005-historico-eventos-imutaveis.md)). Entrega **Departamento Pessoal, jornada/HE, fundação de folha, folha, eSocial e ponto** nas fases de [09](../09-roadmap-fases.md). É a base que os satélites consomem.
2. **Satélites — pacotes Composer próprios** (vendor `ht2ml`, ex.: `ht2ml/modulo-sst`, `ht2ml/modulo-ats`), cobrindo os eixos vizinhos ([09 §8](../09-roadmap-fases.md)). Cada satélite:
    - é **aditivo ao core** ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)) — mesma mecânica de wiring (rotas/permissões/menu/Livewire/Policy em runtime no provider);
    - declara **`ht2ml/extensao-rh` como dependência Composer** quando precisa do cadastro de pessoa; **referencia `funcionarios`/`admin_users` por FK nullable no pacote satélite** (não recria, não edita o núcleo);
    - usa **prefixo de permissão próprio** (`sst.`, `ats.`, …) para não colidir ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md));
    - integra-se ao núcleo por **eventos de domínio / pontos de extensão** (ex.: ATS reage a/dispara a admissão; SST lê o vínculo) — **nunca** editando o núcleo.
3. **Fronteira núcleo × satélite.** Fica no núcleo o que é **da pessoa/DP/folha/ponto** e transacionalmente acoplado ao agregado; vai para satélite o eixo com **domínio próprio** que apenas referencia a pessoa (SST, Benefícios, ATS, T&D, Desempenho; Onboarding/Offboarding/Clima moram no satélite de Desempenho ou próprio).
4. **SST tem prioridade entre os satélites** por ser obrigação acessória do eSocial (**S-2210/S-2220/S-2240**), conversando com a Fase 4 do núcleo ([09 §8](../09-roadmap-fases.md)).

> Alternativa de forma: esta decisão **poderia** ser uma nota no [ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md); optou-se por um ADR-RH dedicado para registrar a **fronteira específica do domínio de RH** (o que é núcleo vs. satélite) sem inflar o ADR genérico de empacotamento.

## Consequências

**Positivas:**

- Núcleo coeso e **fonte única da pessoa** ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)); satélites reusam `funcionarios`/organograma/`anexos` sem duplicar.
- **Modularidade comercial**: ativar/vender por eixo; um cliente que só quer DP+folha não carrega ATS/SST.
- Manutenção e release **por eixo**; falha em um satélite não derruba a folha.
- SST como satélite **casa com a Fase 4** (eSocial) do núcleo, sem inchá-lo.

**Negativas / a gerenciar:**

- **Gestão de dependências entre pacotes**: os satélites versionam `extensao-rh`; o **contrato do núcleo** (API pública, eventos de domínio, FKs estáveis) precisa ser **estável e versionado** (SemVer) para não quebrar satélites a cada release.
- Mais **repositórios e CI** (cada pacote tem o seu — o `extensao-rh` já é repo próprio, ver a nota de distribuição/manutenção); custo operacional de orquestração.
- Risco de um satélite **querer mudar o núcleo**: resolver por **evento/ponto de extensão**, nunca por edição — mesma disciplina que o módulo aplica sobre o core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).
- A fronteira núcleo×satélite exige **decisão consciente** por feature nova ("isto é da pessoa/DP ou é eixo próprio?") — análoga à fronteira ENUM/CATÁLOGO de [ADR-RH-002](ADR-RH-002-fronteira-enum-vs-catalogo.md).

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — mecânica de empacotamento aditivo que núcleo e satélites compartilham.
- [ADR-RH-001: Funcionário como agregado-raiz](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md) — fonte única da pessoa que os satélites referenciam.
- [ADR-RH-003: ACL hierárquica por organograma](ADR-RH-003-acl-hierarquica-organograma.md) — organograma reusado por satélites (ex.: Desempenho).
- [00 — PRD §1.4 (ambição de produto)](../00-prd.md) · [09 — Roadmap §8 (eixos satélites)](../09-roadmap-fases.md).
