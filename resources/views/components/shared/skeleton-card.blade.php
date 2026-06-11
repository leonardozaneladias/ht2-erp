{{-- Preset de skeleton para cards de conteúdo — usar como placeholder() de componentes Livewire #[Lazy]. --}}
<div {{ $attributes->class('border-default-200 rounded-lg border p-5') }} aria-hidden="true">
    <x-shared.skeleton class="mb-4 h-4 w-1/4" />
    <x-shared.skeleton :lines="3" />
</div>
