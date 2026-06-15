@php
    // Preview de upload só para formatos rasterizáveis; senão, mostra o atual.
    $previewUrl = function ($arquivo, string $atual): string {
        $previewaveis = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

        if ($arquivo && in_array(strtolower((string) $arquivo->getClientOriginalExtension()), $previewaveis, true)) {
            return $arquivo->temporaryUrl();
        }

        return $atual;
    };

    $logos = [
        ['campo' => 'logo',      'rotulo' => 'Logo — fundo escuro',   'atual' => $logoAtual,    'dica' => '240 × 60 px · PNG transparente · exibido na sidebar (tema claro)'],
        ['campo' => 'logo_dark', 'rotulo' => 'Logo — fundo claro',    'atual' => $logoDarkAtual,'dica' => '240 × 60 px · PNG transparente · exibido na sidebar (tema escuro)'],
        ['campo' => 'logo_sm',   'rotulo' => 'Ícone (menu recolhido)','atual' => $logoSmAtual,  'dica' => '48 × 48 px · quadrado · exibido quando o menu está recolhido'],
        ['campo' => 'favicon',   'rotulo' => 'Favicon',               'atual' => $faviconAtual, 'dica' => '32 × 32 px · ICO ou PNG · recomendado incluir resoluções 16, 32 e 48 px'],
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

<div
    x-data="{
        push() {
            if (!window.AppearancePreview) return;
            window.AppearancePreview.aplicar({
                atributos: {
                    tema: $wire.tema_padrao,
                    skin: $wire.skin,
                    topbar_color: $wire.topbar_color,
                    menu_color: $wire.menu_color,
                    layout_width: $wire.layout_width,
                    sidenav_size: $wire.sidenav_size_padrao,
                },
                cores: {
                    primary: $wire.cor_primaria,
                    secondary: $wire.cor_secundaria,
                    success: $wire.cor_sucesso,
                    warning: $wire.cor_warning,
                    danger: $wire.cor_perigo,
                    info: $wire.cor_info,
                },
            });
        },
    }"
    x-init="
        [
            'cor_primaria',
            'cor_secundaria',
            'cor_sucesso',
            'cor_warning',
            'cor_perigo',
            'cor_info',
            'tema_padrao',
            'skin',
            'topbar_color',
            'menu_color',
            'layout_width',
            'sidenav_size_padrao',
        ].forEach((p) => $wire.$watch(p, () => push()));
        push();
    "
    @appearance-applied.window="sessionStorage.removeItem('__THEME_CONFIG__');"
    @appearance-discarded.window="window.AppearancePreview && window.AppearancePreview.descartar();"
    class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"
>
    {{-- ─── Coluna de controles ─────────────────────────────────────────── --}}
    <div class="grid content-start gap-6">
        {{-- Presets --}}
        <x-shared.card
            title="Presets de tema"
            subtitle="Comece por um preset e ajuste o que quiser. Aplica cores e chrome (não salva sozinho)."
        >
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($presets as $preset)
                    <button
                        type="button"
                        wire:click="aplicarPreset('{{ $preset->value }}')"
                        class="border-default-300 hover:border-primary hover:bg-light/60 flex items-center gap-3 rounded-lg border p-3 text-start transition-colors"
                    >
                        <span
                            class="size-9 shrink-0 rounded-full ring-1 ring-black/5"
                            style="background: {{ $preset->corAmostra() }}"
                        ></span>
                        <span class="min-w-0">
                            <span class="text-body-color block text-sm font-semibold">{{ $preset->label() }}</span>
                            <span class="text-default-400 block truncate text-xs">{{ $preset->descricao() }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </x-shared.card>

        {{-- Identidade --}}
        <x-shared.card title="Identidade" subtitle="Nome e assinatura exibidos no sistema.">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="nome_sistema" label="Nome do sistema" wire:model="nome_sistema" required />
                <x-shared.input name="slogan" label="Slogan" wire:model="slogan" hint="Texto curto de apoio à marca." />
            </div>
        </x-shared.card>

        {{-- Logotipos --}}
        <x-shared.card title="Logotipos e favicon" subtitle="PNG/JPG/WEBP. Deixe em branco para manter o atual.">
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
                        <p class="text-default-400 mt-1 text-xs">{{ $item['dica'] }}</p>
                        <div wire:loading wire:target="{{ $item['campo'] }}" class="text-default-400 mt-1 text-xs">
                            Enviando…
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        {{-- Cores --}}
        <x-shared.card title="Cores da marca" subtitle="A primária gera a escala tonal automaticamente.">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cores as $campo => $rotulo)
                    <div wire:key="cor-{{ $campo }}">
                        <x-shared.color-picker
                            :name="$campo"
                            :label="$rotulo"
                            wire:model.live="{{ $campo }}"
                            :value="${$campo} ?? '#000000'"
                        />
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        {{-- Layout e tema --}}
        <x-shared.card title="Layout e tema" subtitle="Tema padrão, skin, cores do chrome e largura.">
            <div class="grid gap-5">
                {{-- Tema padrão --}}
                <div>
                    <span class="form-label">Tema padrão</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($temas as $tema)
                            <button
                                type="button"
                                wire:click="$set('tema_padrao', '{{ $tema->value }}')"
                                @class ([
                                    'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                    'border-primary text-primary bg-primary/5' => $tema_padrao === $tema->value,
                                    'border-default-300 text-body-color hover:bg-light/60' => $tema_padrao !== $tema->value,
                                ])
                            >
                                <i class="iconify {{ $tema->icon() }} size-4"></i>
                                {{ $tema->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Skin --}}
                <x-shared.select-search
                    name="skin"
                    label="Skin"
                    wire:model.live="skin"
                    :placeholder="null"
                    :options="collect($skins)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                    :value="$skin"
                />

                {{-- Cores do chrome --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <span class="form-label">Cor da barra superior</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($topbarColors as $cor)
                                <button
                                    type="button"
                                    wire:click="$set('topbar_color', '{{ $cor->value }}')"
                                    title="{{ $cor->label() }}"
                                    @class ([
                                        'size-8 rounded-full ring-1 ring-black/10 transition-transform',
                                        'ring-primary ring-2 scale-110' => $topbar_color === $cor->value,
                                    ])
                                    style="background: {{ $cor->swatch() }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="form-label">Cor do menu lateral</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($menuColors as $cor)
                                <button
                                    type="button"
                                    wire:click="$set('menu_color', '{{ $cor->value }}')"
                                    title="{{ $cor->label() }}"
                                    @class ([
                                        'size-8 rounded-full ring-1 ring-black/10 transition-transform',
                                        'ring-primary ring-2 scale-110' => $menu_color === $cor->value,
                                    ])
                                    style="background: {{ $cor->swatch() }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Largura e menu --}}
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <x-shared.select-search
                        name="layout_width"
                        label="Largura do layout"
                        wire:model.live="layout_width"
                        :placeholder="null"
                        :options="$layoutWidths"
                        :value="$layout_width"
                    />
                    <x-shared.select-search
                        name="sidenav_size_padrao"
                        label="Menu lateral (padrão)"
                        wire:model.live="sidenav_size_padrao"
                        :placeholder="null"
                        :options="$sidenavSizes"
                        :value="$sidenav_size_padrao"
                    />
                </div>

                <div class="border-default-200 grid gap-3 border-t pt-4">
                    <x-shared.toggle
                        name="sidenav_user"
                        label="Exibir card do usuário no menu lateral"
                        wire:model.live="sidenav_user"
                        :checked="$sidenav_user"
                    />
                    <x-shared.toggle
                        name="permitir_preferencia_usuario"
                        label="Permitir que o usuário troque tema/colapso na própria sessão"
                        wire:model.live="permitir_preferencia_usuario"
                        :checked="$permitir_preferencia_usuario"
                    />
                </div>
            </div>
        </x-shared.card>
    </div>

    {{-- ─── Painel de preview (sticky) ──────────────────────────────────── --}}
    <div class="lg:sticky lg:top-20 lg:self-start">
        <x-admin.appearance-preview-panel
            :logo-light="$previewUrl($logo ?? null, $logoAtual)"
            :logo-dark="$previewUrl($logo_dark ?? null, $logoDarkAtual)"
            :logo-sm="$previewUrl($logo_sm ?? null, $logoSmAtual)"
        />

        <div class="mt-4 flex items-center gap-2">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy" wire:click="salvar">
                Aplicar
            </x-shared.loading-button>
            <button
                type="button"
                wire:click="descartar"
                class="border-default-300 text-body-color hover:bg-light rounded-md border px-4 py-2 text-sm font-medium transition-colors"
            >
                Descartar
            </button>
        </div>
    </div>
</div>
