<div @class (['bg-card border-default-200 overflow-hidden rounded-lg border' => ! $bare])>
    @unless ($bare)
        <div class="border-default-200 border-b p-5">
            <h3 class="text-lg font-semibold">Histórico de mudanças</h3>
            <p class="text-default-500 mt-0.5 text-sm">Trilha de auditoria deste registro (quem mudou o quê, e quando).</p>
        </div>
    @endunless

    <div @class (['p-5' => ! $bare])>
        @if ($atividades->isEmpty())
            <x-shared.empty-state
                icon="tabler--history-off"
                title="Sem histórico por aqui"
                description="As mudanças deste registro aparecerão aqui automaticamente."
                size="sm"
            />
        @else
            <ol class="border-default-200 ms-2 space-y-5 border-s ps-5">
                @foreach ($atividades as $atividade)
                    @php ($mudancas = $this->mudancasDe($atividade))
                    {{-- Classes literais (não interpolar): o purge do Tailwind precisa vê-las. --}}
                    @php ([$variante, $icone, $corPonto] = match ($atividade->event) {
                        'created' => ['success', 'tabler--circle-plus', 'bg-success'],
                        'updated' => ['warning', 'tabler--edit', 'bg-warning'],
                        'deleted' => ['danger', 'tabler--trash', 'bg-danger'],
                        'restored' => ['info', 'tabler--restore', 'bg-info'],
                        default => ['default', 'tabler--point', 'bg-default-400'],
                    })
                    <li wire:key="historico-{{ $atividade->id }}" class="relative">
                        <span
                            class="bg-card border-default-300 absolute -start-[1.85rem] top-0.5 flex size-4 items-center justify-center rounded-full border"
                            aria-hidden="true"
                        >
                            <span class="{{ $corPonto }} size-1.5 rounded-full"></span>
                        </span>

                        <div class="flex flex-wrap items-center gap-2">
                            <x-shared.badge :variant="$variante" :icon="$icone" size="sm">
                                {{ $atividade->event ?? '—' }}</x-shared.badge
                            >
                            <span class="text-body-color text-sm font-medium">{{ $atividade->description }}</span>
                        </div>
                        <p class="text-default-400 mt-0.5 text-xs">
                            {{ $atividade->created_at?->format('d/m/Y H:i:s') }} · por {{ $atividade->causer?->getAttribute('nome') ?? 'sistema' }}
                            @if (data_get($atividade->getProperty('impersonado_por'), 'nome'))
                                ·
                                <span class="text-warning"
                                    >personificado por {{ data_get($atividade->getProperty('impersonado_por'), 'nome') }}</span
                                >
                            @endif
                        </p>

                        @if ($mudancas !== [])
                            <details class="mt-2">
                                <summary class="text-primary cursor-pointer text-xs font-medium select-none">
                                    {{ count($mudancas) }} {{ count($mudancas) === 1 ? 'campo alterado' : 'campos alterados' }}
                                </summary>
                                <div class="border-default-200 mt-2 overflow-x-auto rounded-lg border">
                                    <table class="table w-full text-xs">
                                        <thead>
                                            <tr>
                                                <th class="text-start">Campo</th>
                                                <th class="text-start">Antes</th>
                                                <th class="text-start">Depois</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($mudancas as $mudanca)
                                                <tr wire:key="historico-{{ $atividade->id }}-{{ $mudanca['campo'] }}">
                                                    <td class="text-default-500 font-mono">{{ $mudanca['campo'] }}</td>
                                                    <td class="text-default-500">{{ $mudanca['antes'] ?? '—' }}</td>
                                                    <td class="text-body-color font-medium">
                                                        {{ $mudanca['depois'] ?? '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        @endif
                    </li>
                @endforeach
            </ol>
            <div class="mt-4">{{ $atividades->links() }}</div>
        @endif
    </div>
</div>
