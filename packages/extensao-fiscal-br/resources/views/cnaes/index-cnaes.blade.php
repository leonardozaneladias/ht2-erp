<div class="space-y-6">
    <x-admin.page-header title="CNAEs" subtitle="Classificação Nacional de Atividades Econômicas — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.referencia.cnaes.create')" icon="tabler--plus" wire:navigate>
                    Novo CNAE
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:fiscal-br.cnaes.cnae-table />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('fiscal-br::cnaes._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
