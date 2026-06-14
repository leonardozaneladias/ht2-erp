@props ([
    'logoLight' => null,
    'logoDark' => null,
    'logoSm' => null,
])

{{--
    Painel de pré-visualização do Centro de Aparência. As amostras usam as CSS
    custom properties vivas (--color-* e --color-*-foreground), então refletem o
    preview na hora. O tema/skin/menu do chrome real já são previstos na página.
--}}
<x-shared.card title="Pré-visualização" subtitle="Atualiza ao vivo conforme você ajusta.">
    <div class="grid gap-4">
        {{-- Logos em contexto --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="flex h-16 items-center justify-center rounded-lg" style="background: #23303c">
                <img src="{{ $logoLight }}" alt="Logo (fundo escuro)" class="max-h-7 max-w-full" />
            </div>
            <div class="border-default-300 flex h-16 items-center justify-center rounded-lg border bg-white">
                <img src="{{ $logoDark }}" alt="Logo (fundo claro)" class="max-h-7 max-w-full" />
            </div>
            <div class="flex h-16 items-center justify-center rounded-lg" style="background: #23303c">
                <img src="{{ $logoSm }}" alt="Ícone (menu recolhido)" class="max-h-9" />
            </div>
            <div
                class="text-default-400 border-default-300 flex h-16 items-center justify-center gap-1.5 rounded-lg border text-xs"
            >
                <i class="iconify tabler--layout-sidebar size-4"></i>
                menu recolhido
            </div>
        </div>

        {{-- Paleta (base + foreground com contraste) --}}
        <div>
            <p class="text-default-500 mb-2 text-xs font-medium">Paleta</p>
            <div class="flex flex-wrap gap-2">
                @foreach (['primary' => 'Primária', 'secondary' => 'Secundária', 'success' => 'Sucesso', 'info' => 'Info', 'warning' => 'Aviso', 'danger' => 'Perigo'] as $chave => $rotulo)
                    <span
                        class="rounded-md px-2.5 py-1 text-xs font-medium"
                        style="background: var(--color-{{ $chave }}); color: var(--color-{{ $chave }}-foreground, #fff)"
                    >
                        {{ $rotulo }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Escala tonal da primária --}}
        <div>
            <p class="text-default-500 mb-2 text-xs font-medium">Escala da primária</p>
            <div class="flex h-6 overflow-hidden rounded-md">
                @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $tom)
                    <span class="flex-1" style="background: var(--color-primary-{{ $tom }})"></span>
                @endforeach
            </div>
        </div>

        {{-- Componentes de exemplo --}}
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-md px-3 py-2 text-sm font-medium"
                style="background: var(--color-primary); color: var(--color-primary-foreground, #fff)"
            >
                Ação primária
            </button>
            <button
                type="button"
                class="border-default-300 text-body-color rounded-md border px-3 py-2 text-sm font-medium"
            >
                Secundária
            </button>
            <span class="text-sm font-medium" style="color: var(--color-primary)">Link primário</span>
        </div>
    </div>
</x-shared.card>
