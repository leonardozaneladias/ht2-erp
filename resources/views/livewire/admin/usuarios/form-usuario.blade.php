<div class="space-y-6">
    <x-admin.page-header
        :title="$modo === 'criar' ? 'Novo usuário admin' : 'Editar usuário admin'"
        subtitle="Defina identificação, senha e perfis de acesso."
    >
        <x-slot:actions>
            <a class="btn btn-outline-secondary" href="{{ route('admin.usuarios.index') }}" wire:navigate> Cancelar </a>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit.prevent="salvar" class="card">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-shared.input name="nome" label="Nome completo" wire:model="nome" required />
                <x-shared.input type="email" name="email" label="E-mail" wire:model="email" required />
                <x-shared.password-input
                    name="password"
                    :label="$modo === 'criar' ? 'Senha' : 'Nova senha (deixe vazio para manter)'"
                    wire:model="password"
                    :required="$modo === 'criar'"
                />
                <x-shared.toggle name="ativo" label="Usuário ativo" wire:model="ativo" />
            </div>

            <div>
                <label class="form-label">Perfis</label>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                    @foreach ($rolesDisponiveis as $role)
                        <x-shared.checkbox
                            :name="'roles_' . $role->name"
                            :value="$role->name"
                            :label="$role->name"
                            wire:model="roles"
                        />
                    @endforeach
                </div>
                @error ('roles')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="card-footer flex justify-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.usuarios.index') }}" wire:navigate>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                {{ $modo === 'criar' ? 'Criar usuário' : 'Salvar alterações' }}
            </button>
        </div>
    </form>
</div>
