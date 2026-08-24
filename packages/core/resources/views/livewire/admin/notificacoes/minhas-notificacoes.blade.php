@php
    // Classes de tom como literais estáticos para o Tailwind detectar no scan.
    $toneClasses = [
        'info' => 'bg-info/15 text-info',
        'success' => 'bg-success/15 text-success',
        'warning' => 'bg-warning/15 text-warning',
        'danger' => 'bg-danger/15 text-danger',
    ];
@endphp

<div>
    <x-admin.page-header title="Notificações" subtitle="Seus alertas e comunicados do sistema.">
        <x-slot:actions>
            @if ($naoLidas > 0)
                <x-shared.button appearance="outline" icon="tabler--checks" wire:click="marcarTodasComoLidas">
                    Marcar todas como lidas
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 flex items-center gap-2">
        <button
            type="button"
            wire:click="definirFiltro('todas')"
            @class ([
                'cursor-pointer rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                'bg-primary text-white' => $filtro === 'todas',
                'bg-default-100 text-body-color hover:bg-default-200' => $filtro !== 'todas',
            ])
        >
            Todas
        </button>

        <button
            type="button"
            wire:click="definirFiltro('nao_lidas')"
            @class ([
                'flex cursor-pointer items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                'bg-primary text-white' => $filtro === 'nao_lidas',
                'bg-default-100 text-body-color hover:bg-default-200' => $filtro !== 'nao_lidas',
            ])
        >
            Não lidas
            @if ($naoLidas > 0)
                <span class="bg-danger min-w-5 rounded-full px-1.5 text-center text-xs font-bold text-white">
                    {{ $naoLidas }}
                </span>
            @endif
        </button>
    </div>

    <x-shared.card>
        @if ($notificacoes !== null && $notificacoes->isNotEmpty())
            <ul class="divide-default-200 -my-1 divide-y">
                @foreach ($notificacoes as $notificacao)
                    @php
                        $dados = $notificacao->data;
                        $tipo = \HT2ML\Core\Enums\TipoNotificacao::tryFrom($dados['tipo'] ?? 'info') ?? \HT2ML\Core\Enums\TipoNotificacao::Info;
                        $tone = $toneClasses[$tipo->variant()] ?? $toneClasses['info'];
                        $naoLida = $notificacao->read_at === null;
                    @endphp
                    <li
                        wire:key="notif-{{ $notificacao->id }}"
                        @class (['flex items-start gap-4 py-4', 'bg-primary/5 -mx-5 px-5' => $naoLida])
                    >
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $tone }}">
                            <i class="iconify {{ $dados['icon'] ?? $tipo->icon() }} text-xl"></i>
                        </span>

                        <div class="min-w-0 grow">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="text-body-color font-semibold"
                                    >{{ $dados['titulo'] ?? 'Notificação' }}</span
                                >
                                <x-shared.badge :variant="$tipo->variant()" size="sm">
                                    {{ $tipo->label() }}</x-shared.badge
                                >
                                @if ($naoLida)
                                    <span class="bg-primary size-2 rounded-full" title="Não lida"></span>
                                @endif
                            </div>

                            <div
                                class="text-default-500 [&_a]:text-primary [&_a]:underline [&_ol]:list-decimal [&_ol]:ps-5 [&_ul]:list-disc [&_ul]:ps-5 mt-1 text-sm"
                            >
                                {!! \HT2ML\Core\Support\Html\HtmlSanitizer::clean($dados['mensagem'] ?? '') !!}
                            </div>

                            <div class="text-default-400 mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                <span>{{ $notificacao->created_at->diffForHumans() }}</span>

                                @if (filled($dados['url'] ?? null))
                                    <a
                                        href="{{ $dados['url'] }}"
                                        wire:navigate
                                        class="text-primary font-semibold hover:underline"
                                    >
                                        Abrir
                                    </a>
                                @endif

                                @if ($naoLida)
                                    <button
                                        type="button"
                                        wire:click="marcarComoLida('{{ $notificacao->id }}')"
                                        class="hover:text-primary cursor-pointer font-semibold"
                                    >
                                        Marcar como lida
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    wire:click="excluir('{{ $notificacao->id }}')"
                                    class="hover:text-danger cursor-pointer font-semibold"
                                >
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $notificacoes->links() }}</div>
        @else
            <x-shared.empty-state
                icon="tabler--bell-off"
                title="Nenhuma notificação"
                :description="$filtro === 'nao_lidas' ? 'Você está em dia — nenhuma notificação não lida.' : 'Quando algo acontecer, suas notificações aparecem aqui.'"
            />
        @endif
    </x-shared.card>
</div>
