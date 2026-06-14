@props ([
    'name',
    'id' => null,
    'label',
    'hint' => null,
    'checked' => false,
    'stacked' => false,
    'required' => false,
    'onText' => 'Sim',
    'offText' => 'Não',
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $isChecked = old($errorKey) !== null ? (bool) old($errorKey) : ($checked || $attributes->has('checked'));
@endphp

<div class="mb-4">
    @if (! $stacked)
        {{-- Modo inline (default): switch + rótulo lado a lado. Usado em listas de configurações/menus. --}}
        <label class="group inline-flex cursor-pointer items-center gap-3" for="{{ $fieldId }}">
            <span class="relative inline-flex shrink-0">
                <input
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    type="checkbox"
                    value="1"
                    {{ $attributes->class(['peer sr-only']) }}
                    @checked ($isChecked)
                    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                />

                {{-- Trilho --}}
                <span
                    @class ([
                        'bg-default-300 peer-checked:bg-primary h-5 w-9 rounded-full transition-colors duration-200 ease-out',
                        'peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-1 peer-focus-visible:ring-offset-card',
                        'peer-disabled:opacity-50 peer-disabled:cursor-not-allowed',
                        'ring-1 ring-inset ring-black/5 peer-checked:ring-transparent',
                        'peer-aria-[invalid=true]:ring-danger/60' => $hasError,
                    ])
                ></span>

                {{-- Thumb --}}
                <span
                    class="pointer-events-none absolute start-0.5 top-1/2 size-4 -translate-y-1/2 rounded-full bg-white shadow-sm transition-transform duration-200 ease-out peer-checked:translate-x-4"
                ></span>
            </span>

            <span class="text-body-color text-sm font-medium select-none">{{ $label }}</span>
        </label>
    @else
        {{-- Modo stacked: rótulo no topo + controle de altura h-9.25, alinhado aos inputs do grid. --}}
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
        <label class="flex h-9.25 w-fit cursor-pointer items-center gap-3" for="{{ $fieldId }}">
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="checkbox"
                value="1"
                {{ $attributes->class(['peer sr-only']) }}
                @checked ($isChecked)
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            />

            {{-- Trilho com thumb via ::before (input é irmão → peer-checked alcança trilho e estado) --}}
            <span
                @class ([
                    'relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200 ease-out',
                    'bg-default-300 peer-checked:bg-primary',
                    "before:pointer-events-none before:absolute before:start-0.5 before:top-1/2 before:size-4 before:-translate-y-1/2 before:rounded-full before:bg-white before:shadow-sm before:transition-transform before:duration-200 before:ease-out before:content-['']",
                    'peer-checked:before:translate-x-4',
                    'peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-1 peer-focus-visible:ring-offset-card',
                    'peer-disabled:opacity-50 peer-disabled:cursor-not-allowed',
                    'ring-1 ring-inset ring-black/5 peer-checked:ring-transparent',
                    'peer-aria-[invalid=true]:ring-danger/60' => $hasError,
                ])
            ></span>

            {{-- Estado on/off via peer-checked. Os dois spans precisam ser irmãos diretos do
                 input (mesmo nível do trilho) para o seletor `peer` alcançá-los. --}}
            <span class="text-body-color text-sm font-medium select-none peer-checked:hidden">{{ $offText }}</span>
            <span class="text-body-color hidden text-sm font-medium select-none peer-checked:block">{{ $onText }}</span>
        </label>
    @endif

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
