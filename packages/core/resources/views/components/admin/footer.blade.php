@php
    $version = config('app.version', '1.0.0');
    $environmentLabel = app()->environment('production') ? null : strtoupper(app()->environment());
@endphp

<footer class="footer">
    <div class="container-fluid">
        <div class="gap-base grid grid-cols-1 md:grid-cols-2">
            <div class="text-center md:text-start">
                <span class="text-body-color font-medium">© {{ now()->year }} {{ config('app.name') }}</span>
                <span class="text-default-400">· Todos os direitos reservados.</span>
            </div>

            <div class="text-default-400 hidden items-center justify-center gap-2 md:flex md:justify-end">
                @if ($environmentLabel)
                    <span class="badge bg-warning/15 text-warning">{{ $environmentLabel }}</span>
                @endif

                <span>v{{ $version }}</span>
                <span class="text-default-300">·</span>
                <span>{{ config('app.name') }}</span>
            </div>
        </div>
    </div>
</footer>
