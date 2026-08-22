{{--
    Editor rich text (Quill, tema snow) com toolbar mínima. O HTML é mantido num
    <textarea> oculto que carrega o name/wire:model (fonte para POST e Livewire).
    O JS (initRichEditors em resources/js/admin/forms.js) sincroniza Quill -> textarea.

    wire:ignore protege a árvore do Quill dos morphs do Livewire; o wire:model
    segue funcionando porque o JS dispara o evento `input` no textarea.

    Segurança: o HTML deve ser sanitizado no servidor (App\Support\Html\HtmlSanitizer)
    antes de persistir e ao exibir — a toolbar restrita não substitui isso.
--}}

@props ([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
    'placeholder' => 'Escreva a mensagem...',
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $val = (string) old($errorKey, $value ?? '');
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" id="{{ $fieldId }}-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    <div
        wire:ignore
        data-af-quill
        data-af-quill-config="{{ \Illuminate\Support\Js::encode(['placeholder' => $placeholder]) }}"
        @if ($label) data-af-quill-labelledby="{{ $fieldId }}-label" @endif
        @class (['af-quill', 'border-danger! rounded' => $hasError])
    >
        <div data-af-quill-editor></div>
        {{-- O conteúdo do <textarea> é o VALOR do campo, e é whitespace-significativo:
             quebrar a linha antes de {{ $val }} injeta newline + indentação no valor.
             Este arquivo está no .prettierignore por isso — o formatador não é
             idempotente aqui e reintroduzia o bug a cada passada. --}}
        <textarea
            id="{{ $fieldId }}"
            name="{{ $name }}"
            data-af-quill-input
            class="hidden"
            {{ $attributes }}
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        >{{ $val }}</textarea>
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
