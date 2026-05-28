<?php if (isset($component)) { $__componentOriginale2b8f49b1496102dda516685a0fa404b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale2b8f49b1496102dda516685a0fa404b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.auth-form-card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.auth-form-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Definir nova senha</h4>
    <p class="text-default-400 mb-9 text-center text-sm">Sua nova senha deve ter pelo menos 8 caracteres.</p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token" />
        <input type="hidden" wire:model="email" />

        <div class="mb-5">
            <?php if (isset($component)) { $__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.password-input','data' => ['name' => 'password','label' => 'Nova senha','wire:model' => 'password','placeholder' => '••••••••','required' => true,'autocomplete' => 'new-password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.password-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','label' => 'Nova senha','wire:model' => 'password','placeholder' => '••••••••','required' => true,'autocomplete' => 'new-password']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31)): ?>
<?php $attributes = $__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31; ?>
<?php unset($__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31)): ?>
<?php $component = $__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31; ?>
<?php unset($__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31); ?>
<?php endif; ?>
        </div>

        <div class="mb-5">
            <?php if (isset($component)) { $__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.password-input','data' => ['name' => 'password_confirmation','label' => 'Confirmar nova senha','wire:model' => 'password_confirmation','placeholder' => '••••••••','required' => true,'autocomplete' => 'new-password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.password-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password_confirmation','label' => 'Confirmar nova senha','wire:model' => 'password_confirmation','placeholder' => '••••••••','required' => true,'autocomplete' => 'new-password']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31)): ?>
<?php $attributes = $__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31; ?>
<?php unset($__attributesOriginalb3f29ba2237c9300a28c172b7f5d4f31); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31)): ?>
<?php $component = $__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31; ?>
<?php unset($__componentOriginalb3f29ba2237c9300a28c172b7f5d4f31); ?>
<?php endif; ?>
        </div>

        <div class="mb-6">
            <?php if (isset($component)) { $__componentOriginal854bb286facaee7a521ff2ecb55fc751 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal854bb286facaee7a521ff2ecb55fc751 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.password-strength-meter','data' => ['target' => 'password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.password-strength-meter'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['target' => 'password']); ?>
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
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
            <?php if (isset($component)) { $__componentOriginalddf9552503d68b4456843b7d3214825a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddf9552503d68b4456843b7d3214825a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.alert','data' => ['variant' => 'danger','class' => 'mb-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'danger','class' => 'mb-5']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($errors->first()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddf9552503d68b4456843b7d3214825a)): ?>
<?php $attributes = $__attributesOriginalddf9552503d68b4456843b7d3214825a; ?>
<?php unset($__attributesOriginalddf9552503d68b4456843b7d3214825a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddf9552503d68b4456843b7d3214825a)): ?>
<?php $component = $__componentOriginalddf9552503d68b4456843b7d3214825a; ?>
<?php unset($__componentOriginalddf9552503d68b4456843b7d3214825a); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal92bf944d71b159c48c2ae49ec6845420 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92bf944d71b159c48c2ae49ec6845420 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.loading-button','data' => ['type' => 'submit','class' => 'w-full py-3','wire:target' => 'resetPassword']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.loading-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'w-full py-3','wire:target' => 'resetPassword']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            Redefinir senha
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92bf944d71b159c48c2ae49ec6845420)): ?>
<?php $attributes = $__attributesOriginal92bf944d71b159c48c2ae49ec6845420; ?>
<?php unset($__attributesOriginal92bf944d71b159c48c2ae49ec6845420); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92bf944d71b159c48c2ae49ec6845420)): ?>
<?php $component = $__componentOriginal92bf944d71b159c48c2ae49ec6845420; ?>
<?php unset($__componentOriginal92bf944d71b159c48c2ae49ec6845420); ?>
<?php endif; ?>
    </form>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        <a class="text-primary font-semibold underline underline-offset-4" href="<?php echo e(route('admin.login')); ?>">
            ← Voltar para o login
        </a>
    </p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale2b8f49b1496102dda516685a0fa404b)): ?>
<?php $attributes = $__attributesOriginale2b8f49b1496102dda516685a0fa404b; ?>
<?php unset($__attributesOriginale2b8f49b1496102dda516685a0fa404b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale2b8f49b1496102dda516685a0fa404b)): ?>
<?php $component = $__componentOriginale2b8f49b1496102dda516685a0fa404b; ?>
<?php unset($__componentOriginale2b8f49b1496102dda516685a0fa404b); ?>
<?php endif; ?>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/livewire/admin/auth/reset-password.blade.php ENDPATH**/ ?>