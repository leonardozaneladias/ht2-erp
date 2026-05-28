@extends ('admin.dev.components.shell', [
    'title' => 'Preview • família x-shared.list-group',
    'description' => 'Composição vertical para dados estruturados, contadores por status e navegação lateral sem virar tabs.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
        <x-shared.card title="Dados estruturados" subtitle="Ficha compacta do formando">
            <x-shared.list-group>
                <x-shared.list-group-item>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-default-400">CPF</span>
                        <span class="text-body-color font-mono">123.456.789-00</span>
                    </div>
                </x-shared.list-group-item>
                <x-shared.list-group-item>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-default-400">Curso</span>
                        <span class="text-body-color">Odontologia</span>
                    </div>
                </x-shared.list-group-item>
                <x-shared.list-group-item>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-default-400">Telefone</span>
                        <span class="text-body-color font-mono">(61) 99999-0000</span>
                    </div>
                </x-shared.list-group-item>
                <x-shared.list-group-item>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-default-400">E-mail</span>
                        <a href="#!" class="text-primary">ana.prado@exemplo.com</a>
                    </div>
                </x-shared.list-group-item>
            </x-shared.list-group>
        </x-shared.card>

        <x-shared.card title="Contadores e navegação" subtitle="Dois usos recorrentes no admin">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-3">
                    <p class="text-body-color text-sm font-semibold">Indicadores financeiros</p>
                    <x-shared.list-group>
                        <x-shared.list-group-item>
                            <div class="flex items-center justify-between gap-3">
                                <span>Pendentes</span>
                                <x-shared.badge variant="warning">14</x-shared.badge>
                            </div>
                        </x-shared.list-group-item>
                        <x-shared.list-group-item>
                            <div class="flex items-center justify-between gap-3">
                                <span>Pagas</span>
                                <x-shared.badge variant="success">132</x-shared.badge>
                            </div>
                        </x-shared.list-group-item>
                        <x-shared.list-group-item>
                            <div class="flex items-center justify-between gap-3">
                                <span>Vencidas</span>
                                <x-shared.badge variant="danger">8</x-shared.badge>
                            </div>
                        </x-shared.list-group-item>
                    </x-shared.list-group>
                </div>

                <div class="space-y-3">
                    <p class="text-body-color text-sm font-semibold">Sub-rotas da conta</p>
                    <x-shared.list-group>
                        <x-shared.list-group-item href="#!" active>
                            <div class="flex items-center gap-2">
                                <i class="iconify tabler--user text-base"></i>
                                <span>Perfil</span>
                            </div>
                        </x-shared.list-group-item>
                        <x-shared.list-group-item href="#!">
                            <div class="flex items-center gap-2">
                                <i class="iconify tabler--lock text-base"></i>
                                <span>Senha</span>
                            </div>
                        </x-shared.list-group-item>
                        <x-shared.list-group-item href="#!">
                            <div class="flex items-center gap-2">
                                <i class="iconify tabler--bell text-base"></i>
                                <span>Notificações</span>
                            </div>
                        </x-shared.list-group-item>
                    </x-shared.list-group>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
