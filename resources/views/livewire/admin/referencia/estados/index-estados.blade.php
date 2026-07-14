<div class="space-y-6">
    <x-admin.page-header title="Estados" subtitle="Unidades federativas (UF) — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.estados.create')" icon="tabler--plus" wire:navigate>
                    Novo estado
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.estado-table />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('livewire.admin.referencia.estados._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
