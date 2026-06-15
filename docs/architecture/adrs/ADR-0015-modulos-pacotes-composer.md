---
title: 'ADR-0015: Módulos de negócio como pacotes Composer distribuíveis'
version: 1.0.0
date: 2026-06-15
status: accepted
---

# ADR-0015: Módulos de negócio como pacotes Composer distribuíveis

**Status:** Accepted | **Data:** 2026-06-15 | **Decisores:** GDF Sistemas | **Tags:** arquitetura, modularidade, distribuição, packaging

## Contexto e problema

Este boilerplate é usado como base para iniciar todo novo projeto de cliente. A camada administrativa (auth, RBAC, multi-empresa, settings, auditoria, etc.) está madura. A partir dela, cada cliente recebe apenas **regras de negócio** (CRUDs, telas, relatórios).

Hoje o `make:modulo` gera a stack CRUD inteira **dentro de `app/`** (monólito) e **injeta** rotas/permissões/menu em arquivos do core via âncoras de comentário. Consequência: reusar um módulo entre clientes é copiar/colar, e propagar uma correção é manual, cliente a cliente.

A decisão em aberto: qual formato de módulo permite **reuso entre clientes** e **propagação rápida de correções**, mantendo simplicidade e custo zero de infraestrutura (operação solo, 2-5 clientes)?

## Drivers da decisão

- Módulos serão **iguais entre clientes** (config-driven; diferenças por configuração publicável). → favorece artefato versionado.
- Propagar bugfix/melhoria para todos os clientes com baixo atrito.
- Custo zero de infra e baixa curva para dev solo.
- **Retrocompatibilidade**: o `make:modulo` atual (modo `app/`) deve continuar funcionando.
- Evolução natural do [ADR-0002](ADR-0002-monolito-modular.md) ("estrada clara para extrair módulos futuros").

## Alternativas consideradas

### Alt 1: Pastas de domínio no monólito (status quo)

- Prós: simplicidade máxima, zero dependência nova, funciona com o gerador atual.
- Contras: sem reuso real (copiar/colar); bugfix não propaga; cada cliente diverge.

### Alt 2: `nwidart/laravel-modules`

- Prós: pasta `Modules/` self-contained, enable/disable nativo.
- Contras: feito para modularizar **um único app**, não para distribuir entre repos — reuso entre clientes ainda exige publicar pacote; adiciona dependência e paradigma próprio (loader, autoload custom); atrito com discovery de Livewire 4 / PowerGrid.

### Alt 3: Pacote Composer puro + `spatie/laravel-package-tools` (escolhida)

- Prós: distribuição nativa (`composer require` + semver + `composer update` propaga correções); **zero dependência nova** (package-tools já é dep transitiva); auto-discovery nativo do Laravel (`extra.laravel.providers`); é "só um package Laravel" (baixa curva).
- Contras: componentes Livewire e Policies de pacote exigem registro explícito (não há auto-discovery fora de `App\`); acoplamento implícito pacote→core até extrair um `gdf/erp-contracts` (futuro).

## Decisão

Módulos de negócio reutilizáveis serão **pacotes Composer puros** (`gdf/erp-module-{slug}`, namespace `Gdf\{Modulo}\`), distribuídos via **VCS repository** em GitHub privado com **semver por git tag**. A base/core é distribuída como **template repo + `upstream` remote** (git merge propaga melhorias). Extração do core como pacote (`gdf/erp-core`) + Satis/Private Packagist fica como **evolução futura**, quando a escala justificar.

**Pontos de extensão no core** (o core já é config-driven):

1. **Permissões — zero mudança no core.** O pacote mescla seu catálogo em `config('access.modules')` no `boot()` do seu ServiceProvider. `access:sync`, `RolePermissionSeeder`, matriz de acesso e simulador passam a enxergá-lo automaticamente.
2. **Menu — zero mudança no core.** Mesma técnica em `config('admin-menu')` (key estável → personalizações do cliente no banco sobrevivem a updates).
3. **Rotas — única mudança no core.** `App\Support\Modules\ModuleRegistry` (singleton): o grupo autenticado de `routes/admin.php` itera `routeCallbacks()`; o pacote registra um closure (no `register()`) que carrega suas rotas, herdando todo o middleware admin (tenant/2FA/inactivity).
4. **Livewire 4 / Policies — explícitos no pacote** (`Livewire::component()`, `Gate::policy()`).

**Regra de ouro (mantém a base atualizável sem conflitos de merge):** o código do cliente é **aditivo** — nunca edita arquivos do core. Negócio vem de pacotes (merge em runtime); personalização vai para banco (MenuPersonalizacao, settings) ou config publicada do pacote.

O `make:modulo` ganha modo pacote (`--module=`) com namespaces/paths dinâmicos; um novo `make:modulo-pacote` faz o bootstrap da casca. O modo `app/` (sem flag) permanece inalterado.

## Consequências

**Positivas:** reuso e propagação de correção reais (`composer update`); base atualizável via `git merge upstream`; separação forte core/negócio; sem dependência nova; gerador retrocompatível.

**Negativas / a gerenciar:** registro explícito de Livewire/Policies de pacote (gerado pelos stubs); cache de menu/permissão deve ser limpo pós-instalação; acoplamento implícito pacote→core até extrair `gdf/erp-contracts`; prefixo de módulo obrigatório nas permissões (`rh.*`) para evitar colisão.

## Referências

- [ADR-0002: Monólito modular](ADR-0002-monolito-modular.md) — esta decisão concretiza a "extração de módulos" lá prevista.
- Plano-mestre do épico: `docs/superpowers/specs/` / plano de produtização.
