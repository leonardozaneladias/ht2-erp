<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'id' => null,
    'label' => 'Senha',
    'hint' => null,
    'required' => false,
    'withMeter' => false,
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
    'name',
    'id' => null,
    'label' => 'Senha',
    'hint' => null,
    'required' => false,
    'withMeter' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $meterLabelId = $withMeter ? "{$fieldId}-meter-label" : null;
    $describedBy = collect([$errorId, $hintId, $meterLabelId])->filter()->implode(' ') ?: null;
?>

<div class="mb-4" data-password-field>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label class="form-label" for="<?php echo e($fieldId); ?>">
            <?php echo e($label); ?>


            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="relative">
        <input
            id="<?php echo e($fieldId); ?>"
            name="<?php echo e($name); ?>"
            type="password"
            data-password-input
            <?php echo e($attributes->class([
                'form-input pe-10',
                'border-danger!' => $hasError,
            ])); ?>

            <?php if($required): echo 'required'; endif; ?>
            <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>
            aria-invalid="<?php echo e($hasError ? 'true' : 'false'); ?>"
        />

        <button
            type="button"
            class="text-default-400 hover:text-body-color absolute end-3 top-1/2 -translate-y-1/2 transition"
            data-password-toggle
            aria-controls="<?php echo e($fieldId); ?>"
            aria-label="Mostrar senha"
            aria-pressed="false"
        >
            <i class="iconify tabler--eye text-lg" data-password-toggle-icon></i>
        </button>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($withMeter): ?>
        <?php if (isset($component)) { $__componentOriginal854bb286facaee7a521ff2ecb55fc751 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal854bb286facaee7a521ff2ecb55fc751 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.password-strength-meter','data' => ['fieldId' => $fieldId,'labelId' => $meterLabelId]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.password-strength-meter'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fieldId),'label-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meterLabelId)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal854bb286facaee7a521ff2ecb55fc751)): ?>
<?php $attributes = $__attributesOriginal854bb286facaee7a521ff2ecb55fc751; ?>
<?php unset($__attributesOriginal854bb286facaee7a521ff2ecb55fc751); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal854bb286facaee7a521ff2ecb55fc751)): ?>
<?php $component = $__componentOriginal854bb286facaee7a521ff2ecb55fc751; ?>
<?php unset($__componentOriginal854bb286facaee7a521ff2ecb55fc751); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasError): ?>
        <small class="text-danger mt-1 block text-xs" id="<?php echo e($errorId); ?>"><?php echo e($viewErrors->first($errorKey)); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
        <small class="text-default-400 mt-1 block text-xs" id="<?php echo e($hintId); ?>"><?php echo e($hint); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/password-input.blade.php ENDPATH**/ ?>