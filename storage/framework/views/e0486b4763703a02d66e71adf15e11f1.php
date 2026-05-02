<?php $__env->startSection('title', 'Courier Connect Sales'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="pagetitle">
    <h1>Courier Connect Sales</h1>
    <p>Capture Courier Connect sales separately from normal face value declarations so supervisors can compare Courier document sales against the usual Nicoz Diamond and Champions activity.</p>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<section class="metric-grid mb-4">
    <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="metric-card">
            <span class="metric-label"><i class="<?php echo e($card['icon']); ?>"></i> <?php echo e($card['label']); ?></span>
            <strong class="metric-value"><?php echo e($card['value']); ?></strong>
            <div class="metric-note"><?php echo e($card['note']); ?></div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<?php if($isCourierClerk): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Submit Courier Sale</h5>
            <div class="glass-note mb-3">
                Record the insurer and sales amount made using the Courier Connect face value document. Face value usage itself is still declared from the normal face value declaration screen.
            </div>

            <?php if($batches->isEmpty()): ?>
                <div class="alert alert-warning mb-0">
                    No active face value batches are available for your account. Ask your supervisor to allocate Courier stock before posting Courier sales.
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('courier.sales.store')); ?>" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-6">
                        <label for="face_value_id" class="form-label">Face Value Batch</label>
                        <select id="face_value_id" name="face_value_id" class="form-select" required>
                            <option value="">Choose batch...</option>
                            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($batch->id); ?>" data-batch="<?php echo e($batch->batch_id); ?>" <?php echo e((string) old('face_value_id') === (string) $batch->id ? 'selected' : ''); ?>>
                                    #<?php echo e($batch->batch_id); ?> | <?php echo e($batch->starting); ?> - <?php echo e($batch->ending); ?> | Balance <?php echo e(number_format((float) $batch->batch_balance, 0)); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <input type="hidden" id="batch_id" name="batch_id" value="<?php echo e(old('batch_id')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="insurance_provider" class="form-label">Insurance</label>
                        <select id="insurance_provider" name="insurance_provider" class="form-select" required>
                            <option value="">Choose...</option>
                            <?php $__currentLoopData = $insuranceProviders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($provider); ?>" <?php echo e(old('insurance_provider') === $provider ? 'selected' : ''); ?>><?php echo e($provider); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Currency</label>
                        <select id="currency" name="currency" class="form-select" required>
                            <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $currency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($currency); ?>" <?php echo e(old('currency', 'USD') === $currency ? 'selected' : ''); ?>><?php echo e($currency); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="sales_amount" class="form-label">Sales Amount</label>
                        <input type="number" min="0.01" step="0.01" id="sales_amount" name="sales_amount" class="form-control" value="<?php echo e(old('sales_amount')); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sale_date" class="form-label">Sale Date</label>
                        <input type="date" id="sale_date" name="sale_date" class="form-control" value="<?php echo e(old('sale_date', $selectedDate)); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="comments" class="form-label">Comment</label>
                        <input type="text" id="comments" name="comments" class="form-control" value="<?php echo e(old('comments')); ?>" placeholder="Optional reconciliation note">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Submit Courier Sale</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Courier Sales Register</h5>
                <div class="muted">Review Courier Connect sales by insurer, batch, clerk, and currency.</div>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('courier.sales.index')); ?>" class="row g-3 mb-3">
            <div class="col-md-4">
                <label for="sale_date_filter" class="form-label">Sale Date</label>
                <input type="date" id="sale_date_filter" name="sale_date" class="form-control" value="<?php echo e(request('sale_date', $selectedDate)); ?>">
            </div>
            <div class="col-md-4">
                <label for="provider_filter" class="form-label">Insurance</label>
                <select id="provider_filter" name="insurance_provider" class="form-select">
                    <option value="">All insurers</option>
                    <?php $__currentLoopData = $insuranceProviders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($provider); ?>" <?php echo e($selectedProvider === $provider ? 'selected' : ''); ?>><?php echo e($provider); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php if($clerks->isNotEmpty()): ?>
                <div class="col-md-4">
                    <label for="clerk_id" class="form-label">Clerk</label>
                    <select id="clerk_id" name="clerk_id" class="form-select">
                        <option value="">All clerks</option>
                        <?php $__currentLoopData = $clerks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clerk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($clerk->id); ?>" <?php echo e((string) $selectedClerkId === (string) $clerk->id ? 'selected' : ''); ?>>
                                <?php echo e(trim($clerk->name . ' ' . ($clerk->surname ?? ''))); ?><?php echo e($clerk->site ? ' - ' . $clerk->site->site_name : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo e(route('courier.sales.index')); ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <?php if($sales->isEmpty()): ?>
            <div class="empty-state">No Courier Connect sales were found for the current selection.</div>
        <?php else: ?>
            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="courierSalesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Insurance</th>
                            <th>Currency</th>
                            <th>Sales Amount</th>
                            <th>Batch ID</th>
                            <th>Batch Range</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e(optional($sale->sale_date)->toDateString()); ?></td>
                                <td><?php echo e(trim(($sale->clerk->name ?? 'Unknown') . ' ' . ($sale->clerk->surname ?? ''))); ?></td>
                                <td><?php echo e($sale->clerk?->site?->site_name ?? 'N/A'); ?></td>
                                <td><?php echo e($sale->insurance_provider); ?></td>
                                <td><?php echo e($sale->currency); ?></td>
                                <td><?php echo e(number_format((float) $sale->sales_amount, 2)); ?></td>
                                <td><?php echo e($sale->batch_id); ?></td>
                                <td><?php echo e($sale->faceValue?->starting ?? 'N/A'); ?> - <?php echo e($sale->faceValue?->ending ?? 'N/A'); ?></td>
                                <td><?php echo e($sale->comments ?: 'No comment'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const batchSelect = document.getElementById('face_value_id');
    const batchIdField = document.getElementById('batch_id');

    if (!batchSelect || !batchIdField) {
        return;
    }

    function syncBatchId() {
        const selectedOption = batchSelect.options[batchSelect.selectedIndex];
        batchIdField.value = selectedOption ? (selectedOption.dataset.batch || '') : '';
    }

    batchSelect.addEventListener('change', syncBatchId);
    syncBatchId();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma/resources/views/courier-sales/index.blade.php ENDPATH**/ ?>