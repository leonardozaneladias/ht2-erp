# Guia Completo — Plane no Projeto Portal ArtFinal

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 10/04/2026  
**Substitui:** LINEAR-GUIDE.md (mantido como referência histórica)

---

## 1. Por Que Plane

O Plane é a ferramenta de gestão de projeto definitiva para este projeto:

- **Issues ilimitadas grátis** — Sem o gargalo de 250 do Linear
- **Guest gratuito** — O cliente acompanha o projeto sem custo
- **Cloud** — Acesso via `app.plane.so`, sem infra para gerenciar
- **Cycles = Sprints** — Mapeia perfeitamente para as sprints de 7 dias do PRD
- **MCP Server nativo** — O Claude Code cria/gerencia issues direto do terminal
- **GitHub integration** — PRs linkados às issues, status atualiza automaticamente
- **Wiki integrado** — Documentação dentro da ferramenta (bonus vs Linear)
- **Importador do Linear** — Sprint 0 migrada automaticamente
- **Open-source** — Sem vendor lock-in, pode self-host no futuro se precisar

### 1.1 Regra Obrigatória

O Plane é a **fonte de verdade** de gestão do projeto.

Esta regra se aplica a qualquer IDE AI ou agente automatizado usado no repositório.

Projeto canônico deste repositório no Plane:

- **Nome:** `Portal ArtFinal`
- **Project ID:** `c2538d40-6288-47ec-8c8d-a72576784901`

Regra operacional obrigatória:

1. Antes de iniciar trabalho relevante, localizar ou criar a issue correspondente no Plane
2. Toda issue deve estar associada ao module/cycle corretos quando aplicável
3. Durante a execução, manter state, comentários e bloqueios coerentes com o estado real
4. Ao concluir, atualizar a issue no Plane antes de considerar a tarefa encerrada
5. Ao mover uma issue para concluída, registrar no próprio item um resumo objetivo do que foi entregue, dos bloqueios encontrados e dos follow-ups remanescentes
6. Não usar checklist paralela fora do Plane como sistema principal de planejamento e acompanhamento

---

## 2. Setup Inicial — Passo a Passo

### 2.1 Criar Workspace

1. Acessar [app.plane.so](https://app.plane.so) e criar conta (com GitHub de preferência)
2. Nome do Workspace: **HT2ML TECH**
3. URL: `app.plane.so/ht2ml-tech`

### 2.2 Criar Projeto

No Plane, o equivalente ao "Team" do Linear é o **Project**:

- **Nome:** Portal ArtFinal
- **Identificador:** `PAF` (prefixo das issues: PAF-1, PAF-2, etc.)
- **Descrição:** Sistema de Gerenciamento de Formaturas
- **Rede:** Convite necessário (não público)

### 2.3 Conectar GitHub

1. Ir em **Workspace Settings → Integrations → GitHub**
2. Instalar o GitHub App e autorizar o repositório `portalartfinal_v2`
3. Configurar:
    - Auto-link PRs com issues
    - Auto-close issues quando PR é merged
    - Sync de status (PR aberto → In Progress, PR merged → Done)

### 2.4 Configurar Cycles (Sprints)

1. Ir em **Project Settings → Features → Cycles**
2. Ativar Cycles
3. Criar o primeiro Cycle:
    - **Nome:** Sprint 01 — Setup do Projeto
    - **Duração:** 7 dias
    - **Data início:** próxima segunda-feira

No Plane, Cycles são criados manualmente (diferente do Linear que auto-cria). Criar o Cycle da sprint atual no início de cada semana.

### 2.5 Convidar o Cliente como Guest

1. Ir em **Workspace Settings → Members**
2. Clicar **Add Member**
3. Inserir e-mail do cliente
4. Selecionar role: **Guest**
5. O cliente recebe um convite por e-mail e pode acessar via browser

O Guest pode: visualizar issues, comentar, ver progresso dos cycles. Não pode: criar issues, editar configurações, ver dados administrativos do workspace.

### 2.6 Configurar Workflow (States)

O Plane permite states customizados por projeto. Configurar:

| State         | Categoria | Cor      | Quando Usar                           |
| ------------- | --------- | -------- | ------------------------------------- |
| `Backlog`     | Backlog   | Cinza    | Issue identificada mas não priorizada |
| `Todo`        | Unstarted | Azul     | Issue priorizada para a sprint atual  |
| `In Progress` | Started   | Amarelo  | Estou trabalhando nisso agora         |
| `In Review`   | Started   | Roxo     | Código pronto, em revisão ou teste    |
| `Done`        | Completed | Verde    | Entregue e validado                   |
| `Cancelled`   | Cancelled | Vermelho | Issue cancelada                       |

Para configurar: **Project Settings → States**

---

## 3. Labels — Organização por Cor

### 3.1 Labels de Área (obrigatório em toda issue)

| Label     | Cor        | Descrição                             |
| --------- | ---------- | ------------------------------------- |
| `portal`  | 🔵 Azul    | Funcionalidades do Portal do Formando |
| `admin`   | 🟣 Roxo    | Funcionalidades do Backoffice Admin   |
| `gateway` | 🟠 Laranja | Integração com gateway de pagamentos  |
| `infra`   | ⚫ Cinza   | Docker, Laradock, configs, CI/CD      |
| `docs`    | 🟤 Marrom  | Documentação                          |

### 3.2 Labels de Tipo (obrigatório em toda issue)

| Label      | Cor               | Descrição                                |
| ---------- | ----------------- | ---------------------------------------- |
| `feature`  | 🟢 Verde          | Nova funcionalidade                      |
| `bug`      | 🔴 Vermelho       | Correção de bug                          |
| `refactor` | 🟡 Amarelo        | Refatoração sem mudança de comportamento |
| `chore`    | ⚪ Branco         | Tarefas de manutenção                    |
| `test`     | 🩵 Ciano          | Criação ou correção de testes            |
| `debt`     | 🟠 Laranja escuro | Débito técnico                           |

### 3.3 Labels de Módulo (opcional, para filtros avançados)

| Label            | Módulo PRD                     |
| ---------------- | ------------------------------ |
| `mod:auth`       | Autenticação (admin ou portal) |
| `mod:contratos`  | Contratos e turmas             |
| `mod:produtos`   | Pacotes e produtos             |
| `mod:adesao`     | Wizard de adesão               |
| `mod:financeiro` | Parcelas, extrato, cálculos    |
| `mod:emails`     | E-mails transacionais          |
| `mod:acl`        | Perfis e permissões            |
| `mod:auditoria`  | Logs de auditoria              |
| `mod:relatorios` | Relatórios e exports           |
| `mod:config`     | Configurações globais          |

Para criar: **Project Settings → Labels**

---

## 4. Modules — Fases do PRD

No Plane, **Modules** são o equivalente a "Projects" do Linear — agrupam issues de uma iniciativa maior. Mapear para as fases do PRD:

| Ordem | Module                           | Sprints   | Descrição                                                          | Target Date           |
| ----: | -------------------------------- | --------- | ------------------------------------------------------------------ | --------------------- |
|    01 | `01. 🏗️ Fundação`                | 1-3       | Setup, migrations, models, seeders                                 | Semana 3              |
|    02 | `02. 🎨 Análise Inspinia`        | Pré-Admin | Trilha documental e componentização preparatória do admin Inspinia | Antes do Portal/Admin |
|    03 | `03. 🌐 Portal Adesão`           | 4-9       | Layout, wizard 7 etapas, gateway mock                              | Semana 9              |
|    04 | `04. 👤 Portal Área do Formando` | 10-11     | Login, dashboard, extrato, extras                                  | Semana 11             |
|    05 | `05. 🏦 Gateway Itaú`            | 12-13     | Boleto, PIX, Cartão, webhooks                                      | Semana 13             |
|    06 | `06. 📧 E-mails e Notificações`  | 14        | Templates, automações, refinamentos                                | Semana 14             |
|    07 | `07. 🔧 Admin Core`              | 15-19     | Auth, ACL, CRUDs base                                              | Semana 19             |
|    08 | `08. 💰 Admin Financeiro`        | 20-23     | Formandos, parcelas, relatórios                                    | Semana 23             |
|    09 | `09. ✅ Admin Finalização`       | 24        | Usuários, perfis, auditoria                                        | Semana 24             |
|    10 | `10. 🚀 Homologação e Deploy`    | 25-26     | Testes, ajustes, go-live                                           | Semana 26             |

Regras para ordenação:

- usar o PRD como ordem canônica de execução
- manter prefixo numérico no nome do module quando o Plane/MCP não expuser ajuste direto de `sort_order`
- manter `🎨 Análise Inspinia` como `02`, logo após `Fundação`, pois ela prepara layout, catálogo e componentização base antes da implementação das telas

Para criar: **Sidebar → Modules → Create Module**

---

## 5. Issue Templates

O Plane suporta templates de issue. Criar estes templates padrão:

### 5.1 Template: Feature

```markdown
## Contexto

[Qual problema resolve ou qual funcionalidade implementa]

## Referência PRD

- Seção: [número]
- Sprint: [número]
- Módulo: [nome]

## Critérios de Aceite

- [ ] [critério 1]
- [ ] [critério 2]
- [ ] [critério 3]

## Implementação

- [ ] Model/Migration
- [ ] Service/Action
- [ ] FormRequest
- [ ] Controller
- [ ] View/Component
- [ ] Testes
- [ ] Documentação (.docs/modules/)

## Notas

[Observações técnicas, dependências, edge cases]
```

### 5.2 Template: Bug

```markdown
## Descrição

[O que está acontecendo de errado]

## Passos para Reproduzir

1. [passo 1]
2. [passo 2]

## Comportamento Esperado

[O que deveria acontecer]

## Comportamento Atual

[O que está acontecendo]

## Ambiente

- Área: [admin / portal]
- Browser/Device: [Chrome / Safari / Mobile]
- Rota: [URL]
```

### 5.3 Template: Tarefa de Sprint

```markdown
## O que fazer

[Descrição curta e objetiva]

## Arquivos envolvidos

- `app/Services/...`
- `resources/views/...`

## Estimativa

[1h / 2h / 3h / 4h]

## Depende de

- PAF-XX (se houver)
```

---

## 6. Fluxo de Trabalho Diário

### 6.1 Início do Dia

1. Abrir Plane → **My Work** (issues atribuídas a você)
2. Ver as issues no Cycle atual
3. Pegar a primeira issue com state `Todo`
4. Mudar para `In Progress`
5. Criar branch no Git seguindo o padrão:

```bash
git checkout -b feature/paf-42-wizard-etapa-3
```

### 6.2 Durante o Desenvolvimento

```bash
# Commits referenciando a issue
git commit -m "feat(portal): implementar seleção de pacotes — PAF-42"

# Push
git push origin feature/paf-42-wizard-etapa-3
```

### 6.3 PR no GitHub

Referenciar a issue no título ou descrição do PR:

```markdown
## PR: Implementar etapa 3 do wizard (PAF-42)

Closes PAF-42

### O que foi feito

- Criado componente StepProdutos (Livewire)
- Implementado ProdutoDisponibilidadeService

### Checklist

- [x] Pint rodou sem erros
- [x] Prettier formatou Blade
- [x] Testes passaram
```

Quando o PR é merged → Plane automaticamente move PAF-42 para `Done`.

### 6.4 Fim do Dia

1. Atualizar status das issues em progresso
2. Adicionar comentário se não terminou (o que falta)
3. Cliente Guest vê o progresso em tempo real

---

## 7. Fluxo de Sprint (Semanal)

### 7.1 Segunda — Planejamento (30 min)

1. Criar Cycle da semana: `Sprint XX — [Título]`
2. Ler o detalhamento da sprint no PRD (seção 20.3)
3. Criar issues para cada tarefa (usar templates)
4. Adicionar issues ao Cycle
5. Definir prioridades (Urgent / High / Medium / Low)
6. Vincular ao Module correto (fase do PRD)

### 7.2 Terça a Sexta — Execução

- Seguir o fluxo diário (seção 6)
- Mover issues pelo workflow: `Todo → In Progress → In Review → Done`
- Ao encontrar bugs, criar issue com template de Bug

### 7.3 Sexta — Retrospectiva (15 min)

1. Abrir o Cycle atual e ver o progresso
2. Burn-down chart mostra o andamento
3. Issues não concluídas → mover para o próximo Cycle
4. **Verificar Modules:** para cada Module que teve issues nesta sprint, checar se 100% das issues estão `Done`. Se sim, marcar o Module como `Completed` via MCP:

    ```
    "Marque o Module Fundação como Completed no Plane"
    ```

    **Critério objetivo:** `issues_done / issues_total = 100%` → Module fechado.
    Se ainda há issues abertas previstas para sprints futuras, manter como `In Progress`.

5. Se a sprint encerrou uma fase (sprints 3, 9, 11, 13, 14, 19, 23, 24, 26): criar tag de versão conforme `docs/02-CONVENTIONS.md §2`

---

## 8. Views Customizadas

O Plane permite criar views com filtros salvos. Criar estas:

### 8.1 "Sprint Ativa"

- **Filtro:** Cycle = atual, State ≠ Done, State ≠ Cancelled
- **Agrupamento:** Por state
- **Ordenação:** Por prioridade

### 8.2 "Portal — Tudo"

- **Filtro:** Label = `portal`
- **Agrupamento:** Por Module

### 8.3 "Admin — Tudo"

- **Filtro:** Label = `admin`
- **Agrupamento:** Por Module

### 8.4 "Bugs Abertos"

- **Filtro:** Label = `bug`, State ≠ Done, State ≠ Cancelled
- **Ordenação:** Por prioridade

### 8.5 "Para o Cliente Ver"

- **Filtro:** State = Done (última sprint)
- **Uso:** Compartilhar com o Guest para validação

Visualizações disponíveis: **Board** (Kanban), **List**, **Spreadsheet**, **Gantt**

---

## 9. Integração Plane + MCP (Claude Code, Cursor)

O Plane tem MCP server nativo. Funciona igual ao do Linear.

### 9.1 Setup no Claude Code

```bash
# Adicionar o MCP server do Plane
claude mcp add --transport http plane-server https://mcp.plane.so/mcp

# Abrir Claude Code
claude

# Autenticar
/mcp
```

### 9.2 Setup no Cursor

Adicionar ao MCP config:

```json
{
    "mcpServers": {
        "plane": {
            "command": "npx",
            "args": ["-y", "mcp-remote", "https://mcp.plane.so/mcp"]
        }
    }
}
```

### 9.3 O Que o MCP do Plane Permite

| Ação                 | Exemplo de Prompt                                                                   |
| -------------------- | ----------------------------------------------------------------------------------- |
| Criar issue          | "Crie uma issue: Implementar StepProdutos, label portal + feature, prioridade High" |
| Buscar issues        | "Quais issues estão In Progress no cycle atual?"                                    |
| Atualizar status     | "Mova PAF-42 para Done"                                                             |
| Adicionar comentário | "Comente na PAF-42: Testes passando, pronto para review"                            |
| Listar modules       | "Liste os modules ativos do Portal ArtFinal"                                        |
| Criar sub-issues     | "Quebre PAF-50 em 3 sub-issues: migration, service e testes"                        |

### 9.4 Fluxo Completo: Codando + Gerenciando no Claude Code

```
Você: "Quebre a Sprint 7 do PRD em issues no Plane.
Labels: portal + feature + mod:financeiro.
Cycle: Sprint 07. Module: Portal Adesão."

Claude: [cria 8 issues via MCP]

Você: "Implemente o ParcelamentoCalculatorService conforme PAF-71"

Claude: [lê a issue, implementa o código]

Você: "Testes passaram. Mova PAF-71 para Done."

Claude: [atualiza via MCP, continua com a próxima]
```

---

## 10. Acesso do Cliente (Guest)

### 10.1 O Que o Guest Pode Fazer

| Ação                   | Guest |
| ---------------------- | :---: |
| Ver issues e status    |  ✅   |
| Ver Cycles e progresso |  ✅   |
| Comentar em issues     |  ✅   |
| Ver Modules e timeline |  ✅   |
| Ver Pages/Wiki         |  ✅   |
| Criar issues           |  ❌   |
| Editar configurações   |  ❌   |
| Gerenciar members      |  ❌   |

### 10.2 Como o Cliente Acompanha

O cliente acessa `app.plane.so`, faz login com o convite, e vê:

- **Board view** — Kanban com todas as issues da sprint
- **Cycle progress** — Burn-down chart da sprint atual
- **Modules** — Progresso de cada fase do projeto
- **Comentários** — Pode comentar e dar feedback direto nas issues

### 10.3 Boas Práticas com Guest

- Ao finalizar uma sprint, adicionar um comentário resumo no Cycle para o cliente
- Usar issues com descrições claras (o cliente vai ler)
- Marcar issues de validação do cliente como `In Review` para que ele saiba o que precisa aprovar
- Criar uma view "Para o Cliente Ver" filtrando o que é relevante

---

## 11. Wiki/Pages (Bonus do Plane)

O Plane tem Wiki integrado — algo que o Linear não oferece. Aproveitar para:

- **Atas de reunião com o cliente** — Page por reunião
- **Decisões técnicas** — Documentar dentro do Plane em vez de e-mail
- **FAQ do projeto** — Respostas para perguntas recorrentes do cliente
- **Release notes** — O que foi entregue em cada sprint

A documentação técnica pesada continua em `.docs/` no repositório. O Wiki do Plane é para documentação leve e comunicação com o cliente.

---

## 12. Boas Práticas

### 12.1 Issues

- **Títulos com verbo de ação:** "Implementar seleção de pacotes no wizard"
- **Uma issue = uma entrega verificável**
- **Sempre referenciar o PRD:** Seção e sprint na descrição
- **Mínimo 2 labels:** área + tipo
- **Sub-issues para tarefas 8h+**

### 12.2 Cycles

- **Não sobrecarregar:** Se muitos rollovers, planejar menos na próxima
- **Incluir bugs:** Fazem parte da sprint
- **Reservar 20%:** Em 40h de sprint, planejar 32h
- **Scope freeze:** Depois de terça, novas ideias vão para Backlog

### 12.3 Commits e Branches

```bash
# Branch
feature/paf-42-wizard-etapa-3
bugfix/paf-55-calculo-desconto
chore/paf-60-atualizar-seeders

# Commit
git commit -m "feat(portal): implementar seleção de pacotes — PAF-42"
git commit -m "fix(financeiro): corrigir cálculo de desconto — Closes PAF-55"
```

---

## 13. Métricas

O Plane gera dashboards automáticos por Cycle:

| Métrica                              | Meta                                   |
| ------------------------------------ | -------------------------------------- |
| Burn-down                            | Convergindo para zero no fim da sprint |
| Issues completadas vs planejadas     | > 80%                                  |
| Issues adicionadas no meio da sprint | < 3                                    |
| Bugs vs features                     | < 30% bugs                             |

---

## 14. Checklist de Setup Completo

- [ ] Criar conta em [app.plane.so](https://app.plane.so)
- [ ] Criar workspace **HT2ML TECH**
- [ ] Criar projeto **Portal ArtFinal** (identificador: PAF)
- [ ] **Migrar do Linear** (ver MIGRATION-LINEAR-TO-PLANE.md)
- [ ] Configurar states (Backlog, Todo, In Progress, In Review, Done, Cancelled)
- [ ] Criar labels de Área (portal, admin, gateway, infra, docs)
- [ ] Criar labels de Tipo (feature, bug, refactor, chore, test, debt)
- [ ] Criar labels de Módulo (mod:auth, mod:contratos, etc.)
- [ ] Criar Modules para cada fase do PRD (9 modules)
- [ ] Conectar GitHub (integração bidirecional)
- [ ] Convidar cliente como Guest
- [ ] Criar views customizadas (Sprint Ativa, Portal, Admin, Bugs, Cliente)
- [ ] Configurar MCP: `claude mcp add --transport http plane-server https://mcp.plane.so/mcp`
- [ ] Testar MCP: "Liste os projetos do meu workspace Plane"
- [ ] Remover MCP do Linear: `claude mcp remove linear-server`
- [ ] Atualizar CLAUDE.md (trocar Linear por Plane)

---

## 15. Diferenças de Terminologia Linear → Plane

Se você já se acostumou com o Linear, esta tabela ajuda na transição:

| Conceito              | Linear      | Plane             |
| --------------------- | ----------- | ----------------- |
| Container de trabalho | Team        | Project           |
| Sprint                | Cycle       | Cycle             |
| Épico/Iniciativa      | Project     | Module            |
| Tarefa                | Issue       | Work Item / Issue |
| Status                | State       | State             |
| Filtro salvo          | View        | View              |
| Documentação          | — (não tem) | Pages / Wiki      |
| Acesso externo        | — (pago)    | Guest (grátis)    |

---

## 16. Referências

- [Plane Docs](https://docs.plane.so)
- [Plane Cloud](https://app.plane.so)
- [Plane GitHub](https://github.com/makeplane/plane)
- [Plane MCP Server](https://github.com/makeplane/plane-mcp-server)
- [Plane Import from Linear](https://docs.plane.so/importers/linear)
- [Plane Pricing](https://plane.so/pricing)
- [Plane Billing & Plans](https://docs.plane.so/workspaces-and-users/billing-and-plans)
