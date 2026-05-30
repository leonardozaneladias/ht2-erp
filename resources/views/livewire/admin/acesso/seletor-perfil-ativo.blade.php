<div>
    @if ($roles->count() > 1)
        <div class="relative" x-data="{ aberto: false }">
            <button
                type="button"
                class="hover:bg-light flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm"
                @click="aberto = !aberto;"
                @click.outside="aberto = false;"
            >
                <span class="iconify tabler--mask size-4"></span>
                <span class="hidden sm:inline">
                    @if ($perfilAtivoNome)
                        Atuando como
                        <span class="font-medium">{{ $perfilAtivoNome }}</span>
                    @else
                        Atuar como
                    @endif
                </span>
                <span class="iconify tabler--chevron-down text-default-400 size-4"></span>
            </button>

            <div
                x-show="aberto"
                x-cloak
                x-transition
                class="bg-card border-default-200 absolute end-0 z-50 mt-2 w-56 rounded-lg border py-1.5 shadow-lg"
            >
                <p class="text-default-400 px-4 py-1 text-xs font-semibold tracking-wide uppercase">Atuar como</p>

                <button
                    type="button"
                    class="text-body-color hover:bg-light flex w-full items-center justify-between gap-2 px-4 py-2 text-sm"
                    wire:click="definir(null)"
                >
                    Ver tudo (todos os perfis)
                    @if ($perfilAtivoId === null)
                        <span class="iconify tabler--check text-primary size-4"></span>
                    @endif
                </button>

                @foreach ($roles as $role)
                    <button
                        type="button"
                        class="text-body-color hover:bg-light flex w-full items-center justify-between gap-2 px-4 py-2 text-sm"
                        wire:click="definir({{ $role->id }})"
                        wire:key="lente-{{ $role->id }}"
                    >
                        {{ $role->name }}
                        @if ($perfilAtivoId === $role->id)
                            <span class="iconify tabler--check text-primary size-4"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
