<?php if (isset($component)) { $__componentOriginal827d8bf969270a383dec9a20090af039 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal827d8bf969270a383dec9a20090af039 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.card','data' => ['title' => 'Componente Livewire — Exemplo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Componente Livewire — Exemplo']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <p class="text-muted mb-4">Este é um componente Livewire simples demonstrando reatividade sem JavaScript. Use como ponto de partida para componentes do admin.</p>

    <div class="d-flex align-items-center gap-3">
        <button wire:click="decrement" class="btn btn-outline-secondary btn-sm">
            <iconify-icon icon="tabler--minus" class="me-1"></iconify-icon>
            Diminuir
        </button>

        <span class="fs-3 fw-bold px-3"><?php echo e($count); ?></span>

        <button wire:click="increment" class="btn btn-primary btn-sm">
            <iconify-icon icon="tabler--plus" class="me-1"></iconify-icon>
            Aumentar
        </button>

        <button wire:click="resetar" class="btn btn-outline-danger btn-sm ms-2">Resetar</button>
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
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/livewire/admin/exemplo-counter.blade.php ENDPATH**/ ?>