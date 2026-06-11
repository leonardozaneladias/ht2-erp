{{-- Preset de skeleton para gráficos — usar como placeholder() de componentes Livewire #[Lazy]. --}}
<div {{ $attributes->class('border-default-200 rounded-lg border p-5') }} aria-hidden="true">
    <x-shared.skeleton class="mb-4 h-4 w-1/3" />
    <div class="flex h-48 items-end gap-2">
        @foreach ([60, 35, 80, 50, 70, 40, 90, 55] as $altura)
            <x-shared.skeleton class="grow" style="height: {{ $altura }}%" />
        @endforeach
    </div>
</div>
