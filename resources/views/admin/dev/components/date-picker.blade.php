@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.date-picker',
    'description' => 'Wrapper leve de Flatpickr com locale pt-BR, sync amigável com Livewire e configuração explícita por props.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Configurações principais" subtitle="Formato pt-BR, hora opcional e limites de data">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.date-picker name="data_inicio" label="Data de início" default-date="2026-04-11" required />
                <x-shared.date-picker
                    name="data_evento"
                    label="Data do evento"
                    min-date="today"
                    hint="Não permite datas anteriores a hoje."
                />
                <x-shared.date-picker
                    name="agendamento_atendimento"
                    label="Atendimento"
                    format="d/m/Y H:i"
                    enable-time
                    default-date="2026-04-18 14:30"
                />
                <x-shared.date-picker
                    name="vencimento_limite"
                    label="Vencimento limite"
                    min-date="2026-04-11"
                    max-date="2026-12-31"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Contrato e reajuste">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.date-picker name="contrato_inicio" label="Início da vigência" default-date="2026-05-01" />
                <x-shared.date-picker name="contrato_evento" label="Data do evento" default-date="2026-11-21" />
                <x-shared.date-picker
                    name="indice_referencia"
                    label="Mês de referência"
                    format="m/Y"
                    placeholder="mm/aaaa"
                    hint="Pode ser reutilizado em índices e relatórios mensais."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
