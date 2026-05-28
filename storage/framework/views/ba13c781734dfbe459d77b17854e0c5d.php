<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary',
    'appearance' => null,
    'style' => null,
    'size' => 'md',
    'pill' => false,
    'block' => false,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'type' => 'button',
    'href' => null,
    'disabled' => false,
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
    'variant' => 'primary',
    'appearance' => null,
    'style' => null,
    'size' => 'md',
    'pill' => false,
    'block' => false,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'type' => 'button',
    'href' => null,
    'disabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $allowedVariants = ['default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    $allowedAppearances = ['solid', 'outline', 'ghost'];
    $allowedSizes = ['sm', 'md', 'lg'];

    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'primary';
    $appearance = $appearance ?? $style ?? 'solid';
    $appearance = in_array($appearance, $allowedAppearances, true) ? $appearance : 'solid';
    $size = in_array($size, $allowedSizes, true) ? $size : 'md';

    $solidClasses = [
        'default' => 'border-default-300 bg-card text-default-700 hover:bg-light',
        'primary' => 'bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-hover',
        'success' => 'bg-success text-white hover:bg-success-hover',
        'danger' => 'bg-danger text-white hover:bg-danger-hover',
        'warning' => 'bg-warning text-white hover:bg-warning-hover',
        'info' => 'bg-info text-white hover:bg-info-hover',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark text-white hover:bg-dark-hover',
    ];

    $outlineClasses = [
        'default' => 'border-default-300 text-default-700 hover:bg-light',
        'primary' => 'border-primary text-primary hover:bg-primary hover:text-white',
        'secondary' => 'border-secondary text-secondary hover:bg-secondary hover:text-white',
        'success' => 'border-success text-success hover:bg-success hover:text-white',
        'danger' => 'border-danger text-danger hover:bg-danger hover:text-white',
        'warning' => 'border-warning text-warning hover:bg-warning hover:text-white',
        'info' => 'border-info text-info hover:bg-info hover:text-white',
        'light' => 'border-light text-dark hover:bg-light hover:text-dark',
        'dark' => 'border-dark text-dark hover:bg-dark hover:text-white',
    ];

    $ghostClasses = [
        'default' => 'text-default-700 hover:bg-light',
        'primary' => 'text-primary hover:bg-primary/15',
        'secondary' => 'text-secondary hover:bg-secondary/15',
        'success' => 'text-success hover:bg-success/15',
        'danger' => 'text-danger hover:bg-danger/15',
        'warning' => 'text-warning hover:bg-warning/15',
        'info' => 'text-info hover:bg-info/15',
        'light' => 'text-dark hover:bg-light',
        'dark' => 'text-dark hover:bg-dark/15',
    ];

    $variantClasses = match ($appearance) {
        'outline' => $outlineClasses[$variant],
        'ghost' => $ghostClasses[$variant],
        default => $solidClasses[$variant],
    };

    $sizeClasses = [
        'sm' => 'btn-sm',
        'md' => null,
        'lg' => 'btn-lg',
    ];

    $iconSizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];

    $label = trim(strip_tags((string) $slot));
    $hasLabel = $label !== '';

    $classes = [
        'btn',
        $variantClasses,
        $sizeClasses[$size],
        $pill ? 'rounded-full' : null,
        $block ? 'w-full' : null,
        $iconOnly ? 'btn-icon' : null,
        'inline-flex items-center justify-center transition-all',
        $iconOnly ? 'gap-0' : 'gap-x-2',
        $disabled ? 'pointer-events-none opacity-50' : null,
    ];

    $elementAttributes = $attributes->class($classes);

    if ($iconOnly && $hasLabel && ! $attributes->has('aria-label')) {
        $elementAttributes = $elementAttributes->merge(['aria-label' => $label]);
    }

    $leadingIcon = $icon ?: ($iconOnly ? $iconRight : null);
    $trailingIcon = $iconOnly ? null : $iconRight;
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($href): ?>
    <a
        <?php if(! $disabled): ?> href="<?php echo e($href); ?>" <?php endif; ?>
        <?php echo e($elementAttributes); ?>

        <?php if($disabled): ?> aria-disabled="true" tabindex="-1" <?php endif; ?>
    >
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($leadingIcon): ?>
            <i class="iconify <?php echo e($leadingIcon); ?> <?php echo e($iconSizeClasses[$size]); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasLabel): ?>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sr-only' => $iconOnly]); ?>"><?php echo e($slot); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trailingIcon): ?>
            <i class="iconify <?php echo e($trailingIcon); ?> <?php echo e($iconSizeClasses[$size]); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </a>
<?php else: ?>
    <button type="<?php echo e($type); ?>" <?php echo e($elementAttributes); ?> <?php if($disabled): echo 'disabled'; endif; ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($leadingIcon): ?>
            <i class="iconify <?php echo e($leadingIcon); ?> <?php echo e($iconSizeClasses[$size]); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasLabel): ?>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sr-only' => $iconOnly]); ?>"><?php echo e($slot); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trailingIcon): ?>
            <i class="iconify <?php echo e($trailingIcon); ?> <?php echo e($iconSizeClasses[$size]); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </button>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/button.blade.php ENDPATH**/ ?>