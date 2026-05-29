@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.progress-bar',
    'description' => 'Barra de progresso reutilizável para metas, wizard do portal e indicadores determinísticos do sistema.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Variantes principais" subtitle="Uso básico, tamanhos e estados">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <p class="text-body-color text-sm font-semibold">Primária com label automático</p>
                        <x-shared.progress-bar :value="65" label />
                    </div>

                    <div class="space-y-2">
                        <p class="text-body-color text-sm font-semibold">Danger compacta</p>
                        <x-shared.progress-bar :value="32" variant="danger" size="sm" />
                    </div>

                    <div class="space-y-2">
                        <p class="text-body-color text-sm font-semibold">Warning striped</p>
                        <x-shared.progress-bar :value="58" variant="warning" size="lg" striped animated label />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <p class="text-body-color text-sm font-semibold">Auto color para metas</p>
                        <x-shared.progress-bar :value="87" auto-color size="lg" label />
                    </div>

                    <div class="space-y-2">
                        <p class="text-body-color text-sm font-semibold">Com slot interno</p>
                        <x-shared.progress-bar :value="43" variant="info" size="lg">
                            Etapa 3 de 7
                        </x-shared.progress-bar>
                    </div>
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Caso real do domínio" subtitle="Meta de adesão por registro no dashboard">
            <div class="space-y-4">
                @foreach ([
                    ['codigo' => 'PED-2027', 'empresa' => 'Acme S.A.', 'meta' => 120, 'aderidos' => 101],
                    ['codigo' => 'PED-2026', 'empresa' => 'Soluções Tech', 'meta' => 90, 'aderidos' => 48],
                    ['codigo' => 'PED-2028', 'empresa' => 'Logix Brasil', 'meta' => 140, 'aderidos' => 39],
                ] as $item)
                    @php
                        $pct = $item['meta'] > 0 ? ($item['aderidos'] / $item['meta']) * 100 : 0;
                    @endphp
                    <div
                        class="border-default-200/70 bg-light/30 grid gap-3 rounded-xl border p-4 lg:grid-cols-[1.3fr_90px_90px_1fr] lg:items-center"
                    >
                        <div>
                            <p class="text-body-color text-sm font-semibold">{{ $item['codigo'] }}</p>
                            <p class="text-default-400 text-xs">{{ $item['empresa'] }}</p>
                        </div>
                        <div>
                            <p class="text-2xs text-default-400 tracking-[0.18em] uppercase">Meta</p>
                            <p class="text-body-color mt-1 text-sm font-semibold">{{ $item['meta'] }}</p>
                        </div>
                        <div>
                            <p class="text-2xs text-default-400 tracking-[0.18em] uppercase">Aderidos</p>
                            <p class="text-body-color mt-1 text-sm font-semibold">{{ $item['aderidos'] }}</p>
                        </div>
                        <x-shared.progress-bar :value="$pct" auto-color size="lg" label />
                    </div>
                @endforeach
            </div>
        </x-shared.card>
    </div>
@endsection
