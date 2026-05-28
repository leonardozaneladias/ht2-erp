---
title: SPEC-F-007 — Termos Versionados
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-007
fase: foundation
story_points: 5
depends_on: [SPEC-F-001]
unlocks: [SPEC-002, SPEC-010, SPEC-013]
---

# SPEC-F-007 — Termos Versionados

> **Fundacional.** Recupera do PRD v3.1.0 §10 o versionamento de termos de adesão, assinatura digital e consolidação em PDF. Hoje SPEC-002 tem apenas `aceitou_termos: boolean` + `termos_versao: string`, sem modelar o termo em si.

---

## 1. Conceitos

### 1.1 Termo do Contrato

O Contrato (SPEC-F-001) tem **N versões de termos** ao longo do tempo. Cada adesão "congela" a versão aceita no momento do commit — snapshot imutável.

### 1.2 Estrutura de um termo

- **Cabeçalho fixo**: dados da organizadora, da instituição, data
- **Corpo**: cláusulas (HTML ou Markdown)
- **Merge fields**: `{{formando_nome}}`, `{{valor_total}}`, `{{qtd_parcelas}}`, etc.
- **Rodapé**: assinaturas (eletrônica — registro de IP, timestamp, CPF, versão)

---

## 2. Modelo de dados (preview)

### 2.1 `termos_contrato` — nova tabela

| Campo                 | Tipo                                                   |
| --------------------- | ------------------------------------------------------ |
| `id`, `ulid`          |                                                        |
| `contrato_id`         | FK                                                     |
| `versao`              | VARCHAR(20) (ex: `v2026-01`)                           |
| `template_corpo`      | TEXT (HTML ou MD com merge fields)                     |
| `status`              | enum: `rascunho`, `publicada`, `arquivada`             |
| `publicada_em`        | DATETIME                                               |
| `hash_conteudo`       | VARCHAR(64) — SHA256 do template para detectar mudança |
| `created_by_admin_id` | FK admin_users                                         |

Constraint: apenas 1 versão `publicada` por contrato por vez. Publicar nova versão arquiva a anterior.

### 2.2 `aceites_termo` — nova tabela

Log append-only de cada aceite:

| Campo                  | Tipo                                     |
| ---------------------- | ---------------------------------------- |
| `id`, `ulid`           |                                          |
| `adesao_id`            | FK                                       |
| `termo_contrato_id`    | FK (versão específica aceita)            |
| `aceito_em`            | DATETIME                                 |
| `ip`                   | VARCHAR(45)                              |
| `user_agent`           | VARCHAR(500)                             |
| `conteudo_renderizado` | TEXT (corpo com merge fields resolvidos) |
| `hash_renderizado`     | VARCHAR(64)                              |
| `pdf_path`             | VARCHAR(500) nullable (S3 path)          |

---

## 3. Fluxo

### 3.1 Criar nova versão (admin)

```
Admin edita template no backoffice → preview merge fields → publicar
  → status anterior = 'arquivada'
  → status nova = 'publicada'
  → hash_conteudo = sha256(template_corpo)
```

### 3.2 Aceite pelo formando (wizard adesão, etapa 5)

```
Etapa 5 do wizard carrega termo vigente:
  GET /api/v1/contratos/{ulid}/termo-vigente

Frontend renderiza corpo com merge fields substituídos
  (client-side com dados do wizard-store)

Formando marca checkbox + clica "Aceitar"
  → frontend chama POST /api/v1/adesoes/commit com
     termos_versao, aceitou_termos=true

Backend (CommitAdesaoAction):
  1. Valida termo_vigente.ulid match com termos_versao do payload
  2. Renderiza conteúdo final (server-side, autoritativo)
  3. Cria aceites_termo
  4. Enfileira job ConsolidarTermoPdfJob (gera PDF com assinatura eletrônica)
  5. Retorna adesão criada
```

### 3.3 Consolidação em PDF

- Job assíncrono (fila `pdf`)
- Usa `barryvdh/laravel-dompdf` (já em CLAUDE.md §13)
- Template Blade dedicado com CSS print
- Salva em S3 path: `contratos/{contrato_ulid}/termos/{adesao_ulid}-{termo_versao}.pdf`
- Link no extrato do formando e email de confirmação

---

## 4. Pontos a expandir na versão `draft`

- [ ] Merge fields: lista canônica e escape HTML (evitar XSS se admin colocar `<script>`)
- [ ] Assinatura eletrônica: basta IP+timestamp+CPF, ou precisa integração com ICP-Brasil/DocuSign?
- [ ] Política de retenção: PDFs ficam indefinidamente no S3?
- [ ] Reemissão de PDF (formando perde, pede novamente): mesma URL ou gera novo path?
- [ ] Alteração retroativa de termo publicado: bloqueada (append-only); admin só publica nova versão
- [ ] Renderização bilingue (futuro): suportar múltiplos idiomas por termo
- [ ] Testes: aceite com termo_versao desatualizado (spec anterior) → 409 `TermoVersaoDesatualizada`

---

## 5. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §10 — conceito original
- [`SPEC-F-001`](SPEC-F-001-contrato-e-turma.md) — termo pertence ao Contrato
- [`SPEC-002`](../SPEC-002-wizard-adesao.md) — consumidor (etapa 5)
- [`SPEC-010`](../SPEC-010-adesao-publica-codigo-contrato.md) — consumidor (etapa de aceite no wizard público)

---

_**Estado:** `stub`._
