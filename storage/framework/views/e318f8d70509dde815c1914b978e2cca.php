<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'id' => null,
    'label' => null,
    'value' => '1',
    'hint' => null,
    'checked' => false,
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
    'value' => '1',
    'hint' => null,
    'checked' => false,
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
    $isChecked = old($errorKey) !== null ? (bool) old($errorKey) : ($checked || $attributes->has('checked'));
?>

<div class="mb-4">
    <label class="inline-flex cursor-pointer items-start gap-3">
        <input
            id="<?php echo e($fieldId); ?>"
            name="<?php echo e($name); ?>"
            type="checkbox"
            value="<?php echo e($value); ?>"
            <?php echo e($attributes->class([
                'form-checkbox mt-0.5 rounded text-primary focus:ring-primary',
                'border-danger!' => $hasError,
            ])); ?>

            <?php if($isChecked): echo 'checked'; endif; ?>
            <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>
            aria-invalid="<?php echo e($hasError ? 'true' : 'false'); ?>"
        />

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
            <span class="min-w-0">
                <span class="text-body-color block text-sm font-medium"><?php echo e($label); ?></span>
            </span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </label>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasError): ?>
        <small class="text-danger mt-1 block text-xs" id="<?php echo e($errorId); ?>"><?php echo e($viewErrors->first($errorKey)); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
        <small class="text-default-400 mt-1 block text-xs" id="<?php echo e($hintId); ?>"><?php echo e($hint); ?></small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/checkbox.blade.php ENDPATH**/ ?>