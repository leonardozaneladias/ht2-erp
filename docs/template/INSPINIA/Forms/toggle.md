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
    name="exige_resp_cadastro"
    label="Exige Responsável de Cadastro"
    hint="Formandos menores de 18 anos precisam de responsável"
    wire:model.live="contrato.exige_resp_cadastro"
/>
```

---

## Mapeamento no PRD

| Tela                 | Uso                          |
| -------------------- | ---------------------------- |
| 14.3 Instituições    | Ativo                        |
| 14.4 Contratos Tab 2 | 4 toggles de responsáveis    |
| 14.5 Categorias      | Ativo                        |
| 14.6 Produtos        | Disponível na Adesão, Status |
| 14.7 Programações    | Status                       |
| 14.8 Condições       | Modalidade Híbrida           |
| 14.11 Termos         | Status                       |
| 14.15 Configurações  | Ajustar Fim de Mês           |
| 14.18 Usuários Admin | Ativo                        |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2 (Onda 3)  |
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

- props: `name`, `id`, `label`, `hint`, `checked`
- ajustes aplicados:
    - ganhou suporte a erro, `checked` inicial e `aria-invalid`
    - permaneceu sem JS extra, usando a semântica booleana esperada para o backoffice

### Exemplo final

```blade
<x-shared.toggle
    name="exige_responsavel"
    label="Exige responsável de cadastro"
    hint="Ative para contratos com menores de idade."
    checked
/>
```
