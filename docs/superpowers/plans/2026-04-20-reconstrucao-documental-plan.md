# Reconstrução Documental v1.0.0 — Plano Executável

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> **[DIA 0 — ADICIONADO 2026-04-23]** Antes de iniciar o Dia 1 deste plano, completar o ajuste documental do fluxo de adesão pública conforme plano externo `/Users/leonardozaneladias/.claude/plans/glowing-riding-yao.md`. Justificativa: a inversão da hierarquia Contrato↔Turma (SPEC-F-001 v0.3.0) e a redefinição do fluxo público (SPEC-010 v2.0.0 — código do contrato, etapas de escolha curso+período e pacote formatura) mudam contratos de API citados em vários thin indexes que serão criados aqui. Ajuste completo antes; reconstrução depois. Também adicionar `docs/META/PROJECT-STATUS.md` à lista de documentos **contexto obrigatório** referenciada por `01-DOCUMENTATION-GOVERNANCE.md`.

**Goal:** Reconstruir `docs/` do Portal ArtFinal v2 com 16 documentos numerados (00-16) + audit report em 5 dias úteis (qua 2026-04-22 → ter 2026-04-28), preservando 100% da estrutura legada, antes de Sprint F1.1 arrancar (qua 2026-04-29).

**Architecture:** 6 docs novos com conteúdo próprio (01-GOVERNANCE, 02-PRODUCT-OPERATING-MODEL, 03-SCRUM-OPERATING-MODEL, 04-SQUAD-TOPOLOGY, 12-VERTICAL-SLICE-DELIVERY-PLAN, 16-OPEN-QUESTIONS). 10 thin indexes (05-11, 13-15) de ~200-500 linhas apontando para fontes legadas preservadas. 1 hub novo (00-INDEX) substitui `docs/README.md`. 1 audit report em `docs/reports/`. Legado continua em `product/`, `prd/`, `architecture/`, etc. Docsify navigation atualizada via `_sidebar.md` e `_navbar.md`.

**Tech Stack:** Markdown + YAML frontmatter · Docsify (render) · Prettier (format) · markdown-link-check (validação) · git tags para baselines · agentes Claude Code para dispatch paralelo.

**Spec reference:** [`docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md`](../specs/2026-04-20-documentacao-unificada-governanca-design.md)

---

## Pré-requisitos

- [ ] Design spec aprovado e commitado (✅ `eeba8eb`)
- [ ] Branch `feature/planejamento-backend-api-v1` checked out
- [ ] Working directory limpo OU mudanças não-relacionadas empilhadas (`git stash`)
- [ ] `npm run quality` baseline passando (antes de qualquer mudança)
- [ ] Node 18+ instalado (para markdown-link-check)
- [ ] Prettier funcionando (`npx prettier --version`)

---

## Estrutura de arquivos

### Novos (17 arquivos)

| Caminho                                                | Tipo       | Dia | Tamanho-alvo |
| ------------------------------------------------------ | ---------- | --- | ------------ |
| `docs/00-INDEX.md`                                     | hub        | 5   | ~300 linhas  |
| `docs/01-DOCUMENTATION-GOVERNANCE.md`                  | novo       | 1   | ~500 linhas  |
| `docs/02-PRODUCT-OPERATING-MODEL.md`                   | novo       | 2   | ~400 linhas  |
| `docs/03-SCRUM-OPERATING-MODEL.md`                     | novo       | 2   | ~400 linhas  |
| `docs/04-SQUAD-TOPOLOGY.md`                            | novo       | 2   | ~400 linhas  |
| `docs/05-UNIFIED-PROJECT-BRIEF.md`                     | thin index | 4   | ~250 linhas  |
| `docs/06-UNIFIED-PRD.md`                               | thin index | 4   | ~400 linhas  |
| `docs/07-UNIFIED-SRS.md`                               | thin index | 4   | ~350 linhas  |
| `docs/08-UNIFIED-SAD-ARC42.md`                         | thin index | 4   | ~300 linhas  |
| `docs/09-ADR-INDEX.md`                                 | thin index | 4   | ~300 linhas  |
| `docs/10-API-BACKEND-INDEX.md`                         | thin index | 4   | ~400 linhas  |
| `docs/11-FRONTEND-REACT-INDEX.md`                      | thin index | 4   | ~400 linhas  |
| `docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md`              | novo       | 3   | ~500 linhas  |
| `docs/13-QA-AND-ACCEPTANCE-STRATEGY.md`                | thin index | 4   | ~300 linhas  |
| `docs/14-DEV-SETUP-AND-WORKFLOW.md`                    | thin index | 4   | ~300 linhas  |
| `docs/15-RUNBOOK.md`                                   | thin index | 4   | ~300 linhas  |
| `docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md`               | novo       | 3   | ~300 linhas  |
| `docs/reports/2026-04-20-AUDIT-REPORT.md`              | novo       | 1   | ~600 linhas  |
| `docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md` | snapshot   | 5   | copy         |

### Atualizados

| Caminho            | Mudança                          | Dia |
| ------------------ | -------------------------------- | --- |
| `docs/README.md`   | Vira stub redirect (3 linhas)    | 5   |
| `docs/_sidebar.md` | Nova navegação 00-16             | 5   |
| `docs/_navbar.md`  | Quick-access dos docs principais | 5   |

### Preservados (intocados)

`docs/product/`, `docs/prd/`, `docs/architecture/`, `docs/api/`, `docs/data/`, `docs/features/`, `docs/modules/`, `docs/frontend/`, `docs/qa/`, `docs/devops/`, `docs/squads/`, `docs/stories/`, `docs/roadmap/`, `docs/superpowers/` (exceto este plano novo), `docs/template/`, `docs/site/`, `docs/prompts/`, `docs/SPEC-RESTRUCTURE-PLAN.md`, `docs/_archive/` (exceto adição de `legacy-hub/`).

---

## Padrões comuns

### Frontmatter padrão (template 1 — gap docs)

```yaml
---
title: <Título legível em PT-BR>
version: 1.0.0
date: 2026-04-22
status: draft
sprint_baseline: null
owner_role: <product-manager | scrum-master | architect | developer | qa>
last_reviewed: 2026-04-22
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: false
---
```

### Frontmatter padrão (template 2 — thin index)

```yaml
---
title: <Título> — Thin Index
version: 1.0.0
date: 2026-04-27
status: active
sprint_baseline: null
owner_role: <role>
last_reviewed: 2026-04-27
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: []
change_during_sprint: false
---
```

### Comandos de validação recorrentes

**Instalar validador de links (1x no Dia 1):**

```bash
npm install --no-save markdown-link-check
```

**Validar um arquivo md:**

```bash
npx markdown-link-check docs/<file>.md --config .mlc-config.json 2>&1 | tail -20
```

**Validar frontmatter de um arquivo:**

```bash
node -e "
const fs = require('fs');
const path = process.argv[1];
const content = fs.readFileSync(path, 'utf8');
const match = content.match(/^---\n([\s\S]*?)\n---/);
if (!match) { console.error('NO FRONTMATTER'); process.exit(1); }
const fm = match[1];
const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed', 'review_cadence'];
const missing = required.filter(k => !new RegExp('^' + k + ':').test(fm.split('\n').join('\n')));
if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
console.log('OK:', path);
" docs/<file>.md
```

**Prettier format de tudo que mudamos:**

```bash
npx prettier --write 'docs/*.md' 'docs/reports/*.md' 'docs/_archive/legacy-hub/*.md'
```

**Docsify render local:**

```bash
npx docsify-cli serve docs --port 3000 &
sleep 2
open http://localhost:3000
```

---

## Task 0: Setup pré-Dia 1

**Quando executar:** imediatamente após aprovação deste plano (antes de qua 2026-04-22).

**Files:**

- Create: `docs/reports/` (pasta)
- Create: `docs/_archive/legacy-hub/` (pasta)
- Create: `.mlc-config.json` (config do markdown-link-check)

### Task 0.1: Criar pastas de destino

- [ ] **Step 1: Criar estrutura de pastas**

```bash
mkdir -p docs/reports docs/_archive/legacy-hub
```

- [ ] **Step 2: Verificar que pastas existem**

```bash
ls -la docs/reports docs/_archive/legacy-hub
```

Expected: ambas listadas sem erro; cada uma vazia ou com `.gitkeep`.

- [ ] **Step 3: Criar .gitkeep para preservar pastas no git**

```bash
touch docs/reports/.gitkeep docs/_archive/legacy-hub/.gitkeep
```

### Task 0.2: Criar config do markdown-link-check

- [ ] **Step 1: Criar `.mlc-config.json` na raiz do projeto**

Conteúdo exato:

```json
{
    "ignorePatterns": [{ "pattern": "^http://localhost" }, { "pattern": "^http://127.0.0.1" }],
    "replacementPatterns": [
        {
            "pattern": "^/",
            "replacement": "{{BASEURL}}/"
        }
    ],
    "httpHeaders": [
        {
            "urls": ["https://github.com"],
            "headers": { "Accept": "*/*" }
        }
    ],
    "timeout": "10s",
    "retryOn429": true,
    "retryCount": 3
}
```

- [ ] **Step 2: Verificar arquivo criado**

```bash
cat .mlc-config.json
```

Expected: JSON válido renderizado.

### Task 0.3: Instalar markdown-link-check

- [ ] **Step 1: Instalar como devDependency (não-save)**

```bash
npm install --no-save markdown-link-check
```

Expected: instalação bem-sucedida; não modifica package.json.

- [ ] **Step 2: Verificar que funciona**

```bash
npx markdown-link-check --version
```

Expected: output do tipo `3.x.x`.

### Task 0.4: Commit do setup

- [ ] **Step 1: Stage arquivos de setup**

```bash
git add docs/reports/.gitkeep docs/_archive/legacy-hub/.gitkeep .mlc-config.json
```

- [ ] **Step 2: Commit**

```bash
git commit -m "chore(docs): setup pastas + markdown-link-check config"
```

Expected: commit criado sem erros; 3 arquivos adicionados.

---

# 📅 Dia 1 (qua 2026-04-22) — Auditoria + Governance

**Meta do dia:** relatório de auditoria entregue + `01-DOCUMENTATION-GOVERNANCE.md` escrito + commit único.

## Task 1.1: Executar auditoria full-pass

**Files:**

- Read: todo o conteúdo de `docs/`
- Create: pasta temp `docs/reports/raw-audit.txt` (descartado ao fim)

- [ ] **Step 1: Listar todos os arquivos .md de docs/**

```bash
find docs -type f -name "*.md" | sort > /tmp/docs-inventory.txt
wc -l /tmp/docs-inventory.txt
```

Expected: ~60-80 arquivos .md (ordem de grandeza baseada na auditoria preliminar).

- [ ] **Step 2: Classificar cada arquivo por tamanho e última modificação**

```bash
find docs -type f -name "*.md" -exec ls -la {} \; | awk '{print $5, $6, $7, $8, $9}' | sort -k4 > /tmp/docs-meta.txt
head -20 /tmp/docs-meta.txt
```

Expected: listagem ordenada por data de modificação.

- [ ] **Step 3: Identificar arquivos que são placeholders (stub) — tamanho < 500 bytes**

```bash
find docs -type f -name "*.md" -size -500c -exec echo {} \; > /tmp/docs-stubs.txt
cat /tmp/docs-stubs.txt
```

Expected: lista dos SPECs Foundation em stub (SPEC-F-002 a F-011 exceto F-001 e F-010).

- [ ] **Step 4: Coletar links externos quebrados (via grep + check manual)**

```bash
grep -rn "](" docs/*.md | grep -oE "\]\([^)]+\)" | sort -u | head -50 > /tmp/docs-links.txt
wc -l /tmp/docs-links.txt
```

Expected: inventário de ~200-500 links internos.

## Task 1.2: Escrever audit report

**Files:**

- Create: `docs/reports/2026-04-20-AUDIT-REPORT.md`

- [ ] **Step 1: Criar arquivo com frontmatter + estrutura inicial**

Conteúdo (crie o arquivo com o texto abaixo, adaptando contagens reais do Step 1.1):

```markdown
---
title: Auditoria Documental Portal ArtFinal v2 — baseline pré-reconstrução
version: 1.0.0
date: 2026-04-22
status: active
sprint_baseline: pre-F1
owner_role: architect
last_reviewed: 2026-04-22
review_cadence: quarterly
supersedes: null
superseded_by: null
related_adrs: []
change_during_sprint: false
---

# Audit Report — docs/ pré-reconstrução v1.0.0

> Baseline gerada em qua 2026-04-22 antes da reconstrução documental v1.0.0.
> Este documento congela o estado da documentação pré-reconstrução para rastreabilidade.
> Referência: [design spec](../superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md).

## 0. Sumário executivo

- **Total de docs encontrados:** <N> arquivos .md em docs/
- **Classificação:**
    - manter: <N>
    - manter-com-ajuste: <N>
    - fundir: <N>
    - substituir: <N>
    - arquivar: <N>
    - excluir: <N>
- **Conflitos identificados:** <N>
- **Redundâncias identificadas:** <N>
- **Órfãos identificados:** <N>
- **Gaps de rastreabilidade:** <N>
- **Desalinhamentos BE↔FE:** <N>
- **Desalinhamentos doc↔código:** <N>

## 1. Escopo e metodologia

Auditoria conduzida em qua 2026-04-22 via combinação de:

- Full-pass manual de `docs/` (leitura de README.md de cada pasta + arquivos-chave)
- Coleta de metadata via `find + ls` (tamanho, última modificação)
- Identificação de stubs (arquivos < 500 bytes)
- Classificação por heurísticas do design spec §9.3

Limitações:

- Desalinhamentos doc↔código são verificados por sampling (~10% dos SPECs)
- Conflitos sutis (contradições semânticas cross-doc) podem escapar

## 2. Inventário completo

| Caminho | Tipo | Tamanho | Última edição | Classificação | Motivo | Destino |
| ------- | ---- | ------- | ------------- | ------------- | ------ | ------- |

<!-- preencher com dados de /tmp/docs-inventory.txt + /tmp/docs-meta.txt -->

## 3. Conflitos identificados

| #   | Docs em conflito | Tópico | Contradição | Recomendação |
| --- | ---------------- | ------ | ----------- | ------------ |

<!-- exemplos preliminares:
| C-001 | prd/PRD_v4.md vs docs/_archive/PRD_Sistema_Formatura_v3.1.0.md | Conceito de Contrato | v4 descarta Contrato; v3.1 trata como entidade central | v3.1 arquivado; referenciado por SPEC-RESTRUCTURE-PLAN para recuperação via Foundation SPECs |
| C-002 | features/SPEC-002-wizard-adesao.md vs features/foundation/SPEC-F-001-contrato-e-turma.md | evento_ulid vs contrato_ulid | SPEC-002 original usa evento_ulid; SPEC-F-001 redefine como contrato_ulid | SPEC-002 em refactor-pending até absorver Foundation |
-->

## 4. Redundâncias

| #   | Docs redundantes | Tópico | Recomendação |
| --- | ---------------- | ------ | ------------ |

## 5. Órfãos (sem referência de outros docs)

| #   | Doc | Motivo órfão | Recomendação |
| --- | --- | ------------ | ------------ |

## 6. Gaps de rastreabilidade

### 6.1 SPECs sem ADR relacionado

<!-- listar -->

### 6.2 ADRs sem doc impactado identificado

<!-- listar -->

### 6.3 Decisões em CLAUDE.md sem ADR correspondente

<!-- listar -->

## 7. Desalinhamentos BE↔FE

| #   | Doc BE | Doc FE | Desalinhamento | Severidade |
| --- | ------ | ------ | -------------- | ---------- |

## 8. Desalinhamentos doc ↔ código (sampling)

Sampling de 10% dos SPECs:

- SPEC-010 (plan-ready) vs código atual — esperado: código ausente (SPEC ainda não implementado) ✅
- SPEC-001 (refactor-pending) vs código atual — verificar guards e rotas atuais
- SPEC-F-001 (draft) vs migrations atuais — verificar se contrato_id já existe em alguma migration

## 9. Plano de ação

| Item  | Ação                                                                 | Responsável (role)                         | Dia da reconstrução | Status    |
| ----- | -------------------------------------------------------------------- | ------------------------------------------ | ------------------- | --------- |
| A-001 | Substituir docs/README.md por 00-INDEX.md + redirect                 | architect                                  | Dia 5               | Planejado |
| A-002 | Arquivar snapshot README v2.0.0 em \_archive/legacy-hub/             | architect                                  | Dia 5               | Planejado |
| A-003 | Criar 01-DOCUMENTATION-GOVERNANCE com freeze/rolling-wave/SemVer/ADR | architect                                  | Dia 1               | Em curso  |
| A-004 | Criar operating models 02/03/04                                      | architect + product-manager + scrum-master | Dia 2               | Planejado |
| A-005 | Criar 12-VERTICAL-SLICE-DELIVERY-PLAN                                | scrum-master + product-manager             | Dia 3               | Planejado |
| A-006 | Criar 16-OPEN-QUESTIONS seed com pendências reais                    | product-manager                            | Dia 3               | Planejado |
| A-007 | Criar 10 thin indexes (05-11, 13-15)                                 | architect (+ Explore agents)               | Dia 4               | Planejado |
| A-008 | Criar 00-INDEX + Docsify nav + git tags                              | architect                                  | Dia 5               | Planejado |

## 10. Anexos

### 10.1 Lista de ADRs (14 existentes)

<!-- listar ADR-0001 a ADR-0014 com status -->

### 10.2 Lista de SPECs por camada

<!-- listar Foundation (F-001..011), Feature (001-010), Archived -->

### 10.3 Grafo de dependências (texto)

<!-- árvore de referências doc→doc identificadas -->
```

- [ ] **Step 2: Preencher Seção 2 (Inventário) com dados coletados em Task 1.1**

Ler `/tmp/docs-inventory.txt` e `/tmp/docs-meta.txt`. Para cada arquivo:

- Classificar conforme taxonomia (§9.3 do design spec)
- Preencher linha na tabela

**Regras de classificação (heurísticas):**

- Hub existente (`docs/README.md`) → `substituir` (por `00-INDEX.md`)
- Fontes das thin indexes (product/, prd/PRD_v4, architecture/SAD, api/, modules/, frontend/, qa/, devops/) → `manter`
- SPECs ativos (features/, Foundation) → `manter`
- Documentos em `_archive/` → já arquivados, `manter` como histórico
- Arquivos < 500 bytes sem TODO → `manter-com-ajuste` (stub a expandir)
- Duplicados ou superseded → `fundir` ou `arquivar`

Target: preencher ~60-80 linhas da tabela.

- [ ] **Step 3: Preencher Seção 3 (Conflitos)**

Conflitos conhecidos já identificados durante a brainstorm:

- C-001: PRD v3.1.0 vs PRD v4 (conceito de Contrato) — já resolvido via SPEC-RESTRUCTURE-PLAN
- C-002: SPEC-002 vs SPEC-F-001 (evento_ulid vs contrato_ulid) — já marcado como refactor-pending
- C-003: CLAUDE.md v1 (Inspinia) vs docs antigas (Metronic) — CLAUDE.md já marca a substituição

Verificar via grep se há outros conflitos não-documentados:

```bash
grep -l "Metronic" docs --include="*.md" -r
grep -l "evento_ulid" docs --include="*.md" -r
```

- [ ] **Step 4: Preencher Seção 4 (Redundâncias)**

Redundâncias conhecidas:

- `product/PRD_EXPANDED.md` vs `prd/PRD_v4.md` — fusão futura candidata
- `prd/REGRAS_NEGOCIO.md` vs `prd/PLANEJAMENTO_BACKEND_APIV1.md` (seções de regras) — overlap

- [ ] **Step 5: Preencher Seção 5 (Órfãos)**

```bash
grep -L "](" docs/**/*.md 2>/dev/null | head -20
```

Listar docs que ninguém linka internamente.

- [ ] **Step 6: Preencher Seção 6 (Gaps de rastreabilidade)**

ADRs vs SPECs mapping:

```bash
for adr in docs/architecture/adrs/ADR-*.md; do
  name=$(basename $adr .md)
  refs=$(grep -l "$name" docs -r --include="*.md" 2>/dev/null | wc -l)
  echo "$name: $refs referências"
done
```

Identificar ADRs com 0-1 referências (subutilizados).

- [ ] **Step 7: Preencher Seção 7 (Desalinhamentos BE↔FE)**

Verificar via comparação manual:

- `prd/PLANEJAMENTO_BACKEND_APIV1.md` rotas vs `prd/PLANEJAMENTO_FRONTEND_REACT.md` consumidores
- `frontend/08-API-INTEGRATION-CONTRACT.md` vs `api/api-contract.md`

- [ ] **Step 8: Verificar frontmatter válido**

```bash
node -e "$(cat <<'EOF'
const fs = require('fs');
const path = 'docs/reports/2026-04-20-AUDIT-REPORT.md';
const content = fs.readFileSync(path, 'utf8');
const match = content.match(/^---\n([\s\S]*?)\n---/);
if (!match) { console.error('NO FRONTMATTER'); process.exit(1); }
const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed'];
const missing = required.filter(k => !new RegExp('^' + k + ':', 'm').test(match[1]));
if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
console.log('OK');
EOF
)"
```

Expected: `OK`.

## Task 1.3: Escrever 01-DOCUMENTATION-GOVERNANCE.md

**Files:**

- Create: `docs/01-DOCUMENTATION-GOVERNANCE.md`

- [ ] **Step 1: Criar arquivo com frontmatter**

Usar o frontmatter template 1 (gap docs):

```yaml
---
title: Documentation Governance — política viva de documentação
version: 1.0.0
date: 2026-04-22
status: draft
sprint_baseline: null
owner_role: architect
last_reviewed: 2026-04-22
review_cadence: quarterly
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever Seção 1 — Princípios**

Conteúdo (título + 10 bullets):

```markdown
# Documentation Governance

## 1. Princípios não negociáveis

1. **Docs-as-code** — documentação vive junto do código, passa por PR, formato Markdown + YAML frontmatter, navegável via Docsify
2. **Docs vivas** — evoluem a cada sprint; versionamento explícito via SemVer
3. **SSOT por tópico** — cada informação tem uma e apenas uma fonte de verdade; thin indexes apontam, não duplicam
4. **Rastreabilidade** — todo doc referencia ADRs, SPECs e sprints que o impactam (via frontmatter `related_adrs`, `related_features`)
5. **Rolling-wave planning** — detalhamento decresce com horizonte: N+0 frozen, N+1 draft, N+2 sketch, N+3+ ideias
6. **Freeze de sprint** — baseline documental congelada durante sprint ativa; quebra requer ADR (F.1)
7. **Changelog no frontmatter** — cada doc documenta sua própria evolução
8. **Idioma PT-BR** — conteúdo de negócio em português; código/termos técnicos em inglês
9. **Preservação histórica** — docs obsoletos vão para `_archive/`, nunca apagados sem ADR
10. **Default conservador** — na dúvida, arquivar; excluir só com aprovação explícita
```

- [ ] **Step 3: Escrever Seção 2 — Ciclo de vida do documento**

Tabela de estados + transições + template de frontmatter completo. Conteúdo:

```markdown
## 2. Ciclo de vida do documento

### 2.1 Estados possíveis (frontmatter `status`)

| Estado             | Significado                                         | Quem transiciona            | Quando                                      |
| ------------------ | --------------------------------------------------- | --------------------------- | ------------------------------------------- |
| `stub`             | Arquivo criado, conteúdo placeholder/incompleto     | developer                   | Criação inicial via `make:<artifact>`       |
| `draft`            | Conteúdo escrito, aguardando revisão                | developer → architect       | Ao fim de uma story que produz o doc        |
| `active`           | Revisado, fonte de verdade para implementação       | architect → product-manager | Após review pré-sprint                      |
| `refactor-pending` | Escrito mas requer atualização contra novo baseline | product-manager             | Quando dependências mudam                   |
| `superseded`       | Substituído por outro doc                           | architect                   | Ao criar sucessor; preenche `superseded_by` |
| `archived`         | Obsoleto; preserva histórico                        | architect                   | Movido para `_archive/`                     |

### 2.2 Grafo de transições
```

stub → draft → active → refactor-pending → active (após refactor)
↓
superseded → archived

````

Transições proibidas:
- `active` → `stub` (regressão; usa `refactor-pending`)
- `archived` → qualquer (arquivo em _archive é imutável)

### 2.3 Frontmatter padrão unificado

Todo doc em `docs/*.md` (exceto `_archive/`) deve ter:

​```yaml
---
title: <título PT-BR>
version: <SemVer MAJOR.MINOR.PATCH>
date: <YYYY-MM-DD — data da última alteração>
status: <stub | draft | active | refactor-pending | superseded | archived>
sprint_baseline: <F<N>-sprint<X.Y> ou null>
owner_role: <product-manager | scrum-master | architect | developer | qa>
last_reviewed: <YYYY-MM-DD>
review_cadence: <pre-sprint | on-change | quarterly>
supersedes: <path ou null>
superseded_by: <path ou null>
related_adrs: [ADR-NNNN, ...]
related_features: [SPEC-NNN, SPEC-F-NNN, ...]
change_during_sprint: <true | false>
---
​```

Validação: `scripts/validate-frontmatter.sh` (criado no Dia 5).
````

- [ ] **Step 4: Escrever Seção 3 — Freeze de sprint**

Conteúdo:

```markdown
## 3. Freeze de sprint

### 3.1 Regra fundamental

Durante uma **sprint ativa** (entre Sprint Planning e Sprint Retrospective), **baseline documental está congelada**:

- Docs com `sprint_baseline: <sprint-ativa>` e `change_during_sprint: false` são **read-only**
- Alterações em desses docs violam governance
- Enforcement via agente `scrum-master` no checklist pré-PR (decisão **A.2**)

### 3.2 O que conta como baseline

Ao iniciar uma sprint (ritual Planning do `03-SCRUM-OPERATING-MODEL`):

1. Scrum Master identifica todos os docs que a sprint **consumirá** (SPECs, PRD seções, ADRs)
2. Cada doc tem seu `sprint_baseline` atualizado para a sprint atual
3. Git tag `sprint-<N.Y>-baseline` é criada — snapshot imutável do estado inicial
4. Durante a sprint, alterações em docs com essa tag violam freeze

### 3.3 Exceções permitidas durante sprint ativa

Mudanças permitidas sem quebrar freeze:

- Docs com `change_during_sprint: true` (ex.: `16-OPEN-QUESTIONS`, `12-VERTICAL-SLICE` se vertical slice atual)
- Correções de typo/formatação (git commit sem alteração semântica)
- Adição de links cruzados (não altera conteúdo)

### 3.4 Quebra de freeze (decisão F.1)

Quebrar freeze em doc frozen exige:

1. ADR formal — `ADR-NNNN: Quebra de freeze Sprint <N.Y> — razão <X>`
2. Product Manager aprova o ADR
3. ADR linka o doc alterado e a justificativa
4. Alteração feita em PR separado com label `docs-freeze-break`
5. Retrospective da sprint menciona a quebra para lição aprendida

### 3.5 Fim do freeze

A baseline congela até **Sprint Retrospective**. Durante o ritual de retrospective:

1. Docs com `sprint_baseline: <sprint-que-terminou>` são liberados
2. Consolidação documental (K.1) atualiza baseline para próxima sprint
3. Novo git tag criada para próxima baseline
```

- [ ] **Step 5: Escrever Seção 4 — Rolling-wave planning**

Conteúdo:

```markdown
## 4. Rolling-wave planning

Adotado default **B.2 — horizonte médio**:

| Horizonte                 | Granularidade esperada          | Exemplo de doc                                 |
| ------------------------- | ------------------------------- | ---------------------------------------------- |
| **N+0** (sprint ativa)    | Totalmente detalhado, frozen    | Sprint plan atual, SPECs sendo implementados   |
| **N+1** (próxima sprint)  | Detalhado, draft editável       | Sprint plan próxima, SPECs em refactor-pending |
| **N+2** (sprint seguinte) | Esboço, principais estimativas  | Vertical slice plan §6.3 linhas futuras        |
| **N+3+** (futuro)         | Ideias/backlog, sem compromisso | BACKLOG_FUTURO.md, SPECs em `backlog`          |

### 4.1 Quando mover item entre horizontes

- **N+1 → N+0:** no Sprint Planning; item passa por DoR gate
- **N+2 → N+1:** no Backlog Refinement (pré-Planning); item passa por estimativa
- **N+3 → N+2:** no Backlog Refinement quando há capacidade no horizonte
- **N+0 → N+1 (push-back):** se item incompleto ao fim da sprint; retrospective registra motivo

### 4.2 Princípio de detalhamento

> "Detalhar o próximo passo em pormenor; esboçar o seguinte; apenas nomear o subsequente."

Evitar detalhar N+2 ou N+3 — muda rápido demais para o esforço compensar.
```

- [ ] **Step 6: Escrever Seção 5 — Versionamento**

Conteúdo:

````markdown
## 5. Versionamento

### 5.1 SemVer no frontmatter (decisão C.1)

Frontmatter `version` segue SemVer `MAJOR.MINOR.PATCH`:

- **MAJOR** — mudança estrutural do doc (renomeio de seções, grandes reescritas)
- **MINOR** — adição de seção ou conteúdo significativo novo
- **PATCH** — correções, ajustes de links, erratas, typos

### 5.2 Changelog inline (opcional)

Docs críticos (01-GOVERNANCE, 02-PRODUCT, 03-SCRUM, 12-VERTICAL-SLICE) podem ter seção **"Changelog"** no final listando últimas 3-5 mudanças:

​```markdown

## Changelog

- **v1.1.0** — 2026-05-10 — Adicionada seção §6 sobre handling de quebra de freeze multi-sprint
- **v1.0.1** — 2026-05-03 — Corrigido link quebrado para ADR-0005
- **v1.0.0** — 2026-04-22 — Versão inicial (reconstrução documental v1.0.0)
  ​```

Thin indexes não precisam de changelog (ciclo curto, mudanças frequentes).

### 5.3 Snapshots via git tag (decisão E.2)

Ao fim de cada sprint, tag git:

​`bash
git tag -a sprint-<N.Y>-baseline -m "Baseline docs sprint <N.Y>"
git push origin sprint-<N.Y>-baseline
​`

Recuperação de snapshot passado:

​`bash
git show sprint-1.1-baseline:docs/06-UNIFIED-PRD.md
​`

Zero overhead de disco; zero cópias físicas em `_archive/snapshots/`.
````

- [ ] **Step 7: Escrever Seção 6 — Rituais documentais**

Conteúdo:

```markdown
## 6. Rituais documentais

Ciclo de 10 dias úteis da sprint (coordenado com `03-SCRUM-OPERATING-MODEL`):

### 6.1 Pré-sprint (Dia 0-1)

Ritual **Planning** (`/scrum-master`):

1. Identificar docs que a sprint vai consumir (PRD seções, SPECs, ADRs)
2. Atualizar `sprint_baseline` nesses docs
3. Criar git tag `sprint-<N.Y>-baseline`
4. Sprint Goal documentado em `stories/sprint-plan-f<N>.md`

### 6.2 Durante sprint (Dia 1-9)

- Docs com baseline atual = frozen (read-only)
- Alterações viram: (a) novo item backlog para próxima sprint, (b) ADR de quebra de freeze, (c) `16-OPEN-QUESTIONS` se é dúvida cross-cutting
- Daily self-check: `TaskList` para verificar progresso

### 6.3 Pós-sprint (Dia 9-10)

Ritual **Retrospective + Docs Consolidation** (decisão K.1 — obrigatório):

1. Revisar docs que foram alterados pela sprint
2. Atualizar `last_reviewed` + `version` onde aplicável
3. Fechar ADRs pendentes (mover `proposed` → `accepted`)
4. Registrar lições aprendidas em `docs/reports/<sprint-N.Y>-retrospective.md`
5. Liberar baseline (`sprint_baseline` → null ou próxima sprint)
6. Preparar baseline da próxima sprint
```

- [ ] **Step 8: Escrever Seção 7 — Fluxo de mudança**

Conteúdo:

```markdown
## 7. Fluxo de mudança documental

### 7.1 Mudança pequena (PATCH — typo, link, formatação)

Durante sprint ativa: permitida sem gate se não altera semântica.

​`bash
git add docs/<file>.md
git commit -m "docs(docs): fix typo em <file>"
​`

### 7.2 Mudança média (MINOR — adiciona seção)

1. PR com label `docs-change`
2. Scrum Master verifica: doc está frozen?
3. Se NÃO: review + merge
4. Se SIM: bloqueado até fim de sprint OU ADR de quebra de freeze

### 7.3 Mudança grande (MAJOR — reestruturação)

Nunca durante sprint ativa. Processo:

1. Rascunho em `docs/superpowers/specs/YYYY-MM-DD-<topic>-design.md` via `superpowers:brainstorming`
2. Plano em `docs/superpowers/plans/YYYY-MM-DD-<topic>-plan.md` via `superpowers:writing-plans`
3. Execução inter-sprint (entre retrospective e próximo planning)
4. Snapshot do estado anterior via git tag (`<topic>-pre-refactor`)
```

- [ ] **Step 9: Escrever Seção 8 — ADR lifecycle**

Conteúdo:

````markdown
## 8. ADR lifecycle

### 8.1 Quando criar ADR (decisão D.2)

ADR é obrigatório para:

- Decisões caras de reverter (ex.: schema de DB, formato de URL pública, algoritmo de tokenização)
- Convenções cross-module (ex.: padrão de validação, estrutura de erro, versionamento de API)
- Escolha entre alternativas com trade-offs significativos (ex.: Sanctum vs Passport)
- Quebra de freeze de sprint (tipo especial; ver §3.4)

ADR NÃO é obrigatório para:

- Decisões reversíveis facilmente (renomear variável, mover arquivo)
- Convenções locais (padrão de naming de um módulo específico)
- Implementação rotineira (CRUD padrão Laravel)

### 8.2 Template de ADR

Caminho: `docs/architecture/adrs/ADR-NNNN-<kebab-title>.md`

Estrutura (alinhada aos 14 ADRs existentes):

## ​```markdown

title: ADR-NNNN — <título>
version: 1.0.0
date: YYYY-MM-DD
status: proposed | accepted | superseded | deprecated
owner_role: architect
related_features: []
supersedes: null
superseded_by: null

---

# ADR-NNNN — <Título>

## Contexto

<3-5 parágrafos: situação que motiva a decisão, restrições, forças em jogo>

## Decisão

<O que foi decidido; tom afirmativo: "Nós escolhemos X">

## Alternativas consideradas

- **Alternativa A** — descrição + motivo da rejeição
- **Alternativa B** — descrição + motivo da rejeição

## Consequências

## **Positivas:**

## **Negativas:**

## **Neutras / trade-offs:**

## Implementação

<Notas sobre como implementar; links para código/docs afetados>

## Referências

<Links externos, standards, artigos>
​```

### 8.3 Ciclo de status ADR

​`
proposed → accepted → superseded
               ↓
         deprecated
​`

- **proposed** → decisão em discussão; pode virar backlog se aprovada
- **accepted** → decisão ativa; referência oficial
- **superseded** → substituído por outro ADR; preenche `superseded_by`
- **deprecated** → não mais aplicável mas não tem substituto (ex.: feature removida)

### 8.4 Numeração

ADRs numerados sequencialmente: ADR-0001, ADR-0002, ... Nunca reutilizar número.

Próximo disponível: **ADR-0015** (após os 14 existentes).
````

- [ ] **Step 10: Escrever Seção 9 — Destino de docs antigos**

Conteúdo:

```markdown
## 9. Destino de docs antigos

### 9.1 Classes (alinhado com §9.3 do design spec)

| Classe              | Significado                  | Onde vai                          | Frontmatter                                  |
| ------------------- | ---------------------------- | --------------------------------- | -------------------------------------------- |
| `manter`            | Útil, ativo                  | Permanece no lugar                | `status: active`                             |
| `manter-com-ajuste` | Útil com pequenos problemas  | Permanece; correção inline        | `status: active` + TODO em 16-OPEN-QUESTIONS |
| `fundir`            | Conteúdo integra outro doc   | Aguarda fusão; não move sozinho   | `status: refactor-pending`                   |
| `substituir`        | Doc novo ocupa lugar         | Legado → `_archive/`              | `superseded_by: <path-novo>`                 |
| `arquivar`          | Obsoleto; preserva histórico | Move para `_archive/<subfolder>/` | `status: archived`                           |
| `excluir`           | Sem valor histórico          | **Só com aprovação explícita**    | —                                            |

### 9.2 Regras de arquivamento

1. Nunca deletar sem aprovação — default é arquivar
2. Preservar caminho relativo — `_archive/<subfolder>/<original-name>.md`
3. Preencher `superseded_by` no arquivo arquivado
4. Adicionar entrada em `docs/_archive/README.md` com data e motivo
5. Criar stub no caminho original que redireciona (se houver links externos)
```

- [ ] **Step 11: Escrever Seção 10 — Enforcement**

Conteúdo:

```markdown
## 10. Enforcement

### 10.1 Camadas de enforcement

| Camada                      | Mecanismo                                                | Escopo                     |
| --------------------------- | -------------------------------------------------------- | -------------------------- |
| **1. Convenção**            | Este doc + CLAUDE.md                                     | Todos os contribuidores    |
| **2. Agente** (decisão A.2) | `/scrum-master` check pré-PR                             | Sprint freeze              |
| **3. Pre-commit**           | Hook valida frontmatter                                  | Localmente antes de commit |
| **4. CI**                   | GitHub Actions valida links + frontmatter + markdownlint | PR review                  |
| **5. Manual trimestral**    | Auditoria `/ln-611-docs-structure-auditor`               | Drift detection            |

### 10.2 Checklist do agente scrum-master (pré-PR)

Quando um PR altera `docs/*.md`:

1. ✅ Identificar `sprint_baseline` dos arquivos alterados
2. ✅ Comparar com sprint ativa (via git tag atual)
3. ✅ Se frozen E `change_during_sprint: false` → bloquear ou exigir ADR
4. ✅ Validar frontmatter (todos os campos obrigatórios presentes)
5. ✅ Validar `version` bumped corretamente (SemVer)
6. ✅ Validar `last_reviewed` atualizado

### 10.3 Auditoria trimestral

A cada 3 meses:

- Executar `/ln-611-docs-structure-auditor` full-pass
- Registrar em `docs/reports/YYYY-QN-audit.md`
- Identificar drift entre doc e código
- Identificar ADRs obsoletos
- Propor consolidações ou arquivamentos
```

- [ ] **Step 12: Escrever Seção final — Relação com SPEC-RESTRUCTURE-PLAN + Referências**

Conteúdo:

```markdown
## 11. Relação com outros docs de governança

- **[`SPEC-RESTRUCTURE-PLAN.md`](SPEC-RESTRUCTURE-PLAN.md)** — política específica de SPECs (Camadas 1/2/3: Foundation / Refactor / Novos). Complementar a este doc: governance geral aqui, governance de SPECs lá.
- **[`CLAUDE.md`](../CLAUDE.md)** — instruções mestras do projeto (padrões de código, estrutura admin/portal). Regras de idioma, naming, arquitetura. Este doc herda o princípio de PT-BR declarado lá.
- **[`02-PRODUCT-OPERATING-MODEL.md`](02-PRODUCT-OPERATING-MODEL.md)** — papel Product Manager; DoR.
- **[`03-SCRUM-OPERATING-MODEL.md`](03-SCRUM-OPERATING-MODEL.md)** — papel Scrum Master; rituais; enforcement operacional.
- **[`04-SQUAD-TOPOLOGY.md`](04-SQUAD-TOPOLOGY.md)** — agentes que ocupam os papéis.

## 12. Referências externas

- [arc42 Template](https://arc42.org/) — estrutura do SAD (usada em `08-UNIFIED-SAD-ARC42`)
- [MADR (Markdown ADR)](https://adr.github.io/madr/) — formato ADR (usado em ADRs 0001-0014)
- [SemVer](https://semver.org/) — versionamento
- [Docs as Code](https://www.writethedocs.org/guide/docs-as-code/) — filosofia

---

_Este doc é **manutenção obrigatória** trimestral. Owner: architect. Review: `last_reviewed` ≤ 90 dias._
```

- [ ] **Step 13: Validar frontmatter**

```bash
node -e "$(cat <<'EOF'
const fs = require('fs');
const path = 'docs/01-DOCUMENTATION-GOVERNANCE.md';
const content = fs.readFileSync(path, 'utf8');
const match = content.match(/^---\n([\s\S]*?)\n---/);
if (!match) { console.error('NO FRONTMATTER'); process.exit(1); }
const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed', 'review_cadence', 'change_during_sprint'];
const missing = required.filter(k => !new RegExp('^' + k + ':', 'm').test(match[1]));
if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
console.log('OK');
EOF
)"
```

Expected: `OK`.

- [ ] **Step 14: Rodar Prettier**

```bash
npx prettier --write docs/01-DOCUMENTATION-GOVERNANCE.md docs/reports/2026-04-20-AUDIT-REPORT.md
```

Expected: "X ms" para cada arquivo; sem erros.

- [ ] **Step 15: Validar links (internos)**

```bash
npx markdown-link-check docs/01-DOCUMENTATION-GOVERNANCE.md --config .mlc-config.json 2>&1 | tail -20
```

Expected: todos os links internos existem OU marcados como TODO (para docs que serão criados nos dias seguintes).

⚠️ **Aceitável neste ponto:** links para docs que ainda não existem (02, 03, 04, 16) falham — serão criados em Dia 2-3. Não bloquear commit.

## Task 1.4: Commit Dia 1

- [ ] **Step 1: Stage arquivos do Dia 1**

```bash
git add docs/01-DOCUMENTATION-GOVERNANCE.md docs/reports/2026-04-20-AUDIT-REPORT.md
```

- [ ] **Step 2: Verificar o que será commitado**

```bash
git diff --cached --stat
```

Expected: 2 arquivos, ~1100 linhas inseridas.

- [ ] **Step 3: Commit (sem Co-Authored-By)**

```bash
git commit -m "$(cat <<'EOF'
docs(docs): auditoria inicial + governance model v1.0.0

Dia 1 da reconstrução documental v1.0.0. Entrega:
- Audit report com inventário classificado, conflitos, redundâncias,
  órfãos, gaps de rastreabilidade, desalinhamentos BE↔FE
- 01-DOCUMENTATION-GOVERNANCE.md com política viva: ciclo de vida dos
  docs, freeze de sprint (A.2 agent-checked), rolling-wave N+0..N+3
  (B.2), versionamento SemVer (C.1), ADR triggers cross-module (D.2),
  snapshots via git tag (E.2), quebra de freeze com ADR (F.1), ciclo
  de 5 rituais, fluxo de mudança, ADR lifecycle, destino de docs
  antigos, enforcement multi-camada.

Ref: docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md
EOF
)"
```

Expected: commit `<hash>` criado; 2 arquivos alterados; lint-staged roda Prettier; hook husky OK.

- [ ] **Step 4: Git tag baseline do Dia 1**

```bash
git tag -a docs-reconstruction-day-1 -m "Dia 1 reconstrução: audit + governance"
```

Expected: tag criada localmente.

### ✅ Checkpoint Dia 1

Ao fim do Dia 1, responder ao usuário:

- Arquivos criados: `docs/reports/2026-04-20-AUDIT-REPORT.md`, `docs/01-DOCUMENTATION-GOVERNANCE.md`
- Commit: `<hash>`
- Tag: `docs-reconstruction-day-1`
- Próximo: Dia 2 — operating models

**Aguardar aprovação do usuário antes de prosseguir ao Dia 2 (decisão BB.1).**

---

# 📅 Dia 2 (qui 2026-04-23) — Operating Models

**Meta do dia:** `02-PRODUCT-OPERATING-MODEL.md`, `03-SCRUM-OPERATING-MODEL.md`, `04-SQUAD-TOPOLOGY.md` escritos + commit único.

## Task 2.1: Escrever 02-PRODUCT-OPERATING-MODEL.md

**Files:**

- Create: `docs/02-PRODUCT-OPERATING-MODEL.md`

**Strategy:** pode ser dispatch paralelo com Task 2.2 (agentes independentes). Escolha `subagent-driven-development` se quiser paralelismo.

- [ ] **Step 1: Criar arquivo com frontmatter**

```yaml
---
title: Product Operating Model — papel Product Manager para solo+agentes
version: 1.0.0
date: 2026-04-23
status: draft
sprint_baseline: null
owner_role: product-manager
last_reviewed: 2026-04-23
review_cadence: quarterly
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever Seções 1-2 (propósito + agentes)**

Conteúdo:

```markdown
# Product Operating Model

> Manual operacional do papel **Product Manager** no contexto **solo + agentes Claude Code/BMAD**. Este não é um RACI corporativo — é um guia de quando acionar qual agente com quais inputs para produzir quais artefatos.

## 1. Propósito do papel

Product Manager é responsável por:

1. **Visão e valor** — manter clareza sobre o que o sistema entrega e para quem
2. **Priorização** — decidir ordem e escopo do backlog
3. **Backlog** — manter `BACKLOG_FUTURO.md` e SPECs em `backlog` atualizados
4. **Alinhamento** — garantir que docs, backlog e entregas se correspondem
5. **Critérios de aceite** — definir o que "feito" significa para cada feature
6. **Governança de mudança** — arbitrar o que entra agora vs depois
7. **Sincronização BE↔FE** — garantir que slice vertical faz sentido em ambas as pontas
8. **Interface com cliente** — traduzir feedback do cliente em ajustes documentais

## 2. Agentes que ocupam o papel

| Agente/Skill              | Quando usar                                                   | Input típico                          | Output típico                       |
| ------------------------- | ------------------------------------------------------------- | ------------------------------------- | ----------------------------------- |
| `/product-manager` (BMAD) | Criação/refinamento de PRD, user stories, acceptance criteria | Brief + contexto do projeto           | PRD, stories com SPs, AC em Gherkin |
| `/bmad-orchestrator`      | Abertura de fase; decide qual agente chamar                   | Fase em planejamento                  | Plan de ativação + handoff          |
| **Humano (você)**         | Decisões estratégicas, alinhamento cliente, trade-offs macro  | Contexto do brainstorm + estado atual | Escolhas A/B/C registradas          |

Regra: **humano toma a decisão final**; agentes produzem opções e análises.
```

- [ ] **Step 3: Escrever Seção 3 — Inputs que o papel produz/mantém**

Conteúdo:

```markdown
## 3. Artefatos que o papel mantém

| Artefato                  | Caminho                                    | Cadência de atualização            |
| ------------------------- | ------------------------------------------ | ---------------------------------- |
| PRD v4                    | `docs/prd/PRD_v4.md`                       | Por release (major)                |
| PRD Expanded              | `docs/product/PRD_EXPANDED.md`             | Por release (major)                |
| SRS                       | `docs/product/SRS.md`                      | On-change                          |
| Journeys + Personas       | `docs/product/journeys-personas.md`        | On-change                          |
| User flows                | `docs/product/user-flows.md`               | On-change                          |
| Roadmap                   | `docs/prd/ROADMAP.md`                      | Pré-sprint                         |
| Backlog futuro            | `docs/roadmap/BACKLOG_FUTURO.md`           | Pré-sprint + ad-hoc                |
| SPECs de feature          | `docs/features/SPEC-NNN-*.md`              | Pré-sprint (DoR); durante dev (AC) |
| Foundation SPECs          | `docs/features/foundation/SPEC-F-NNN-*.md` | Conforme desbloqueio por fase      |
| Acceptance criteria       | Dentro de cada SPEC (seção dedicada)       | Durante Planning                   |
| Thin index 06-UNIFIED-PRD | `docs/06-UNIFIED-PRD.md`                   | Pré-sprint                         |
```

- [ ] **Step 4: Escrever Seção 4 — Quando acionar (6 gatilhos)**

Conteúdo:

```markdown
## 4. Quando acionar o papel — 6 gatilhos concretos

### 4.1 Nova feature na visão

**Sinal:** nova ideia vem do cliente, de inspiração, de análise competitiva.

**Fluxo:**

1. Registrar em `BACKLOG_FUTURO.md` com prioridade tentativa
2. Se é uma grande feature → brainstorm (`superpowers:brainstorming`) antes de comprometer
3. Se pequena e clara → vira SPEC em `backlog` diretamente

**Não fazer:** não adicionar à sprint ativa; aguardar próximo Planning.

### 4.2 Refinamento pré-sprint

**Sinal:** próximo Sprint Planning está a ≤ 3 dias.

**Fluxo:**

1. Identificar próximas N stories do backlog
2. Para cada story: DoR check (§5)
3. Ajustar SPECs relacionadas de `refactor-pending` → `active`
4. Estimar SPs (Fibonacci)
5. Mapear dependências inter-stories

**Agente:** `/product-manager` para refinamento; `/scrum-master` para estimativa.

### 4.3 Mudança de escopo mid-sprint

**Sinal:** durante sprint, descobre-se que escopo original não fecha OU cliente pede mudança.

**Fluxo:**

1. **Default: NÃO mudar**. Scope creep durante sprint é proibido.
2. Opção A: mudança vira item backlog para próxima sprint
3. Opção B: se é blocker crítico → ADR + aprovação PM (ver quebra de freeze §3.4 do GOVERNANCE)
4. Registrar em `16-OPEN-QUESTIONS` para retrospective

### 4.4 Validação de acceptance criteria pós-dev

**Sinal:** developer abriu PR com story dizendo "done".

**Fluxo:**

1. Ler AC da SPEC envolvida
2. Validar funcionalmente (não é QA técnico; é "cliente usaria assim?")
3. Se ACs passam → aprovar PR
4. Se não passam → comentário específico apontando o AC falho

### 4.5 Alinhamento com cliente

**Sinal:** fim de sprint (Sprint Review) OU solicitação do cliente fora de sprint.

**Fluxo durante Sprint Review:**

1. Preparar demo da vertical slice (P.1 — tunnel via ngrok)
2. Apresentar checklist de AC atendidos
3. Capturar feedback em `16-OPEN-QUESTIONS` com tag `from-client`
4. Traduzir feedback em: (a) item backlog, (b) ajuste SPEC, (c) ADR

**Fluxo fora de sprint:**

1. Agendar janela específica (não interromper sprint)
2. Documentar conversation summary em `16-OPEN-QUESTIONS`

### 4.6 Consolidação pós-sprint

**Sinal:** Sprint Retrospective em curso.

**Fluxo:**

1. Revisar SPECs que foram implementados
2. Atualizar status: `active` para os concluídos; `refactor-pending` para os que precisam ajuste baseado no que foi aprendido
3. Atualizar BACKLOG_FUTURO com itens descobertos
4. Fechar ADRs pendentes
5. Preparar baseline próxima sprint
```

- [ ] **Step 5: Escrever Seção 5 — Definition of Ready (DoR)**

Conteúdo (decisão I.1):

```markdown
## 5. Definition of Ready (DoR)

Checklist que toda story precisa passar antes de entrar em sprint (gate entre PM → SM):

### 5.1 Critérios obrigatórios

- [ ] **Acceptance criteria em Gherkin** — Given/When/Then escritos e validados
- [ ] **Contrato de API definido** — endpoints afetados com payload/response em OpenAPI YAML
- [ ] **Dependências mapeadas** — quais stories/SPECs bloqueiam, quais são desbloqueados
- [ ] **Story points estimados** — Fibonacci (1, 2, 3, 5, 8, 13)
- [ ] **Impacto em thin indexes identificado** — quais docs 05-16 são afetados

### 5.2 Critérios recomendados (não-bloqueante)

- [ ] **Sketches de UI** — se FE está envolvido, wireframes low-fi
- [ ] **ADR pré-pensado** — se decisão arquitetural nova é necessária
- [ ] **Cenários de erro** — happy path + 2-3 edge cases mínimos

### 5.3 Quem valida

Product Manager (`/product-manager` skill) faz primeiro pass; Scrum Master (`/scrum-master`) valida antes de aceitar na sprint.

**Story sem DoR completo** → fica no backlog até resolver pendências.
```

- [ ] **Step 6: Escrever Seção 6 — Change control**

Conteúdo:

```markdown
## 6. Change control fora da sprint

### 6.1 Princípio

Mudanças de escopo entre sprints são **esperadas e bem-vindas** — rolling-wave permite isso. Mudanças durante sprint ativa são **problemáticas** e precisam ADR.

### 6.2 Fluxo normal (entre sprints)

1. Cliente/brainstorm identifica nova necessidade
2. Product Manager decide: backlog agora, próxima sprint, ou futuro
3. Se impacta SPEC ativo → SPEC vai para `refactor-pending`
4. Próxima sprint pode absorver a mudança

### 6.3 Fluxo excepcional (durante sprint)

Ver `01-DOCUMENTATION-GOVERNANCE §3.4` — quebra de freeze.

### 6.4 Registro

Toda mudança de escopo (entre sprints ou durante) é registrada em:

- Item de backlog em `BACKLOG_FUTURO.md` se adiada
- Comentário no PR de SPEC afetado
- Retrospective notes se durante sprint
```

- [ ] **Step 7: Escrever Seção 7 — Handoff com Scrum Master**

Conteúdo:

```markdown
## 7. Handoff com Scrum Master

**DoR é o contrato.** PM entrega stories com DoR completo; SM aceita na sprint e protege contra mudanças.

### 7.1 Artefatos no handoff

Do PM para o SM (entrada da Planning):

- Lista priorizada de stories (top N do backlog)
- DoR checklist para cada
- SPs estimados
- Dependências

Do SM para o PM (saída da Retrospective):

- Relatório de stories concluídas vs comprometidas
- Lições sobre AC mal-escritos ou DoR incompleto
- Sugestões de refinamento de backlog

### 7.2 Fricção aceitável

SM pode **recusar** story com DoR incompleto. Isso NÃO é conflito — é o sistema funcionando. PM responde refinando a story antes da próxima sprint.
```

- [ ] **Step 8: Validar frontmatter + Prettier**

```bash
npx prettier --write docs/02-PRODUCT-OPERATING-MODEL.md
node -e "$(cat <<'EOF'
const fs = require('fs');
const path = 'docs/02-PRODUCT-OPERATING-MODEL.md';
const content = fs.readFileSync(path, 'utf8');
const match = content.match(/^---\n([\s\S]*?)\n---/);
const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed', 'review_cadence', 'change_during_sprint'];
const missing = required.filter(k => !new RegExp('^' + k + ':', 'm').test(match[1]));
if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
console.log('OK');
EOF
)"
```

Expected: `OK`.

## Task 2.2: Escrever 03-SCRUM-OPERATING-MODEL.md

**Files:**

- Create: `docs/03-SCRUM-OPERATING-MODEL.md`

- [ ] **Step 1: Criar arquivo com frontmatter**

```yaml
---
title: Scrum Operating Model — papel Scrum Master para solo+agentes
version: 1.0.0
date: 2026-04-23
status: draft
sprint_baseline: null
owner_role: scrum-master
last_reviewed: 2026-04-23
review_cadence: quarterly
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever Seções 1-2 (propósito + agentes)**

Conteúdo:

```markdown
# Scrum Operating Model

> Manual operacional do papel **Scrum Master** no contexto **solo + agentes Claude Code/BMAD**. Foco em proteção da sprint, rituais assíncronos checklist-driven, enforcement de freeze.

## 1. Propósito do papel

Scrum Master é responsável por:

1. **Proteger Sprint Goal** — freeze de baseline, bloquear scope creep
2. **Cadência** — manter ciclo de 10 dias úteis por sprint (G.1)
3. **Ritos assíncronos** — 5 rituais checklist-driven (§3)
4. **Impedimentos** — classificar, escalar ao PM se necessário
5. **Fluxo entre doc-backlog-execução** — garantir que mudanças não atropelam
6. **Disciplina pós-sprint** — consolidação documental obrigatória (K.1)
7. **Prevenção de churn** — recusar stories sem DoR completo
8. **Sincronização BE↔FE dentro da sprint** — contrato-first (H.1)

## 2. Agentes que ocupam o papel

| Agente/Skill           | Quando usar                                        | Input típico                  | Output típico                                   |
| ---------------------- | -------------------------------------------------- | ----------------------------- | ----------------------------------------------- |
| `/scrum-master` (BMAD) | Sprint Planning, retrospective, checklist de ritos | Backlog + capacity            | Sprint plan com stories + estimativas           |
| `/squad-configurator`  | Configuração da squad por fase                     | Fase + escopo                 | Doc `docs/squads/SQUAD-F<N>.md`                 |
| `/bmad-orchestrator`   | Ativação/desativação de fase                       | Sinal de início de fase       | Handoff para agentes específicos                |
| **Humano (você)**      | Judgment calls em impedimentos, quebra de freeze   | Contexto do que está travando | Decisão registrada (backlog, ADR, ou continuar) |
```

- [ ] **Step 3: Escrever Seção 3 — Rituais assíncronos (5 rituais)**

Conteúdo detalhado dos 5 rituais:

```markdown
## 3. Rituais assíncronos (5 rituais checklist-driven)

### 3.1 Planning (Dia 0-1 da sprint)

**Objetivo:** congelar baseline documental, aceitar stories na sprint, definir Sprint Goal.

**Checklist:**

- [ ]   1. PM entrega backlog priorizado para sprint
- [ ]   2. Validar DoR de cada story (§5 do 02-PRODUCT-OPERATING-MODEL)
- [ ]   3. Rejeitar stories com DoR incompleto (voltam para backlog)
- [ ]   4. Calcular capacity — baseline 17 SP + reserva 2-3 cross-cutting (O.1)
- [ ]   5. Selecionar stories que couberem
- [ ]   6. Identificar docs que a sprint vai consumir (SPECs, PRD seções, ADRs)
- [ ]   7. Atualizar `sprint_baseline` nesses docs para `F<N>-sprint<X.Y>`
- [ ]   8. Criar git tag `sprint-<N.Y>-baseline`
- [ ]   9. Escrever Sprint Goal em `stories/sprint-plan-f<N>.md`
- [ ]   10. Mapear sequência de implementação BE↔FE (H.1 — contrato-first)

**Output:** `docs/stories/sprint-plan-f<N>.md` atualizado + git tag + baseline frozen.

### 3.2 Daily self-check (diário)

**Objetivo:** auto-verificação de progresso e impedimentos.

**Checklist (a cada dia útil da sprint):**

- [ ]   1. `TaskList` — ver tasks in-progress e pending
- [ ]   2. Verificar: estou no trilho do Sprint Goal?
- [ ]   3. Algum impedimento bloqueando? → classificar (§5)
- [ ]   4. DoD da story atual está claro? → se não, consultar 02-PRODUCT
- [ ]   5. Registrar em `TaskList` progressão + blockers

**Output:** TaskList atualizado; `16-OPEN-QUESTIONS` se surgiu dúvida cross-cutting.

### 3.3 Pre-PR Review (antes de cada merge)

**Objetivo:** DoD check antes de fechar story.

**Checklist:**

- [ ]   1. Código passa `./vendor/bin/pint --dirty --format agent`
- [ ]   2. Código passa `./vendor/bin/phpstan analyse --level=6`
- [ ]   3. Testes passam `php artisan test --compact`
- [ ]   4. Prettier passa para arquivos FE
- [ ]   5. Invocar `/pr-review-toolkit:code-reviewer` sobre diff
- [ ]   6. Invocar `/pr-review-toolkit:pr-test-analyzer` para coverage de AC
- [ ]   7. Invocar `/pr-review-toolkit:silent-failure-hunter` para error handling
- [ ]   8. Se toca docs: frontmatter válido + links OK
- [ ]   9. Se toca docs frozen sem ADR → bloquear
- [ ]   10. PR description inclui AC atendidos + screenshots/demo se FE

**Output:** PR aprovado para merge OU comentários específicos de bloqueio.

### 3.4 Sprint Review (Dia 9 da sprint)

**Objetivo:** demo ao cliente + validação de AC.

**Checklist:**

- [ ]   1. Rodar app local (Docker Compose) — `make up`
- [ ]   2. Seedar dados de demo — `make fresh && make seed-demo`
- [ ]   3. Subir tunnel — `ngrok http 80` (P.1)
- [ ]   4. Enviar URL para cliente com 48h de validade
- [ ]   5. Preparar checklist de AC para conduzir demo
- [ ]   6. Durante demo: capturar feedback em `16-OPEN-QUESTIONS` com tag `from-client-sprint-N.Y`
- [ ]   7. Classificar cada feedback: (a) aprovado, (b) ajuste menor, (c) repensar próxima sprint

**Output:** `16-OPEN-QUESTIONS` atualizado + lista de AC validados.

**Caso especial F1 (decisão M.1):** demo é técnica — mostra healthcheck, `/me`, arch tests verdes. Cliente é informado que F2 é a primeira demo funcional.

### 3.5 Retrospective + Docs Consolidation (Dia 10 da sprint — OBRIGATÓRIO K.1)

**Objetivo:** liberar baseline, registrar lições, preparar próxima sprint.

**Checklist:**

- [ ]   1. Revisar stories concluídas vs comprometidas
- [ ]   2. Calcular velocity real (SP entregue)
- [ ]   3. Registrar em `docs/reports/<sprint-N.Y>-retrospective.md`:
    - O que correu bem
    - O que pode melhorar
    - Ações concretas para próxima sprint
- [ ]   4. Atualizar SPECs: `active` para concluídos; `refactor-pending` para ajustes descobertos
- [ ]   5. Atualizar `last_reviewed` em todos os docs tocados
- [ ]   6. Bumpar `version` SemVer onde aplicável
- [ ]   7. Fechar ADRs pendentes (proposed → accepted)
- [ ]   8. Liberar `sprint_baseline` (null ou próxima sprint)
- [ ]   9. Criar git tag `sprint-<N.Y>-done`
- [ ]   10. Handoff ao PM: relatório + sugestões de refinamento

**Output:** retrospective doc + docs consolidados + baseline liberada.
```

- [ ] **Step 4: Escrever Seção 4 — Enforcement de freeze**

Conteúdo:

```markdown
## 4. Enforcement de freeze (decisão A.2 — agent-checked)

### 4.1 Mecanismo

Quando um PR altera `docs/*.md`, o agente `/scrum-master` é invocado como parte do Pre-PR Review:
```

/scrum-master verifica freeze PR #<NNN>

```

Input: diff do PR + sprint ativa (derivada de git tag atual).

Output: aprovação OU lista de arquivos frozen violados.

### 4.2 Regras

Um arquivo está frozen se:
- `sprint_baseline` == sprint ativa
- `change_during_sprint` == false

Exceção: mudanças que tocam apenas:
- Typos (detectáveis via `git diff --stat` < 5 linhas)
- Links cruzados (detectáveis via `git diff` sendo apenas `](...)`)
- Correção de frontmatter `last_reviewed`

### 4.3 Bloqueio

Quando detecta violação:

1. Comenta no PR: "Arquivo `<path>` está frozen na sprint <X.Y>. Opções: (a) aguardar fim da sprint, (b) ADR de quebra de freeze"
2. Adiciona label `docs-freeze-violation`
3. Requer ADR antes de re-review

### 4.4 Fallback (risco R6)

Se `/scrum-master` falhar tecnicamente no check, fallback é manual:
- PM humano faz check antes de aprovar PR
- Registrar em 16-OPEN-QUESTIONS a limitação
- ADR-NNNN para eventual migração para A.1 (advisory only)
```

- [ ] **Step 5: Escrever Seção 5 — Impedimentos**

Conteúdo:

```markdown
## 5. Impedimentos

### 5.1 Classificação

| Tipo                 | Definição                                                | Escalação                                                                   |
| -------------------- | -------------------------------------------------------- | --------------------------------------------------------------------------- |
| **Técnico**          | Bug, configuração, ferramenta quebrada                   | Resolve localmente OU registra 16-OPEN-QUESTIONS                            |
| **Blocker externo**  | Dependência de terceiro (gateway Itaú, cliente, serviço) | Registra como blocker; timeline indefinida; reorganiza story para dar volta |
| **Scope creep**      | Feature aumentou vs planejado                            | Bloqueia; vira backlog OU ADR de quebra de freeze                           |
| **Decisão pendente** | PM precisa bater martelo                                 | Registra 16-OPEN-QUESTIONS com responsável PM                               |
| **Drift documental** | Doc não bate com código                                  | Registra em próxima retrospective; ajusta doc ou código                     |

### 5.2 Fluxo padrão

1. SM detecta impedimento via daily self-check
2. Classifica conforme §5.1
3. Tenta resolver em ≤ 1 hora
4. Se não resolver → escala ao PM (se decisão de negócio) ou arquitetura (se técnico)
5. Registra em `16-OPEN-QUESTIONS` com timebox

### 5.3 Quando pausar a sprint

Sinais para considerar pausar a sprint (casos raros):

- Blocker externo bloqueia ≥ 50% da capacity
- Decisão crítica pendente ≥ 2 dias
- Time inteiro (você + agentes-chave) indisponível

Em pause: git tag `sprint-<N.Y>-paused` + notas em retrospective.
```

- [ ] **Step 6: Escrever Seção 6 — Definition of Done (DoD)**

Conteúdo (decisão J.1):

```markdown
## 6. Definition of Done (DoD)

Checklist que toda story precisa passar antes de fechar (gate entre SM → PM):

### 6.1 Critérios obrigatórios

- [ ] **Código escrito e revisado** por `/pr-review-toolkit:code-reviewer`
- [ ] **Testes passando** — `php artisan test --compact` 100%
- [ ] **PHPStan level 6 limpo** — `./vendor/bin/phpstan analyse --level=6` sem erros novos
- [ ] **Pint limpo** — `./vendor/bin/pint --dirty --format agent` sem alterações
- [ ] **Prettier limpo** — `npx prettier --write resources/` sem diffs
- [ ] **Review-agent aprovado** — `pr-review-toolkit:pr-test-analyzer` e `silent-failure-hunter` sem bloqueios críticos
- [ ] **PR aprovado** — review humano OK
- [ ] **AC funcional atendidos** — validado pelo PM
- [ ] **Commit convencional** — `tipo(escopo): descrição pt-BR`

### 6.2 Critérios recomendados (não-bloqueante)

- [ ] **Coverage mantido ou melhorado** — sem regressão
- [ ] **Docs relacionados atualizados** — SPECs, ADRs, thin indexes
- [ ] **Screenshot/demo** incluído no PR se FE

### 6.3 Quem valida

Scrum Master (`/scrum-master`) faz o checklist técnico; Product Manager valida AC funcional. Ambos precisam aprovar.

**Story sem DoD completo** → não fecha; volta para in-progress.
```

- [ ] **Step 7: Escrever Seção 7 — Artefatos mantidos**

Conteúdo:

```markdown
## 7. Artefatos que o papel mantém

| Artefato            | Caminho                                      | Cadência      |
| ------------------- | -------------------------------------------- | ------------- |
| Sprint plans        | `docs/stories/sprint-plan-f<N>.md`           | Por fase      |
| Stories             | `docs/stories/STORY-NNN.md`                  | Pré-sprint    |
| Squad docs          | `docs/squads/SQUAD-F<N>.md`                  | Por fase      |
| Retrospective notes | `docs/reports/<sprint-N.Y>-retrospective.md` | Fim de sprint |
| Git tags            | `sprint-<N.Y>-baseline`, `sprint-<N.Y>-done` | Por sprint    |
| Thin index 04       | `docs/04-SQUAD-TOPOLOGY.md`                  | On-change     |
```

- [ ] **Step 8: Escrever Seção 8 — Handoff com PM**

Conteúdo:

```markdown
## 8. Handoff com Product Manager

**DoD é o contrato.** SM entrega stories com DoD completo; PM valida AC funcional e aprova release.

### 8.1 Entrada (PM → SM)

No Sprint Planning:

- Backlog priorizado com DoR completo
- Estimativas SP
- Dependências

### 8.2 Saída (SM → PM)

No Sprint Retrospective:

- Stories done (SPs fechados)
- Stories em refactor (AC parcial)
- Lições aprendidas
- Sugestões para DoR/DoD refinement
- Velocity calculada (input para próximo Planning)

### 8.3 Reunião de handoff

Assíncrona via git commit + TaskList:

- SM commita `docs/reports/<sprint-N.Y>-retrospective.md`
- PM lê + atualiza backlog baseado em lições
- Próximo Planning usa input refinado
```

- [ ] **Step 9: Validar frontmatter + Prettier**

```bash
npx prettier --write docs/03-SCRUM-OPERATING-MODEL.md
node -e "$(cat <<'EOF'
const fs = require('fs');
const path = 'docs/03-SCRUM-OPERATING-MODEL.md';
const content = fs.readFileSync(path, 'utf8');
const match = content.match(/^---\n([\s\S]*?)\n---/);
const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed', 'review_cadence', 'change_during_sprint'];
const missing = required.filter(k => !new RegExp('^' + k + ':', 'm').test(match[1]));
if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
console.log('OK');
EOF
)"
```

Expected: `OK`.

## Task 2.3: Escrever 04-SQUAD-TOPOLOGY.md

**Files:**

- Create: `docs/04-SQUAD-TOPOLOGY.md`

- [ ] **Step 1: Criar arquivo com frontmatter**

```yaml
---
title: Squad Topology — mapa geral de agentes solo+Claude Code
version: 1.0.0
date: 2026-04-23
status: draft
sprint_baseline: null
owner_role: architect
last_reviewed: 2026-04-23
review_cadence: quarterly
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever Seção 1 — Topologia**

Conteúdo:

```markdown
# Squad Topology

> Mapa geral da squad. Complementa `docs/squads/SQUAD-F<N>.md` (squad específica por fase). Este doc cobre topologia cross-phase: 4 lanes virtuais, regras de handoff, sincronização BE↔FE.

## 1. Topologia

1 humano (você — orquestrador) + N agentes Claude Code/BMAD em 4 lanes virtuais:

​`
                    ┌─── BE Lane (Laravel)
                    │
você (orquestrador) ├─── FE Lane (React SPA)
                    │
                    ├─── Cross-cutting (Processo + Arquitetura)
                    │
                    └─── QA + Review
​`

### 1.1 Princípios

- **Humano toma decisão final** — agentes produzem análises e opções
- **Um agente por domínio** — não sobrepor responsabilidades
- **Handoff explícito** — outputs de um agente viram inputs de outro
- **Fallback humano** — qualquer agente pode ser substituído por você se falhar
- **Solo + assíncrono** — nenhuma cerimônia síncrona entre agentes
```

- [ ] **Step 3: Escrever Seção 2 — BE Lane**

Conteúdo:

```markdown
## 2. BE Lane (Laravel)

### 2.1 Agentes/skills

| Agente/Skill                       | Quando usar                                                 | Ordem típica numa story BE |
| ---------------------------------- | ----------------------------------------------------------- | -------------------------- |
| `laravel-specialist`               | Setup Laravel, guards, Sanctum, providers                   | 1 — kickoff                |
| `laravel-architecture`             | Decisões arquiteturais (service vs action, DTO vs Resource) | 2 — design                 |
| `laravel-api`                      | REST API design, versioning, resources, pagination          | 3 — design API             |
| `laravel-best-practices`           | Padrões gerais (strict_types, type hints)                   | Durante todo dev           |
| `laravel-models`                   | Eloquent models, relacionamentos, casts                     | 4 — data layer             |
| `eloquent-best-practices`          | Query optimization, N+1 prevention                          | 4 — data layer             |
| `laravel-services`                 | Services de domínio, managers externos                      | 5 — business logic         |
| `laravel-actions`                  | Actions invocáveis (Spatie QueueableAction)                 | 5 — business logic         |
| `laravel-dtos`                     | Spatie Laravel Data                                         | 5 — business logic         |
| `laravel-enums`                    | Backed enums PHP 8.1                                        | 5 — business logic         |
| `laravel-exceptions`               | Custom exceptions                                           | 5 — business logic         |
| `laravel-jobs`                     | Queues, Horizon                                             | 6 — async                  |
| `laravel-policies`                 | Authorization                                               | 7 — security               |
| `laravel-validation`               | Form requests                                               | 7 — security               |
| `laravel-security`                 | Sanctum, CSRF, rate limiting                                | 7 — security               |
| `laravel-routing`                  | Routes + middleware                                         | 8 — HTTP layer             |
| `laravel-controllers`              | Thin controllers                                            | 8 — HTTP layer             |
| `laravel-testing` / `pest-testing` | Pest feature tests                                          | 9 — tests                  |
| `php-best-practices`               | PHP 8.4 features, PSR                                       | Durante todo dev           |
| `laravel-quality`                  | Pint + PHPStan                                              | Pré-PR                     |

### 2.2 Ordem canônica por story BE

1. `/laravel-architecture` — decide camadas (controller → action → service → repository)
2. `/laravel-models` + `/eloquent-best-practices` — cria/modifica models + migrations
3. `/laravel-dtos` + `/laravel-enums` — tipa dados de transporte
4. `/laravel-actions` ou `/laravel-services` — implementa domain logic
5. `/laravel-api` + `/laravel-controllers` — expõe via HTTP
6. `/laravel-policies` + `/laravel-validation` — valida + autoriza
7. `/pest-testing` — feature tests
8. `/laravel-quality` — pint + phpstan
```

- [ ] **Step 4: Escrever Seção 3 — FE Lane**

Conteúdo:

```markdown
## 3. FE Lane (React SPA)

### 3.1 Agentes/skills

| Agente/Skill                    | Quando usar                                            | Ordem típica numa story FE |
| ------------------------------- | ------------------------------------------------------ | -------------------------- |
| `react-patterns`                | Hooks, composition, TypeScript strict                  | Durante todo dev           |
| `react-best-practices`          | Performance, error boundaries                          | Durante todo dev           |
| `react-state-management`        | Zustand stores, React Query cache                      | 3 — state design           |
| `react-ui-patterns`             | Loading states, error handling, forms                  | 4 — UI patterns            |
| `frontend-patterns`             | Geral (Next, state, perf)                              | Durante todo dev           |
| `frontend-design`               | Design system, tokens, layout                          | 2 — visual design          |
| `interface-design`              | Dashboards, admin panels                               | 2 — visual design          |
| `building-components`           | Componentes acessíveis composable                      | 5 — components             |
| `ui-ux-pro-max`                 | Estilos, paletas, tipografia                           | 2 — visual design          |
| `tailwindcss-development`       | Utilities, dark mode, responsive                       | Durante componentes        |
| `tailwind-design-system`        | v4 tokens, CSS vars                                    | 2 — visual design          |
| `react-use`                     | Hooks prontos (useLocalStorage, useDebounce)           | 3 — state design           |
| `react-modernization`           | Migração para patterns atuais                          | Ad-hoc                     |
| `vercel-react-view-transitions` | View transitions API                                   | Ad-hoc                     |
| `vercel-react-best-practices`   | Next.js specific (não se aplica; portal é Vite+Router) | —                          |
| `react-components`              | Converter Stitch designs em componentes                | Ad-hoc                     |

### 3.2 Ordem canônica por story FE

1. `/frontend-design` ou `/interface-design` — design do fluxo/tela
2. `/react-state-management` — define stores + queries
3. `/react-ui-patterns` — define padrões de loading/error
4. `/building-components` — componentiza UI
5. `/tailwindcss-development` — estiliza com Tailwind v4
6. `/react-best-practices` — otimiza (memo, lazy)
7. Playwright E2E — testa fluxo
```

- [ ] **Step 5: Escrever Seção 4 — Cross-cutting lane**

Conteúdo:

```markdown
## 4. Cross-cutting lane (Processo + Arquitetura)

### 4.1 Agentes/skills

| Agente/Skill                      | Papel                          | Quando acionar                     |
| --------------------------------- | ------------------------------ | ---------------------------------- |
| `/product-manager` (BMAD)         | Product Manager                | 6 gatilhos do §4 de 02-PRODUCT     |
| `/scrum-master` (BMAD)            | Scrum Master                   | 5 rituais do §3 de 03-SCRUM        |
| `/bmad-orchestrator`              | Orquestrador de fase           | Início de fase nova                |
| `/squad-configurator`             | Configuração squad por fase    | Início de fase nova                |
| `/adr-skill`                      | Criação de ADRs                | Decisão cara de reverter           |
| `feature-dev:code-architect`      | Arquitetura macro de feature   | Story com ≥ 8 SP                   |
| `feature-dev:code-explorer`       | Exploração de codebase         | Antes de tocar código novo         |
| `feature-dev:code-reviewer`       | Review high-level              | Pré-PR de story complexa           |
| `api-design-principles`           | Design de API                  | Contrato OpenAPI dia 1-2 da sprint |
| `api-surface-evolution` (Laravel) | Evolução de API com versioning | Ao bumpar MAJOR API                |
```

- [ ] **Step 6: Escrever Seção 5 — QA + Review lane**

Conteúdo:

```markdown
## 5. QA + Review lane

### 5.1 Agentes/skills

| Agente/Skill                                        | Papel                           | Gatilho                                    |
| --------------------------------------------------- | ------------------------------- | ------------------------------------------ |
| `pr-review-toolkit:code-reviewer`                   | Review geral de PR              | Todo PR                                    |
| `pr-review-toolkit:pr-test-analyzer`                | Gap de coverage de AC           | Todo PR                                    |
| `pr-review-toolkit:silent-failure-hunter`           | Detecta suppressão de erros     | Todo PR                                    |
| `pr-review-toolkit:type-design-analyzer`            | Qualidade de types/interfaces   | PR com type novo                           |
| `pr-review-toolkit:comment-analyzer`                | Qualidade de docstrings         | PR com muitos comments                     |
| `superpowers:verification-before-completion`        | Validação antes de claim "done" | Antes de marcar task como concluída        |
| `superpowers:requesting-code-review`                | Solicita review efetivo         | Pré-PR para PR complexo                    |
| `laravel-owasp-security` / `laravel-security-audit` | Auditoria de segurança          | Stories que tocam auth, uploads, validação |
| Playwright MCP                                      | E2E testing                     | Story FE completa                          |

### 5.2 Ordem padrão pré-PR

1. `verification-before-completion` — valida que story está realmente done
2. `code-reviewer` — review geral
3. `pr-test-analyzer` — coverage de AC
4. `silent-failure-hunter` — error handling
5. `type-design-analyzer` — se introduziu types
6. Playwright E2E — se FE envolvido
```

- [ ] **Step 7: Escrever Seção 6 — Sincronização BE↔FE (contrato-first H.1)**

Conteúdo:

````markdown
## 6. Sincronização BE↔FE intra-sprint (decisão H.1)

### 6.1 Contrato OpenAPI é o ponto de sync

Princípio: **contrato publicado e congelado no dia 1-2 da sprint destrava lanes paralelas**.

### 6.2 Fluxo diário

​```
Dia 1 (Planning)
SM invoca /api-design-principles + BE lane
→ rascunho OpenAPI das rotas afetadas da sprint
BE lane revisa contrato
→ feedback + ajustes

Dia 2 (morning)
Contrato OpenAPI congelado em docs/api/openapi-skeleton.yaml
FE lane roda `npx openapi-typescript` codegen
→ resources/spa/src/api/types.gen.ts atualizado
FE lane pode começar (mock + tipos)

Dia 2-7 (parallel)
BE lane implementa endpoints contra contrato
FE lane implementa hooks/pages contra contrato
Sem bloqueio mútuo enquanto contrato está congelado

Dia 8 (integration)
Conecta FE ↔ BE real (sem mock)
Playwright E2E valida fluxo completo

Dia 9 (Review)
Demo com sistema real
Se contrato precisa mudar → ADR de quebra de freeze
​```

### 6.3 Quando o contrato pode mudar durante a sprint

- **NUNCA** sem ADR — isso destrava bug propagation mútua
- Se mudança é obrigatória (bug, segurança, AC mal escrito):
    1. ADR-NNNN: quebra de freeze de contrato sprint X.Y
    2. Atualizar openapi-skeleton.yaml
    3. FE roda codegen e ajusta
    4. Comunica no PR afetado
    5. Registrar lição na retrospective

### 6.4 Handoff artifacts

| Artifact                             | Produzido por                   | Consumido por       |
| ------------------------------------ | ------------------------------- | ------------------- |
| `docs/api/openapi-skeleton.yaml`     | BE lane + api-design-principles | FE lane             |
| `resources/spa/src/api/types.gen.ts` | codegen (FE lane)               | FE components/hooks |
| Endpoints Laravel                    | BE lane                         | FE via axios        |
| Postman collection                   | BE lane (opcional)              | QA manual           |
| Playwright E2E                       | QA lane                         | Integração dia 8    |
````

- [ ] **Step 8: Escrever Seção 7 — Regras de acoplamento**

Conteúdo:

```markdown
## 7. Regras de acoplamento

### 7.1 Vertical slice obrigatório

- BE e FE **sempre** na mesma sprint (exceção: F1 infra-only M.1)
- Stories BE que não têm FE correspondente → suspeita de scope creep
- Stories FE sem endpoint BE correspondente → contrato mock aceitável para 1 sprint, depois deve haver BE

### 7.2 Ordenação BE vs FE dentro da sprint

- BE lidera nos primeiros 2 dias (define contrato)
- FE destrava ao fim do dia 2 (após codegen)
- Ambas paralelas dias 3-7
- Integração dia 8
- Demo dia 9

### 7.3 O que NUNCA fazer

- ❌ Criar endpoint BE sem atualizar openapi-skeleton.yaml
- ❌ FE usar tipos manuais em vez de `types.gen.ts`
- ❌ Contrato mudar silenciosamente mid-sprint
- ❌ Slice "só BE" ou "só FE" por múltiplas sprints (violação do princípio vertical)
- ❌ BE ou FE ignorarem Playwright E2E na integração
```

- [ ] **Step 9: Escrever Seção 8 — Relação com squad-específica**

Conteúdo:

```markdown
## 8. Relação com SQUAD-F<N>

Este doc é **geral**. Cada fase tem seu `docs/squads/SQUAD-F<N>.md` gerado via `/squad-configurator` que:

- Lista skills específicas da fase
- Mapeia stories → skills primárias
- Critérios de aceite da fase
- BMAD agents utilizados

Exemplo: `docs/squads/SQUAD-F1.md` cobre F1 (infra). Durante F2+, novas squad docs serão geradas.

**Leitura recomendada:**

1. Este doc primeiro (topologia geral)
2. SQUAD-F<N> da fase atual (específico)
```

- [ ] **Step 10: Validar frontmatter + Prettier**

```bash
npx prettier --write docs/04-SQUAD-TOPOLOGY.md
node -e "..." # mesma validação dos anteriores
```

## Task 2.4: Validação + commit Dia 2

- [ ] **Step 1: Validar frontmatter de todos os 3 docs**

```bash
for f in docs/02-PRODUCT-OPERATING-MODEL.md docs/03-SCRUM-OPERATING-MODEL.md docs/04-SQUAD-TOPOLOGY.md; do
  echo "=== $f ==="
  node -e "
  const fs = require('fs');
  const content = fs.readFileSync('$f', 'utf8');
  const match = content.match(/^---\n([\s\S]*?)\n---/);
  if (!match) { console.error('NO FRONTMATTER'); process.exit(1); }
  const required = ['title', 'version', 'date', 'status', 'owner_role', 'last_reviewed', 'review_cadence', 'change_during_sprint'];
  const missing = required.filter(k => !new RegExp('^' + k + ':', 'm').test(match[1]));
  if (missing.length) { console.error('MISSING:', missing); process.exit(1); }
  console.log('OK');
  "
done
```

Expected: `OK` para os 3.

- [ ] **Step 2: Validar links**

```bash
for f in docs/02-PRODUCT-OPERATING-MODEL.md docs/03-SCRUM-OPERATING-MODEL.md docs/04-SQUAD-TOPOLOGY.md; do
  echo "=== $f ==="
  npx markdown-link-check "$f" --config .mlc-config.json 2>&1 | tail -10
done
```

⚠️ **Aceitável:** falhas para `00-INDEX.md` (será criado Dia 5), `12-VERTICAL-SLICE-DELIVERY-PLAN.md` (Dia 3), `16-OPEN-QUESTIONS-AND-BLOCKERS.md` (Dia 3).

- [ ] **Step 3: Stage + commit**

```bash
git add docs/02-PRODUCT-OPERATING-MODEL.md docs/03-SCRUM-OPERATING-MODEL.md docs/04-SQUAD-TOPOLOGY.md
git commit -m "$(cat <<'EOF'
docs(docs): operating models PM/SM + squad topology

Dia 2 da reconstrução documental v1.0.0. Entrega:
- 02-PRODUCT-OPERATING-MODEL.md — papel Product Manager para
  solo+agentes; 6 gatilhos de ativação, DoR, change control,
  handoff com SM
- 03-SCRUM-OPERATING-MODEL.md — papel Scrum Master para
  solo+agentes; 5 rituais checklist-driven (Planning, Daily,
  Pre-PR, Review, Retrospective), enforcement de freeze A.2,
  DoD, handoff com PM
- 04-SQUAD-TOPOLOGY.md — 4 lanes virtuais (BE, FE, Cross-cutting,
  QA+Review) com mapa de agentes por responsabilidade,
  sincronização contrato-first H.1

Ref: docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md §5
EOF
)"
```

- [ ] **Step 4: Git tag**

```bash
git tag -a docs-reconstruction-day-2 -m "Dia 2 reconstrução: operating models + squad topology"
```

### ✅ Checkpoint Dia 2

Aguardar aprovação antes de Dia 3.

---

# 📅 Dia 3 (sex 2026-04-24) — Vertical Slice + Open Questions

**Meta do dia:** `12-VERTICAL-SLICE-DELIVERY-PLAN.md` + `16-OPEN-QUESTIONS-AND-BLOCKERS.md` (seed ~10) + commit.

## Task 3.1: Escrever 12-VERTICAL-SLICE-DELIVERY-PLAN.md

**Files:**

- Create: `docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md`

- [ ] **Step 1: Criar com frontmatter**

```yaml
---
title: Vertical Slice Delivery Plan — BE+FE acoplados por sprint
version: 1.0.0
date: 2026-04-24
status: draft
sprint_baseline: null
owner_role: scrum-master
last_reviewed: 2026-04-24
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: []
related_features:
    [
        SPEC-001,
        SPEC-002,
        SPEC-003,
        SPEC-004,
        SPEC-005,
        SPEC-007,
        SPEC-009,
        SPEC-010,
        SPEC-011,
        SPEC-012,
        SPEC-013,
        SPEC-014,
        SPEC-015,
        SPEC-F-001,
        SPEC-F-002,
        SPEC-F-003,
        SPEC-F-004,
        SPEC-F-005,
        SPEC-F-006,
        SPEC-F-007,
        SPEC-F-008,
        SPEC-F-009,
        SPEC-F-010,
        SPEC-F-011,
    ]
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever Seção 1 — Definição**

Usar texto do design spec §6.1 como base; expandir:

```markdown
# Vertical Slice Delivery Plan

## 1. Definição de vertical slice

Uma **vertical slice** é uma funcionalidade end-to-end entregue em **uma sprint**, incluindo:

- Migration(s) de BD
- Model(s) Eloquent
- Service/Action de domínio
- Controller + rota + validation
- Page/hook/store FE
- Teste E2E Playwright
- Demo testável pelo cliente

### 1.1 O que NÃO é vertical slice

- Stories "só BE" por múltiplas sprints
- Stories "só FE" com mock permanente
- Refactors de infra sem user-facing outcome
- (Exceção: F1 é infra-only aceita por decisão M.1)

### 1.2 Critérios de aceite de slice

Uma slice está "complete" se:

- [ ] Cliente consegue executar o fluxo end-to-end
- [ ] Todos os AC em Gherkin passam (verificados manualmente)
- [ ] Playwright E2E verde
- [ ] Demo apresentada no Sprint Review
- [ ] Commits seguem convenção BE+FE na mesma sprint
```

- [ ] **Step 3: Escrever Seção 2 — Princípios (cita decisões H.1, M.1, P.1, Q.1)**

Conteúdo alinhado ao design spec §6.2.

- [ ] **Step 4: Escrever Seção 3 — Ciclo canônico de 10 dias**

Tabela detalhada do ciclo, com atividades por dia, agentes envolvidos, output esperado.

- [ ] **Step 5: Escrever Seção 4 — Roadmap de slices por fase**

Reproduzir tabela do design spec §6.3 com F1→F8 e SPECs envolvidos.

Expandir cada fase com:

- Objetivo de negócio
- Demo esperada para cliente
- Critério de sucesso da fase
- Riscos

- [ ] **Step 6: Escrever Seção 5 — Matriz de dependências**

```markdown
## 5. Matriz de dependências entre slices

​`
F1 (infra) ──────> F2 (adesão pública) ──> F3 (login + wizard aut)
                        │                        │
                        └──> F7 (admin — valida) └──> F4 (pagamento)
                                                      │
                                                      └──> F5 (convites) ──> F6 (extras)
                                                                               │
                                                                               └──> F8 (emails + hardening)
​`
```

- [ ] **Step 7: Escrever Seção 6 — Capacidade**

Conteúdo (decisão O.1):

```markdown
## 6. Capacidade por sprint

### 6.1 Baseline

- **17 SP** em stories de feature
- **2-3 SP** reserva para cross-cutting (docs, ADRs, review)
- **Total efetivo: ~20 SP por sprint**
- **Velocity real: calculada ao fim de cada sprint; baseline ajustada após 3 sprints**

### 6.2 Distribuição BE vs FE

Depende do slice:

- **F2 (adesão pública):** ~60% BE (Foundation + API) / 40% FE (wizard público)
- **F3 (login + wizard aut):** ~50/50
- **F4 (pagamento):** ~70% BE (gateway + webhooks) / 30% FE (status visual)
- **F5 (convites):** ~40% BE / 60% FE (RSVP público tem UX rica)
- **F7 (admin):** ~50/50 com Livewire no admin

### 6.3 Buffer

Sempre reservar Dia 10 como buffer. Se slice termina dia 9 (incluindo demo), Dia 10 é para:

- Retrospective (obrigatório)
- Consolidação documental
- Folga recuperativa
- Preparação da próxima sprint
```

- [ ] **Step 8: Escrever Seção 7 — Como cliente testa**

Conteúdo (decisão P.1):

```markdown
## 7. Como o cliente testa cada slice

### 7.1 Ambiente (decisão P.1)

- **Docker Compose local** no sua máquina
- **Tunnel via ngrok** para exposição temporária
- **Link válido por 48h** enviado ao cliente por email/WhatsApp
- **Sem infra de staging cloud** (fora do escopo atual)

### 7.2 Fluxo de teste

1. Após DoD passar (fim da sprint), rodar `make up` + `make seed-demo`
2. Iniciar tunnel: `ngrok http 80 --domain=<dominio-temporario>.ngrok.app`
3. Capturar URL
4. Enviar ao cliente com:
    - URL
    - Validade (48h)
    - Checklist de AC para conduzir o teste
    - Credenciais de demo (formando demo, admin demo)
5. Capturar feedback em `16-OPEN-QUESTIONS.md` tag `from-client-sprint-<N.Y>`
6. Encerrar tunnel após 48h

### 7.3 Fallback

Se ngrok falhar:

- Opção A: vídeo demo gravado via OBS; cliente assiste + envia feedback escrito (inferior à interativa)
- Opção B: troubleshooting (ver `15-RUNBOOK.md` — seção "demo ao cliente")

### 7.4 Caso F1

Cliente é informado via email pré-F1:

> "F1 é debt-payment técnico — infraestrutura que vai suportar F2+. Não há demo interativa. F2 (primeira feature de negócio) é a primeira demo funcional, prevista para aproximadamente [data]."
```

- [ ] **Step 9: Escrever Seção 8 — Feature flags**

Conteúdo (decisão Q.1):

```markdown
## 8. Feature flags — slice incompleto

### 8.1 Quando usar

Slice que não fecha 100% até dia 9 da sprint pode:

- Opção A: estender para próxima sprint (penaliza velocity)
- Opção B: merge parcial com feature flag desligada (preserva velocity; débito técnico)

### 8.2 Ferramenta (decisão Q.1)

**Laravel Pennant** — oficial Laravel 13, per-user flags.

Instalação: `composer require laravel/pennant` (adicionar em F3 quando primeira flag for necessária).

### 8.3 Fluxo

1. PR merge com flag `off` como default
2. Feature invisível em prod
3. Flag liga para user específico (você) para testar
4. ADR-NNNN: "Feature X com flag — débito Y até sprint Z" (débito técnico)
5. Próxima sprint: trabalho restante + remove flag
6. Flag removida quando 100% dos usuários ativos

### 8.4 Disciplina

- Flags não podem durar mais de 2 sprints
- Retrospective registra flag ativa como débito técnico
- Se flag > 2 sprints → scope creep; reconsiderar SPEC
```

- [ ] **Step 10: Escrever Seção 9 — Rollback**

Conteúdo:

```markdown
## 9. Rollback conceitual

### 9.1 Sinais de rejeição pelo cliente

Durante Sprint Review, cliente pode:

- Aprovar (default)
- Aprovar com ressalvas menores → vira backlog refinement
- Rejeitar parcial → slice não passa DoD de PM; próxima sprint refina
- Rejeitar total → rollback conceitual

### 9.2 Rollback conceitual (raro)

Quando slice é rejeitado totalmente:

1. ADR-NNNN: "Rollback slice X sprint Y.Z — motivo W"
2. Feature flag desliga em prod (se Pennant em uso)
3. Código **não é revertido** (pode ser reusado após ajuste)
4. SPEC volta para `refactor-pending`
5. Backlog adiciona nova versão da story com ajustes baseados no feedback
6. Sprint seguinte reimplementa

### 9.3 Rollback de código (extremo)

Só se slice quebrou sistema existente:

- `git revert <merge-commit>`
- Hotfix deploy
- ADR obrigatório

Evitar. Slice bem projetado nunca quebra sistema anterior (princípio de aditividade).
```

- [ ] **Step 11: Validar + Prettier**

```bash
npx prettier --write docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md
node -e "..." # validação frontmatter
```

## Task 3.2: Escrever 16-OPEN-QUESTIONS-AND-BLOCKERS.md

**Files:**

- Create: `docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md`

- [ ] **Step 1: Criar com frontmatter**

```yaml
---
title: Open Questions e Blockers — cross-cutting unificado
version: 1.0.0
date: 2026-04-24
status: active
sprint_baseline: null
owner_role: product-manager
last_reviewed: 2026-04-24
review_cadence: on-change
supersedes: null
superseded_by: null
related_adrs: []
related_features: []
change_during_sprint: true
---
```

**Nota:** `change_during_sprint: true` (exceção; este doc é intencionalmente mutável durante sprint para capturar dúvidas em tempo real).

- [ ] **Step 2: Escrever estrutura + seed de 10 pendências reais**

Conteúdo (baseado na auditoria inicial):

```markdown
# Open Questions e Blockers — cross-cutting

> Doc centralizador de pendências, blockers, gaps e dúvidas que cruzam múltiplos módulos/docs. **Este doc é exceção ao freeze** — pode ser atualizado durante sprint ativa (captura de feedback cliente, descoberta de gaps).

## 1. Pendências ativas — precisam resposta antes de F2 (qua 2026-05-11)

| #     | Pergunta                                                                                                   | Por quê importa                                                           | Responsável                   | Prazo      | Status                              |
| ----- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------- | ----------------------------- | ---------- | ----------------------------------- |
| Q-001 | Cliente valida terminologia "responsável financeiro" vs "responsável legal" vs "responsável cadastro"?     | Bloqueia SPEC-F-002 (Responsáveis); afeta fields em múltiplos formulários | você/cliente                  | 2026-04-28 | 🔴 Pendente                         |
| Q-002 | Laravel Pennant é aceito como feature flag tool ou prefere alternativa?                                    | Afeta F3 onwards; precisa instalação antes de primeira flag               | você                          | 2026-05-05 | 🟡 Default aceito (Q.1)             |
| Q-003 | Ngrok paid ($10/mo) ou free (random subdomain)?                                                            | Afeta F2 demo cliente                                                     | você                          | 2026-05-04 | 🔴 Pendente                         |
| Q-004 | SPECs arquivadas (SPEC-006 Seating, SPEC-008 Enquetes) ficam em `_archive/future/` ou voltam para backlog? | Afeta escopo de v2                                                        | você                          | Pós-F8     | 🟢 Arquivadas; BACKLOG_FUTURO cobre |
| Q-005 | Gateway Itaú tem ambiente sandbox acessível para F4?                                                       | Bloqueia SPEC-003 (Pagamento)                                             | você + cliente (contato Itaú) | 2026-05-25 | 🔴 Pendente                         |
| Q-006 | Cliente tem domínio próprio para prod ou usa subdomínio da empresa hoster?                                 | Afeta F8 (deploy)                                                         | você/cliente                  | 2026-06-30 | 🟡 Adiada para F8                   |
| Q-007 | SLA de resposta do cliente em Sprint Review (48h de tunnel)?                                               | Afeta cadência — se cliente demora 1 semana, próxima sprint atrasa        | você/cliente                  | 2026-04-26 | 🔴 Pendente (definir antes de F2)   |
| Q-008 | E-mails transacionais: Mailpit em dev, AWS SES em prod? Ou Resend/Postmark?                                | Afeta F8 (SPEC-015)                                                       | você                          | 2026-06-30 | 🟡 Default: SES (decisão em F8)     |
| Q-009 | Admin multi-tenant ou single-tenant?                                                                       | Afeta SPEC-011+; pode virar ADR                                           | você                          | 2026-05-20 | 🔴 Pendente                         |
| Q-010 | Coverage de testes: exigir mínimo 80% ou só AC cobertos?                                                   | Afeta DoD; pode virar J.2 em vez de J.1                                   | você                          | 2026-05-03 | 🟢 Default aceito (J.1 sem mínimo)  |

## 2. Blockers técnicos

| #     | Bloqueador                                                            | Impacto                                               | Mitigação proposta                                                               |
| ----- | --------------------------------------------------------------------- | ----------------------------------------------------- | -------------------------------------------------------------------------------- |
| B-001 | Agente `/scrum-master` pode não fazer freeze enforcement técnico (R6) | Freeze vira advisory (A.1 fallback)                   | Testar enforcement no Dia 1 da Sprint 1.1; ADR de migração A.2→A.1 se necessário |
| B-002 | Ngrok free tier tem restrição de 1 tunnel ativo por vez               | Demo concorrente impossível (não é relevante em solo) | Sem ação                                                                         |
| B-003 | Scramble (OpenAPI generator) compatibilidade com Laravel 13           | Codegen do FE depende                                 | Verificar no Dia 4 da F1 Sprint 1.1                                              |

## 3. Gaps entre documento e implementação

| #     | Gap                                                                        | Doc afetado          | SPEC afetado    | Resolução                                                      |
| ----- | -------------------------------------------------------------------------- | -------------------- | --------------- | -------------------------------------------------------------- |
| G-001 | `prd/PRD_v4.md` ainda cita "Metronic"; real é "Inspinia"                   | prd/PRD_v4.md        | —               | Correção PATCH no próximo refinement                           |
| G-002 | CLAUDE.md diz "seating/enquetes arquivadas" mas não linka o BACKLOG_FUTURO | CLAUDE.md            | SPEC-006, 008   | Adicionar link no próximo ajuste de CLAUDE.md                  |
| G-003 | SPECs Foundation F-002 a F-011 são stubs; precisam expansão                | features/foundation/ | SPEC-F-002..011 | Expansão progressiva por fase (ver SPEC-RESTRUCTURE-PLAN §4.2) |

## 4. Gaps entre BE e FE

| #     | Gap                                                                                                                   | Doc BE                     | Doc FE                                  | Resolução                                               |
| ----- | --------------------------------------------------------------------------------------------------------------------- | -------------------------- | --------------------------------------- | ------------------------------------------------------- |
| D-001 | `prd/PLANEJAMENTO_BACKEND_APIV1.md` (134KB) pode divergir de `prd/PLANEJAMENTO_FRONTEND_REACT.md` em alguns endpoints | PLANEJAMENTO_BACKEND_APIV1 | PLANEJAMENTO_FRONTEND_REACT             | Validar ponto a ponto no Dia 4 via thin indexes 10 e 11 |
| D-002 | OpenAPI skeleton (`api/openapi-skeleton.yaml`) ainda incompleto para rotas de adesão pública                          | api/                       | frontend/08-API-INTEGRATION-CONTRACT.md | Completar durante Gate 1 do SPEC-010 (F2)               |

## 5. Dúvidas respondidas (histórico)

| #     | Pergunta                                                     | Resposta                                        | Data       | ADR                        |
| ----- | ------------------------------------------------------------ | ----------------------------------------------- | ---------- | -------------------------- |
| R-001 | Portal é SPA React ou híbrido Livewire+React?                | **SPA React puro** (decisão memória 2026-04-20) | 2026-04-20 | Registrar como ADR-0015    |
| R-002 | Estratégia de reconstrução: evoluir, reconstruir ou híbrido? | **Reconstruir (B)** — 16 docs numerados         | 2026-04-20 | Este spec + commit eeba8eb |
| R-003 | Timing: adiar F1 ou paralelo?                                | **Adiar F1 para qua 2026-04-29 (B.1)**          | 2026-04-20 | Este spec                  |
| R-004 | Squad: solo, time real, ou híbrido?                          | **Solo + agentes Claude Code/BMAD (A)**         | 2026-04-20 | Este spec                  |

## 6. Processo de manutenção

### 6.1 Adicionar nova pendência

Durante sprint:

1. Detecta-se dúvida/gap
2. Registra em §1 (se pergunta) ou §2 (se blocker técnico) ou §3/§4 (se gap)
3. Define responsável e prazo
4. Status: 🔴 Pendente

### 6.2 Resolver pendência

1. Resposta encontrada
2. Move de §1 para §5 (histórico)
3. Status: 🟢 Resolvido
4. Se gerou ADR, linka

### 6.3 Escalação

- 🔴 Pendente + prazo vencido → escala para Retrospective da sprint atual
- Pendência ≥ 2 sprints sem resposta → eleva prioridade; bloqueia features que dependem

## 7. Legenda de status

- 🔴 **Pendente** — sem resposta; dentro do prazo
- 🟡 **Decidido por default** — default do spec aceito; decisão formal postergada
- 🟢 **Resolvido** — pergunta respondida; histórico preservado

---

_Este doc é atualizado continuamente durante sprints. Owner: product-manager. Review: on-change._
```

- [ ] **Step 3: Validar + Prettier**

```bash
npx prettier --write docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md
node -e "..."
```

## Task 3.3: Validação + commit Dia 3

- [ ] **Step 1: Validar frontmatter**

```bash
for f in docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md; do
  node -e "..."  # mesma validação
done
```

- [ ] **Step 2: Validar links**

```bash
for f in docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md; do
  npx markdown-link-check "$f" --config .mlc-config.json 2>&1 | tail -10
done
```

- [ ] **Step 3: Commit**

```bash
git add docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md
git commit -m "$(cat <<'EOF'
docs(docs): vertical slice plan + open questions seed

Dia 3 da reconstrução documental v1.0.0. Entrega:
- 12-VERTICAL-SLICE-DELIVERY-PLAN.md — BE+FE acoplados por sprint,
  ciclo canônico 10 dias, roadmap F1→F8 (~157 SP em ~13 sprints),
  matriz de dependências, capacidade 17+3 SP (O.1), demo ao cliente
  via Docker+ngrok (P.1), feature flags Laravel Pennant (Q.1),
  rollback conceitual
- 16-OPEN-QUESTIONS-AND-BLOCKERS.md — doc centralizador exceção
  ao freeze; seed com 10 pendências reais, 3 blockers técnicos,
  3 gaps doc↔impl, 2 gaps BE↔FE, 4 dúvidas resolvidas

Ref: docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md §6, §7.3
EOF
)"
```

- [ ] **Step 4: Git tag**

```bash
git tag -a docs-reconstruction-day-3 -m "Dia 3 reconstrução: vertical slice + open questions"
```

### ✅ Checkpoint Dia 3

Aguardar aprovação antes de Dia 4.

---

# 📅 Dia 4 (seg 2026-04-27) — Thin Indexes (10 docs paralelos Y.1)

**Meta do dia:** 10 thin indexes (05, 06, 07, 08, 09, 10, 11, 13, 14, 15) + commit único.

**Estratégia (Y.1):** dispatch 10 agentes Explore em paralelo para ler fontes; consolidação manual em série.

## Task 4.1: Dispatch agentes Explore

- [ ] **Step 1: Dispatch 10 agentes Explore paralelos**

Usar skill `superpowers:dispatching-parallel-agents` OU invocar 10 agentes Agent(subagent_type="Explore") em uma única resposta.

Prompts para cada agente (condensados; cada um é um Explore separado):

**Agente 1 — Explore para 05-UNIFIED-PROJECT-BRIEF:**

> Leia `docs/product/PROJECT_BRIEF.md` e `docs/product/README.md`. Produza um sumário executivo (~300 palavras) com: (a) propósito do sistema, (b) valor para formandos e empresas, (c) macroescopo MVP v1, (d) fora de escopo v1 (com ref para BACKLOG_FUTURO), (e) premissas, (f) riscos principais, (g) objetivos por release. Identifique gaps e erratas para marcação 🟡 Inferido ou ⏳ Pendente.

**Agente 2 — Explore para 06-UNIFIED-PRD:**

> Leia `docs/prd/PRD_v4.md` e `docs/product/PRD_EXPANDED.md` e `docs/prd/REGRAS_NEGOCIO.md` e `docs/product/journeys-personas.md` e `docs/product/user-flows.md`. Produza um sumário (~500 palavras) com: (a) jornadas principais, (b) features por domínio (admin, portal, gateway), (c) priorização por fase (F1-F8), (d) critérios macro de aceite, (e) dependências BE↔FE. Marque bounded contexts seating/enquetes como 🟡 deferred v2.

**Agente 3 — Explore para 07-UNIFIED-SRS:**

> Leia `docs/product/SRS.md`, `docs/prd/REGRAS_NEGOCIO.md`, `docs/prd/SEGURANCA.md`, `docs/prd/PERFORMANCE.md`. Produza um sumário (~400 palavras) de requisitos consolidados: funcionais, não funcionais, integração, auth, paginação, erros, validação, permissões, observabilidade, acessibilidade, performance. Identifique NFRs que estão fragmentados e precisam consolidação.

**Agente 4 — Explore para 08-UNIFIED-SAD-ARC42:**

> Leia `docs/architecture/SAD-arc42.md` (55KB). Produza um sumário (~400 palavras) dos 12 capítulos arc42 + destacar as 5 decisões arquiteturais mais críticas (referenciando ADRs). Não reescrever o SAD; apenas indexar.

**Agente 5 — Explore para 09-ADR-INDEX:**

> Leia `docs/architecture/adrs/ADR-*.md` (14 ADRs). Para cada um: título, status (proposed/accepted/superseded/deprecated), 1 linha de resumo, data. Produza lista com link para cada. Adicionar seção sobre template de novos ADRs e regra especial "ADR-NNNN: Quebra de freeze" (F.1).

**Agente 6 — Explore para 10-API-BACKEND-INDEX:**

> Leia `docs/api/api-contract.md`, `docs/api/openapi-skeleton.yaml`, `docs/api/api-conventions.md`, `docs/api/error-envelope.md`, `docs/api/integrations.md`, listagem de `docs/modules/*.md`, e `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md`. Produza sumário (~500 palavras) com: contratos, endpoints por fase, convenções, 20 módulos BE indexados, dependências do FE. Marcar módulos por fase F1-F8.

**Agente 7 — Explore para 11-FRONTEND-REACT-INDEX:**

> Leia `docs/frontend/00-README-INDEX.md` e os outros 13 docs numerados (01-14) e `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md`. Produza sumário (~500 palavras) com: rotas, stores, hooks, componentes, fluxos, design system, QA por fluxo. Marcar deltas vs planejamento original.

**Agente 8 — Explore para 13-QA-AND-ACCEPTANCE-STRATEGY:**

> Leia `docs/qa/qa-strategy.md`, `docs/qa/acceptance-criteria.md`, `docs/qa/critical-scenarios.md`, `docs/qa/nfr-tests.md`, `docs/qa/test-plan.md`. Produza sumário (~400 palavras) com: estratégia de testes, testes BE+FE por slice, smoke, regressão, E2E, critérios funcionais. Alinhar com ciclo de 10 dias de `12-VERTICAL-SLICE-DELIVERY-PLAN.md`.

**Agente 9 — Explore para 14-DEV-SETUP-AND-WORKFLOW:**

> Leia `docs/devops/dev-setup.md`, `docs/devops/conventions.md`, `docs/devops/engineering-standards.md`, `docs/devops/ci-cd.md`, `docs/devops/tools-and-packages.md`. Produza sumário (~400 palavras) com: setup, convenções, fluxo de branch, revisão, codegen, DoR/DoD. Incluir regra OpenAPI→codegen (H.1).

**Agente 10 — Explore para 15-RUNBOOK:**

> Leia `docs/devops/runbook-deploy.md`, `docs/devops/runbook-operations.md`, `docs/devops/monitoring-alerts.md`, `docs/devops/security-operations.md`, `docs/devops/infra.md`. Produza sumário (~400 palavras) com: execução local, debug, geração de tipos, validação de integração, troubleshooting, publicação, rollback conceitual. Adicionar runbook "demo ao cliente via tunnel" (P.1).

- [ ] **Step 2: Consolidar outputs dos 10 agentes**

Receber os 10 sumários e consolidar em documento único por thin index.

## Task 4.2 a 4.11: Escrever cada thin index

**Para cada um dos 10 docs (05, 06, 07, 08, 09, 10, 11, 13, 14, 15):**

- [ ] **Step 1: Criar arquivo com frontmatter template 2 (thin index)**
- [ ] **Step 2: Escrever Seção 1 — Propósito** (1-2 parágrafos)
- [ ] **Step 3: Escrever Seção 2 — Fontes de verdade primárias** (tabela)
- [ ] **Step 4: Escrever Seção 3 — O que você PRECISA saber** (5-15 bullets do sumário do agente Explore)
- [ ] **Step 5: Escrever Seção 4 — Marcações explícitas** (Confirmado/Inferido/Pendente/Obsoleto)
- [ ] **Step 6: Escrever Seção 5 — Delta desde a fonte** (erratas, gaps, mudanças não aplicadas)
- [ ] **Step 7: Escrever Seção 6 — Docs relacionados** (links para thin indexes, ADRs, SPECs)
- [ ] **Step 8: Prettier + validação frontmatter**

**Target por doc:** 200-500 linhas.

**Exemplo completo (apenas 05 aqui; os outros 9 seguem o mesmo padrão com conteúdo específico):**

`docs/05-UNIFIED-PROJECT-BRIEF.md`:

```markdown
---
title: Unified Project Brief — Thin Index
version: 1.0.0
date: 2026-04-27
status: active
sprint_baseline: null
owner_role: product-manager
last_reviewed: 2026-04-27
review_cadence: pre-sprint
supersedes: null
superseded_by: null
related_adrs: [ADR-0001, ADR-0002]
change_during_sprint: false
---

# Unified Project Brief — Thin Index

> **Este doc é um índice.** A verdade está nas fontes listadas abaixo.
> Política de evolução: ver [01-DOCUMENTATION-GOVERNANCE](01-DOCUMENTATION-GOVERNANCE.md).

## 1. Propósito

Visão unificada do Portal ArtFinal v2 — sistema web para gerenciamento completo de formaturas. Este documento agrega propósito, valor, escopo macro, premissas e riscos em um único ponto de entrada para executivos, novos contribuidores e stakeholders externos que querem entender o "o quê e para quem" sem ler docs longos.

## 2. Fontes de verdade (primárias)

| Fonte                  | Caminho                                                | Seções relevantes | Status    |
| ---------------------- | ------------------------------------------------------ | ----------------- | --------- |
| Project Brief original | [`product/PROJECT_BRIEF.md`](product/PROJECT_BRIEF.md) | Todo              | ✅ active |
| Product README         | [`product/README.md`](product/README.md)               | Todo              | ✅ active |
| PRD v4 §1              | [`prd/PRD_v4.md`](prd/PRD_v4.md)                       | §1 (Visão)        | ✅ active |
| Roadmap macro          | [`prd/ROADMAP.md`](prd/ROADMAP.md)                     | Todo              | ✅ active |

## 3. O que você PRECISA saber antes de consultar as fontes

<!-- conteúdo do Agente 1 Explore aqui — 5-15 bullets executivos -->

- Portal ArtFinal v2 é **sistema web de gestão de formaturas** com 2 ambientes (admin backoffice + portal formando)
- Admin é Livewire 3 + Inspinia + Tailwind; Portal é SPA React 19 + TanStack Router/Query + Zustand
- MVP v1 cobre: adesão, financeiro, convites, extras, perfil (sem seating, sem enquetes)
- Backend: monólito modular Laravel 13; API-first via `/api/v1`
- Autenticação separada: Sanctum para Portal, guard admin para Backoffice
- Valor primário: **formando adere sem fricção** (inclui adesão pública via código da turma, SPEC-010)
- Valor para empresa: **gestão completa ciclo de vida da formatura**
- Integração principal: Gateway Itaú (PIX, boleto, cartão)
- Público-alvo: formandos universitários + empresas organizadoras + convidados (RSVP)
- Objetivo de v1: entrega em ~6 meses via 13 sprints (ver `12-VERTICAL-SLICE-DELIVERY-PLAN`)

## 4. Marcações explícitas

| Item                               | Classificação  | Observação                                                         |
| ---------------------------------- | -------------- | ------------------------------------------------------------------ |
| Seating (mapa de mesas)            | ❌ Obsoleto v1 | Arquivado em `_archive/future/SPEC-006-*`; BACKLOG_FUTURO prevê v2 |
| Enquetes                           | ❌ Obsoleto v1 | Arquivado em `_archive/future/SPEC-008-*`                          |
| Adesão pública via código de turma | ⏳ Pendente    | SPEC-010 em plan-ready; ativa em F2                                |
| Multi-formando (pais de gêmeos)    | ⏳ Pendente    | SPEC-F-003 em stub                                                 |
| Integração Gateway Itaú            | 🟡 Inferido    | SPEC-F-009 em stub; requer Q-005 (sandbox access)                  |
| Termos versionados                 | ⏳ Pendente    | SPEC-F-007 em stub                                                 |

## 5. Delta desde a fonte

### 5.1 Em `product/PROJECT_BRIEF.md`

<!-- gaps identificados pelo Agente 1 -->

- Ainda menciona "9 SPECs" — atualizado para 9 feature + 11 Foundation (total 20)
- Não reflete decisão de arquivar seating/enquetes — ver 16-OPEN-QUESTIONS Q-004
- Lista React 18 em alguns lugares — real é React 19

### 5.2 Em `prd/PRD_v4.md` §1

- Seção "Fora do core inicial" ainda não lista seating/enquetes explicitamente — gap G-001

## 6. Docs relacionados

- [00-INDEX.md](00-INDEX.md) — hub mestre
- [06-UNIFIED-PRD.md](06-UNIFIED-PRD.md) — PRD detalhado
- [07-UNIFIED-SRS.md](07-UNIFIED-SRS.md) — requisitos
- [12-VERTICAL-SLICE-DELIVERY-PLAN.md](12-VERTICAL-SLICE-DELIVERY-PLAN.md) — roadmap de entrega
- [roadmap/BACKLOG_FUTURO.md](roadmap/BACKLOG_FUTURO.md) — seating/enquetes v2
- [ADR-0001: API-first versionamento v1](architecture/adrs/ADR-0001-api-first-versionamento-v1.md)
- [ADR-0002: Monólito modular](architecture/adrs/ADR-0002-monolito-modular.md)
- [SPEC-010: Adesão pública via código da turma](features/SPEC-010-adesao-publica-codigo-contrato.md)

---

_Thin index mantido em lockstep com fontes. Última revisão: 2026-04-27._
```

**Repetir estrutura para 06, 07, 08, 09, 10, 11, 13, 14, 15** com conteúdo específico de cada.

## Task 4.12: Validação + commit Dia 4

- [ ] **Step 1: Validar frontmatter de todos os 10 thin indexes**

```bash
for f in docs/05-UNIFIED-PROJECT-BRIEF.md docs/06-UNIFIED-PRD.md docs/07-UNIFIED-SRS.md docs/08-UNIFIED-SAD-ARC42.md docs/09-ADR-INDEX.md docs/10-API-BACKEND-INDEX.md docs/11-FRONTEND-REACT-INDEX.md docs/13-QA-AND-ACCEPTANCE-STRATEGY.md docs/14-DEV-SETUP-AND-WORKFLOW.md docs/15-RUNBOOK.md; do
  echo "=== $f ==="
  node -e "..." # validação frontmatter
done
```

Expected: `OK` para os 10.

- [ ] **Step 2: Validar links**

```bash
for f in docs/05-*.md docs/06-*.md ... docs/15-*.md; do
  npx markdown-link-check "$f" --config .mlc-config.json 2>&1 | tail -5
done
```

⚠️ **Aceitável:** falhas para `00-INDEX.md` (será criado Dia 5).

- [ ] **Step 3: Prettier em lote**

```bash
npx prettier --write 'docs/0[5-9]-*.md' 'docs/1[013-5]-*.md'
```

- [ ] **Step 4: Commit**

```bash
git add docs/05-UNIFIED-PROJECT-BRIEF.md docs/06-UNIFIED-PRD.md docs/07-UNIFIED-SRS.md docs/08-UNIFIED-SAD-ARC42.md docs/09-ADR-INDEX.md docs/10-API-BACKEND-INDEX.md docs/11-FRONTEND-REACT-INDEX.md docs/13-QA-AND-ACCEPTANCE-STRATEGY.md docs/14-DEV-SETUP-AND-WORKFLOW.md docs/15-RUNBOOK.md

git commit -m "$(cat <<'EOF'
docs(docs): thin indexes 05-11, 13-15

Dia 4 da reconstrução documental v1.0.0. Entrega 10 thin indexes
(~3000 linhas totais) apontando para fontes de verdade existentes:

- 05-UNIFIED-PROJECT-BRIEF  → product/PROJECT_BRIEF + product/README
- 06-UNIFIED-PRD            → prd/PRD_v4 + product/PRD_EXPANDED
- 07-UNIFIED-SRS            → product/SRS + prd/REGRAS_NEGOCIO + SEGURANCA + PERFORMANCE
- 08-UNIFIED-SAD-ARC42      → architecture/SAD-arc42 (arc42 completo)
- 09-ADR-INDEX              → architecture/adrs/ (14 ADRs)
- 10-API-BACKEND-INDEX      → api/ + modules/ + PLANEJAMENTO_BACKEND_APIV1
- 11-FRONTEND-REACT-INDEX   → frontend/00-14 + PLANEJAMENTO_FRONTEND_REACT
- 13-QA-AND-ACCEPTANCE      → qa/ (5 docs)
- 14-DEV-SETUP-AND-WORKFLOW → devops/dev-setup + conventions + ci-cd + tools + standards
- 15-RUNBOOK                → devops/runbook-deploy + runbook-operations + monitoring + security + infra

Cada thin index inclui: propósito, fontes primárias com seções,
5-15 bullets executivos, marcações Confirmado/Inferido/Pendente/
Obsoleto, delta (erratas/gaps), docs relacionados. Zero re-escrita
de conteúdo; fontes legadas preservadas.

Ref: docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md §7
EOF
)"
```

- [ ] **Step 5: Git tag**

```bash
git tag -a docs-reconstruction-day-4 -m "Dia 4 reconstrução: 10 thin indexes"
```

### ✅ Checkpoint Dia 4

Aguardar aprovação antes de Dia 5.

---

# 📅 Dia 5 (ter 2026-04-28) — Hub + Docsify + Validação

**Meta do dia:** `00-INDEX.md` + `_sidebar.md` + `_navbar.md` + `README.md` stub + snapshot legado + validação + 2 git tags + commit final.

## Task 5.1: Criar snapshot do hub legado

- [ ] **Step 1: Snapshot do README.md atual para \_archive/legacy-hub/**

```bash
cp docs/README.md docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md
```

- [ ] **Step 2: Atualizar frontmatter do snapshot para archived**

Editar `docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md`:

```yaml
---
title: docs/README.md (legado) — snapshot v2.0.0
version: 2.0.0
date: 2026-04-19
status: archived
sprint_baseline: pre-F1
owner_role: architect
last_reviewed: 2026-04-28
review_cadence: never
supersedes: null
superseded_by: docs/00-INDEX.md
related_adrs: []
change_during_sprint: false
---
```

Adicionar no início do body:

```markdown
> **⚠️ Este é um snapshot histórico.** O hub documental foi substituído por [`docs/00-INDEX.md`](../../00-INDEX.md) em 2026-04-28 pela reconstrução documental v1.0.0. Preservado aqui para rastreabilidade.
>
> **Sucessor:** [`docs/00-INDEX.md`](../../00-INDEX.md)
> **Motivo:** Reconstrução documental v1.0.0 (ver design [`2026-04-20-documentacao-unificada-governanca-design.md`](../../superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md))
```

## Task 5.2: Criar 00-INDEX.md (hub mestre)

**Files:**

- Create: `docs/00-INDEX.md`

- [ ] **Step 1: Criar com frontmatter**

```yaml
---
title: Portal ArtFinal v2 — Hub da Documentação (00-INDEX)
version: 1.0.0
date: 2026-04-28
status: active
sprint_baseline: null
owner_role: architect
last_reviewed: 2026-04-28
review_cadence: pre-sprint
supersedes: docs/README.md (v2.0.0)
superseded_by: null
related_adrs: []
change_during_sprint: false
---
```

- [ ] **Step 2: Escrever seção de entrada**

Conteúdo:

````markdown
# Portal ArtFinal v2 — Hub da Documentação

> **Plataforma de Gestão de Formaturas.** Documentação única, versionada junto ao código, navegável via Docsify.
>
> **Este é o hub.** Entre aqui, encontre seu caminho por persona.

## Arquitetura documental (visão macro)

​```mermaid
graph TD
HUB([00-INDEX<br/>Hub Mestre])

    subgraph "Processo"
        P1[01-GOVERNANCE]
        P2[02-PRODUCT]
        P3[03-SCRUM]
        P4[04-SQUAD]
    end

    subgraph "Visão & Requisitos"
        V1[05-BRIEF]
        V2[06-PRD]
        V3[07-SRS]
    end

    subgraph "Arquitetura"
        A1[08-SAD-ARC42]
        A2[09-ADR-INDEX]
    end

    subgraph "Implementação"
        I1[10-API-BACKEND]
        I2[11-FRONTEND]
    end

    subgraph "Entrega"
        D1[12-VERTICAL-SLICE]
        D2[13-QA]
    end

    subgraph "Operação"
        O1[14-DEV-SETUP]
        O2[15-RUNBOOK]
    end

    subgraph "Meta"
        M1[16-OPEN-QUESTIONS]
    end

    HUB --> P1 & P2 & P3 & P4
    HUB --> V1 & V2 & V3
    HUB --> A1 & A2
    HUB --> I1 & I2
    HUB --> D1 & D2
    HUB --> O1 & O2
    HUB --> M1

​```
````

- [ ] **Step 3: Escrever Mapa de Documentos (tabela completa)**

Conteúdo:

```markdown
## Mapa de Documentos (17 docs numerados + audit report)

| #      | Documento                                                          | Tipo       | Owner           | Status    | Audiência       |
| ------ | ------------------------------------------------------------------ | ---------- | --------------- | --------- | --------------- |
| **00** | [INDEX](00-INDEX.md)                                               | hub        | architect       | ✅ active | Todos           |
| **01** | [DOCUMENTATION-GOVERNANCE](01-DOCUMENTATION-GOVERNANCE.md)         | governança | architect       | ✅ active | Contribuidores  |
| **02** | [PRODUCT-OPERATING-MODEL](02-PRODUCT-OPERATING-MODEL.md)           | processo   | product-manager | ✅ active | PM role         |
| **03** | [SCRUM-OPERATING-MODEL](03-SCRUM-OPERATING-MODEL.md)               | processo   | scrum-master    | ✅ active | SM role         |
| **04** | [SQUAD-TOPOLOGY](04-SQUAD-TOPOLOGY.md)                             | processo   | architect       | ✅ active | Orquestrador    |
| **05** | [UNIFIED-PROJECT-BRIEF](05-UNIFIED-PROJECT-BRIEF.md)               | thin index | product-manager | ✅ active | Stakeholders    |
| **06** | [UNIFIED-PRD](06-UNIFIED-PRD.md)                                   | thin index | product-manager | ✅ active | PM, PO, dev     |
| **07** | [UNIFIED-SRS](07-UNIFIED-SRS.md)                                   | thin index | architect       | ✅ active | Arquitetos, QA  |
| **08** | [UNIFIED-SAD-ARC42](08-UNIFIED-SAD-ARC42.md)                       | thin index | architect       | ✅ active | Arquitetos      |
| **09** | [ADR-INDEX](09-ADR-INDEX.md)                                       | thin index | architect       | ✅ active | Arquitetos      |
| **10** | [API-BACKEND-INDEX](10-API-BACKEND-INDEX.md)                       | thin index | developer (BE)  | ✅ active | BE dev, FE dev  |
| **11** | [FRONTEND-REACT-INDEX](11-FRONTEND-REACT-INDEX.md)                 | thin index | developer (FE)  | ✅ active | FE dev          |
| **12** | [VERTICAL-SLICE-DELIVERY-PLAN](12-VERTICAL-SLICE-DELIVERY-PLAN.md) | entrega    | scrum-master    | ✅ active | Todos           |
| **13** | [QA-AND-ACCEPTANCE-STRATEGY](13-QA-AND-ACCEPTANCE-STRATEGY.md)     | thin index | qa              | ✅ active | QA, dev líderes |
| **14** | [DEV-SETUP-AND-WORKFLOW](14-DEV-SETUP-AND-WORKFLOW.md)             | thin index | developer       | ✅ active | Dev             |
| **15** | [RUNBOOK](15-RUNBOOK.md)                                           | thin index | developer       | ✅ active | On-call, DevOps |
| **16** | [OPEN-QUESTIONS-AND-BLOCKERS](16-OPEN-QUESTIONS-AND-BLOCKERS.md)   | meta       | product-manager | ✅ active | Todos           |
| —      | [Audit Report 2026-04-20](reports/2026-04-20-AUDIT-REPORT.md)      | report     | architect       | ✅ active | Histórico       |
```

- [ ] **Step 4: Escrever Rotas de Leitura por Persona (decisão T.1 — 5 personas)**

Conteúdo:

```markdown
## Rotas de Leitura por Persona

### 🧑‍💻 Persona 1: Você (como developer) retomando projeto

​`
CLAUDE.md → 00-INDEX → 14-DEV-SETUP → 01-GOVERNANCE →
features/SPEC-<atual> → 10-API-BACKEND ou 11-FRONTEND →
04-SQUAD → devops/dev-setup
​`

### 🎯 Persona 2: Você (como Product Manager) refinando backlog

​`
02-PRODUCT → 06-UNIFIED-PRD → 16-OPEN-QUESTIONS →
12-VERTICAL-SLICE → roadmap/BACKLOG_FUTURO →
features/README → SPEC específico
​`

### 📋 Persona 3: Você (como Scrum Master) planejando sprint

​`
03-SCRUM → 12-VERTICAL-SLICE → squads/SQUAD-F<N> →
stories/sprint-plan-f<N> → 04-SQUAD → 16-OPEN-QUESTIONS
​`

### 🤖 Persona 4: Agente Claude Code em nova sessão

​`
CLAUDE.md (auto-load) → 00-INDEX → 01-GOVERNANCE →
task-específica → 04-SQUAD (qual agente acionar) →
10-API-BACKEND ou 11-FRONTEND
​`

### 👥 Persona 5: Cliente / stakeholder externo

​`
05-UNIFIED-PROJECT-BRIEF → 12-VERTICAL-SLICE §4
(roadmap) → 16-OPEN-QUESTIONS (pendências)
​`
```

- [ ] **Step 5: Escrever Seção "Convenção de idioma" + "Princípios não negociáveis" + "Servir localmente"**

Adaptar do `README.md` v2.0.0 original, corrigindo links para apontar para 00-INDEX em vez de README.md.

- [ ] **Step 6: Escrever seção final "Como contribuir"**

Conteúdo:

```markdown
## Como contribuir

1. Ler [01-DOCUMENTATION-GOVERNANCE](01-DOCUMENTATION-GOVERNANCE.md)
2. Verificar [16-OPEN-QUESTIONS](16-OPEN-QUESTIONS-AND-BLOCKERS.md) antes de abrir nova dúvida
3. Seguir [02-PRODUCT-OPERATING-MODEL](02-PRODUCT-OPERATING-MODEL.md) §5 (DoR) para nova story
4. Seguir [03-SCRUM-OPERATING-MODEL](03-SCRUM-OPERATING-MODEL.md) §6 (DoD) para fechar story
5. Mudanças documentais seguem [01-GOVERNANCE §7](01-DOCUMENTATION-GOVERNANCE.md#7-fluxo-de-mudança-documental)

---

_Reconstrução documental v1.0.0 em 2026-04-28 — substitui [hub legado v2.0.0](_archive/legacy-hub/README-v2.0.0-2026-04-19.md)._
```

## Task 5.3: Atualizar README.md como redirect

- [ ] **Step 1: Substituir conteúdo de docs/README.md por stub**

Conteúdo completo novo:

```markdown
---
title: docs/README — redirect para hub
version: 1.0.1
date: 2026-04-28
status: active
sprint_baseline: null
owner_role: architect
last_reviewed: 2026-04-28
review_cadence: never
supersedes: null
superseded_by: null
change_during_sprint: false
---

# Portal ArtFinal v2 — Documentação

**Esta página foi movida.** O novo hub é [**`00-INDEX.md`**](00-INDEX.md).

Para histórico do hub anterior (v2.0.0, 2026-04-19), ver [`_archive/legacy-hub/README-v2.0.0-2026-04-19.md`](_archive/legacy-hub/README-v2.0.0-2026-04-19.md).
```

## Task 5.4: Atualizar Docsify navigation

- [ ] **Step 1: Ler \_sidebar.md atual**

```bash
cat docs/_sidebar.md
```

- [ ] **Step 2: Substituir \_sidebar.md**

Conteúdo completo novo:

```markdown
<!-- _sidebar.md -->

- **🏠 Hub**
    - [00 · Index](00-INDEX.md)

- **📐 Processo**
    - [01 · Governance](01-DOCUMENTATION-GOVERNANCE.md)
    - [02 · Product OM](02-PRODUCT-OPERATING-MODEL.md)
    - [03 · Scrum OM](03-SCRUM-OPERATING-MODEL.md)
    - [04 · Squad Topology](04-SQUAD-TOPOLOGY.md)

- **🎯 Visão & Requisitos**
    - [05 · Project Brief](05-UNIFIED-PROJECT-BRIEF.md)
    - [06 · PRD](06-UNIFIED-PRD.md)
    - [07 · SRS](07-UNIFIED-SRS.md)

- **🏛️ Arquitetura**
    - [08 · SAD-arc42](08-UNIFIED-SAD-ARC42.md)
    - [09 · ADR Index](09-ADR-INDEX.md)

- **🔧 Implementação**
    - [10 · API Backend](10-API-BACKEND-INDEX.md)
    - [11 · Frontend React](11-FRONTEND-REACT-INDEX.md)

- **🚀 Entrega**
    - [12 · Vertical Slice](12-VERTICAL-SLICE-DELIVERY-PLAN.md)
    - [13 · QA Strategy](13-QA-AND-ACCEPTANCE-STRATEGY.md)

- **⚙️ Operação**
    - [14 · Dev Setup](14-DEV-SETUP-AND-WORKFLOW.md)
    - [15 · Runbook](15-RUNBOOK.md)

- **❓ Meta**
    - [16 · Open Questions](16-OPEN-QUESTIONS-AND-BLOCKERS.md)

- **📚 Fontes legadas (preservadas)**
    - [Features/SPECs](features/README.md)
    - [PRD v4](prd/README.md)
    - [Architecture](architecture/index.md)
    - [Frontend docs](frontend/00-README-INDEX.md)
    - [QA docs](qa/index.md)
    - [DevOps docs](devops/index.md)
    - [Modules](modules/README.md)
    - [Squads](squads/README.md)
    - [Stories](stories/sprint-plan-f1.md)
    - [Roadmap (backlog futuro)](roadmap/BACKLOG_FUTURO.md)

- **📦 Arquivo**
    - [\_archive/](__archive/README.md)
    - [Hub legado v2.0.0](_archive/legacy-hub/README-v2.0.0-2026-04-19.md)
```

- [ ] **Step 3: Atualizar \_navbar.md**

Conteúdo:

```markdown
<!-- _navbar.md -->

- 🏠 [Hub](00-INDEX.md)
- 📐 [Processo](01-DOCUMENTATION-GOVERNANCE.md)
- 🎯 [PRD](06-UNIFIED-PRD.md)
- 🏛️ [SAD](08-UNIFIED-SAD-ARC42.md)
- 🔧 [API](10-API-BACKEND-INDEX.md)
- 🔧 [Frontend](11-FRONTEND-REACT-INDEX.md)
- 🚀 [Sprint Plan](12-VERTICAL-SLICE-DELIVERY-PLAN.md)
- ❓ [Pendências](16-OPEN-QUESTIONS-AND-BLOCKERS.md)
```

## Task 5.5: Validação completa

- [ ] **Step 1: Validar frontmatter de TODOS os novos docs**

```bash
for f in docs/00-INDEX.md docs/01-DOCUMENTATION-GOVERNANCE.md docs/02-PRODUCT-OPERATING-MODEL.md docs/03-SCRUM-OPERATING-MODEL.md docs/04-SQUAD-TOPOLOGY.md docs/05-UNIFIED-PROJECT-BRIEF.md docs/06-UNIFIED-PRD.md docs/07-UNIFIED-SRS.md docs/08-UNIFIED-SAD-ARC42.md docs/09-ADR-INDEX.md docs/10-API-BACKEND-INDEX.md docs/11-FRONTEND-REACT-INDEX.md docs/12-VERTICAL-SLICE-DELIVERY-PLAN.md docs/13-QA-AND-ACCEPTANCE-STRATEGY.md docs/14-DEV-SETUP-AND-WORKFLOW.md docs/15-RUNBOOK.md docs/16-OPEN-QUESTIONS-AND-BLOCKERS.md docs/reports/2026-04-20-AUDIT-REPORT.md docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md docs/README.md; do
  echo "=== $f ==="
  node -e "..." # validação
done
```

Expected: `OK` para todos (20 arquivos).

- [ ] **Step 2: Validar TODOS os links — agora sem ignorar nada**

```bash
for f in docs/[0-9][0-9]-*.md; do
  echo "=== $f ==="
  npx markdown-link-check "$f" --config .mlc-config.json 2>&1 | tail -5
done
```

Expected: **TODOS OS LINKS VÁLIDOS** (0 erros). Se falha, corrigir antes de seguir.

- [ ] **Step 3: Validar Docsify render local**

```bash
# Terminal 1
npx docsify-cli serve docs --port 3000 &
SERVER_PID=$!
sleep 3

# Testar render
curl -s http://localhost:3000/ | grep -q "00-INDEX" && echo "OK hub" || echo "FAIL hub"
curl -s http://localhost:3000/#/01-DOCUMENTATION-GOVERNANCE | head -5

# Cleanup
kill $SERVER_PID
```

Expected: `OK hub`.

- [ ] **Step 4: Visual smoke test no browser**

```bash
npx docsify-cli serve docs --port 3000 &
sleep 2
open http://localhost:3000
```

Checklist manual:

- [ ] Hub renderiza corretamente
- [ ] Sidebar mostra 00-16 + seções
- [ ] Navbar mostra quick-access
- [ ] Links internos funcionam (clicar em 2-3 docs aleatórios)
- [ ] Mermaid diagram do hub renderiza
- [ ] Dark mode funciona (se configurado)

- [ ] **Step 5: Prettier final em lote**

```bash
npx prettier --write 'docs/*.md' 'docs/reports/*.md' 'docs/_archive/legacy-hub/*.md'
```

## Task 5.6: Git tags + commit final

- [ ] **Step 1: Stage tudo do Dia 5**

```bash
git add docs/00-INDEX.md docs/README.md docs/_sidebar.md docs/_navbar.md docs/_archive/legacy-hub/README-v2.0.0-2026-04-19.md
```

- [ ] **Step 2: Commit**

```bash
git commit -m "$(cat <<'EOF'
docs(docs): 00-INDEX + Docsify nav + validação

Dia 5 (final) da reconstrução documental v1.0.0. Entrega:
- 00-INDEX.md — novo hub mestre com mapa macro em Mermaid,
  tabela de 17 docs, 5 rotas de leitura por persona
  (T.1), princípios não negociáveis, servir localmente,
  como contribuir
- README.md — stub redirect 3 linhas apontando para 00-INDEX
- _sidebar.md — nova navegação Docsify em 8 grupos
  (Hub · Processo · Visão · Arquitetura · Implementação ·
  Entrega · Operação · Meta · Legado · Arquivo)
- _navbar.md — quick-access dos 8 docs mais consultados
- _archive/legacy-hub/README-v2.0.0-2026-04-19.md —
  snapshot do hub legado com frontmatter archived e aviso
- Validação completa: frontmatter OK em 20 arquivos; todos
  os links internos válidos; Docsify render OK

Reconstrução v1.0.0 completa. Sprint F1.1 pode arrancar
qua 2026-04-29. Tags criadas: docs-reconstruction-v1.0.0,
sprint-1.1-baseline.

Ref: docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md
EOF
)"
```

- [ ] **Step 3: Criar tags finais**

```bash
git tag -a docs-reconstruction-v1.0.0 -m "Reconstrução documental v1.0.0 completa (17 docs novos + audit + snapshot)"
git tag -a sprint-1.1-baseline -m "Baseline Sprint F1.1 — arranque qua 2026-04-29"
```

- [ ] **Step 4: Push tudo**

```bash
git push origin feature/planejamento-backend-api-v1
git push origin docs-reconstruction-day-1 docs-reconstruction-day-2 docs-reconstruction-day-3 docs-reconstruction-day-4 docs-reconstruction-v1.0.0 sprint-1.1-baseline
```

## Task 5.7: Criar PR único (decisão CC.1)

- [ ] **Step 1: Abrir PR**

```bash
gh pr create --title "docs: reconstrução documental v1.0.0 — hub 00-INDEX + governance + 16 docs unificados" --body "$(cat <<'EOF'
## Summary

Reconstrução documental v1.0.0 em 5 dias (qua 2026-04-22 → ter 2026-04-28). Entrega 17 docs novos em `docs/` numerados 00-16 + audit report, preservando 100% da estrutura legada. Prepara Sprint F1.1 para arrancar qua 2026-04-29.

- Novo hub `00-INDEX` substitui `README.md` (vira redirect)
- 6 docs novos com conteúdo próprio: governance, product/scrum operating models, squad topology, vertical slice plan, open questions
- 10 thin indexes (~200-500 linhas) apontando para fontes legadas preservadas
- Docsify nav atualizada (`_sidebar.md`, `_navbar.md`)
- Snapshot do hub v2.0.0 preservado em `_archive/legacy-hub/`
- 2 git tags: `docs-reconstruction-v1.0.0`, `sprint-1.1-baseline`

## Test plan

- [x] Frontmatter válido em 20 arquivos
- [x] Todos os links internos válidos (markdown-link-check)
- [x] Docsify render OK localmente
- [x] Prettier limpo em todos os .md novos
- [x] Tags git criadas
- [ ] Cliente notificado sobre adiamento Sprint F1 para qua 2026-04-29
- [ ] Equipe alinhada com novo hub (solo; só você)

## Ref

- Design spec: [docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md](docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md)
- Plan: [docs/superpowers/plans/2026-04-20-reconstrucao-documental-plan.md](docs/superpowers/plans/2026-04-20-reconstrucao-documental-plan.md)
EOF
)"
```

Expected: PR criada e URL retornada.

### ✅ Checkpoint Dia 5 — Reconstrução COMPLETA

Aguardar merge/aprovação final do usuário.

**Próximo passo após merge:**

- Sprint F1.1 arranca qua 2026-04-29 usando o novo aparato documental
- Scrum Master invoca Planning ritual
- Git tag `sprint-1.1-baseline` já criada
- Primeira história: STORY-001 (instalar pacotes Composer base)

---

## Critérios de sucesso globais

Reconstrução v1.0.0 só está completa quando:

- [x] 17 arquivos novos criados em `docs/` (00-16 + audit report)
- [x] Todos com frontmatter válido seguindo padrão §4.3 do spec
- [x] 10 thin indexes (05-11, 13-15) apontam para fontes existentes via caminho + seção
- [x] 6 gap docs (01, 02, 03, 04, 12, 16) têm conteúdo próprio substantivo
- [x] `docs/README.md` é stub redirect para `00-INDEX.md`
- [x] `docs/_sidebar.md` e `docs/_navbar.md` renderizam a numeração 00-16 no Docsify
- [x] Nenhum link quebrado (validado via markdown-link-check)
- [x] Snapshot do hub legado preservado em `_archive/legacy-hub/`
- [x] Git tags criadas: `docs-reconstruction-v1.0.0` e `sprint-1.1-baseline`
- [x] PR único aberto consolidando todas as mudanças
- [x] Documentação continua navegável via Docsify (`npx docsify-cli serve docs`)
- [x] Sprint F1.1 pode iniciar qua 2026-04-29 com baseline congelada

---

## Self-review do plano

### Spec coverage

- [x] Arquitetura física (spec §3) → Task 0, 5.1-5.4
- [x] Governance model (spec §4) → Task 1.3 completo com A.2, B.2, C.1, D.2, E.2, F.1
- [x] Operating model PM (spec §5.1) → Task 2.1 com 6 gatilhos, DoR, change control
- [x] Operating model SM (spec §5.2) → Task 2.2 com 5 rituais, A.2 enforcement, DoD
- [x] Squad topology (spec §5.3) → Task 2.3 com 4 lanes, H.1 contrato-first
- [x] Vertical slice plan (spec §6) → Task 3.1 com roadmap F1-F8, O.1, P.1, Q.1
- [x] 16 docs content (spec §7) → Tasks 4.2-4.11 + 5.2 com template S.1, T.1, V.1
- [x] Cronograma (spec §8) → Todo o plano orquestrado Dia 1-5
- [x] Audit report (spec §9) → Task 1.1-1.2
- [x] 6 defaults aprovados X.custom (22/04), Y.1, Z.1, AA.1, BB.1, CC.1 → Tasks 4.1, 5.6, 5.7

### Ambiguidades resolvidas

- Conteúdo de cada thin index é pattern-driven + output de Explore agent paralelo
- Tasks 4.2-4.11 reutilizam o mesmo template de 8 steps (doc 05 expandido; resto igual)

### Gaps conhecidos aceitos

- Conteúdo completo dos docs 06-15 thin indexes não está pré-escrito (vem do Explore agent no Dia 4)
- Audit report content não pré-escrito (vem de Task 1.1 outputs)
- Estes são **gaps intencionais** — o plano estabelece estrutura, agentes produzem conteúdo

---

## Referências

- Spec: [`docs/superpowers/specs/2026-04-20-documentacao-unificada-governanca-design.md`](../specs/2026-04-20-documentacao-unificada-governanca-design.md)
- Plano SPEC-010 prévio (template): [`docs/superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md`](2026-04-19-adesao-publica-codigo-contrato-plan.md)
- Design SPEC-010 prévio: [`docs/superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md`](../specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md)
- CLAUDE.md (instruções mestras)

---

_Plano gerado via `superpowers:writing-plans` em 2026-04-20. Target execution: qua 2026-04-22 → ter 2026-04-28._
