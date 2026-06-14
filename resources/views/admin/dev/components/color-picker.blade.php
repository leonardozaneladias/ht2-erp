@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.color-picker',
    'description' => 'Seletor de cor (swatch nativo + hex) sobre input type=color, sem dependência extra. Bindável via wire:model.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Variações" subtitle="Com valor inicial, com hint e em estado de erro">
            <x-shared.color-picker name="cor_primaria" label="Cor primária" value="#1ab394" />

            <x-shared.color-picker
                name="cor_secundaria"
                label="Cor secundária"
                value="#3b82f6"
                hint="Reflete em botões e destaques após salvar."
            />

            <x-shared.color-picker name="cor_obrigatoria" label="Cor da marca" value="#f97316" required />
        </x-shared.card>

        <x-shared.card title="Uso" subtitle="API alinhada aos demais campos">
            <pre
                class="bg-light/50 text-body-color overflow-x-auto rounded-lg p-4 text-xs leading-relaxed"
            ><code>&lt;x-shared.color-picker
    name="cor_primaria"
    label="Cor primária"
    wire:model="cor_primaria"
    :value="$cor_primaria"
/&gt;</code></pre>
            <p class="text-default-500 mt-3 text-sm">Passe <code class="text-body-color">:value</code> com o valor atual para o render inicial. O hex (#RRGGBB) é espelhado ao lado do swatch via Alpine.</p>
        </x-shared.card>
    </div>
@endsection
