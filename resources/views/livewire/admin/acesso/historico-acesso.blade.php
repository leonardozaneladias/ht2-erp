<div class="space-y-6">
    <x-admin.page-header title="Histórico de acesso" subtitle="Trilha de concessões, negações e mudanças de perfis." />

    <x-shared.card>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <x-shared.select
                name="event"
                label="Tipo de evento"
                :options="array_merge(
                    [['value' => '', 'label' => 'Todos']],
                    collect($eventosDisponiveis)->map(fn ($e) => ['value' => $e, 'label' => \Illuminate\Support\Str::headline($e)])->all(),
                )"
                wire:model.live="event"
            />
            <x-shared.input type="date" name="de" label="De" wire:model.live="de" />
            <x-shared.input type="date" name="ate" label="Até" wire:model.live="ate" />
            <div class="flex items-end">
                <x-shared.button appearance="ghost" variant="default" wire:click="limparFiltros">
                    Limpar filtros
                </x-shared.button>
            </div>
        </div>
    </x-shared.card>

    <x-shared.card>
        <x-admin.table.table :density="'comfortable'">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Quem</th>
                    <th>Evento</th>
                    <th>Alvo</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($eventos as $evento)
                    <tr wire:key="ev-{{ $evento->id }}" class="hover:bg-light/40 transition-colors">
                        <td class="whitespace-nowrap">{{ $evento->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $evento->causer?->nome ?? 'Sistema' }}</td>
                        <td>
                            @php ($variante = match (true) {
                                    str_contains($evento->event ?? '', 'negado') => 'danger',
                                    str_contains($evento->event ?? '', 'revogado') => 'warning',
                                    str_contains($evento->event ?? '', 'concedido') => 'success',
                                    default => 'info',
                                })
                            <x-shared.badge :variant="$variante">
                                {{ \Illuminate\Support\Str::headline($evento->event ?? '—') }}
                            </x-shared.badge>
                        </td>
                        <td>
                            @if ($evento->subject)
                                {{ $evento->subject->nome ?? $evento->subject->name ?? ('#' . $evento->subject_id) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-default-600 text-sm">
                            @php ($props = $evento->properties)
                            @if (isset($props['permissao']))
                                <span class="font-medium">{{ $props['permissao'] }}</span>
                            @endif
                            @if (isset($props['motivo']))
                                <span class="text-default-400 block text-xs">{{ $props['motivo'] }}</span>
                            @endif
                            @if (isset($props['total']))
                                <span>{{ $props['total'] }} registro(s)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-shared.empty-state
                                icon="tabler--history"
                                title="Nenhum evento de acesso"
                                description="As mudanças de acesso aparecerão aqui conforme forem feitas."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table.table>

        <div class="mt-4">{{ $eventos->links() }}</div>
    </x-shared.card>
</div>
