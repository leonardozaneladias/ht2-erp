<div class="space-y-6">
    <x-admin.page-header title="Logs de auditoria" subtitle="Histórico append-only de mudanças relevantes do painel." />

    <div class="card">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <x-shared.select
                    name="log"
                    label="Log"
                    :options="array_merge([['value' => '', 'label' => 'Todos']], collect($logsDisponiveis)->map(fn ($l) => ['value' => $l, 'label' => $l])->all())"
                    wire:model.live="logName"
                />
                <x-shared.select
                    name="event"
                    label="Evento"
                    :options="array_merge([['value' => '', 'label' => 'Todos']], collect($eventosDisponiveis)->map(fn ($e) => ['value' => $e, 'label' => $e])->all())"
                    wire:model.live="event"
                />
                <x-shared.input type="date" name="de" label="De" wire:model.live="de" />
                <x-shared.input type="date" name="ate" label="Até" wire:model.live="ate" />
                <div class="flex items-end">
                    <button type="button" class="btn btn-outline-secondary" wire:click="limparFiltros">
                        Limpar filtros
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table w-full" data-testid="tabela-auditoria">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Quem</th>
                            <th>Log</th>
                            <th>Evento</th>
                            <th>Sujeito</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($eventos as $evento)
                            <tr wire:key="evt-{{ $evento->id }}">
                                <td>{{ $evento->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $evento->causer?->nome ?? $evento->causer?->email ?? 'sistema' }}</td>
                                <td><x-shared.badge>{{ $evento->log_name ?? '—' }}</x-shared.badge></td>
                                <td>{{ $evento->event ?? '—' }}</td>
                                <td>
                                    @if ($evento->subject)
                                        <span class="text-default-600 text-sm">
                                            {{ class_basename($evento->subject_type) }} #{{ $evento->subject_id }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $evento->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-shared.empty-state
                                        icon="tabler--history"
                                        title="Nenhum evento registrado"
                                        description="Os eventos do painel aparecem aqui assim que ocorrerem."
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
</div>
