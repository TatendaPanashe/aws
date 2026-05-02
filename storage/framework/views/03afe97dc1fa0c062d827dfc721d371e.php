<?php $__env->startSection('title', 'Users Management'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
?>

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="mb-1">Users Management</h1>
                    <?php if($isZINARASupervisor): ?>
                        <div class="muted">Managing ZINARA Clerks under your supervision</div>
                    <?php elseif($isZINARAClerk): ?>
                        <div class="muted">Your account information</div>
                    <?php else: ?>
                        <div class="muted">Manage system users and their roles</div>
                    <?php endif; ?>
                </div>
                <?php if($isZINARASupervisor): ?>
                    <a href="<?php echo e(route('teams.create')); ?>" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Create ZINARA Clerk
                    </a>
                <?php elseif(!$isZINARAClerk): ?>
                    <a href="<?php echo e(route('teams.create')); ?>" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Add User
                    </a>
                <?php endif; ?>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if($users->isEmpty()): ?>
                <div class="empty-state text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <p class="mt-3">No users found.</p>
                    <?php if($isZINARASupervisor): ?>
                        <p class="text-muted">Click the "Create ZINARA Clerk" button to add clerks under your supervision.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table datatable table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>SBU</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $userItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($index + 1); ?></td>
                                    <td>
                                        <strong><?php echo e($userItem->name); ?> <?php echo e($userItem->surname ?? ''); ?></strong>
                                        <?php if($userItem->created_by == Auth::id() && $isZINARASupervisor): ?>
                                            <br>
                                            <small class="text-success">Created by you</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($userItem->email); ?></td>
                                    <td>
                                        <?php if($userItem->role_id == 6): ?>
                                            <span class="badge bg-primary">ZINARA Supervisor</span>
                                        <?php elseif($userItem->role_id == 7): ?>
                                            <span class="badge bg-info">ZINARA Clerk</span>
                                        <?php elseif($userItem->role_id == 2): ?>
                                            <span class="badge bg-secondary">Clerk</span>
                                        <?php elseif($userItem->role_id == 3): ?>
                                            <span class="badge bg-warning">Supervisor</span>
                                        <?php elseif($userItem->role_id == 5): ?>
                                            <span class="badge bg-danger">Super User</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo e($userItem->role->role_name ?? 'Unknown'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($userItem->site): ?>
                                            <?php echo e($userItem->site->site_name); ?>

                                            <?php if($userItem->site->sbu == 'SBU3'): ?>
                                                <br>
                                                <small class="text-info">(Courier)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($userItem->network->name ?? 'Not assigned'); ?></td>
                                    <td>
                                        <?php if($userItem->site && $userItem->site->sbu == 'SBU3'): ?>
                                            <span class="badge bg-info">SBU3 (Courier)</span>
                                        <?php elseif($userItem->site && $userItem->site->sbu): ?>
                                            <span class="badge bg-secondary"><?php echo e($userItem->site->sbu); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($userItem->is_active ?? true): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Blocked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('teams.edit', $userItem->id)); ?>" class="btn btn-warning btn-sm" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?php echo e(route('teams.resetpwd', $userItem->id)); ?>" class="btn btn-info btn-sm" title="Reset Password" onclick="return confirm('Reset password for <?php echo e($userItem->name); ?>?')">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <form action="<?php echo e(route('block', $userItem->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to <?php echo e($userItem->is_active ?? true ? 'block' : 'unblock'); ?> this user?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-danger btn-sm" title="<?php echo e(($userItem->is_active ?? true) ? 'Block User' : 'Unblock User'); ?>">
                                                    <i class="bi bi-<?php echo e(($userItem->is_active ?? true) ? 'person-x' : 'person-check'); ?>"></i>
                                                </button>
                                            </form>
                                            <?php if($isZINARASupervisor && $userItem->role_id == 7): ?>
                                                <a href="<?php echo e(route('facevalues.reports.trace')); ?>?clerk_id=<?php echo e($userItem->id); ?>" class="btn btn-primary btn-sm" title="View Face Value History">
                                                    <i class="bi bi-upc-scan"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($isZINARASupervisor && $users->count() > 0): ?>
                    <div class="mt-3 text-muted">
                        <i class="bi bi-info-circle"></i>
                        <small>ZINARA Clerks: <?php echo e($users->where('role_id', 7)->count()); ?> active | 
                        Total clerks under supervision: <?php echo e($users->count()); ?></small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
        cursor: pointer;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .empty-state {
        padding: 60px 20px;
    }
    .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    .muted {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma/resources/views/teams/index.blade.php ENDPATH**/ ?>