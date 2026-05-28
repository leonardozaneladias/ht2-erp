
<div class="card relative flex min-h-screen flex-col justify-between rounded-none p-12.5">
    <div class="mb-7.5 flex flex-col items-center justify-center">
        <a href="<?php echo e(route('admin.login')); ?>">
            <img
                alt="<?php echo e(config('app.name')); ?>"
                class="flex h-8 dark:hidden"
                src="<?php echo e(asset(config('branding.logo_dark_path'))); ?>"
            />
            <img
                alt="<?php echo e(config('app.name')); ?>"
                class="hidden h-8 dark:flex"
                src="<?php echo e(asset(config('branding.logo_path'))); ?>"
            />
        </a>
    </div>

    <div><?php echo e($slot); ?></div>

    <p class="text-default-400 mt-7.5 text-center text-sm">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?></p>
</div>
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/auth-form-card.blade.php ENDPATH**/ ?>