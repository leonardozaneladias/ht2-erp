@php
    // Classes de tom como literais estáticos para o Tailwind detectar no scan.
    $toneClasses = [
        'info' => 'bg-info/15 text-info',
        'success' => 'bg-success/15 text-success',
        'warning' => 'bg-warning/15 text-warning',
        'danger' => 'bg-danger/15 text-danger',
    ];
@endphp

<div
    class="topbar-item relative inline-flex"
    x-data="{ open: false }"
    @keydown.escape.window="open = false;"
    wire:poll.60s
>
    <button
        type="button"
        class="topbar-link relative flex cursor-pointer items-center"
        aria-label="Abrir notificações"
        :aria-expanded="open"
        @click="open = !open;"
    >
        <i class="iconify tabler--bell topbar-link-icon"></i>

        @if ($this->naoLidas > 0)
            <x-shared.badge
                variant="danger"
                solid
                class="absolute -end-px -top-[13px] min-w-4 px-1 py-0 text-[10px] leading-none text-white"
            >
                {{ $this->naoLidas > 99 ? '99+' : $this->naoLidas }}
            </x-shared.badge>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        @click.outside="open = false;"
        class="bg-card border-default-300 absolute end-0 top-full z-30 mt-2 w-80 rounded border py-1 shadow"
        style="display: none"
    >
        <div class="border-default-300 flex items-center justify-between gap-3 border-b px-3.5 py-2.5">
            <h6 class="text-body-color text-base font-semibold">Notificações</h6>

            @if ($this->naoLidas > 0)
                <button
                    type="button"
                    wire:click="marcarTodasComoLidas"
                    class="text-primary cursor-pointer text-xs font-semibold hover:underline"
                >
                    Marcar todas como lidas
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto" data-simplebar>
            @forelse ($this->recentes as $notificacao)
                @php
                    $dados = $notificacao->data;
                    $tipo = \App\Enums\TipoNotificacao::tryFrom($dados['tipo'] ?? 'info') ?? \App\Enums\TipoNotificacao::Info;
                    $tone = $toneClasses[$tipo->variant()] ?? $toneClasses['info'];
                    $naoLida = $notificacao->read_at === null;
                @endphp
                <button
                    type="button"
                    wire:click="abrir('{{ $notificacao->id }}')"
                    @class ([
                        'hover:bg-default-100 flex w-full items-start gap-3 px-4 py-3 text-start transition-colors',
                        'bg-primary/5' => $naoLida,
                    ])
                >
                    <span class="shrink-0">
                        <span class="flex size-9 items-center justify-center rounded-full {{ $tone }}">
                            <i class="iconify {{ $dados['icon'] ?? $tipo->icon() }} text-lg"></i>
                        </span>
                    </span>

                    <span class="min-w-0 grow">
                        <span class="text-body-color block font-medium">{{ $dados['titulo'] ?? 'Notificação' }}</span>
                        <span
                            class="text-default-500 block text-xs"
                            >{{ \Illuminate\Support\Str::limit($dados['mensagem'] ?? '', 80) }}</span
                        >
                        <span class="text-default-400 text-2xs">{{ $notificacao->created_at->diffForHumans() }}</span>
                    </span>

                    @if ($naoLida)
                        <span class="bg-primary mt-1.5 size-2 shrink-0 rounded-full"></span>
                    @endif
                </button>
            @empty
                <div class="px-4 py-10 text-center">
                    <i class="iconify tabler--bell-off text-default-400 text-2xl"></i>
                    <p class="text-default-500 mt-2 text-sm">Nenhuma notificação por aqui.</p>
                </div>
            @endforelse
        </div>

        <a
            href="{{ route('admin.conta.notificacoes') }}"
            wire:navigate
            class="text-body-color border-default-300 hover:text-primary flex justify-center border-t py-3 text-sm font-bold underline underline-offset-2 transition-colors"
        >
            Ver todas as notificações
        </a>
    </div>
</div>
