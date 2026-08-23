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
    // O entangle é deferido por padrão (não gasta um round-trip por dígito). Com
    // `wire:model.live`, o servidor precisa acompanhar a digitação — é o que permite, por
    // exemplo, a linha do tempo mostrar a variação salarial enquanto o valor é digitado.
    $entangle = $livewireProp !== null
        ? "\$wire.entangle('" . e($livewireProp) . "')" . (str_contains((string) $wireModelKey, '.live') ? '.live' : '')
        : '0';
@endphp

<div
    x-data="{
        cents: {{ $entangle }},
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
