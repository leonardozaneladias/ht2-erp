# Tooltip

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/tooltips.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-tooltip`)
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Tooltip contextual exibido ao passar o mouse. Implementação pura Preline — não precisa de Tippy.js ou similar. Suporta 4 placements (top, bottom, left, right) via CSS variable `[--placement:top]`.

> **Caso especial:** para UI complexas com tooltips em muitos elementos, considerar Alpine.js + CSS puro em vez de criar `<x-shared.tooltip>` — o JSX-like markup fica mais limpo para tooltips inline.

---

## Código Original (Inspinia — essência)

```html
<span class="hs-tooltip inline-block [--placement:top]">
    <button class="hs-tooltip-toggle text-primary" type="button">
        Texto ativador
        <span
            class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible bg-dark invisible absolute z-10 inline-block rounded px-3 py-2 text-sm font-medium text-white opacity-0 shadow-2xs transition-opacity"
            role="tooltip"
        >
            Conteúdo do tooltip
        </span>
    </button>
</span>
```

Placements alternativos: `[--placement:bottom]`, `[--placement:left]`, `[--placement:right]`.

---

## Componente Blade Proposto

**Nome:** `<x-shared.tooltip>`
**Arquivo:** `resources/views/components/shared/tooltip.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop        | Tipo     | Obrigatório | Default | Descrição                |
| ----------- | -------- | :---------: | ------- | ------------------------ |
| `content`   | `string` |     ✅      | —       | Texto do tooltip         |
| `placement` | `string` |     ❌      | `'top'` | top, bottom, left, right |

### Slots

- `$slot`: O elemento ativador (botão, ícone, texto)

### Código

```blade
{{-- resources/views/components/shared/tooltip.blade.php --}}
@props ([
    'content',
    'placement' => 'top',
])

<span class="hs-tooltip inline-block [--placement:{{ $placement }}]">
    <span class="hs-tooltip-toggle">
        {{ $slot }}
        <span
            class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible bg-dark invisible absolute z-10 inline-block rounded py-2 px-3 text-sm font-medium text-white opacity-0 shadow-2xs transition-opacity whitespace-nowrap"
            role="tooltip"
        >
            {{ $content }}
        </span>
    </span>
</span>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.tooltip content="Editar registro">
    <button class="btn btn-icon"><i class="iconify tabler--edit"></i></button>
</x-shared.tooltip>
```

### Real (Toggle do Contrato 14.4 com tooltip explicativo)

```blade
<div class="flex items-center gap-2">
    <x-shared.toggle wire:model="exige_responsavel_cadastro" label="Exige Responsável de Cadastro" />
    <x-shared.tooltip
        placement="right"
        content="Quando ativo, formandos menores de 18 anos obrigam o cadastro de um responsável adicional."
    >
        <i class="iconify tabler--info-circle text-default-400 cursor-help"></i>
    </x-shared.tooltip>
</div>
```

### Real (Contrato Tooltip em badge de programação 14.7)

```blade
<x-shared.tooltip
    content="Vigente de {{ $programacao->inicio->format('d/m/Y') }} a {{ $programacao->fim->format('d/m/Y') }}"
>
    <x-shared.badge variant="success">ATIVA</x-shared.badge>
</x-shared.tooltip>
```

---

## Quando Usar ✅

- Ícones `info-circle` ao lado de configs técnicas (14.4, 14.15)
- Botões icon-only em tabelas (explicar ação antes do clique)
- Abbreviações ou labels truncados

## Quando NÃO Usar ❌

- Conteúdo rico (imagens, forms) → usar `<x-shared.popover>` (parking lot)
- Tooltips em dispositivos touch → considerar abrir modal/drawer, tooltip hover não funciona em mobile
- Conteúdo essencial → tooltip é progressivo; informação crítica precisa estar visível

---

## Mapeamento no PRD

| Tela                             | Seção PRD | Uso                                                  |
| -------------------------------- | --------- | ---------------------------------------------------- |
| Contratos                        | 14.4      | Tooltip nos toggles "Exige Responsável"              |
| Produtos                         | 14.6      | Tooltip em "Grupo Exclusivo"                         |
| Configurações                    | 14.15     | Tooltip em "Margem de Dias" explicando comportamento |
| Qualquer tabela com icon buttons | —         | "Editar", "Ver", "Excluir"                           |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P1 (Onda 2)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Código Final Blade

**Arquivo:** `resources/views/components/shared/tooltip.blade.php`
**Preview:** `resources/views/admin/dev/components/tooltip.blade.php`

### API final consolidada

| Prop        | Tipo     | Default | Observação                       |
| ----------- | -------- | ------- | -------------------------------- |
| `content`   | `string` | —       | Texto do tooltip                 |
| `placement` | `string` | `top`   | `top`, `bottom`, `left`, `right` |

### Código

```blade
<x-shared.tooltip content="Editar registro" placement="right">
    <button type="button" class="btn btn-icon bg-light text-dark hover:text-primary">
        <i class="iconify tabler--edit text-base"></i>
    </button>
</x-shared.tooltip>
```

## Notas de Adaptação

1. **A decisão oficial foi tomada**: `x-shared.tooltip` passa a ser o padrão para tooltips textuais simples
2. **Preline obrigatório** — tooltips funcionam via `hs-tooltip-shown` que o Preline aplica no hover
3. **`whitespace-nowrap`** adicionado — tooltips com texto quebrado ficam estranhos
4. **`cursor-help`** no ativador é opcional mas recomendado quando o ativador é um ícone info
5. **Não usar em touch-only** — considere `role="button"` + modal para mobile
6. **`content` texto plano apenas** — tooltip HTML rico continua fora do escopo e não vira popover nesta fase
7. **Z-index controlado no componente final** para conviver melhor com cards e overlays
