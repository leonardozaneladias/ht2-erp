@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.date-range-picker',
    'description' => 'Componente irmão do date-picker para intervalos, apoiado pela mesma base Flatpickr do admin.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Ranges principais" subtitle="Filtro de período, vencimento e vigência">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.date-range-picker
                    name="periodo"
                    label="Período do relatório"
                    default-date="2026-04-01 to 2026-04-30"
                />
                <x-shared.date-range-picker
                    name="vigencia"
                    label="Vigência do desconto"
                    min-date="2026-01-01"
                    max-date="2026-12-31"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Filtros financeiros">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.date-range-picker
                    name="vencimento"
                    label="Vencimento"
                    hint="Usado em Parcelas, Relatórios e filtros operacionais."
                    default-date="2026-05-10 to 2026-05-31"
                />

                <x-shared.date-range-picker
                    name="adesao"
                    label="Data de adesão"
                    placeholder="Selecione o intervalo de adesão"
                />
            </div>
        </x-shared.card>
    </div>
@endsection
