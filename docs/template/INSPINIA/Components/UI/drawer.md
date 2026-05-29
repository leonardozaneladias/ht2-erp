# Drawer (Offcanvas)

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/offcanvas.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-overlay`, `data-hs-overlay`)
**Plugins CSS:** Classes `hs-overlay-open:*` do Preline

---

## Descrição

Painel lateral deslizante para formulários rápidos, filtros avançados e previews. Ocupa lateral da tela (normalmente direita), com backdrop e scroll lock no body. Diferente de `<x-shared.modal>` (centralizado), drawer é **lateral** e mantém contexto da página visível atrás. Suporta 4 posições (start, end, top, bottom), scroll opcional no body, backdrop opcional.

---

## Código Original (Inspinia — essência)

```html
<!-- Botão que abre -->
<button
    type="button"
    class="btn bg-primary text-white"
    data-hs-overlay="#drawer-pedido"
    aria-controls="drawer-pedido"
    aria-expanded="false"
>
    Abrir Drawer
</button>

<!-- O drawer -->
<div
    id="drawer-pedido"
    class="hs-overlay hs-overlay-open:translate-x-0 bg-card border-default-300 fixed start-0 top-0 z-80 hidden h-full w-full max-w-sm -translate-x-full transform border-e transition-all duration-300"
    role="dialog"
    tabindex="-1"
    aria-labelledby="drawer-pedido-label"
>
    <div class="flex items-center justify-between border-b p-5">
        <h3 id="drawer-pedido-label">Título</h3>
        <button type="button" data-hs-overlay="#drawer-pedido" aria-label="Close">
            <i class="iconify tabler--x text-xl"></i>
        </button>
    </div>
    <div class="p-5">Conteúdo do drawer</div>
</div>
```

### Variações de posição

- **Start (left, padrão):** `start-0 -translate-x-full` + `hs-overlay-open:translate-x-0`
- **End (right):** `end-0 translate-x-full` + `hs-overlay-open:translate-x-0`
- **Top:** `top-0 -translate-y-full h-auto w-full` + `hs-overlay-open:translate-y-0`
- **Bottom:** `bottom-0 translate-y-full h-auto w-full` + `hs-overlay-open:translate-y-0`

---

## Componente Blade Final

**Nome:** `<x-admin.drawer>`
**Arquivo:** `resources/views/components/admin/drawer.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/drawer.blade.php`

### Props

| Prop         | Tipo      | Obrigatório | Default | Descrição                                                                                  |
| ------------ | --------- | :---------: | ------- | ------------------------------------------------------------------------------------------ |
| `id`         | `string`  |     ✅      | —       | ID único (usado por `data-hs-overlay="#id"`)                                               |
| `title`      | `?string` |     ❌      | `null`  | Título no header                                                                           |
| `position`   | `string`  |     ❌      | `'end'` | start, end, top, bottom                                                                    |
| `size`       | `string`  |     ❌      | `'md'`  | Em `start/end`, controla largura (`max-w-*`). Em `top/bottom`, controla altura (`max-h-*`) |
| `bodyScroll` | `bool`    |     ❌      | `false` | Permitir scroll do body quando drawer aberto                                               |
| `backdrop`   | `bool`    |     ❌      | `true`  | Mostrar overlay escuro atrás                                                               |

### Slots

| Slot              | Descrição                           |
| ----------------- | ----------------------------------- |
| `$slot` (default) | Conteúdo principal                  |
| `$footer`         | Footer fixo no fim (botões de ação) |

### Código

```blade
{{-- resources/views/components/admin/drawer.blade.php --}}
@props ([
    'id',
    'title' => null,
    'position' => 'end',
    'size' => 'md',
    'bodyScroll' => false,
    'backdrop' => true,
])

@php
    $position = in_array($position, ['start', 'end', 'top', 'bottom'], true) ? $position : 'end';
    $size = in_array($size, ['sm', 'md', 'lg', 'xl', 'full'], true) ? $size : 'md';

    $widthSizes = [
        'sm' => 'max-w-xs',
        'md' => 'max-w-sm',
        'lg' => 'max-w-md',
        'xl' => 'max-w-lg',
        'full' => 'max-w-full',
    ];

    $heightSizes = [
        'sm' => 'max-h-60',
        'md' => 'max-h-80',
        'lg' => 'max-h-[32rem]',
        'xl' => 'max-h-[40rem]',
        'full' => 'max-h-screen',
    };

    $positionClasses = match ($position) {
        'start' => 'start-0 top-0 h-full w-full -translate-x-full border-e',
        'top' => 'inset-x-0 top-0 size-full -translate-y-full border-b',
        'bottom' => 'inset-x-0 bottom-0 size-full translate-y-full border-t',
        default => 'end-0 top-0 h-full w-full translate-x-full border-s',
    };

    $sizeClass = in_array($position, ['top', 'bottom'], true)
        ? $heightSizes[$size]
        : $widthSizes[$size];

    $dialogAttributes = $attributes
        ->class(collect([
            'hs-overlay',
            'hs-overlay-open:translate-x-0',
            'hs-overlay-open:translate-y-0',
            'bg-card border-default-300 fixed z-80 hidden transform transition-all duration-300',
            $positionClasses,
            $sizeClass,
            $bodyScroll ? '[--body-scroll:true]' : null,
            ! $backdrop ? '[--overlay-backdrop:false]' : null,
        ])->filter()->all())
        ->merge([
            'id' => $id,
            'role' => 'dialog',
            'tabindex' => '-1',
            'aria-modal' => $backdrop ? 'true' : 'false',
            'aria-labelledby' => $title ? "{$id}-label" : null,
            'aria-label' => $title ? null : 'Painel lateral',
        ]);
@endphp

<div {{ $dialogAttributes }}>
    <div class="flex h-full flex-col">
        @if ($title)
            <div class="border-default-300 flex items-center justify-between gap-3 border-b p-5">
                <div class="min-w-0">
                    <h3 id="{{ $id }}-label" class="truncate text-lg font-semibold text-body-color">{{ $title }}</h3>
                </div>

                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-full opacity-70 transition hover:bg-light hover:opacity-100"
                    aria-label="Fechar"
                    data-hs-overlay="#{{ $id }}"
                >
                    <i class="iconify tabler--x text-xl"></i>
                </button>
            </div>
        @endif

        <div class="grow overflow-y-auto p-5">{{ $slot }}</div>

        @isset ($footer)
            <div class="border-default-300 shrink-0 border-t p-5">{{ $footer }}</div>
        @endisset
    </div>
</div>
```

---

## Exemplos de Uso

### Abrir com botão simples

```blade
<x-shared.button data-hs-overlay="#drawer-categoria"> Nova Categoria </x-shared.button>

<x-admin.drawer id="drawer-categoria" title="Nova Categoria">
    <form wire:submit="salvar">
        <x-shared.input label="Nome" wire:model="nome" />
        <x-shared.textarea label="Descrição" wire:model="descricao" />
        <x-shared.toggle label="Ativo" wire:model="ativo" />

        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-shared.button variant="default" appearance="outline" data-hs-overlay="#drawer-categoria">
                    Cancelar
                </x-shared.button>
                <x-shared.loading-button variant="primary" type="submit"> Salvar </x-shared.loading-button>
            </div>
        </x-slot:footer>
    </form>
</x-admin.drawer>
```

### Real (Drawer de Filtros Avançados)

```blade
<x-shared.button variant="default" appearance="outline" icon="tabler--filter" data-hs-overlay="#drawer-filtros">
    Filtros Avançados
</x-shared.button>

<x-admin.drawer id="drawer-filtros" title="Filtros Avançados" size="lg">
    <div class="space-y-4">
        <x-shared.select-search label="Categoria" wire:model.live="filtros.categoria_id" />
        <x-shared.select-search label="Produto" wire:model.live="filtros.produto_id" />
        <x-shared.select-search label="Cliente" wire:model.live="filtros.cliente_id" />
        <x-shared.date-range-picker label="Data do Pedido" wire:model.live="filtros.data_pedido" />
        <x-shared.select label="Situação" wire:model.live="filtros.situacao">
            <option value="">Todos</option>
            <option value="em_dia">Em dia</option>
            <option value="em_atraso">Em atraso</option>
        </x-shared.select>
    </div>

    <x-slot:footer>
        <div class="flex justify-between">
            <x-shared.button variant="default" appearance="ghost" wire:click="limparFiltros"> Limpar </x-shared.button>
            <x-shared.button variant="primary" data-hs-overlay="#drawer-filtros"> Aplicar </x-shared.button>
        </div>
    </x-slot:footer>
</x-admin.drawer>
```

---

## Quando Usar ✅

- Formulários curtos que não precisam de página dedicada
- Filtros avançados que ocupam espaço demais inline
- Preview de detalhes sem sair da listagem
- Form rápido "Novo X" sem redirect

## Quando NÃO Usar ❌

- Formulários longos com múltiplas sections → usar página dedicada
- Alertas/confirmações → usar `<x-shared.modal>` ou `<x-shared.confirm-dialog>`
- Conteúdo efêmero → usar `<x-shared.toast>`

---

## Classificação

| Critério         | Valor                                       |
| ---------------- | ------------------------------------------- |
| **Vai usar**     | 🟢 Sim                                      |
| **Complexidade** | Média (muitas posições, attributes Preline) |
| **Status**       | 🟢 Concluído                                |

---

## Notas de Adaptação

1. **`data-hs-overlay`** continua obrigatório no trigger e no botão de fechar — dependência explícita de Preline
2. **`size` agora é contextual** — largura em `start/end`, altura em `top/bottom`, o que aproxima a API do comportamento real do template
3. **Fallback acessível:** quando `title` não é informado, o componente usa `aria-label="Painel lateral"`
4. **Footer fixo** permanece via `shrink-0`, com corpo em `grow overflow-y-auto` para rolagem interna correta
5. **Position `start`/`end`** respeita RTL; em pt-BR o default `end` continua sendo a lateral direita
6. **`[--body-scroll:true]`** e `:backdrop="false"` já estão cobertos na implementação final
7. **Preview pronto:** acessar `/admin/dev/components/drawer` para validar drawer lateral, drawer de filtros e variação inferior
