# Footer (Admin)

**Categoria:** Navigation
**Origem Inspinia:** `resources/views/shared/partials/footer.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classe `.footer` do Inspinia
**Documentação Inspinia:** Não há seção dedicada

---

## Descrição

Rodapé simples do admin, fixo no fim da área `content-page`. Grid 2 colunas responsivo com copyright à esquerda e info extra à direita (visível apenas em `md+`). Não contém links, nem menu, nem newsletter — é apenas branding e crédito.

---

## Preview Visual

```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│   content-page area                                      │
│                                                          │
├──────────────────────────────────────────────────────────┤
│ © 2026 {{ config('app.name') }}        v1.0.0 · Todos direitos │
└──────────────────────────────────────────────────────────┘
```

- **Mobile:** copyright centralizado, info da direita escondida
- **Desktop (md+):** grid 2 colunas, copyright à esquerda, info à direita

---

## Código Original (Inspinia)

```html
<!-- Footer Start -->
<footer class="footer">
    <div class="container-fluid">
        <div class="gap-base grid grid-cols-1 md:grid-cols-2">
            <div class="text-center md:text-start">
                <script>
                    document.write(new Date().getFullYear());
                </script>
                © Inspinia - By
                <span class="font-semibold">WebAppLayers</span>
            </div>
            <div class="hidden md:block md:text-end">
                10GB of
                <span class="font-bold">250GB</span>
                Free.
            </div>
        </div>
    </div>
</footer>
<!-- Footer End -->
```

### Anti-pattern a corrigir

- **`<script>document.write(new Date().getFullYear())</script>`** — `document.write` é obsoleto e causa reflow. Usar PHP server-side: `{{ date('Y') }}`.
- **Link "10GB of 250GB Free"** — frase sem sentido do template, remover.

---

## Componente Blade Proposto

**Nome:** `<x-admin.footer>`
**Arquivo view:** `resources/views/components/admin/footer.blade.php`
**Classe PHP:** Blade anônimo — sem classe
**Tipo:** Blade anônimo

### Props

Nenhuma. Conteúdo fixo.

### Slots

Nenhum.

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/footer.blade.php --}}
<footer class="footer">
    <div class="container-fluid">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-base">
            <div class="text-center md:text-start">
                © {{ date('Y') }} {{ config('app.name') }} · Todos os direitos reservados.
            </div>
            <div class="md:text-end hidden md:block">
                <span class="text-default-500">v{{ config('app.version', '1.0.0') }}</span>
                <span class="mx-2 text-default-300">·</span>
                <span
                    >Feito com <i class="iconify tabler--heart-filled text-danger text-sm"></i> pela
                    equipe</span
                >
            </div>
        </div>
    </div>
</footer>
```

### Setup de `config('app.version')`

```php
// config/app.php
'version' => env('APP_VERSION', '1.0.0'),
```

```env
# .env
APP_VERSION=1.2.3
```

O pipeline de deploy pode atualizar `APP_VERSION` a partir do git tag.

---

## Exemplos de Uso

### Exemplo Básico (via layout master)

```blade
<x-admin.layout title="Dashboard">
    {{-- footer é incluído automaticamente após @slot --}}
</x-admin.layout>
```

Incluído automaticamente dentro de `<div class="content-page">` pelo `<x-admin.layout>`.

---

## Quando Usar ✅

- Em todas as views do admin autenticado — via `<x-admin.layout>`

## Quando NÃO Usar ❌

- Telas de auth (login, 404) — sem rodapé para manter foco no formulário
- Modais e drawers — sem rodapé dentro de modais
- PDFs gerados — não faz sentido

## Boas Práticas 💡

- **`date('Y')` no servidor:** evita `document.write`, melhora performance e é i18n-friendly
- **`config('app.version')`:** expor a versão ajuda o suporte a identificar bugs por versão
- **Não transformar em "mega footer":** se precisar de links/contatos/políticas, criar variante `<x-admin.footer-full>` em vez de complicar este. KISS.
- **Não colocar links de LGPD/Termos aqui:** o admin não precisa desse tipo de link no rodapé

---

## Classificação

| Critério                   | Valor        |
| -------------------------- | ------------ |
| **Vai usar no projeto**    | 🟢 Sim       |
| **Complexidade**           | Trivial      |
| **Status componentização** | 🟢 Concluído |

---

## Dependências

| Tipo                        | Item                                                              |
| --------------------------- | ----------------------------------------------------------------- |
| **Depende de (JS)**         | Nenhum                                                            |
| **Depende de (CSS)**        | Classe `.footer` do Inspinia (define padding, borda superior, bg) |
| **Depende de (Laravel)**    | `config('app.version')` opcional                                  |
| **Usado por (views)**       | Todas as views admin                                              |
| **Usado por (componentes)** | `<x-admin.layout>`                                                |

---

## Notas de Adaptação

1. **Substituir `document.write`** por `{{ date('Y') }}` server-side
2. **Remover "10GB of 250GB Free"** — frase sem sentido do template
3. **Substituir `Inspinia - By WebAppLayers`** por `{{ config('app.name') }} · Todos os direitos reservados`
4. **Adicionar versão da app** à direita — útil para suporte
5. **`gap-base`** é uma classe custom do Inspinia (equivale a `gap-4` ou `gap-6` do Tailwind puro). Confirmar valor em `config/_root.css`
6. **Dark mode:** a classe `.footer` do Inspinia já tem variante dark — não precisamos adicionar classes `dark:` manuais
7. **Considerar footer por ambiente** (dev/staging/prod): mostrar badge "STAGING" ou "DEV" quando `app()->environment() !== 'production'`

## Código Final Blade

Implementação consolidada em `resources/views/components/admin/footer.blade.php`.

Principais ajustes aplicados no código final:

- `document.write` foi removido em favor de `now()->year`
- o rodapé agora expõe `config('app.version')`
- ambientes não produtivos mostram badge de contexto (`LOCAL`, `STAGING`, etc.)

---

## Changelog do Componente

| Data       | Descrição   |
| ---------- | ----------- |
| 2026-04-11 | Doc criada  |
