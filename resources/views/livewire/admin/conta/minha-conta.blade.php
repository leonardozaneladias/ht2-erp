<div>
    <x-admin.page-header title="Minha conta" subtitle="Seus dados, segurança e preferências." />

    <x-shared.tab-nav>
        @foreach ($abas as $aba)
            <x-shared.tab-trigger :id="$aba['value']" :icon="$aba['icon']" :active="$aba['value'] === $abaAtiva">
                {{ $aba['label'] }}
            </x-shared.tab-trigger>
        @endforeach
    </x-shared.tab-nav>

    {{-- Sem gap: a aba ativa conecta no topo do primeiro card de cada aba. --}}
    <div>
        <x-shared.tab-panel id="perfil" :active="$abaAtiva === 'perfil'">
            <livewire:admin.conta.perfil-conta />
        </x-shared.tab-panel>

        <x-shared.tab-panel id="seguranca" :active="$abaAtiva === 'seguranca'">
            <div class="grid gap-6">
                <livewire:admin.conta.trocar-senha />
                <livewire:admin.conta.seguranca-conta />
                <livewire:admin.conta.historico-logins />
            </div>
        </x-shared.tab-panel>

        <x-shared.tab-panel id="preferencias" :active="$abaAtiva === 'preferencias'">
            <livewire:admin.conta.preferencias-conta />
        </x-shared.tab-panel>
    </div>
</div>
