---
title: 'ADR-0016: Instâncias por cliente via clone + re-origin'
version: 1.0.0
date: 2026-06-17
status: accepted
---

# ADR-0016: Instâncias por cliente via clone + re-origin

**Status:** Accepted | **Data:** 2026-06-17 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** arquitetura, distribuição, instâncias, git

> Nomenclatura: **HT2 ERP** é o produto/base distribuível; **Grupo GDF** é o primeiro cliente (uma instância). Hoje os repositórios vivem na conta pessoal `leonardozaneladias` (`ht2-erp`, `ht2-erp-gdf`, `erp-module-{slug}`); migram para a org `ht2-erp` no futuro via _transfer_.

## Contexto e problema

O [ADR-0015](ADR-0015-modulos-pacotes-composer.md) decidiu **como** os módulos de negócio são empacotados e distribuídos (pacotes Composer `ht2erp/modulo-{slug}`, base distribuível, propagação por `git merge upstream` + `composer update`). Faltava decidir **como instanciar um ERP por cliente** de forma que, depois de instanciado, ainda seja possível **trazer atualizações** da base e dos módulos em **dois sentidos**:

- melhorias genéricas da base/módulos **descem** para os clientes;
- melhorias genéricas descobertas **num** cliente **sobem** (via PR) para a base;
- customizações específicas do cliente ficam **só nele**.

O ADR-0015 chegou a citar "**template repo + `upstream` remote**" para a base. Na prática isso tem dois furos:

1. **"Use this template" do GitHub squasha o histórico** (cria um repo com história não relacionada). O primeiro `git merge upstream/main` falha com `refusing to merge unrelated histories`, e o vínculo de atualização morre logo no início.
2. **Forkar não é opção** quando base e cliente vivem na mesma conta (o GitHub não permite fork do próprio repo na mesma conta), e fork público vazaria código.

Era preciso um mecanismo que **preserve o histórico comum** (para `git merge upstream` limpo) sem depender de fork nem de template.

## Drivers da decisão

- `git merge upstream/main` precisa funcionar **limpo** desde o primeiro update (histórico comum).
- PR de volta **não pode vazar customização** do cliente para a base.
- Custo zero de infra; operação solo, 2-5 clientes.
- Manter o cliente atualizável sem dor de merge ao longo do tempo (regra de ouro de customização aditiva).
- Adiar infra de Composer privado até existir um gatilho real para ela.

## Alternativas consideradas

### Alt 1: "Use this template" (citado no ADR-0015)

- Prós: 1 clique no GitHub.
- Contras: histórico não relacionado → `git merge upstream` quebra (`unrelated histories`); precisaria de `--allow-unrelated-histories` + resolução manual recorrente. Reprovado.

### Alt 2: Fork na mesma conta

- Contras: GitHub não permite fork do próprio repo na mesma conta; fork em outra conta/visibilidade pública vaza código. Reprovado.

### Alt 3: Clone + re-origin (escolhida)

- `git clone` da base preserva **todo o histórico**; depois renomeia-se o remote: `origin` → cliente, `upstream` → base.
- Prós: `git merge upstream/main` limpo (mesma raiz de histórico); PR de volta bifurca de `upstream/main` (não arrasta commits de customização); sem fork, sem template, sem infra nova.
- Contras: dois passos manuais no bootstrap (re-origin) — automatizados por `bin/new-client.sh`.

## Decisão

**Cada cliente é uma instância criada por _clone + re-origin_ da base.**

```bash
git clone git@github.com:leonardozaneladias/ht2-erp.git cliente-gdf
cd cliente-gdf
git remote rename origin upstream                                  # base = upstream
git remote add origin git@github.com:leonardozaneladias/ht2-erp-gdf.git  # cliente = origin
./bin/new-client.sh         # provisiona o cliente de forma ADITIVA (não apaga git)
git push -u origin main
```

| Repo (conta `leonardozaneladias`) | Papel                                                                                                                    | Remotes                          |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | -------------------------------- |
| `ht2-erp`                         | **Base/produto** — monorepo: core + `packages/modulo-*`. Onde todo dev genérico acontece.                                | —                                |
| `ht2-erp-gdf`                     | **Cliente GDF** — base + customizações aditivas.                                                                         | `origin`=gdf, `upstream`=ht2-erp |
| `erp-module-rh`                   | **Repo do módulo RH** (downstream) — alimentado por `git subtree split` no release; consumido via Composer só na fase 2. | —                                |

### A base é um monorepo

`packages/modulo-*` é **versionado na base** (refina o ADR-0015, que tratava `packages/` como local-only/gitignored). Consequências:

- O módulo desce ao cliente **embutido**, junto do `git merge upstream` — zero infra de Composer privado para o 1º cliente.
- O release de um módulo extrai sua pasta para o repo próprio com `git subtree split --prefix=packages/modulo-<slug>` (ver `bin/release-module.sh`), preservando a base como fonte de verdade.

### Fluxo bidirecional de atualização

- **Correção/melhoria (base ou módulo)** → commit na base `ht2-erp` → em cada cliente: `make update-base`
  (`git fetch upstream && git merge upstream/main` + `migrate --force && access:sync && cache:clear`).
- **Melhoria genérica descoberta no cliente** → branch a partir de `upstream/main` (não de `origin/main`, para não arrastar customização) → `gh pr create --repo …/ht2-erp` → merge na base → desce via `make update-base`.

```bash
# no cliente, subir uma melhoria genérica SEM vazar customização:
git fetch upstream
git switch -c fix/algo-generico upstream/main
# ... commit só do que é genérico ...
git push -u origin fix/algo-generico
gh pr create --repo leonardozaneladias/ht2-erp --base main
```

### Regra de ouro — customização aditiva (mantém o merge sem dor)

Toda customização do cliente é **aditiva**; nunca edita arquivos da base:

- **(a) config/banco em runtime** — Setup Wizard, settings, branding por empresa.
- **(b) arquivos novos** do cliente — nunca editar arquivos da base; criar arquivos próprios.
- **(c) pontos de extensão** da base — eventos, config, _bindings_. Mudar o comportamento do core = a base **expõe um gancho** e o cliente registra num arquivo próprio.

### Modelo de consumo de módulo: embutido agora → Composer VCS depois

- **Agora (1º cliente):** módulo **embutido** (vem no `git merge upstream`). `composer.json`/`composer.lock` do cliente são **idênticos** à base (módulo via path repository com symlink), então o merge não conflita neles.
- **Gatilho para ativar Composer:** surgir o **2º cliente** ou clientes com **conjuntos de módulos contratados distintos** (cada um requer só o que comprou).
- **O que muda ao ativar:**
    - publicar `erp-module-{slug}` (já preparado por `bin/release-module.sh`) e trocar o `path` repository por um **`vcs`** repository no `composer.json` do cliente;
    - autenticação por **deploy key SSH** (read-only) por repo de módulo no ambiente de deploy (custo zero p/ poucos clientes);
    - como `composer.json`/`composer.lock` do cliente passam a **divergir** da base (VCS repo + versões contratadas), configurar **merge driver `ours`** para eles, evitando que `git merge upstream` os sobrescreva:

        ```gitattributes
        # .gitattributes (no cliente, ao ativar Composer)
        composer.json merge=ours
        composer.lock merge=ours
        ```

        ```bash
        git config merge.ours.driver true
        ```

## Consequências

**Positivas:** atualização bidirecional real e de baixo atrito; `git merge upstream` limpo desde o 1º update; PR de volta sem vazar customização; 1º cliente sem nenhuma infra de Composer privado; caminho de evolução para Composer já desenhado e com gatilho explícito.

**Negativas / a gerenciar:** o bootstrap exige re-origin (automatizado por `new-client.sh`); a base passa a ser um monorepo (módulos versionados nela — refino do ADR-0015); ao ativar Composer, lembrar do merge driver `ours` e das deploy keys. O `bin/init-project.sh` (derivar um **produto novo**, que apaga o vínculo de git) **não** serve para instanciar cliente — usar `bin/new-client.sh`.

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](ADR-0015-modulos-pacotes-composer.md) — este ADR **refina** (não revoga) o 0015: corrige "template repo" → "clone + re-origin" e versiona `packages/modulo-*` na base.
- [`docs/distribuicao-manutencao.md`](../../distribuicao-manutencao.md) — runbook operacional (criar cliente, propagar correções, PR de volta, release de módulo).
- `bin/new-client.sh`, `bin/release-module.sh`, `bin/update-from-upstream.sh` — tooling que materializa esta decisão.
