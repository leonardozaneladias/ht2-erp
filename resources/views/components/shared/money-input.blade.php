@props ([
    'name',
    'label' => 'Valor',
    'prefix' => 'R$ ',
    'hint' => null,
    'required' => false,
])

@php
    // Extrai o nome da prop Livewire do atributo wire:model (ex: wire:model="preco" → "preco")
    $wireModelKey = collect($attributes->all())
        ->keys()
        ->first(fn(string $k) => str_starts_with($k, 'wire:model'));
    $livewireProp = $wireModelKey !== null ? $attributes->get($wireModelKey) : null;
@endphp

<div
    x-data="{
        cents: {{ $livewireProp !== null ? "\$wire.entangle('" . e($livewireProp) . "')" : '0' }},
        get display() {
            const v = typeof this.cents === 'number' ? this.cents : parseInt(this.cents) || 0;
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(v / 100);
        },
        handleInput(value) {
            const digits = value.replace(/\D/g, '');
            this.cents = digits.length ? parseInt(digits, 10) : 0;
        }
    }"
>
    <x-shared.input
        :name="$name"
        :label="$label"
        :hint="$hint"
        :required="$required"
        type="text"
        inputmode="numeric"
        :placeholder="$prefix . '0,00'"
        class="font-mono"
        x-bind:value="display"
        @input="handleInput($event.target.value);"
        {{ $attributes->except($wireModelKey !== null ? [$wireModelKey] : []) }}
    />
</div>
