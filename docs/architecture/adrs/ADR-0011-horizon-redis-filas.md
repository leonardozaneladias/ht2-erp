---
title: 'ADR-0011: Horizon + Redis para filas (vs SQS/database)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0011: Horizon + Redis para filas (vs SQS/database)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, SRE | **Tags:** filas, horizon, redis, observabilidade

## Contexto e problema

O backend precisa processar, de forma assíncrona: envio de e-mails (convite, RSVP reminder, comprovante), webhooks de pagamento, expiração de holds de seating (`everyMinute`), emissão em lote de convites (500+ chunks), exports Excel/PDF, pushs mobile. Cada carga tem prioridade, concurrency e timeout distintos.

Qual driver e orquestrador de filas escolher?

## Drivers da decisão

- Workloads heterogêneos (latência baixa para seating vs tolerante para exports).
- Observabilidade: precisamos ver metrics de throughput, slow jobs, failed jobs sem infra extra.
- Custo operacional: time pequeno, preferir gerenciar 1 Redis em vez de Redis + SQS + UI própria.
- Laravel 13 nativo: queue workers + Horizon rodam out-of-the-box.
- SLA de entrega: at-least-once com retry exponencial.

## Alternativas consideradas

### Alt 1: `queue:database`

- Prós: zero dependência extra, usa o Postgres.
- Contras: alta contenção em pick (`SELECT FOR UPDATE SKIP LOCKED` OK, mas concurrency baixa); aumenta load do DB principal; sem dashboard nativo; throughput insuficiente para bulk convite.

### Alt 2: AWS SQS

- Prós: managed, escala infinita.
- Contras: vendor lock-in; latência maior; sem dashboard Laravel-nativo; FIFO cost extra; observabilidade via CloudWatch custosa para cada supervisor.

### Alt 3: Redis driver direto sem Horizon

- Prós: simples.
- Contras: sem UI, sem autoscaling de workers, sem balance strategy; SRE cega a travamentos de fila.

### Alt 4: Laravel Horizon + Redis (escolhida)

- Prós: oficial Laravel; UI em `/horizon` com throughput, waits, failed jobs, recent; `supervisors` por workload (seating, webhooks, exports, notifications); `balance: auto` escala processos; `tags` por job rastreáveis; integração com Pulse para métricas agregadas. Redis já é dependência para cache + lock (ADR-0006).
- Contras: exige Redis em HA em produção; requer snapshot scheduled para dashboard.

## Decisão

Usar **Horizon + Redis** como único stack de filas. `config/horizon.php` define supervisors por workload (§7.2):

| Supervisor            | Filas                      | Concurrency | Tries | Timeout |
| --------------------- | -------------------------- | ----------- | ----- | ------- |
| `supervisor-default`  | `default`, `notifications` | 3–20 auto   | 3     | 90s     |
| `supervisor-webhooks` | `webhooks`                 | 2–6 simple  | 5     | 120s    |
| `supervisor-exports`  | `exports`                  | 1–2 simple  | 2     | 600s    |
| `supervisor-seating`  | `critical-seating`         | 2–4 simple  | 1     | 30s     |

Scheduling de `ExpirarHoldsJob` (§5.4) roda `everyMinute` + `withoutOverlapping(5)` + `onOneServer()`. Retry policy padrão: `backoff()` exponencial `[10, 30, 90, 300, 600]s`, `failed()` log estruturado; Horizon mantém `failed_jobs` como DLQ (§7.5). Alertas Sentry quando `count > 5` falhas em 5 min por classe.

Dashboard Horizon protegido por gate `admin` em `/horizon`. `trim` configurado para retenção controlada de recent/completed/failed (§7.2).

## Consequências positivas

- Um único dashboard para SRE entender filas (vs CloudWatch + custom).
- Supervisors isolam workloads — seating não degrada por export pesado.
- `balance: auto` ajusta processos dinamicamente em burst.
- Retry + backoff + DLQ nativos.
- Redis mesma instância do cache e do lock (ADR-0006) — uma dependência, não três.

## Consequências negativas

- Redis vira SPOF para filas. Mitigação: Redis em HA (sentinel ou cluster) obrigatório em produção.
- Horizon requer `php artisan horizon:snapshot` scheduled para métricas. Documentado no runbook SRE.

## Ligações

- §0 princípio 9 (auditoria), §7.1, §7.2, §7.3, §7.4, §7.5, §5.4 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0006 (Redis lock), ADR-0013 (webhook job assíncrono)
- SAD arc42 seção "Visão de runtime — Filas"
