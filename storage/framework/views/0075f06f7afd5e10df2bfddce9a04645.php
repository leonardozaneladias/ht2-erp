<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'info',
    'solid' => false,
    'icon' => null,
    'dismissible' => false,
    'title' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'variant' => 'info',
    'solid' => false,
    'icon' => null,
    'dismissible' => false,
    'title' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $variants = [
        'primary' => [
            'soft' => 'bg-primary/15 text-primary',
            'solid' => 'bg-primary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'secondary' => [
            'soft' => 'bg-secondary/15 text-secondary',
            'solid' => 'bg-secondary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'success' => [
            'soft' => 'bg-success/15 text-success',
            'solid' => 'bg-success text-white',
            'icon' => 'tabler--circle-check',
        ],
        'danger' => [
            'soft' => 'bg-danger/15 text-danger',
            'solid' => 'bg-danger text-white',
            'icon' => 'tabler--alert-octagon',
        ],
        'warning' => [
            'soft' => 'bg-warning/15 text-warning',
            'solid' => 'bg-warning text-white',
            'icon' => 'tabler--alert-triangle',
        ],
        'info' => [
            'soft' => 'bg-info/15 text-info',
            'solid' => 'bg-info text-white',
            'icon' => 'tabler--info-circle',
        ],
        'light' => [
            'soft' => 'border border-default-300 bg-light/60 text-default-700',
            'solid' => 'bg-light text-dark',
            'icon' => 'tabler--info-circle',
        ],
        'dark' => [
            'soft' => 'bg-dark/15 text-dark',
            'solid' => 'bg-dark text-white',
            'icon' => 'tabler--info-circle',
        ],
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'info';
    $tone = $solid ? 'solid' : 'soft';
    $resolvedIcon = $icon === false ? null : ($icon ?: $variants[$variant]['icon']);
    $wrapperId = $attributes->get('id') ?: 'alert-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $wrapperAttributes = $attributes
        ->class([
            'hs-removing:translate-x-5 hs-removing:opacity-0',
            'rounded px-4 py-3 transition duration-300',
            'flex items-start gap-3',
            $variants[$variant][$tone],
            '[&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-2',
            '[&_a:hover]:opacity-80',
        ])
        ->merge([
            'id' => $wrapperId,
            'role' => 'alert',
        ]);
?>

<div <?php echo e($wrapperAttributes); ?>>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resolvedIcon): ?>
        <span class="mt-0.5 inline-flex shrink-0 items-center justify-center">
            <i class="iconify <?php echo e($resolvedIcon); ?> text-xl"></i>
        </span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="min-w-0 grow text-sm leading-5">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title): ?>
            <p class="mb-1 font-semibold"><?php echo e($title); ?></p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="space-y-2"><?php echo e($slot); ?></div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dismissible): ?>
        <button
            type="button"
            class="ms-auto inline-flex size-8 shrink-0 items-center justify-center rounded-full opacity-70 transition hover:opacity-100"
            aria-label="Fechar"
            data-hs-remove-element="#<?php echo e($wrapperId); ?>"
        >
            <i class="iconify tabler--x text-lg"></i>
        </button>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/alert.blade.php ENDPATH**/ ?>