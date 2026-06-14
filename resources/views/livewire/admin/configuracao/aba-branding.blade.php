@php
    // Mostra o preview do upload só para formatos rasterizáveis; senão, o atual.
    $previewUrl = function ($arquivo, string $atual): string {
        $previewaveis = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

        if ($arquivo && in_array(strtolower((string) $arquivo->getClientOriginalExtension()), $previewaveis, true)) {
            return $arquivo->temporaryUrl();
        }

        return $atual;
    };

    $logos = [
        ['campo' => 'logo', 'rotulo' => 'Logo — tema claro', 'atual' => $logoAtual],
        ['campo' => 'logo_dark', 'rotulo' => 'Logo — tema escuro', 'atual' => $logoDarkAtual],
        ['campo' => 'logo_sm', 'rotulo' => 'Logo reduzido (menu recolhido)', 'atual' => $logoSmAtual],
        ['campo' => 'favicon', 'rotulo' => 'Favicon', 'atual' => $faviconAtual],
    ];

    $cores = [
        'cor_primaria' => 'Primária',
        'cor_secundaria' => 'Secundária',
        'cor_sucesso' => 'Sucesso',
        'cor_info' => 'Informação',
        'cor_warning' => 'Aviso',
        'cor_perigo' => 'Perigo',
    ];
@endphp

<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card
            class="rounded-t-none border-t-0"
            title="Identidade"
            subtitle="Nome e assinatura exibidos no sistema."
        >
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="nome_sistema" label="Nome do sistema" wire:model="nome_sistema" required />
                <x-shared.input name="slogan" label="Slogan" wire:model="slogan" hint="Texto curto de apoio à marca." />
            </div>
        </x-shared.card>

        <x-shared.card title="Logotipos e favicon" subtitle="Envie PNG/JPG/WEBP. Deixe em branco para manter o atual.">
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($logos as $item)
                    <div wire:key="upload-{{ $item['campo'] }}">
                        <x-shared.file-upload
                            variant="compact"
                            :name="$item['campo']"
                            :label="$item['rotulo']"
                            wire:model="{{ $item['campo'] }}"
                            :preview="$previewUrl(${$item['campo']} ?? null, $item['atual'])"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                        />
                        <div
                            wire:loading
                            wire:target="{{ $item['campo'] }}"
                            class="text-default-400 -mt-3 mb-2 text-xs"
                        >
                            Enviando…
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Cores do tema" subtitle="Refletem em toda a interface após salvar.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cores as $campo => $rotulo)
                    <div wire:key="cor-{{ $campo }}">
                        <x-shared.color-picker
                            :name="$campo"
                            :label="$rotulo"
                            wire:model="{{ $campo }}"
                            :value="${$campo} ?? '#000000'"
                        />
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar marca e tema
            </x-shared.loading-button>
        </div>
    </form>
</div>
