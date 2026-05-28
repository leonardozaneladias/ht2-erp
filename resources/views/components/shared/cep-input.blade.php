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
    $inputmaskConfig = [
        'mask' => '99999-999',
        'clearIncomplete' => true,
    ];
@endphp

<div class="relative" data-af-cep-field>
    <x-shared.input
        :name="$name"
        :label="$label"
        :hint="$hint"
        :required="$required"
        type="text"
        icon="tabler--map-pin"
        placeholder="00000-000"
        :data-af-cep="\Illuminate\Support\Js::encode($cepConfig)"
        :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
        class="font-mono"
        {{ $attributes }}
    />

    <span class="pointer-events-none absolute end-3 top-10 hidden" data-cep-loading>
        <i class="iconify tabler--loader-2 text-default-400 animate-spin"></i>
    </span>
</div>
