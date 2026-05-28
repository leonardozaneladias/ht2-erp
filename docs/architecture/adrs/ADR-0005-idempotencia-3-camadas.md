---
title: 'ADR-0005: Idempotência em três camadas (header + cache + DB unique)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0005: Idempotência em três camadas (header + cache + DB unique)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, SRE | **Tags:** idempotencia, concorrencia, pagamentos, seating

## Contexto e problema

Endpoints sensíveis (reservar assento, criar pedido extra, iniciar pagamento, emitir lote de convites, processar webhook) precisam tolerar: cliente retry por timeout, duplo clique, replay de webhook, proxy reenviando request. Aplicar o efeito duas vezes gera estado inválido (duas reservas no mesmo assento, dois pagamentos confirmados, 2× convites emitidos).

Sem estratégia explícita, cada action reinventa a roda — alguns com `firstOrCreate`, outros sem nada.

## Drivers da decisão

- Garantia "at-most-once" para efeitos de domínio.
- Ao mesmo tempo, permitir retry seguro por parte do cliente (ele obtém o MESMO resultado, não erro 409).
- Defesa em profundidade: falha em uma camada não significa dobrar efeito.
- Compatível com retry exponencial do Horizon e timeout do cliente mobile.

## Alternativas consideradas

### Alt 1: Apenas DB unique

- Prós: simples.
- Contras: cliente recebe 500 em vez de "sua operação já foi aplicada"; não detecta payload divergente reutilizando mesma key; sem política de janela temporal.

### Alt 2: Apenas header + cache

- Prós: latência baixa.
- Contras: cache Redis é eventualmente volátil; sem persistência, uma queda de Redis permite duplicação; não cobre webhooks.

### Alt 3: Três camadas combinadas (escolhida)

- Prós: cada camada cobre um modo de falha distinto.
- Contras: levemente mais código a revisar; exige disciplina de sempre passar `idempotency_key` para as Actions.

## Decisão

Toda operação sensível aplica as **três camadas obrigatórias**:

1. **Camada HTTP — `IdempotencyKeyGuard` (middleware)**
    - Exige header `X-Idempotency-Key` (1 ≤ len ≤ 80).
    - Cache key = `idem:{user_id}:{route_name}:{key}` com TTL 24h.
    - Fingerprint = `sha256(method|route|body)`; se diferente da armazenada → `409 Conflict`.
    - Route name obrigatório na chave — impede colisão cross-endpoint.

2. **Camada Action — `firstOrCreate` / lookup por `idempotency_key`**
    - Antes de qualquer `INSERT`, Action consulta registro pela coluna `idempotency_key` única; se existir, devolve o estado atual.
    - Ex.: `ReservarAssentoAction` §3.5.

3. **Camada DB — constraint `UNIQUE`**
    - Coluna `idempotency_key` com unique index; qualquer race que escape das duas camadas anteriores cai em `SQLSTATE 23505`, convertido para `InvariantViolationException → 409`.

Webhooks (ADR-0013) seguem padrão equivalente: header HMAC + `firstOrCreate(provider, gateway_reference)` + unique composto.

## Consequências positivas

- Retries seguros do cliente; double-tap não gera dois efeitos.
- Payload adulterado reutilizando key é detectado (cache fingerprint).
- Resiliência a falha parcial: se cache cai, DB unique ainda impede duplicação.
- Observabilidade: falhas idempotentes aparecem como `idempotency_key_reutilizada` no log, separáveis de validações.

## Consequências negativas

- Toda Action sensível exige `idempotencyKey` em seu DTO (ligeira verbosidade).
- Cache Redis recebe ~1 chave por POST sensível (negligível).
- Cliente precisa gerar uma ULID/UUID por tentativa lógica e reusar em retries (documentado no guia da API).

## Ligações

- §0 princípio 4, §2.9, §3.5, §4.3, §4.5, §5.5, §5.6 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0006 (concorrência seating), ADR-0013 (webhook HMAC)
- SAD arc42 seção "Conceitos de corte transversal — Concorrência/Consistência"
