# Distribuição e manutenção do HT2 ERP

> Como desenvolver o **HT2 ERP** (produto/base), distribuí-lo para clientes e
> propagar correções/melhorias de forma sustentável e com custo zero de infra.
> Decisão de arquitetura: [`ADR-0015`](architecture/adrs/ADR-0015-modulos-pacotes-composer.md).

## Visão geral

| Camada                                  | Onde vive                              | Como distribui                  | Como propaga correção                |
| --------------------------------------- | -------------------------------------- | ------------------------------- | ------------------------------------ |
| **Base/core** (HT2 ERP)                 | repo template `ht2-erp/erp-base`       | "Use this template" por cliente | `git merge upstream/main` no cliente |
| **Módulo de negócio** (RH, Financeiro…) | pacote Composer `ht2erp/modulo-{slug}` | `composer require`              | `composer update` no cliente         |

- **HT2 ERP** é o produto. **Grupo GDF** é o primeiro cliente (uma instância).
- Módulos são **iguais entre clientes** (config-driven); a customização por cliente
  vai em config publicável (`config/{slug}.php`), no banco (settings, menu) ou em
  views publicadas — **nunca** editando o core.
- **Regra de ouro:** o repositório do cliente é **aditivo**. Negócio vem de pacotes;
  personalização vai para banco/config publicada. É isso que mantém o `git merge upstream`
  sem conflitos e o `composer update` seguro.

A marca é configurável em [`config/modulos.php`](../config/modulos.php) (`vendor`,
`namespace`, `org`, `path`, `prefixo_pacote`).

---

## 1. Criar um módulo-pacote e gerar o CRUD

```bash
# 1. Cria a casca do pacote em packages/modulo-rh e registra o path repository
php artisan make:modulo-pacote Rh

# 2. Instala o pacote local (symlink) para desenvolvê-lo dentro do boilerplate
composer require "ht2erp/modulo-rh:@dev"

# 3. Gera um recurso CRUD DENTRO do pacote (namespaces HT2ERP\Rh\..., views rh::)
php artisan make:modulo Funcionario --module=Rh \
  --fields="nome:string, cargo:string, salario:money, status:enum(ativo|inativo)" --tenant

# 4. Formata, migra, publica permissões e limpa caches
./vendor/bin/pint && npx prettier --write packages/modulo-rh/
php artisan migrate && php artisan access:sync && php artisan cache:clear
```

O CRUD nasce em `packages/modulo-rh/src|database|resources|routes|tests`. A integração
ao core é automática e **sem editar o core**:

- **Rotas** → `packages/modulo-rh/routes/admin.php`, carregado dentro do grupo `/admin`
  via `App\Support\Modules\ModuleRegistry` (herda o middleware admin).
- **Permissões + menu** → `packages/modulo-rh/config/rh.php` (publicável), agregados em
  runtime a `config('access.modules')` e `config('admin-menu')` pelo ServiceProvider do
  pacote. `access:sync`, a matriz de acesso e a sidebar enxergam automaticamente.
- **Livewire + Policy** → registrados no ServiceProvider do pacote (`Livewire::component`,
  `Gate::policy`).

> Depois de instalar/atualizar um pacote, rode `php artisan cache:clear` (o menu e o
> ACL são cacheados).

---

## 2. Desenvolvimento local de um módulo

Durante o desenvolvimento o pacote vive em `packages/` do boilerplate, instalado via
**path repository** com `symlink: true` — editar `packages/modulo-rh/src/...` reflete na
hora, igual a editar `app/`. O `make:modulo-pacote` adiciona o repository ao
`composer.json` raiz:

```json
"repositories": [
  { "type": "path", "url": "packages/*", "options": { "symlink": true } }
]
```

Teste o módulo a partir do próprio boilerplate (reusa `TestCase`/`Pest.php` do app).
Só invista em testbench isolado quando o módulo precisar de CI próprio.

---

## 3. Promover e publicar um módulo (semver)

Quando o módulo estabiliza, mova-o para um repositório Git próprio e versione por tag:

```bash
# extrai a pasta como repo próprio preservando histórico (ou simplesmente copie + git init)
git subtree split --prefix=packages/modulo-rh -b modulo-rh-split
# crie ht2-erp/erp-module-rh no GitHub (privado) e empurre a branch como main
git push git@github.com:ht2-erp/erp-module-rh.git modulo-rh-split:main

# no repo do módulo, versione (semver):
git tag v1.0.0 && git push --tags
```

Convenção semver para módulos:

- **patch** (`v1.0.1`) — correção de bug, sem mudança de schema/contrato.
- **minor** (`v1.1.0`) — campo/feature retrocompatível.
- **major** (`v2.0.0`) — breaking: migração incompatível, rename de permissão/rota.

No boilerplate/cliente, troque o `path` repository por um **VCS** repository:

```json
"repositories": [
  { "type": "vcs", "url": "git@github.com:ht2-erp/erp-module-rh.git" }
]
```

```bash
composer require "ht2erp/modulo-rh:^1.0"
```

---

## 4. Novo cliente a partir do template

1. No GitHub, marque `ht2-erp/erp-base` como **Template repository**.
2. "Use this template" → `ht2-erp/cliente-acme` (privado).
3. No clone do cliente:
    ```bash
    git remote add upstream git@github.com:ht2-erp/erp-base.git
    cp .env.example .env
    ./bin/init-project.sh          # renomeia marca/slug
    ddev start && make setup
    ```
4. Instale os módulos contratados pelo cliente:
    ```bash
    composer require "ht2erp/modulo-rh:^1.0" "ht2erp/modulo-financeiro:^1.0"
    php artisan migrate --force && php artisan access:sync && php artisan cache:clear
    ```
5. Atribua as permissões dos módulos aos perfis em `/admin/acesso`.

---

## 5. Propagar uma correção do CORE (upstream merge)

```bash
# no erp-base: corrija, commite, push para main

# em cada cliente:
git fetch upstream
git merge upstream/main        # ou rebase, conforme preferência
# rode as "ações pós-merge" que o CHANGELOG indicar:
php artisan migrate --force && php artisan access:sync && php artisan cache:clear
```

Conflitos são raros **se a regra de ouro for respeitada** (cliente aditivo; negócio em
pacotes; personalização no banco/config publicada). Mantenha um `CHANGELOG.md` no
`erp-base` com as ações pós-merge de cada release.

---

## 6. Propagar uma correção de MÓDULO

```bash
# no repo do módulo: corrija, commite
git tag v1.0.1 && git push --tags

# em cada cliente que usa o módulo:
composer update ht2erp/modulo-rh
php artisan migrate --force && php artisan access:sync && php artisan cache:clear
```

Um patch chega a todos os clientes com um `composer update` por cliente.

---

## 7. Autenticação de repositórios privados

Para o Composer baixar pacotes privados (`ht2-erp/erp-module-*`) no deploy:

- **SSH deploy keys** (custo zero, recomendado p/ poucos clientes): adicione uma deploy
  key (read-only) por repo de módulo no ambiente de deploy e use URLs `git@github.com:...`.
- **Token de máquina** (`auth.json` / `COMPOSER_AUTH`): um Personal Access Token com escopo
  de leitura nos repos privados, útil em CI.

---

## 8. Quando evoluir a infraestrutura (futuro)

Hoje (solo, 2-5 clientes): **template + upstream** para a base e **VCS repositories** para
módulos bastam. Evolua quando doer:

- `git merge upstream` virar custoso (muitos clientes/conflitos) → extrair o core como
  pacote `ht2erp/erp-core` e o cliente vira um `create-project` fino.
- gerenciar `repositories` VCS em cada cliente ficar tedioso → **Satis** (estático, custo
  ~zero) ou **Private Packagist** (pago) centraliza a descoberta de pacotes.

---

## Referência rápida de comandos

| Tarefa                      | Comando                                                                             |
| --------------------------- | ----------------------------------------------------------------------------------- |
| Criar casca de pacote       | `php artisan make:modulo-pacote Rh`                                                 |
| Gerar CRUD no pacote        | `php artisan make:modulo Funcionario --module=Rh --fields="..."`                    |
| Instalar módulo (dev local) | `composer require "ht2erp/modulo-rh:@dev"`                                          |
| Publicar versão de módulo   | `git tag v1.0.0 && git push --tags`                                                 |
| Atualizar módulo no cliente | `composer update ht2erp/modulo-rh`                                                  |
| Propagar correção do core   | `git fetch upstream && git merge upstream/main`                                     |
| Pós-instalação/atualização  | `php artisan migrate --force && php artisan access:sync && php artisan cache:clear` |
