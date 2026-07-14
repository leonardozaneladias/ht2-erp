@php
    /**
     * Toolbar do topo do grid: "Ver lixeira / Ver ativos" + "Exportar PDF".
     *
     * View ÚNICA de todos os CRUDs, entregue ao PowerGrid por `includeViewOnTop` — roda
     * com $this = o componente PowerGrid.
     *
     * Substituiu 38 arquivos: 26 `_lixeira-toggle.blade.php` (24 linhas cada, diferindo só
     * na string da permissão e em incluir ou não o export) e 12 `_export-pdf.blade.php`
     * BYTE-IDÊNTICOS entre si. Todos eram gerados pelos stubs do `make:modulo` — a
     * duplicação nascia junto com cada módulo novo, e os stubs foram reescritos para
     * consumir esta view.
     *
     * As duas metades são independentes e detectadas pelas traits que o componente usa:
     * - lixeira: trait ComLixeira, que expõe `permissaoRestaurar()`;
     * - PDF: trait ExportaPdf, que expõe `exportarPdf()`.
     *
     * Um CRUD sem SoftDeletes usa só a metade do PDF (era exatamente para isso que existiam
     * os `_export-pdf.blade.php` avulsos).
     */
    $temLixeira = method_exists($this, 'permissaoRestaurar');
    $podeVerLixeira = $temLixeira && (auth('admin')->user()?->can($this->permissaoRestaurar()) ?? false);
    $temExportPdf = method_exists($this, 'exportarPdf');
@endphp

@if ($podeVerLixeira || $temExportPdf)
    {{-- Botões INLINE: o override do header do PowerGrid põe esta view na MESMA linha
         flex dos demais controles (export/colunas/filtros/busca) — sem wrapper com
         linha própria, a toolbar dos CRUDs cai para uma linha só. --}}
    @if ($podeVerLixeira)
        <x-shared.button
            :variant="$this->verLixeira ? 'primary' : 'default'"
            appearance="outline"
            size="sm"
            :icon="$this->verLixeira ? 'tabler--arrow-back-up' : 'tabler--trash'"
            wire:click="alternarLixeira"
        >
            {{ $this->verLixeira ? 'Ver ativos' : 'Ver lixeira' }}
        </x-shared.button>
    @endif
    @if ($temExportPdf)
        <x-shared.button
            variant="default"
            appearance="outline"
            size="sm"
            icon="tabler--file-type-pdf"
            wire:click="exportarPdf"
            wire:loading.attr="disabled"
            wire:target="exportarPdf"
        >
            Exportar PDF
        </x-shared.button>
    @endif
@endif
