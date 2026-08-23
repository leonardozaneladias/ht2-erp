@props ([
    'name',
    'id' => null,
    'label' => null,
    'accept' => 'image/*',
    'maxSize' => 2,
    'multiple' => false,
    'preview' => null,
    'mode' => 'livewire',
    // 'card' = área tracejada grande (padrão); 'compact' = thumbnail + botão
    // (para logos/favicons em grids densos, ex.: branding).
    'variant' => 'card',
    'uploadUrl' => null,
    'hint' => null,
    'required' => false,
    // Textos da zona no modo dropzone (defaults preservam o comportamento atual).
    'title' => null,
    'description' => null,
    // Tamanho da zona no modo dropzone: 'md' (padrão) ou 'lg' — hero para telas
    // cuja ação principal é o upload (importações, cargas em massa).
    'size' => 'md',
    // Barra de progresso do upload temporário do Livewire (eventos livewire-upload-*).
    'progress' => true,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $mode = in_array($mode, ['livewire', 'dropzone'], true) ? $mode : 'livewire';
    $size = in_array($size, ['md', 'lg'], true) ? $size : 'md';
    // Pré-computado para a tag <i> caber numa linha só (o FileUploadTest casa
    // '<i class="iconify tabler--cloud-upload' por regex de linha única).
    $zoneIconSize = $size === 'lg' ? 'text-3xl' : 'text-2xl';
    $dropzoneConfig = [
        'accept' => $accept,
        'maxSize' => $maxSize,
        'multiple' => $multiple,
        'uploadUrl' => $uploadUrl,
    ];
    $fileConfig = [
        'accept' => $accept,
        'maxSize' => $maxSize,
        'multiple' => $multiple,
        'mode' => $mode,
        'uploadUrl' => $uploadUrl,
        'hasPreview' => filled($preview),
    ];
@endphp

<div
    class="mb-4"
    data-af-file-upload
    data-af-file-mode="{{ $mode }}"
    data-af-file="{{ \Illuminate\Support\Js::encode($fileConfig) }}"
    @if ($mode === 'dropzone') data-af-dropzone="{{ \Illuminate\Support\Js::encode($dropzoneConfig) }}" @endif
    @if ($progress)
        {{-- Upload temporário do Livewire: os eventos borbulham do input até aqui. --}}
        x-data="{ enviando: false, progresso: 0 }"
        x-on:livewire-upload-start="
            enviando = true;
            progresso = 0;
        "
        x-on:livewire-upload-progress="progresso = $event.detail.progress;"
        x-on:livewire-upload-finish="enviando = false;"
        x-on:livewire-upload-error="enviando = false;"
        x-on:livewire-upload-cancel="enviando = false;"
    @endif
>
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    @if ($mode === 'dropzone')
        <input
            id="{{ $fieldId }}"
            name="{{ $multiple ? $name.'[]' : $name }}"
            type="file"
            class="hidden"
            data-af-dropzone-input
            accept="{{ $accept }}"
            @if ($multiple) multiple @endif
            @required ($required && ! $uploadUrl)
            {{ $attributes }}
        />
        {{-- wire:ignore: o Dropzone é dono deste DOM (chips, estados de drag) — o
             morph do Livewire apagaria os previews a cada re-render. O input fica
             fora (o Livewire precisa re-bindar o wire:model nele). --}}
        <div wire:ignore>
            <div
                data-af-dropzone
                @class ([
                    'border-default-300 hover:border-primary hover:bg-primary/5 group cursor-pointer rounded-xl border-2 border-dashed transition',
                    'min-h-37.5 p-5' => $size === 'md',
                    'min-h-64 p-8' => $size === 'lg',
                    'border-danger!' => $hasError,
                ])
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            >
                <div class="dz-message needsclick my-8 text-center">
                    <div class="mb-base mx-auto {{ $size === 'lg' ? 'size-14' : 'size-11' }}">
                        <span
                            data-af-dz-zone-icon
                            @class ([
                                'bg-primary/10 text-primary group-hover:-translate-y-0.5 inline-flex items-center justify-center rounded-full',
                                'size-11' => $size === 'md',
                                'size-14' => $size === 'lg',
                            ])
                        >
                            <i class="iconify tabler--cloud-upload {{ $zoneIconSize }}" aria-hidden="true"></i>
                        </span>
                    </div>
                    <h4 class="text-body-color mb-2 font-medium {{ $size === 'lg' ? 'text-xl' : 'text-lg' }}">
                        {{ $title ?? 'Arraste arquivos aqui ou clique para enviar' }}
                    </h4>
                    @if (filled($description))
                        <p class="text-default-400 mb-2 text-sm">{{ $description }}</p>
                    @else
                        <p class="text-default-400 mb-2 text-sm italic">
                            {{ $multiple ? 'Você pode enviar vários arquivos de uma vez.' : 'Envie um arquivo.' }}
                        </p>
                    @endif
                    <p class="text-default-400 text-xs">Máx. {{ $maxSize }}MB {{ $accept !== '*/*' ? '• '.$accept : '' }}</p>
                </div>
            </div>
            <div class="dropzone-previews mt-4 space-y-2" data-af-dropzone-previews></div>
            <template data-af-dropzone-template>
                {{-- O Dropzone insere o chip como DOM novo — a entrada anima 1x. --}}
                <div
                    class="border-default-200 bg-card animate-fade-up rounded-xl border shadow-sm motion-reduce:animate-none"
                >
                    <div class="flex items-center gap-3 p-3">
                        <img class="hidden size-10 shrink-0 rounded-lg object-cover" data-dz-thumbnail src="#" alt="" />
                        <span
                            class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-lg"
                            data-af-dz-icon
                        >
                            <i class="iconify tabler--file-description text-xl" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-body-color truncate text-sm font-medium" data-dz-name></p>
                            <p class="text-default-400 text-xs" data-dz-size></p>
                            <div class="dz-error-message text-danger mt-0.5 text-xs" data-dz-errormessage></div>
                        </div>
                        <button
                            type="button"
                            class="text-default-400 hover:text-danger transition"
                            data-dz-remove
                            aria-label="Remover arquivo"
                        >
                            <i class="iconify tabler--x text-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
        @if ($progress)
            {{-- template x-if: sem Alpine na página nada é renderizado (preview dev). --}}
            <template x-if="enviando">
                <div class="mt-3">
                    <div class="bg-light/60 h-1.5 w-full overflow-hidden rounded-full">
                        <div
                            class="bg-primary h-full rounded-full transition-all duration-300"
                            :style="`width: ${progresso}%`"
                        ></div>
                    </div>
                    <p class="text-default-400 mt-1 text-xs" x-text="`Enviando… ${progresso}%`"></p>
                </div>
            </template>
        @endif
    @elseif ($variant === 'compact')
        {{-- Thumbnail + botão (mesmos data-af-file-* do modo card; só muda o layout). --}}
        <div
            data-af-file-trigger
            class="group border-default-300 hover:border-primary flex cursor-pointer items-center gap-4 rounded-lg border p-2 transition {{ $hasError ? 'border-danger!' : '' }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        >
            <input
                id="{{ $fieldId }}"
                name="{{ $multiple ? $name.'[]' : $name }}"
                type="file"
                class="hidden"
                data-af-file-input
                accept="{{ $accept }}"
                @if ($multiple) multiple @endif
                @required ($required)
                {{ $attributes }}
            />

            <div class="bg-light/40 flex h-14 w-24 shrink-0 items-center justify-center overflow-hidden rounded-md">
                <img
                    src="{{ $preview }}"
                    alt="Preview"
                    class="max-h-full max-w-full object-contain @if (blank($preview)) hidden @endif"
                    data-af-file-preview-image
                />
                <span class="text-default-400 @if (filled($preview)) hidden @endif" data-af-file-preview-icon>
                    <i class="iconify tabler--photo text-xl" aria-hidden="true"></i>
                </span>
            </div>

            <div class="min-w-0 grow">
                <span
                    class="text-primary group-hover:text-primary-600 inline-flex items-center gap-1 text-sm font-medium"
                >
                    <i class="iconify tabler--upload text-base" aria-hidden="true"></i>
                    Selecionar arquivo
                </span>
                <p class="text-default-400 truncate text-xs" data-af-file-name>
                    {{ filled($preview) ? 'Arquivo atual' : '' }}
                </p>
                <p class="text-default-400 text-xs">Máx. {{ $maxSize }}MB</p>
            </div>
        </div>
    @else
        <div
            data-af-file-trigger
            class="cursor-pointer rounded-2xl border-2 border-dashed border-default-300 bg-light/30 p-6 text-center transition hover:border-primary hover:bg-primary/5 {{ $hasError ? 'border-danger!' : '' }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        >
            <input
                id="{{ $fieldId }}"
                name="{{ $multiple ? $name.'[]' : $name }}"
                type="file"
                class="hidden"
                data-af-file-input
                accept="{{ $accept }}"
                @if ($multiple) multiple @endif
                @required ($required)
                {{ $attributes }}
            />

            <div data-af-file-empty @class (['hidden' => filled($preview)])>
                <span class="bg-info/15 text-info inline-flex size-11 items-center justify-center rounded-full">
                    <i class="iconify tabler--cloud-upload text-2xl" aria-hidden="true"></i>
                </span>
                <p class="text-body-color mt-3 text-sm font-medium">Clique para selecionar um arquivo</p>
                <p class="text-default-400 mt-1 text-xs">Máx. {{ $maxSize }}MB {{ $accept !== '*/*' ? '• '.$accept : '' }}</p>
            </div>

            <div data-af-file-preview @class (['hidden' => blank($preview)])>
                <img
                    src="{{ $preview }}"
                    alt="Preview"
                    class="mx-auto max-h-32 rounded-lg object-cover @if(blank($preview)) hidden @endif"
                    data-af-file-preview-image
                />
                <span
                    class="inline-flex size-11 items-center justify-center rounded-full bg-primary/10 text-primary @if(filled($preview)) hidden @endif"
                    data-af-file-preview-icon
                >
                    <i class="iconify tabler--file text-2xl" aria-hidden="true"></i>
                </span>
                <p class="text-body-color mt-3 text-sm font-medium" data-af-file-name>{{ filled($preview) ? 'Arquivo atual' : '' }}</p>
            </div>

            <ul class="text-default-500 mt-4 hidden space-y-2 text-left text-sm" data-af-file-list></ul>
        </div>
    @endif

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
