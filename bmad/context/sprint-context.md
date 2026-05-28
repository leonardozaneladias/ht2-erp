# Contexto Compartilhado — Sprint F1 Portal ArtFinal

## Projeto

**Sistema:** Portal ArtFinal — gerenciamento de formaturas  
**Stack:** Laravel 13, PHP 8.4, PostgreSQL 16, Redis, Livewire 4, Tailwind v4  
**Fase:** F1 — Fundação de domínio e API-ready (34 SP)  
**Nível BMAD:** 3 (34 SP, 2 sprints)

## Regras invioláveis

- `declare(strict_types=1)` em TODOS os arquivos PHP
- Type hints e return types em TODOS os métodos
- IDs públicos: ULID (nunca sequencial em URL/API)
- Dinheiro: INTEGER centavos (nunca float)
- Soft-delete via campo `ativo`, nunca DELETE real
- Guards separados: `admin` (AdminUser) e `portal` (PortalUser)
- Webhooks sem CSRF, com validação HMAC

## Estrutura de guards

```
Guards:      admin (AdminUser)          portal (PortalUser)
Tabelas:     admin_users                portal_users
Rotas:       routes/admin.php           routes/portal.php
Prefixo:     /admin/*                   /portal/*
```

## Convenções de naming

| Artefato  | Padrão              | Exemplo                       |
| --------- | ------------------- | ----------------------------- |
| Model     | PascalCase singular | `Organizacao`, `Turma`        |
| Enum      | PascalCase          | `StatusAdesao`, `PerfilAtor`  |
| DTO       | PascalCase + Data   | `NovaAdesaoData`              |
| Action    | PascalCase + Action | `CriarAdesaoAction`           |
| Migration | snake_case          | `create_organizacoes_table`   |
| Tabela BD | snake_case plural   | `organizacoes`, `admin_users` |

## Épicos F1

| Épico | Descrição                               | SP   |
| ----- | --------------------------------------- | ---- |
| F1-E1 | Setup & Configuração                    | 5 SP |
| F1-E2 | Infraestrutura de domínio               | 8 SP |
| F1-E3 | Camada HTTP (routes + middlewares)      | 8 SP |
| F1-E4 | Modelos e banco de dados                | 8 SP |
| F1-E5 | Tipos de domínio (Enums, DTOs, Actions) | 3 SP |
| F1-E6 | Qualidade e CI                          | 2 SP |

## Pacotes a instalar (F1)

```bash
composer require laravel/sanctum spatie/laravel-data saloonphp/laravel-plugin \
  sentry/sentry-laravel league/flysystem-aws-s3-v3 \
  laravellegends/pt-br-validator spatie/laravel-medialibrary
```

## Sizing Fibonacci

- 1 SP → trivial (1-2h)
- 2 SP → simples (2-4h)
- 3 SP → moderado (4-8h)
- 5 SP → complexo (1-2 dias)
- 8 SP → muito complexo (2-3 dias) — máximo permitido
