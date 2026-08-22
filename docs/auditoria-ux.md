# Auditoria de Usabilidade — Painel Admin

> Revisão geral de UI/UX conduzida em 2026-06-12 via Playwright (navegador real)
> contra o ambiente DDEV (`https://ht2ml-platform.ddev.site`), viewport **1366×768**,
> nos temas **claro e escuro**, com inspeção do console em cada tela.

## Metodologia

Para cada tela: snapshot de acessibilidade → interações por tipo (filtros,
dropdowns, abas, modais, drawers, kebabs) → captura de console (zero erros) →
screenshot em light e dark. Severidades:

- **P1** — quebra funcional (impede uso).
- **P2** — defeito visual/UX perceptível (não bloqueia, mas degrada).
- **P3** — polimento (consistência, refinamento).

## Telas auditadas

Produção: dashboard, usuários (index + form), empresas (index + form),
controle de acesso, auditoria, configurações (6 abas), menus, comunicados,
minha conta (3 abas), notificações. Auth: login, esqueceu-senha. Setup wizard.
Showcase de componentes (`/admin/dev/components`).

Console: **zero erros de JavaScript** em todas as telas (confirmado também pelo
smoke automatizado `tests/Browser/Admin/SmokeTest.php`).

## Achados

| #   | Tela               | Sev    | Descrição                                                                                                                                                      | Status       |
| --- | ------------------ | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| 1   | Dashboard          | **P2** | Card/título "Novos usuários" duplicado e área vazia: a view envolvia `<x-admin.chart-line>` (que já é um `chart-card`) dentro de outro `<x-admin.chart-card>`. | ✅ corrigido |
| 2   | Empresas (lista)   | **P3** | Coluna CNPJ exibida sem máscara (`15156249000168` em vez de `15.156.249/0001-68`).                                                                             | ✅ corrigido |
| 3   | Auth (login/senha) | **P3** | Separador do `<title>` divergia do layout interno (`\|` no auth vs `—` nas telas internas).                                                                    | ✅ corrigido |

## Observações sem ação (registradas)

- **Branding "Laravel Admin" nas telas de auth:** não é bug de código — o
  `auth-layout` já usa `BrandingService::nomeSistema()`. O valor "Laravel Admin"
  é o default de `GeneralSettings::nome`, que cada cliente personaliza nas
  Configurações. As telas internas mostram "Sistemas GDF" por ser o nome da
  **empresa ativa** (tenant), não o nome do sistema.
- **TomSelect "No results found" em inglês:** string do bundle JS do PowerGrid
  (`tom-select` empacotado em `dist/powergrid.js`). Traduzir exigiria `render`
  functions no construtor, não expressáveis via config JSON — baixo ROI; fica
  registrado para uma eventual camada JS própria.

## Verificação

- Bugs específicos do pedido original (z-index dos dropdowns do PowerGrid e
  filtros com busca/multi-seleção) cobertos pelos Batches 1–4, com regressão em
  `tests/Browser/Admin/PowerGridDropdownsTest.php` e
  `tests/Browser/Admin/FiltroMultiSelectTest.php`.
- Smoke de todas as telas autenticadas e de auth sem erros de JS:
  `tests/Browser/Admin/SmokeTest.php` (`make test-e2e`).
