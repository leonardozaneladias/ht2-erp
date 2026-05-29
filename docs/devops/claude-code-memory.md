# Memória Automática e Hooks — Claude Code

**Versão:** 1.0.0

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
# após terminar módulo, atualizar a doc do módulo correspondente
# componentes Blade: verificar catálogo antes de criar novo
# commits em português: tipo(escopo): descrição
```

Essas regras ficam persistentes e são carregadas automaticamente em toda sessão futura.

---

## 3. Custom Slash Commands

O projeto inclui alguns slash commands em `.claude/commands/` para automatizar workflows. Adapte o conteúdo conforme as necessidades da sua aplicação.

### 3.1 Comando: `/modulo` — Implementar módulo

Arquivo: `.claude/commands/modulo.md`

```markdown
Implemente o módulo $ARGUMENTS da aplicação.

Antes de começar:

1. Leia CLAUDE.md para o contexto e as regras do projeto
2. Leia docs/devops/conventions.md para padrões de código
3. Consulte o catálogo de componentes se envolver UI

Implemente:

- Model(s) com relationships, casts, scopes
- Migration(s) com índices e constraints
- Enum(s) se necessário
- FormRequest(s) com validações completas
- Service(s) / Action(s) com lógica de negócio
- Controller(s) magros (máx 5-7 linhas por método)
- Views Blade usando componentes do catálogo
- Rotas em routes/admin.php
- Factory para testes
- Pelo menos 1 teste para cada Service

Após implementar:

- Rode ./vendor/bin/pint
- Rode php artisan test
```

**Uso:** `/modulo clientes` → implementa o módulo de clientes

### 3.2 Comando: `/review` — Revisar código

Arquivo: `.claude/commands/review.md`

```markdown
Faça uma revisão de código dos arquivos alterados no git (staged ou último commit).

Checklist de revisão (de docs/devops/conventions.md):

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

### 3.3 Comando: `/docs` — Atualizar documentação do módulo

Arquivo: `.claude/commands/docs.md`

```markdown
Atualize a documentação do módulo $ARGUMENTS.

1. Leia o código atual do módulo (Models, Services, Controllers, Routes)
2. Atualize a doc correspondente do módulo com:
    - Models envolvidos (campos principais)
    - Services e Actions (método principal, responsabilidade)
    - Rotas (método, URI, Controller, middleware)
    - Components Blade utilizados
    - Regras de negócio implementadas
    - Testes existentes
    - Status: 🟢 Completo, 🟡 Em Progresso, ou 🔴 Pendente

Não invente informação — documente apenas o que existe no código.
```

**Uso:** `/docs clientes` → atualiza a doc do módulo de clientes

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
                    "prompt": "Resuma em 2-3 linhas o que foi feito nesta sessão. Escreva o resumo no formato '- [DATA] RESUMO' e adicione ao final do arquivo .claude/memory-log.md. Se o arquivo não existir, crie com o header '# Log de Sessões'. Use a data de hoje."
                }
            ]
        }
    ]
}
```

Esse hook usa `type: "prompt"` em vez de `command` — ou seja, ele pede ao próprio Claude para gerar o resumo e gravar no arquivo. Resultado: um arquivo `.claude/memory-log.md` que cresce automaticamente com tudo que foi feito:

```markdown
# Log de Sessões

- [2026-04-10] Criada migration de clientes, model com relationships, e ClienteService
- [2026-04-11] Implementado CRUD de usuários com DataTable Livewire e export Excel
- [2026-04-12] Ajustado filtro de status na listagem, adicionados 5 testes unitários
```

---

## 5. Estrutura de Arquivos Claude Code

```
<projeto>/
├── CLAUDE.md                          ← Contexto principal (raiz)
├── .claude/
│   ├── settings.json                  ← Hooks e configurações
│   ├── commands/                      ← Slash commands customizados
│   │   ├── modulo.md                  ← /modulo nome
│   │   ├── review.md                  ← /review
│   │   └── docs.md                    ← /docs nome
│   └── memory-log.md                  ← Log automático de sessões (gerado pelo hook)
└── docs/                              ← Documentação do projeto
```

---

## 6. Setup Completo (Executar Uma Vez)

```bash
cd <projeto>

# 1. Criar estrutura de diretórios
mkdir -p .claude/commands

# 2. Criar os slash commands (copiar conteúdo das seções 3.1 a 3.3 acima)
# Cada arquivo .md vai em .claude/commands/

# 3. Criar o settings.json com hooks (copiar seção 4 acima)
# Vai em .claude/settings.json

# 4. Adicionar memórias rápidas na primeira sessão do Claude Code:
# Abrir claude e digitar:
#   # sempre usar Enums PHP para campos de status
#   # valores monetários em centavos (int), nunca float
#   # ao terminar módulo, atualizar a doc do módulo
#   # commits: tipo(escopo): descrição

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

Durante o dia:
  → Trabalhar normalmente
  → Hook roda Pint em todo .php editado (automático)
  → Hook roda Prettier em todo .blade.php editado (automático)
  → # (hashtag) para registrar novas regras quando surgir

Fim do dia:
  → Encerrar sessão
  → Hook Stop grava resumo em .claude/memory-log.md (automático)

Ao concluir um módulo:
  → /docs clientes  (atualiza doc do módulo trabalhado)
  → /review         (revisa os arquivos alterados)
```
