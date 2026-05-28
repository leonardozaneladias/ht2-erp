---
name: squad-configurator
description: "Compõe e documenta a squad de agents/skills para cada fase do Portal ArtFinal. Use quando precisar saber quais skills e agents devem atuar em uma fase (F1–F8), atribuir skills a tarefas de sprint, gerar ou atualizar SQUAD-F{N}.md no repositório, ou fazer onboarding de um novo agent. Triggers: 'qual a squad da F2?', 'configure a squad para esta sprint', 'documente os agents', 'quem trabalha nesta fase?', 'gere SQUAD.md', 'monte a squad'."
---

# Squad Configurator — Portal ArtFinal

Skill para compor a squad de agents especializados por fase do projeto e gerar documentação versionada em git.

## Workflow

### 1. Identificar fase/sprint atual

```bash
git branch --show-current
cat docs/bmm-workflow-status.yaml 2>/dev/null | grep "current_phase"
```

Se a fase não for detectável automaticamente, pergunte ao usuário.

### 2. Carregar mapeamento da fase

Leia `references/phase-squads.md` para obter skills obrigatórias, opcionais, agents BMAD e responsabilidades por domínio da fase identificada.

### 3. Verificar disponibilidade

```bash
ls .agents/skills/    # skills locais do projeto
ls ~/.claude/skills/  # skills globais do usuário
```

Para skills ausentes, informe o comando de instalação (`npx skills add ...`).

### 4. Mapear skills → tarefas da sprint

Para cada tarefa da sprint, identifique o domínio e atribua skill primária + secundária conforme a tabela abaixo. Indique qual BMAD agent executa (developer, scrum-master, ux-designer etc.).

### 5. Gerar SQUAD-F{N}.md

Crie ou atualize `docs/squads/SQUAD-F{N}.md` usando o template em `references/squad-template.md`.

Regras:
- Um arquivo por fase: `SQUAD-F1.md` ... `SQUAD-F8.md`
- Atualizar `docs/squads/README.md` com índice de todas as squads
- Commit: `docs(squad): configurar squad F{N} — {descrição breve}`

### 6. Atualizar AGENTS_AND_SKILLS.txt (opcional)

Se novas skills foram instaladas, ofereça regenerar a seção 4 do `AGENTS_AND_SKILLS.txt`.

---

## Tabela de atribuição por domínio

| Domínio | Skill primária | Skill secundária |
|---|---|---|
| Migrations e Models | `laravel-models` | `superpowers-laravel:migrations-and-factories` |
| Actions e DTOs | `laravel-actions` | `laravel-dtos` |
| Services | `laravel-services` | `laravel-best-practices` |
| Controllers API | `laravel-api` | `laravel-controllers` |
| FormRequests | `laravel-validation` | `superpowers-laravel:form-requests-and-validation` |
| Policies e ACL | `laravel-policies` | `laravel-permission-development` |
| Jobs e Filas | `laravel-jobs` | `configuring-horizon` |
| Events e Listeners | `laravel-best-practices` | `laravel-services` |
| Livewire Admin | `livewire-development` | `tailwindcss-development` |
| Componentes Blade | `superpowers-laravel:blade-components-and-layouts` | `tailwindcss-development` |
| React SPA | `react-best-practices` | `react-state-management` |
| Testes Feature/Unit | `pest-testing` | `laravel-testing` |
| Segurança | `laravel-security` | `laravel-owasp-security` |
| Performance e Cache | `superpowers-laravel:performance-caching` | `debug-using-debugbar` |
| Observabilidade | `pulse-development` | `configuring-horizon` |
| Enums | `laravel-enums` | `laravel-best-practices` |
| State Machines | `laravel-state-machines` | `laravel-services` |
| Value Objects | `laravel-value-objects` | `laravel-dtos` |
| Query Builders | `laravel-query-builders` | `superpowers-laravel:performance-eager-loading` |
| ADR e Decisões | `adr-skill` | — |
| Auditoria e Logs | `superpowers-laravel:exception-handling-and-logging` | `laravel-security` |
| Filas e Horizon | `configuring-horizon` | `superpowers-laravel:queues-and-horizon` |

---

## Referências

- Mapeamento completo por fase F1–F8: [references/phase-squads.md](references/phase-squads.md)
- Template do documento SQUAD-F{N}.md: [references/squad-template.md](references/squad-template.md)
- Cronograma de fases: `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §14
- SPECs de features: `docs/features/SPEC-NNN-*.md`
