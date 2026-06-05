<x-shared.card title="Perfil" subtitle="Sua foto, nome e identificação no painel.">
    <form wire:submit="salvar" class="grid gap-5">
        <div class="flex items-center gap-4">
            @if ($avatar)
                <img src="{{ $avatar->temporaryUrl() }}" alt="Prévia" class="size-16 rounded-full object-cover" />
            @else
                <x-shared.avatar :name="$usuario->nome" :src="$usuario->urlAvatar()" size="size-16" />
            @endif

            <div class="flex flex-col gap-2">
                <input type="file" wire:model="avatar" accept="image/png,image/jpeg,image/webp" class="text-sm" />
                @if ($usuario->urlAvatar())
                    <button
                        type="button"
                        wire:click="removerAvatar"
                        class="text-danger text-left text-xs hover:underline"
                    >
                        Remover foto
                    </button>
                @endif
                @error ('avatar')
                    <small class="text-danger text-xs">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <x-shared.input name="nome" label="Nome" wire:model="nome" required />

        <div class="grid gap-1">
            <span class="text-default-500 text-sm font-medium">E-mail</span>
            <span class="text-default-700">{{ $usuario->email }}</span>
        </div>

        <div class="border-default-200 grid gap-2 border-t pt-4 text-sm">
            <div class="flex justify-between">
                <span class="text-default-500">Papéis globais</span>
                <span class="text-default-700">{{ $usuario->getRoleNames()->join(', ') ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-default-500">Último login</span>
                <span class="text-default-700">
                    {{ $usuario->last_login_at?->timezone($usuario->timezone ?? config('app.timezone'))->translatedFormat('d/m/Y \à\s H:i') ?? '—' }}
                </span>
            </div>
        </div>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar perfil</x-shared.loading-button
            >
        </div>
    </form>
</x-shared.card>
