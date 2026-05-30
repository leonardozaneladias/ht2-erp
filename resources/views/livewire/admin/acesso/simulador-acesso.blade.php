<div class="space-y-6">
    <x-admin.page-header
        title="Simulador de acesso"
        subtitle="Veja o acesso efetivo de um usuário e a origem de cada permissão."
    />

    <x-shared.card>
        <x-shared.select
            name="usuarioId"
            label="Usuário"
            placeholder="Selecione um usuário para simular"
            :options="$usuarios"
            wire:model.live="usuarioId"
        />
    </x-shared.card>

    @if ($usuario === null)
        <x-shared.card>
            <x-shared.empty-state
                icon="tabler--user-search"
                title="Selecione um usuário"
                description="Escolha um usuário acima para visualizar o acesso efetivo dele."
            />
        </x-shared.card>
    @else
        @if ($usuario->hasRole(config('access.super_admin_role')))
            <x-shared.alert variant="warning" icon="tabler--crown" title="Super-admin">
                {{ $usuario->nome }} tem acesso irrestrito a todo o sistema.
            </x-shared.alert>
        @endif
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-admin.kpi-card
                label="Permitidas"
                :value="(string) $resumo['permitidos']"
                icon="tabler--circle-check"
                color="success"
            />
            <x-admin.kpi-card
                label="Negadas/sem acesso"
                :value="(string) $resumo['negados']"
                icon="tabler--circle-x"
                color="danger"
            />
            <x-admin.kpi-card
                label="Total no catálogo"
                :value="(string) $resumo['total']"
                icon="tabler--list-check"
                color="info"
            />
        </div>
        <x-shared.accordion>
            @foreach ($acessoPorModulo as $modulo => $itens)
                @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($modulo)?->label() ?? \Illuminate\Support\Str::headline($modulo))
                @php ($permitidasNoModulo = $itens->filter(fn ($dto) => $dto->permitido)->count())
                <x-shared.accordion-item
                    :id="'sim-' . $modulo"
                    :title="$moduloLabel . ' (' . $permitidasNoModulo . '/' . $itens->count() . ')'"
                    :open="$loop->first"
                >
                    <div class="overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr>
                                    <th>Permissão</th>
                                    <th>Situação</th>
                                    <th>Origem</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($itens as $dto)
                                    <tr wire:key="sim-{{ $modulo }}-{{ $dto->ability }}">
                                        <td class="font-medium">{{ $dto->ability }}</td>
                                        <td>
                                            @if ($dto->permitido)
                                                <x-shared.badge variant="success" icon="tabler--check">
                                                    Permitido</x-shared.badge
                                                >
                                            @elseif ($dto->origem === \App\Enums\OrigemAcesso::Deny)
                                                <x-shared.badge variant="danger" icon="tabler--ban">
                                                    Negado</x-shared.badge
                                                >
                                            @else
                                                <x-shared.badge variant="neutral" icon="tabler--minus">
                                                    Sem acesso</x-shared.badge
                                                >
                                            @endif
                                        </td>
                                        <td>
                                            <x-shared.badge :variant="$dto->origem->variant()">
                                                {{ $dto->origem->label() }}
                                                @if ($dto->roleDeOrigem) —{{ $dto->roleDeOrigem }}@endif
                                            </x-shared.badge>
                                        </td>
                                        <td class="text-default-600">
                                            @if ($dto->expiraEm)
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="iconify tabler--clock size-4"></span>
                                                    expira {{ $dto->expiraEm->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                            @if ($dto->motivo)
                                                <span class="text-default-400 block text-xs">{{ $dto->motivo }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-shared.accordion-item>
            @endforeach
        </x-shared.accordion>
    @endif
</div>
