<x-admin.layout title="Livewire" subtitle="Componentes reativos com Livewire 4">
    <x-admin.page-header title="Livewire — Exemplos" subtitle="Componentes reativos server-side com Livewire 4" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @if (class_exists(\HT2ML\ExemploDemo\Livewire\ExemploCounter::class))
            {{-- Componente da extensão de demonstração: o núcleo NÃO depende dela.
                 Sem ht2ml/extensao-exemplo-demo instalada, este bloco some em vez
                 de estourar "Unable to find component". --}}
            <livewire:exemplo-counter />
        @endif

        <x-shared.card title="Como usar">
            <p class="text-default-500">Componentes Livewire ficam em <code>src/Livewire/</code> do pacote</p>
            <p class="text-default-500">Views em <code>resources/views/</code> do pacote, sob o namespace dele</p>
            <p class="text-default-500 mb-0">Use com a tag <code>&lt;livewire:exemplo-counter /&gt;</code></p>
        </x-shared.card>
    </div>
</x-admin.layout>
