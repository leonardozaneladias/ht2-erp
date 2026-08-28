# Fluxo de desenvolvimento — Base ↔ Cliente

> Guia prático de como trabalhar no produto base e numa instância de cliente,
> e como direcionar o assistente (Claude Code) para o destino certo.
> Complementa [ADR-0015](architecture/adrs/ADR-0015-modulos-pacotes-composer.md),
> [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md) e
> [distribuicao-manutencao.md](distribuicao-manutencao.md).
>
> Neste clone, o cliente é o **GDF** (`ht2-erp-gdf`); troque pelos nomes da sua
> instância onde aplicável.

---

## 1. A estrutura (não é "mesmo repo, branch diferente")

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

| Remote     | Repositório         | Papel                                                      |
| ---------- | ------------------- | ---------------------------------------------------------- |
| `origin`   | `ht2-erp-<cliente>` | **Cliente** — base + customizações aditivas                |
| `upstream` | `ht2-erp`           | **Base / produto** — core + `packages/modulo-*` (monorepo) |

A "continuidade" **não** é uma branch separada: é o remote `upstream`. As correções
do produto descem para o cliente via **merge de `upstream/main`** (comando `make update-base`).

---

## 2. A decisão central: base, cliente ou ambos?

Antes de qualquer mudança, a pergunta é: **isso serve a todos os clientes (base) ou só a esta instância?**

| Natureza da mudança                                                                             | Destino     |
| ----------------------------------------------------------------------------------------------- | ----------- |
| Bug/melhoria do **core ou framework**, vale para qualquer cliente                               | **base**    |
| Logo, cor, nome, empresa, dados ou **config específica do cliente**; arquivo novo só do cliente | **cliente** |
| Bug **genérico** que o cliente precisa **agora** (corrige no produto e aplica já)               | **ambos**   |

**Regra de ouro (customização aditiva):** no cliente, **nunca edite o core**
(models, migrations existentes, rotas base). Customize por: (a) configuração em
banco/runtime (Setup Wizard, settings, branding por empresa), (b) **arquivos novos**
do cliente, (c) ganchos que a base expõe (eventos, bindings). Respeitar isso mantém
o `make update-base` sem conflitos.

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

## 4. Fluxo B — Mudança no base (produto, vale para todos)

```bash
git fetch upstream
git switch -c fix/algo upstream/main          # ⚠️ parte do BASE, não de origin/main
# ... só o código genérico (zero customização do cliente) ...
git push upstream fix/algo                     # envia a branch para o repo BASE
gh pr create --repo leonardozaneladias/ht2-erp --base main --head fix/algo
# após o merge no base, traga para o cliente:
make update-base
```

Exemplo: correção de comportamento de autenticação/redirect que afeta qualquer cliente.

> **Por que `git push upstream` (e não `origin`)?** O repo do cliente não é um _fork_
> do `ht2-erp` no GitHub (é um clone independente), então um PR entre eles precisa que
> a branch esteja **no próprio repo base**. Por isso a branch genérica é empurrada para
> `upstream` e o PR é aberto dentro do `ht2-erp`.

> **Cuidado decisivo:** bifurque de `upstream/main`. Se partir de `origin/main`, o PR
> arrasta commits de customização do cliente para o produto.

## 5. Fluxo C — Ambos (corrige no base e aplica no cliente já)

1. Faça o **Fluxo B** (PR no base e merge).
2. Traga para o cliente: `make update-base`.

Se o cliente **não puder esperar** o ciclo do base, é aceitável aplicar a correção
também numa branch do cliente (Fluxo A) — sabendo que, no próximo `make update-base`,
o mesmo fix vindo do base pode gerar um **conflito trivial** (linhas idênticas),
resolvido aceitando qualquer lado.

---

## 6. Como direcionar o assistente (Claude Code)

Para o assistente saber o destino sem ambiguidade, use uma destas formas:

| Você diz…                                                               | Ele faz                                                     |
| ----------------------------------------------------------------------- | ----------------------------------------------------------- |
| **"no base"** / "no produto" / "genérico" / "pra todos os clientes"     | branch de `upstream/main` → PR no **`ht2-erp`** (Fluxo B)   |
| **"no cliente"** / "só nesta instância" / "específico" / "customização" | branch de `origin/main` → fica no repo do cliente (Fluxo A) |
| **"ambos"** / "corrige no base e aplica no cliente já"                  | PR no base **+** `make update-base` (Fluxo C)               |

**Atalho:** prefixe o pedido com `[base]`, `[cliente]` (ou `[gdf]`) ou `[ambos]`.

**Sem tag, o assistente infere pela natureza e confirma antes de agir:**

- Corrige comportamento do core/framework ou vale para qualquer cliente → propõe **base**.
- Logo, cor, nome, empresa, dados ou config específicos do cliente, ou arquivo novo só dele → propõe **cliente**.
- Bug genérico que o cliente precisa já → propõe **ambos**.

Exemplos:

- `[base] adiciona rate limit no login` → vai para o produto.
- `[cliente] muda a cor primária para #000` → só nesta instância.
- `corrige o N+1 na listagem de usuários` (sem tag) → infere **base** (genérico) e confirma.

---

## 7. Módulos reutilizáveis

Módulos de negócio que servem a vários clientes vivem **no base**, em `packages/modulo-*`
(ver [ADR-0015](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)):

```bash
# no BASE:
php artisan make:modulo rh
php artisan make:recurso Funcionario --modulo=rh --fields="..." --tenant
make release-modulo slug=rh versao=v0.1.0   # quando estabilizar (subtree split + tag)
```

Hoje (fase 1) os módulos descem **embutidos** ao cliente no `make update-base`.
Quando houver um 2º cliente com módulos contratados distintos, migra-se para
consumo via Composer (fase 2 do ADR-0015).

---

## 8. Comandos de referência

| Comando                             | O que faz                                                                  |
| ----------------------------------- | -------------------------------------------------------------------------- |
| `make update-base`                  | (no cliente) traz correções do base: `git merge upstream/main` + pós-merge |
| `make new-client`                   | provisiona uma nova instância de cliente (aditivo)                         |
| `make setup-client`                 | setup inicial do cliente (sem dados demo → Setup Wizard)                   |
| `make release-modulo slug= versao=` | corta release de um módulo do base para repo próprio                       |
| `git fetch upstream`                | atualiza a referência local do base                                        |

**Proteções:** o hook `pre-push` bloqueia push direto na `main`; no clone do cliente
há o opt-out `.husky/allow-main-push` (1 dev empurra direto). Commits seguem
Conventional Commits (validados por `commitlint`); escopos válidos incluem
`admin, auth, infra, ui, models, docs` — entre outros.

---

## 9. Referências

- [ADR-0016 — Instâncias por cliente](architecture/adrs/ADR-0016-instancias-por-cliente.md)
- [ADR-0015 — Módulos como pacotes Composer](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
- [Distribuição e manutenção](distribuicao-manutencao.md)
- [Convenções de devops/branching](devops/conventions.md)
