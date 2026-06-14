# Toggle Switch

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** `.form-switch` do Inspinia (ou utilitários Tailwind)

---

## Descrição

Switch on/off estilo iOS. Alternativa a checkbox para estados booleanos (ativo/inativo, exige/não exige, habilitar/desabilitar). Mais visível que checkbox tradicional.

---

## Código Original (Inspinia)

```html
<label class="flex cursor-pointer items-center gap-2">
    <input type="checkbox" class="form-switch" />
    <span>Ativo</span>
</label>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.toggle>`
**Arquivo:** `resources/views/components/shared/toggle.blade.php`

### Props

| Prop    | Tipo      | Default | Descrição                 |
| ------- | --------- | ------- | ------------------------- |
| `name`  | `string`  | —       | —                         |
| `label` | `string`  | —       | Label à direita do switch |
| `hint`  | `?string` | `null`  | —                         |

### Código

```blade
{{-- resources/views/components/shared/toggle.blade.php --}}
@props ([
    'name',
    'label',
    'hint' => null,
])

<div class="mb-4">
    <label class="flex items-center gap-2 cursor-pointer">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="checkbox"
            {{ $attributes->class(['form-switch']) }}
            value="1"
        />
        <span>{{ $label }}</span>
    </label>
    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs ms-8">{{ $hint }}</small>
    @endif
</div>
```

---

## Exemplos de Uso

```blade
<x-shared.toggle name="ativo" label="Ativo" wire:model="form.ativo" />

<x-shared.toggle
    name="exige_aprovacao"
    label="Exige Aprovação"
    hint="Registros marcados precisam de aprovação manual"
    wire:model.live="pedido.exige_aprovacao"
/>
```

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Classe `.form-switch`** vem do Inspinia — ou usar utilitários Tailwind puros (peer checked:bg-primary, etc.)
2. **`value="1"`** — garante que o checkbox envie `1` quando marcado
3. **Alternativa pura Tailwind:**
    ```html
    <label class="relative inline-flex cursor-pointer items-center">
        <input type="checkbox" class="peer sr-only" />
        <div
            class="bg-default-200 peer peer-checked:bg-primary h-6 w-11 rounded-full after:absolute after:start-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"
        ></div>
        <span class="ms-3">{{ $label }}</span>
    </label>
    ```

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/toggle.blade.php`
- **Preview visual:** `/admin/dev/components/toggle`

### API final

- props: `name`, `id`, `label`, `hint`, `checked`, `stacked`, `required`, `onText`, `offText`
- ajustes aplicados:
    - ganhou suporte a erro, `checked` inicial e `aria-invalid`
    - permaneceu sem JS extra, usando a semântica booleana esperada para o backoffice
    - **modo `stacked`** (default `false`): renderiza o rótulo no topo (`.form-label`) e o switch
      dentro de um controle de altura `h-9.25`, alinhado verticalmente aos `x-shared.input`/`select`
      vizinhos quando o toggle está num **grid de formulário**. Sem `stacked`, mantém o layout inline
      (switch + rótulo lado a lado) usado em listas de configurações/menus.
    - `required` exibe o `*` no rótulo (paridade com `x-shared.input`); `onText`/`offText` (default
      `Sim`/`Não`) mostram o estado on/off ao lado do switch no modo `stacked`, via `peer-checked`
      (puro CSS, sem Alpine).

### Quando usar `stacked`

| Contexto                                                 | Modo             |
| -------------------------------------------------------- | ---------------- |
| Toggle dentro de um `grid` ao lado de inputs (form CRUD) | `stacked`        |
| Lista vertical de preferências (Configurações, Menus)    | default (inline) |

### Exemplo final

```blade
{{-- Em listas de configurações (inline) --}}
<x-shared.toggle
    name="exige_aprovacao"
    label="Exige aprovação"
    hint="Ative para registros que precisam de revisão manual."
    checked
/>

{{-- Dentro de um grid de formulário (alinhado aos demais campos) --}}
<x-shared.toggle name="ativo" label="Empresa ativa" wire:model="ativo" stacked onText="Ativa" offText="Inativa" />
```
