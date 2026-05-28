---
title: SPEC-F-003 — Multi-formando
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-003
fase: foundation
story_points: 5
depends_on: [SPEC-F-002]
unlocks: [SPEC-001, SPEC-002, SPEC-009, SPEC-010]
---

# SPEC-F-003 — Multi-formando

> **Fundacional.** Formaliza `1 PortalUser ↔ N Formandos` (recuperação do PRD v3.1.0 §11). Caso de uso canônico: pais de gêmeos que formam juntos — os pais têm 1 login, ambos os filhos têm cadastro próprio vinculado. Também cobre pais que pagam pelo filho único (conta do pai, formando do filho).
> O PRD v4 §5.1 ER diagram indica `PortalUser >──< Formando` mas não formaliza. Os SPECs 001/002/008/009 assumem `formandos[0]` — gap conhecido (blocker `B-ENQ-05` reconhece).

---

## 1. Casos de uso

| Cenário                                  | Modelagem                                                        |
| ---------------------------------------- | ---------------------------------------------------------------- |
| Formando ≥ 18 paga e loga                | 1 `PortalUser` = 1 `Formando`; vínculo `proprio`                 |
| Pai/mãe paga pelo filho único            | 1 `PortalUser` (pai) → 1 `Formando` (filho); vínculo `pai`/`mae` |
| Pais de gêmeos                           | 1 `PortalUser` → 2 `Formandos`; 2 `Adesões` separadas            |
| Formando ≥ 18 + pais ajudando (2 logins) | **fora MVP** — feature de "compartilhar acesso" posterior        |
| Divórcio/transferência de titularidade   | **fora MVP** — admin faz manualmente                             |

---

## 2. Modelo de dados (preview)

### 2.1 `formandos` — alterações

| Campo                    | Mudança                                                                      |
| ------------------------ | ---------------------------------------------------------------------------- |
| `portal_user_id`         | Remover coluna direta (migra para tabela pivô)                               |
| `primary_portal_user_id` | Adicionar (FK nullable; PortalUser "dono" da conta operadora)                |
| `vinculo`                | Adicionar VARCHAR(30): `proprio`, `pai`, `mae`, `responsavel_legal`, `outro` |

### 2.2 `portal_user_formandos` — nova tabela pivô

| Campo                                | Tipo            | Observação                               |
| ------------------------------------ | --------------- | ---------------------------------------- |
| `portal_user_id`                     | FK portal_users |                                          |
| `formando_id`                        | FK formandos    |                                          |
| `role`                               | VARCHAR(20)     | `principal`, `compartilhado` (futuro v2) |
| `created_at`, `updated_at`           |                 |                                          |
| UNIQUE (portal_user_id, formando_id) |                 |                                          |

MVP: só `principal`. `compartilhado` reservado para v2 (2 logins no mesmo formando).

### 2.3 `adesoes` — reforço

- `portal_user_id` — conta que fez/paga a adesão
- `formando_id` — pessoa que forma (distinta quando `portal_user ≠ formando`)
- Constraint: UNIQUE (`formando_id`, `contrato_id`) onde `status ∉ ('cancelada')` — impede 2 adesões ativas para o mesmo formando no mesmo contrato

---

## 3. Implicações para endpoints

### 3.1 `GET /api/v1/me` (SPEC-001 refactor)

```json
{
    "data": {
        "id": "01J...",
        "nome": "João Silva (pai)",
        "cpf_mascarado": "***.***.***-99",
        "email": "joao@email.com",
        "formandos": [
            {
                "id": "01J...",
                "nome": "Pedro Silva",
                "cpf_mascarado": "***.***.***-11",
                "vinculo": "pai",
                "turma": { "id": "01J...", "nome": "MED-USP-2026" },
                "adesao_ativa": { "id": "01J...", "status": "ativa" }
            },
            {
                "id": "01J...",
                "nome": "Maria Silva",
                "cpf_mascarado": "***.***.***-22",
                "vinculo": "pai",
                "turma": { "id": "01J...", "nome": "ODONTO-USP-2026" },
                "adesao_ativa": null
            }
        ]
    }
}
```

### 3.2 Contexto do formando "ativo"

Quando PortalUser tem múltiplos formandos, muitas telas precisam de um "formando selecionado". Proposta:

- Query param `?formando_ulid=01J...` em endpoints relevantes (extras, convites, pagamentos)
- Default: primeiro formando com adesão ativa; senão primeiro formando
- Frontend usa seletor persistido em localStorage (`selected_formando_ulid`)

### 3.3 Listagens

- `GET /api/v1/me/adesoes` — agrupa por `formando_id`
- `GET /api/v1/me/convites?formando_ulid=X`
- `GET /api/v1/me/extrato?formando_ulid=X`

---

## 4. UX do portal (referência para SPECs)

### 4.1 Portal home (`/portal/home`)

```
"Suas adesões" (lista por formando):
 ┌─ Pedro Silva (seu filho) ───────────
 │  MED-USP-2026 · Ativa · R$ 8.000 pendente
 │  [Ver detalhes] [Pagar parcela]
 └─────────────────────────────────────
 ┌─ Maria Silva (sua filha) ───────────
 │  ODONTO-USP-2026 · Pendente pagamento
 │  [Continuar adesão] [Pagar 1ª parcela]
 └─────────────────────────────────────
 [+ Adicionar outro formando] → /adesao
```

### 4.2 Seletor de contexto (header)

Quando > 1 formando:

```
[Avatar] João Silva (pai) ▾
         ├─ Pedro Silva    ← formando selecionado
         ├─ Maria Silva
         └─ Trocar ▸
```

---

## 5. Pontos a expandir na versão `draft`

- [ ] Migration de `formandos.portal_user_id` para `portal_user_formandos` (data migration com validação)
- [ ] Middleware `portal.formando.context` — resolve `formando_ulid` query param e injeta no request
- [ ] Policy `FormandoAccessPolicy` — impede PortalUser acessar Formando não vinculado
- [ ] Hook React `useFormandoAtivo()` — lê query param + localStorage
- [ ] Validação: impede adicionar mesmo CPF (formando) em 2 PortalUsers diferentes (exceto via transferência admin)
- [ ] Testes: matriz 1 pai × 2 formandos × 3 turmas em 2 contratos diferentes; race conditions em commit simultâneo
- [ ] Regra de validação: formando que se tornou ≥18 pode requisitar "separar conta" (fora MVP mas precisa estar documentado como `deferred-v2`)

---

## 6. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §11 — conceito original
- [`docs/prd/PRD_v4.md`](../../prd/PRD_v4.md) §5.1 ER — menção sem detalhes
- [`SPEC-F-002`](SPEC-F-002-responsaveis.md) — complementa (responsável e formando são papéis distintos)
- [`SPEC-008` arquivado](../../_archive/future/SPEC-008-enquetes.md) blocker B-ENQ-05 — gap reconhecido previamente

---

_**Estado:** `stub`. Crítico para desbloquear SPEC-002 refactor e SPEC-010._
