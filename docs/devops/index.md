---
title: DevOps — Índice
version: 1.0.0
status: draft
publico: DevOps, SRE, Engenharia
---

# DevOps — Índice

Índice navegável dos documentos operacionais desta pasta.

---

## Documentos

### ⭐ [`ddev-setup.md`](ddev-setup.md) — Guia DDEV + OrbStack (começe por aqui)

Guia rápido e copiável para **instalar, configurar e rodar** o ambiente com DDEV +
OrbStack — neste projeto e em produtos criados a partir do skeleton. Inclui
pré-requisitos, primeiro boot, HTTPS (mkcert), o que fica versionado em `.ddev/` e
troubleshooting dos problemas reais (troca de provider, portas, Mutagen, banco por provider).

**Quando consultar:** primeiro contato com o projeto; criar um projeto novo; bater um problema de ambiente.

### 1. [`dev-setup.md`](dev-setup.md) — Setup de ambiente de desenvolvimento

Passo a passo obrigatório para colocar o ambiente local em execução. Cobre pré-requisitos (OrbStack + DDEV no macOS, Docker Desktop/WSL2 em Windows/Linux), setup inicial, comandos do dia a dia, portas locais, troubleshooting e setup do editor (VS Code + PhpStorm).

**Quando consultar:**

- Primeiro boot do projeto.
- Quando algo no ambiente local para de funcionar.
- Ao integrar novo desenvolvedor.

### 2. [`conventions.md`](conventions.md) — Convenções e padrões

Conventional Commits PT-BR, branching strategy, naming conventions, padrões de código PHP (strict_types, PSR-12/Pint, PHPStan level 6), padrões de Models/Services, frontend (Tailwind, Blade, Livewire), banco de dados, testes, logs e auditoria, segurança, cache (Redis), filas (Horizon), tratamento de erros, performance, localização pt_BR, e-mails e a regra obrigatória de componentização Inspinia/Blade.

**Quando consultar:**

- Antes de abrir PR.
- Em code review.
- Quando há dúvida sobre padrão arquitetural.

### 3. [`tools-and-packages.md`](tools-and-packages.md) — Ferramentas e pacotes

Pacotes Composer (Livewire, Spatie Permission/Activitylog, Horizon, Pulse, DomPDF, Excel, Pint, Larastan, Pest) e NPM (Vite, Tailwind 4, plugins Inspinia), justificativas de decisão, checklist de instalação, ferramentas de desenvolvimento e stack resumida.

**Quando consultar:**

- Ao avaliar/adicionar um pacote.
- Ao entender por que uma dependência existe.

### 4. [`infra.md`](infra.md) — Infraestrutura local

URLs e serviços do ambiente DDEV, comandos do Makefile, primeiro boot, acessos (PostgreSQL, Redis, Mailpit), configuração do Vite/Horizon e troubleshooting de containers.

**Quando consultar:**

- Ao subir/derrubar o ambiente.
- Ao diagnosticar problemas de containers.

### 5. [`claude-code-memory.md`](claude-code-memory.md) — Memória e contexto (Claude Code)

Os mecanismos de memória do Claude Code e o que este repositório versiona para agentes (`CLAUDE.md`, `AGENTS.md`, `.ai/skills/`, `.github/skills/`). O repositório não versiona `.claude/`: slash commands e hooks, se usados, ficam na máquina de cada um.

**Quando consultar:**

- Ao configurar o Claude Code no projeto.

### 6. [`ci-cd.md`](ci-cd.md) — CI

O workflow real (`.github/workflows/ci.yml`): gatilhos, concorrência, os jobs `quality`, `core-sozinho`, `tests` e `browser`, e o Dependabot. Não existe workflow de deploy.

### 7. [`security-operations.md`](security-operations.md) — Operações de Segurança

Checklists de segurança, rotação de segredos, auditoria de permissões e resposta a incidentes.

### Arquivados

O rascunho de CI/CD, o runbook de deploy e o documento de monitoramento e alertas, todos de abril de 2026, descreviam ferramentas que nunca foram instaladas. Estão em [`../historico/devops/`](../historico/README.md).

---

## Quick reference

### Comandos essenciais

```bash
# Ambiente
make up                              # ddev start
make bash                            # ddev ssh
make fresh                           # ddev artisan migrate:fresh --seed

# Qualidade
make quality                         # Pint + ESLint + Prettier + PHPStan + testes
make test                            # Unit, Feature, Extensoes, Arch (em paralelo)
make doutor                          # ht2ml:doutor
vendor/bin/pint --dirty --format agent
```

### Links operacionais (dev)

| Recurso | URL / Comando                     |
| ------- | --------------------------------- |
| Admin   | https://ht2ml-platform.ddev.site/admin   |
| Horizon | https://ht2ml-platform.ddev.site/horizon |
| Pulse   | https://ht2ml-platform.ddev.site/pulse   |
| Mailpit | `ddev mailpit`                    |
| Banco   | `ddev psql`                       |

---

## Referências externas

- [Laravel 13 docs](https://laravel.com/docs/13.x)
- [Horizon docs](https://laravel.com/docs/13.x/horizon)
- [Pulse docs](https://laravel.com/docs/13.x/pulse)
- [Livewire docs](https://livewire.laravel.com/docs)
- [Conventional Commits](https://www.conventionalcommits.org/pt-br/v1.0.0/)
