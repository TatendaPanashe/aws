<?php $__env->startSection('title', 'Create User'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    
    // Get SBU3 network for auto-selection
    $sbu3Network = null;
    if ($isZINARAUser && isset($networks)) {
        $sbu3Network = $networks->firstWhere('name', 'SBU3');
    }
?>

<div class="card">
    <div class="card-body">
        <h1 class="mb-4">Create User</h1>

        <?php if(session('error')): ?>
            <div class="alert alert-danger">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <?php if($isZINARAUser): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>ZINARA Mode:</strong> You can only create ZINARA Clerk accounts. Network and Site will be auto-filtered to Courier (SBU3) sites.
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('teams.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="name" id="name" value="<?php echo e(old('name')); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3 col-md-6">
                    <label for="surname" class="form-label">Surname</label>
                    <input type="text" class="form-control <?php $__errorArgs = ['surname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="surname" id="surname" value="<?php echo e(old('surname')); ?>">
                    <?php $__errorArgs = ['surname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3 col-md-12">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="email" id="email" value="<?php echo e(old('email')); ?>" required>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="role" id="role" required>
                        <option value="">Select Role</option>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($isZINARAUser): ?>
                                
                                <?php if($role->id == 7): ?>
                                    <option value="<?php echo e($role->id); ?>" <?php echo e(old('role') == $role->id ? 'selected' : ''); ?>>
                                        <?php echo e($role->role_name); ?>

                                    </option>
                                <?php endif; ?>
                            <?php elseif($isZINARASupervisor): ?>
                                
                                <?php if($role->id == 7): ?>
                                    <option value="<?php echo e($role->id); ?>" <?php echo e(old('role') == $role->id ? 'selected' : ''); ?>>
                                        <?php echo e($role->role_name); ?>

                                    </option>
                                <?php endif; ?>
                            <?php else: ?>
                                <option value="<?php echo e($role->id); ?>" <?php echo e(old('role') == $role->id ? 'selected' : ''); ?>>
                                    <?php echo e($role->role_name); ?>

                                </option>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3">
                    <label for="networkid" class="form-label">Network</label>
                    <?php if($isZINARAUser && $sbu3Network): ?>
                        
                        <input type="text" class="form-control" value="<?php echo e($sbu3Network->name); ?> (Courier Network)" disabled>
                        <input type="hidden" name="networkid" value="<?php echo e($sbu3Network->id); ?>">
                        <small class="text-muted">Network is automatically set to Courier (SBU3) for ZINARA users.</small>
                    <?php else: ?>
                        <select class="form-control <?php $__errorArgs = ['networkid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="networkSelect" onchange="getSites()" name="networkid" required>
                            <option value="">Select Network</option>
                            <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($network->id); ?>" <?php echo e(old('networkid') == $network->id ? 'selected' : ''); ?>>
                                    <?php echo e($network->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php endif; ?>
                    <?php $__errorArgs = ['networkid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3">
                    <label for="siteid" class="form-label">Site</label>
                    <select class="form-control <?php $__errorArgs = ['siteid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="sitelist" name="siteid" required>
                        <option value="">Select Site</option>
                        <?php if($isZINARAUser && $sbu3Network): ?>
                            
                            <?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($site->sbu == 'SBU3' || $site->network_id == $sbu3Network->id): ?>
                                    <option value="<?php echo e($site->id); ?>" <?php echo e(old('siteid') == $site->id ? 'selected' : ''); ?>>
                                        <?php echo e($site->site_name); ?> <?php if($site->sbu): ?> (<?php echo e($site->sbu); ?>) <?php endif; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($site->id); ?>" <?php echo e(old('siteid') == $site->id ? 'selected' : ''); ?>>
                                    <?php echo e($site->site_name); ?> <?php if($site->sbu): ?> (<?php echo e($site->sbu); ?>) <?php endif; ?>
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </select>
                    <?php $__errorArgs = ['siteid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3 col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password" id="password" placeholder="At least 8 characters, 1 uppercase letter and 1 number" required>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3 col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Confirm your password" required>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="<?php echo e(route('teams.index')); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    function getSites() {
        var networkId = document.getElementById('networkSelect').value;
        
        $("#sitelist").empty();
        $("#sitelist").append('<option value="">Loading sites...</option>');
        
        if (networkId) {
            $.ajax({
                method: 'GET',
                url: '/teams/getsites/' + networkId,
                success: function(data) {
                    $("#sitelist").empty();
                    $("#sitelist").append('<option value="">Select Site</option>');
                    
                    if (data.length > 0) {
                        $.each(data, function(i, item) {
                            $("#sitelist").append('<option value="' + item.id + '">' + item.site_name + '</option>');
                        });
                    } else {
                        $("#sitelist").append('<option value="">No sites available for this network</option>');
                    }
                },
                error: function() {
                    $("#sitelist").empty();
                    $("#sitelist").append('<option value="">Error loading sites</option>');
                }
            });
        } else {
            $("#sitelist").empty();
            $("#sitelist").append('<option value="">Select Network First</option>');
        }
    }
    
    // Preserve selected site if editing
    <?php if(old('siteid')): ?>
    $(document).ready(function() {
        var selectedSite = "<?php echo e(old('siteid')); ?>";
        if(selectedSite) {
            setTimeout(function() {
                $('#sitelist').val(selectedSite);
            }, 500);
        }
    });
    <?php endif; ?>
    
    // For ZINARA users, trigger site load on page load if network is pre-selected
    <?php if($isZINARAUser && $sbu3Network): ?>
    $(document).ready(function() {
        // Sites are already loaded in the select for ZINARA users
        console.log('ZINARA mode active - Sites filtered to SBU3 only');
    });
    <?php endif; ?>
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/teams/create.blade.php ENDPATH**/ ?>