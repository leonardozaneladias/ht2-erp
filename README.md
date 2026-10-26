# HT2ML Platform

**Plataforma de backoffice administrativo** — a base sobre a qual se constrói ERP, sistema de gestão de restaurantes, CRM ou qualquer outro sistema administrativo. Desktop-first e server-side, em **Laravel 13 + Livewire 4 + Inspinia (Tailwind 4)**, com guard `admin`, RBAC de dois níveis (perfis globais + por empresa), auditoria, filas (Horizon) e monitoramento (Pulse).

> **A plataforma é abstrata: nenhum produto vive dentro dela** ([ADR-0019](docs/architecture/adrs/ADR-0019-plataforma-abstrata-sem-produto.md)). O que é de um domínio de negócio específico sai para **extensões** — pacotes Composer instaláveis em qualquer produto ([ADR-0015](docs/architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). A raiz deste repositório é o **app de desenvolvimento e vitrine** da plataforma, não um produto.

Vocabulário (glossário completo em [CONTEXT-MAP.md](CONTEXT-MAP.md)): **core** é o pacote `ht2ml/core`; **produto** é uma aplicação que instala o core; **instância** é um deploy de um produto para um cliente; **módulo** é uma área de negócio (`rh`, `escola`), que pode viver no produto ou viajar num pacote; **extensão** é o envelope, o pacote que carrega um módulo ou só uma biblioteca ([ADR-0021](docs/architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)).

O onboarding detalhado para agentes e humanos está em [CLAUDE.md](CLAUDE.md).

---

## Stack

| Camada                  | Tecnologia                                                        |
| ----------------------- | ----------------------------------------------------------------- |
| Backend                 | Laravel 13, PHP 8.4                                               |
| Dados                   | PostgreSQL 16, Redis                                              |
| Admin (UI)              | Livewire 4, Inspinia, Tailwind CSS 4                              |
| Auth                    | Guard `admin` (AdminUser)                                         |
| ACL / Auditoria         | spatie/laravel-permission, spatie/laravel-activitylog             |
| Assíncrono / observação | Laravel Horizon, Laravel Pulse                                    |
| Build                   | Vite                                                              |
| Testes                  | Pest                                                              |
| Ambiente recomendado    | DDEV + OrbStack; ver [docs/devops/infra.md](docs/devops/infra.md) |

---

## Início rápido

**Pré-requisitos (uma vez por máquina):**

```bash
brew install orbstack          # abra o OrbStack 1x e selecione "Docker"
brew install ddev/ddev/ddev
```

> macOS usa **OrbStack** como provider Docker (rápido e leve). Em Windows/Linux o mesmo `.ddev/` funciona com Docker Desktop/WSL2.

**Subir o projeto:**

```bash
git clone git@github.com:leonardozaneladias/ht2-erp.git erp && cd erp
cp .env.example .env
ddev start       # sobe tudo + hooks (composer/npm install + migrate)
make setup       # 1x: key:generate, seed, assets Horizon/Pulse, build
mkcert -install  # 1x por máquina: HTTPS confiável (pede admin) → ddev restart
ddev launch      # abre https://ht2ml-platform.ddev.site
```

> Guia completo (instalar, configurar, rodar, troubleshooting e projeto novo): **[docs/devops/ddev-setup.md](docs/devops/ddev-setup.md)**.

Comandos do dia-a-dia via **Makefile** (wrappers do `ddev`):

```bash
make up            # ddev start
make down          # ddev stop
make restart       # ddev restart
make bash          # shell no container web (ddev ssh)
make status        # ddev describe
make logs          # ddev logs -f
make artisan …     # ddev artisan …   (ex.: make artisan migrate)
make composer …    # ddev composer …
make npm …         # ddev npm …
make migrate       # migrate
make fresh         # migrate:fresh --seed
make seed          # db:seed
make horizon       # reinicia o Horizon no container
make dev           # Vite dev server (HMR)
make test          # artisan test --parallel --testsuite=Unit,Feature,Extensoes,Arch
make test-e2e      # build do Vite + Pest --group=browser (roda no HOST)
make lint          # Pint + Prettier (corrige)
make quality       # npm run quality: Pint, ESLint, Prettier, PHPStan e testes (só confere)
make doutor        # ht2ml:doutor — as contribuições dos módulos fecham? (exit 1 se não)
make setup         # 1x: key, migrate --seed, assets Horizon/Pulse, build
make setup-client  # 1x numa instância de cliente: sem dados demo, abre o Setup Wizard
make new-client    # provisiona uma instância (bin/new-client.sh, no HOST)
make update-base   # (na instância) traz updates do produto (bin/update-from-upstream.sh)
make release-modulo pacote=extensao-rh versao=v0.2.0   # release de um pacote (bin/release-module.sh)
```

`pacote` é o nome do diretório em `packages/` (`core`, `extensao-rh`…), não o slug do módulo.

Seeders criam `admin@example.com` / `password` (super-admin) e `gestor@example.com` / `password` (gestor).

---

## Derivar um produto ou instanciar um cliente

Dois caminhos distintos — não os confunda (runbook em **[docs/distribuicao-manutencao.md](docs/distribuicao-manutencao.md)**):

- **Produto novo** → nasce do skeleton por Composer ([ADR-0017](docs/architecture/adrs/ADR-0017-produto-novo-via-skeleton.md)). O `ht2ml/skeleton` e os pacotes `ht2ml/*` são repositórios privados no GitHub, fora do Packagist: o `create-project` precisa do `--repository` apontando para o repositório do skeleton e de uma credencial com leitura nos repositórios `ht2ml-*` (chave SSH na conta, ou um token com _Contents: Read-only_). O `ht2ml/core` já vem no `composer.json` do skeleton.

    ```bash
    composer create-project ht2ml/skeleton meu-produto \
      --repository='{"type":"vcs","url":"git@github.com:leonardozaneladias/ht2ml-skeleton.git","no-api":true}'
    cd meu-produto
    cp .env.example .env && php artisan key:generate
    php artisan migrate --seed
    npm install && npm run build
    ```

    Passo a passo, extensões opcionais e atualização (`composer update ht2ml/core` → `migrate` → `access:sync` → `ht2ml:doutor`): **[docs/guias/produto-via-skeleton.md](docs/guias/produto-via-skeleton.md)**.

    Um produto **não clona** este repositório. Clonar a plataforma faria o produto novo nascer com a identidade dela no `composer.json`, no `.env` e no banco. Foi esse problema que aposentou o `bin/init-project.sh` (ADR-0017). O script ainda está em `bin/`, mas não é caminho suportado.

- **Instância de cliente** (mesmo produto, cliente diferente) → _clone + re-origin_ + `bin/new-client.sh`. **Preserva o histórico** e configura remotes, `.env` e DDEV de forma aditiva ([ADR-0016](docs/architecture/adrs/ADR-0016-instancias-por-cliente.md)).

    ```bash
    git clone git@github.com:{org}/{produto}.git cliente-acme && cd cliente-acme
    git remote rename origin upstream
    git remote add origin git@github.com:{org}/{produto}-acme.git
    make new-client
    git push -u origin main && ddev start && make setup-client
    ```

    `make setup-client` não semeia dados demo → o Setup Wizard em `/admin/setup` cria a empresa, o branding e o admin. Depois, `make update-base` traz correções do produto para a instância.

---

## Depois do setup — primeiros passos

1. Acesse **`https://ht2ml-platform.ddev.site/admin/login`** com `admin@example.com` / `password`.
2. Confira o **módulo de referência do stack** em `/admin/usuarios`, `/admin/perfis` e `/admin/auditoria` — implementação completa de FormRequest/Service/Action/DTO/Policy/Livewire/Activity Log que serve de molde para novos módulos. Ele vive no core, em `packages/core/`.
3. Para criar seu próprio módulo, use o gerador (`make:modulo` e `make:recurso --modulo=`), descrito em [CLAUDE.md §16](CLAUDE.md#16-produto-novo-e-módulos-de-negócio) e em [docs/criar-recurso.md](docs/criar-recurso.md).

Arquivos-chave do módulo de referência (namespace `HT2ML\Core\`):

- `packages/core/src/Services/Admin/AdminUserService.php` (Service não recebe Request)
- `packages/core/src/Actions/Admin/CreateAdminUserAction.php` (Action atômica)
- `packages/core/src/DTOs/Admin/AdminUserDTO.php` (DTO readonly)
- `packages/core/src/Policies/AdminUserPolicy.php` (Policy registrada no `CoreServiceProvider`)
- `packages/core/src/Livewire/Admin/Usuarios/{IndexUsuarios,FormUsuario,UsuariosTable}.php` (UI Livewire 4)
- `tests/Feature/Admin/Usuarios/*.php` (10 arquivos de teste Pest, contados em 2026-10-25)

A raiz deste repositório quase não tem código próprio: `app/` guarda só `Models/User.php` e três providers. O resto é o core, instalado de `packages/core` por path repository.

---

## Pacotes

Tudo em `packages/` é publicado como repositório próprio (`leonardozaneladias/ht2ml-<pacote>`) por `make release-modulo`. Na raiz, cada um entra por path repository com symlink.

| Pacote                                                                    | Tipo                  | O que é                                                                                                                                                                                                 |
| ------------------------------------------------------------------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [`ht2ml/core`](packages/core/README.md)                                   | core                  | A plataforma: auth com 2FA, multiempresa, perfis e permissões, auditoria, configurações, menu, design system, catálogos de referência, geradores e os canais por onde módulos entram (`ModuleRegistry`) |
| [`ht2ml/extensao-rh`](packages/extensao-rh/README.md)                     | módulo `rh`           | Departamento pessoal: departamentos e funcionários                                                                                                                                                      |
| [`ht2ml/extensao-fiscal-br`](packages/extensao-fiscal-br/README.md)       | módulo `fiscal-br`    | Catálogos de classificação fiscal: CNAE, CFOP e NCM                                                                                                                                                     |
| [`ht2ml/extensao-documentos`](packages/extensao-documentos/README.md)     | biblioteca            | Numeração sequencial de documentos por empresa, tipo e ano, sem tela                                                                                                                                    |
| [`ht2ml/extensao-exemplo-demo`](packages/extensao-exemplo-demo/README.md) | módulo `exemplo-demo` | Vitrine do design system e CRUD de exemplo; prova que um produto pode não instalar uma extensão                                                                                                         |
| `ht2ml/extensao-api`                                                      | biblioteca            | Camada comum de API REST: envelope de resposta, paginação, idioma, erros, limiter, tokens Sanctum e portão de OpenAPI ([ADR-0023](docs/architecture/adrs/ADR-0023-api-rest-como-extensao.md))           |
| `ht2ml/extensao-ia`                                                       | biblioteca            | Contratos de IA (tradutor, resumidor, assistente) com fakes ligados por padrão, cache de tradução e limites por custo ([ADR-0024](docs/architecture/adrs/ADR-0024-ia-como-extensao-de-contratos.md))    |

Versões publicadas (2026-10-25): `ht2ml/core` v0.5.0, `ht2ml/skeleton` v0.4.0 (pede `ht2ml/core: ^0.5.0`), `ht2ml/extensao-api` v0.1.1, `ht2ml/extensao-ia` v0.1.0, `ht2ml/extensao-documentos` v0.1.1, e `ht2ml/extensao-rh`, `ht2ml/extensao-fiscal-br` e `ht2ml/extensao-exemplo-demo` v0.1.0. A `extensao-api` precisa do core ≥ 0.5, que é onde entrou o canal `EscopoDeRota::Api`.

---

## URLs locais (DDEV)

| O quê      | URL / Comando                            |
| ---------- | ---------------------------------------- |
| Aplicação  | https://ht2ml-platform.ddev.site         |
| Horizon    | https://ht2ml-platform.ddev.site/horizon |
| Pulse      | https://ht2ml-platform.ddev.site/pulse   |
| Mailpit    | `ddev mailpit`                           |
| PostgreSQL | `ddev psql` (ou `ddev describe`)         |

---

## Frontend (assets)

```bash
ddev npm install
ddev npm run dev      # desenvolvimento Vite (HMR em *.ddev.site:5173)
ddev npm run build    # build de produção
```

Pontos de entrada Vite: `resources/css/admin.css`, `resources/js/admin.js`.

---

## Qualidade e commits

- Formatação PHP: `./vendor/bin/pint`
- Lint JS: `npm run lint:js`
- Análise estática: `./vendor/bin/phpstan analyse`
- Testes: `php artisan test --testsuite=Unit,Feature,Extensoes,Arch`
- Diagnóstico dos módulos: `php artisan ht2ml:doutor`
- Check agregado: `npm run quality` (Pint, ESLint, Prettier, PHPStan e testes, sem corrigir nada)

Suítes, guardas de arquitetura e o que o CI roda: [docs/testes-e-guardas.md](docs/testes-e-guardas.md).

Mensagens de commit seguem **Conventional Commits** (`tipo(escopo): descrição em pt-BR`). Regras e hooks em [docs/devops/conventions.md](docs/devops/conventions.md).

---

## Documentação

| Doc                                                                                              | Finalidade                                                    |
| ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------- |
| [CLAUDE.md](CLAUDE.md)                                                                           | Contexto, regras e convenções do projeto (lido por agentes)   |
| [CONTEXT-MAP.md](CONTEXT-MAP.md)                                                                 | Glossário da plataforma e mapa dos contextos                  |
| [CONTRIBUTING.md](CONTRIBUTING.md)                                                               | Como contribuir                                               |
| [CHANGELOG.md](CHANGELOG.md)                                                                     | Mudanças, com as ações pós-merge de cada uma                  |
| [docs/README.md](docs/README.md)                                                                 | **Hub** da documentação técnica                               |
| [docs/architecture/index.md](docs/architecture/index.md)                                         | Índice dos ADRs                                               |
| [docs/guias/produto-via-skeleton.md](docs/guias/produto-via-skeleton.md)                         | Criar e atualizar um produto a partir do skeleton             |
| [docs/guias/criar-extensao.md](docs/guias/criar-extensao.md)                                     | Criar uma extensão de ponta a ponta                           |
| [docs/criar-recurso.md](docs/criar-recurso.md)                                                   | Gerador de CRUD (`make:recurso --modulo=`)                    |
| [docs/comandos.md](docs/comandos.md)                                                             | Comandos artisan da plataforma                                |
| [docs/testes-e-guardas.md](docs/testes-e-guardas.md)                                             | Suítes de teste, guardas de arquitetura e CI                  |
| [docs/superficie-do-core.md](docs/superficie-do-core.md)                                         | O que uma extensão consome do core                            |
| [docs/fluxo-desenvolvimento.md](docs/fluxo-desenvolvimento.md)                                   | Branches, PRs e o caminho de uma correção entre repositórios  |
| [docs/distribuicao-manutencao.md](docs/distribuicao-manutencao.md)                               | **Runbook**: novo cliente, propagar correções, releases       |
| [docs/devops/release.md](docs/devops/release.md)                                                 | Ordem e passos de release: core, extensões, skeleton          |
| [docs/devops/ddev-setup.md](docs/devops/ddev-setup.md)                                           | **Guia DDEV + OrbStack** (instalar/configurar/rodar)          |
| [docs/devops/conventions.md](docs/devops/conventions.md)                                         | Convenções de código e Git                                    |
| [docs/devops/infra.md](docs/devops/infra.md)                                                     | Ambiente DDEV, Makefile, URLs                                 |
| [docs/template/INSPINIA/CATALOGO-COMPONENTES.md](docs/template/INSPINIA/CATALOGO-COMPONENTES.md) | Catálogo de componentes Blade (fonte de verdade)              |
| [docs/plans/modules/rh/README.md](docs/plans/modules/rh/README.md)                               | Planejamento do módulo de RH                                  |
| [packages/](packages/)                                                                           | Os pacotes — cada um com seu README (ver [Pacotes](#pacotes)) |
| [bin/new-client.sh](bin/new-client.sh)                                                           | Instanciar um **cliente** (clone + re-origin, aditivo)        |

---

## Licença

MIT.
