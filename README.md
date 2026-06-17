# HT2 ERP

**ERP administrativo multiempresa** da HT2 — backoffice desktop-first e server-side, construído em **Laravel 13 + Livewire 4 + Inspinia (Tailwind 4)**, com guard `admin`, RBAC de dois níveis (papéis globais + por empresa), auditoria, filas (Horizon) e monitoramento (Pulse).

O produto é **modular**: cada módulo de negócio é distribuído como **pacote Composer** aditivo ao core ([ADR-0015](docs/architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Em desenvolvimento: o **módulo de RH / Departamento Pessoal** (`ht2erp/modulo-rh`) — visão, modelo e blueprint na [suíte de documentação do RH](docs/plans/modules/rh/README.md).

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
ddev launch      # abre https://gdf-erp.ddev.site
```

> Guia completo (instalar, configurar, rodar, troubleshooting e projeto novo): **[docs/devops/ddev-setup.md](docs/devops/ddev-setup.md)**.

Comandos do dia-a-dia via **Makefile** (wrappers do `ddev`):

```bash
make up          # ddev start
make bash        # shell no container web (ddev ssh)
make fresh       # migrate:fresh --seed
make dev         # Vite dev server (HMR)
make test        # php artisan test
make lint        # Pint + Prettier
make quality     # Lint + PHPStan + Test
```

Seeders criam `admin@example.com` / `password` (super-admin) e `gestor@example.com` / `password` (gestor).

---

## Derivar um novo projeto ou instanciar um cliente

Há **dois caminhos** distintos — não os confunda (detalhes no runbook **[docs/distribuicao-manutencao.md](docs/distribuicao-manutencao.md)** e no [ADR-0016](docs/architecture/adrs/ADR-0016-instancias-por-cliente.md)):

- **Produto novo** (diverge da base para sempre) → `bin/init-project.sh`. Renomeia marca / banco / Horizon / Pulse e oferece reinicializar o git history (cortando o vínculo com a base).

    ```bash
    ./bin/init-project.sh           # interativo
    ./bin/init-project.sh --dry-run # mostra o que mudaria sem editar
    ```

    Pergunta nome do projeto, slug e domínio de e-mail e aplica em `composer.json`, `package.json`, `.env.example`, `.env`, `README.md`, `CLAUDE.md`, `AGENTS.md`. Depois oferece (com confirmação) reset de `CHANGELOG.md`, `.claude/memory-log.md`, `docs/superpowers/plans|specs/` e reinicialização do git history.

- **Cliente** (continua recebendo updates da base) → _clone + re-origin_ + `bin/new-client.sh`. **Preserva o histórico** (merge limpo) e configura remotes / `.env` / DDEV de forma aditiva, sem apagar o git.

    ```bash
    git clone git@github.com:leonardozaneladias/ht2-erp.git cliente-acme && cd cliente-acme
    git remote rename origin upstream
    git remote add origin git@github.com:leonardozaneladias/ht2-erp-acme.git
    make new-client                 # provisiona o cliente (aditivo)
    git push -u origin main && ddev start && make setup-client
    ```

    `make setup-client` não semeia dados demo (instalado=false) → o Setup Wizard em `/admin/setup` cria a empresa/branding/admin. Depois, `make update-base` traz correções/melhorias da base para o cliente.

---

## Depois do setup — primeiros passos

1. Acesse **`https://gdf-erp.ddev.site/admin/login`** com `admin@example.com` / `password`.
2. Confira o **módulo de referência do stack** em `/admin/usuarios`, `/admin/perfis` e `/admin/auditoria` — implementação completa de FormRequest/Service/Action/DTO/Policy/Livewire/Activity Log que serve de molde para novos módulos.
3. Para criar seu próprio módulo, siga o passo-a-passo em [CLAUDE.md §16](CLAUDE.md#16-iniciando-um-novo-projeto-com-este-boilerplate).

Arquivos-chave do módulo de referência:

- `app/Services/Admin/AdminUserService.php` (Service não recebe Request)
- `app/Actions/Admin/CreateAdminUserAction.php` (Action atômica)
- `app/DTOs/Admin/AdminUserDTO.php` (DTO readonly)
- `app/Policies/AdminUserPolicy.php` (Policy registrada em `AppServiceProvider`)
- `app/Livewire/Admin/Usuarios/{Index,Form}Usuarios.php` (UI Livewire 4)
- `tests/Feature/Admin/Usuarios/*.php` (5 testes Pest)

---

## URLs locais (DDEV)

| O quê      | URL / Comando                     |
| ---------- | --------------------------------- |
| Aplicação  | https://gdf-erp.ddev.site         |
| Horizon    | https://gdf-erp.ddev.site/horizon |
| Pulse      | https://gdf-erp.ddev.site/pulse   |
| Mailpit    | `ddev mailpit`                    |
| PostgreSQL | `ddev psql` (ou `ddev describe`)  |

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
- Análise estática: `./vendor/bin/phpstan analyse`
- Testes: `php artisan test`
- Check agregado: `npm run quality`

Mensagens de commit seguem **Conventional Commits** (`tipo(escopo): descrição em pt-BR`). Regras e hooks em [docs/devops/conventions.md](docs/devops/conventions.md).

---

## Documentação

| Doc                                                                                              | Finalidade                                              |
| ------------------------------------------------------------------------------------------------ | ------------------------------------------------------- |
| [CLAUDE.md](CLAUDE.md)                                                                           | Contexto, regras e convenções do projeto                |
| [docs/README.md](docs/README.md)                                                                 | Hub da documentação técnica                             |
| [docs/plans/modules/rh/README.md](docs/plans/modules/rh/README.md)                               | Suíte de documentação do módulo de RH (Fase 1)          |
| [docs/template/INSPINIA/CATALOGO-COMPONENTES.md](docs/template/INSPINIA/CATALOGO-COMPONENTES.md) | Catálogo de componentes Blade (fonte de verdade)        |
| [docs/devops/ddev-setup.md](docs/devops/ddev-setup.md)                                           | **Guia DDEV + OrbStack** (instalar/configurar/rodar)    |
| [docs/devops/conventions.md](docs/devops/conventions.md)                                         | Convenções de código e Git                              |
| [docs/devops/infra.md](docs/devops/infra.md)                                                     | Ambiente DDEV, Makefile, URLs                           |
| [docs/distribuicao-manutencao.md](docs/distribuicao-manutencao.md)                               | **Runbook**: novo cliente, propagar correções, releases |
| [bin/init-project.sh](bin/init-project.sh)                                                       | Inicialização de **produto novo** (diverge da base)     |
| [bin/new-client.sh](bin/new-client.sh)                                                           | Instanciar um **cliente** (clone + re-origin, aditivo)  |

---

## Licença

MIT.
