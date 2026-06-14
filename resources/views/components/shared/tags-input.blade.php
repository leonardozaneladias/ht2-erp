@props ([
    'name',
    'id' => null,
    'label' => null,
    'options' => null,
    'value' => [],
    'placeholder' => 'Adicionar item...',
    'hint' => null,
    'required' => false,
    'searchable' => false,
    'allowCreate' => false,
    'maxItems' => null,
])

{{--
    Chips/tags com entrada livre. Continua sobre Choices.js (x-shared.choices-select)
    enquanto o x-shared.combobox nao tem um modo "chips" — por isso NAO delega
    mais ao x-shared.select-search, que ja migrou para o combobox (sem chips).
--}}

<x-shared.choices-select
    :name="$name"
    :id="$id"
    :label="$label"
    :options="$options"
    :value="$value"
    :placeholder="$placeholder"
    :hint="$hint"
    :required="$required"
    :searchable="$searchable"
    :allow-create="$allowCreate"
    :max-items="$maxItems"
    multiple
    remove-item
    {{ $attributes }}
>
    {{ $slot ?? '' }}
</x-shared.choices-select>
