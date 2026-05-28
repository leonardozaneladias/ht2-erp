<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'id' => null,
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'required' => false,
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
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'required' => false,
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
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $inputClasses = [
        'form-input',
        'border-danger!' => $hasError,
    ];
?>

<div class="mb-4">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label class="form-label" for="<?php echo e($fieldId); ?>">
            <?php echo e($label); ?>


            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
        <div class="input-icon-group">
            <i class="iconify <?php echo e($icon); ?> input-icon"></i>
            <input
                id="<?php echo e($fieldId); ?>"
                name="<?php echo e($name); ?>"
                type="<?php echo e($type); ?>"
                <?php echo e($attributes->class($inputClasses)); ?>

                <?php if($required): echo 'required'; endif; ?>
                <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>
                aria-invalid="<?php echo e($hasError ? 'true' : 'false'); ?>"
            />
        </div>
    <?php else: ?>
        <input
            id="<?php echo e($fieldId); ?>"
            name="<?php echo e($name); ?>"
            type="<?php echo e($type); ?>"
            <?php echo e($attributes->class($inputClasses)); ?>

            <?php if($required): echo 'required'; endif; ?>
            <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>
            aria-invalid="<?php echo e($hasError ? 'true' : 'false'); ?>"
        />
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasError): ?>
        <small class="text-danger mt-1 block text-xs" id="<?php echo e($errorId); ?>"><?php echo e($viewErrors->first($errorKey)); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
        <small class="text-default-400 mt-1 block text-xs" id="<?php echo e($hintId); ?>"><?php echo e($hint); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/input.blade.php ENDPATH**/ ?>