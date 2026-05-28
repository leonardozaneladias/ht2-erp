# Sistema de Skins e Theme Bootstrap

**Categoria:** Layout (infraestrutura de tema)
**Origem Inspinia:** `resources/views/shared/partials/head-css.blade.php`
**Plugins JS:** Script inline auto-contido (~70 linhas)
**Plugins CSS:** `resources/css/config/_root.css` (CSS custom properties por skin)
**Documentação Inspinia:** `Docs/index.html` § "Layout" (config options)

---

## Descrição

Script crítico injetado no `<head>` **antes de qualquer render** que aplica o tema/skin/cor/tamanho do layout lendo atributos do `<html>` e/ou `sessionStorage`. Resolve o problema de FOUC (Flash Of Unstyled Content) ao carregar a página já com o tema correto. Inclui também auto-detecção do modo dark via `prefers-color-scheme` quando `theme="system"` e auto-fallback para `offcanvas` em viewports abaixo de 1140px.

---

## Conceitos do Sistema

### 1. Atributos de configuração no `<html>`

Todos os aspectos visuais são **data-attributes** no `<html>`:

| Atributo               | Valores possíveis                                                                                                          | Controla                                  |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| `dir`                  | `ltr` \| `rtl`                                                                                                             | Direção do layout                         |
| `data-theme`           | `light` \| `dark` \| `system`                                                                                              | Modo claro/escuro                         |
| `data-skin`            | `default` \| `minimal` \| `modern` \| `material` \| `saas` \| `flat` \| `galaxy` \| `luxe` \| `retro` \| `neon` \| `pixel` | 11 skins (Portal ArtFinal usa `default`)  |
| `data-layout-width`    | `fluid` \| `boxed`                                                                                                         | Largura do container                      |
| `data-layout-position` | `fixed` \| `scrollable`                                                                                                    | Topbar/sidenav fixos ou scrolláveis       |
| `data-topbar-color`    | `light` \| `dark` \| `gray` \| `gradient`                                                                                  | Cor da topbar                             |
| `data-menu-color`      | `light` \| `dark` \| `gray` \| `gradient` \| `image`                                                                       | Cor do sidenav                            |
| `data-sidenav-size`    | `default` \| `compact` \| `condensed` \| `on-hover` \| `on-hover-active` \| `offcanvas`                                    | Tamanho/comportamento                     |
| `data-sidenav-user`    | presente ou ausente                                                                                                        | Mostra card do usuário no topo do sidenav |

### 2. Persistência

O script salva a config em `sessionStorage.__THEME_CONFIG__` como JSON. Ao recarregar a página:

1. Lê `sessionStorage` primeiro
2. Se vazio, lê atributos do `<html>`
3. Se ausente, usa `defaultConfig` hardcoded
4. Aplica os atributos de volta no `<html>` antes de qualquer render

### 3. Responsividade automática

```js
if (window.innerWidth <= 1140) {
    size = 'offcanvas';
}
html.setAttribute('data-sidenav-size', size);
```

Em telas pequenas (< 1140px), **força** sidenav offcanvas, mesmo que a config salva seja `default`. A config original permanece preservada no storage.

---

## Código Original (Inspinia)

```html
<script>
    (function () {
        const html = document.documentElement;
        const storageKey = '__THEME_CONFIG__';
        const savedConfig = sessionStorage.getItem(storageKey);

        // Default config
        const defaultConfig = {
            dir: 'ltr',
            skin: 'default',
            theme: 'light',
            width: 'fluid',
            position: 'fixed',
            orientation: 'vertical',
            'sidenav-size': 'default',
            'sidenav-user': true,
            'topbar-color': 'light',
            'sidenav-color': 'dark',
        };

        function getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        const htmlConfig = {
            dir: html.getAttribute('dir') || defaultConfig.dir,
            skin: html.getAttribute('data-skin') || defaultConfig.skin,
            theme:
                html.getAttribute('data-theme') === 'system'
                    ? getSystemTheme()
                    : html.getAttribute('data-theme') ||
                      (defaultConfig['theme'] === 'system' ? getSystemTheme() : defaultConfig['theme']),
            'topbar-color': html.getAttribute('data-topbar-color') || defaultConfig['topbar-color'],
            'sidenav-color': html.getAttribute('data-menu-color') || defaultConfig['sidenav-color'],
            'sidenav-size': html.getAttribute('data-sidenav-size') || defaultConfig['sidenav-size'],
            'sidenav-user': html.hasAttribute('data-sidenav-user') || defaultConfig['sidenav-user'],
            position: html.getAttribute('data-layout-position') || defaultConfig['position'],
            width: html.getAttribute('data-layout-width') || defaultConfig['width'] || false,
        };

        window.defaultConfig = structuredClone(htmlConfig);

        let config = savedConfig ? JSON.parse(savedConfig) : htmlConfig;
        window.config = config;

        html.setAttribute('dir', config.dir);
        html.setAttribute('data-skin', config.skin);
        html.setAttribute('data-theme', config.theme);
        html.setAttribute('data-topbar-color', config['topbar-color']);
        html.setAttribute('data-menu-color', config['sidenav-color']);
        html.setAttribute('data-layout-position', config['position']);
        html.setAttribute('data-layout-width', config['width']);

        if (config['sidenav-user'] === true) {
            html.setAttribute('data-sidenav-user', 'true');
        } else {
            html.removeAttribute('data-sidenav-user');
        }

        if (config['sidenav-size']) {
            let size = config['sidenav-size'];
            if (window.innerWidth <= 1140) {
                size = 'offcanvas';
            }
            html.setAttribute('data-sidenav-size', size);
        }
    })();
</script>
@vite(["resources/js/vendor.js"]) @vite(["resources/js/app.js"])
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.partials.theme-bootstrap>`
**Arquivo view:** `resources/views/components/admin/partials/theme-bootstrap.blade.php`
**Classe PHP:** Blade anônimo — sem classe
**Tipo:** Blade anônimo (sem props — conteúdo fixo)

### Props

Nenhuma. O script é auto-contido e lê dos atributos do `<html>` já renderizados pelo layout master.

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/partials/theme-bootstrap.blade.php --}}
{{-- Script crítico de tema — DEVE ficar no <head> antes de qualquer CSS/JS para evitar FOUC --}}
<script>
    (function () {
        const html = document.documentElement;
        const storageKey = '__ARTFINAL_ADMIN_THEME__';
        const savedConfig = sessionStorage.getItem(storageKey);

        const defaultConfig = {
            dir: 'ltr',
            skin: 'default',
            theme: 'light',
            width: 'fluid',
            position: 'fixed',
            orientation: 'vertical',
            'sidenav-size': 'default',
            'sidenav-user': true,
            'topbar-color': 'light',
            'sidenav-color': 'dark',
        };

        function getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        const htmlConfig = {
            dir: html.getAttribute('dir') || defaultConfig.dir,
            skin: html.getAttribute('data-skin') || defaultConfig.skin,
            theme:
                html.getAttribute('data-theme') === 'system'
                    ? getSystemTheme()
                    : html.getAttribute('data-theme') || defaultConfig.theme,
            'topbar-color': html.getAttribute('data-topbar-color') || defaultConfig['topbar-color'],
            'sidenav-color': html.getAttribute('data-menu-color') || defaultConfig['sidenav-color'],
            'sidenav-size': html.getAttribute('data-sidenav-size') || defaultConfig['sidenav-size'],
            'sidenav-user': html.hasAttribute('data-sidenav-user') || defaultConfig['sidenav-user'],
            position: html.getAttribute('data-layout-position') || defaultConfig.position,
            width: html.getAttribute('data-layout-width') || defaultConfig.width,
        };

        window.defaultConfig = structuredClone(htmlConfig);

        const config = savedConfig ? JSON.parse(savedConfig) : htmlConfig;
        window.config = config;

        html.setAttribute('dir', config.dir);
        html.setAttribute('data-skin', config.skin);
        html.setAttribute('data-theme', config.theme);
        html.setAttribute('data-topbar-color', config['topbar-color']);
        html.setAttribute('data-menu-color', config['sidenav-color']);
        html.setAttribute('data-layout-position', config.position);
        html.setAttribute('data-layout-width', config.width);

        if (config['sidenav-user'] === true) {
            html.setAttribute('data-sidenav-user', 'true');
        } else {
            html.removeAttribute('data-sidenav-user');
        }

        if (config['sidenav-size']) {
            let size = config['sidenav-size'];
            if (window.innerWidth <= 1140) {
                size = 'offcanvas';
            }
            html.setAttribute('data-sidenav-size', size);
        }
    })();
</script>
```

---

## API JavaScript pública

Após a execução, o script deixa disponível em `window`:

```js
window.config; // objeto mutável com a config atual
window.defaultConfig; // snapshot da config inicial (somente leitura)
```

### Alternar tema light/dark em runtime (helper)

```js
// resources/js/admin.js
export function toggleTheme() {
    const current = window.config.theme;
    const next = current === 'dark' ? 'light' : 'dark';
    window.config.theme = next;
    document.documentElement.setAttribute('data-theme', next);
    sessionStorage.setItem('__ARTFINAL_ADMIN_THEME__', JSON.stringify(window.config));
}

// Bind no botão da topbar
document.getElementById('light-dark-mode')?.addEventListener('click', toggleTheme);
```

---

## Exemplos de Uso

### Exemplo Básico (já integrado ao layout master)

```blade
{{-- dentro de resources/views/components/admin/layout.blade.php, no <head> --}}
<x-admin.partials.theme-bootstrap />
@vite (['resources/css/admin.css', 'resources/js/admin.js'])
```

### Exemplo: Admin força dark mode por padrão

```blade
<html lang="pt-BR" data-theme="dark" ...>
```

O script detecta `data-theme="dark"` já aplicado e salva no sessionStorage. Usuário pode alternar depois via botão da topbar.

### Exemplo: Respeitar `prefers-color-scheme` do sistema operacional

```blade
<html lang="pt-BR" data-theme="system" ...>
```

O script detecta `system`, lê `window.matchMedia('(prefers-color-scheme: dark)')` e aplica dark/light conforme o tema do SO.

---

## Quando Usar ✅

- No `<head>` do layout master do admin (`<x-admin.layout>`), **ANTES** do `@vite` de CSS
- No `<head>` do layout de auth do admin (`<x-admin.auth-layout>`) se quisermos tema persistente nas telas de login
- No `<head>` do layout do portal do formando SE quisermos dark mode lá (decisão pendente)

## Quando NÃO Usar ❌

- Fora do `<head>` — precisa executar antes do body para evitar FOUC
- Depois do `@vite` — o CSS pode carregar com `data-theme` ainda não aplicado
- Em iframes/documentos isolados (PDF de termos, preview de e-mail) — não faz sentido

## Boas Práticas 💡

- **storageKey único por aplicação:** usamos `__ARTFINAL_ADMIN_THEME__` em vez de `__THEME_CONFIG__` para não conflitar com outros apps no mesmo domínio
- **Nunca definir `data-theme` no servidor via PHP** — o script no cliente sobrescreve. Deixar o `<html>` com o `data-theme` padrão e deixar o script cuidar da persistência
- **Debounce em toggles rápidos:** alternar tema 10x em 1s pode causar flicker. Debounce no click do botão
- **Migrate sessão → localStorage** se quisermos persistência entre abas/janelas (atualmente é por aba)

---

## Mapeamento no PRD (Portal ArtFinal)

| Tela                    | Seção PRD | Como É Usado                                                       | Sprint |
| ----------------------- | --------- | ------------------------------------------------------------------ | :----: |
| Todas as telas do admin | 14.\*     | Aplicado no layout master via `<x-admin.partials.theme-bootstrap>` | 15–16  |
| Login Admin             | 14.1      | Se usar layout auth com tema, incluir aqui também                  |   15   |

---

## Classificação

| Critério                   | Valor                        |
| -------------------------- | ---------------------------- |
| **Vai usar no projeto**    | 🟢 Sim                       |
| **Prioridade**             | P0 (pré-requisito do layout) |
| **Sprint planejada**       | 15–16                        |
| **Complexidade**           | Simples (copiar/adaptar)     |
| **Status componentização** | 🟢 Concluído                 |

---

## Dependências

| Tipo                        | Item                                                                                                                                                                          |
| --------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Depende de (JS)**         | Nenhum — script vanilla                                                                                                                                                       |
| **Depende de (CSS)**        | CSS do Inspinia com seletores por `data-theme`, `data-skin`, `data-menu-color`, `data-topbar-color`, `data-sidenav-size` — precisa ser copiado para `resources/css/admin.css` |
| **Usado por (telas)**       | Todas — via `<x-admin.layout>`                                                                                                                                                |
| **Usado por (componentes)** | `<x-admin.layout>` apenas                                                                                                                                                     |

---

## Notas de Adaptação

1. **storageKey:** mantido como `__THEME_CONFIG__` para permanecer compatível com o `LayoutCustomizer` já existente em `resources/js/app.js`
2. **Skins expostos:** Portal ArtFinal usa apenas `default`. Os demais 10 skins ficam no parking lot — o CSS deles pode ser removido do bundle final para reduzir payload
3. **Apenas vertical orientation:** `data-layout-orientation="horizontal"` não será suportado — o CSS relacionado pode ser removido
4. **Remover exposição ao usuário final:** o admin do ArtFinal não terá customizer visível. Usuário não pode trocar skin nem cores, só alternar dark/light via botão simples da topbar
5. **CSS custom properties:** o script apenas seta atributos — o **CSS correspondente** (em `config/_root.css` + `structure/*.css` do Inspinia) é quem transforma os atributos em cores reais. **CRITICAL:** copiar esses CSS para `resources/css/admin/` ou manter sob `resources/vendor/inspinia/css/`
6. **Fallback 1140px:** o breakpoint está hardcoded — se o projeto mudar breakpoints do Tailwind (ex: `xl: 1280px`), considerar unificar
7. **Dark mode do portal:** decisão pendente — portal do formando é mobile-first com identidade própria. Não usar este script lá; fazer um `<x-portal.partials.theme-bootstrap>` simplificado se necessário

---

## Changelog do Componente

| Data       | Descrição                                                                                                                        |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-11 | Doc criada — Fase 2 Onda 1                                                                                                       |
| 2026-04-11 | Implementação concluída — partial compatível com `resources/js/admin.js` e adapter em `admin/partials/theme-bootstrap.blade.php` |

## Código Final Blade

Implementação consolidada em:

- `resources/views/components/admin/partials/theme-bootstrap.blade.php`
- `resources/views/admin/partials/theme-bootstrap.blade.php` (adapter)

O código final mantém o bootstrap original do Inspinia, mas sem embutir `@vite` dentro da partial e preservando o `storageKey` compartilhado com o `LayoutCustomizer`.
