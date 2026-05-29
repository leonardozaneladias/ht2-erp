<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo perfil' : 'Editar perfil'"
        subtitle="Defina o nome e as permissões do perfil."
    >
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.perfis.index') }}" wire:navigate>Cancelar</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit.prevent="salvar" class="card">
        <div class="card-body space-y-6">
            <x-shared.input name="name" label="Nome do perfil" wire:model="name" required />

            <div class="space-y-4">
                <label class="form-label">Permissões</label>
                @forelse ($permissoesAgrupadas as $modulo => $permissoes)
                    <div>
                        <h6 class="text-default-600 mb-2 text-sm font-medium tracking-wide uppercase">{{ $modulo }}</h6>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                            @foreach ($permissoes as $permissao)
                                <x-shared.checkbox
                                    :name="'perm_' . $permissao->id"
                                    :value="$permissao->name"
                                    :label="$permissao->name"
                                    wire:model="permissions"
                                />
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-default-500 text-sm">Nenhuma permissão cadastrada ainda.</p>
                @endforelse
                @error ('permissions')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="card-footer flex justify-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.perfis.index') }}" wire:navigate>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'criar' ? 'Criar perfil' : 'Salvar alterações' }}
            </button>
        </div>
    </form>
</div>
