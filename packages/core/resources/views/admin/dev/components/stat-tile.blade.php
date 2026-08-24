@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.stat-tile',
    'description' => 'Tile de métrica com ícone soft, label e valor com contagem animada — resultado de importações, resumos de lote e relatórios rápidos.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card
            title="Resultado de uma importação"
            subtitle="Variants semânticas + stagger via :delay — recarregue para ver a contagem"
        >
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-shared.stat-tile label="Total de linhas" :value="248" icon="tabler--sum" :delay="0" />
                <x-shared.stat-tile
                    label="Criados"
                    :value="212"
                    icon="tabler--user-plus"
                    variant="success"
                    :delay="70"
                />
                <x-shared.stat-tile
                    label="Atualizados"
                    :value="31"
                    icon="tabler--refresh"
                    variant="info"
                    :delay="140"
                />
                <x-shared.stat-tile
                    label="Com erro"
                    :value="5"
                    icon="tabler--alert-triangle"
                    variant="danger"
                    :delay="210"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Variants" subtitle="default, primary, success, info, warning e danger">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <x-shared.stat-tile label="Default" :value="1024" />
                <x-shared.stat-tile label="Primary" :value="87" icon="tabler--users" variant="primary" />
                <x-shared.stat-tile label="Success" :value="42" icon="tabler--circle-check" variant="success" />
                <x-shared.stat-tile label="Info" :value="13" icon="tabler--info-circle" variant="info" />
                <x-shared.stat-tile label="Warning" :value="7" icon="tabler--alert-hexagon" variant="warning" />
                <x-shared.stat-tile label="Danger" :value="3" icon="tabler--alert-triangle" variant="danger" />
            </div>
        </x-shared.card>

        <x-shared.card
            title="Sem contagem animada"
            subtitle=":count-up=false — valor estático (drawer de histórico, listas densas)"
        >
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-shared.stat-tile label="Lidas" :value="1580" icon="tabler--file-text" :count-up="false" />
                <x-shared.stat-tile
                    label="Importadas"
                    :value="1502"
                    icon="tabler--database-import"
                    variant="success"
                    :count-up="false"
                />
                <x-shared.stat-tile
                    label="Ignoradas"
                    :value="61"
                    icon="tabler--player-skip-forward"
                    variant="info"
                    :count-up="false"
                />
                <x-shared.stat-tile
                    label="Sem vínculo"
                    :value="17"
                    icon="tabler--user-question"
                    variant="warning"
                    :count-up="false"
                />
            </div>
        </x-shared.card>
    </div>
@endsection
