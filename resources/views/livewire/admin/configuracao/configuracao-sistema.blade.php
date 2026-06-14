<div>
    <x-admin.page-header
        title="Configurações"
        subtitle="Dados do cliente, identidade visual e preferências do sistema."
    />

    <x-shared.tab-nav>
        @foreach ($abas as $aba)
            <x-shared.tab-trigger :id="$aba->value" :icon="$aba->icon()" :active="$aba->value === $abaAtiva">
                {{ $aba->label() }}
            </x-shared.tab-trigger>
        @endforeach
    </x-shared.tab-nav>

    {{-- Sem gap: a aba ativa conecta no topo do card de cada aba (rounded-t-none border-t-0). --}}
    <div>
        @foreach ($abas as $aba)
            <x-shared.tab-panel :id="$aba->value" :active="$aba->value === $abaAtiva">
                @switch ($aba->value)
                    @case ('general')
                        <livewire:admin.configuracao.aba-empresa />
                        @break
                    @case ('branding')
                        <livewire:admin.configuracao.aba-branding />
                        @break
                    @case ('login')
                        <livewire:admin.configuracao.aba-login />
                        @break
                    @case ('email')
                        <livewire:admin.configuracao.aba-email />
                        @break
                    @case ('localizacao')
                        <livewire:admin.configuracao.aba-localizacao />
                        @break
                    @case ('seguranca')
                        <livewire:admin.configuracao.aba-seguranca />
                        @break
                    @default
                        <x-shared.card
                            class="rounded-t-none border-t-0"
                            :title="$aba->label()"
                            :subtitle="$aba->descricao()"
                        >
                            <x-shared.empty-state
                                size="sm"
                                :icon="$aba->icon()"
                                title="Em breve"
                                description="Esta seção será habilitada nos próximos passos da configuração."
                            />
                        </x-shared.card>
                @endswitch
            </x-shared.tab-panel>
        @endforeach
    </div>
</div>
