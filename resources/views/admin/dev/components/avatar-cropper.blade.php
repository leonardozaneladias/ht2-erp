@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.avatar-cropper',
    'description' => 'Avatar com crop circular interativo: arrastar para posicionar + zoom antes do upload (cropperjs, lazy).',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card title="Sem foto (iniciais)" subtitle="Selecione uma imagem para abrir o modal de crop">
            <x-shared.avatar-cropper model="avatar" name="Maria Souza" />
            <p class="text-default-400 mt-4 text-xs">Nesta preview não há componente Livewire por trás — o upload final não acontece, mas o fluxo de seleção, crop, zoom e máscara redonda é totalmente funcional.</p>
        </x-shared.card>

        <x-shared.card title="Com foto atual + remover" subtitle="Props `current` e `remove-action`">
            <x-shared.avatar-cropper
                model="avatar"
                name="João Pereira"
                current="https://i.pravatar.cc/128?img=12"
                remove-action="removerAvatar"
                size="size-20"
            />
        </x-shared.card>
    </div>
@endsection
