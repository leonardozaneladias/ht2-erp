# Spinner

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/spinners.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind (`animate-spin`)

---

## Descrição

Indicador de carregamento circular (border spinner). Usa `animate-spin` do Tailwind + `border-{color}` + `border-t-transparent` para criar o efeito de rotação. Acessibilidade via `role="status"` + `aria-label="loading"` + `<span class="sr-only">`.

---

## Código Original (Inspinia — essência)

```html
<!-- Básico -->
<div
    aria-label="loading"
    class="border-primary inline-block size-8 animate-spin rounded-full border-3 border-t-transparent"
    role="status"
>
    <span class="sr-only">Loading...</span>
</div>
```

O template tem também variantes "grow spinner" (não-border, pulsante) — menos usadas, parking lot.

---

## Componente Blade Proposto

**Nome:** `<x-shared.spinner>`
**Arquivo:** `resources/views/components/shared/spinner.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop      | Tipo     | Obrigatório | Default           | Descrição                                                        |
| --------- | -------- | :---------: | ----------------- | ---------------------------------------------------------------- |
| `variant` | `string` |     ❌      | `'primary'`       | Cor (primary, success, danger, etc.)                             |
| `size`    | `string` |     ❌      | `'md'`            | xs (size-3), sm (size-4), md (size-6), lg (size-8), xl (size-12) |
| `label`   | `string` |     ❌      | `'Carregando...'` | Label acessível                                                  |

### Código

```blade
{{-- resources/views/components/shared/spinner.blade.php --}}
@props ([
    'variant' => 'primary',
    'size' => 'md',
    'label' => 'Carregando...',
])

@php
    $sizeClass = match($size) {
        'xs' => 'size-3 border-2',
        'sm' => 'size-4 border-2',
        'lg' => 'size-8 border-3',
        'xl' => 'size-12 border-4',
        default => 'size-6 border-[3px]',
    };
@endphp

<div
    {{ $attributes->class([
        "border-{$variant}",
        'inline-block animate-spin rounded-full border-t-transparent',
        $sizeClass,
    ]) }}
    role="status"
    aria-label="{{ $label }}"
>
    <span class="sr-only">{{ $label }}</span>
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.spinner />
<x-shared.spinner variant="success" size="lg" />
<x-shared.spinner variant="danger" size="xs" />
```

### Real (Loading em Livewire)

```blade
<div class="flex items-center gap-2" wire:loading wire:target="salvar">
    <x-shared.spinner size="sm" />
    <span class="text-sm text-default-500">Salvando...</span>
</div>
```

### Real (Placeholder de tabela durante carregamento)

```blade
<div wire:loading.delay wire:target="$refresh" class="flex items-center justify-center py-12">
    <x-shared.spinner size="lg" />
</div>
```

---

## Quando Usar ✅

- `wire:loading` de Livewire em áreas pequenas (inline, ao lado de texto)
- Estado inicial de tabelas pesadas (antes dos dados carregarem)
- Dentro de botões de submit → **NÃO** — usar `<x-shared.loading-button>` que integra Ladda

## Quando NÃO Usar ❌

- Substituto de progress bar para operações determinísticas (upload, etc.) → usar `<x-shared.progress-bar>`
- Dentro de botão de submit → usar `<x-shared.loading-button>` (tem animação Ladda integrada)
- Loading de página inteira → usar skeleton (parking lot: `ui/placeholders.blade.php`)

---

## Mapeamento no PRD

| Tela              | Seção PRD | Uso                      |
| ----------------- | --------- | ------------------------ |
| Qualquer Livewire | —         | `wire:loading` indicator |
| 14.2 Dashboard    | 14.2      | Gráficos durante fetch   |
| 14.3–14.13        | —         | Listagens durante filtro |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P1 (Onda 2)  |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **`border-[3px]`** é Tailwind arbitrary — alternativa é adicionar `border-3` ao config
2. **Mapa explícito de variantes** — a implementação final usa um mapa fixo de classes (`primary`, `success`, `danger`, etc.) para evitar dependência de safelist dinâmica
3. **`sr-only`** obrigatório para screen readers
4. **Variante "grow"** (pulsing circle) do Inspinia vai para parking lot — border é mais usado e elegante
5. **Não confundir com `loading-button`** — este é só o visual, sem interatividade

---

## Código Final Blade

**Arquivo:** `resources/views/components/shared/spinner.blade.php`
**Preview:** `resources/views/admin/dev/components/spinner.blade.php`

### API final consolidada

| Prop      | Tipo     | Default         | Observação                                                                                 |
| --------- | -------- | --------------- | ------------------------------------------------------------------------------------------ |
| `variant` | `string` | `primary`       | `default`, `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark` |
| `size`    | `string` | `md`            | `xs`, `sm`, `md`, `lg`, `xl`                                                               |
| `label`   | `string` | `Carregando...` | Texto acessível via `aria-label` + `sr-only`                                               |

### Observações de implementação

- o componente final é um Blade anônimo trivial, sem dependência JS
- as espessuras de borda variam junto com o tamanho para manter leitura visual consistente
- o preview cobre variantes de cor, tamanhos e dois casos reais de loading
