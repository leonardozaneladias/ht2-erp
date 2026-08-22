<div
    class="space-y-6"
    x-data
    x-on:auditoria-abrir-detalhe.window="window.HSOverlay?.open(document.querySelector('#drawer-auditoria-detalhe'));"
>
    <x-admin.page-header title="Logs de auditoria" subtitle="Histórico append-only de mudanças relevantes do painel." />

    @if ($this->podeExpurgar)
        <div class="mb-4 flex justify-end">
            <x-shared.button type="button" variant="light" icon="tabler--trash" wire:click="solicitarExpurgo">
                Expurgar logs antigos
            </x-shared.button>
        </div>
    @endif

    <livewire:admin.auditoria.auditoria-table />

    {{-- Drawer de detalhes: contexto completo + diff antes/depois da atividade. --}}
    <x-admin.drawer id="drawer-auditoria-detalhe" title="Detalhes da atividade" size="xl">
        @if ($this->detalhe)
            @php ($atividade = $this->detalhe)
            <div class="space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-shared.badge variant="default" size="sm">{{ $atividade->log_name ?? '—' }}</x-shared.badge>
                        <x-shared.badge variant="info" size="sm">{{ $atividade->event ?? '—' }}</x-shared.badge>
                    </div>
                    <p class="text-body-color mt-2 text-sm font-medium">{{ $atividade->description }}</p>
                </div>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-default-400 text-xs uppercase">Quando</dt>
                        <dd class="text-body-color mt-0.5">
                            {{ $atividade->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-default-400 text-xs uppercase">Quem</dt>
                        <dd class="text-body-color mt-0.5">
                            {{ $atividade->causer?->getAttribute('nome') ?? $atividade->causer?->getAttribute('email') ?? 'sistema' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-default-400 text-xs uppercase">Empresa</dt>
                        <dd class="text-body-color mt-0.5">{{ $atividade->empresa?->getAttribute('nome') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-default-400 text-xs uppercase">Filial</dt>
                        <dd class="text-body-color mt-0.5">{{ $atividade->filial?->getAttribute('nome') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-default-400 text-xs uppercase">Registro</dt>
                        <dd class="text-body-color mt-0.5">
                            {{ $atividade->getProperty('subject_label') ?? ($atividade->subject_type !== null ? class_basename($atividade->subject_type) . ' #' . $atividade->subject_id : '—') }}
                        </dd>
                    </div>
                    @if ($atividade->getProperty('impersonado_por'))
                        <div>
                            <dt class="text-warning text-xs uppercase">Personificado por</dt>
                            <dd class="text-body-color mt-0.5">
                                {{ data_get($atividade->getProperty('impersonado_por'), 'nome') ?? '—' }}
                            </dd>
                        </div>
                    @endif
                    @if ($atividade->getProperty('contexto'))
                        <div>
                            <dt class="text-default-400 text-xs uppercase">IP</dt>
                            <dd class="text-body-color mt-0.5">
                                {{ data_get($atividade->getProperty('contexto'), 'ip') ?? '—' }}
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-default-400 text-xs uppercase">Navegador</dt>
                            <dd class="text-default-500 mt-0.5 text-xs break-all">
                                {{ data_get($atividade->getProperty('contexto'), 'user_agent') ?? '—' }}
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($this->mudancas !== [])
                    <div>
                        <h4 class="text-body-color mb-2 text-sm font-semibold">Mudanças de dados</h4>
                        <x-shared.static-table
                            class="border-default-300 border"
                            :headers="['Campo', 'Antes', 'Depois']"
                        >
                            @foreach ($this->mudancas as $mudanca)
                                <tr wire:key="mudanca-{{ $atividade->id }}-{{ $mudanca['campo'] }}">
                                    <td class="text-default-500 font-mono text-xs">{{ $mudanca['campo'] }}</td>
                                    <td class="text-default-500">{{ $mudanca['antes'] ?? '—' }}</td>
                                    <td class="text-body-color font-medium">{{ $mudanca['depois'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </x-shared.static-table>
                    </div>
                @else
                    <x-shared.alert variant="info" :dismissible="false">
                        Evento de domínio sem diff de atributos
                        @if ($atividade->properties?->isNotEmpty())
                            — propriedades:
                            <span
                                class="font-mono text-xs break-all"
                                >{{ $atividade->properties->except(['subject_label', 'contexto', 'impersonado_por'])->toJson(JSON_UNESCAPED_UNICODE) }}</span
                            >
                        @endif
                    </x-shared.alert>
                @endif
            </div>
        @else
            <p class="text-default-400 text-sm">Escolha um registro na tabela para ver os detalhes.</p>
        @endif
    </x-admin.drawer>
</div>
