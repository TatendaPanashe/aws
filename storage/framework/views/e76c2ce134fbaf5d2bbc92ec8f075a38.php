<?php $__env->startSection('title'); ?>
Welcome
<?php $__env->stopSection(); ?>



<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title">All Networks</h5>
                <a href="<?php echo e(route('networks.create')); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Network
                </a>
                </div>

                <!-- Table with stripped rows -->
                <table class="table table-striped">
                <thead>
                    <tr>
                    <th scope="col">Name</th>
                    <!--<th scope="col">City</th>
                    <th scope="col">Province</th>-->
                    <th scope="col">Description</th>
                    <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                       
                    <td><?php echo e($network->name); ?></td>
                    <!--<td><?php echo e($network->city); ?></td>
                    <td><?php echo e($network->province); ?></td>-->
                    <td><?php echo e($network->description); ?></td>
                    
                    <td><?php echo e($network->user->name); ?></td>
                    <td>
                        <a href="<?php echo e(route('networks.show', $network)); ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?php echo e(route('networks.edit', $network)); ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i>
                        </a>
                        <form action="<?php echo e(route('destroynetwork')); ?>" method="POST" class="d-inline">
                        <?php echo csrf_field(); ?>
                           
                        <input type="hidden" name="id" value="<?php echo e($network->id); ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                            <i class="bi bi-trash"></i>
                        </button>
                        </form>
                    </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                </table>
                <!-- End Table with stripped rows -->
            </div>
            </div>
        </div>
    </div>
</section>

        <?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/networks/index.blade.php ENDPATH**/ ?>