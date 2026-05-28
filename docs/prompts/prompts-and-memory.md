# Prompts, Memória e Contexto para IA

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 09/04/2026

---

## 1. Arquivo CLAUDE.md (colocar na raiz do projeto)

Este arquivo é lido automaticamente pelo Claude Code quando você abre o projeto.

```markdown
# Portal ArtFinal — Sistema de Gerenciamento de Formaturas

## O que é

Sistema web para gerenciamento de formaturas com dois ambientes independentes:

- **Admin (Backoffice)** — gestão de contratos, produtos, formandos, financeiro
- **Portal (Formando)** — adesão, área autenticada, extrato, extras

## Stack

- Laravel 13, PHP 8.4, PostgreSQL 16, Redis
- Livewire 3, Tailwind CSS 4, Inspinia Template (admin)
- Horizon (filas), Pulse (monitoramento)
- Docker via Laradock (ver .docs/INFRA.md)

## Documentação

Toda a documentação está em `.docs/`:

- `PRD_v3.0.md` — PRD completo (nota: menciona "Metronic" → ler como Inspinia)
- `ARCHITECTURE-GUIDE.md` — Estrutura de pastas, padrões, quando usar Service/Action/Job
- `CONVENTIONS.md` — Padrões de commit, naming, código, cache, erros, performance
- `TOOLS-AND-PACKAGES.md` — Pacotes instalados e justificativas
- `TEMPLATE-MAP-AND-COMPONENTS.md` — Mapeamento do Inspinia e catálogo de componentes
- `PROMPTS-AND-MEMORY.md` — Prompts por fase, checklists de sprint
- `PADRONIZACAO-SPRINTS-AGENTES.md` — Pint, Prettier, ESLint, Husky, agentes IA
- `LINEAR-GUIDE.md` — Guia Linear + MCP (Claude Code, Cursor)
- `modules/*.md` — Documentação modular (um arquivo por módulo)

## Padrões obrigatórios

1. Controllers magros (5-7 linhas por método), Services gordos
2. FormRequest para TODA validação de input
3. Enums PHP 8.1+ para campos com valores finitos
4. DTOs (readonly classes) para transporte entre camadas
5. Valores monetários SEMPRE em centavos (int)
6. Append-only para audit_logs (nunca deletar)
7. Snapshots imutáveis para dados de adesão
8. Componentes Blade reutilizáveis (nunca copiar HTML)
9. Conventional Commits em português
10. Testes para Services críticos (especialmente cálculo de parcelas)

## Guards de Autenticação

- `admin` → AdminUser (tabela admin_users)
- `portal` → PortalUser (tabela portal_users)
  Nunca misturar. São mundos completamente separados.

## Rotas

- `/admin/*` → routes/admin.php (guard admin)
- `/portal/*` → routes/portal.php (guard portal)
- `/webhook/*` → routes/webhook.php (sem CSRF)

## Sprint Atual

Sprint: [ATUALIZAR]
Fase: [ATUALIZAR]
Foco: [ATUALIZAR]

## Importante

- O PRD menciona "Metronic" como template — foi substituído por **Inspinia (Tailwind 4)**
- Template do Portal: decisão pendente entre **Preline UI** e **Tailwind puro** (definir Sprint 4)
- Antes de criar qualquer componente, verificar se já existe em `.docs/TEMPLATE-MAP-AND-COMPONENTS.md`
- Antes de criar um Service, verificar se o PRD já define o nome (Section 20.3 - Sprints)
- Após terminar um módulo, atualizar `.docs/modules/XX-nome.md`
- Ao instalar pacotes novos, atualizar `.docs/TOOLS-AND-PACKAGES.md`
```

---

## 2. Memória do Claude (memory_user_edits)

Informações essenciais para manter no sistema de memória do Claude para este projeto:

```
1. Leonardo está desenvolvendo o Portal ArtFinal — sistema de formaturas com Laravel 13, Inspinia template (Tailwind 4), Livewire 3, PostgreSQL 16
2. O sistema tem dois frontends independentes: Admin (Inspinia) e Portal do Formando (Preline UI ou Tailwind puro, mobile-first)
3. Estratégia Portal-First: Portal é desenvolvido antes do Admin, com dados via seeders
4. Documentação em .docs/ com módulos separados em .docs/modules/
5. Padrões: Controllers magros, Services gordos, FormRequests obrigatórios, valores em centavos, Conventional Commits em PT-BR
6. Ambiente: Docker via Laradock (macOS), PHP 8.4, Redis, Horizon, Mailpit
7. PRD: 31 tabelas, 26 sprints, wizard de 7 etapas, gateway Itaú, ACL com Spatie Permission
8. PRD menciona Metronic = substituído por Inspinia
```

---

## 3. Prompts por Fase do Projeto

### 3.1 Prompt — Sprint 1: Setup do Projeto

```
Estou na Sprint 1 do Portal ArtFinal. Preciso que você:

1. Crie a estrutura de pastas conforme .docs/ARCHITECTURE-GUIDE.md
2. Configure o vite.config.js com dois entry points (admin.css/js e portal.css/js)
3. Configure o tailwind.config.js integrando o Inspinia
4. Configure config/auth.php com os dois guards (admin e portal)
5. Crie os arquivos de rotas separados (routes/admin.php, routes/portal.php, routes/webhook.php)
6. Configure o RouteServiceProvider para carregar as rotas separadas
7. Crie os layouts base (admin e portal) usando componentes Blade
8. Crie o CLAUDE.md na raiz do projeto

Stack: Laravel 13, PHP 8.4, Tailwind CSS 4 (Inspinia), Livewire 3
Ambiente: Laradock (ver .docs/INFRA.md)
Padrões: ver .docs/CONVENTIONS.md
```

### 3.2 Prompt — Criar Módulo (genérico)

```
Preciso criar o módulo [NOME_MÓDULO] do Portal ArtFinal.

Contexto:
- PRD: seção [NÚMERO] do .docs/PRD_v3.0.md
- Sprint: [NÚMERO]
- Área: [admin | portal]

Preciso que você:
1. Crie o(s) Model(s) com relationships, casts e scopes
2. Crie a(s) Migration(s) com índices e constraints
3. Crie o(s) FormRequest(s) com validações completas
4. Crie o(s) Service(s) com a lógica de negócio
5. Crie o(s) Controller(s) magros (máx 5-7 linhas por método)
6. Crie as views Blade usando componentes do catálogo
7. Defina as rotas em routes/[admin|portal].php
8. Crie o Factory para testes
9. Atualize o .docs/modules/[XX-nome].md

Siga os padrões de:
- .docs/CONVENTIONS.md (naming, code style)
- .docs/ARCHITECTURE-GUIDE.md (quando usar Service vs Action vs Job)
- .docs/TEMPLATE-MAP-AND-COMPONENTS.md (componentes visuais)
```

### 3.3 Prompt — Criar CRUD Admin

```
Preciso criar o CRUD completo de [ENTIDADE] no backoffice admin.

Referência visual: PRD seção 14.[X] — Tela: [NOME_DA_TELA]
Rota base: /admin/[recurso]

O CRUD deve incluir:
1. Listagem com DataTable (busca, filtros, ordenação, paginação)
   - Componente Livewire para tabela dinâmica
   - Filtros conforme PRD (collapsable)
   - Ações por linha (dropdown)
   - Exportação CSV/Excel

2. Formulário de criação/edição
   - FormRequest com validações
   - Campos conforme tabela do PRD
   - Upload de imagem (se aplicável)
   - Máscaras de input
   - Feedback de validação em tempo real (Livewire)

3. Confirmação de exclusão/inativação (modal)

4. Todas as regras de negócio descritas no PRD

Template: Inspinia (Tailwind 4)
Components: usar catálogo em .docs/TEMPLATE-MAP-AND-COMPONENTS.md
Padrões: ver .docs/CONVENTIONS.md
```

### 3.4 Prompt — Análise Geral do Template Inspinia (pós-compra)

```
Tenho o template Inspinia Multipurpose Admin Dashboard (versão Tailwind + Laravel) descompactado em [CAMINHO].

Preciso de uma análise completa em 3 etapas:

## ETAPA 1 — Mapeamento Estrutural
1. Liste toda a árvore de pastas e arquivos
2. Identifique o sistema de build (Vite? Webpack?)
3. Mapeie layouts Blade (quantos, nomes, herança)
4. Liste todos os partials/components Blade
5. Identifique os entry points CSS e JS
6. Documente o sistema de temas/skins e como trocar
7. Liste os plugins JS com versões
8. Verifique se tem Livewire, Inertia ou outro framework reativo

## ETAPA 2 — Curadoria para o Projeto
Com base no mapeamento do meu projeto (.docs/TEMPLATE-MAP-AND-COMPONENTS.md):
1. Para cada componente que preciso, indique qual arquivo do Inspinia usar como base
2. Indique quais assets CSS/JS são realmente necessários e quais descartar
3. Identifique conflitos potenciais com Livewire/Alpine.js
4. Sugira a configuração ideal do tailwind.config.js integrando Inspinia + custom

## ETAPA 3 — Plano de Extração
1. Defina a ordem de extração dos componentes (prioridade do projeto)
2. Para cada componente, escreva o passo-a-passo de conversão para Blade component
3. Identifique dependências entre componentes (ex: DataTable depende de Pagination)
4. Estime o esforço de extração

Gere um relatório em .docs/TEMPLATE-ANALYSIS-FULL.md
```

### 3.5 Prompt — Análise de Lacunas (Gap Analysis)

```
Faça uma análise de lacunas entre o PRD (.docs/PRD_v3.0.md) e a estrutura atual do projeto.

Para cada módulo do PRD (seções 3 a 20), identifique:

1. O que já está implementado
2. O que falta implementar
3. Quais pacotes/ferramentas estão faltando
4. Quais decisões técnicas ainda precisam ser tomadas
5. Riscos ou complexidades não endereçadas

Organize o resultado como uma matriz:

| Módulo | Status | Falta | Pacote Necessário | Risco |
|--------|--------|-------|-------------------|-------|

Ao final, sugira uma ordem de prioridade de implementação.
```

---

## 4. Prompt Template para Documentação de Módulo

Usar este template ao criar cada arquivo `.docs/modules/XX-nome.md`:

```markdown
# Módulo: [Nome do Módulo]

**Sprint:** [Sprint onde foi criado]
**Última Atualização:** [data]
**Status:** 🟢 Completo | 🟡 Em Progresso | 🔴 Pendente
**Referência PRD:** Seção [X.X]

## Escopo

[Descrição do que este módulo faz]

## Models Envolvidos

| Model | Tabela | Campos Principais |
| ----- | ------ | ----------------- |
|       |        |                   |

## Services e Actions

| Classe | Método Principal | Responsabilidade |
| ------ | ---------------- | ---------------- |
|        |                  |                  |

## Rotas

| Método | URI | Controller@Action | Middleware |
| ------ | --- | ----------------- | ---------- |
|        |     |                   |            |

## Components Blade Utilizados

- `x-admin.[nome]` — [descrição]
- `x-shared.[nome]` — [descrição]

## Regras de Negócio

1. [Regra derivada do PRD]
2. [Regra derivada do PRD]

## Telas / UI

[Descrição visual ou referência ao Inspinia]

## Testes

| Teste | Tipo         | Cenário |
| ----- | ------------ | ------- |
|       | Unit/Feature |         |

## Dependências

- Depende de: [outros módulos]
- Dependido por: [outros módulos]

## Changelog do Módulo

| Data | Descrição |
| ---- | --------- |
|      |           |
```

---

## 5. Controle de Tarefas — Linear (opcional)

Se usar o Linear MCP para gestão de tarefas, organizar assim:

### Projetos

- **Portal ArtFinal**

### Ciclos (= Sprints)

- Sprint 01 — Setup do Projeto (7 dias)
- Sprint 02 — Migrations e Models (Grupo 1)
- Sprint 03 — Migrations (Grupo 2) + Seeders
- ...

### Labels

- `admin` — Tarefas do backoffice
- `portal` — Tarefas do portal
- `infra` — Infraestrutura
- `docs` — Documentação
- `bug` — Correções
- `debt` — Débito técnico

### Status

- Backlog → Todo → In Progress → Review → Done

---

## 6. Checklist de Início de Sprint

Antes de iniciar cada sprint, fazer:

- [ ] Ler o detalhamento da sprint no PRD (seção 20.3)
- [ ] Listar entregáveis da sprint
- [ ] Criar branch: `feature/sprint-XX-descricao`
- [ ] Criar tarefas no Linear (se usar)
- [ ] Verificar dependências de módulos anteriores
- [ ] Verificar se há pacotes/plugins para instalar
- [ ] Atualizar o campo "Sprint Atual" no CLAUDE.md

---

## 7. Checklist de Fim de Sprint

Depois de finalizar cada sprint:

- [ ] Rodar testes: `php artisan test`
- [ ] Rodar formatter: `./vendor/bin/pint`
- [ ] Rodar análise estática: `./vendor/bin/phpstan analyse`
- [ ] Atualizar documentação dos módulos criados/alterados
- [ ] Atualizar `.docs/CHANGELOG.md`
- [ ] Commit final com mensagem de sprint
- [ ] Merge para `develop`
- [ ] Tag de versão se for fim de fase
- [ ] Apresentar para validação do cliente (conforme PRD)
