---
title: 'ADR-0006: Concorrência em seating (Redis lock + unique parcial + lockForUpdate)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0006: Concorrência em seating (Redis lock + unique parcial + lockForUpdate)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, SRE, Produto | **Tags:** concorrencia, seating, postgres, redis

## Contexto e problema

Reserva de assento é o cenário mais disputado do sistema: em uma formatura típica, >100 formandos podem tentar reservar o mesmo assento simultaneamente durante a abertura do mapa. Falha aqui significa: dois formandos "com reserva confirmada" no mesmo lugar, litígio com o cliente, LGPD e reputação. SLA: 0% de conflito em 1.000 tentativas simultâneas, P95 ≤ 700ms.

Soluções triviais (apenas `DB::transaction`, apenas `SELECT FOR UPDATE`, apenas unique simples em `assento_id`) falham em algum cenário:

- `SELECT FOR UPDATE` sem lock externo permite race entre "select" e "insert" se o registro ainda não existe.
- Unique `(assento_id)` simples impede também estados finais (cancelada/expirada) — destruiria a auditoria.
- Lock Redis sozinho não tem persistência e não é ACID com a transação DB.

## Drivers da decisão

- Invariante dura: no máximo UMA reserva ativa (`hold` OU `confirmada`) por assento.
- Latência curta: P95 ≤ 700ms.
- Observabilidade: cada falha deve ser distinguível (ocupado vs hold expirou vs cota).
- Janela de hold configurável (default 5 min).
- Defesa em profundidade: múltiplas camadas.

## Alternativas consideradas

### Alt 1: Serializable isolation em todas transações de seating

- Prós: correção teórica.
- Contras: custo de serialization conflict com rollback frequente; throughput cai drasticamente em picos.

### Alt 2: Unique simples `(assento_id)` sem estado

- Prós: simples.
- Contras: histórico de cancelamentos/expirações não cabe; soft-delete em tabela transacional (proibido §4.8).

### Alt 3: Apenas Redis lock (sem DB constraint)

- Prós: latência baixa.
- Contras: se lock não renovar (GC pause, network partition), dobra reserva; não há defesa final no DB.

### Alt 4: Quatro camadas combinadas (escolhida)

1. **Idempotência por `idempotency_key`** (ADR-0005): mesma key → devolve reserva existente.
2. **Redis lock `seating:assento:{ulid}`** com TTL 10s + `lock->block(3)`: serializa chamadas concorrentes na mesma instância/cluster.
3. **`DB::transaction` + `Assento::lockForUpdate()`**: row-lock ACID dentro da transação Postgres.
4. **Unique index parcial** `ON reservas_assentos (assento_id) WHERE status IN ('hold','confirmada')`: invariante final, independente de qualquer lock.

## Decisão

Implementar `ReservarAssentoAction` (§3.5) combinando as quatro camadas, na ordem exata. Em caso de falha da unique parcial (`SQLSTATE 23505`), traduzir para `AssentoIndisponivelException` → HTTP 409.

Migration define também:

- `CHECK` constraint `hold_expires_at IS NOT NULL` quando `status='hold'`, `confirmado_at IS NOT NULL` quando `status='confirmada'`.
- `CHECK` constraint de valores válidos de `status` (defesa além do enum PHP — ADR-0010).

Hold expirado: job `ExpirarHoldsJob` (fila `critical-seating`, `everyMinute`, `withoutOverlapping(5)`) marca `status=expirada`, liberando o assento. Confirmação do hold é transação separada (`ConfirmarAssentoAction`) com `lockForUpdate` + validação de `hold_expires_at`.

Troca de assento (`TrocarAssentoAction`): sempre liberar antes de reservar, em ordem fixa por `assento_id` ASC para prevenir deadlock.

## Consequências positivas

- 0% de conflito garantido pela unique parcial (defesa final DB).
- Redis lock reduz contenção no Postgres (serializa cedo).
- `lockForUpdate` garante ACID dentro da transação.
- Testes de concorrência Pest (§10.2, §10.3) validam a invariante.
- Observabilidade: `AssentoIndisponivel` vs `HoldExpirado` são exceções diferentes, tipadas.

## Consequências negativas

- Quatro camadas = mais código para revisar e testar.
- Redis vira dependência crítica para latência de seating. Mitigação: Redis em HA; fallback de degradação é aceitar maior latência caindo direto na unique parcial.
- Deadlock cross-assento possível em troca — mitigado pela ordem fixa `assento_id ASC`.

## Ligações

- §0 princípio 5, §3.5, §4.3, §5.1, §5.2, §5.3, §5.4, §10.2, §10.3 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0005 (idempotência), ADR-0011 (Horizon/Redis), technical-design-seating.md
- SAD arc42 seção "Cenários de runtime — Seating"
