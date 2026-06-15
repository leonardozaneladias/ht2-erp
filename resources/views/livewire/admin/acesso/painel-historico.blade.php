<div class="bg-card border-default-200 overflow-hidden rounded-lg border">
    {{-- Cabeçalho --}}
    <div class="border-default-200 border-b p-5">
        <h3 class="text-lg font-semibold">Histórico de acesso</h3>
        <p class="text-default-500 mt-0.5 text-sm">Trilha de concessões, negações e mudanças de perfis.</p>
    </div>

    <div class="space-y-4 p-5">
        {{-- Filtros --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <x-shared.select-search
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

        {{-- Tabela --}}
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Quando</th>
                        <th>Quem</th>
                        <th>Evento</th>
                        <th>Alvo</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                        <tr wire:key="ev-{{ $evento->id }}" class="hover:bg-default-100/50 transition-colors">
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
                                    {{ $evento->subject->nome ?? $evento->subject->name ?? '#' . $evento->subject_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-default-600">
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
            </table>
        </div>

        <div>{{ $eventos->links() }}</div>
    </div>
</div>
