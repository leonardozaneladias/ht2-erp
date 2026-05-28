# Módulo: Auditoria e ACL

**Sprint:** 24
**Última Atualização:** —
**Status:** 🔴 Pendente
**Referência PRD:** Seção 14.18-14.19 (Usuários e Perfis ACL), Seção 11 (Controle de Acesso)

## Escopo

_A definir durante a implementação._ Gestão de usuários admin, perfis ACL, permissões granulares (Spatie) e visualização de logs de auditoria append-only.

## Models Envolvidos

| Model    | Tabela     | Campos Principais                                                       |
| -------- | ---------- | ----------------------------------------------------------------------- |
| AuditLog | audit_logs | user_type, user_id, action, auditable_type, auditable_id, before, after |

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

1. `audit_logs` é append-only (nunca editar, nunca deletar)
2. Toda ação crítica grava before/after em JSON
3. Implementado via Trait `HasAuditLog` + Observers
4. Spatie Permission para roles/permissions granulares
5. A definir

## Telas / UI

—

## Testes

| Teste | Tipo | Cenário |
| ----- | ---- | ------- |
| —     | —    | —       |

## Dependências

- Depende de: Autenticação Admin (01)
- Dependido por: Todos os módulos admin (através de HasAuditLog)

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
