{{-- Grid de ícones curados (IconesMenu). Os literais aqui garantem o CSS no
     build do iconify/tailwind. Espera: $modelo (propriedade Livewire) e
     $selecionado (valor atual). --}}
<div class="grid grid-cols-8 gap-1.5">
    @foreach (\App\Support\Menu\IconesMenu::disponiveis() as $opcao)
        <button
            type="button"
            wire:click="$set('{{ $modelo }}', '{{ $opcao }}')"
            title="{{ $opcao }}"
            @class ([
                'border-default-200 hover:border-primary/60 hover:bg-light flex size-9 cursor-pointer items-center justify-center rounded-lg border transition-colors duration-200',
                'border-primary bg-primary/10 text-primary' => $selecionado === $opcao,
                'text-default-600' => $selecionado !== $opcao,
            ])
        >
            <i class="iconify {{ $opcao }} text-lg"></i>
        </button>
    @endforeach
</div>
