@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.select-search',
    'description' => 'Wrapper Choices.js para relacionamentos, listas maiores, múltipla seleção e agrupamentos.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Variações principais" subtitle="Single, grouped e multiple">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.select-search
                    name="instituicao_id"
                    label="Instituição"
                    :options="[
                        1 => 'UNESP • Engenharia 2026',
                        2 => 'USP • Medicina 2026',
                        3 => 'PUC • Direito 2026',
                    ]"
                    value="2"
                    required
                />

                <x-shared.select-search
                    name="curso_id"
                    label="Curso por área"
                    :options="[
                        'Saúde' => [
                            ['value' => 'medicina', 'label' => 'Medicina'],
                            ['value' => 'enfermagem', 'label' => 'Enfermagem'],
                        ],
                        'Negócios' => [
                            ['value' => 'adm', 'label' => 'Administração'],
                            ['value' => 'contabeis', 'label' => 'Ciências Contábeis'],
                        ],
                    ]"
                    placeholder="Selecione um curso"
                />
            </div>

            <div class="mt-4">
                <x-shared.select-search
                    name="filtros[]"
                    label="Filtros múltiplos"
                    :options="[
                        'adimplente' => 'Somente adimplentes',
                        'com_foto' => 'Com foto',
                        'com_portal' => 'Com acesso ao portal',
                        'vip' => 'Pacote VIP',
                    ]"
                    :value="['adimplente', 'vip']"
                    hint="Útil em filtros avançados com poucas dezenas de opções."
                    multiple
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Formandos e contratos">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.select-search
                    name="contrato_id"
                    label="Contrato"
                    :options="[
                        11 => 'ADM-2026-01 • Administração Noturno',
                        12 => 'DIR-2026-02 • Direito Integral',
                        13 => 'MED-2026-03 • Medicina',
                    ]"
                    value="11"
                />

                <x-shared.select-search
                    name="gateway_default"
                    label="Gateway padrão"
                    :options="[
                        'asaas' => 'Asaas',
                        'mercado_pago' => 'Mercado Pago',
                        'stone' => 'Stone',
                    ]"
                    :searchable="false"
                    value="asaas"
                />
            </div>
        </x-shared.card>
    </div>
@endsection
