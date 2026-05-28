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
    'type' => 'submit',
    'target' => null,
    'loadingText' => null,
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
    'type' => 'submit',
    'target' => null,
    'loadingText' => null,
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
    $targetAttributes = $target
        ? ['wire:target' => $target]
        : [];

    $label = trim(strip_tags((string) $slot));
    $hasLabel = $label !== '';
    $loadingLabel = filled($loadingText) ? $loadingText : $label;
?>

<?php if (isset($component)) { $__componentOriginaldaf9267e17f12fa8892736b0b564d945 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldaf9267e17f12fa8892736b0b564d945 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.button','data' => ['variant' => $variant,'appearance' => $appearance,'style' => $style,'size' => $size,'pill' => $pill,'block' => $block,'iconOnly' => $iconOnly,'type' => $type,'disabled' => $disabled,'attributes' => $attributes->merge($targetAttributes),'wire:loading.attr' => 'disabled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variant),'appearance' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appearance),'style' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($style),'size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'pill' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pill),'block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'icon-only' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconOnly),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type),'disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($disabled),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->merge($targetAttributes)),'wire:loading.attr' => 'disabled']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <span
        class="inline-flex items-center gap-x-2"
        <?php if($target): ?> wire:loading.remove wire:target="<?php echo e($target); ?>" <?php else: ?> wire:loading.remove <?php endif; ?>
    >
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
            <i class="iconify <?php echo e($icon); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasLabel): ?>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sr-only' => $iconOnly]); ?>"><?php echo e($slot); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($iconRight && ! $iconOnly): ?>
            <i class="iconify <?php echo e($iconRight); ?> shrink-0"></i>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </span>

    <span
        class="inline-flex items-center gap-x-2"
        <?php if($target): ?> wire:loading wire:target="<?php echo e($target); ?>" <?php else: ?> wire:loading <?php endif; ?>
    >
        <i class="iconify tabler--loader-2 shrink-0 animate-spin"></i>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($loadingLabel !== ''): ?>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sr-only' => $iconOnly]); ?>"><?php echo e($loadingLabel); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </span>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldaf9267e17f12fa8892736b0b564d945)): ?>
<?php $attributes = $__attributesOriginaldaf9267e17f12fa8892736b0b564d945; ?>
<?php unset($__attributesOriginaldaf9267e17f12fa8892736b0b564d945); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldaf9267e17f12fa8892736b0b564d945)): ?>
<?php $component = $__componentOriginaldaf9267e17f12fa8892736b0b564d945; ?>
<?php unset($__componentOriginaldaf9267e17f12fa8892736b0b564d945); ?>
<?php endif; ?>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/loading-button.blade.php ENDPATH**/ ?>