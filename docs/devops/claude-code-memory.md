# Memória Automática e Hooks — Claude Code

**Projeto:** Portal ArtFinal  
**Data:** 10/04/2026

---

## 1. Os 3 Mecanismos de Memória do Claude Code

```
┌─────────────────────────────────────────────┐
│  Auto Memory (automática)                    │
│  Claude Code salva aprendizados sozinho      │
│  Arquivo: ~/.claude/auto-memory              │
│  Sem ação sua necessária                     │
├─────────────────────────────────────────────┤
│  # (hashtag) — Memória rápida manual         │
│  Digite # + regra dentro da sessão           │
│  Salva no CLAUDE.md ou ~/.claude/CLAUDE.md   │
│  1 segundo por regra                         │
├─────────────────────────────────────────────┤
│  Hooks — Automação determinística            │
│  Shell scripts que rodam automaticamente     │
│  Configurados em .claude/settings.json       │
│  Rodam em TODA edição, commit ou ferramenta  │
└─────────────────────────────────────────────┘
```

---

## 2. Memórias Rápidas com `#`

Dentro de qualquer sessão do Claude Code, digite `#` seguido da regra:

```bash
# sempre rodar ./vendor/bin/pint antes de commitar
# FormRequest obrigatório para toda validação, nunca validar no Controller
# valores monetários em centavos (int), exibição via MoneyHelper::format()
# ao criar Service, verificar se o nome já está definido no PRD seção 20.3
# após terminar módulo, atualizar .docs/modules/XX-nome.md
# componentes Blade: verificar catálogo antes de criar novo
# commits em português: tipo(escopo): descrição — PAF-XX
# PRD menciona Metronic = ler como Inspinia
```

Essas regras ficam persistentes e são carregadas automaticamente em toda sessão futura.

---

## 3. Custom Slash Commands

Criar estes comandos em `.claude/commands/` para automatizar workflows do projeto:

### 3.1 Comando: `/sprint` — Planejar sprint

```bash
mkdir -p .claude/commands
```

Arquivo: `.claude/commands/sprint.md`

```markdown
Leia o PRD em .docs/PRD_v3.1.0.md, seção 20.3, e encontre a Sprint $ARGUMENTS.

Para esta sprint:

1. Liste todas as entregas definidas no PRD
2. Quebre cada entrega em tarefas de 2-4 horas
3. Para cada tarefa, defina: título, labels (área + tipo + módulo), prioridade, estimativa
4. Organize as tarefas por dia (7 dias)
5. Identifique dependências entre tarefas

Siga os padrões de .docs/CONVENTIONS.md para naming.
Siga a estrutura de .docs/ARCHITECTURE-GUIDE.md para decisão Service vs Action vs Job.

Se o MCP do Plane estiver conectado, crie as issues automaticamente.
```

**Uso:** `/sprint 7` → planeja a Sprint 7 inteira

### 3.2 Comando: `/modulo` — Implementar módulo

Arquivo: `.claude/commands/modulo.md`

```markdown
Implemente o módulo $ARGUMENTS do projeto Portal ArtFinal.

Antes de começar:

1. Leia .docs/PRD_v3.1.0.md e encontre a seção relevante do módulo
2. Leia .docs/ARCHITECTURE-GUIDE.md para padrões de estrutura
3. Leia .docs/CONVENTIONS.md para padrões de código
4. Leia .docs/TEMPLATE-MAP-AND-COMPONENTS.md se envolver UI
5. Verifique se existe .docs/modules/ com doc desse módulo

Implemente:

- Model(s) com relationships, casts, scopes
- Migration(s) com índices e constraints
- Enum(s) se necessário
- FormRequest(s) com validações completas
- Service(s) / Action(s) com lógica de negócio
- Controller(s) magros (máx 5-7 linhas por método)
- Views Blade usando componentes do catálogo
- Rotas em routes/admin.php ou routes/portal.php
- Factory para testes
- Pelo menos 1 teste para cada Service

Após implementar:

- Atualize .docs/modules/XX-nome.md com o template padrão
- Rode ./vendor/bin/pint
- Rode php artisan test
```

**Uso:** `/modulo contratos` → implementa o módulo de contratos

### 3.3 Comando: `/review` — Revisar código

Arquivo: `.claude/commands/review.md`

```markdown
Faça uma revisão de código dos arquivos alterados no git (staged ou último commit).

Checklist de revisão (de .docs/CONVENTIONS.md):

1. [ ] Controller magro? (máx 5-7 linhas por método)
2. [ ] Validação em FormRequest?
3. [ ] Valores monetários em centavos?
4. [ ] Enums em vez de strings mágicas?
5. [ ] Type hints e return types em tudo?
6. [ ] Sem lógica de negócio no Controller?
7. [ ] Testes para cenários críticos?
8. [ ] Sem dados sensíveis em logs?
9. [ ] N+1 tratado (eager loading)?
10. [ ] Componente Blade reutilizável?
11. [ ] declare(strict_types=1)?
12. [ ] Conventional Commits no último commit?

Para cada problema encontrado, indique:

- Arquivo e linha
- O que está errado
- Como corrigir (com exemplo de código)
```

**Uso:** `/review` → revisa os últimos arquivos alterados

### 3.4 Comando: `/docs` — Atualizar documentação do módulo

Arquivo: `.claude/commands/docs.md`

```markdown
Atualize a documentação do módulo $ARGUMENTS.

1. Leia o código atual do módulo (Models, Services, Controllers, Routes)
2. Leia o template de documentação em .docs/modules/01-auth-admin.md como referência
3. Atualize o arquivo .docs/modules/ correspondente com:
    - Models envolvidos (campos principais)
    - Services e Actions (método principal, responsabilidade)
    - Rotas (método, URI, Controller, middleware)
    - Components Blade utilizados
    - Regras de negócio implementadas
    - Testes existentes
    - Status: 🟢 Completo, 🟡 Em Progresso, ou 🔴 Pendente
    - Changelog do módulo com a data de hoje

Não invente informação — documente apenas o que existe no código.
```

**Uso:** `/docs contratos` → atualiza .docs/modules/03-contratos.md

### 3.5 Comando: `/fim-sprint` — Finalizar sprint

Arquivo: `.claude/commands/fim-sprint.md`

```markdown
Finalize a Sprint $ARGUMENTS do projeto Portal ArtFinal.

Execute as seguintes verificações e ações:

1. Rode ./vendor/bin/pint --test (verificar formatação PHP)
2. Rode npx prettier --check "resources/" (verificar Blade/JS/CSS)
3. Rode ./vendor/bin/phpstan analyse (análise estática)
4. Rode php artisan test (testes)
5. Liste os módulos que foram alterados nesta sprint
6. Para cada módulo alterado, verifique se .docs/modules/ está atualizado
7. Atualize .docs/CHANGELOG.md com as entregas da sprint
8. Sugira a mensagem de commit final
9. Sugira a tag de versão se for fim de fase

Relate o resultado de cada etapa.
```

**Uso:** `/fim-sprint 4` → executa checklist de fim de sprint

---

## 4. Hooks Automáticos

Adicionar ao `.claude/settings.json`:

```json
{
    "hooks": {
        "PostToolUse": [
            {
                "matcher": "Write(*.php)",
                "hooks": [
                    {
                        "type": "command",
                        "command": "./vendor/bin/pint $CLAUDE_FILE_PATH 2>/dev/null || true"
                    }
                ]
            },
            {
                "matcher": "Write(*.blade.php)",
                "hooks": [
                    {
                        "type": "command",
                        "command": "npx prettier --write $CLAUDE_FILE_PATH 2>/dev/null || true"
                    }
                ]
            }
        ],
        "Stop": [
            {
                "matcher": "",
                "hooks": [
                    {
                        "type": "command",
                        "command": "echo \"$(date '+%Y-%m-%d %H:%M') — Sessão finalizada\" >> .claude/session-log.txt"
                    }
                ]
            }
        ]
    }
}
```

O que esses hooks fazem:

- **PostToolUse (Write .php):** Roda Pint automaticamente em todo arquivo PHP que o Claude editar. Sem precisar lembrar de rodar manualmente.
- **PostToolUse (Write .blade.php):** Roda Prettier automaticamente em todo Blade editado. Ordena classes Tailwind automaticamente.
- **Stop:** Registra data/hora de cada sessão finalizada em um log.

### 4.1 Hook avançado: Registrar resumo da sessão

Para registrar o que foi feito em cada sessão automaticamente, adicionar este hook de Stop:

```json
{
    "Stop": [
        {
            "matcher": "",
            "hooks": [
                {
                    "type": "prompt",
                    "prompt": "Resuma em 2-3 linhas o que foi feito nesta sessão. Escreva o resumo no formato '- [DATA] RESUMO' e adicione ao final do arquivo .claude/memory-log.md. Se o arquivo não existir, crie com o header '# Log de Sessões — Portal ArtFinal'. Use a data de hoje."
                }
            ]
        }
    ]
}
```

Esse hook usa `type: "prompt"` em vez de `command` — ou seja, ele pede ao próprio Claude para gerar o resumo e gravar no arquivo. Resultado: um arquivo `.claude/memory-log.md` que cresce automaticamente com tudo que foi feito:

```markdown
# Log de Sessões — Portal ArtFinal

- [2026-04-10] Criada migration de contratos, model com relationships, e ContratoService
- [2026-04-11] Implementado CRUD de instituições com DataTable Livewire e export Excel
- [2026-04-12] Corrigido cálculo de parcela mínima, adicionados 5 testes unitários
```

---

## 5. Estrutura de Arquivos Claude Code

```
portalartfinal_v2/
├── CLAUDE.md                          ← Contexto principal (raiz)
├── .claude/
│   ├── settings.json                  ← Hooks e configurações
│   ├── commands/                      ← Slash commands customizados
│   │   ├── sprint.md                  ← /sprint XX
│   │   ├── modulo.md                  ← /modulo nome
│   │   ├── review.md                  ← /review
│   │   ├── docs.md                    ← /docs nome
│   │   └── fim-sprint.md             ← /fim-sprint XX
│   └── memory-log.md                  ← Log automático de sessões (gerado pelo hook)
└── .docs/                             ← Documentação do projeto
```

---

## 6. Setup Completo (Executar Uma Vez)

```bash
cd ~/portalartfinal_v2

# 1. Criar estrutura de diretórios
mkdir -p .claude/commands

# 2. Criar os 5 slash commands (copiar conteúdo das seções 3.1 a 3.5 acima)
# Cada arquivo .md vai em .claude/commands/

# 3. Criar o settings.json com hooks (copiar seção 4 acima)
# Vai em .claude/settings.json

# 4. Adicionar memórias rápidas na primeira sessão do Claude Code:
# Abrir claude e digitar:
#   # sempre usar Enums PHP 8.1+ para campos de status
#   # valores monetários em centavos (int), nunca float
#   # ao terminar módulo, atualizar .docs/modules/
#   # commits: tipo(escopo): descrição — PAF-XX
#   # PRD menciona Metronic = ler como Inspinia

# 5. Commitar tudo
git add .claude/
git commit -m "chore(infra): configurar Claude Code (hooks, commands, memória)"
```

---

## 7. Workflow Diário com Tudo Integrado

```
Manhã:
  → Abrir Claude Code no projeto
  → Claude lê CLAUDE.md automaticamente (contexto completo)
  → Claude lê auto-memory (aprendizados de sessões anteriores)
  → Claude lê .claude/memory-log.md (histórico do que foi feito)
  → /sprint 7  (se for início de sprint, planeja e cria issues no Plane)

Durante o dia:
  → Trabalhar normalmente
  → Hook roda Pint em todo .php editado (automático)
  → Hook roda Prettier em todo .blade.php editado (automático)
  → # (hashtag) para registrar novas regras quando surgir

Fim do dia:
  → Encerrar sessão
  → Hook Stop grava resumo em .claude/memory-log.md (automático)

Fim da sprint:
  → /fim-sprint 7  (roda checks, atualiza docs, sugere commit)
  → /docs contratos  (atualiza doc do módulo trabalhado)
```
