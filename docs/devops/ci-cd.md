---
title: CI
date: 2026-10-25
status: vigente
---

# CI

O CI roda no GitHub Actions, num único workflow: [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml). Não existe workflow de deploy. Nada é publicado nem implantado a partir do Actions.

> A versão anterior deste documento (rascunho de abril, com deploy por Envoy e Docker que nunca existiu) está em [historico/devops/ci-cd.md](../historico/devops/ci-cd.md).

## Gatilhos

- `push` para `main` e `develop`.
- `pull_request` com destino `main` e `develop`.

Não há branch `develop` no remoto hoje; o gatilho só fica lá. Branches de trabalho (`feat/`, `fix/`, `chore/`, `docs/`, `refactor/`) rodam pelo PR, não pelo push. Listá-las nos dois gatilhos faria a mesma commit rodar duas vezes.

## Concorrência

```yaml
concurrency:
    group: ${{ github.workflow }}-${{ github.head_ref || github.ref }}
    cancel-in-progress: ${{ github.ref != 'refs/heads/main' }}
```

Um push novo cancela o run anterior da mesma branch. A `main` é a exceção: a proteção da branch não exige que o PR esteja atualizado antes do merge, então o run pós-merge é o único que testa a combinação real de dois PRs. Ele nunca é cancelado.

## Ambiente

- PHP 8.4 (`shivammathur/setup-php`), sem cobertura.
- Node 22 (`actions/setup-node`), com cache de npm.
- Banco SQLite `:memory:` nos testes. O PostgreSQL 16 só existe no DDEV local.
- Cache de `vendor/` por hash do `composer.lock`.

## Jobs

Os quatro jobs rodam em paralelo.

### `quality` — Qualidade

1. `./vendor/bin/pint --test` — formatação PHP.
2. `./vendor/bin/phpstan analyse` — análise estática (nível 6, erros no formato do GitHub).
3. `npm run lint:js:check` — ESLint em `resources/js/`.
4. `composer audit` — **informativo** (`continue-on-error: true`). Mostra advisories sem reprovar o build; o Dependabot abre os PRs de correção.
5. **Cada pacote declara o que usa** — roda o `shipmonk/composer-dependency-analyser` contra o `composer.json` de cada pacote em `packages/` (`core`, `extensao-rh`, `extensao-fiscal-br`, `extensao-exemplo-demo`, `extensao-documentos`), com a config [`composer-dependency-analyser.php`](../../composer-dependency-analyser.php). Reprova se um pacote usa classe de dependência que não declara. A lista de pacotes é manual, no próprio `ci.yml`: pacote novo precisa entrar no laço.
6. `npx prettier --check` — formatação de JS/TS/CSS/JSON em `resources/` e de JSON/MD/YAML da raiz.

### `core-sozinho` — Core sozinho num Laravel limpo

É o guard A4. O monorepo nunca exercita o caminho de um produto que instala só o `ht2ml/core`; este job exercita.

1. `composer create-project laravel/laravel /tmp/prova`.
2. Instala só `ht2ml/core:@dev`, por path repository apontado para `packages/*`.
3. `migrate`, `access:sync`, `ht2ml:doutor` (SQLite em arquivo).
4. Confere a superfície: mais de 50 rotas `admin.*`, `config('access.modules')` e `config('admin-menu')` não vazios.
5. Primeiro dia de um produto: `make:modulo sonda`, `composer require ht2ml/extensao-sonda:@dev`, `make:recurso Leitura --modulo=sonda`, migra, sincroniza e exige a rota `admin.sonda.leituras.index` e 6 permissões `sonda.*`.
6. Prova que `make:recurso` sem `--modulo` falha antes de escrever arquivos e manda usar `make:modulo`.

### `tests` — Testes (Pest)

1. Copia `.env.example`, gera `APP_KEY`.
2. `php artisan ht2ml:doutor`.
3. `php artisan test --compact --testsuite=Unit,Feature,Extensoes,Arch`.

### `browser` — Testes de browser (Pest + Playwright)

1. `npm ci`, instala o Chromium do Playwright (com cache, sem `--with-deps`).
2. `npm run build` — compila os assets com Vite.
3. `./vendor/bin/pest --group=browser`.

## Dependabot

[`.github/dependabot.yml`](../../.github/dependabot.yml) abre PRs semanais em três ecossistemas:

| Ecossistema      | Prefixo do commit | Agrupamento                         |
| ---------------- | ----------------- | ----------------------------------- |
| `composer`       | `chore(deps)`     | tudo num grupo `php-deps`           |
| `npm`            | `chore(deps)`     | tudo num grupo `js-deps`            |
| `github-actions` | `ci(deps)`        | sem grupo                           |

No npm, o major do `cropperjs` é ignorado de propósito (ver [tools-and-packages.md](tools-and-packages.md)).

## Rodar o mesmo localmente

```bash
make quality   # Pint + PHPStan + ESLint + Prettier + testes
make test      # suítes Unit, Feature, Extensoes e Arch, em paralelo
make doutor    # ht2ml:doutor
```

O analisador de dependências e o job `core-sozinho` não têm alvo no Makefile; rode os comandos do `ci.yml` à mão.
