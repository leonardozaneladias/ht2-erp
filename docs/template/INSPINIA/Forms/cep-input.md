# CEP Input (+ ViaCEP)

**Categoria:** Form (masked + autocomplete)  
**Origem Inspinia:** `resources/views/form/other-plugin.blade.php`  
**Plugins JS:** Inputmask 5.0.9 + fetch nativo

---

## Descrição

Campo oficial para CEP com máscara pt-BR e busca ViaCEP no `blur`. A implementação consolidada deixou de usar Alpine inline: toda a lógica fica no helper `resources/js/admin/forms.js`, que dispara um evento customizado e opcionalmente chama um método Livewire configurado por prop.

---

## API Final

**Nome:** `<x-shared.cep-input>`  
**Arquivo:** `resources/views/components/shared/cep-input.blade.php`

### Props

| Prop        | Tipo      | Default      |
| ----------- | --------- | ------------ |
| `name`      | `string`  | —            |
| `label`     | `?string` | `CEP`        |
| `hint`      | `?string` | `null`       |
| `required`  | `bool`    | `false`      |
| `target`    | `?string` | `null`       |
| `eventName` | `string`  | `cep-filled` |

---

## Código Final Blade

```blade
@props ([
    'name',
    'label' => 'CEP',
    'hint' => null,
    'required' => false,
    'target' => null,
    'eventName' => 'cep-filled',
])

@php
    $cepConfig = [
        'target' => $target,
        'eventName' => $eventName,
    ];
@endphp

<div class="relative">
    <x-shared.input
        :name="$name"
        :label="$label"
        :hint="$hint"
        :required="$required"
        type="text"
        icon="tabler--map-pin"
        placeholder="00000-000"
        data-af-cep='@json($cepConfig)'
        data-af-inputmask='@json(["mask" => "99999-999", "clearIncomplete" => true])'
        class="font-mono"
        {{ $attributes }}
    />

    <span class="pointer-events-none absolute end-3 top-10 hidden" data-cep-loading>
        <i class="iconify tabler--loader-2 animate-spin text-default-400"></i>
    </span>
</div>
```

---

## Exemplos de Uso

```blade
<x-shared.cep-input name="cep" label="CEP" wire:model.live="form.cep" />
```

```blade
<x-shared.cep-input
    name="cep_residencial"
    target="preencherEnderecoResidencial"
    wire:model.live="form.cep_residencial"
/>
```

---

## Notas de Implementação

1. O helper faz fetch em `https://viacep.com.br/ws/{cep}/json/`.
2. Em sucesso, o campo dispara `cep-filled` com `logradouro`, `bairro`, `cidade`, `uf`, `cep` e `raw`.
3. Se `target` estiver configurado e Livewire estiver presente, o helper chama `component.call(target, detail)`.
4. O estado de carregamento é exibido pelo loader inline do próprio componente, sem Alpine.
5. Em falha ou CEP inexistente, o helper usa o sistema oficial de toast.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Média        |
| **Status**       | 🟢 Concluído |
