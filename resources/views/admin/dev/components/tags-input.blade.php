@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.tags-input',
    'description' => 'Wrapper semântico para chips/tags múltiplas, preservando o motor Choices.js do select-search.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Tags controladas" subtitle="Dias de vencimento e lembrete">
            <div class="space-y-4">
                <x-shared.tags-input
                    name="dias_vencimento"
                    label="Dias de vencimento"
                    :options="array_combine(range(1, 31), range(1, 31))"
                    :value="[5, 10, 20]"
                    placeholder="Selecione os dias"
                    hint="Base oficial para o cenário de 14.15 Configurações Globais."
                />

                <x-shared.tags-input
                    name="dias_lembrete"
                    label="Dias de lembrete"
                    :options="array_combine(range(1, 15), range(1, 15))"
                    :value="[3, 7]"
                    searchable
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Tags livres" subtitle="Quando o domínio aceita valores criados pelo usuário">
            <x-shared.tags-input
                name="marcadores"
                label="Marcadores internos"
                :options="['vip' => 'VIP', 'pendencia' => 'Pendência', 'manual' => 'Manual']"
                :value="['vip', 'auditoria']"
                allow-create
                searchable
                hint="`allowCreate` adiciona entradas livres sem trocar o plugin base."
            />
        </x-shared.card>
    </div>
@endsection
