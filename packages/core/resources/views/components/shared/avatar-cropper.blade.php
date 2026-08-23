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
    Avatar com crop circular interativo. A imagem entra por três caminhos — clicar no
    avatar, arrastar um arquivo sobre ele, ou colar (Ctrl+V) — e sai como um recorte
    512×512 enviado ao Livewire via $wire.$upload, com barra de progresso.
    JS: resources/js/admin/avatar-cropper.js (delegação no document — imune ao morph).
--}}

<div
    data-af-avatar-cropper
    data-af-avatar="{{ json_encode(['model' => $model, 'maxSize' => (float) $maxSize]) }}"
    {{ $attributes->class(['group/avatar flex items-center gap-4']) }}
>
    {{-- O avatar inteiro é o alvo: clicar abre o seletor, arrastar solta aqui. Antes, o
         único caminho era o botão ao lado — e a foto, que é o elemento óbvio para clicar,
         não fazia nada. --}}
    <button
        type="button"
        data-af-avatar-trigger
        class="focus-visible:outline-primary relative shrink-0 cursor-pointer rounded-full focus-visible:outline-2 focus-visible:outline-offset-2"
        aria-label="Alterar foto — clique, arraste uma imagem ou cole (Ctrl+V)"
    >
        @if ($pending)
            <img alt="Prévia da nova foto" src="{{ $pending }}" class="{{ $size }} rounded-full object-cover" />
        @else
            <x-shared.avatar :name="$name" :src="$current" :size="$size" />
        @endif

        {{-- Overlay de câmera: aparece no hover/foco, e fica fixo enquanto se arrasta um
             arquivo por cima (a classe is-dragging é posta pelo JS). --}}
        <span
            class="absolute inset-0 flex items-center justify-center rounded-full bg-black/55 text-white opacity-0 transition-opacity group-hover/avatar:opacity-100 group-focus-visible/avatar:opacity-100 group-[.is-dragging]/avatar:opacity-100 motion-reduce:transition-none"
            aria-hidden="true"
        >
            <i class="iconify tabler--camera size-1/3"></i>
        </span>
    </button>

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

        {{-- A instrução mora no aria-label do avatar e no modal. Repeti-la aqui é ruído
             permanente num cabeçalho que já carrega nome, situação, cargo e 5 indicadores —
             e a linha extra empurrava o resumo para duas linhas. --}}
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
        class="fixed inset-0 z-80 items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        style="display: none"
        role="dialog"
        aria-modal="true"
        aria-label="Ajustar foto de perfil"
    >
        <div class="bg-card border-default-300 w-full max-w-md overflow-hidden rounded-2xl border shadow-2xl">
            <div class="flex items-start justify-between gap-3 px-5 pt-4 pb-3">
                <div>
                    <h3 class="text-default-900 text-base font-semibold">Ajustar foto</h3>
                    <p class="text-default-400 mt-0.5 text-sm">Arraste a foto para posicioná-la no círculo. Role para dar zoom.</p>
                </div>
                <x-shared.button
                    type="button"
                    variant="default"
                    appearance="ghost"
                    size="sm"
                    icon="tabler--x"
                    icon-only
                    class="-mt-1 -mr-2"
                    data-af-avatar-cancel
                    aria-label="Fechar"
                />
            </div>

            {{-- Stage edge-to-edge: a foto domina o modal; máscara circular via CSS.
                 Altura limitada por viewport para o modal nunca tomar a tela toda. --}}
            <div class="h-[clamp(14rem,48vh,22rem)] w-full overflow-hidden bg-black">
                <img data-af-avatar-stage alt="" class="block max-w-full" />
            </div>

            {{-- Barra de controles: zoom por slider (sincronizado com scroll/pinch) + girar. --}}
            <div class="flex items-center gap-2 px-5 py-3">
                <x-shared.button
                    type="button"
                    variant="default"
                    appearance="ghost"
                    size="sm"
                    icon="tabler--zoom-out"
                    icon-only
                    data-af-avatar-zoom-out
                    aria-label="Diminuir zoom"
                />
                <input
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    value="0"
                    class="grow"
                    data-af-avatar-zoom-slider
                    aria-label="Zoom da foto"
                />
                <x-shared.button
                    type="button"
                    variant="default"
                    appearance="ghost"
                    size="sm"
                    icon="tabler--zoom-in"
                    icon-only
                    data-af-avatar-zoom-in
                    aria-label="Aumentar zoom"
                />
                <span class="bg-default-200 h-5 w-px" aria-hidden="true"></span>
                <x-shared.button
                    type="button"
                    variant="default"
                    appearance="ghost"
                    size="sm"
                    icon="tabler--rotate-clockwise"
                    icon-only
                    data-af-avatar-rotate
                    aria-label="Girar 90 graus"
                />
            </div>

            {{-- Progresso do upload: o $wire.$upload sempre expôs o percentual num 5º
                 callback, que ninguém usava — e o envio do recorte ficava sem sinal de
                 vida entre o "Aplicar" e o preview. --}}
            <div
                class="border-default-200 hidden border-t px-5 pt-3"
                data-af-avatar-progress
                role="progressbar"
                aria-label="Enviando a foto"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="0"
            >
                <div class="bg-default-200 h-1.5 w-full overflow-hidden rounded-full">
                    <div
                        class="bg-primary h-full w-0 rounded-full transition-[width] duration-150 motion-reduce:transition-none"
                        data-af-avatar-progress-bar
                    ></div>
                </div>
            </div>

            <div class="border-default-200 flex items-center justify-between gap-3 border-t px-5 py-3">
                <small class="text-default-400 text-xs">PNG · JPG · WebP · até {{ $maxSize }} MB</small>

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
