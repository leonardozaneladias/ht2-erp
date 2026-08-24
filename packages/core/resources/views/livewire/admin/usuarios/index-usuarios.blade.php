<div class="space-y-6">
    <x-admin.page-header title="Usuários admin" subtitle="Gerencie quem tem acesso ao painel administrativo.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.usuarios.create')" icon="tabler--plus" wire:navigate>
                    Novo usuário
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.usuarios.usuarios-table />

    <livewire:admin.impersonation.iniciar-impersonation />
    <livewire:admin.lgpd.anonimizar-usuario />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('livewire.admin.usuarios._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
