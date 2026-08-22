@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.field-display',
    'description' => 'Rótulo + valor read-only — o tijolo das fichas de visualização (Ver) e telas de detalhe.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Ficha em grid" subtitle="Composição típica de uma seção de ficha">
            <div class="grid grid-cols-2 gap-x-4 gap-y-4 md:grid-cols-3">
                <x-shared.field-display label="Nome">Mesa Diretor Munique</x-shared.field-display>
                <x-shared.field-display label="Status">
                    <x-shared.badge variant="success" pill size="sm">Publicado</x-shared.badge>
                </x-shared.field-display>
                <x-shared.field-display label="Preço">R$ 2.350,00</x-shared.field-display>
                <x-shared.field-display label="Categoria">Escritório</x-shared.field-display>
                <x-shared.field-display label="Início">02/01/2026</x-shared.field-display>
                <x-shared.field-display label="Observação">—</x-shared.field-display>
            </div>
        </x-shared.card>

        <x-shared.card title="Valores ricos" subtitle="O slot aceita badge, link e composição">
            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                <x-shared.field-display label="Responsável">
                    <a href="#" class="text-primary hover:underline">Helena Marchetti</a>
                </x-shared.field-display>
                <x-shared.field-display label="Tags">
                    <span class="flex flex-wrap gap-1.5">
                        <x-shared.badge variant="default" size="sm" pill>madeira</x-shared.badge>
                        <x-shared.badge variant="default" size="sm" pill>premium</x-shared.badge>
                    </span>
                </x-shared.field-display>
            </div>
        </x-shared.card>
    </div>
@endsection
