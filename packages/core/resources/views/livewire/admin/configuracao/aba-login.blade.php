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
            <x-shared.file-upload
                variant="compact"
                name="fundo"
                label="Arquivo da imagem"
                wire:model="fundo"
                :preview="$previewFundo"
                accept="image/png,image/jpeg,image/webp"
                :max-size="4"
                hint="Aparece no painel lateral da tela de login."
            />
            <div wire:loading wire:target="fundo" class="text-default-400 -mt-3 text-xs">Enviando…</div>
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
