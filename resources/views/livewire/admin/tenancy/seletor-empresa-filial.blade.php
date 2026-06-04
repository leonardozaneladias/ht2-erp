<div class="flex items-center gap-1.5">
    {{-- Empresa ativa --}}
    @if ($empresas->count() > 1)
        <div class="relative" x-data="{ aberto: false }">
            <button
                type="button"
                class="hover:bg-light flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm"
                @click="aberto = !aberto;"
                @click.outside="aberto = false;"
            >
                <span class="iconify tabler--building size-4"></span>
                <span class="hidden max-w-40 truncate sm:inline">
                    <span class="font-medium">{{ $empresaAtivaNome ?? 'Selecionar empresa' }}</span>
                </span>
                <span class="iconify tabler--chevron-down text-default-400 size-4"></span>
            </button>

            <div
                x-show="aberto"
                x-cloak
                x-transition
                class="bg-card border-default-200 absolute end-0 z-50 mt-2 max-h-80 w-60 overflow-y-auto rounded-lg border py-1.5 shadow-lg"
            >
                <p class="text-default-400 px-4 py-1 text-xs font-semibold tracking-wide uppercase">Empresa</p>

                @foreach ($empresas as $empresa)
                    <button
                        type="button"
                        class="text-body-color hover:bg-light flex w-full items-center justify-between gap-2 px-4 py-2 text-start text-sm"
                        wire:click="definirEmpresa({{ $empresa->id }})"
                        wire:key="empresa-{{ $empresa->id }}"
                    >
                        <span class="truncate">{{ $empresa->nome }}</span>
                        @if ($empresaAtivaId === $empresa->id)
                            <span class="iconify tabler--check text-primary size-4 shrink-0"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($empresaAtivaNome)
        <span class="text-default-500 hidden items-center gap-1.5 px-2 py-1.5 text-sm sm:flex">
            <span class="iconify tabler--building size-4"></span>
            <span class="max-w-40 truncate font-medium">{{ $empresaAtivaNome }}</span>
        </span>
    @endif

    {{-- Filial ativa (só quando há mais de uma disponível na empresa) --}}
    @if ($filiais->count() > 1)
        <div class="relative" x-data="{ aberto: false }">
            <button
                type="button"
                class="hover:bg-light flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm"
                @click="aberto = !aberto;"
                @click.outside="aberto = false;"
            >
                <span class="iconify tabler--map-pin size-4"></span>
                <span class="hidden max-w-36 truncate sm:inline"> {{ $filialAtivaNome ?? 'Todas as filiais' }} </span>
                <span class="iconify tabler--chevron-down text-default-400 size-4"></span>
            </button>

            <div
                x-show="aberto"
                x-cloak
                x-transition
                class="bg-card border-default-200 absolute end-0 z-50 mt-2 max-h-80 w-56 overflow-y-auto rounded-lg border py-1.5 shadow-lg"
            >
                <p class="text-default-400 px-4 py-1 text-xs font-semibold tracking-wide uppercase">Filial</p>

                <button
                    type="button"
                    class="text-body-color hover:bg-light flex w-full items-center justify-between gap-2 px-4 py-2 text-start text-sm"
                    wire:click="definirFilial(null)"
                >
                    Todas as filiais
                    @if ($filialAtivaId === null)
                        <span class="iconify tabler--check text-primary size-4 shrink-0"></span>
                    @endif
                </button>

                @foreach ($filiais as $filial)
                    <button
                        type="button"
                        class="text-body-color hover:bg-light flex w-full items-center justify-between gap-2 px-4 py-2 text-start text-sm"
                        wire:click="definirFilial({{ $filial->id }})"
                        wire:key="filial-{{ $filial->id }}"
                    >
                        <span class="truncate">{{ $filial->nome }}</span>
                        @if ($filialAtivaId === $filial->id)
                            <span class="iconify tabler--check text-primary size-4 shrink-0"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
