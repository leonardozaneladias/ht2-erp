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

<x-shared.select-search
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
</x-shared.select-search>
