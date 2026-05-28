# File Upload

**Categoria:** Form  
**Origem Inspinia:** `resources/views/form/fileuploads.blade.php`  
**Plugins JS:** Dropzone 6 beta + helper próprio para modo simples

---

## Descrição

O escopo oficial do projeto foi consolidado em um único componente `x-shared.file-upload` com dois modos:

- `livewire`: upload simples, preview leve e integração direta com `wire:model`
- `dropzone`: drag-and-drop rico com preview em lista

Assim preservamos as duas necessidades sem criar dois componentes concorrentes.

---

## API Final

**Nome:** `<x-shared.file-upload>`  
**Arquivo:** `resources/views/components/shared/file-upload.blade.php`

### Props

| Prop        | Tipo      | Default    |
| ----------- | --------- | ---------- | --- |
| `name`      | `string`  | —          |
| `id`        | `?string` | `null`     |
| `label`     | `?string` | `null`     |
| `accept`    | `string`  | `image/*`  |
| `maxSize`   | `int      | float`     | `2` |
| `multiple`  | `bool`    | `false`    |
| `preview`   | `?string` | `null`     |
| `mode`      | `string`  | `livewire` |
| `uploadUrl` | `?string` | `null`     |
| `hint`      | `?string` | `null`     |
| `required`  | `bool`    | `false`    |

---

## Código Final Blade

```blade
@props ([
    'name',
    'id' => null,
    'label' => null,
    'accept' => 'image/*',
    'maxSize' => 2,
    'multiple' => false,
    'preview' => null,
    'mode' => 'livewire',
    'uploadUrl' => null,
    'hint' => null,
    'required' => false,
])

<div class="mb-4" data-af-file-upload data-af-file-mode="{{ $mode }}">
    @if ($mode === 'dropzone')
        <div data-af-dropzone class="rounded-lg border-2 border-dashed border-default-300 p-5">
            <div class="dz-message my-8 text-center needsclick">
                <i class="iconify tabler--cloud-upload text-2xl"></i>
            </div>
        </div>
        <div class="mt-5 dropzone-previews" data-af-dropzone-previews></div>
        <template data-af-dropzone-template>...</template>
    @else
        <div
            data-af-file-trigger
            class="cursor-pointer rounded-2xl border-2 border-dashed border-default-300 p-6 text-center"
        >
            <input id="{{ $id ?? $name }}" type="file" class="hidden" data-af-file-input {{ $attributes }} />
            <div data-af-file-empty>...</div>
            <div data-af-file-preview class="hidden">...</div>
        </div>
    @endif
</div>
```

---

## Exemplos de Uso

```blade
<x-shared.file-upload
    name="logo"
    label="Logo da instituição"
    accept="image/png,image/jpeg"
    :preview="$instituicao?->logo_url"
    wire:model="logo"
/>
```

```blade
<x-shared.file-upload
    name="anexos"
    label="Anexos"
    mode="dropzone"
    multiple
    accept=".pdf,.jpg,.png"
    upload-url="{{ route('admin.uploads.temp') }}"
/>
```

---

## Notas de Implementação

1. `mode="livewire"` é o padrão e cobre o fluxo simples do admin, com validação de tamanho no cliente e preview de um ou vários arquivos.
2. `mode="dropzone"` ativa o boot via `Dropzone` em `resources/js/admin/forms.js`.
3. Quando `upload-url` não é informado, o modo `dropzone` mantém um `input[type=file]` oculto sincronizado, permitindo submit padrão do form sem perder os arquivos.
4. O preview simples não usa Alpine; a UI é atualizada pelo helper JS do projeto.
5. A validação server-side continua obrigatória nos dois modos.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Média        |
| **Status**       | 🟢 Concluído |
