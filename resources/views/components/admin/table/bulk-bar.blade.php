@props ([
    'count' => 0,
    'label' => 'selecionado(s)',
])

@if ($count > 0)
    <div
        class="border-primary/25 bg-primary/8 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
    >
        <span class="text-body-color inline-flex items-center gap-2 text-sm font-medium">
            <span
                class="bg-primary inline-flex size-6 items-center justify-center rounded-full text-xs font-bold text-white"
            >
                {{ $count }}
            </span>
            {{ $label }}
        </span>

        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    </div>
@endif
