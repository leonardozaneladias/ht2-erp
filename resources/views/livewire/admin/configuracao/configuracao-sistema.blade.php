<div>
    <x-admin.page-header
        title="Configurações"
        subtitle="Dados do cliente, identidade visual e preferências do sistema."
    />

    <div class="grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
        {{-- ─── Navegação lateral + busca ──────────────────────────────── --}}
        <x-shared.card class="lg:sticky lg:top-20">
            <div class="relative mb-3">
                <i
                    class="iconify tabler--search text-default-400 pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                ></i>
                <input
                    type="search"
                    wire:model.live.debounce.250ms="busca"
                    placeholder="Buscar configuração…"
                    aria-label="Buscar configuração"
                    class="form-input ps-9 text-sm"
                />
            </div>

            <nav class="grid gap-0.5">
                @forelse ($abasFiltradas as $aba)
                    <button
                        type="button"
                        wire:key="nav-{{ $aba->value }}"
                        wire:click="irPara('{{ $aba->value }}')"
                        @class ([
                            'flex items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm font-medium transition-colors',
                            'bg-primary/10 text-primary' => $aba->value === $abaAtiva,
                            'text-body-color hover:bg-light/70' => $aba->value !== $abaAtiva,
                        ])
                        aria-current="{{ $aba->value === $abaAtiva ? 'page' : 'false' }}"
                    >
                        <i class="iconify {{ $aba->icon() }} size-4 shrink-0"></i>
                        <span class="truncate">{{ $aba->label() }}</span>
                    </button>
                @empty
                    <p class="text-default-400 px-3 py-6 text-center text-sm">Nenhuma configuração encontrada.</p>
                @endforelse

                {{-- Zona de perigo (ações destrutivas) --}}
                <div class="border-default-200 my-1 border-t"></div>
                <button
                    type="button"
                    wire:key="nav-danger"
                    wire:click="irPara('danger')"
                    @class ([
                        'flex items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm font-medium transition-colors',
                        'bg-danger/10 text-danger' => $abaAtiva === 'danger',
                        'text-danger/80 hover:bg-danger/5' => $abaAtiva !== 'danger',
                    ])
                    aria-current="{{ $abaAtiva === 'danger' ? 'page' : 'false' }}"
                >
                    <i class="iconify tabler--alert-triangle size-4 shrink-0"></i>
                    <span class="truncate">Zona de perigo</span>
                </button>
            </nav>
        </x-shared.card>

        {{-- ─── Conteúdo da aba ativa (render server-side) ─────────────── --}}
        <div wire:key="conteudo-{{ $abaAtiva }}">
            @switch ($abaAtiva)
                @case ('general')
                    <livewire:admin.configuracao.aba-empresa wire:key="aba-empresa" />
                    @break
                @case ('appearance')
                @case ('branding')
                    <livewire:admin.configuracao.aba-aparencia wire:key="aba-aparencia" />
                    @break
                @case ('login')
                    <livewire:admin.configuracao.aba-login wire:key="aba-login" />
                    @break
                @case ('email')
                    <livewire:admin.configuracao.aba-email wire:key="aba-email" />
                    @break
                @case ('localizacao')
                    <livewire:admin.configuracao.aba-localizacao wire:key="aba-localizacao" />
                    @break
                @case ('seguranca')
                    <livewire:admin.configuracao.aba-seguranca wire:key="aba-seguranca" />
                    @break
                @case ('danger')
                    <livewire:admin.configuracao.aba-danger-zone wire:key="aba-danger-zone" />
                    @break
            @endswitch
        </div>
    </div>
</div>
