# Configurações do Sistema

Tela administrativa de configuração do sistema (`/admin/configuracoes`) e
assistente de primeira instalação (`/admin/setup`). Permite que cada cliente
ajuste seus dados, identidade visual e preferências sem tocar em código.

> As settings guardam a identidade e as preferências da **instalação**. A plataforma é
> multiempresa no core, com modo single-tenant ([ADR-0018](architecture/adrs/ADR-0018-multiempresa-no-core-modo-single-tenant.md)):
> dado de cada empresa (CNPJ, filiais, branding próprio) vive na tabela `empresas`, não
> aqui. Ver [`multi-empresa.md`](multi-empresa.md) §4.

---

## Arquitetura

Baseada em [`spatie/laravel-settings`](https://github.com/spatie/laravel-settings):
configurações são **classes PHP tipadas**, persistidas na tabela `settings` e
cacheadas.

| Camada   | Local                                                                                                         | Responsabilidade                                     |
| -------- | ------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| Settings | `packages/core/src/Settings/*Settings.php`                                                                    | Grupos tipados (fonte da verdade dos campos)         |
| Enum     | `packages/core/src/Enums/Admin/SettingsGroup.php`                                                             | Grupos + rótulos/ícones das abas                     |
| Defaults | `database/settings/*.php` (na raiz do app; o skeleton leva para o produto)                                    | Valores de fábrica (settings migrations)             |
| Runtime  | `packages/core/src/Services/Admin/Settings/SettingsRuntimeApplier.php`                                        | Aplica idioma/fuso/SMTP/sessão ao `config()` no boot |
| UI       | `packages/core/src/Livewire/Admin/Configuracao/`                                                              | Shell (`ConfiguracaoSistema`) + uma `Aba*` por grupo |
| Gravação | `packages/core/src/Actions/Admin/Settings/Save*Action.php` + `packages/core/src/DTOs/Admin/Settings/*DTO.php` | Persistência transacional + activity log             |

Tudo, menos os defaults, vive em `ht2ml/core` (namespace `HT2ML\Core\...`). As settings
migrations ficam em `database/settings/` do app porque é o caminho que
`packages/core/config/settings.php` declara em `migrations_paths` (`database_path('settings')`).

Grupos disponíveis: **Empresa** (`general`), **Aparência** (`appearance` +
`branding`), **Tela de login** (`login`), **E-mail** (`email`), **Localização**
(`localizacao`), **Segurança** (`seguranca`), **Notificações** (`notificacoes`).
A navegação é uma **sidebar
vertical com busca** (`SettingsGroup::correspondeBusca()`, sem acento) e renderiza
apenas a aba ativa (troca server-side via `?aba=`). Há ainda a **Zona de perigo**
(`AbaDangerZone`) com ações destrutivas e reconfirmação por digitação.

Acesso protegido pela permissão `configuracoes.editar`.

---

## Aparência (Centro de Aparência)

A aba **Aparência** (`AbaAparencia`) unifica identidade, cores e layout, com
**preview ao vivo** no chrome real + painel de pré-visualização.

- **Logo/favicon**: enviados pela aba e guardados no disco `public`
  (`storage/app/public/branding`). Sem upload, usa o fallback de
  `packages/core/config/branding.php` (assets em `public/images`). Resolvidos por
  `BrandingService::logoUrl()`/`faviconUrl()`.
- **Cores → paleta**: `BrandingService::cssVariables()` deriva de 1 cor a escala
  tonal `--color-primary-50…950` (via `color-mix`) + `--color-<cor>-foreground`
  (texto de maior contraste WCAG). Injetadas em `<x-admin.branding-css>` no
  seletor `:root, html[data-skin]` **após o `@vite`** — vencem inclusive os skins,
  sem rebuild. Só hex `#RRGGBB` válido é emitido (proteção contra injeção de CSS).
- **Layout/tema server-driven** (`AppearanceSettings` + `AppearanceService`):
  tema padrão, skin, cores de topbar/menu, largura e tamanho do menu alimentam os
  atributos `data-*` do `<html>` (antes fixos) e o `theme-bootstrap`
  (`paraThemeConfig()`). O serviço é tolerante a falhas — cai no padrão sem banco
  (páginas de erro/instalação), como o `SettingsRuntimeApplier`.
- **Presets** (`HT2ML\Core\Enums\Admin\Appearance\ThemePreset` + `ThemePresetCatalog`):
  **Safira** (padrão de fábrica, azul `#1577ce`), **Grafite**, **Esmeralda** e **Violeta**,
  aplicáveis em 1 clique.
- **Preview ao vivo** (`resources/js/admin/appearance-preview.js`, exposto em
  `window.AppearancePreview`): replica a fórmula do `BrandingService` no cliente,
  aplica os `data-*` e injeta a paleta num `<style>`. **Aplicar** salva sem reload;
  **Descartar** restaura.

A identidade padrão da plataforma é neutra: `APP_NAME="HT2ML Platform"` no
`.env.example` (e o mesmo default em `config/app.php`), seed inicial e a migração
condicional `database/settings/2026_06_14_120000_paleta_padrao_da_plataforma.php` (que só
troca a paleta antiga, preservando customizações do cliente). Um produto define o próprio
nome no `.env` e no Setup Wizard. A **Zona de perigo**
restaura tudo ao padrão (`ResetarAparenciaAction`) ou expurga logs antigos
(`ExpurgarLogsAction`).

---

## Notificações

A aba **Notificações** (`AbaNotificacoes`) define a aparência/comportamento do feedback
visual da instância: **posição, duração, estilo (pílula/card) e quantidade máxima** dos
toasts, e a **posição das confirmações** (SweetAlert2). Persistido em `NotificacaoSettings`
e aplicado no frontend por `NotificacaoService::paraJsConfig()` (injetado pelo partial
`x-admin.partials.notification-config` em `window.__notificacaoConfig`), lido por
`resources/js/admin/toast.js` e `confirm.js`. A aba tem **preview ao vivo** (botões
"Testar"). O disparo de toasts no backend é centralizado no trait
`HT2ML\Core\Livewire\Concerns\EmiteNotificacoes` (`notificarSucesso/Erro/Aviso/Info` e
`notificarAposRedirect` para fluxos com redirect).

---

## Setup Wizard

Enquanto `GeneralSettings::instalado === false`, o middleware
`EnsureSystemConfigured` redireciona o painel para `/admin/setup`. O assistente
coleta empresa, marca e cria o primeiro super-admin (`ConcluirSetupAction`) e
então marca `instalado = true`. A disposição padrão do menu não é aplicada por
ninguém: ela é **declarada** em `packages/core/config/admin-menu.php` e nas configs das
extensões (ADR-0022), então a instalação nasce com `menu_personalizacoes` vazia
e cada linha ali passa a significar uma decisão humana.

- **Dev**: `migrate:fresh --seed` marca `instalado = true` (pula o wizard).
- **Cliente novo**: `migrate` sem `--seed` deixa `instalado = false` → o wizard roda.

---

## Como adicionar um novo grupo/aba

**Hoje não há ponto de extensão para isso.** É uma lacuna conhecida, registrada aqui para
ninguém tentar o caminho antigo.

O enum `SettingsGroup` (que define as abas, a ordem e as palavras-chave da busca) e o
`@switch` de `packages/core/resources/views/livewire/admin/configuracao/configuracao-sistema.blade.php`
(que renderiza a aba ativa) vivem no core. Nem o produto nem uma extensão os editam
([ADR-0022](architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)), e o core não tem um
canal como o `ModuleRegistry` para registrar uma aba de configuração.

O que dá para fazer hoje:

- **No produto:** uma classe de settings própria em `app/Settings/FooSettings.php`
  (estende `Spatie\LaravelSettings\Settings`, define `group()` e, se houver segredos,
  `encrypted()`). O spatie a descobre sozinho, porque `auto_discover_settings` aponta para
  `app_path('Settings')`. Os defaults vão numa settings migration em `database/settings/`
  semeando **todas** as propriedades (`php artisan make:settings-migration` ou manual) —
  senão o pacote lança `MissingSettings`. A tela de edição é uma página Livewire própria do
  produto, fora de `/admin/configuracoes`.
- **Numa extensão:** a descoberta automática não alcança o pacote (é a mesma família de
  descobertas que morrem num pacote, ver [`superficie-do-core.md`](superficie-do-core.md)).
  A configuração da extensão fica em `config/{chave}.php` publicável.
- **Uma aba nova em `/admin/configuracoes`** exige mudança no core: um PR na base que
  acrescente o case ao enum, a aba Livewire e o `@case` no shell, com teste em
  `tests/Feature/Admin/Configuracao/`. Se o pedido se repetir, o certo é o core ganhar um
  canal de registro de aba, como já existe para rotas, menu e permissões.

---

## Usando settings no código

```php
use HT2ML\Core\Settings\GeneralSettings;

$nome = app(GeneralSettings::class)->nome_cliente;
// ou via injeção de dependência no construtor/método.
```

> Nos testes, o sistema é considerado **instalado** por padrão (ver
> `tests/Pest.php`). Os testes do próprio wizard chamam `marcarInstalado(false)`.
