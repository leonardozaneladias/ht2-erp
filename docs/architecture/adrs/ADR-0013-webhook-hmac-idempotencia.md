---
title: 'ADR-0013: Webhook de pagamento — HMAC + idempotência + job pós-commit'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0013: Webhook de pagamento — HMAC + idempotência + job pós-commit

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel, Segurança, SRE | **Tags:** webhook, pagamentos, idempotencia, hmac

## Contexto e problema

O gateway de pagamento (Itaú no MVP, outros no futuro) notifica confirmação/estorno via webhook. Sem cuidado, os seguintes problemas são garantidos:

- Replay: atacante reenvia o payload mil vezes → mil efeitos.
- Spoofing: quem conhecer a URL pública pode mentir sobre pagamento.
- Retry do próprio gateway: após timeout, o gateway reenvia → dobra efeito.
- Processamento dentro do request HTTP: se o processamento demora, o gateway considera timeout e reenvia (mesmo que tenha dado certo).

## Drivers da decisão

- Segurança: só webhook autêntico aplica efeito.
- Idempotência: mesmo evento não aplica duas vezes.
- Performance: webhook retorna rápido (< 500ms), processamento pesado fica em job.
- Observabilidade: cada evento recebido fica registrado mesmo se o processamento falhar.

## Alternativas consideradas

### Alt 1: Processar síncrono sem idempotência

- Prós: simples.
- Contras: replay duplica efeito; timeout do gateway gera re-send; catastrófico.

### Alt 2: IP whitelist do gateway

- Prós: simples.
- Contras: IPs mudam; não prova autenticidade do payload (DNS spoof); quebra em migrações cloud.

### Alt 3: HMAC + `firstOrCreate` + job assíncrono (escolhida)

- Prós: três camadas de proteção; retorno rápido; reprocessamento natural via job retry; pegada de auditoria completa.
- Contras: exige secret bem guardado e rotação; exige migration com unique composto.

## Decisão

Fluxo do endpoint `/webhooks/pagamentos/{provider}` (§5.5):

1. **Sem CSRF, sem auth:sanctum**. Registrado em `routes/webhook.php` isolado.
2. **Rate limit `webhook: 600/min por IP`** — permissivo mas presente.
3. **Validação HMAC obrigatória antes de qualquer persistência**:
    ```php
    hash_equals(
        hash_hmac('sha256', $rawPayload, $webhookSecret),
        $request->header('X-Signature')
    )
    ```
    Falha → `401 invalid signature` + log `webhook.assinatura_invalida`.
4. **Idempotência dura no DB**: `WebhookEvento::firstOrCreate(['provider' => $p, 'gateway_reference' => $ref], [...])` — unique composto `(provider, gateway_reference)` na migration (§4.5). Se já existia e `status='processado'` → retorna `200 { status: 'already_processed' }`.
5. **Processamento assíncrono obrigatório**: `ProcessarWebhookPagamentoJob::dispatch($eventoId)` na fila `webhooks`. Retorno do controller: `202 accepted`.
6. **Job idempotente**: consome o `WebhookEvento`, marca `status=processado` apenas ao final; em falha, Horizon retenta com backoff `[5s, 30s, 90s, 300s, 600s]`.
7. **Orquestração de efeito**: `ProcessarWebhookPagamentoAction` decide entre `ConfirmarAdesaoAction`, `ConfirmarPagamentoExtraAction`, `EstornarPedidoExtraAction` — cada uma idempotente por ULID.
8. **Reconciliação complementar**: `ReconciliarPagamentosJob` roda a cada 15 min para detectar divergências (pagamentos pendentes há > 60 min).
9. **Replay protection temporal**: eventos com `recebido_at < now()-24h` são descartados (evita flood histórico em caso de vazamento do secret).
10. **Dispatch pós-commit**: jobs que dependem do webhook persistido usam `dispatchAfterCommit` / `afterCommit()` para evitar race.

## Consequências positivas

- Defesa em profundidade: HMAC (identidade), `firstOrCreate` (idempotência no DB), retry (resiliência do job), reconciliação (cinto + suspensório).
- Auditoria completa: `webhook_eventos.payload` tem o payload bruto mesmo se processamento falhar.
- Retorno rápido ao gateway — evita ciclo de retry por timeout.
- Reprocessável pela UI do Horizon em caso de bug corrigido.

## Consequências negativas

- Secret do webhook é crítico. Mitigação: vault + rotação + auditoria de acesso.
- Processamento em job pode atrasar efeito em poucos segundos. Aceito — é o trade-off certo.

## Ligações

- §5.5, §5.6, §5.7, §4.5 (migration), §7.3 (jobs), §11.5 (checklist segurança) do PLANEJAMENTO_BACKEND_APIV1.md
- Apêndice D #3
- ADR-0005 (idempotência 3 camadas), ADR-0011 (Horizon)
- technical-design-payments.md
- SAD arc42 seção "Integrações externas — Pagamentos"
