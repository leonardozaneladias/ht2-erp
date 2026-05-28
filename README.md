# Portal ArtFinal v2 — Sistema de gerenciamento de formaturas

Sistema web com **dois ambientes independentes**: **Admin (backoffice)** — Livewire + Inspinia (Tailwind CSS 4); **Portal do formando** — SPA React 19 (`resources/spa/`), TanStack Router/Query, Sanctum. Guards, usuários e rotas de autenticação **nunca** são misturados entre si.

Este repositório contém código e documentação técnica. O onboarding detalhado para agentes e humanos está em [CLAUDE.md](CLAUDE.md).

---

## Stack (resumo)

| Camada                  | Tecnologia                                                           |
| ----------------------- | -------------------------------------------------------------------- |
| Backend                 | Laravel 13, PHP 8.4                                                  |
| Dados                   | PostgreSQL 16, Redis                                                 |
| Admin                   | Livewire 4, Inspinia, Tailwind CSS 4                                 |
| Portal                  | React 19, Vite, TanStack Router/Query, Zustand, Zod                  |
| API                     | Laravel Sanctum (`/api/v1/*`), shell SPA em `/portal`                |
| Assíncrono / observação | Laravel Horizon, Laravel Pulse                                       |
| Integrações             | Saloon (HTTP), JWT (draft público — ver SPEC)                        |
| Testes                  | Pest, PHPUnit                                                        |
| Ambiente recomendado    | Docker (Laradock); ver [documentação de infra](docs/devops/infra.md) |

---

## Requisitos

- **Fluxo oficial:** Docker + [Laradock](laradock/) conforme [docs/devops/infra.md](docs/devops/infra.md).
- **Node + PHP** também são necessários no host se optar por instalar ou compilar artefactos sem os passos docados para container (detalhes e restrições no mesmo doc).

---

## Início rápido

1. Na raiz do clone, execute **`./docker-setup.sh`** — sobe stacks, Composer, migrations/publicações (Horizon, Pulse), NPM e rebuild conforme script (ver secção «Primeiro Boot» em [docs/devops/infra.md](docs/devops/infra.md)).
2. Comandos do dia-a-dia no container via **Makefile** (tabela completa em [infra](docs/devops/infra.md)):

```bash
make up          # containers
make bash        # shell no workspace (/var/www)
make test        # php artisan test
make migrate    # migrações
```

Desenvolvimento local com servidor, fila, logs e Vite: `composer run dev` na máquina com dependências já instaladas (`composer.json`/`package.json`).

---

## URLs locais típicos (Docker)

| O quê     | URL                      |
| --------- | ------------------------ |
| Aplicação | http://localhost         |
| Horizon   | http://localhost/horizon |
| Pulse     | http://localhost/pulse   |
| pgAdmin   | http://localhost:5050    |
| Mailpit   | http://localhost:8125    |

Credenciais e variantes externas/host: ver [docs/devops/infra.md](docs/devops/infra.md).

---

## Frontend (assets)

```bash
npm install
npm run dev      # desenvolvimento Vite (portal + bundles admin onde aplicável)
npm run build    # build de produção
```

Pontos de entrada Vite configurados incluem `resources/css/admin.css`, `resources/js/admin.js` e `resources/spa/src/main.tsx`.

---

## Qualidade e commits

- Formatação PHP: `./vendor/bin/pint`
- Lint/check agregado: `npm run quality` (alinhar com [docs/devops/conventions.md](docs/devops/conventions.md) e tooling do projecto)

Mensagens de commit (**Conventional Commits**, escopos e `commitlint`):

```bash
npm run commit
composer run commit
composer run lint:last-commit-msg   # valida a última mensagem
```

Regras e hooks: secção §1 em [docs/devops/conventions.md](docs/devops/conventions.md).

---

## Documentação

| Doc                                                        | Finalidade                                            |
| ---------------------------------------------------------- | ----------------------------------------------------- |
| [docs/README.md](docs/README.md)                           | Hub da documentação técnica                           |
| [CLAUDE.md](CLAUDE.md)                                     | Contexto do produto para agentes IDE e humanos        |
| [docs/META/PROJECT-STATUS.md](docs/META/PROJECT-STATUS.md) | Fase atual (desenvolvimento / homologação / produção) |
| [docs/devops/conventions.md](docs/devops/conventions.md)   | Convenções de código e Git                            |
| [docs/devops/infra.md](docs/devops/infra.md)               | Ambiente Docker, Makefile, URLs                       |

> O nome do pacote Composer continua sendo o skeleton `laravel/laravel`; não confundir com a marca Portal ArtFinal do produto.

---

## Segurança

Vulnerabilidades **desta aplicação** devem ser reportadas apenas pelos **canais internos definidos pela Art Final Eventos**, não através do contacto público do framework Laravel.

---

## Licença

O projeto declara licença **MIT** nos metadados Composer (consistente com o ecossistema Laravel / skeleton). Código próprio Art Final pode estar sujeito a termos comerciais adicionais fora deste README — usar o canal jurídico da organização quando aplicável.
