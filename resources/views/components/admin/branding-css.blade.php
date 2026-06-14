@php
    $branding = app(\App\Services\Admin\Settings\BrandingService::class);
    $brandingVars = $branding->cssVariables();
@endphp

<link rel="icon" href="{{ $branding->faviconUrl() }}" />

@if (filled($brandingVars))
    {{-- html[data-skin] iguala a especificidade dos skins (html[data-skin="x"] = 0,1,1)
         e, vindo após o @vite, vence por ordem de carga — garante que a cor da marca
         (instância/empresa) prevaleça quando um skin não-default está ativo. --}}
    <style>
        :root,
        html[data-skin] {
            {!! $brandingVars !!}
        }
    </style>
@endif
