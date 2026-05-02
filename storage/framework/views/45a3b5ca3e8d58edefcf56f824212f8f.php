<?php $__env->startSection('title', 'Receive Face Values'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Receive Face Values</h1>
                <h3>Balance: <?php echo e(number_format($balance, 0)); ?></h3>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#basicModal">
                Add FaceValues Stock
            </button>
           
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Range</th>
                        <th>Received</th>
                        <th>Allocated</th>
                        <th>Balance</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $supervisorfacevalues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisorfacevalue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($supervisorfacevalue->id); ?></td>
                        <td><?php echo e($supervisorfacevalue->starting); ?> - <?php echo e($supervisorfacevalue->ending); ?></td>
                        <td><?php echo e(number_format($supervisorfacevalue->received, 0)); ?></td>
                        <td><?php echo e(number_format($supervisorfacevalue->allocated, 0)); ?></td>
                        <td><?php echo e(number_format($supervisorfacevalue->balance, 0)); ?></td>
                        <td><?php echo e($supervisorfacevalue->created_at ? $supervisorfacevalue->created_at->format('Y-m-d H:i:s') : 'Not Available'); ?></td>
                        <td>
                            <?php if($supervisorfacevalue->balance > 0): ?>
                                <a href="<?php echo e(route('allocate', $supervisorfacevalue->id)); ?>" class="btn btn-primary btn-sm">Allocate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2">Totals</th>
                        <th><?php echo e(number_format($totalReceived, 0)); ?></th>
                        <th><?php echo e(number_format($totalAllocated, 0)); ?></th>
                        <th colspan="3"><?php echo e(number_format($balance, 0)); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Add Face Value Stock Modal -->
<div class="modal fade" id="basicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Face Value Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo e(route('supervisorfacevalues.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label>Range Starting</label>
                        <input id="starting" onkeyup="calculateEnding()" type="text" class="form-control" name="starting" placeholder="e.g., A100001" required>
                        <small class="text-muted">Format: Letters + Numbers + Check Digit (last character)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label>Number of Face Values</label>
                        <input id="amount" onkeyup="calculateEnding()" type="number" step="1" class="form-control" name="amount" placeholder="Enter quantity" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Range Ending (Auto)</label>
                        <input id="ending" readonly type="text" class="form-control bg-light" name="ending" placeholder="Will be auto-calculated">
                        <small class="text-muted">Calculated by ignoring the check digit</small>
                    </div>

                    <div id="error" class="text-danger mb-3"></div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">Add to Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calculateEnding() {
    const start = document.getElementById('starting').value;
    const amount = parseInt(document.getElementById('amount').value);
    const errorDiv = document.getElementById('error');
    const submitBtn = document.getElementById('submitBtn');
    
    // Reset
    document.getElementById('ending').value = '';
    errorDiv.innerHTML = '';
    submitBtn.disabled = false;
    
    if (!start || !amount || amount <= 0) {
        return;
    }
    
    if (start.length < 2) {
        errorDiv.innerHTML = 'Starting range must have at least 2 characters (including check digit)';
        submitBtn.disabled = true;
        return;
    }
    
    // Remove the last character (check digit)
    const startWithoutCheck = start.slice(0, -1);
    const checkDigit = start.slice(-1);
    
    // Extract prefix and number from the string without check digit
    const prefix = startWithoutCheck.match(/^[A-Za-z]+/)?.[0] || '';
    const numberPart = startWithoutCheck.match(/\d+/)?.[0];
    
    if (!numberPart) {
        errorDiv.innerHTML = 'Invalid format. Example: A100001 (where 1 is check digit)';
        submitBtn.disabled = true;
        return;
    }
    
    // Calculate new number
    const currentNumber = parseInt(numberPart);
    const newNumber = currentNumber + amount - 1;
    
    // Preserve the same number of digits (pad with leading zeros if needed)
    const numberLength = numberPart.length;
    const paddedNewNumber = String(newNumber).padStart(numberLength, '0');
    
    // Build the ending serial (without check digit first, then add check digit)
    const endingWithoutCheck = prefix + paddedNewNumber;
    const ending = endingWithoutCheck + checkDigit;
    
    document.getElementById('ending').value = ending;
    errorDiv.innerHTML = '';
    submitBtn.disabled = false;
}
</script>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
    input[readonly] {
        background-color: #e9ecef;
    }
    .text-muted {
        font-size: 0.8rem;
    }
</style>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/supervisorfacevalues/index.blade.php ENDPATH**/ ?>