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

    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Esqueceu sua senha?</h4>
    <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <?php if (isset($component)) { $__componentOriginalddf9552503d68b4456843b7d3214825a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddf9552503d68b4456843b7d3214825a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.alert','data' => ['variant' => 'success','class' => 'mb-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'success','class' => 'mb-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e(session('status')); ?> <?php echo $__env->renderComponent(); ?>
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

    <form wire:submit="sendLink">
        <div class="mb-6">
            <?php if (isset($component)) { $__componentOriginal17a87fdf4ec30f6e846eda7730b89d1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal17a87fdf4ec30f6e846eda7730b89d1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.input','data' => ['name' => 'email','label' => 'Endereço de e-mail','type' => 'email','wire:model' => 'email','icon' => 'tabler--mail','placeholder' => 'admin@exemplo.com.br','required' => true,'autofocus' => true,'autocomplete' => 'email']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','label' => 'Endereço de e-mail','type' => 'email','wire:model' => 'email','icon' => 'tabler--mail','placeholder' => 'admin@exemplo.com.br','required' => true,'autofocus' => true,'autocomplete' => 'email']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal17a87fdf4ec30f6e846eda7730b89d1e)): ?>
<?php $attributes = $__attributesOriginal17a87fdf4ec30f6e846eda7730b89d1e; ?>
<?php unset($__attributesOriginal17a87fdf4ec30f6e846eda7730b89d1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal17a87fdf4ec30f6e846eda7730b89d1e)): ?>
<?php $component = $__componentOriginal17a87fdf4ec30f6e846eda7730b89d1e; ?>
<?php unset($__componentOriginal17a87fdf4ec30f6e846eda7730b89d1e); ?>
<?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->has('email')): ?>
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
<?php echo e($errors->first('email')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.loading-button','data' => ['type' => 'submit','class' => 'w-full py-3','wire:target' => 'sendLink']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.loading-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'w-full py-3','wire:target' => 'sendLink']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            Enviar link de redefinição
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
        Lembrou a senha?
        <a class="text-primary font-semibold underline underline-offset-4" href="<?php echo e(route('admin.login')); ?>">
            Voltar para o login
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
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/livewire/admin/auth/forgot-password.blade.php ENDPATH**/ ?>