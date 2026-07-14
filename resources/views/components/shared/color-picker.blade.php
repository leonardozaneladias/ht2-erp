{{--
    Seletor de cor: swatch nativo (<input type="color">) + hex ao lado. Sem
    dependência extra. O valor (#RRGGBB) é mantido num <input hidden> que carrega
    o name/wire:model (pode ser vazio quando clearable). O Alpine espelha swatch
    <-> hex <-> hidden e dispara `input` para o Livewire captar.

    clearable=true: o campo é opcional (ex.: cores que "herdam o tema" quando em
    branco) — mostra um botão limpar e o estado "herda do tema".
    Passe :value com o valor atual para o render inicial.
--}}

@props ([
    'name',
    'id' => null,
    'label' => null,
    'value' => '#000000',
    'hint' => null,
    'required' => false,
    'clearable' => false,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $raw = trim((string) old($errorKey, $value));
    // Não-clearable: vazio cai no preto. Clearable: vazio permanece vazio (= tema).
    $val = $raw !== '' ? $raw : ($clearable ? '' : '#000000');
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    <div
        x-data="{ value: @js($val) }"
        @class ([
            'flex w-full max-w-xs items-center gap-3 rounded-lg border p-2',
            'border-default-300' => ! $hasError,
            'border-danger!' => $hasError,
        ])
    >
        <input
            type="color"
            id="{{ $fieldId }}"
            value="{{ $val !== '' ? $val : '#ffffff' }}"
            :value="value || '#ffffff'"
            x-on:input="
                value = $event.target.value;
                $refs.bound.value = value;
                $refs.bound.dispatchEvent(new Event('input', { bubbles: true }));
            "
            class="size-9 shrink-0 cursor-pointer rounded border-0 bg-transparent p-0"
            aria-label="{{ $label ?? 'Cor' }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        />

        <span class="text-body-color font-mono text-sm uppercase" x-show="value" x-text="value">{{ $val }}</span>
        <span class="text-default-400 text-sm" x-show="!value" x-cloak>herda do tema</span>

        @if ($clearable)
            <button
                type="button"
                x-show="value"
                x-cloak
                @click="
                    value = '';
                    $refs.bound.value = '';
                    $refs.bound.dispatchEvent(new Event('input', { bubbles: true }));
                "
                class="text-default-400 hover:text-danger hover:bg-light ms-auto flex size-5 items-center justify-center rounded-full transition-colors"
                aria-label="Limpar cor (herda do tema)"
            >
                <i class="iconify tabler--x text-sm" aria-hidden="true"></i>
            </button>
        @endif

        {{-- O aria-describedby (erro/hint) vai no <input type="color"> focável acima,
             não aqui: o hidden não é focável nem anunciado por leitores de tela. --}}
        <input type="hidden" x-ref="bound" value="{{ $val }}" {{ $attributes }} />
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
