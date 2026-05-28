@props ([
    'fieldId' => null,
    'labelId' => null,
])

<div @if ($fieldId) data-password-meter-standalone="{{ $fieldId }}" @endif>
    <div class="bg-default-200 mt-2 h-1 overflow-hidden rounded-full">
        <div class="h-full w-0 transition-all duration-300" data-password-meter-bar></div>
    </div>
    <small
        class="text-default-400 mt-1 block text-xs"
        @if ($labelId) id="{{ $labelId }}" @endif
        data-password-meter-label
    >
        Digite uma senha
    </small>
</div>
