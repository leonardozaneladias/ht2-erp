<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'trendLabel' => 'vs. período anterior',
    'href' => null,
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
    'label',
    'value',
    'icon',
    'color' => 'primary',
    'trend' => null,
    'trendLabel' => 'vs. período anterior',
    'href' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $tag = filled($href) ? 'a' : 'div';
    $trendValue = is_numeric($trend) ? (float) $trend : null;
    $trendPositive = $trendValue !== null ? $trendValue >= 0 : null;

    $colorMap = [
        'primary' => ['soft' => 'bg-primary/12 text-primary', 'accent' => 'text-primary'],
        'success' => ['soft' => 'bg-success/12 text-success', 'accent' => 'text-success'],
        'warning' => ['soft' => 'bg-warning/12 text-warning', 'accent' => 'text-warning'],
        'danger' => ['soft' => 'bg-danger/12 text-danger', 'accent' => 'text-danger'],
        'info' => ['soft' => 'bg-info/12 text-info', 'accent' => 'text-info'],
        'default' => ['soft' => 'bg-light text-default-500', 'accent' => 'text-default-500'],
    ];

    $palette = $colorMap[$color] ?? $colorMap['primary'];
    $trendClasses = $trendPositive === null
        ? 'text-default-400'
        : ($trendPositive ? 'text-success' : 'text-danger');
    $trendIcon = $trendPositive === null
        ? 'tabler--minus'
        : ($trendPositive ? 'tabler--trending-up' : 'tabler--trending-down');
    $trendPrefix = $trendPositive ? '+' : '';
    $hasExtraContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
?>

<<?php echo e($tag); ?>

    <?php if($href): ?> href="<?php echo e($href); ?>" <?php endif; ?>
    <?php echo e($attributes->class([
        'card block transition',
        'hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30' => filled($href),
    ])); ?>

>
    <div class="card-body">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-default-400 text-2xs font-semibold tracking-[0.22em] uppercase"><?php echo e($label); ?></p>
                <p class="text-body-color mt-2 truncate text-2xl font-semibold"><?php echo e($value); ?></p>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trendValue !== null): ?>
                    <div class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium <?php echo e($trendClasses); ?>">
                        <i class="iconify <?php echo e($trendIcon); ?> text-sm"></i>
                        <span><?php echo e($trendPrefix); ?><?php echo e(number_format($trendValue, 1, ',', '.')); ?>%</span>
                        <span class="text-default-400"><?php echo e($trendLabel); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl <?php echo e($palette['soft']); ?>">
                <i class="iconify <?php echo e($icon); ?> text-2xl"></i>
            </span>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasExtraContent): ?>
            <div class="border-default-200/80 text-default-400 mt-4 border-t pt-4 text-sm"><?php echo e($slot); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</<?php echo e($tag); ?>>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/kpi-card.blade.php ENDPATH**/ ?>