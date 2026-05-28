# Módulo: Gateway Itaú

**Sprint:** 12-13
**Última Atualização:** —
**Status:** 🔴 Pendente
**Referência PRD:** Seção 15 (Sistema de Pagamentos e Integração)

## Escopo

_A definir durante a implementação._ Integração com gateway de pagamentos Itaú: boleto, PIX e cartão. Webhooks de confirmação com validação HMAC. Jobs idempotentes.

## Models Envolvidos

| Model | Tabela | Campos Principais |
| ----- | ------ | ----------------- |
| —     | —      | —                 |

## Services e Actions

| Classe | Método Principal | Responsabilidade |
| ------ | ---------------- | ---------------- |
| —      | —                | —                |

## Rotas

| Método | URI           | Controller@Action        | Middleware             |
| ------ | ------------- | ------------------------ | ---------------------- |
| POST   | /webhook/itau | WebhookController@handle | VerifyWebhookSignature |

## Components Blade Utilizados

- —

## Regras de Negócio

1. Webhooks validados com HMAC-SHA256 antes de qualquer processamento
2. Todo job no gateway é idempotente (fila `gateway`, backoff `[10, 60, 300]`)
3. Logs no canal `gateway` (sem dados sensíveis)
4. A definir

## Telas / UI

—

## Testes

| Teste | Tipo | Cenário |
| ----- | ---- | ------- |
| —     | —    | —       |

## Dependências

- Depende de: Parcelas Financeiro (14)
- Dependido por: Adesão Wizard (11), Extras (17)

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
