# Template — SQUAD-F{N}.md

Use este template para gerar `docs/squads/SQUAD-F{N}.md`.
Substitua todos os `{placeholder}` com os valores reais.

---

```markdown
# Squad — F{N}: {Nome da Fase}

> **Fase:** F{N}
> **Objetivo:** {objetivo da fase em 1 linha}
> **Story Points:** {SP total}
> **Dependências:** {fases anteriores}
> **Status:** 🟡 Em planejamento | 🟢 Em andamento | ✅ Concluída
> **Atualizado em:** {data}

---

## Agents BMAD

| Agent | Papel na fase |
|---|---|
| `bmad-orchestrator` | Inicialização, status e roteamento entre stories |
| `scrum-master` | Quebrar epics em stories; estimativas Fibonacci; planejar sprints |
| `developer` | Implementar stories; commitar; invocar skills |
| `ux-designer` | (se aplicável) Wireframes e fluxo de UX |
| `product-manager` | (se aplicável) Validação de requisitos e priorização |

---

## Skills por domínio

### Obrigatórias

| Skill | Domínio | Como invocar |
|---|---|---|
| `{skill-name}` | {domínio} | `/nome-da-skill` ou `Skill({ skill: "nome" })` |

### Opcionais/situacionais

| Skill | Domínio | Quando usar |
|---|---|---|
| `{skill-name}` | {domínio} | {condição de ativação} |

---

## Atribuição por tarefa

| Tarefa / Story | Domínio | Skill primária | Skill secundária | BMAD agent |
|---|---|---|---|---|
| {descrição curta} | {domínio} | `{skill}` | `{skill}` | `developer` |

---

## Critérios de aceite da fase

- [ ] {critério 1}
- [ ] {critério 2}
- [ ] `php artisan test --compact` passa 100%
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros
- [ ] `./vendor/bin/pint --dirty` sem alterações

---

## Notas e decisões

- {ADR relacionado, se houver}
- {Decisão técnica relevante}

---

*Gerado por skill `squad-configurator`. Atualizar ao iniciar cada sprint.*
```
