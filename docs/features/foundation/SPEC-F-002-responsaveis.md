---
title: SPEC-F-002 — Responsáveis (cadastro + financeiro)
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-002
fase: foundation
story_points: 3
depends_on: [SPEC-F-001]
unlocks: [SPEC-F-003, SPEC-002, SPEC-009, SPEC-010]
---

# SPEC-F-002 — Responsáveis (cadastro + financeiro)

> **Fundacional.** Recupera a distinção entre "responsável de cadastro" e "responsável financeiro" do PRD v3.1.0 §4 e §11. Governa quais campos aparecem no wizard de adesão conforme flags do Contrato (SPEC-F-001). Sem esta spec, SPEC-002 wizard não tem lógica para o checkbox "Sou o próprio responsável financeiro".

---

## 1. Conceitos

### 1.1 Três papéis distintos

| Papel                       | Definição                                                             | Captura                                                      |
| --------------------------- | --------------------------------------------------------------------- | ------------------------------------------------------------ |
| **Formando**                | Pessoa que vai formar                                                 | `formandos.cpf`, `formandos.nome`                            |
| **Responsável de cadastro** | Quem assina o contrato em nome do formando (tipicamente para menores) | `adesao.responsavel_cadastro_*` ou flag "próprio formando"   |
| **Responsável financeiro**  | Quem recebe cobranças e pagamentos                                    | `adesao.responsavel_financeiro_*` ou flag "próprio formando" |

### 1.2 Flags governantes no Contrato (SPEC-F-001)

- `exige_responsavel_cadastro` — se `true`, wizard obriga dados de responsável de cadastro
- `exige_responsavel_financeiro` — se `true`, wizard obriga dados de responsável financeiro
- `permite_formando_resp_cadastro` — se `true` E formando ≥ 18 anos, pode marcar "sou o próprio responsável de cadastro"
- `permite_formando_resp_financeiro` — idem para financeiro

### 1.3 Regra de maioridade

Calculada via `formandos.data_nascimento` no momento do commit da adesão. Se < 18 anos, flags de permissão são ignoradas — responsável separado sempre obrigatório.

---

## 2. Modelo de dados (preview)

### 2.1 `adesoes` — campos adicionados

Substituindo o campo único `responsavel_*` atual do SPEC-002:

| Campo                                   | Tipo         | Observação                                       |
| --------------------------------------- | ------------ | ------------------------------------------------ |
| `responsavel_cadastro_mesmo_formando`   | BOOLEAN      |                                                  |
| `responsavel_cadastro_nome`             | VARCHAR(150) | nullable                                         |
| `responsavel_cadastro_cpf`              | VARCHAR(14)  | nullable                                         |
| `responsavel_cadastro_email`            | VARCHAR(150) | nullable                                         |
| `responsavel_cadastro_telefone`         | VARCHAR(30)  | nullable                                         |
| `responsavel_cadastro_vinculo`          | VARCHAR(30)  | enum: `pai`, `mae`, `responsavel_legal`, `outro` |
| `responsavel_financeiro_mesmo_formando` | BOOLEAN      |                                                  |
| `responsavel_financeiro_nome`           | VARCHAR(150) | nullable                                         |
| `responsavel_financeiro_cpf`            | VARCHAR(14)  | nullable                                         |
| `responsavel_financeiro_email`          | VARCHAR(150) | nullable                                         |
| `responsavel_financeiro_telefone`       | VARCHAR(30)  | nullable                                         |

Observação: guardar "responsável" diretamente na `adesao` como snapshot é intencional (snapshot imutável no momento do contrato). Edição futura gera audit log via `spatie/activitylog`.

### 2.2 Alternativa considerada (descartada para MVP)

Tabela `responsaveis` separada com FK na adesão. Descartada por:

- Aumenta complexidade de join sem ganho funcional (responsável raramente é reaproveitado entre adesões)
- Snapshot direto preserva estado histórico sem versionamento extra

Revisitar se vier requisito de "reaproveitar responsável entre irmãos" (relacionado a SPEC-F-003 Multi-formando).

---

## 3. Regras de UX (para SPEC-002 consumir)

### 3.1 Fluxo da etapa 2 do wizard

```
SE contrato.exige_responsavel_financeiro = true:
    SE formando ≥ 18 anos E contrato.permite_formando_resp_financeiro = true:
        mostrar checkbox "Sou o próprio responsável financeiro"
        SE marcado:
            responsavel_financeiro_mesmo_formando = true
            pular campos de nome/cpf/email/tel
        SE desmarcado:
            pedir dados do responsável financeiro
    SENÃO:
        pedir dados do responsável financeiro (obrigatório)
SENÃO:
    pular etapa de responsável financeiro completamente

Repetir lógica análoga para responsável de cadastro se exigido.
```

### 3.2 Consolidação visual

Se ambos os responsáveis forem a mesma pessoa (ex.: a mãe é cadastro E financeiro), mostrar UX unificada com "Responsável único" e replicar dados internamente.

---

## 4. Pontos a expandir na versão `draft`

- [ ] FormRequest `StoreAdesaoRequest` com validação condicional (`required_if` aninhado em 2 dimensões)
- [ ] Regra: `responsavel_financeiro_cpf` ≠ `formando.cpf` quando `mesmo_formando = false`
- [ ] Cálculo de idade (Carbon) e testes de edge cases (formando com 18 anos feitos no dia da adesão)
- [ ] DTO `ResponsaveisData` com 2 sub-objetos
- [ ] Policy: quem pode editar responsável após commit? (resposta: ninguém sem aprovação admin — audit log)
- [ ] Testes: matriz 2×2×2 (exige × permite × idade) = 8 cenários mínimos por tipo de responsável

---

## 5. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §4.1 (flags), §11 (regras)
- [`SPEC-F-001`](SPEC-F-001-contrato-e-turma.md) — flags vivem no Contrato
- [`SPEC-002`](../SPEC-002-wizard-adesao.md) — consumidor principal
- [`docs/superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md`](../../superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md) §2.7

---

_**Estado:** `stub`._
