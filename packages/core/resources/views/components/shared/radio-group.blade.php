@props ([
    'name',
    'label' => null,
    'hint' => null,
    'inline' => false,
    'required' => false,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$errorKey}-hint" : null;
    $errorId = $hasError ? "{$errorKey}-error" : null;
    // Associa erro/hint ao grupo via aria-describedby no <fieldset> (mesmo padrão
    // do x-shared.input); sem isto os <small> ficam com id órfão e leitores de
    // tela não anunciam o erro/hint ao navegar pelos radios (WCAG 1.3.1/3.3.1/4.1.2).
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
@endphp

{{-- aria-required no <fieldset> comunica a obrigatoriedade do grupo de radios ao
     leitor de tela (sem o efeito colateral do required nativo, que num radio força
     uma escolha já no submit do browser, incompatível com a validação Livewire em
     pt-BR). O asterisco no legend é só visual — segue a convenção do required-indicator. --}}
<fieldset
    class="mb-4"
    @if ($required) aria-required="true" @endif
    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
>
    @if ($label)
        <legend class="form-label mb-2">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </legend>
    @endif

    <div @class (['flex gap-4', 'flex-col' => ! $inline, 'flex-row flex-wrap items-start' => $inline])>
        {{ $slot ?? '' }}
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</fieldset>
