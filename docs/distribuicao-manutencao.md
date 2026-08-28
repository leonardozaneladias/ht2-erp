# Distribuição e manutenção do HT2 ERP

> Como desenvolver o **HT2 ERP** (produto/base), instanciá-lo por cliente e
> propagar correções/melhorias de forma sustentável e com custo zero de infra.
> Decisões de arquitetura: [`ADR-0015`](architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
> (módulos como pacotes) e [`ADR-0016`](architecture/adrs/ADR-0016-instancias-por-cliente.md)
> (instâncias por cliente via _clone + re-origin_).

## Visão geral

| Camada                                  | Onde vive                                                                     | Como distribui (1º cliente)               | Como propaga correção                                     |
| --------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------- | --------------------------------------------------------- |
| **Base/core** (HT2 ERP)                 | repo `leonardozaneladias/ht2-erp` — monorepo: core + `packages/modulo-*`      | _clone + re-origin_ (`bin/new-client.sh`) | `make update-base` no cliente                             |
| **Módulo de negócio** (RH, Financeiro…) | `packages/modulo-{slug}` na base; repo próprio `erp-module-{slug}` no release | **embutido** no clone/merge (agora)       | embutido: `make update-base`; Composer: `composer update` |

- **HT2 ERP** é o produto. **Grupo GDF** é o primeiro cliente (uma instância).
- Os repositórios vivem hoje na conta pessoal **`leonardozaneladias`** (migram para a org `ht2-erp` depois via _transfer_).
- Módulos são **iguais entre clientes** (config-driven); a customização por cliente
  vai em config publicável (`config/{slug}.php`), no banco (settings, menu) ou em
  views publicadas — **nunca** editando o core.

### Regra de ouro — customização aditiva

O repositório do cliente é **aditivo**; nunca edita arquivos da base. É isso que mantém o
`git merge upstream` sem conflitos. Toda customização cai em um destes três baldes:

- **(a) config/banco em runtime** — Setup Wizard, settings, branding por empresa.
- **(b) arquivos novos** do cliente — crie arquivos próprios; não edite arquivos da base.
- **(c) pontos de extensão** da base — eventos, config, _bindings_. Mudar o comportamento do
  core = a base **expõe um gancho** e o cliente registra num arquivo próprio.

### `init-project.sh` (produto novo) × `new-client.sh` (cliente) — não confunda

| Script                | Quando usar                                                      | O que faz com o git                                                |
| --------------------- | ---------------------------------------------------------------- | ------------------------------------------------------------------ |
| `bin/init-project.sh` | **Derivar um PRODUTO NOVO** que diverge da base **para sempre**  | Oferece **reinicializar o git history** (corta o vínculo)          |
| `bin/new-client.sh`   | **Instanciar um CLIENTE** que continua recebendo updates da base | **Preserva o histórico**; só configura remotes/.env/DDEV (aditivo) |

> Para instanciar um cliente, use **`new-client.sh`**. O `init-project.sh` apaga o vínculo
> `upstream` e impede `git merge upstream` — serve só para nascer um produto independente.

A marca de pacotes é configurável em [`config/extensoes.php`](../config/extensoes.php) (`vendor`,
`namespace`, `org`, `path`, `prefixo_pacote`).

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

# 4. Formata, migra, publica permissões e limpa caches
./vendor/bin/pint && npx prettier --write packages/extensao-rh/
php artisan migrate && php artisan access:sync && php artisan cache:clear
```

O CRUD nasce em `packages/extensao-rh/src|database|resources|routes|tests`. A integração
ao core é automática e **sem editar o core**:

- **Rotas** → `packages/extensao-rh/routes/admin.php`, carregado dentro do grupo `/admin`
  via `HT2ML\Core\Support\Modules\ModuleRegistry` (herda o middleware admin).
- **Permissões + menu** → `packages/extensao-rh/config/rh.php` (publicável), agregados em
  runtime a `config('access.modules')` e `config('admin-menu')` pelo ServiceProvider do
  pacote. `access:sync`, a matriz de acesso e a sidebar enxergam automaticamente.
- **Livewire + Policy** → registrados no ServiceProvider do pacote (`Livewire::component`,
  `Gate::policy`).

> Depois de instalar/atualizar um pacote, rode `php artisan cache:clear` (o menu e o
> ACL são cacheados).

---

## 2. Desenvolvimento local de um módulo

O pacote vive em `packages/` **versionado no monorepo da base** (core + `packages/modulo-*`,
ver ADR-0016), instalado via **path repository** com `symlink: true` — editar
`packages/extensao-rh/src/...` reflete na hora, igual a editar `app/`. O `make:modulo`
adiciona o repository ao `composer.json` raiz:

```json
"repositories": [
  { "type": "path", "url": "packages/*", "options": { "symlink": true } }
]
```

Teste o módulo a partir do próprio monorepo (reusa `TestCase`/`Pest.php` do app).
Só invista em testbench isolado quando o módulo precisar de CI próprio.

---

## 3. Cortar um release de módulo (semver) — `release-module.sh`

Quando o pacote estabiliza, extraia a pasta para um repositório Git próprio e versione por tag.
O `bin/release-module.sh` automatiza o `git subtree split` + push + tag.

**O nome do repo é derivado do `composer.json` do pacote**: `ht2ml/core` vira
`<org>/ht2ml-core`, `ht2ml/extensao-rh` vira `<org>/ht2ml-extensao-rh`. Nada de convenção
hardcoded — a anterior (`erp-module-{slug}`, `packages/modulo-{slug}`) ficou para trás quando
os pacotes passaram a se chamar `ht2ml/*`, e o script deixou de achar qualquer coisa.

```bash
# lista os pacotes e o repo que cada um teria
./bin/release-module.sh

# cria o repo 1x (privado)
gh repo create leonardozaneladias/ht2ml-core --private

# ensaia — mostra o que faria, incluindo as notas do release
./bin/release-module.sh core v0.1.0 --dry-run

# corta o release (subtree split + push + tag + release no GitHub)
make release-modulo pacote=core versao=v0.1.0
```

O script recusa publicar quando: a versão já existe como tag no repo remoto, há mudanças não
commitadas no prefixo (o split usaria só o commitado, e o release sairia diferente do que você
vê), ou o `composer.json` do pacote está incompleto. As notas saem dos commits que tocaram o
prefixo desde a última tag publicada.

Convenção semver para módulos:

- **patch** (`v1.0.1`) — correção de bug, sem mudança de schema/contrato.
- **minor** (`v1.1.0`) — campo/feature retrocompatível.
- **major** (`v2.0.0`) — breaking: migração incompatível, rename de permissão/rota.

O release deixa o `ht2ml-extensao-rh` populado e versionado. O **consumo por Composer** (trocar o
`path` por um `vcs` repository e `composer require "ht2ml/extensao-rh:^1.0"`) fica **latente**
até o gatilho da seção 9.

---

## 4. Novo cliente — _clone + re-origin_ (não "Use this template")

> Por que não template/fork: "Use this template" squasha o histórico (o 1º `git merge upstream`
> quebra com `unrelated histories`) e fork na mesma conta não é permitido. O **clone preserva o
> histórico comum** → merge limpo. Detalhes no [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md).

```bash
# 1. Crie o repo do cliente (privado)
gh repo create leonardozaneladias/ht2-erp-gdf --private

# 2. Clone a base e troque os remotes (origin = cliente, upstream = base)
git clone git@github.com:leonardozaneladias/ht2-erp.git cliente-gdf
cd cliente-gdf
git remote rename origin upstream
git remote add origin git@github.com:leonardozaneladias/ht2-erp-gdf.git

# 3. Provisiona o cliente de forma ADITIVA (remotes/.env/DDEV/opt-out de push)
make new-client            # ou: ./bin/new-client.sh
#    → pergunta nome/slug/e-mail, cria .env, .ddev/config.local.yaml (name=<slug>)
#      e .husky/allow-main-push (opt-out local do pre-push). NÃO apaga git.

# 4. Sobe e configura (setup-client = SEM dados demo → o Setup Wizard roda)
git push -u origin main
ddev start && make setup-client
#    → acesse /admin/setup (Setup Wizard cria empresa/branding/admin do cliente)
```

O **módulo de negócio vem embutido** no clone (a base é um monorepo e já versiona
`packages/modulo-*`); não há infra de Composer privado para o 1º cliente. Para ativar o consumo
por Composer (2º cliente / módulos contratados distintos), ver a seção 9.

---

## 5. Propagar uma correção do CORE (base → clientes)

```bash
# na base (ht2-erp): corrija numa branch, abra PR, faça merge na main

# em cada cliente:
make update-base
#   = git fetch upstream && git merge --no-edit upstream/main
#     && php artisan migrate --force && php artisan access:sync && php artisan cache:clear
#     (usa `ddev artisan` automaticamente se em DDEV)
```

Conflitos são raros **se a regra de ouro for respeitada** (cliente aditivo; negócio no
monorepo/pacote; personalização no banco/config publicada). Em conflito, `update-base` para e
instrui. Mantenha o `CHANGELOG.md` da base com as **ações pós-merge** de cada release.

---

## 6. Propagar uma correção de MÓDULO

- **Fase embutida (agora):** a correção do módulo está no monorepo da base, então **desce junto**
  com `make update-base` — nada de `composer update` ainda.
- **Fase Composer (depois):** após cortar o release (`make release-modulo slug=rh versao=v1.0.1`):

    ```bash
    # em cada cliente que consome o módulo via Composer:
    composer update ht2ml/extensao-rh
    php artisan migrate --force && php artisan access:sync && php artisan cache:clear
    ```

---

## 7. PR de volta (cliente → base) — subir uma melhoria genérica

Descobriu no cliente uma melhoria **genérica** (serve a todos)? Suba via PR **bifurcando de
`upstream/main`**, para não arrastar nenhum commit de customização do cliente:

```bash
git fetch upstream
git switch -c fix/algo-generico upstream/main
# ... commit SÓ do que é genérico (nada específico do cliente) ...
git push -u origin fix/algo-generico
gh pr create --repo leonardozaneladias/ht2-erp --base main
```

Depois do merge na base, a melhoria volta ao cliente pelo caminho normal: `make update-base`.

---

## 8. Autenticação de repositórios privados (só na fase Composer)

Enquanto o módulo é **embutido**, não há nada a autenticar (vem no `git merge`). Ao ativar o
consumo por Composer (seção 9), para o Composer baixar `erp-module-*` privados no deploy:

- **SSH deploy keys** (custo zero, recomendado p/ poucos clientes): uma deploy key (read-only)
  por repo de módulo no ambiente de deploy, com URLs `git@github.com:...`.
- **Token de máquina** (`auth.json` / `COMPOSER_AUTH`): um PAT com escopo de leitura nos repos
  privados, útil em CI.

---

## 9. Quando ativar o Composer / evoluir a infraestrutura

Hoje (solo, 1 cliente): **clone + re-origin** para a base e **módulo embutido** bastam. Evolua
quando houver gatilho real:

- **2º cliente** ou clientes com **conjuntos de módulos contratados distintos** → ative o consumo
  por **Composer VCS**: publique `erp-module-{slug}` (seção 3), troque o `path` por um `vcs`
  repository no `composer.json` do cliente e configure o **merge driver `ours`** para
  `composer.json`/`composer.lock` (evita que `git merge upstream` sobrescreva as versões
  contratadas do cliente). Passo-a-passo no [ADR-0016](architecture/adrs/ADR-0016-instancias-por-cliente.md).
- `git merge upstream` virar custoso (muitos clientes/conflitos) → extrair o core como pacote
  `ht2ml/erp-core` e o cliente vira um `create-project` fino.
- gerenciar `repositories` VCS em cada cliente ficar tedioso → **Satis** (estático, custo ~zero)
  ou **Private Packagist** (pago) centraliza a descoberta de pacotes.

---

## Referência rápida de comandos

| Tarefa                         | Comando                                                                             |
| ------------------------------ | ----------------------------------------------------------------------------------- |
| Criar módulo (casca do pacote) | `php artisan make:modulo rh`                                                        |
| Gerar recurso no módulo        | `php artisan make:recurso Funcionario --modulo=rh --fields="..."`                   |
| Cortar release de módulo       | `make release-modulo slug=rh versao=v0.1.0`                                         |
| Novo cliente (clone+re-origin) | `make new-client` (após clone + re-origin)                                          |
| Setup inicial do cliente       | `make setup-client` (sem dados demo → o Setup Wizard cria empresa/admin)            |
| Trazer update da base          | `make update-base` (no cliente)                                                     |
| PR de volta (genérico)         | `git switch -c fix/x upstream/main && gh pr create --repo …/ht2-erp`                |
| Pós-merge/instalação           | `php artisan migrate --force && php artisan access:sync && php artisan cache:clear` |
