<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'subtitle' => null,
    'chartId' => null,
    'height' => 350,
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
    'title',
    'subtitle' => null,
    'chartId' => null,
    'height' => 350,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $resolvedChartId = $chartId ?: 'chart-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $hasBodyContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
?>

<?php if (isset($component)) { $__componentOriginal827d8bf969270a383dec9a20090af039 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal827d8bf969270a383dec9a20090af039 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.card','data' => ['attributes' => $attributes,'title' => $title,'subtitle' => $subtitle]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($subtitle)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($headerActions)): ?>
         <?php $__env->slot('headerActions', null, []); ?> 
            <?php echo e($headerActions); ?>

         <?php $__env->endSlot(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="space-y-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($chart)): ?>
            <div class="border-default-200/80 bg-light/30 overflow-hidden rounded-xl border p-3" dir="ltr">
                <?php echo e($chart); ?>

            </div>
        <?php else: ?>
            <div
                id="<?php echo e($resolvedChartId); ?>"
                class="border-default-200/80 bg-light/30 overflow-hidden rounded-xl border"
                style="min-height: <?php echo e((int) $height); ?>px"
                data-af-chart-host
                wire:ignore
            ></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasBodyContent): ?>
            <div class="text-default-400 text-sm"><?php echo e($slot); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal827d8bf969270a383dec9a20090af039)): ?>
<?php $attributes = $__attributesOriginal827d8bf969270a383dec9a20090af039; ?>
<?php unset($__attributesOriginal827d8bf969270a383dec9a20090af039); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal827d8bf969270a383dec9a20090af039)): ?>
<?php $component = $__componentOriginal827d8bf969270a383dec9a20090af039; ?>
<?php unset($__componentOriginal827d8bf969270a383dec9a20090af039); ?>
<?php endif; ?>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/chart-card.blade.php ENDPATH**/ ?>