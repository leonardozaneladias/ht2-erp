@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.select-search',
    'description' => 'Wrapper Choices.js para relacionamentos, listas maiores, múltipla seleção e agrupamentos.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Variações principais" subtitle="Single, grouped e multiple">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.select-search
                    name="empresa_id"
                    label="Empresa"
                    :options="[
                        1 => 'Acme S.A. • Comercial 2026',
                        2 => 'Soluções Tech • Suporte 2026',
                        3 => 'Logix Brasil • Operações 2026',
                    ]"
                    value="2"
                    required
                />

                <x-shared.select-search
                    name="categoria_id"
                    label="Categoria por área"
                    :options="[
                        'Operacional' => [
                            ['value' => 'suporte', 'label' => 'Suporte'],
                            ['value' => 'operacoes', 'label' => 'Operações'],
                        ],
                        'Negócios' => [
                            ['value' => 'comercial', 'label' => 'Comercial'],
                            ['value' => 'financeiro', 'label' => 'Financeiro'],
                        ],
                    ]"
                    placeholder="Selecione uma categoria"
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
                        'vip' => 'Plano VIP',
                    ]"
                    :value="['adimplente', 'vip']"
                    hint="Útil em filtros avançados com poucas dezenas de opções."
                    multiple
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Usuários e registros">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.select-search
                    name="registro_id"
                    label="Registro"
                    :options="[
                        11 => 'ADM-2026-01 • Comercial Noturno',
                        12 => 'ADM-2026-02 • Suporte Integral',
                        13 => 'ADM-2026-03 • Operações',
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
