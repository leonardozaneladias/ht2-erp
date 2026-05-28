# Squads por Fase — Portal ArtFinal

Documentação das squads de agents e skills por fase de desenvolvimento.
Gerado e mantido pela skill `squad-configurator`.

---

## Índice

| Fase                                         | Arquivo                    | Status             | SP  |
| -------------------------------------------- | -------------------------- | ------------------ | --- |
| F1 — Fundação                                | [SQUAD-F1.md](SQUAD-F1.md) | ✅ Concluída       | 34  |
| **S2 — Portal Adesão Pública**               | [SQUAD-S2.md](SQUAD-S2.md) | 🟡 Em planejamento | 47  |
| S3 — Portal Autenticado (planejada)          | _a criar_                  | ⬜ Pendente        | ~30 |
| F2/Admin estrutural (adiada Portal-First)    | [SQUAD-F2.md](SQUAD-F2.md) | ⬜ Após S2/S3      | 40  |
| F3 — Cliente web React (absorvido por S2/S3) | [SQUAD-F3.md](SQUAD-F3.md) | ⬜ Pendente        | 34  |
| F4 — Convites e RSVP                         | [SQUAD-F4.md](SQUAD-F4.md) | ⬜ Pendente        | 28  |
| F5 — Seating                                 | [SQUAD-F5.md](SQUAD-F5.md) | ⬜ Pendente        | 34  |
| F6 — Extras, pagamentos e enquetes           | [SQUAD-F6.md](SQUAD-F6.md) | ⬜ Pendente        | 34  |
| F7 — Hardening e observabilidade             | [SQUAD-F7.md](SQUAD-F7.md) | ⬜ Pendente        | 21  |
| F8 — Mobile MVP                              | [SQUAD-F8.md](SQUAD-F8.md) | ⬜ Pendente        | 34  |

**Total:** 306 SP

> **Nota — 2026-04-25:** A sequência canônica do ROADMAP (F2=Admin estrutural antes do cliente React) foi
> ajustada localmente para honrar a regra **Portal-First** do `CLAUDE.md` §15. As squads S2 e S3 cobrem
> o portal do formando (SPEC-010 público + SPEC-001/002/009 autenticado) antes do admin estrutural (F2).
> Após S3, retomamos a numeração F2..F8 do roadmap original.

---

## Como usar

Para gerar ou atualizar a squad de uma fase:

```
/squad-configurator
```

Ou: "monte a squad para a F2" / "qual a squad desta sprint?"

A skill `squad-configurator` lê o contexto atual (branch, BMAD status) e compõe automaticamente a squad com skills obrigatórias, opcionais e atribuição por tarefa.

---

## Legenda de status

| Ícone | Significado            |
| ----- | ---------------------- |
| ⬜    | Pendente (fase futura) |
| 🟡    | Em planejamento        |
| 🟢    | Em andamento           |
| ✅    | Concluída              |
| 🔴    | Bloqueada              |
