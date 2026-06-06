<div>
    <div class="grid gap-6">
        {{-- Códigos de recuperação (exibidos uma única vez) --}}
        @if ($recoveryCodes !== [])
            <x-shared.alert variant="warning" title="Guarde seus códigos de recuperação">
                Cada código funciona uma única vez e não será exibido novamente. Use-os caso perca o acesso ao
                aplicativo autenticador.

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($recoveryCodes as $codigo)
                        <code class="bg-card border-default-300 rounded border px-2 py-1 text-center font-mono text-sm">
                            {{ $codigo }}
                        </code>
                    @endforeach
                </div>
            </x-shared.alert>
        @endif

        <x-shared.card
            title="Verificação em duas etapas (2FA)"
            subtitle="Um código temporário do seu celular, além da senha."
        >
            @if ($ativo)
                <div class="flex items-center gap-2">
                    <x-shared.badge variant="success">Ativa</x-shared.badge>
                    <span class="text-default-500 text-sm">Sua conta está protegida por 2FA.</span>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <x-shared.button
                        type="button"
                        variant="light"
                        wire:click="iniciarConfirmacaoDeSenha('regenerar')"
                        icon="tabler--refresh"
                    >
                        Gerar novos códigos de recuperação
                    </x-shared.button>
                    <x-shared.button
                        type="button"
                        variant="danger"
                        wire:click="iniciarConfirmacaoDeSenha('desativar')"
                        icon="tabler--shield-off"
                    >
                        Desativar 2FA
                    </x-shared.button>
                </div>
            @elseif ($configurando)
                <p class="text-default-500 mb-4 text-sm">Escaneie o QR Code no seu aplicativo autenticador (Google Authenticator, Authy…) e informe o código de 6 dígitos para concluir.</p>
                <div class="flex flex-col gap-5 md:flex-row md:items-start">
                    <div class="border-default-300 bg-card w-fit rounded-lg border p-3">{!! $svgQr !!}</div>

                    <form wire:submit="confirmar" class="grow md:max-w-xs">
                        <x-shared.input
                            name="codigoConfirmacao"
                            label="Código de verificação"
                            wire:model="codigoConfirmacao"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="000000"
                        />
                        <x-shared.loading-button type="submit" target="confirmar" icon="tabler--check">
                            Confirmar e ativar
                        </x-shared.loading-button>
                    </form>
                </div>
            @else
                <p class="text-default-500 mb-4 text-sm">A verificação em duas etapas está desativada. Recomendamos ativá-la para reforçar a segurança do seu acesso.</p>
                <x-shared.button
                    type="button"
                    wire:click="iniciarConfirmacaoDeSenha('ativar')"
                    icon="tabler--shield-lock"
                >
                    Ativar 2FA
                </x-shared.button>
            @endif
        </x-shared.card>
    </div>

    @include ('admin.partials.confirms-password')
</div>
