@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.badge',
    'description' => 'Badges de status, contagem e contexto rápido para tabelas, menus e indicadores compactos.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Variações" subtitle="Soft, solid, pill e tamanhos">
            <div class="flex flex-wrap gap-3">
                <x-shared.badge variant="default">Rascunho</x-shared.badge>
                <x-shared.badge variant="primary">Novo</x-shared.badge>
                <x-shared.badge variant="secondary" solid>Em revisão</x-shared.badge>
                <x-shared.badge variant="success" pill icon="tabler--circle-check">Ativo</x-shared.badge>
                <x-shared.badge variant="danger" pill solid>Bloqueado</x-shared.badge>
                <x-shared.badge variant="warning" size="sm">Pendente</x-shared.badge>
                <x-shared.badge variant="info" size="lg" icon="tabler--clock-hour-4">Agendado</x-shared.badge>
            </div>
        </x-shared.card>

        <x-shared.card title="Exemplo de listagem" subtitle="Badges em um cenário próximo ao domínio">
            <div class="space-y-3">
                @foreach ([
                    ['Registro ADM-2026-01', 'Ativo', 'success'],
                    ['Registro ADM-2026-02', 'Sem reajuste', 'warning'],
                    ['Registro ADM-2026-03', 'Inativo', 'default'],
                    ['Registro ADM-2026-04', 'Bloqueado', 'danger'],
                ] as [$codigo, $status, $variant])
                    <div
                        class="border-default-300 bg-light/30 flex items-center justify-between rounded-lg border px-4 py-3"
                    >
                        <div>
                            <p class="text-body-color font-medium">{{ $codigo }}</p>
                            <p class="text-default-400 text-sm">Equipe Comercial • 148 usuários</p>
                        </div>

                        <x-shared.badge :variant="$variant" pill> {{ $status }} </x-shared.badge>
                    </div>
                @endforeach
            </div>
        </x-shared.card>
    </div>
@endsection
