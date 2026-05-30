<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo perfil' : 'Editar perfil'"
        subtitle="Defina nome, nível hierárquico e permissões do perfil."
    >
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.perfis.index') }}" wire:navigate>Cancelar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit.prevent="salvar" class="space-y-6">
        <x-shared.card title="Dados do perfil">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-shared.input
                        name="name"
                        label="Nome do perfil"
                        placeholder="ex.: Gerente Financeiro"
                        wire:model="name"
                        required
                    />
                </div>
                <x-shared.input
                    name="nivel"
                    type="number"
                    label="Nível hierárquico"
                    hint="Maior = mais poder. Você só gere perfis abaixo do seu nível."
                    min="1"
                    max="999"
                    wire:model="nivel"
                    required
                />
            </div>
            <div class="mt-4">
                <x-shared.textarea
                    name="descricao"
                    label="Descrição (opcional)"
                    rows="2"
                    placeholder="Para que serve este perfil?"
                    wire:model="descricao"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Permissões">
            <div class="space-y-4">
                @forelse ($permissoesAgrupadas as $grupo => $permissoes)
                    @php ($moduloLabel = \App\Enums\ModuloAcesso::tryFrom($grupo)?->label() ?? \Illuminate\Support\Str::headline($grupo))
                    <div class="border-default-200 rounded-lg border p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-default-500 text-xs font-semibold tracking-wide uppercase">
                                {{ $moduloLabel }}
                            </p>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="btn btn-xs btn-light"
                                    wire:click="marcarModulo('{{ $grupo }}')"
                                >
                                    <span class="iconify tabler--checks me-1 size-3.5"></span> Marcar tudo
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-xs btn-light"
                                    wire:click="limparModulo('{{ $grupo }}')"
                                >
                                    <span class="iconify tabler--square-off me-1 size-3.5"></span> Limpar
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissoes as $permissao)
                                <x-shared.checkbox
                                    :name="'perm-' . $permissao->id"
                                    :value="$permissao->name"
                                    :label="$permissao->label ?? $permissao->name"
                                    :hint="$permissao->descricao"
                                    wire:model="permissions"
                                />
                            @endforeach
                        </div>
                    </div>
                @empty
                    <x-shared.empty-state
                        icon="tabler--lock"
                        title="Nenhuma permissão disponível"
                        description="Rode php artisan access:sync para popular o catálogo de permissões."
                    />
                @endforelse
                @error ('permissions')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </x-shared.card>

        <div class="flex justify-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.perfis.index') }}" wire:navigate>Cancelar</a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="salvar">Salvar perfil</span>
                <span wire:loading wire:target="salvar">Salvando...</span>
            </button>
        </div>
    </form>
</div>
