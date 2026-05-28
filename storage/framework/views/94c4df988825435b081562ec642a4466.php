<?php
    $user = auth('admin')->user();

    $displayUser = [
        'nome' => $user?->nome ?? config('branding.user_default_name'),
        'perfil' => $user?->perfil?->nome ?? 'Preview local',
        'avatar' => $user?->avatar_url ?: asset(config('branding.avatar_default')),
    ];

    $notifications = [
        [
            'title' => 'Parcelas vencidas aguardando ação',
            'time' => 'há 10 minutos',
            'icon' => 'tabler--alert-triangle',
            'variant' => 'warning',
            'href' => route('admin.financeiro.parcelas.index'),
        ],
        [
            'title' => 'Novo contrato pronto para revisão',
            'time' => 'há 32 minutos',
            'icon' => 'tabler--file-text',
            'variant' => 'primary',
            'href' => route('admin.contratos.index'),
        ],
        [
            'title' => 'Cadastro manual de formando concluído',
            'time' => 'há 1 hora',
            'icon' => 'tabler--user-plus',
            'variant' => 'success',
            'href' => route('admin.formandos.index'),
        ],
    ];

    $notificationCount = count($notifications);

    $notificationToneClasses = [
        'primary' => 'bg-primary/15 text-primary',
        'success' => 'bg-success/15 text-success',
        'warning' => 'bg-warning/15 text-warning',
        'danger' => 'bg-danger/15 text-danger',
        'info' => 'bg-info/15 text-info',
    ];
?>

<header class="app-header">
    <div class="container-fluid flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="logo-topbar">
                <a class="logo-box" href="<?php echo e(route('admin.dashboard')); ?>">
                    <div class="logo-light">
                        <img class="logo-lg h-6" alt="<?php echo e(config('app.name')); ?>" src="<?php echo e(asset(config('branding.logo_path'))); ?>" />
                        <img class="logo-sm h-6" alt="<?php echo e(config('app.name')); ?>" src="<?php echo e(asset(config('branding.logo_sm_path'))); ?>" />
                    </div>

                    <div class="logo-dark">
                        <img class="logo-lg h-6" alt="<?php echo e(config('app.name')); ?>" src="<?php echo e(asset(config('branding.logo_dark_path'))); ?>" />
                        <img class="logo-sm h-6" alt="<?php echo e(config('app.name')); ?>" src="<?php echo e(asset(config('branding.logo_sm_path'))); ?>" />
                    </div>
                </a>
            </div>

            <button
                id="button-toggle-menu"
                type="button"
                class="sidenav-toggle-button btn bg-primary btn-icon rounded-full text-white"
                aria-label="Alternar menu lateral"
            >
                <i class="iconify tabler--menu-4 text-xl"></i>
            </button>

            <form action="<?php echo e(route('admin.formandos.index')); ?>" class="hidden xl:flex" id="search-box" role="search">
                <div class="input-icon-group">
                    <i class="iconify tabler--search input-icon text-lg text-(--topbar-item-color)/50!"></i>
                    <input
                        id="topbar-search"
                        type="search"
                        name="q"
                        class="form-input w-57.5 border-(--topbar-search-border)! bg-(--topbar-search-bg)! text-(--topbar-item-color)! placeholder:opacity-50"
                        placeholder="Buscar formandos, contratos ou parcelas..."
                    />
                </div>
            </form>
        </div>

        <div class="flex items-center gap-2.5">
            <div class="hidden sm:inline-flex">
                <div class="topbar-item">
                    <button
                        id="light-dark-mode"
                        type="button"
                        class="topbar-link btn btn-icon size-8 rounded-full transition-[scale,background]"
                        aria-label="Alternar tema claro e escuro"
                    >
                        <i
                            class="iconify tabler--moon topbar-link-icon absolute scale-100 rotate-0 transition-all duration-200 dark:scale-0 dark:-rotate-90"
                        ></i>
                        <i
                            class="iconify tabler--sun topbar-link-icon absolute scale-0 rotate-90 transition-all duration-200 dark:scale-100 dark:rotate-0"
                        ></i>
                    </button>
                </div>
            </div>

            <?php if (isset($component)) { $__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown','data' => ['class' => 'topbar-item','placement' => 'bottom-end','autoClose' => 'inside']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'topbar-item','placement' => 'bottom-end','auto-close' => 'inside']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                 <?php $__env->slot('button', null, []); ?> 
                    <button
                        type="button"
                        class="topbar-link hs-dropdown-toggle relative flex items-center"
                        aria-label="Abrir notificações"
                    >
                        <i class="iconify tabler--bell topbar-link-icon"></i>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($notificationCount > 0): ?>
                            <?php if (isset($component)) { $__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.badge','data' => ['variant' => 'warning','solid' => true,'class' => 'absolute -end-px -top-[13px] min-w-4 px-1 py-0 text-[10px] leading-none text-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'warning','solid' => true,'class' => 'absolute -end-px -top-[13px] min-w-4 px-1 py-0 text-[10px] leading-none text-white']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                <?php echo e($notificationCount); ?>

                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5)): ?>
<?php $attributes = $__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5; ?>
<?php unset($__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5)): ?>
<?php $component = $__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5; ?>
<?php unset($__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>
                 <?php $__env->endSlot(); ?>

                <div class="border-default-300 border-b px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <h6 class="text-body-color text-base font-semibold">Notificações</h6>
                        <?php if (isset($component)) { $__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.badge','data' => ['variant' => 'warning','solid' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'warning','solid' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($notificationCount); ?> alertas <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5)): ?>
<?php $attributes = $__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5; ?>
<?php unset($__attributesOriginal7923fde6eb4ea6b7f87a6d313822a0e5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5)): ?>
<?php $component = $__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5; ?>
<?php unset($__componentOriginal7923fde6eb4ea6b7f87a6d313822a0e5); ?>
<?php endif; ?>
                    </div>
                </div>

                <div class="max-h-80 overflow-y-auto" data-simplebar>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a
                            href="<?php echo e($notification['href']); ?>"
                            class="dropdown-item items-start gap-3 px-4.5 py-3 text-wrap"
                        >
                            <span class="shrink-0">
                                <span
                                    class="flex size-9 items-center justify-center rounded <?php echo e($notificationToneClasses[$notification['variant']] ?? $notificationToneClasses['info']); ?>"
                                >
                                    <i class="iconify <?php echo e($notification['icon']); ?> text-lg"></i>
                                </span>
                            </span>

                            <span class="text-default-400 grow">
                                <span class="text-body-color font-medium"><?php echo e($notification['title']); ?></span>
                                <br />
                                <span class="text-xs"><?php echo e($notification['time']); ?></span>
                            </span>
                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <a
                    href="<?php echo e(route('admin.conta.notificacoes')); ?>"
                    class="dropdown-item text-reset border-light justify-center border-t py-3 font-bold underline underline-offset-2"
                >
                    Ver central de notificações
                </a>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1)): ?>
<?php $attributes = $__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1; ?>
<?php unset($__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1)): ?>
<?php $component = $__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1; ?>
<?php unset($__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1); ?>
<?php endif; ?>

            <div class="hidden md:inline-flex">
                <div class="topbar-item">
                    <button
                        type="button"
                        class="topbar-link btn group size-8 rounded-full"
                        data-toggle="fullscreen"
                        aria-label="Alternar tela cheia"
                    >
                        <i class="iconify tabler--maximize topbar-link-icon group-[.fullscreen-active]:hidden"></i>
                        <i
                            class="iconify tabler--minimize topbar-link-icon hidden group-[.fullscreen-active]:inline-block"
                        ></i>
                    </button>
                </div>
            </div>

            <?php if (isset($component)) { $__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown','data' => ['class' => 'topbar-item before:bg-default-700/35 relative inline-flex before:h-4.5 before:w-px before:content-[\'\']','placement' => 'bottom-end']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'topbar-item before:bg-default-700/35 relative inline-flex before:h-4.5 before:w-px before:content-[\'\']','placement' => 'bottom-end']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                 <?php $__env->slot('button', null, []); ?> 
                    <button
                        type="button"
                        class="hs-dropdown-toggle topbar-link ms-2.5 flex cursor-pointer items-center px-3!"
                        aria-label="Abrir menu do usuário"
                    >
                        <img
                            alt="<?php echo e($displayUser['nome']); ?>"
                            class="size-8 rounded-full lg:me-3"
                            src="<?php echo e($displayUser['avatar']); ?>"
                        />

                        <div class="hidden items-center gap-1.5 lg:flex">
                            <div class="text-start">
                                <p class="text-body-color font-semibold"><?php echo e($displayUser['nome']); ?></p>
                                <p class="text-default-400 text-xs"><?php echo e($displayUser['perfil']); ?></p>
                            </div>

                            <i class="iconify tabler--chevron-down align-middle text-sm"></i>
                        </div>
                    </button>
                 <?php $__env->endSlot(); ?>

                <div class="px-3.5 py-2">
                    <h6 class="text-default-500 text-xs">Bem-vindo de volta</h6>
                    <p class="text-body-color mt-1 font-semibold"><?php echo e($displayUser['nome']); ?></p>
                </div>

                <?php if (isset($component)) { $__componentOriginale9dd0543f1e6be3801901856ac1412aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9dd0543f1e6be3801901856ac1412aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown-item','data' => ['icon' => 'tabler--user-circle','href' => route('admin.perfil.show')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'tabler--user-circle','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.perfil.show'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Meu perfil
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $attributes = $__attributesOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $component = $__componentOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__componentOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginale9dd0543f1e6be3801901856ac1412aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9dd0543f1e6be3801901856ac1412aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown-item','data' => ['icon' => 'tabler--settings-2','href' => route('admin.conta.edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'tabler--settings-2','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.conta.edit'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Configurações da conta
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $attributes = $__attributesOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $component = $__componentOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__componentOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginale9dd0543f1e6be3801901856ac1412aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9dd0543f1e6be3801901856ac1412aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown-item','data' => ['icon' => 'tabler--bell-ringing','href' => route('admin.conta.notificacoes')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'tabler--bell-ringing','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.conta.notificacoes'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    Notificações
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $attributes = $__attributesOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__attributesOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale9dd0543f1e6be3801901856ac1412aa)): ?>
<?php $component = $__componentOriginale9dd0543f1e6be3801901856ac1412aa; ?>
<?php unset($__componentOriginale9dd0543f1e6be3801901856ac1412aa); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal0dcfe40c970d625bd3a05e439a5532ff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0dcfe40c970d625bd3a05e439a5532ff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.dropdown-divider','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.dropdown-divider'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0dcfe40c970d625bd3a05e439a5532ff)): ?>
<?php $attributes = $__attributesOriginal0dcfe40c970d625bd3a05e439a5532ff; ?>
<?php unset($__attributesOriginal0dcfe40c970d625bd3a05e439a5532ff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0dcfe40c970d625bd3a05e439a5532ff)): ?>
<?php $component = $__componentOriginal0dcfe40c970d625bd3a05e439a5532ff; ?>
<?php unset($__componentOriginal0dcfe40c970d625bd3a05e439a5532ff); ?>
<?php endif; ?>

                <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                    <?php echo csrf_field(); ?>

                    <button type="submit" class="dropdown-item text-danger w-full text-start">
                        <i class="iconify tabler--logout align-middle text-base"></i>
                        <span class="align-middle">Sair</span>
                    </button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1)): ?>
<?php $attributes = $__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1; ?>
<?php unset($__attributesOriginal0b9ea84d3979b9ec01f06e18d6c464d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1)): ?>
<?php $component = $__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1; ?>
<?php unset($__componentOriginal0b9ea84d3979b9ec01f06e18d6c464d1); ?>
<?php endif; ?>
        </div>
    </div>
</header>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/topbar.blade.php ENDPATH**/ ?>