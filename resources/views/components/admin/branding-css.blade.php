@php
    $branding = app(\App\Services\Admin\Settings\BrandingService::class);
    $brandingVars = $branding->cssVariables();
@endphp

<link rel="icon" href="{{ $branding->faviconUrl() }}" />

@if (filled($brandingVars))
    <style>
        :root {
            {!! $brandingVars !!}
        }
    </style>
@endif
