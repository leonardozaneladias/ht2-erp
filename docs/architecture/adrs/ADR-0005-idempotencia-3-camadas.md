---
title: 'ADR-0005: Idempotência em três camadas (request + cache + DB unique)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0005: Idempotência em três camadas (request + cache + DB unique)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, SRE | **Tags:** idempotencia, concorrencia

## Contexto e problema

Operações sensíveis (criar um pedido, registrar uma transação, processar um lote) precisam tolerar: retry por timeout, duplo clique no formulário, replay de request, proxy reenviando a chamada. Aplicar o efeito duas vezes gera estado inválido (dois registros equivalentes, duas transações confirmadas).

Sem estratégia explícita, cada action reinventa a roda — alguns com `firstOrCreate`, outros sem nada.

## Drivers da decisão

- Garantia "at-most-once" para efeitos de domínio.
- Ao mesmo tempo, permitir retry seguro por parte do cliente (ele obtém o MESMO resultado, não erro 409).
- Defesa em profundidade: falha em uma camada não significa dobrar efeito.
- Compatível com retry exponencial do Horizon.

## Alternativas consideradas

### Alt 1: Apenas DB unique

- Prós: simples.
- Contras: cliente recebe erro genérico em vez de "sua operação já foi aplicada"; não detecta payload divergente reutilizando a mesma key; sem política de janela temporal.

### Alt 2: Apenas request + cache

- Prós: latência baixa.
- Contras: cache Redis é eventualmente volátil; sem persistência, uma queda de Redis permite duplicação.

### Alt 3: Três camadas combinadas (escolhida)

- Prós: cada camada cobre um modo de falha distinto.
- Contras: levemente mais código a revisar; exige disciplina de sempre passar `idempotency_key` para as Actions.

## Decisão

Toda operação sensível aplica as **três camadas obrigatórias**:

1. **Camada de request — `IdempotencyKeyGuard` (middleware)**
    - Exige uma chave de idempotência por requisição (1 ≤ len ≤ 80), enviada em header ou campo dedicado do formulário.
    - Cache key = `idem:{user_id}:{route_name}:{key}` com TTL 24h.
    - Fingerprint = `sha256(method|route|body)`; se diferente da armazenada → conflito (`409 Conflict`).
    - Route name obrigatório na chave — impede colisão entre rotas.

2. **Camada Action — `firstOrCreate` / lookup por `idempotency_key`**
    - Antes de qualquer `INSERT`, a Action consulta o registro pela coluna `idempotency_key` única; se existir, devolve o estado atual.

3. **Camada DB — constraint `UNIQUE`**
    - Coluna `idempotency_key` com unique index; qualquer race que escape das duas camadas anteriores cai em `SQLSTATE 23505`, convertido para `InvariantViolationException → 409`.

Integrações externas (ex.: callbacks/notificações de sistemas terceiros) seguem padrão equivalente: validação de origem + `firstOrCreate(origem, referencia_externa)` + unique composto.

## Consequências positivas

- Retries seguros do cliente; double-tap não gera dois efeitos.
- Payload adulterado reutilizando a key é detectado (cache fingerprint).
- Resiliência a falha parcial: se o cache cai, o DB unique ainda impede duplicação.
- Observabilidade: falhas idempotentes aparecem como `idempotency_key_reutilizada` no log, separáveis de validações.

## Consequências negativas

- Toda Action sensível exige `idempotencyKey` em seu DTO (ligeira verbosidade).
- Cache Redis recebe ~1 chave por operação sensível (negligível).
- Cliente precisa gerar uma ULID/UUID por tentativa lógica e reusar em retries (documentado no guia da operação).

## Ligações

- ADR-0011 (Horizon, retry de jobs)
