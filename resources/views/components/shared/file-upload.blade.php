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
>
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <span class="text-danger">*</span>
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
        <div
            data-af-dropzone
            class="min-h-37.5 rounded-lg border-2 border-dashed border-default-300 p-5 transition hover:border-primary {{ $hasError ? 'border-danger!' : '' }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        >
            <div class="dz-message needsclick my-8 text-center">
                <div class="mb-base mx-auto size-11">
                    <span class="bg-info/15 text-info inline-flex size-11 items-center justify-center rounded-full">
                        <i class="iconify tabler--cloud-upload text-2xl"></i>
                    </span>
                </div>
                <h4 class="mb-3 text-lg">Arraste arquivos aqui ou clique para enviar</h4>
                <p class="text-default-400 mb-2 italic">
                    {{ $multiple ? 'Você pode enviar vários arquivos.' : 'Envie um arquivo com preview rico.' }}
                </p>
                <p class="text-default-400 text-xs">Máx. {{ $maxSize }}MB {{ $accept !== '*/*' ? '• '.$accept : '' }}</p>
            </div>
        </div>
        <div class="dropzone-previews mt-5" data-af-dropzone-previews></div>
        <template data-af-dropzone-template>
            <div class="border-default-300 bg-card mt-1 rounded-xl border border-dashed">
                <div class="flex items-center gap-3 p-3">
                    <img class="size-10 rounded object-cover" data-dz-thumbnail src="#" alt="Preview" />
                    <div class="min-w-0 flex-1">
                        <a class="text-primary block truncate font-semibold" data-dz-name></a>
                        <p class="text-default-400 text-xs" data-dz-size></p>
                    </div>
                    <button type="button" class="text-danger" data-dz-remove>
                        <i class="iconify tabler--x text-lg"></i>
                    </button>
                </div>
            </div>
        </template>
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
                    <i class="iconify tabler--photo text-xl"></i>
                </span>
            </div>

            <div class="min-w-0 grow">
                <span
                    class="text-primary group-hover:text-primary-600 inline-flex items-center gap-1 text-sm font-medium"
                >
                    <i class="iconify tabler--upload text-base"></i>
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
                    <i class="iconify tabler--cloud-upload text-2xl"></i>
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
                    <i class="iconify tabler--file text-2xl"></i>
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
