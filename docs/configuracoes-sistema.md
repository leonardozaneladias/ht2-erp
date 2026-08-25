# Configurações do Sistema

Tela administrativa de configuração do sistema (`/admin/configuracoes`) e
assistente de primeira instalação (`/admin/setup`). Permite que cada cliente
ajuste seus dados, identidade visual e preferências sem tocar em código.

> Modelo **single-tenant**: cada cliente tem sua própria instalação; as settings
> guardam os dados desse único cliente.

---

## Arquitetura

Baseada em [`spatie/laravel-settings`](https://github.com/spatie/laravel-settings):
configurações são **classes PHP tipadas**, persistidas na tabela `settings` e
cacheadas.

| Camada   | Local                                                                             | Responsabilidade                                     |
| -------- | --------------------------------------------------------------------------------- | ---------------------------------------------------- |
| Settings | `app/Settings/*Settings.php`                                                      | Grupos tipados (fonte da verdade dos campos)         |
| Enum     | `app/Enums/Admin/SettingsGroup.php`                                               | Grupos + rótulos/ícones das abas                     |
| Defaults | `database/settings/*.php`                                                         | Valores de fábrica (settings migrations)             |
| Runtime  | `app/Services/Admin/Settings/SettingsRuntimeApplier.php`                          | Aplica idioma/fuso/SMTP/sessão ao `config()` no boot |
| UI       | `app/Livewire/Admin/Configuracao/`                                                | Shell (`ConfiguracaoSistema`) + uma `Aba*` por grupo |
| Gravação | `app/Actions/Admin/Settings/Save*Action.php` + `app/DTOs/Admin/Settings/*DTO.php` | Persistência transacional + activity log             |

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
  `config/branding.php` (assets HT2 em `public/images`). Resolvidos por
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
- **Presets** (`ThemePreset` + `ThemePresetCatalog`): HT2 ERP (padrão), Grafite,
  Esmeralda e Violeta, aplicáveis em 1 clique.
- **Preview ao vivo** (`resources/js/admin/appearance-preview.js`, exposto em
  `window.AppearancePreview`): replica a fórmula do `BrandingService` no cliente,
  aplica os `data-*` e injeta a paleta num `<style>`. **Aplicar** salva sem reload;
  **Descartar** restaura.

A identidade padrão do starter é **HT2 ERP**: `config/app.php`, seed inicial e a
migração condicional `database/settings/*_rebrand_ht2_defaults.php` (que só troca a
paleta Inspinia antiga, preservando customizações do cliente). A **Zona de perigo**
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
ninguém: ela é **declarada** em `config/admin-menu.php` e nas configs das
extensões (ADR-0022), então a instalação nasce com `menu_personalizacoes` vazia
e cada linha ali passa a significar uma decisão humana.

- **Dev**: `migrate:fresh --seed` marca `instalado = true` (pula o wizard).
- **Cliente novo**: `migrate` sem `--seed` deixa `instalado = false` → o wizard roda.

---

## Como adicionar um novo grupo/aba

1. **Settings class** em `app/Settings/FooSettings.php` (estende `Settings`,
   define `group()` e, se houver segredos, `encrypted()`).
2. **Enum**: adicione um case em `HT2ML\Core\Enums\Admin\SettingsGroup` (rótulo, ícone,
   descrição), inclua-o em `abas()` (ordem na navegação) e em `palavrasChave()`
   (termos para a busca).
3. **Defaults**: crie uma settings migration em `database/settings/` semeando
   **todas** as propriedades (`php artisan make:settings-migration` ou manual) —
   senão o pacote lança `MissingSettings`.
4. **DTO + Action** em `app/DTOs/Admin/Settings/` e `app/Actions/Admin/Settings/`.
5. **Componente** `app/Livewire/Admin/Configuracao/AbaFoo.php` + view, no padrão
   das abas existentes (`rules()`, `validate()`, `dispatch('toast', ...)`).
6. **Plugue no shell**: adicione um `@case('foo')` em
   `resources/views/livewire/admin/configuracao/configuracao-sistema.blade.php`.
7. (Opcional) Aplique em runtime no `SettingsRuntimeApplier`.
8. **Teste** em `tests/Feature/Admin/Configuracao/`.

---

## Usando settings no código

```php
use HT2ML\Core\Settings\GeneralSettings;

$nome = app(GeneralSettings::class)->nome_cliente;
// ou via injeção de dependência no construtor/método.
```

> Nos testes, o sistema é considerado **instalado** por padrão (ver
> `tests/Pest.php`). Os testes do próprio wizard chamam `marcarInstalado(false)`.
