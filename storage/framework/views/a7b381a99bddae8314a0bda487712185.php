<?php if (isset($component)) { $__componentOriginal7651faf8e4a1e278424aad70c82de3ba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7651faf8e4a1e278424aad70c82de3ba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layout','data' => ['title' => 'Dashboard']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Dashboard']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <?php if (isset($component)) { $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-card','data' => ['label' => 'Registros','value' => '0','icon' => 'tabler--database','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Registros','value' => '0','icon' => 'tabler--database','color' => 'primary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $attributes = $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $component = $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
        </div>
        <div class="col-xl-3 col-md-6">
            <?php if (isset($component)) { $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-card','data' => ['label' => 'Usuários ativos','value' => '0','icon' => 'tabler--users','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Usuários ativos','value' => '0','icon' => 'tabler--users','color' => 'success']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $attributes = $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $component = $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
        </div>
        <div class="col-xl-3 col-md-6">
            <?php if (isset($component)) { $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-card','data' => ['label' => 'Tarefas pendentes','value' => '0','icon' => 'tabler--clock','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tarefas pendentes','value' => '0','icon' => 'tabler--clock','color' => 'warning']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $attributes = $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $component = $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
        </div>
        <div class="col-xl-3 col-md-6">
            <?php if (isset($component)) { $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-card','data' => ['label' => 'Alertas','value' => '0','icon' => 'tabler--alert-triangle','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Alertas','value' => '0','icon' => 'tabler--alert-triangle','color' => 'danger']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $attributes = $__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__attributesOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3)): ?>
<?php $component = $__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3; ?>
<?php unset($__componentOriginal70a6fcfaf7f2326c7059ce9dfede57e3); ?>
<?php endif; ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <?php if (isset($component)) { $__componentOriginal91b17fe816eccd2dd419f56044b0f392 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91b17fe816eccd2dd419f56044b0f392 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.chart-card','data' => ['title' => 'Atividade mensal','subtitle' => 'Últimos 12 meses']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.chart-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Atividade mensal','subtitle' => 'Últimos 12 meses']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginale1120ab2f405006d49120e25c0fba890 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale1120ab2f405006d49120e25c0fba890 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.chart-line','data' => ['title' => 'Atividade','series' => [],'categories' => []]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.chart-line'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Atividade','series' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([]),'categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale1120ab2f405006d49120e25c0fba890)): ?>
<?php $attributes = $__attributesOriginale1120ab2f405006d49120e25c0fba890; ?>
<?php unset($__attributesOriginale1120ab2f405006d49120e25c0fba890); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale1120ab2f405006d49120e25c0fba890)): ?>
<?php $component = $__componentOriginale1120ab2f405006d49120e25c0fba890; ?>
<?php unset($__componentOriginale1120ab2f405006d49120e25c0fba890); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91b17fe816eccd2dd419f56044b0f392)): ?>
<?php $attributes = $__attributesOriginal91b17fe816eccd2dd419f56044b0f392; ?>
<?php unset($__attributesOriginal91b17fe816eccd2dd419f56044b0f392); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91b17fe816eccd2dd419f56044b0f392)): ?>
<?php $component = $__componentOriginal91b17fe816eccd2dd419f56044b0f392; ?>
<?php unset($__componentOriginal91b17fe816eccd2dd419f56044b0f392); ?>
<?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.exemplo-counter', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-308135186-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7651faf8e4a1e278424aad70c82de3ba)): ?>
<?php $attributes = $__attributesOriginal7651faf8e4a1e278424aad70c82de3ba; ?>
<?php unset($__attributesOriginal7651faf8e4a1e278424aad70c82de3ba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7651faf8e4a1e278424aad70c82de3ba)): ?>
<?php $component = $__componentOriginal7651faf8e4a1e278424aad70c82de3ba; ?>
<?php unset($__componentOriginal7651faf8e4a1e278424aad70c82de3ba); ?>
<?php endif; ?>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/admin/dashboard/index.blade.php ENDPATH**/ ?>