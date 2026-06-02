@props ([
    'title',
    'subtitle' => null,
    'breadcrumbs' => null,
])

@php
    $title = trim((string) $title);

    $resolvedBreadcrumbs = collect(is_array($breadcrumbs) ? $breadcrumbs : [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => $title, 'current' => true],
    ])
        ->filter()
        ->values()
        ->all();

    $hasActions = isset($actions) && trim(strip_tags((string) $actions)) !== '';
@endphp

@if ($title !== '')
    <div class="page-title-head mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <h4 class="page-main-title">{{ $title }}</h4>

            @if (filled($subtitle))
                <p class="text-default-500 mt-1 text-sm">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex flex-col gap-3 xl:items-end">
            @if ($hasActions)
                <div class="flex flex-wrap items-center gap-2 xl:justify-end">{{ $actions }}</div>
            @endif

            <x-shared.breadcrumb :items="$resolvedBreadcrumbs" class="hidden md:block" />
        </div>
    </div>
@endif
