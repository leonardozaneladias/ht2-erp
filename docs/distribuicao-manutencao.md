# Distribuição e manutenção da plataforma HT2ML

> Como desenvolver a **plataforma** (a base), derivar produtos e instâncias dela e
> propagar correções/melhorias de forma sustentável e com custo zero de infra.
> Decisões de arquitetura: [`ADR-0015`](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
> (módulos como pacotes), [`ADR-0016`](architecture/adrs/ADR-0016-instancias-por-cliente.md)
> (instâncias por cliente via _clone + re-origin_), [`ADR-0017`](architecture/adrs/ADR-0017-produto-novo-via-skeleton.md)
> (produto novo via skeleton) e [`ADR-0019`](architecture/adrs/ADR-0019-plataforma-abstrata-sem-produto.md)
> (a plataforma é abstrata, sem produto dentro).

## Visão geral

| Camada                            | Onde vive                                                                                      | Como distribui                            | Como propaga correção                                |
| --------------------------------- | ---------------------------------------------------------------------------------------------- | ----------------------------------------- | ---------------------------------------------------- |
| **Base** (monorepo da plataforma) | repo `leonardozaneladias/ht2-erp`: `packages/core` + `packages/extensao-*` + app hospedeiro    | é a fonte                                 | PR na base                                           |
| **Core** (`ht2ml/core`)           | `packages/core` na base; repo `leonardozaneladias/ht2ml-core` a cada release                   | Composer VCS (privado)                    | release do core + `composer update` no produto       |
| **Extensão** (`ht2ml/extensao-*`) | `packages/extensao-{nome}` na base; repo `leonardozaneladias/ht2ml-extensao-{nome}` no release | Composer VCS (privado)                    | release da extensão + `composer update` no produto   |
| **Skeleton** (`ht2ml/skeleton`)   | gerado da base por `bin/release-skeleton.sh`; repo `leonardozaneladias/ht2ml-skeleton`         | `composer create-project`                 | não propaga: é o ponto de partida de um produto novo |
| **Produto**                       | repo próprio, criado do skeleton                                                               | —                                         | `composer update ht2ml/core` + pós-update            |
| **Instância por clone**           | repo `ht2-erp-<cliente>`, clone da base                                                        | _clone + re-origin_ (`bin/new-client.sh`) | `make update-base`                                   |

- A base é uma **plataforma abstrata**: não contém produto. Produtos (o EduConecta, por
  exemplo) instalam o core por Composer.
- Os repositórios vivem hoje na conta pessoal **`leonardozaneladias`** (migram para a org
  `ht2-erp` depois via _transfer_). A conta é lida de `org` em
  [`packages/core/config/extensoes.php`](../packages/core/config/extensoes.php).
- Extensões são **iguais entre clientes** (config-driven); a customização por cliente
  vai em config publicável (`config/{chave}.php`), no banco (settings, menu) ou em
  views publicadas — **nunca** editando o pacote.

Estado dos repositórios publicados (tags medidas com `gh api` em 2026-10-25):

| Repositório                               | Última tag        |
| ----------------------------------------- | ----------------- |
| `ht2ml-core`                              | v0.5.0            |
| `ht2ml-skeleton`                          | v0.4.0            |
| `ht2ml-extensao-rh`                       | v0.1.0            |
| `ht2ml-extensao-fiscal-br`                | v0.1.0            |
| `ht2ml-extensao-documentos`               | v0.1.1            |
| `ht2ml-extensao-exemplo-demo`             | v0.1.0            |
| `ht2ml-extensao-api`                      | v0.1.1            |
| `ht2ml-extensao-ia`                       | v0.1.0            |

O repositório `erp-module-rh` (tags v0.1.0 e v0.1.1) é legado da convenção antiga de
nomes e não recebe mais releases.

### Regra de ouro — customização aditiva

Quem consome a plataforma é **aditivo**; nunca edita arquivos dela. Numa instância por
clone, é isso que mantém o `git merge upstream` sem conflitos. Num produto, é o que
mantém o `composer update` sem surpresa. Toda customização cai em um destes três baldes:

- **(a) config/banco em runtime** — Setup Wizard, settings, branding por empresa.
- **(b) arquivos novos** do produto/cliente — crie arquivos próprios; não edite arquivos da base.
- **(c) pontos de extensão** da base — eventos, config, _bindings_, canais do
  `ModuleRegistry`. Mudar o comportamento do core = a base **expõe um gancho** e o
  produto registra num arquivo próprio.

### Produto novo × instância de cliente — não confunda

| Caminho                                  | Quando usar                                                        | O que faz com o git                                                |
| ---------------------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `composer create-project ht2ml/skeleton` | **Produto novo**, com identidade própria                           | Repo novo, sem histórico da base; o core chega por Composer        |
| clone + re-origin + `bin/new-client.sh`  | **Instância de um produto** que continua recebendo updates da base | **Preserva o histórico**; só configura remotes/.env/DDEV (aditivo) |

`bin/init-project.sh` (clonar a base e renomear para um produto novo) está **aposentado**
pelo ADR-0017. O script continua no repositório, mas não é o caminho: um produto que
clonou a base nasce com a identidade dela em `.env`, `composer.json` e no banco.
Produto novo: [`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md).

A marca de pacotes é configurável em
[`packages/core/config/extensoes.php`](../packages/core/config/extensoes.php)
(`vendor`, `namespace`, `org`, `path`, `prefixo_pacote`, `idioma_inflexao`).

---

## 1. Criar um módulo-pacote e gerar o CRUD

```bash
# 1. Cria a casca do pacote em packages/extensao-rh e registra o path repository
php artisan make:modulo rh

# 2. Instala o pacote local (symlink) para desenvolvê-lo dentro do monorepo
composer require "ht2ml/extensao-rh:@dev"

# 3. Gera um recurso CRUD DENTRO do pacote (namespaces HT2ML\Rh\..., views rh::)
php artisan make:recurso Funcionario --modulo=rh \
  --fields="nome:string, cargo:string, salario:money, status:enum(ativo|inativo)" --tenant

# 4. Formata, migra, publica permissões, limpa caches e confere
npx prettier --write packages/extensao-rh/resources/views/
php artisan migrate && php artisan access:sync && php artisan cache:clear
php artisan ht2ml:doutor
```

O CRUD nasce em `packages/extensao-rh/src|database|resources|routes|tests`. A integração
ao core é automática e **sem editar o core**:

- **Rotas** → `packages/extensao-rh/routes/admin.php`, carregado dentro do grupo `/admin`
  via `HT2ML\Core\Support\Modules\ModuleRegistry` (herda o middleware admin).
- **Permissões + menu** → **derivados** da entrada do recurso em `recursos` de
  `packages/extensao-rh/config/rh.php` (publicável). O ServiceProvider do pacote só
  declara `ModuleRegistry::modulo('rh')->deConfig('rh')`; o `ModuloBuilder` calcula as
  permissões `rh.funcionarios.*` e o item de menu, e
  `ModuleRegistry::aplicarContribuicoes()` os aplica uma vez, no boot do core.
  `access:sync`, a matriz de acesso e a sidebar enxergam automaticamente.
- **Livewire + Policy** → registrados no ServiceProvider do pacote (`Livewire::component`,
  `Gate::policy`).

> Depois de instalar/atualizar um pacote, rode `php artisan cache:clear` (o menu e o
> ACL são cacheados).

Guia completo de extensão (biblioteca × módulo, canais de rota, seeders, testes, listas
que precisam ser editadas): [`guias/criar-extensao.md`](guias/criar-extensao.md).

---

## 2. Desenvolvimento local de um pacote

O pacote vive em `packages/` **versionado no monorepo da base** (ver ADR-0016), instalado
via **path repository** com `symlink: true` — editar `packages/extensao-rh/src/...`
reflete na hora, igual a editar `app/`. O `make:modulo` adiciona o repository ao
`composer.json` raiz:

```json
"repositories": [
  { "type": "path", "url": "packages/*", "options": { "symlink": true } }
]
```

No monorepo, o `composer.json` raiz pede cada pacote como `@dev`. Os testes de um
pacote vivem em `packages/<pacote>/tests` e rodam na suíte `Extensoes`, reusando o
`TestCase` e o `tests/Pest.php` do app. Ver [`testes-e-guardas.md`](testes-e-guardas.md).

---

## 3. Cortar um release (semver) — `release-module.sh`

Quando o pacote estabiliza, a pasta é extraída para um repositório Git próprio e
versionada por tag. O `bin/release-module.sh` automatiza o `git subtree split` + push +
tag + release no GitHub.

**O nome do repo é derivado do `composer.json` do pacote**: `ht2ml/core` vira
`<org>/ht2ml-core`, `ht2ml/extensao-rh` vira `<org>/ht2ml-extensao-rh`. Nada de convenção
hardcoded — a anterior (`erp-module-{slug}`, `packages/modulo-{slug}`) ficou para trás quando
os pacotes passaram a se chamar `ht2ml/*`, e o script deixou de achar qualquer coisa.

```bash
# lista os pacotes e o nome de cada um
./bin/release-module.sh

# cria o repo 1x (privado), antes do primeiro release
gh repo create leonardozaneladias/ht2ml-extensao-rh --private

# ensaia — mostra o que faria, incluindo as notas do release
./bin/release-module.sh extensao-rh v0.2.0 --dry-run

# corta o release (subtree split + push + tag + release no GitHub)
make release-modulo pacote=extensao-rh versao=v0.2.0
```

O script recusa publicar quando: a versão já existe como tag no repo remoto, há mudanças não
commitadas no prefixo (o split usaria só o commitado, e o release sairia diferente do que você
vê), ou o `composer.json` do pacote está incompleto. As notas saem dos commits que tocaram o
prefixo desde a última tag publicada.

A ordem dos releases (core → extensões → skeleton), a política de versão e o que conferir
depois estão no runbook: [`devops/release.md`](devops/release.md).

Convenção semver para pacotes:

- **patch** (`v1.0.1`) — correção de bug, sem mudança de schema/contrato.
- **minor** (`v1.1.0`) — campo/feature retrocompatível.
- **major** (`v2.0.0`) — breaking: migração incompatível, rename de permissão/rota.

Enquanto a série for `0.x`, o Composer trata a **minor** como quebra (`^0.4` não aceita
`0.5`); o runbook detalha.

### Release do skeleton

O skeleton é **gerado** da base, não mantido à parte:

```bash
./bin/release-skeleton.sh v0.4.0 --dry-run
./bin/release-skeleton.sh v0.4.0
```

O script copia o app hospedeiro (lista de inclusão curta), troca `phpunit.xml`,
`phpstan.neon` e o `.github/` pelos de `stubs/skeleton/`, reescreve o `composer.json`
(repositórios VCS dos pacotes, só `ht2ml/core` no `require`) e empurra para
`<org>/ht2ml-skeleton`. A restrição do core é **a última tag publicada do core**, lida
com `git ls-remote` — por isso o core é publicado antes. Sem tag no core, o script para.

> Visto em 2026-10-25: a v0.3.3 do skeleton (2026-08-24) pedia `ht2ml/core: ^0.1.3` com o
> core já em v0.4.0 — o skeleton não tinha sido cortado de novo. A v0.4.0, cortada logo
> após o core v0.5.0, pede `^0.5.0`. Corte o skeleton sempre que o core subir de minor.

---

## 4. Instância de cliente — _clone + re-origin_ (não "Use this template")

> Por que não template/fork: "Use this template" squasha o histórico (o 1º `git merge upstream`
> quebra com `unrelated histories`) e fork na mesma conta não é permitido. O **clone preserva o
> histórico comum** → merge limpo. Detalhes no [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md).

```bash
# 1. Crie o repo do cliente (privado)
gh repo create leonardozaneladias/ht2-erp-<cliente> --private

# 2. Clone a base e troque os remotes (origin = cliente, upstream = base)
git clone git@github.com:leonardozaneladias/ht2-erp.git ht2-erp-<cliente>
cd ht2-erp-<cliente>
git remote rename origin upstream
git remote add origin git@github.com:leonardozaneladias/ht2-erp-<cliente>.git

# 3. Provisiona o cliente de forma ADITIVA (remotes/.env/DDEV/opt-out de push)
make new-client            # ou: ./bin/new-client.sh
#    → pergunta nome/slug/e-mail, cria .env, .ddev/config.local.yaml (name=<slug>)
#      e .husky/allow-main-push (opt-out local do pre-push). NÃO apaga git.

# 4. Sobe e configura (setup-client = SEM dados demo → o Setup Wizard roda)
git push -u origin main
ddev start && make setup-client
#    → acesse /admin/setup (Setup Wizard cria empresa/branding/admin do cliente)
```

As extensões **vêm embutidas** no clone (a base é um monorepo e já versiona
`packages/extensao-*`); a instância não precisa de credencial de Composer para elas.

---

## 5. Propagar uma correção do CORE

**Para uma instância por clone:**

```bash
# na base (ht2-erp): corrija numa branch, abra PR, faça merge na main

# em cada instância:
make update-base
#   = git fetch upstream && git merge --no-edit upstream/main
#     && php artisan migrate --force && php artisan access:sync && php artisan cache:clear
#     (usa `ddev artisan` automaticamente se em DDEV)
php artisan ht2ml:doutor   # o script não roda; rode à mão
```

Conflitos são raros **se a regra de ouro for respeitada** (cliente aditivo; negócio no
pacote; personalização no banco/config publicada). Em conflito, `update-base` para e
instrui. Mantenha o `CHANGELOG.md` da base com as **ações pós-merge** de cada release.

**Para um produto via skeleton:** release do core ([`devops/release.md`](devops/release.md)) e,
no produto:

```bash
composer update ht2ml/core
php artisan migrate --force && php artisan access:sync && php artisan cache:clear
php artisan ht2ml:doutor
```

---

## 6. Propagar uma correção de EXTENSÃO

- **Instância por clone:** a correção está no monorepo da base, então **desce junto** com
  `make update-base`.
- **Produto via skeleton:** corte o release da extensão
  (`make release-modulo pacote=extensao-rh versao=v0.1.1`) e, em cada produto que a usa:

    ```bash
    composer update ht2ml/extensao-rh
    php artisan migrate --force && php artisan access:sync && php artisan cache:clear
    php artisan ht2ml:doutor
    ```

---

## 7. PR de volta (instância → base) — subir uma melhoria genérica

Descobriu na instância uma melhoria **genérica** (serve a todos)? Suba via PR **bifurcando de
`upstream/main`**, para não arrastar nenhum commit de customização do cliente.

Os repositórios de instância **não são forks** do `ht2-erp` no GitHub (issue #50): são clones
independentes. Um PR do repo do cliente para a base não existe; a branch precisa estar
**dentro** do repositório da base.

```bash
git fetch upstream
git switch -c fix/algo-generico upstream/main
# ... commit SÓ do que é genérico (nada específico do cliente) ...
git push upstream HEAD:refs/heads/fix/algo-generico
gh pr create --repo leonardozaneladias/ht2-erp --base main --head fix/algo-generico
```

Depois do merge na base, a melhoria volta à instância pelo caminho normal: `make update-base`.
O mesmo fluxo, com mais contexto: [`fluxo-desenvolvimento.md`](fluxo-desenvolvimento.md) §4.

Num **produto via skeleton** não há `upstream`: a mudança genérica é feita num clone da base,
com PR normal, e chega ao produto pelo próximo release.

---

## 8. Autenticação de repositórios privados

Os pacotes `ht2ml/*` e o skeleton são privados e não estão no Packagist. Quem consome por
Composer precisa de credencial:

- **Máquina de desenvolvimento:** chave SSH com acesso aos repositórios. O `composer.json`
  do skeleton declara os pacotes por `git@github.com:...`.
- **CI do produto:** o segredo `COMPOSER_TOKEN` (token do GitHub com **Contents: Read-only**
  nos repositórios `ht2ml-*`). Ele entra em **dois lugares** do workflow: na reescrita de URL
  do git (`insteadOf`) e em `COMPOSER_AUTH`. E fica em **dois cofres**: Actions e Dependabot.
  Detalhes em `stubs/skeleton/.github/README.md`, que viaja com o skeleton.
- **Deploy:** SSH deploy key (read-only) por repositório, ou o mesmo token via `auth.json` /
  `COMPOSER_AUTH`.

Numa instância por clone as extensões vêm no `git merge`, e não há nada a autenticar além do
acesso ao repositório da base.

---

## 9. Quando evoluir a infraestrutura

- **Instância por clone com conjuntos de extensões distintos** → o cliente passa a consumir
  pacotes por Composer VCS em vez dos embutidos: configure o **merge driver `ours`** para
  `composer.json`/`composer.lock` (evita que `git merge upstream` sobrescreva as versões do
  cliente). Passo a passo no [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md).
- `git merge upstream` virar custoso (muitos clientes/conflitos) → migrar a instância para
  produto via skeleton, que já existe.
- Gerenciar `repositories` VCS em cada produto ficar tedioso → **Satis** (estático, custo
  ~zero) ou **Private Packagist** (pago) centraliza a descoberta de pacotes.

---

## Referência rápida de comandos

| Tarefa                           | Comando                                                                                                         |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Criar módulo (casca do pacote)   | `php artisan make:modulo rh`                                                                                    |
| Gerar recurso no módulo          | `php artisan make:recurso Funcionario --modulo=rh --fields="..."`                                               |
| Cortar release de pacote         | `make release-modulo pacote=extensao-rh versao=v0.1.0`                                                          |
| Cortar release do skeleton       | `./bin/release-skeleton.sh v0.4.0`                                                                              |
| Produto novo                     | ver [`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md)                                            |
| Nova instância (clone+re-origin) | `make new-client` (após clone + re-origin)                                                                      |
| Setup inicial da instância       | `make setup-client` (sem dados demo → o Setup Wizard cria empresa/admin)                                        |
| Trazer update da base            | `make update-base` (na instância)                                                                               |
| PR de volta (genérico)           | `git push upstream HEAD:refs/heads/<branch>` + `gh pr create --repo leonardozaneladias/ht2-erp --head <branch>` |
| Pós-merge/instalação             | `php artisan migrate --force && php artisan access:sync && php artisan cache:clear && php artisan ht2ml:doutor` |
