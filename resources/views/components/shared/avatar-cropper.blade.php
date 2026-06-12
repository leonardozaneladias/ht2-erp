@props ([
    // Propriedade Livewire que recebe o arquivo recortado ($wire.$upload).
    'model',
    // Nome do usuário (fallback de iniciais do x-shared.avatar).
    'name' => '',
    // URL do avatar atual persistido (null => iniciais).
    'current' => null,
    // temporaryUrl() do upload pendente (preview antes do submit).
    'pending' => null,
    // Tamanho do avatar exibido.
    'size' => 'size-16',
    // Limite client-side em MB (a validação server-side continua valendo).
    'maxSize' => 2,
    // Método Livewire para remover a foto atual (omite o botão se null).
    'removeAction' => null,
])

{{--
    Avatar com crop circular interativo: seleção de arquivo → modal com máscara
    redonda (arrastar para posicionar + zoom ±) → "Aplicar" envia o recorte
    512×512 ao Livewire via $wire.$upload. JS: resources/js/admin/avatar-cropper.js
    (delegação no document — sem re-init em wire:navigate).
--}}

<div
    data-af-avatar-cropper
    data-af-avatar="{{ json_encode(['model' => $model, 'maxSize' => (float) $maxSize]) }}"
    {{ $attributes->class(['flex items-center gap-4']) }}
>
    @if ($pending)
        <img alt="Prévia da nova foto" src="{{ $pending }}" class="{{ $size }} rounded-full object-cover" />
    @else
        <x-shared.avatar :name="$name" :src="$current" :size="$size" />
    @endif

    <div class="flex flex-col items-start gap-1.5">
        <div class="flex flex-wrap items-center gap-2">
            <x-shared.button
                type="button"
                variant="default"
                appearance="outline"
                size="sm"
                icon="tabler--camera"
                data-af-avatar-trigger
            >
                Alterar foto
            </x-shared.button>

            @if ($removeAction !== null && $current !== null && ! $pending)
                <x-shared.button
                    type="button"
                    variant="danger"
                    appearance="ghost"
                    size="sm"
                    wire:click="{{ $removeAction }}"
                >
                    Remover foto
                </x-shared.button>
            @endif
        </div>

        <small class="text-default-400 text-xs">PNG, JPG ou WebP até {{ $maxSize }} MB.</small>

        @error ($model)
            <small class="text-danger text-xs">{{ $message }}</small>
        @enderror
    </div>

    <input
        type="file"
        accept="image/png,image/jpeg,image/webp"
        class="hidden"
        data-af-avatar-input
        aria-label="Selecionar nova foto"
    />

    {{-- Modal do crop (controlado pelo módulo JS; clique no backdrop fecha). --}}
    <div
        data-af-avatar-cropper-modal
        class="fixed inset-0 z-80 items-center justify-center bg-black/60 p-4"
        style="display: none"
        role="dialog"
        aria-modal="true"
        aria-label="Ajustar foto de perfil"
    >
        <div class="bg-card border-default-300 w-full max-w-md rounded-xl border p-5 shadow-xl">
            <h3 class="text-default-900 mb-1 text-base font-semibold">Ajustar foto</h3>
            <p class="text-default-400 mb-4 text-sm">Arraste para posicionar e use o zoom para enquadrar.</p>

            <div class="bg-default-200 mx-auto aspect-square w-full overflow-hidden rounded-lg">
                <img data-af-avatar-stage alt="" class="block max-w-full" />
            </div>

            <div class="mt-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <x-shared.button
                        type="button"
                        variant="default"
                        appearance="outline"
                        size="sm"
                        icon="tabler--zoom-out"
                        icon-only
                        data-af-avatar-zoom-out
                        aria-label="Diminuir zoom"
                    />
                    <x-shared.button
                        type="button"
                        variant="default"
                        appearance="outline"
                        size="sm"
                        icon="tabler--zoom-in"
                        icon-only
                        data-af-avatar-zoom-in
                        aria-label="Aumentar zoom"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <x-shared.button
                        type="button"
                        variant="default"
                        appearance="outline"
                        size="sm"
                        data-af-avatar-cancel
                    >
                        Cancelar
                    </x-shared.button>
                    <x-shared.button
                        type="button"
                        variant="primary"
                        size="sm"
                        icon="tabler--check"
                        data-af-avatar-apply
                    >
                        Aplicar
                    </x-shared.button>
                </div>
            </div>
        </div>
    </div>
</div>
