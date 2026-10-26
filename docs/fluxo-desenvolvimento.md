# Fluxo de desenvolvimento — Base, instância e produto

> Guia prático de como trabalhar na base da plataforma, numa instância de cliente
> e num produto que instala o core por Composer, e como direcionar o assistente
> (Claude Code) para o destino certo.
> Complementa [ADR-0015](architecture/adrs/ADR-0015-modulos-pacotes-composer.md),
> [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md),
> [ADR-0017](architecture/adrs/ADR-0017-produto-novo-via-skeleton.md) e
> [distribuicao-manutencao.md](distribuicao-manutencao.md).
>
> Este repositório (`leonardozaneladias/ht2-erp`) é a **base**: o monorepo com
> `packages/core` e `packages/extensao-*`. Num clone dele, `origin` aponta para a
> própria base e não há `upstream`. As seções sobre `upstream` valem para o clone de
> uma instância de cliente.

---

## 0. As três topologias

Há três formas de um repositório se relacionar com a base, e o fluxo muda com cada uma.

| Topologia                   | Como nasce                                          | Como recebe atualização                                                   | Remotes                               |
| --------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------- | ------------------------------------- |
| **Base** (este repositório) | —                                                   | é a fonte                                                                 | `origin` → `ht2-erp`                  |
| **Instância por clone**     | `git clone` da base + re-origin + `make new-client` | `make update-base` (`git merge upstream/main` + pós-merge)                | `origin` → cliente, `upstream` → base |
| **Produto via skeleton**    | `composer create-project ht2ml/skeleton`            | `composer update ht2ml/core` → `migrate` → `access:sync` → `ht2ml:doutor` | só `origin` → produto                 |

A **instância por clone** é o desenho do [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md),
e as seções 1 a 6 abaixo tratam dela. O **produto via skeleton** é o desenho do
[ADR-0017](architecture/adrs/ADR-0017-produto-novo-via-skeleton.md) e é o caminho
de todo produto novo; ele tem a seção 7.

---

## 1. Instância por clone: não é "mesmo repo, branch diferente"

São **dois repositórios GitHub distintos**, conectados localmente por dois remotes.
Eles **compartilham o histórico** (a instância foi criada por `git clone` + re-origin),
e é isso que permite `git merge` limpo entre eles.

```
   GitHub                                   Máquina local (pasta do cliente)
┌────────────────────┐                     ┌──────────────────────────────────────┐
│ ht2-erp     (BASE) │ ◀── upstream ────── │  .git com 2 remotes:                  │
│  core + packages/  │                     │   • origin   → ht2-erp-<cliente>      │
└────────────────────┘                     │   • upstream → ht2-erp  (base)        │
┌────────────────────┐                     │                                      │
│ ht2-erp-<cliente>  │ ◀── origin ──────── │  branches: main, feat/..., fix/...    │
│  base + custom     │                     └──────────────────────────────────────┘
└────────────────────┘
```

| Remote     | Repositório         | Papel                                                         |
| ---------- | ------------------- | ------------------------------------------------------------- |
| `origin`   | `ht2-erp-<cliente>` | **Cliente** — base + customizações aditivas                   |
| `upstream` | `ht2-erp`           | **Base** — `packages/core` + `packages/extensao-*` (monorepo) |

A "continuidade" **não** é uma branch separada: é o remote `upstream`. As correções
da base descem para o cliente via **merge de `upstream/main`** (comando `make update-base`).

---

## 2. A decisão central: base, cliente ou ambos?

Antes de qualquer mudança, a pergunta é: **isso serve a todos os clientes (base) ou só a esta instância?**

| Natureza da mudança                                                                             | Destino     |
| ----------------------------------------------------------------------------------------------- | ----------- |
| Bug/melhoria do **core ou framework**, vale para qualquer cliente                               | **base**    |
| Logo, cor, nome, empresa, dados ou **config específica do cliente**; arquivo novo só do cliente | **cliente** |
| Bug **genérico** que o cliente precisa **agora** (corrige na base e aplica já)                  | **ambos**   |

**Regra de ouro (customização aditiva):** no cliente, **nunca edite o core**
(models, migrations existentes, rotas base). Customize por: (a) configuração em
banco/runtime (Setup Wizard, settings, branding por empresa), (b) **arquivos novos**
do cliente, (c) ganchos que a base expõe (eventos, bindings, configs publicáveis).
Respeitar isso mantém o `make update-base` sem conflitos.

---

## 3. Fluxo A — Mudança no cliente (específica da instância)

```bash
git switch -c feat/algo origin/main          # parte do CLIENTE
# ... mudança ADITIVA (arquivo novo / config / gancho da base) ...
git push -u origin feat/algo
gh pr create --repo leonardozaneladias/ht2-erp-<cliente> --base main --head feat/algo
# merge na main do cliente (PR acima, ou direto — cliente solo tem opt-out de push)
```

Exemplo: identidade visual + cadastro da empresa do cliente.

## 4. Fluxo B — Mudança na base (vale para todos)

```bash
git fetch upstream
git switch -c fix/algo upstream/main          # ⚠️ parte da BASE, não de origin/main
# ... só o código genérico (zero customização do cliente) ...
git push upstream fix/algo                     # envia a branch para o repo BASE
gh pr create --repo leonardozaneladias/ht2-erp --base main --head fix/algo
# após o merge na base, traga para o cliente:
make update-base
```

Exemplo: correção de comportamento de autenticação/redirect que afeta qualquer cliente.

> **Por que `git push upstream` (e não `origin`)?** O repo do cliente não é um _fork_
> do `ht2-erp` no GitHub (é um clone independente), então um PR entre eles precisa que
> a branch esteja **no próprio repo base**. Por isso a branch genérica é empurrada para
> `upstream` e o PR é aberto dentro do `ht2-erp`. Contexto: issue #50.

> **Cuidado decisivo:** bifurque de `upstream/main`. Se partir de `origin/main`, o PR
> arrasta commits de customização do cliente para a base.

Trabalhando **no próprio clone da base**, o fluxo é o mesmo sem o desvio: branch de
`origin/main`, `git push -u origin <branch>` e PR em `leonardozaneladias/ht2-erp`.

## 5. Fluxo C — Ambos (corrige na base e aplica no cliente já)

1. Faça o **Fluxo B** (PR na base e merge).
2. Traga para o cliente: `make update-base`.

Se o cliente **não puder esperar** o ciclo da base, é aceitável aplicar a correção
também numa branch do cliente (Fluxo A) — sabendo que, no próximo `make update-base`,
o mesmo fix vindo da base pode gerar um **conflito trivial** (linhas idênticas),
resolvido aceitando qualquer lado.

---

## 6. Como direcionar o assistente (Claude Code)

Para o assistente saber o destino sem ambiguidade, use uma destas formas:

| Você diz…                                                               | Ele faz                                                     |
| ----------------------------------------------------------------------- | ----------------------------------------------------------- |
| **"na base"** / "no produto" / "genérico" / "pra todos os clientes"     | branch de `upstream/main` → PR no **`ht2-erp`** (Fluxo B)   |
| **"no cliente"** / "só nesta instância" / "específico" / "customização" | branch de `origin/main` → fica no repo do cliente (Fluxo A) |
| **"ambos"** / "corrige na base e aplica no cliente já"                  | PR na base **+** `make update-base` (Fluxo C)               |

**Atalho:** prefixe o pedido com `[base]`, `[cliente]` ou `[ambos]`.

**Sem tag, o assistente infere pela natureza e confirma antes de agir:**

- Corrige comportamento do core/framework ou vale para qualquer cliente → propõe **base**.
- Logo, cor, nome, empresa, dados ou config específicos do cliente, ou arquivo novo só dele → propõe **cliente**.
- Bug genérico que o cliente precisa já → propõe **ambos**.

Exemplos:

- `[base] adiciona rate limit no login` → vai para a base.
- `[cliente] muda a cor primária para #000` → só nesta instância.
- `corrige o N+1 na listagem de usuários` (sem tag) → infere **base** (genérico) e confirma.

---

## 7. Produto via skeleton (sem `upstream`)

Um produto que nasceu de `composer create-project ht2ml/skeleton` **não tem histórico
comum com a base** e não tem `upstream`. A plataforma chega por Composer
(`vendor/ht2ml/core`, `vendor/ht2ml/extensao-*`), e o produto nunca edita esses
arquivos. Passo a passo de criação: [`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md).

Atualizar o core é atualizar uma dependência:

```bash
composer update ht2ml/core          # dentro da restrição do composer.json (ex.: ^0.4)
php artisan migrate                 # migrations novas do pacote
php artisan access:sync             # permissões novas do catálogo
php artisan ht2ml:doutor            # as contribuições dos módulos ainda fecham?
```

Uma versão **minor** nova do core em série `0.x` (de `0.4` para `0.5`) não entra com
`composer update`: em Composer, `^0.4` não aceita `0.5`. É preciso
`composer require ht2ml/core:^0.5` e ler as notas do release antes.

A mudança que o produto precisa no core segue o **Fluxo B adaptado**: clone da base,
branch de `origin/main`, PR em `leonardozaneladias/ht2-erp`, release do core
([`devops/release.md`](devops/release.md)) e então `composer require`/`update` no
produto. Não há atalho de merge — e é isso que impede o produto de divergir do core.

---

## 8. Extensões e módulos reutilizáveis

Módulos de negócio e bibliotecas que servem a vários produtos vivem **na base**, em
`packages/extensao-*` (ver [ADR-0015](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
e [`guias/criar-extensao.md`](guias/criar-extensao.md)):

```bash
# na BASE:
php artisan make:modulo rh
composer require "ht2ml/extensao-rh:@dev"
php artisan make:recurso Funcionario --modulo=rh --fields="..." --tenant
make release-modulo pacote=extensao-rh versao=v0.2.0   # subtree split + tag
```

`pacote=` é o nome do diretório em `packages/`. O repositório de destino é derivado do
`composer.json` do pacote (`ht2ml/extensao-rh` → `leonardozaneladias/ht2ml-extensao-rh`).

O consumo por Composer já está em uso: `ht2ml/core` e as extensões têm repositórios
próprios com tags (`ht2ml-core` até v0.5.0, medido em 2026-10-25), e o skeleton os
declara como repositórios VCS. Numa **instância por clone**, as extensões continuam
descendo embutidas no `make update-base`, porque o monorepo inteiro vem no merge.
Runbook de publicação: [`devops/release.md`](devops/release.md).

---

## 9. Comandos de referência

| Comando                               | O que faz                                                                 |
| ------------------------------------- | ------------------------------------------------------------------------- |
| `make update-base`                    | (instância) traz correções da base: `git merge upstream/main` + pós-merge |
| `make new-client`                     | provisiona uma nova instância de cliente (aditivo)                        |
| `make setup-client`                   | setup inicial da instância (sem dados demo → Setup Wizard)                |
| `make release-modulo pacote= versao=` | corta release de um pacote de `packages/` para o repositório próprio      |
| `make doutor`                         | roda `ht2ml:doutor` no container                                          |
| `git fetch upstream`                  | (instância) atualiza a referência local da base                           |

Todos os comandos artisan da plataforma: [`comandos.md`](comandos.md).

**Proteções:** o hook `.husky/pre-push` bloqueia push direto na `main`; no clone da
instância há o opt-out `.husky/allow-main-push` (1 dev empurra direto).

**Commits** seguem Conventional Commits, validados pelo `commitlint`
(`commitlint.config.cjs`). O escopo é **obrigatório** e a lista é fechada:

| Grupo                      | Escopos                                                                                                               |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Plataforma e áreas do core | `plataforma`, `core`, `admin`, `auth`, `acesso`, `ui`, `grid`, `export`, `models`, `referencia`, `gerador`, `guardas` |
| Extensões                  | `api`, `ia`, `webhook`, `rh`, `fiscal`                                                                                |
| Distribuição e operação    | `skeleton`, `distribuicao`, `infra`, `ci`, `deps`                                                                     |
| Documentação e ferramentas | `docs`, `arquitetura`, `skills`                                                                                       |

Tipos aceitos: `feat`, `fix`, `refactor`, `docs`, `style`, `test`, `chore`, `perf`,
`ci`, `revert`. Cabeçalho com no máximo 72 caracteres e sem ponto final.
Migration, schema e seeder usam o escopo `models` — nunca `database`.

---

## 10. Referências

- [ADR-0017 — Produto novo via skeleton](architecture/adrs/ADR-0017-produto-novo-via-skeleton.md)
- [ADR-0016 — Instâncias por cliente](architecture/adrs/ADR-0016-instancias-por-cliente.md)
- [ADR-0015 — Módulos como pacotes Composer](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
- [Distribuição e manutenção](distribuicao-manutencao.md)
- [Produto via skeleton](guias/produto-via-skeleton.md)
- [Runbook de release](devops/release.md)
- [Convenções de devops/branching](devops/conventions.md)
