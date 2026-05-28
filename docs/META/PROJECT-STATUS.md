---
title: Status do Projeto
description: Status operacional do projeto
owner_role: architect
change_during_sprint: false
status_projeto: desenvolvimento
last_review: 2026-04-23
---

# Status do Projeto

> **ATENÇÃO — CONTEXTO OBRIGATÓRIO.** Este documento é lido por qualquer agente (Claude, Codex, Gemini, humano)
> **antes** de iniciar qualquer trabalho de engenharia, documentação ou planejamento neste repositório.
> O campo `status_projeto` determina quais práticas, riscos e restrições se aplicam. Releia este arquivo
> sempre que entrar no projeto (primeiro contato) e periodicamente (mudanças de fase, início de sprint).

---

## Status atual

```yaml
status: desenvolvimento
```

O projeto está em fase de desenvolvimento ativo, **sem usuários reais** e **sem dados de produção**.
Breaking changes em modelagem, contratos de API, estruturas de banco e fluxos são **permitidos** e
esperados nesta fase, desde que acompanhados de atualização documental coerente.

---

## Significado dos status

### `desenvolvimento`

- Sem usuários reais conectados ao sistema.
- Breaking changes permitidos livremente (modelagem, API, rotas, contratos de eventos).
- Migrations podem usar `DROP TABLE`, `DROP COLUMN` e recriar estruturas do zero.
- Dados de desenvolvimento vêm sempre de `DevelopmentSeeder` (nunca de dumps de produção).
- Não é necessário manter compatibilidade retroativa entre migrations.
- Feature flags **opcionais**, usadas apenas quando convenientes para testes A/B internos.
- Testes automatizados prioritariamente com factories e seeders — não dependem de fixtures imutáveis.

### `homologacao`

- Cliente (organizadora parceira) acessa o sistema para testar fluxos reais.
- Breaking changes **exigem comunicação prévia** ao stakeholder cliente + janela de manutenção agendada.
- Migrations preferem `ALTER TABLE ADD COLUMN NULL` + backfill assíncrono, nunca `DROP` direto.
- Dados de homologação preservados entre sprints (importados via seeder específico ou criados pelo cliente).
- Testes E2E com dados realistas (não produtivos mas representativos).
- Feature flags **recomendadas** para rollout gradual de features com impacto visual ou de fluxo.
- Rollback plan obrigatório para mudanças estruturais.

### `producao`

- Usuários reais (formandos, responsáveis, comissões, organizadoras) ativos na plataforma.
- **Zero tolerância** a perda ou corrupção de dados.
- Migrations **obrigatoriamente** compatíveis em 3 passos (add nullable → backfill → drop) com janelas de deploy.
- Todo deploy passa por **gate de aprovação manual** do arquiteto + owner de produto.
- Feature flags **obrigatórias** para qualquer novo fluxo de usuário final.
- Rollback plan testado e ensaiado para toda mudança estrutural.
- Observabilidade (Pulse, Horizon, logs estruturados) com thresholds e alertas operacionais.
- Backups PITR ativos; runbooks de recuperação versionados e testados a cada trimestre.

---

## Como alterar este status

1. Editar o campo `status_projeto` no frontmatter desde arquivo e a declaração YAML em **"Status atual"**.
2. Atualizar `last_review` com a data da mudança (formato ISO `YYYY-MM-DD`).
3. Rodar o validador documental do projeto (`php artisan docs:validate` quando existir;
   até lá, inspeção manual cobrindo CLAUDE.md §3 e índice de docs).
4. Comunicar a mudança ao squad em canal acordado (Plane wiki + squad channel).
5. Commit no formato: `chore(infra): atualizar status do projeto para <novo_status>`.

Mudanças de status **nunca** ocorrem no meio de sprint sem aprovação explícita do arquiteto.

---

## Impacto nas decisões de engenharia

| Situação                                  | `desenvolvimento`                               | `homologacao`                                   | `producao`                                              |
| ----------------------------------------- | ----------------------------------------------- | ----------------------------------------------- | ------------------------------------------------------- |
| Breaking migrations (DROP/RENAME)         | Permitidas livremente                           | Evitar; exigem comunicação + janela             | Proibidas (usar padrão 3 passos: add → backfill → drop) |
| Seeders                                   | `DevelopmentSeeder` completo; dados fake livres | Seeder de homologação com dados representativos | Nenhum seeder rodado em produção (só migrations)        |
| Testes E2E com dados reais                | Não aplicável (sem dados reais)                 | Usar dados do próprio ambiente de homologação   | Proibido tocar em dados produtivos em testes            |
| Estratégia de rollback                    | Reversão via `migrate:fresh --seed`             | Rollback script + snapshot pré-deploy           | PITR + plano de rollback ensaiado; gate de aprovação    |
| Necessidade de feature flags              | Opcional (conveniência)                         | Recomendada para mudanças visíveis ao cliente   | Obrigatória para qualquer fluxo novo de usuário         |
| Observabilidade (Pulse, Horizon, alertas) | Básica (dashboards locais)                      | Thresholds suaves; canal de alerta dedicado     | Thresholds rígidos; on-call + runbooks                  |
| Backup / PITR                             | Não necessário                                  | Snapshot periódico                              | PITR ativo, teste trimestral de restauração             |
| Comunicação de quebra                     | Interna ao squad                                | Email/call ao cliente + nota na wiki            | Aviso antecipado + janela agendada + changelog público  |

---

## Referências cruzadas

- [`CLAUDE.md`](../../CLAUDE.md) §3 — documento referenciado como leitura obrigatória antes de qualquer trabalho.
- [`docs/README.md`](../README.md) — hub de documentação.
- [`docs/prd/PRD_v4.md`](../prd/PRD_v4.md) §20.3 — plano de sprints (contexto de fase do roadmap).
- [`docs/devops/conventions.md`](../devops/conventions.md) — convenções de commit e qualidade.

---

_Este documento é **governança pura**. Não descreve código. Não deve ser alterado fora de uma transição
explícita de fase do projeto (ex.: entrando em homologação, promovendo para produção)._
