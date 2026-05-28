# Collapse

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/collapse.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-collapse-toggle`, `data-hs-collapse`)
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Componente de collapse/expand controlado pelo Preline. Um botão com `data-hs-collapse="#id"` alterna a visibilidade de um container `.hs-collapse`. Suporta labels dinâmicos (ex: "Read more" / "Read less") via classes utilitárias `hs-collapse-open:hidden` e `hs-collapse-open:block`.

> Usado principalmente para **filtros collapsáveis** (14.12 Formandos, 14.13 Parcelas) e **descrições "Read more"** em textos longos.

---

## Código Original (Inspinia — essência)

```html
{{-- Botão --}}
<button
    class="hs-collapse-toggle btn bg-primary hover:bg-primary-hover text-white"
    data-hs-collapse="#collapseExample"
    aria-controls="collapseExample"
    aria-expanded="false"
>
    Collapse Button
</button>

{{-- Conteúdo --}}
<div
    id="collapseExample"
    class="hs-collapse hidden w-full overflow-hidden transition-[height] duration-300"
    aria-labelledby="collapseLink"
>
    <div class="card border-light card-body border border-dashed">
        Conteúdo que aparece/some ao clicar no botão acima.
    </div>
</div>
```

### Label dinâmico (Read more / Read less)

```html
<button class="hs-collapse-toggle ..." data-hs-collapse="#show-hide-collapse">
    <span class="hs-collapse-open:hidden">Read more</span>
    <span class="hs-collapse-open:block hidden">Read less</span>
    <i class="iconify tabler--chevron-up hs-collapse-open:rotate-180"></i>
</button>
```

---

## Componente Blade Final

**Nome:** `<x-shared.collapse>`
**Arquivo:** `resources/views/components/shared/collapse.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/collapse.blade.php`

### Props

| Prop   | Tipo     | Obrigatório | Default | Descrição                                           |
| ------ | -------- | :---------: | ------- | --------------------------------------------------- |
| `id`   | `string` |     ✅      | —       | ID único do container                               |
| `open` | `bool`   |     ❌      | `false` | Se o collapse já deve vir aberto no primeiro render |

### Slots

| Slot              | Descrição                                                          |
| ----------------- | ------------------------------------------------------------------ |
| `$trigger`        | O botão/link que controla o collapse (opcional — pode ser externo) |
| `$slot` (default) | O conteúdo que aparece/some                                        |

### Código

```blade
{{-- resources/views/components/shared/collapse.blade.php --}}
@props ([
    'id',
    'open' => false,
])

@php
    $panelAttributes = $attributes->class([
        'hs-collapse',
        $open ? 'open' : 'hidden',
        'w-full overflow-hidden transition-[height] duration-300',
    ])->merge([
        'id' => $id,
    ]);
@endphp

@isset ($trigger)
    {{ $trigger }}
@endisset

<div {{ $panelAttributes }}> {{ $slot }}</div>
```

---

## Exemplos de Uso

### Básico com trigger interno

```blade
<x-shared.collapse id="detalhes-parcela">
    <x-slot:trigger>
        <x-shared.button
            class="hs-collapse-toggle"
            variant="default"
            appearance="ghost"
            data-hs-collapse="#detalhes-parcela"
            aria-controls="detalhes-parcela"
            aria-expanded="false"
        >
            Ver detalhes
            <i class="iconify tabler--chevron-down hs-collapse-open:rotate-180 transition-transform"></i>
        </x-shared.button>
    </x-slot:trigger>

    <div class="card border border-dashed card-body mt-2">
        <p>Histórico de tentativas de cobrança, boletos gerados, e-mails enviados.</p>
    </div>
</x-shared.collapse>
```

### Real (Portal ArtFinal — Filtros avançados 14.12)

```blade
<div class="mb-4">
    <button
        class="hs-collapse-toggle btn bg-primary text-white hover:bg-primary-hover"
        data-hs-collapse="#filtros-formandos"
        aria-controls="filtros-formandos"
    >
        <i class="iconify tabler--filter"></i> Filtros Avançados
        <i class="iconify tabler--chevron-down hs-collapse-open:rotate-180"></i>
    </button>
</div>

<x-shared.collapse id="filtros-formandos">
    <x-shared.card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-shared.select-search label="Contrato" wire:model.live="filtro.contrato_id" />
            <x-shared.select-search label="Curso" wire:model.live="filtro.curso_id" />
            <x-shared.select-search label="Período" wire:model.live="filtro.periodo_id" />
            <x-shared.date-range-picker label="Data Adesão" wire:model.live="filtro.data_adesao" />
            <x-shared.select label="Adimplência" wire:model.live="filtro.adimplencia">
                <option value="">Todos</option>
                <option value="em_dia">Em dia</option>
                <option value="inadimplente">Inadimplente</option>
            </x-shared.select>
        </div>
    </x-shared.card>
</x-shared.collapse>
```

---

## Quando Usar ✅

- Filtros avançados colapsáveis (14.12, 14.13)
- Seções de "Detalhes" ou "Ver mais" em cards
- Descrições longas que precisam ocultar/mostrar

## Quando NÃO Usar ❌

- Menus de navegação → usar accordion (`<x-shared.accordion>`) que agrupa múltiplos
- Modais → usar `<x-shared.modal>`
- Drawers laterais → usar `<x-admin.drawer>`

---

## Mapeamento no PRD

| Tela                                | Seção PRD   | Uso                           |
| ----------------------------------- | ----------- | ----------------------------- |
| Gestão Formandos (filtros)          | 14.12       | Filtros avançados colapsáveis |
| Gestão Financeira (filtros)         | 14.13       | Filtros collapsáveis no topo  |
| Ficha Formando (detalhes auditoria) | 14.12 Tab 7 | Expandir JSON before/after    |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P1 (Onda 2)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Preline obrigatório** — `hs-collapse-toggle`, `data-hs-collapse` e a classe `.hs-collapse` continuam sendo o contrato oficial
2. **Attributes forwarding** agora é aplicado via `ComponentAttributeBag`, mantendo `class`, `wire:*` e `data-*` no painel expandível
3. **`id` único obrigatório** — segue sendo a base para o acoplamento entre trigger e painel
4. **O slot `trigger` não ganha wrapper extra** na implementação final, evitando espaçamento inesperado em toolbars e headers
5. **Preview pronto:** acessar `/admin/dev/components/collapse` para validar uso com trigger interno e painel de filtros
