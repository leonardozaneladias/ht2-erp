# Módulo: E-mails Transacionais

**Sprint:** 14
**Última Atualização:** —
**Status:** 🔴 Pendente
**Referência PRD:** Seção 16 (E-mails Transacionais e Notificações)

## Escopo

_A definir durante a implementação._ Mailables, templates e automações: adesão concluída, boleto gerado, lembrete de vencimento, pagamento confirmado, recuperação de senha.

## Models Envolvidos

| Model    | Tabela     | Campos Principais                            |
| -------- | ---------- | -------------------------------------------- |
| EmailLog | email_logs | destinatario, assunto, tipo, status, sent_at |

## Services e Actions

| Classe | Método Principal | Responsabilidade |
| ------ | ---------------- | ---------------- |
| —      | —                | —                |

## Rotas

| Método | URI | Controller@Action | Middleware |
| ------ | --- | ----------------- | ---------- |
| —      | —   | —                 | —          |

## Components Blade Utilizados

- —

## Regras de Negócio

1. Todo e-mail é assíncrono (fila `emails`)
2. Todo envio registra em `email_logs`
3. Mailables para e-mails transacionais, Notifications para sino do admin
4. A definir

## Telas / UI

—

## Testes

| Teste | Tipo | Cenário |
| ----- | ---- | ------- |
| —     | —    | —       |

## Dependências

- Depende de: Adesão Wizard (11), Gateway (16), Parcelas (14)
- Dependido por: —

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
