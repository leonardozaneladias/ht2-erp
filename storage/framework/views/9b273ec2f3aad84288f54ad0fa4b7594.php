
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title'        => null,
    'heroSubtitle' => 'Painel administrativo.',
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
    'title'        => null,
    'heroSubtitle' => 'Painel administrativo.',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $pageTitle = filled($title)
        ? sprintf('%s | %s', $title, config('app.name'))
        : config('app.name');
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo e($pageTitle); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
    <link rel="icon" href="<?php echo e(asset('images/favicon.ico')); ?>" />

    <?php if (isset($component)) { $__componentOriginal72f5b0931e5058de95bc399878ce1896 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72f5b0931e5058de95bc399878ce1896 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.partials.theme-bootstrap','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.partials.theme-bootstrap'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72f5b0931e5058de95bc399878ce1896)): ?>
<?php $attributes = $__attributesOriginal72f5b0931e5058de95bc399878ce1896; ?>
<?php unset($__attributesOriginal72f5b0931e5058de95bc399878ce1896); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72f5b0931e5058de95bc399878ce1896)): ?>
<?php $component = $__componentOriginal72f5b0931e5058de95bc399878ce1896; ?>
<?php unset($__componentOriginal72f5b0931e5058de95bc399878ce1896); ?>
<?php endif; ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/admin.css', 'resources/js/admin.js']); ?>
</head>
<body>
    <div class="min-h-screen">
        <div class="flex h-full w-full">
            
            <div class="hidden w-full md:block">
                <div
                    class="relative h-full overflow-hidden bg-[url('/images/auth.jpg')] bg-cover bg-center bg-no-repeat"
                >
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-9">
                        <img
                            alt="<?php echo e(config('app.name')); ?>"
                            class="mb-5 h-7"
                            src="<?php echo e(asset(config('branding.logo_path'))); ?>"
                        />
                        <p class="text-lg font-bold text-white"><?php echo e(config('app.name')); ?></p>
                        <p class="mt-1 text-sm text-white/60"><?php echo e($heroSubtitle); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="min-w-full md:max-w-118 md:min-w-106"><?php echo e($slot); ?></div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/auth-layout.blade.php ENDPATH**/ ?>