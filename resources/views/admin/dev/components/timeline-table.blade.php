@extends ('admin.dev.components.shell', [
    'title' => 'Preview • Timeline Table',
    'description' => 'Grid visual para programações de valor e parcelamento, com status, vigência, valor e ações rápidas.',
])

@section ('preview')
    @php
        $programacoes = [
            [
                'inicio' => '2026-04-01',
                'fim' => '2026-05-31',
                'valor_formatado' => 'R$ 289,90',
                'parcelas' => '8x',
                'descricao' => 'Campanha de lançamento com entrada reduzida',
                'actions' => [
                    ['label' => 'Editar', 'icon' => 'tabler--edit', 'href' => '#'],
                    ['label' => 'Encerrar', 'icon' => 'tabler--ban', 'variant' => 'danger', 'href' => '#'],
                ],
            ],
            [
                'inicio' => '2026-06-01',
                'fim' => '2026-07-31',
                'valor_formatado' => 'R$ 319,90',
                'parcelas' => '6x',
                'descricao' => 'Janela futura para renovação de campanha institucional',
                'actions' => [
                    ['label' => 'Editar', 'icon' => 'tabler--edit', 'href' => '#'],
                ],
            ],
            [
                'inicio' => '2026-01-01',
                'fim' => '2026-03-31',
                'valor_formatado' => 'R$ 259,90',
                'parcelas' => '10x',
                'descricao' => 'Programação anterior já expirada, mantida para histórico',
            ],
        ];
    @endphp
    <div class="grid gap-6">
        <x-shared.card title="Timeline de programações" subtitle="Uso canônico da subtela 14.7">
            <x-admin.timeline-table
                :programacoes="$programacoes"
                gap-message="Existe um intervalo sem programação ativa entre 01/06/2026 e 05/06/2026."
            />
        </x-shared.card>

        <x-shared.card title="Estado vazio" subtitle="Fallback para contratos sem regras cadastradas">
            <x-admin.timeline-table
                :programacoes="[]"
                empty-message="Nenhuma programação de valor foi configurada ainda."
            />
        </x-shared.card>
    </div>
@endsection
