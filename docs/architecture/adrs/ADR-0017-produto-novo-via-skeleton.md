---
title: 'ADR-0017: Produto novo nasce do skeleton via Composer, não do clone da base'
version: 1.0.0
date: 2026-08-22
status: accepted
---

# ADR-0017: Produto novo nasce do skeleton via Composer, não do clone da base

**Status:** Accepted | **Data:** 2026-08-22 | **Decisores:** HT2ML | **Tags:** arquitetura, distribuição, produtos, composer

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md)): **core** são os pacotes `ht2ml/*`; **produto** é uma aplicação que instala o core (o HT2 ERP é um deles); **instância** é um deploy de um produto para um cliente.

## Contexto e problema

A base era derivada por dois caminhos, documentados no README: `bin/init-project.sh` para **produto novo** (clona, renomeia marca/banco/filas e oferece cortar o histórico) e clone + re-origin + `bin/new-client.sh` para **instância de cliente** ([ADR-0016](ADR-0016-instancias-por-cliente.md)). O segundo tem ADR; o primeiro nunca teve — e é o que falhou na prática.

Ao publicar um produto novo a partir da base, ele nasceu com a identidade do produto anterior em toda parte: `APP_NAME`, `APP_URL`, `.ddev/config.yaml`, o `vendor`/`namespace` em `config/modulos.php`, o enum `ThemePreset::HT2_ERP` (que é o preset _default_), o `LICENSE`, os nomes dos pacotes em `packages/` — e uma migration de settings que **grava** o nome do produto anterior no banco.

O `init-project.sh` cobre seis pontos superficiais, algo em torno de 20% do total. O resto é manual, invisível e silenciosamente esquecível: ninguém percebe que o banco do produto novo tem o nome do produto velho até alguém abrir a tela de aparência.

## Drivers da decisão

- Um produto novo não deve herdar a identidade de outro produto, nem por um instante.
- Rebranding não pode depender de disciplina humana nem de um script que persegue strings.
- O [ADR-0015](ADR-0015-modulos-pacotes-composer.md) já previa consumo por Composer ("embutido agora → Composer VCS depois"). Falta executar, não decidir.
- Dois desenvolvedores: não há orçamento para manter dois mecanismos de derivação vivos.

## Alternativas consideradas

### Alt 1: completar o `init-project.sh` até cobrir 100% dos pontos de marca

Rejeitada. Persegue strings para sempre: cada extensão nova acrescenta pontos a perseguir, e o CI passaria a ter como trabalho provar continuamente que nada escapou. É consertar o sintoma.

### Alt 2: template repo do GitHub

Já rejeitada pelo ADR-0016 — "Use this template" faz squash do histórico e mata o `git merge upstream` logo no primeiro update.

### Alt 3: produto novo nasce do skeleton (escolhida)

`composer create-project ht2ml/skeleton meu-produto`, e o core chega por `composer require ht2ml/core`. É o padrão do próprio Laravel, onde `laravel/laravel` é o esqueleto e `laravel/framework` é o núcleo.

## Decisão

**Produto novo nasce de `composer create-project ht2ml/skeleton`.** O core e as extensões chegam por Composer, a partir de repositórios VCS privados.

**Instância de cliente continua por clone + re-origin**, exatamente como o ADR-0016 estabeleceu. Aquele ADR **permanece válido, com escopo estreitado**: ele trata de instâncias de um produto, não de produtos novos. Onde ele diz "base distribuível", leia-se "produto distribuível".

**`bin/init-project.sh` é aposentado.**

## Consequências

- O rebranding deixa de existir como problema em vez de ser resolvido: um produto que nunca clonou a base nunca teve o nome dela no `composer.json`, no `.env` ou no banco.
- Esta decisão **depende da extração dos pacotes**: sem `ht2ml/core` publicável, não há skeleton que funcione. Ela só entra em vigor quando a extração estiver feita.
- O job de CI de bootstrap muda de alvo: em vez de provar que o `init-project.sh` limpou tudo, prova que `composer create-project ht2ml/skeleton` gera um app que migra, testa e builda.
- Quem já derivou um produto pelo caminho antigo continua com o rebranding manual pendente — a decisão não retroage.

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](ADR-0015-modulos-pacotes-composer.md)
- [ADR-0016: Instâncias por cliente via clone + re-origin](ADR-0016-instancias-por-cliente.md)
- [CONTEXT-MAP.md](../../../CONTEXT-MAP.md) — linguagem da plataforma
