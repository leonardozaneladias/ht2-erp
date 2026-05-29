# Laravel Admin Boilerplate — Inspinia + Livewire

Starter kit para sistemas administrativos com **Laravel + Blade + Livewire + Inspinia**. Um único ambiente admin (backoffice desktop-first), server-side, com guard `admin`, ACL via Spatie, auditoria, filas (Horizon) e monitoramento (Pulse).

O onboarding detalhado para agentes e humanos está em [CLAUDE.md](CLAUDE.md).

---

## Stack

| Camada                  | Tecnologia                                              |
| ----------------------- | ------------------------------------------------------- |
| Backend                 | Laravel 13, PHP 8.4                                     |
| Dados                   | PostgreSQL 16, Redis                                    |
| Admin (UI)              | Livewire 4, Inspinia, Tailwind CSS 4                    |
| Auth                    | Guard `admin` (AdminUser)                               |
| ACL / Auditoria         | spatie/laravel-permission, spatie/laravel-activitylog   |
| Assíncrono / observação | Laravel Horizon, Laravel Pulse                          |
| Build                   | Vite                                                    |
| Testes                  | Pest                                                    |
| Ambiente recomendado    | Docker (Laradock); ver [docs/devops/infra.md](docs/devops/infra.md) |

---

## Início rápido

1. Na raiz do clone, execute **`./docker-setup.sh`** — sobe stacks, Composer, migrations/publicações (Horizon, Pulse), NPM e build (ver «Primeiro Boot» em [docs/devops/infra.md](docs/devops/infra.md)).
2. Comandos do dia-a-dia no container via **Makefile**:

```bash
make up          # containers
make bash        # shell no workspace (/var/www)
make fresh       # migrate:fresh --seed
make test        # php artisan test
make lint        # Pint + Prettier
make quality     # Lint + PHPStan + Test
```

Seeders criam `admin@example.com` / `password` (super-admin) e `gestor@example.com` / `password` (gestor).

---

## URLs locais (Docker)

| O quê     | URL                      |
| --------- | ------------------------ |
| Aplicação | http://localhost         |
| Horizon   | http://localhost/horizon |
| Pulse     | http://localhost/pulse   |
| pgAdmin   | http://localhost:5050    |
| Mailpit   | http://localhost:8125    |

---

## Frontend (assets)

```bash
npm install
npm run dev      # desenvolvimento Vite
npm run build    # build de produção
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

| Doc                                                                          | Finalidade                                  |
| ---------------------------------------------------------------------------- | ------------------------------------------- |
| [CLAUDE.md](CLAUDE.md)                                                       | Contexto, regras e convenções do projeto    |
| [docs/README.md](docs/README.md)                                            | Hub da documentação técnica                 |
| [docs/template/INSPINIA/CATALOGO-COMPONENTES.md](docs/template/INSPINIA/CATALOGO-COMPONENTES.md) | Catálogo de componentes Blade (fonte de verdade) |
| [docs/devops/conventions.md](docs/devops/conventions.md)                    | Convenções de código e Git                  |
| [docs/devops/infra.md](docs/devops/infra.md)                                | Ambiente Docker, Makefile, URLs             |

---

## Licença

MIT.
