# Changelog

Mantido no padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

> **Ações pós-merge (clientes):** ao trazer um release da base com `make update-base`,
> rode as ações que a entrada indicar. O padrão seguro é
> `php artisan migrate --force && php artisan access:sync && php artisan cache:clear`
> (já incluído no `make update-base`).

## [Unreleased]

### Added

- Estratégia de **instâncias por cliente** via _clone + re-origin_ ([ADR-0016](docs/architecture/adrs/ADR-0016-instancias-por-cliente.md)):
  fluxo bidirecional de atualização (`make update-base` desce; PR de volta sobe), regra
  de ouro de customização aditiva e modelo de consumo "embutido agora → Composer depois".
- Tooling de instâncias: `bin/new-client.sh` (provisiona um cliente de forma aditiva),
  `bin/release-module.sh` (corta release de módulo via `git subtree split` + tag) e
  `bin/update-from-upstream.sh` (traz updates da base no cliente). Targets de Makefile:
  `new-client`, `release-modulo`, `update-base`.
- Target `make setup-client` — setup inicial de uma instância de cliente **sem dados
  demo** (roda `RolePermissionSeeder` em vez de `migrate --seed`; mantém `instalado=false`),
  para que o Setup Wizard (`/admin/setup`) crie a empresa/branding/admin reais. O `make setup`
  (dev) segue semeando demo e pulando o Wizard.

### Changed

- A base passou a ser um **monorepo**: `packages/modulo-*` agora é versionado nela
  (antes `gitignored`/repo aninhado). O módulo desce ao cliente embutido no
  `git merge upstream`; o release o extrai para `erp-module-{slug}` via subtree split.
- `.husky/pre-push`: branch protegida agora é configurável (`.husky/protected-branch`)
  com _opt-out_ local (`.husky/allow-main-push`, gitignored) — usado por clientes.
- `docs/distribuicao-manutencao.md` e `ADR-0015` refinados: "template repo" → "clone +
  re-origin"; URLs apontam para a conta `leonardozaneladias`.

    _Ações pós-merge:_ `php artisan migrate --force && php artisan access:sync && php artisan cache:clear`.

### Fixed

- Sidebar rola até o item de menu ativo ao carregar a página (e após `wire:navigate`):
  os links navegam por full page load e o scroll do SimpleBar resetava a cada clique —
  em menus longos o item da tela atual ficava fora da área visível.
- Espaço do ícone nos inputs com `input-icon-group`: o seletor de padding passou de
  irmão adjacente (`+`) para irmão geral (`~`) — o `altInput` do flatpickr insere o
  input visível depois do original (hidden) e ficava sem o `ps-10` (placeholder
  sobreposto ao ícone do calendário).
- Upstream das correções do laudo 18 do módulo RH nos componentes compartilhados:
  **F9-01** — `x-shared.date-picker`/`date-range-picker` guardam o valor canônico ISO
  no input original e exibem d/m/Y no `altInput` (antes o ISO hidratado era re-parseado
  como d/m/Y: data exibida errada e mês/dia trocados na digitação); **F9-02** —
  o combobox do `select-search` re-sincroniza com o `<select>` nativo hidratado pelo
  Livewire (antes a edição mostrava o placeholder mesmo com valor salvo). Novo
  componente `x-shared.required-indicator` e regressão browser em
  `tests/Browser/Admin/ComponentesFormRegressaoTest.php`.
