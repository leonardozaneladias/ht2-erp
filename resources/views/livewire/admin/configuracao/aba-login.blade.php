@php
    $previewFundo = $fundo && in_array(strtolower((string) $fundo->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'webp'], true)
        ? $fundo->temporaryUrl()
        : $fundoAtual;
@endphp

<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card title="Textos" subtitle="Cabeçalho exibido no formulário de login.">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="titulo" label="Título" wire:model="titulo" required />
                <x-shared.input name="subtitulo" label="Subtítulo" wire:model="subtitulo" />
            </div>
        </x-shared.card>

        <x-shared.card
            title="Imagem de fundo"
            subtitle="Aparece no painel lateral da tela de login. Ideal: 1600×1200 ou maior."
        >
            <div class="flex items-start gap-4">
                <div class="border-default-300 bg-light/40 h-28 w-44 shrink-0 overflow-hidden rounded-lg border">
                    @if ($previewFundo)
                        <img
                            src="{{ $previewFundo }}"
                            alt="Fundo da tela de login"
                            class="h-full w-full object-cover"
                        />
                    @else
                        <div class="text-default-400 flex h-full items-center justify-center px-2 text-center text-xs">
                            Padrão do tema
                        </div>
                    @endif
                </div>
                <div class="grow">
                    <input
                        type="file"
                        wire:model="fundo"
                        accept="image/png,image/jpeg,image/webp"
                        class="form-input text-sm"
                    />
                    <div wire:loading wire:target="fundo" class="text-default-400 mt-1 text-xs">Enviando…</div>
                    @error ('fundo')
                        <small class="text-danger mt-1 block text-xs">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Exibição" subtitle="Elementos visíveis na página de login.">
            <x-shared.toggle name="mostrar_logo" label="Exibir logotipo" wire:model="mostrar_logo" />
            <x-shared.toggle name="mostrar_versao" label="Exibir versão do sistema" wire:model="mostrar_versao" />
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar tela de login
            </x-shared.loading-button>
        </div>
    </form>
</div>
