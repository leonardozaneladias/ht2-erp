---
title: 'ADR-0018: Multiempresa permanece no core, atrás de um modo single-tenant'
version: 1.0.0
date: 2026-08-22
status: accepted
---

# ADR-0018: Multiempresa permanece no core, atrás de um modo single-tenant

**Status:** Accepted | **Data:** 2026-08-22 | **Decisores:** HT2ML | **Tags:** arquitetura, multiempresa, core, modularidade

## Contexto e problema

A plataforma está sendo fatiada em **core + extensões**, aplicando o [ADR-0015](ADR-0015-modulos-pacotes-composer.md) à própria base. O candidato mais visível à extração era o multiempresa: um produto single-tenant não deveria carregar empresa, filial, seletor de contexto e RBAC de dois níveis.

A medição do acoplamento mudou a resposta:

- **74 de 373** arquivos em `app/` (19,8%) citam empresa, filial ou contexto; **27 de 260** blades; **50 de 194** testes (25,8%).
- **8 tabelas do core** carregam `empresa_id` (mais 2 no RH), várias com FK `NOT NULL`; 3 carregam `filial_id`.
- **60+ permissões** e 10 itens de menu dependem do conceito.
- Há uma **dependência de boot**: `ConcluirSetupAction` cria a primeira empresa, e sem ela o Setup Wizard não fecha — a aplicação fica presa em `/admin/setup`.

Do outro lado, três fatos abaratam a alternativa: o RBAC por empresa é implementação própria e não a feature de _teams_ do Spatie (`config/permission.php` → `teams => false`); a união entre papéis globais e papéis por empresa acontece em **um único ponto** (`app/Support/Access/AccessCache.php`); e o gerador de módulos **já tem o interruptor** `--tenant`.

## Drivers da decisão

- Um produto single-tenant não deve pagar pelo multiempresa na interface nem no modelo mental.
- A extração não pode parar a plataforma por meses nem invalidar 50 testes de uma vez.
- Extrair para extensão inverteria a dependência: o core passaria a consumir a extensão no `register()`, e não há ordenação entre pacotes.

## Alternativas consideradas

### Alt 1: extrair `ht2ml/multiempresa` como extensão

Rejeitada pelo custo medido acima, somado a três quebras de boot e à inversão de dependência. É a resposta arquiteturalmente pura e a mais cara por ordens de grandeza.

### Alt 2: assumir que a plataforma é sempre multiempresa

Rejeitada. Exclui produtos que não têm o conceito — e já existe um caso concreto na casa que não é multiempresa nem ERP.

### Alt 3: modo single-tenant no core (escolhida)

O multiempresa fica no core e é neutralizado por configuração.

## Decisão

**Multiempresa permanece no core**, desligável por um modo single-tenant que neutraliza o global scope do `BelongsToEmpresa`, o middleware de contexto, o seletor na topbar, o item de menu e as permissões de multiempresa.

Três pontos de boot precisam de tratamento para que o modo funcione:

1. `ConcluirSetupAction` — passa a criar uma **empresa implícita** em vez de exigir a escolha do usuário.
2. Os seeders (`EmpresaSeeder`, e o vínculo de empresa demo no `AdminUserSeeder`).
3. As FKs `NOT NULL` de `exemplos`, `anexos`, `import_logs` e `document_sequences`, que estouram na escrita sem contexto.

## Consequências

- Um produto single-tenant liga a flag, recebe a empresa implícita e nunca vê o conceito na interface.
- O core continua **carregando** o código de multiempresa mesmo quando desligado. Isso é peso, não complexidade: o custo é tamanho do pacote, não superfície de manutenção do produto.
- A extração continua possível no futuro — e passa a ser decidida com um segundo produto real como evidência, em vez de por hipótese.
- Isto **emenda a ambição** do ADR-0015 aplicada à própria base: nem tudo que é opcional precisa virar pacote. Um interruptor bem colocado entrega o mesmo resultado para o consumidor por uma fração do custo.

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](ADR-0015-modulos-pacotes-composer.md)
- [ADR-0012: Spatie Permission com `guard_name` explícito por modelo](ADR-0012-spatie-permission-guard-name.md)
- [app/CONTEXT.md](../../../app/CONTEXT.md) — Empresa, Filial, Contexto
