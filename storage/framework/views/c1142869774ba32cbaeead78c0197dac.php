<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'fieldId' => null,
    'labelId' => null,
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
    'fieldId' => null,
    'labelId' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php if($fieldId): ?> data-password-meter-standalone="<?php echo e($fieldId); ?>" <?php endif; ?>>
    <div class="bg-default-200 mt-2 h-1 overflow-hidden rounded-full">
        <div class="h-full w-0 transition-all duration-300" data-password-meter-bar></div>
    </div>
    <small
        class="text-default-400 mt-1 block text-xs"
        <?php if($labelId): ?> id="<?php echo e($labelId); ?>" <?php endif; ?>
        data-password-meter-label
    >
        Digite uma senha
    </small>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/shared/password-strength-meter.blade.php ENDPATH**/ ?>