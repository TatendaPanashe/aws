<?php $__env->startSection('title'); ?>
Login
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-layout">
    <section class="auth-copy">
        <div class="hero-spotlight">Secure Access</div>
        <h1>Enter the operations workspace.</h1>
        <p>
            Sign in to manage daily collections, monitor face value stock, review branch activity, and keep reporting aligned across your network.
        </p>

        <div class="auth-list">
            <div class="auth-list-item">
                <i class="bi bi-shield-lock"></i>
                <div>
                    <strong>Controlled access</strong><br>
                    <small>Role-aware navigation for clerks, supervisors, managers, admins, and super users.</small>
                </div>
            </div>
            <div class="auth-list-item">
                <i class="bi bi-diagram-3"></i>
                <div>
                    <strong>Branch visibility</strong><br>
                    <small>Move from branch-level capture to network-level reporting without switching tools.</small>
                </div>
            </div>
            <div class="auth-list-item">
                <i class="bi bi-graph-up-arrow"></i>
                <div>
                    <strong>Operational insight</strong><br>
                    <small>Use live dashboards and structured reports instead of static spreadsheets.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <h2>Login to GRUMA</h2>
        <p class="auth-subtitle">Use your assigned email and password to open the workspace.</p>

        <form method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label for="email" class="form-label"><?php echo e(__('Email Address')); ?></label>
                <input id="email" type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="email" value="<?php echo e(old('email')); ?>" required autocomplete="email" autofocus>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="invalid-feedback d-block" role="alert">
                        <strong><?php echo e($message); ?></strong>
                    </span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label"><?php echo e(__('Password')); ?></label>
                <input id="password" type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password" required autocomplete="current-password">
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="invalid-feedback d-block" role="alert">
                        <strong><?php echo e($message); ?></strong>
                    </span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="remember"><?php echo e(__('Remember Me')); ?></label>
                </div>

                <?php if(Route::has('password.request')): ?>
                    <a class="text-decoration-none" href="<?php echo e(route('password.request')); ?>">
                        <?php echo e(__('Forgot Your Password?')); ?>

                    </a>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100"><?php echo e(__('Login')); ?></button>
        </form>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontpages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/app/resources/views/auth/login.blade.php ENDPATH**/ ?>