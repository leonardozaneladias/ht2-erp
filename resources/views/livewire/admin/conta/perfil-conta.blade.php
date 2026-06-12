<x-shared.card title="Perfil" subtitle="Sua foto, nome e identificação no painel.">
    <form wire:submit="salvar" class="grid gap-5">
        <x-shared.avatar-cropper
            model="avatar"
            :name="$usuario->nome"
            :current="$usuario->urlAvatar()"
            :pending="$avatar?->temporaryUrl()"
            remove-action="removerAvatar"
        />

        <x-shared.input name="nome" label="Nome" wire:model="nome" required />

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-shared.phone-input name="telefone" label="Telefone" wire:model="telefone" />
            <x-shared.input name="cargo" label="Cargo" placeholder="Ex.: Gerente financeiro" wire:model="cargo" />
        </div>

        <x-shared.textarea
            name="bio"
            label="Bio"
            rows="3"
            maxlength="500"
            hint="Uma breve descrição sobre você (até 500 caracteres). Visível apenas na sua conta."
            wire:model="bio"
        />

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
