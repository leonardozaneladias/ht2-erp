---
title: Release de pacotes e do skeleton
date: 2026-10-25
status: vigente
---

# Release de pacotes e do skeleton

Runbook para publicar `ht2ml/core`, as extensões `ht2ml/extensao-*` e o
`ht2ml/skeleton` a partir do monorepo da base. Nada disso roda no CI: é feito à mão,
por quem tem permissão de push nos repositórios da conta `leonardozaneladias`.

Os scripts são `bin/release-module.sh` e `bin/release-skeleton.sh`. Este documento
segue o que eles fazem de fato (lidos em 2026-10-25); na dúvida, o cabeçalho de cada
script é a fonte.

## O modelo

A base é a fonte de verdade. Cada pacote tem um repositório próprio que **só recebe
releases**: o script extrai `packages/<dir>` com `git subtree split`, empurra o
resultado como `main` do repositório do pacote e cria a tag. Ninguém commita direto
nesses repositórios.

| Pacote                  | Diretório na base          | Repositório                                |
| ----------------------- | -------------------------- | ------------------------------------------ |
| `ht2ml/core`            | `packages/core`            | `leonardozaneladias/ht2ml-core`            |
| `ht2ml/extensao-<nome>` | `packages/extensao-<nome>` | `leonardozaneladias/ht2ml-extensao-<nome>` |
| `ht2ml/skeleton`        | gerado do app hospedeiro   | `leonardozaneladias/ht2ml-skeleton`        |

O nome do repositório é derivado do `name` do `composer.json` do pacote (`/` vira `-`),
e a conta vem de `org` em `packages/core/config/extensoes.php`.

## Ordem: core → extensões → skeleton

1. **Core primeiro.** As extensões do tipo `modulo` declaram `ht2ml/core`, e o skeleton
   lê a **última tag publicada do core** para escrever a restrição do produto. Publicar o
   skeleton antes do core faz o produto novo pedir uma versão velha.
2. **Extensões depois**, cada uma com a versão dela. Elas versionam independente do core.
3. **Skeleton por último.** Ele declara os repositórios VCS de todos os pacotes da lista
   em `bin/release-skeleton.sh`; esses repositórios precisam existir e ter tag.

## Pré-requisitos

- **Branch `main` atualizada e limpa.** O `release-module.sh` faz o split do `HEAD`
  atual e recusa se houver mudança não commitada no prefixo. O `release-skeleton.sh`
  **copia os arquivos do diretório de trabalho**, não do histórico: qualquer mudança
  local entra no skeleton. Rode os dois de um `main` recém-atualizado.
- **Acesso por SSH** aos repositórios (`git@github.com:<org>/...`).
- **`php`** no PATH (os scripts leem JSON e a config com `php -r`). `composer` e `gh`
  são opcionais: sem `composer`, o `composer validate` é pulado; sem `gh`, a release do
  GitHub não é criada (a tag é).
- **O repositório do pacote existe.** Pacote novo precisa dele antes do primeiro release:

    ```bash
    gh repo create leonardozaneladias/ht2ml-extensao-<nome> --private
    ```

    Se o `COMPOSER_TOKEN` dos produtos for um token _fine-grained_ com repositórios
    listados um a um, acrescente o repositório novo a ele — senão o CI do produto não
    consegue instalar o pacote.

## Pacote: `bin/release-module.sh`

```
./bin/release-module.sh <dir-do-pacote> <versao> [--dry-run]
```

- `<dir-do-pacote>` é o nome do diretório em `packages/` (`core`, `extensao-rh`, …).
- `<versao>` precisa casar com `^v[0-9]+\.[0-9]+\.[0-9]+$` — `v0.5.0`, nunca `0.5.0`.
- Sem argumentos, lista os pacotes disponíveis e o `name` de cada um.

```bash
# 1. ensaia: valida, faz o split local e mostra os pushes e as notas
./bin/release-module.sh core v0.5.0 --dry-run

# 2. publica
./bin/release-module.sh core v0.5.0
# ou, pelo Makefile (sem --dry-run):
make release-modulo pacote=core versao=v0.5.0
```

O `make release-modulo` só repassa `pacote` e `versao`; para ensaiar, chame o script
direto com `--dry-run`.

O que o script faz, em ordem:

1. valida a versão, a existência de `packages/<dir>/composer.json`, que o prefixo está
   versionado e que não há mudança não commitada nele;
2. recusa se a tag já existe no repositório remoto (`git ls-remote`) — releases são
   imutáveis;
3. confere o `composer.json` do pacote (`name`, `autoload.psr-4`, `require`) e roda
   `composer validate --no-check-publish` se houver `composer`;
4. `git subtree split -q --prefix=packages/<dir>`;
5. monta as notas com os commits que tocaram o prefixo desde a última tag publicada
   (busca a tag antiga no repositório do pacote e compara com o split novo);
6. `git push --no-verify <repo> <split>:refs/heads/main` e
   `git push --no-verify <repo> <split>:refs/tags/<versao>`;
7. `gh release create` com as notas, se `gh` existir.

O `--no-verify` é intencional: o `pre-push` da base bloqueia `refs/heads/main` em
qualquer remote, e aqui o destino é o repositório do pacote.

## Skeleton: `bin/release-skeleton.sh`

```
./bin/release-skeleton.sh <versao> [--dry-run]
```

```bash
./bin/release-skeleton.sh v0.4.0 --dry-run   # monta a árvore e mostra o require
./bin/release-skeleton.sh v0.4.0
```

O que ele faz:

1. copia do diretório de trabalho só a lista de inclusão: `app bootstrap config database
public resources routes storage lang artisan composer.json package.json vite.config.js
phpunit.xml pint.json phpstan.neon eslint.config.js` e os dotfiles de editor/git/env;
2. sobrescreve com os arquivos de `stubs/skeleton/` (`phpunit.xml`, `phpstan.neon`) e
   copia `stubs/skeleton/.github` como o CI do produto;
3. tira `export-ignore` do `.gitattributes` (senão o `create-project` baixaria o tarball
   sem o `.github`);
4. deixa em `tests/` só `TestCase.php`, `Pest.php` e um `SmokeTest` que pede `/admin/login`;
5. lê a última tag de `<org>/ht2ml-core` e **para** se não houver nenhuma;
6. reescreve o `composer.json`: `name` `ht2ml/skeleton`, `type` `project`, repositórios
   VCS (`no-api`) para o core e as extensões da lista, **só** `ht2ml/core: ^<última tag>`
   no `require`, e `preferred-install` `source` para `ht2ml/*`;
7. remove `composer.lock` e `package-lock.json`, esvazia `storage/` mantendo os
   `.gitignore`, gera um `README.md`;
8. recusa se a tag já existe; senão faz um commit único e empurra com
   `git push --force` para `main` e a tag anotada.

O `main` do skeleton é **reescrito** a cada release (um commit só, sem histórico).
Quem precisa de uma versão antiga usa a tag.

> Visto em 2026-10-25: a v0.3.3 do skeleton pedia `ht2ml/core: ^0.1.3` com o core já em
> v0.4.0, porque o skeleton não foi cortado de novo depois dos releases do core. A
> v0.4.0, cortada logo depois do core v0.5.0, pede `^0.5.0`. Corte o skeleton sempre
> que o core subir de minor.

## Versão e breaking change

Semver, com `v` na tag:

- **patch** (`v1.0.1`) — correção de bug, sem mudança de schema nem de contrato.
- **minor** (`v1.1.0`) — funcionalidade retrocompatível (campo novo, canal novo, comando novo).
- **major** (`v2.0.0`) — breaking: migração incompatível, renomear permissão, rota,
  chave de config ou classe pública, remover método da superfície que as extensões usam
  ([`superficie-do-core.md`](../superficie-do-core.md)).

**Enquanto a série for `0.x`**, o Composer trata a minor como breaking: `^0.4` aceita
`0.4.9` e recusa `0.5.0`. Na prática:

- mudança que exige ação no produto (migração incompatível, rename, remoção) **sobe a
  minor** (`v0.4.x` → `v0.5.0`);
- correção e adição compatível **sobem a patch** — assim chegam a quem tem `^0.4` com um
  `composer update`.

Toda versão que exige ação no produto registra os passos no `CHANGELOG.md` da base
(seção da versão) e nas notas do release: o que rodar além de
`migrate` + `access:sync` + `ht2ml:doutor`, e o que muda de nome.

Renomear permissão ou chave de menu é migração de dado em produção: invalida permissões
já atribuídas a perfis e personalizações de menu gravadas. É por isso que
`extensao-fiscal-br` e `extensao-exemplo-demo` mantêm chaves sem prefixo de módulo.

## Depois de publicar

```bash
# a tag está no repositório do pacote?
git ls-remote --tags git@github.com:leonardozaneladias/ht2ml-core.git | tail -3

# a release do GitHub foi criada? (pacotes; o skeleton só tem tag)
gh release view v0.5.0 --repo leonardozaneladias/ht2ml-core

# o main do repositório do pacote aponta para o split?
git ls-remote git@github.com:leonardozaneladias/ht2ml-core.git refs/heads/main
```

Num produto (o EduConecta é o primeiro), confirme que a versão nova resolve e que o pós-update fecha:

```bash
composer update ht2ml/core          # ou composer require ht2ml/core:^0.5 para minor nova em 0.x
composer show ht2ml/core | head -5
php artisan migrate && php artisan access:sync && php artisan ht2ml:doutor
```

Depois de um release do skeleton, o teste de verdade é criar um produto num diretório
vazio ([`guias/produto-via-skeleton.md`](../guias/produto-via-skeleton.md)) e ver
`/admin/login` responder.

## Problemas conhecidos

- **"A tag vX já existe"** — releases são imutáveis. Escolha a próxima versão; não
  apague tag publicada, porque o `composer.lock` de algum produto pode apontar para ela.
- **Notas "Primeiro release." num pacote que já tinha tags** — era o bug antigo de
  calcular a faixa no monorepo; o script atual busca a tag no repositório do pacote. Se o
  `git fetch` da tag falhar, ele avisa e usa o histórico inteiro do prefixo.
- **`php` do host é um shim de container** — os scripts passam caminhos absolutos ao
  `php` e montam o skeleton em `.skeleton-build/` dentro do repositório (não em `/tmp`)
  justamente por isso.
