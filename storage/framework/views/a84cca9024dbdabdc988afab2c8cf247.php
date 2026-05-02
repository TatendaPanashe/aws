<?php $__env->startSection('title', 'Create Site'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    
    // Get the SBU3 network ID for auto-selection
    $sbu3Network = null;
    if ($isZINARAUser && isset($networks)) {
        $sbu3Network = $networks->firstWhere('name', 'SBU3');
    }
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Create Site</h5>

        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <form class="row g-3" action="<?php echo e(route('postsite')); ?>" method="post">
            <?php echo csrf_field(); ?>
            
            
            <?php if(!$isZINARAUser): ?>
                <div class="col-md-12">
                    <label for="network_id" class="form-label">Network</label>
                    <select type="text" required name="network_id" class="form-control" id="network_id">
                        <option value="">Select Network</option>
                        <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($network->id); ?>" <?php echo e(old('network_id') == $network->id ? 'selected' : ''); ?>>
                                <?php echo e($network->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['network_id'];
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
            <?php else: ?>
                
                <?php if($sbu3Network): ?>
                    <input type="hidden" name="network_id" value="<?php echo e($sbu3Network->id); ?>">
                <?php endif; ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>ZINARA Mode:</strong> Sites will be automatically created under the Courier network (SBU3).
                </div>
            <?php endif; ?>
            
            <div class="col-md-12">
                <label for="site_name" class="form-label">Name of Site</label>
                <input type="text" name="site_name" class="form-control" id="site_name" placeholder="Site name" value="<?php echo e(old('site_name')); ?>" required>
                <?php $__errorArgs = ['site_name'];
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

            <div class="col-md-12">
                <label for="site_description" class="form-label">Site Description</label>
                <textarea type="text" name="site_description" class="form-control" id="site_description" placeholder="Site description" rows="3"><?php echo e(old('site_description')); ?></textarea>
                <?php $__errorArgs = ['site_description'];
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

            <div class="col-md-12">
                <label for="code_name" class="form-label">Code Name</label>
                <input type="text" name="code_name" class="form-control" id="code_name" placeholder="Code name" value="<?php echo e(old('code_name')); ?>">
                <?php $__errorArgs = ['code_name'];
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

            <div class="col-md-12">
                <label for="code" class="form-label">Code</label>
                <input type="text" name="code" class="form-control" id="code" placeholder="Site code" value="<?php echo e(old('code')); ?>">
                <?php $__errorArgs = ['code'];
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

            <div class="col-md-12">
                <label for="POS" class="form-label">POS Number</label>
                <input type="text" name="POS" class="form-control" id="POS" placeholder="POS Number" value="<?php echo e(old('POS')); ?>">
                <?php $__errorArgs = ['POS'];
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

            <div class="col-md-12">
                <label for="bank" class="form-label">Bank</label>
                <input type="text" name="bank" class="form-control" id="bank" placeholder="Bank" value="<?php echo e(old('bank')); ?>">
                <?php $__errorArgs = ['bank'];
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

            <div class="col-md-12">
                <label for="sbu" class="form-label">SBU</label>
                <?php if(!$isZINARAUser): ?>
                    <select type="text" required name="sbu" class="form-control" id="sbu">
                        <option value="">Select SBU</option>
                        <option value="SBU1" <?php echo e(old('sbu') == 'SBU1' ? 'selected' : ''); ?>>SBU1</option>
                        <option value="SBU2" <?php echo e(old('sbu') == 'SBU2' ? 'selected' : ''); ?>>SBU2</option>
                        <option value="SBU3" <?php echo e(old('sbu') == 'SBU3' ? 'selected' : ''); ?>>SBU3 (Courier)</option>
                    </select>
                <?php else: ?>
                    <input type="text" class="form-control" value="SBU3 (Courier) - Auto-selected for ZINARA" disabled>
                    <input type="hidden" name="sbu" value="SBU3">
                    <small class="text-muted">SBU is automatically set to SBU3 (Courier) for ZINARA users.</small>
                <?php endif; ?>
                <?php $__errorArgs = ['sbu'];
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
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
                <a href="<?php echo e(route('sites')); ?>" class="btn btn-info">View All Sites</a>
            </div>
        </form>
    </div>
</div>

<?php if($isZINARAUser): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('ZINARA mode active - Network and SBU are auto-configured');
    });
</script>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma/resources/views/site/create.blade.php ENDPATH**/ ?>