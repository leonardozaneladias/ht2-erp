# Guia Completo — Linear no Projeto Portal ArtFinal

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 09/04/2026

---

## 1. Por Que Linear

O Linear é a ferramenta de gestão de projeto mais alinhada com o perfil deste projeto:

- **Dev solo/time pequeno** — O Linear não exige configuração pesada. Funciona bem desde o primeiro dia, diferente do Jira que precisa de semanas de setup
- **Cycles = Sprints de 7 dias** — O conceito de Cycles mapeia perfeitamente para as sprints do PRD
- **Keyboard-first** — Tudo via atalhos. `C` cria issue, `S` muda status, `⌘K` abre command palette
- **GitHub sync nativo** — PRs linkados às issues automaticamente, status atualiza sozinho
- **MCP Server** — O Claude pode criar e gerenciar issues diretamente na conversa
- **Plano gratuito** — Suficiente para o projeto inteiro na fase atual

---

## 2. Setup Inicial — Passo a Passo

### 2.1 Criar Workspace

1. Acessar [linear.app](https://linear.app) e criar conta (com GitHub de preferência)
2. Nome do Workspace: **HT2ML TECH**
3. Isso será o espaço geral da empresa. Projetos diferentes ficam separados dentro dele

### 2.2 Criar Team

O Team no Linear é o equivalente a um time/departamento. Para dev solo, criar apenas um:

- **Nome:** Portal ArtFinal
- **Identificador:** `PAF` (este vira o prefixo das issues: PAF-1, PAF-2, etc.)
- **Descrição:** Sistema de Gerenciamento de Formaturas

### 2.3 Conectar GitHub (FAZER IMEDIATAMENTE)

Esta é a integração mais importante. Sem ela, você perde a automação que faz o Linear valer a pena.

1. Ir em **Settings → Integrations → GitHub**
2. Instalar o GitHub App e autorizar o repositório `portalartfinal_v2`
3. Habilitar:
    - Auto-link PRs com issues (ativado por padrão)
    - Auto-close issues quando PR é merged
    - Sync de status (PR aberto → In Progress, PR merged → Done)

### 2.4 Configurar Cycles (Sprints)

1. Ir em **Team Settings → Cycles**
2. Ativar **Enable Cycles**
3. Configurar:
    - **Duração:** 1 semana (7 dias, alinhado com o PRD)
    - **Dia de início:** Segunda-feira
    - **Auto-rollover:** Ativado (issues não concluídas passam automaticamente para o próximo cycle)
    - **Cooldown:** Nenhum (sprints são contínuas)

### 2.5 Configurar Workflow (Status)

Manter os status padrão do Linear e adicionar um:

| Status        | Categoria | Quando Usar                           |
| ------------- | --------- | ------------------------------------- |
| `Backlog`     | Backlog   | Issue identificada mas não priorizada |
| `Todo`        | Unstarted | Issue priorizada para a sprint atual  |
| `In Progress` | Started   | Estou trabalhando nisso agora         |
| `In Review`   | Started   | Código pronto, em revisão ou teste    |
| `Done`        | Completed | Entregue e validado                   |
| `Cancelled`   | Cancelled | Issue cancelada ou descartada         |

---

## 3. Labels — Organização por Cor

Labels são a forma de categorizar issues transversalmente (uma issue pode ter múltiplos labels).

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

---

## 4. Projects — Fases do PRD

Projects no Linear representam iniciativas maiores que contêm múltiplas issues. Mapear para as fases do PRD:

| Project                 | Sprints | Descrição                             | Target Date |
| ----------------------- | ------- | ------------------------------------- | ----------- |
| 🏗️ Fundação             | 1-3     | Setup, migrations, models, seeders    | Semana 3    |
| 🌐 Portal Adesão        | 4-9     | Layout, wizard 7 etapas, gateway mock | Semana 9    |
| 👤 Portal Área Formando | 10-11   | Login, dashboard, extrato, extras     | Semana 11   |
| 🏦 Gateway Itaú         | 12-13   | Boleto, PIX, Cartão, webhooks         | Semana 13   |
| 📧 E-mails              | 14      | Templates, automações, refinamentos   | Semana 14   |
| 🔧 Admin Core           | 15-19   | Auth, ACL, CRUDs base                 | Semana 19   |
| 💰 Admin Financeiro     | 20-23   | Formandos, parcelas, relatórios       | Semana 23   |
| ✅ Admin Final          | 24      | Usuários, perfis, auditoria           | Semana 24   |
| 🚀 Homologação          | 25-26   | Testes, ajustes, go-live              | Semana 26   |

Cada Project tem:

- **Start Date** e **Target Date** definidos
- **Status:** Planned → In Progress → Completed
- **Description:** Link para o PRD seção correspondente
- Issues vinculadas via Cycle

---

## 5. Issue Templates — Padronização

Criar templates para garantir que toda issue tenha informação suficiente.

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
3. [passo 3]

## Comportamento Esperado

[O que deveria acontecer]

## Comportamento Atual

[O que está acontecendo]

## Ambiente

- Área: [admin / portal]
- Browser: [Chrome / Safari / Mobile]
- Rota: [URL onde o bug acontece]

## Screenshots / Logs

[Se aplicável]
```

### 5.3 Template: Tarefa de Sprint (Subtask)

```markdown
## O que fazer

[Descrição curta e objetiva]

## Arquivos envolvidos

- `app/Services/...`
- `resources/views/...`

## Estimativa

[1h / 2h / 3h / 4h]

## Depende de

- [PAF-XX] (se houver dependência)
```

---

## 6. Fluxo de Trabalho Diário

### 6.1 Início do Dia

1. Abrir Linear → **My Issues** (atalho: `G` + `I`)
2. Ver as issues atribuídas no cycle atual
3. Pegar a primeira issue com status `Todo`
4. Mudar para `In Progress` (atalho: `S` → selecionar status)
5. Copiar nome da branch: atalho `⌘ + Shift + .` (copia `feature/paf-XX-descricao`)

### 6.2 Durante o Desenvolvimento

```bash
# Branch criada automaticamente pelo Linear
git checkout -b feature/paf-42-wizard-etapa-3

# Trabalhar no código...

# Commits referenciando a issue
git commit -m "feat(portal): implementar seleção de pacotes — PAF-42"

# Push
git push origin feature/paf-42-wizard-etapa-3
```

### 6.3 PR no GitHub

O PR deve referenciar a issue no título ou descrição:

```
## PR: Implementar etapa 3 do wizard (PAF-42)

Closes PAF-42

### O que foi feito
- Criado componente StepProdutos (Livewire)
- Implementado ProdutoDisponibilidadeService
- Criados testes para grupo exclusivo

### Checklist
- [x] Pint rodou sem erros
- [x] Prettier formatou Blade
- [x] PHPStan passou
- [x] Testes passaram
```

Quando o PR é merged:

- Linear automaticamente move PAF-42 para `Done`
- O commit fica linkado na issue
- O PR fica visível na timeline da issue

### 6.4 Fim do Dia

1. Atualizar status das issues em progresso
2. Adicionar comentário nas issues com notas do que falta (se não terminou)
3. Verificar se alguma issue ficou órfã no `Todo` sem ser iniciada

---

## 7. Fluxo de Sprint (Semanal)

### 7.1 Segunda — Planejamento (30 min)

1. Verificar o que ficou da sprint anterior (auto-rollover)
2. Ler o detalhamento da sprint no PRD (seção 20.3)
3. Criar issues para cada tarefa da sprint (usar template de Sprint)
4. Atribuir todas ao Cycle atual
5. Definir prioridades (Urgent / High / Medium / Low)
6. Vincular ao Project correto

### 7.2 Terça a Sexta — Execução

- Seguir o fluxo diário (seção 6)
- Mover issues pelo workflow: `Todo → In Progress → In Review → Done`
- Ao encontrar bugs, criar issue com template de Bug

### 7.3 Sexta/Sábado — Retrospectiva (15 min)

1. Abrir **Cycle Insights** (Linear mostra automaticamente)
2. Verificar:
    - Quantas issues foram completadas
    - Quantas ficaram para rollover
    - Se houve scope creep (issues adicionadas no meio do cycle)
3. Anotar lições aprendidas no comentário do Cycle
4. Se a sprint encerrou uma fase (ex: Sprint 3 = fim da Fundação):
    - Atualizar o Project para `Completed`
    - Criar tag de versão no Git

---

## 8. Views Customizadas (Salvar no Linear)

Criar estas views para acesso rápido:

### 8.1 "Sprint Ativa"

- **Filtro:** Cycle = current, Status ≠ Done, Status ≠ Cancelled
- **Agrupamento:** Por status
- **Ordenação:** Por prioridade
- **Uso:** Dashboard diário — o que preciso fazer agora

### 8.2 "Portal — Tudo"

- **Filtro:** Label = `portal`
- **Agrupamento:** Por Project
- **Uso:** Visão geral de todo o trabalho do portal

### 8.3 "Admin — Tudo"

- **Filtro:** Label = `admin`
- **Agrupamento:** Por Project
- **Uso:** Visão geral de todo o trabalho do admin

### 8.4 "Bugs Abertos"

- **Filtro:** Label = `bug`, Status ≠ Done, Status ≠ Cancelled
- **Ordenação:** Por prioridade
- **Uso:** Triagem de bugs

### 8.5 "Debt Técnico"

- **Filtro:** Label = `debt`, Status = Backlog
- **Uso:** Coisas para resolver quando sobrar tempo

---

## 9. Integração Linear + MCP (Claude Code, Cursor, Claude.ai)

O Linear oferece um servidor MCP oficial hospedado remotamente em `https://mcp.linear.app/mcp`. Não precisa rodar nada local — o servidor é gerenciado pela própria Linear, usa OAuth 2.1 para autenticação, e funciona em Claude Code, Cursor, Claude.ai (chat web) e outros clientes MCP.

Isso significa que você pode criar issues, atualizar status, consultar sprints e gerenciar o projeto inteiro sem sair do terminal ou da conversa.

### 9.1 Setup no Claude Code (CLI)

O Claude Code suporta MCP nativamente. O setup é um único comando:

```bash
# 1. Adicionar o servidor MCP do Linear
claude mcp add --transport http linear-server https://mcp.linear.app/mcp

# 2. Abrir uma sessão do Claude Code
claude

# 3. Dentro da sessão, rodar o comando de autenticação
/mcp
```

Ao rodar `/mcp`, o Claude Code abre o browser para você autorizar o acesso via OAuth. Faça login na sua conta Linear, autorize, e pronto. A partir daí, toda sessão do Claude Code terá acesso ao seu workspace Linear.

**Verificar se está funcionando:**

```bash
# Dentro do Claude Code, testar com:
"Liste os projetos do meu workspace Linear"
```

Se retornar seus projetos, está configurado.

**Dica:** A configuração do MCP é compartilhada entre Claude Code CLI e a extensão de IDE (Cursor/VS Code), então configurar em um lugar já funciona no outro.

### 9.2 Setup no Cursor

Duas opções:

**Opção A — Instalação automática (recomendada):**

1. Abrir Cursor
2. Ir na página de MCP tools do Cursor
3. Buscar "Linear"
4. Clicar "Add to Cursor"
5. Autorizar via OAuth quando solicitado

**Opção B — Configuração manual:**

1. `Ctrl/Cmd + P` → buscar `MCP: Add Server`
2. Colar a configuração:

```json
{
    "mcpServers": {
        "linear": {
            "command": "npx",
            "args": ["-y", "mcp-remote", "https://mcp.linear.app/mcp"]
        }
    }
}
```

3. Nome: `Linear` → Enter
4. Ativar via `MCP: List Servers` → selecionar Linear → Start Server
5. Autorizar via OAuth no browser

### 9.3 Setup no Claude.ai (Chat Web/App)

Se quiser usar o Linear diretamente aqui no Claude (chat web ou app desktop):

1. Ir em **Settings → Connectors** (no menu lateral)
2. Clicar **"Add more"**
3. Buscar **Linear** e conectar
4. Autorizar via OAuth

Após conectar, você pode pedir diretamente na conversa para criar/buscar/atualizar issues.

### 9.4 O Que o MCP do Linear Permite Fazer

| Ação                 | Exemplo de Prompt                                                                             |
| -------------------- | --------------------------------------------------------------------------------------------- |
| Criar issue          | "Crie uma issue: Implementar StepProdutos no wizard, label portal + feature, prioridade High" |
| Buscar issues        | "Quais issues estão In Progress na sprint atual?"                                             |
| Atualizar status     | "Mova PAF-42 para Done"                                                                       |
| Adicionar comentário | "Adicione um comentário na PAF-42: Testes passando, pronto para review"                       |
| Listar projetos      | "Liste os projetos ativos do Portal ArtFinal"                                                 |
| Consultar cycle      | "Qual o progresso do cycle atual? Quantas issues faltam?"                                     |
| Criar sub-issues     | "Quebre a PAF-50 em 3 sub-issues: migration, service e testes"                                |
| Buscar por label     | "Liste todas as issues com label bug que estão abertas"                                       |

### 9.5 Fluxo Real: Codando + Gerenciando Issues no Claude Code

Este é o fluxo mais produtivo — você desenvolve E gerencia o projeto no mesmo lugar:

```
# Sessão no Claude Code (terminal)

Você: "Estou começando a Sprint 7. Quebre as entregas do PRD
(seção 20.3 — Sprint 7) em issues no Linear.
Use labels portal + feature + mod:financeiro.
Prioridade High para o cálculo dinâmico, Medium para o resto."

Claude: [cria 8 issues no Linear via MCP, cada uma com labels e prioridade]

Você: "Agora implemente o ParcelamentoCalculatorService
conforme a issue PAF-71"

Claude: [lê os detalhes da issue PAF-71 via MCP, implementa o código]

Você: "Testes passaram. Mova PAF-71 para Done e comece a PAF-72"

Claude: [atualiza status via MCP, lê PAF-72, continua codando]
```

### 9.6 Fluxo com Agente PO (Planejamento de Sprint)

Combine o Agente PO (documento 08) com o MCP para automatizar o planejamento:

```
"Atue como PO do projeto Portal ArtFinal.

Quebre a Sprint 7 (Wizard Adesão: Etapa 5 — Pagamento com Cálculo Dinâmico)
em tarefas atômicas de 2-4h.

Para cada tarefa:
1. Defina o título da issue
2. Defina labels (área + tipo + módulo)
3. Defina prioridade
4. Defina dependências entre tarefas
5. Estime horas

Depois, crie todas as issues no Linear via MCP,
vincule ao Cycle da Sprint 7 e ao Project 'Portal Adesão'."
```

### 9.7 Troubleshooting do MCP

| Problema                     | Solução                                                                              |
| ---------------------------- | ------------------------------------------------------------------------------------ |
| Conexão falha no Claude Code | Rodar `claude mcp remove linear-server` e adicionar novamente                        |
| OAuth não abre o browser     | Verificar se o browser padrão está configurado no macOS                              |
| Erro de permissão            | Reautorizar em Linear Settings → Security & Access → OAuth Applications              |
| MCP lento ou instável        | A conexão remota é recente e pode ter instabilidades. Tentar novamente ou reconectar |
| Cursor não reconhece         | Verificar se `npx` está disponível no PATH. Rodar `npm install -g mcp-remote`        |

### 9.8 Checklist de Setup MCP

- [ ] **Claude Code:** `claude mcp add --transport http linear-server https://mcp.linear.app/mcp`
- [ ] **Claude Code:** Rodar `/mcp` e autorizar via OAuth
- [ ] **Cursor:** Adicionar via MCP tools page ou config manual
- [ ] **Claude.ai:** Conectar via Settings → Connectors
- [ ] **Testar:** "Liste os projetos do meu workspace Linear"
- [ ] **Validar:** Criar uma issue de teste e verificar que apareceu no Linear

---

## 10. Atalhos Essenciais do Linear

Estes são os atalhos que fazem o Linear ser mais rápido que qualquer alternativa. Investir 20 minutos aprendendo eles economiza horas por semana.

### 10.1 Navegação

| Atalho    | Ação                           |
| --------- | ------------------------------ |
| `⌘K`      | Command palette (busca global) |
| `G` + `I` | Ir para My Issues              |
| `G` + `V` | Ir para Views                  |
| `G` + `P` | Ir para Projects               |
| `G` + `C` | Ir para Cycles                 |
| `G` + `B` | Ir para Backlog                |

### 10.2 Issues

| Atalho          | Ação                                 |
| --------------- | ------------------------------------ |
| `C`             | Criar nova issue                     |
| `S`             | Mudar status                         |
| `A`             | Atribuir (assign)                    |
| `P`             | Mudar prioridade                     |
| `L`             | Adicionar label                      |
| `⌘ + Shift + C` | Copiar ID da issue                   |
| `⌘ + Shift + .` | Copiar nome da branch Git            |
| `X`             | Selecionar issue (para bulk actions) |
| `⌘ + Enter`     | Salvar issue (no modal de criação)   |

### 10.3 Bulk Actions

| Atalho                            | Ação                          |
| --------------------------------- | ----------------------------- |
| `X` (múltiplas) + `S`             | Mudar status de várias issues |
| `X` (múltiplas) + `A`             | Atribuir várias issues        |
| `X` (múltiplas) + `⌘ + Shift + M` | Mover para outro cycle        |

---

## 11. Boas Práticas

### 11.1 Issues

- **Títulos curtos e com verbo de ação:** "Implementar seleção de pacotes no wizard" em vez de "Pacotes"
- **Uma issue = uma entrega verificável:** Se não dá para validar isoladamente, subdividir
- **Sempre vincular ao PRD:** Adicionar referência à seção do PRD na descrição
- **Sub-issues para tarefas grandes:** Issue de 8h+ deve ter sub-issues de 2-4h
- **Sempre com labels:** Mínimo 2 labels (área + tipo)

### 11.2 Cycles

- **Não sobrecarregar:** Se a sprint anterior teve muitos rollovers, planejar menos na próxima
- **Incluir bugs:** Bugs não são "extras" — fazem parte do trabalho da sprint
- **Reservar 20% para imprevistos:** Em uma sprint de 40h, planejar 32h de tarefas
- **Scope freeze depois de terça:** Novas ideias vão para o Backlog, não para a sprint atual

### 11.3 Backlog

- **Manter limpo:** Issue no backlog há mais de 4 semanas sem ser priorizada → cancelar ou arquivar
- **Triagem semanal:** Revisar o backlog na segunda junto com o planejamento
- **Priorizar com contexto:** Adicionar comentário explicando POR QUE uma issue foi priorizada

### 11.4 Commits e Branch Naming

```bash
# Branch: sempre com prefixo do Linear
feature/paf-42-wizard-etapa-3
bugfix/paf-55-calculo-desconto
chore/paf-60-atualizar-seeders

# Commit: sempre com ID do Linear no final ou usando "Closes"
git commit -m "feat(portal): implementar seleção de pacotes — PAF-42"
git commit -m "fix(financeiro): corrigir cálculo de desconto — Closes PAF-55"
```

---

## 12. Métricas para Acompanhar

O Linear gera automaticamente métricas por Cycle. As mais importantes para este projeto:

| Métrica             | O que medir                             | Meta                          |
| ------------------- | --------------------------------------- | ----------------------------- |
| **Cycle Velocity**  | Issues completadas por sprint           | Estável (± 20% entre sprints) |
| **Completion Rate** | % de issues completadas vs planejadas   | > 80%                         |
| **Scope Creep**     | Issues adicionadas no meio da sprint    | < 3 por sprint                |
| **Rollover Rate**   | Issues que passam para a próxima sprint | < 20%                         |
| **Bug Ratio**       | % de bugs vs features                   | < 30%                         |

Se o Completion Rate ficar consistentemente abaixo de 70%, reduzir o número de issues por sprint. Se o Scope Creep ficar alto, melhorar a definição no planejamento de segunda.

---

## 13. Checklist de Setup Completo

Execute na ordem:

- [ ] Criar conta no [linear.app](https://linear.app)
- [ ] Criar workspace **HT2ML TECH**
- [ ] Criar team **Portal ArtFinal** (identificador: `PAF`)
- [ ] Conectar GitHub (integração bidirecional)
- [ ] Ativar Cycles (1 semana, segunda a domingo)
- [ ] Ativar auto-rollover
- [ ] Configurar workflow status (Backlog → Todo → In Progress → In Review → Done → Cancelled)
- [ ] Criar labels de Área (portal, admin, gateway, infra, docs)
- [ ] Criar labels de Tipo (feature, bug, refactor, chore, test, debt)
- [ ] Criar labels de Módulo (mod:auth, mod:contratos, mod:produtos, etc.)
- [ ] Criar Projects para cada fase do PRD (9 projects)
- [ ] Criar issue templates (Feature, Bug, Tarefa de Sprint)
- [ ] Criar views customizadas (Sprint Ativa, Portal, Admin, Bugs, Debt)
- [ ] Configurar auto-close de issues via GitHub merge
- [ ] Configurar auto-status (PR aberto → In Progress, PR merged → Done)
- [ ] Criar o primeiro Cycle (Sprint 01 — Setup do Projeto)
- [ ] Quebrar a Sprint 01 em issues e adicionar ao Cycle
- [ ] Testar o fluxo: criar issue → criar branch → commit → PR → merge → issue fecha
- [ ] **MCP Claude Code:** `claude mcp add --transport http linear-server https://mcp.linear.app/mcp`
- [ ] **MCP Claude Code:** Rodar `/mcp` e autorizar OAuth
- [ ] **MCP Cursor:** Adicionar via MCP tools page (buscar "Linear")
- [ ] **MCP Claude.ai:** Conectar via Settings → Connectors → Linear
- [ ] **MCP Teste:** Criar uma issue de teste via Claude Code e verificar no Linear

---

## 14. O Que Este Documento NÃO Cobre

- **Integrações com Slack/Notion:** Não necessárias para dev solo. Adicionar quando/se o time crescer
- **Roadmap público:** Não aplicável para este projeto (é sistema para cliente específico)
- **Time tracking:** O Linear não tem nativo. Se precisar, usar Toggl ou Clockify separadamente
- **Multi-team setup:** Um team é suficiente para este projeto. Separar em `Engineering` + `Design` só quando houver múltiplas pessoas

---

## 15. Referências

- [Linear Docs](https://linear.app/docs)
- [Linear Method — Princípios e Práticas](https://linear.app/method/introduction)
- [Linear + GitHub Integration](https://linear.app/docs/github-integration)
- [Linear Issue Templates](https://linear.app/docs/issue-templates)
- [Linear Cycles](https://linear.app/docs/use-cycles)
- [Linear Keyboard Shortcuts](https://linear.app/docs/keyboard-shortcuts)
- [Linear MCP Server — Docs Oficial](https://linear.app/docs/mcp)
- [Linear + Claude Integration](https://linear.app/integrations/claude)
- [Claude Code — MCP Docs](https://code.claude.com/docs/en/mcp)
