<div class="space-y-6">
    <x-admin.page-header
        title="Matriz de permissões"
        subtitle="Configure rapidamente o que cada perfil pode fazer, agrupado por módulo."
    />

    @if ($perfis->isEmpty())
        <x-shared.card>
            <x-shared.empty-state
                icon="tabler--table-off"
                title="Nenhum perfil editável"
                description="Você só pode editar perfis de nível abaixo do seu. Crie um perfil para começar."
            />
        </x-shared.card>
    @else
        @if ($superAdmin)
            <x-shared.alert variant="warning" icon="tabler--crown">
                O perfil <strong>{{ $superAdmin->name }}</strong> tem acesso irrestrito por padrão e não é editável.
            </x-shared.alert>
        @endif
        <x-shared.accordion>
            @foreach ($permissoesPorModulo as $modulo => $permissoes)
                @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($modulo)?->label() ?? \Illuminate\Support\Str::headline($modulo))
                @php ($idsModulo = $permissoes->pluck('id')->map(fn ($id) => (int) $id)->all())
                <x-shared.accordion-item :id="'mod-' . $modulo" :title="$moduloLabel" :open="$loop->first">
                    <div class="overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="bg-card sticky left-0 z-10 min-w-64">Permissão</th>
                                    @foreach ($perfis as $perfil)
                                        <th class="text-center align-bottom">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="font-medium">{{ $perfil->name }}</span>
                                                <span class="text-default-400 text-xs">nível {{ $perfil->nivel }}</span>
                                                <div class="flex gap-1">
                                                    <button
                                                        type="button"
                                                        class="btn btn-xs btn-light"
                                                        title="Marcar todo o módulo"
                                                        wire:click="marcarModulo({{ $perfil->id }}, {{ \Illuminate\Support\Js::from($idsModulo) }}, true)"
                                                    >
                                                        <span class="iconify tabler--checks size-3.5"></span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-xs btn-light"
                                                        title="Limpar o módulo"
                                                        wire:click="marcarModulo({{ $perfil->id }}, {{ \Illuminate\Support\Js::from($idsModulo) }}, false)"
                                                    >
                                                        <span class="iconify tabler--square-off size-3.5"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissoes as $permissao)
                                    <tr wire:key="perm-{{ $modulo }}-{{ $permissao->id }}">
                                        <td class="bg-card sticky left-0 z-10">
                                            <div class="font-medium">{{ $permissao->label ?? $permissao->name }}</div>
                                            @if ($permissao->descricao)
                                                <div class="text-default-500 text-xs">{{ $permissao->descricao }}</div>
                                            @endif
                                        </td>
                                        @foreach ($perfis as $perfil)
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="checkbox checkbox-primary"
                                                    wire:model="grade.{{ $perfil->id }}.{{ $permissao->id }}"
                                                    aria-label="{{ $permissao->name }} para {{ $perfil->name }}"
                                                />
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-shared.accordion-item>
            @endforeach
        </x-shared.accordion>
        <x-shared.card title="Salvar alterações" subtitle="As mudanças de cada perfil são salvas individualmente.">
            <div class="flex flex-wrap gap-2">
                @foreach ($perfis as $perfil)
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        wire:click="salvarPerfil({{ $perfil->id }})"
                        wire:loading.attr="disabled"
                        wire:confirm="Salvar as permissões do perfil {{ $perfil->name }}? Isto altera o acesso dos usuários deste perfil."
                    >
                        <span class="iconify tabler--device-floppy me-1 size-4"></span>
                        Salvar {{ $perfil->name }}
                    </button>
                @endforeach
            </div>
        </x-shared.card>
    @endif
</div>
