# Prompt para Claude Code — Setup Completo

Cole este prompt inteiro no Claude Code para configurar tudo de uma vez.

---

Leia o CLAUDE.md na raiz do projeto para ter contexto.

Preciso que você faça o setup completo do Claude Code para este projeto. Execute os seguintes passos:

## 1. Substituir o .claude/settings.local.json

Substitua o conteúdo do `.claude/settings.local.json` pelo arquivo que está em `.docs/settings.local.json` (eu vou colocar lá). Ele contém:

- Todas as permissões atuais do Linear (mantidas)
- Novas permissões para o Plane MCP (wildcard)
- Permissões simplificadas para git, npm, php, composer, make
- Hooks PostToolUse: Pint automático em .php, Prettier automático em .blade.php
- Hook Stop: log de sessão + resumo automático em .claude/memory-log.md

## 2. Criar os 5 Slash Commands

Crie a pasta `.claude/commands/` e os seguintes arquivos:

### .claude/commands/sprint.md

```
Leia o PRD em .docs/PRD_v3.1.0.md, seção 20.3, e encontre a Sprint $ARGUMENTS.

Para esta sprint:
1. Liste todas as entregas definidas no PRD
2. Quebre cada entrega em tarefas de 2-4 horas
3. Para cada tarefa, defina: título, labels (área + tipo + módulo), prioridade, estimativa
4. Organize as tarefas por dia (7 dias)
5. Identifique dependências entre tarefas

Siga os padrões de .docs/CONVENTIONS.md para naming.
Siga a estrutura de .docs/ARCHITECTURE-GUIDE.md para decisão Service vs Action vs Job.
Lembre: arquitetura API-Ready — Services nunca recebem Request, retornam DTOs com toArray().

Se o MCP do Plane estiver conectado, crie as issues automaticamente.
```

### .claude/commands/modulo.md

```
Implemente o módulo $ARGUMENTS do projeto Portal ArtFinal.

Antes de começar:
1. Leia .docs/PRD_v3.1.0.md e encontre a seção relevante do módulo
2. Leia .docs/ARCHITECTURE-GUIDE.md para padrões (incluindo seção 5 — API-Ready)
3. Leia .docs/CONVENTIONS.md para padrões de código
4. Leia .docs/TEMPLATE-MAP-AND-COMPONENTS.md se envolver UI
5. Verifique se existe .docs/modules/ com doc desse módulo

Implemente seguindo API-Ready:
- Model(s) com relationships, casts, scopes
- Migration(s) com índices e constraints
- Enum(s) se necessário
- DTO(s) com toArray() para transporte
- FormRequest(s) com validações completas
- Service(s) que recebem dados tipados e retornam DTOs (NUNCA Request, NUNCA redirect)
- Action(s) se operação atômica
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

### .claude/commands/review.md

```
Faça uma revisão de código dos arquivos alterados no git (staged ou último commit).

Checklist de revisão (de .docs/CONVENTIONS.md):
1. [ ] Controller magro? (máx 5-7 linhas por método)
2. [ ] Validação em FormRequest?
3. [ ] Valores monetários em centavos?
4. [ ] Enums em vez de strings mágicas?
5. [ ] Type hints e return types em tudo?
6. [ ] Sem lógica de negócio no Controller?
7. [ ] API-Ready: Service recebe dados tipados (não Request)?
8. [ ] API-Ready: Service retorna DTO (não redirect/view/json)?
9. [ ] API-Ready: DTO tem toArray()?
10. [ ] Testes para cenários críticos?
11. [ ] Sem dados sensíveis em logs?
12. [ ] N+1 tratado (eager loading)?
13. [ ] Componente Blade reutilizável?
14. [ ] declare(strict_types=1)?
15. [ ] Conventional Commits no último commit?

Para cada problema encontrado, indique: arquivo, linha, o que está errado, como corrigir.
```

### .claude/commands/docs.md

```
Atualize a documentação do módulo $ARGUMENTS.

1. Leia o código atual do módulo (Models, Services, Controllers, Routes, DTOs)
2. Leia o template de documentação em .docs/modules/01-auth-admin.md como referência
3. Atualize o arquivo .docs/modules/ correspondente com:
   - Models envolvidos (campos principais)
   - Services e Actions (método principal, parâmetros tipados, DTO retornado)
   - Rotas (método, URI, Controller, middleware)
   - Components Blade utilizados
   - Regras de negócio implementadas
   - Testes existentes
   - Status: 🟢 Completo, 🟡 Em Progresso, ou 🔴 Pendente
   - Changelog do módulo com a data de hoje

Não invente informação — documente apenas o que existe no código.
```

### .claude/commands/fim-sprint.md

```
Finalize a Sprint $ARGUMENTS do projeto Portal ArtFinal.

Execute:
1. Rode ./vendor/bin/pint --test (verificar formatação PHP)
2. Rode npx prettier --check "resources/" (verificar Blade/JS/CSS)
3. Rode ./vendor/bin/phpstan analyse (análise estática)
4. Rode php artisan test (testes)
5. Liste os módulos que foram alterados nesta sprint
6. Para cada módulo alterado, verifique se .docs/modules/ está atualizado
7. Atualize .docs/CHANGELOG.md com as entregas da sprint
8. Sugira a mensagem de commit final
9. Sugira a tag de versão se for fim de fase
10. Verifique se há Services que violam API-Ready (recebem Request ou retornam redirect)

Relate o resultado de cada etapa.
```

## 3. Criar o arquivo de memory-log

Crie `.claude/memory-log.md` com:

```markdown
# Log de Sessões — Portal ArtFinal

<!-- Este arquivo é atualizado automaticamente pelo hook Stop do Claude Code -->
```

## 4. Verificar que tudo foi criado

Liste a estrutura `.claude/` e confirme que contém:

```
.claude/
├── settings.local.json     ← Com permissões + hooks
├── commands/
│   ├── sprint.md
│   ├── modulo.md
│   ├── review.md
│   ├── docs.md
│   └── fim-sprint.md
└── memory-log.md           ← Log automático de sessões
```

## 5. Registrar memórias rápidas

Adicione estas memórias com #:

```
# arquitetura API-Ready: Services nunca recebem Request, retornam DTOs com toArray()
# ao criar Service, parâmetros tipados (Model, Enum, int, string, Carbon), nunca Request
# todo DTO deve ter método toArray() para serialização JSON futura
# PRD menciona Metronic = substituído por Inspinia (Tailwind 4)
# gestão de projeto migrou de Linear para Plane Cloud (app.plane.so)
```
