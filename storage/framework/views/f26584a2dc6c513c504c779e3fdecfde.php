<?php $__env->startSection('title', 'All Sites'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
?>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="card-title mb-1">All Sites</h5>
                            <?php if($isZINARAUser): ?>
                                <div class="muted">Showing Courier/ZINARA sites (SBU3) only</div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e(route('getsite')); ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Site
                        </a>
                    </div>

                    <?php if(session('success')): ?>
                        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                    <?php endif; ?>

                    <?php if(session('error')): ?>
                        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
                    <?php endif; ?>

                    <?php if($sites->isEmpty()): ?>
                        <div class="empty-state text-center py-5">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <p class="mt-3">No sites found.</p>
                            <?php if($isZINARAUser): ?>
                                <p class="text-muted">Create a site under SBU3 (Courier) to get started.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table datatable table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">Site Name</th>
                                        <th scope="col">Network</th>
                                        <th scope="col">Code</th>
                                        <th scope="col">Site Description</th>
                                        <th scope="col">Supervised By</th>
                                        <th scope="col">POS ID</th>
                                        <th scope="col">Bank</th>
                                        <th scope="col">SBU</th>
                                        <?php if($isZINARASupervisor): ?>
                                            <th scope="col">ZINARA Clerks</th>
                                        <?php endif; ?>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($site->site_name); ?></td>
                                        <td><?php echo e($site->network->name ?? 'N/A'); ?></td>
                                        <td><?php echo e($site->code ?? 'N/A'); ?></td>
                                        <td><?php echo e(Str::limit($site->site_description, 50) ?? 'N/A'); ?></td>
                                        <td><?php echo e($site->user->name ?? 'Unassigned'); ?></td>
                                        <td><?php echo e($site->POS ?? 'N/A'); ?></td>
                                        <td><?php echo e($site->bank ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if($site->sbu == 'SBU3'): ?>
                                                <span class="badge bg-info">SBU3 (Courier)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo e($site->sbu ?? 'N/A'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if($isZINARASupervisor): ?>
                                            <td>
                                                <?php
                                                    // Get ZINARA clerks assigned to this site
                                                    $zinaraClerks = App\Models\User::where('siteid', $site->id)
                                                        ->where('role_id', 7)
                                                        ->where('created_by', Auth::id())
                                                        ->get();
                                                ?>
                                                
                                                <?php if($zinaraClerks->count() > 0): ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-people"></i> <?php echo e($zinaraClerks->count()); ?> Clerk(s)
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php $__currentLoopData = $zinaraClerks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clerk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#">
                                                                        <i class="bi bi-person"></i> 
                                                                        <?php echo e($clerk->name); ?> <?php echo e($clerk->surname); ?>

                                                                        <br>
                                                                        <small class="text-muted"><?php echo e($clerk->email); ?></small>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="bi bi-person-x"></i> No clerks assigned
                                                    </span>
                                                    <a href="<?php echo e(route('teams.create')); ?>?site_id=<?php echo e($site->id); ?>" class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-plus"></i> Add Clerk
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo e(route('showsite', $site)); ?>" class="btn btn-primary btn-sm" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo e(route('editsite', $site)); ?>" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="<?php echo e(route('destroysite')); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="id" value="<?php echo e($site->id); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this site?')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if($isZINARASupervisor): ?>
<script>
    // Optional: Add any JavaScript for additional functionality
    document.addEventListener('DOMContentLoaded', function() {
        // You can add tooltips or other enhancements here
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma/resources/views/site/index.blade.php ENDPATH**/ ?>