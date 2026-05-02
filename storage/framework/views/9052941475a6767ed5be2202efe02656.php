<?php $__env->startSection('title'); ?>
Daily Face Value Entries
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $roleId = (int) Auth::user()->role_id;
    $showSbuFilter = in_array($roleId, [1, 3, 5, 6], true);
    $isSbuLocked = in_array($roleId, [3, 6], true) && filled($resolvedSbu ?? null);
?>

<div class="pagetitle">
    <h1>Daily Face Value Entries</h1>
    <p>Inspect clerk-level ZINARA face value activity for a selected day, including allocations, declarations, spoilage, and batch balances.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Daily Entries</h5>
                <div class="muted">Choose a reporting date and optionally focus on one clerk.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('facevalues.reports.hub')); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Reports Hub
                </a>
                <a href="<?php echo e(route('facevalues.reports.exceptions')); ?>" class="btn btn-primary">
                    <i class="bi bi-clipboard2-pulse"></i> Exception Report
                </a>
            </div>
        </div>

        <div class="glass-note mb-3">
            Parent allocation rows show stock handed to clerks, while declaration rows show usage, spoilage, and current batch balance updates.
        </div>

        <form method="GET" action="<?php echo e(route('clientfvreport')); ?>" class="row g-3">
            <?php if($showSbuFilter): ?>
                <div class="col-md-4">
                    <label for="sbu" class="form-label">SBU</label>
                    <select id="sbu" name="sbu" class="form-select" <?php echo e($isSbuLocked ? 'disabled' : ''); ?>>
                        <option value="">All SBUs</option>
                        <?php $__currentLoopData = $sbuOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sbu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($sbu); ?>" <?php echo e((string) ($resolvedSbu ?? '') === (string) $sbu ? 'selected' : ''); ?>><?php echo e($sbu); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if($isSbuLocked): ?>
                        <input type="hidden" name="sbu" value="<?php echo e($resolvedSbu); ?>">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="col-md-4">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo e($date->toDateString()); ?>">
            </div>
            <div class="col-md-4">
                <label for="clerk_id" class="form-label">Clerk</label>
                <select id="clerk_id" name="clerk_id" class="form-select">
                    <option value="">All Clerks</option>
                    <?php $__currentLoopData = $clerks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clerk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($clerk->id); ?>" <?php echo e((string) $selectedClerkId === (string) $clerk->id ? 'selected' : ''); ?>>
                            <?php echo e(trim($clerk->name . ' ' . ($clerk->surname ?? ''))); ?><?php echo e($clerk->site ? ' - ' . $clerk->site->site_name : ''); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo e(route('clientfvreport')); ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<section class="metric-grid mb-4">
    <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="metric-card">
            <span class="metric-label"><i class="<?php echo e($card['icon']); ?>"></i> <?php echo e($card['label']); ?></span>
            <strong class="metric-value"><?php echo e($card['value']); ?></strong>
            <div class="metric-note"><?php echo e($card['note']); ?></div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<?php if($docs->isEmpty()): ?>
    <div class="empty-state">
        No face value activity was captured for <?php echo e($date->toDateString()); ?> in the selected scope.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Daily Entry Register</h5>
                    <div class="muted"><?php echo e($date->toFormattedDateString()); ?> detailed face value movement.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportFaceValueDailyEntries()">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="faceValueDailyEntriesTable">
                    <thead>
                        <tr>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Network</th>
                            <th>SBU</th>
                            <th>Date & Time</th>
                            <th>Entry Type</th>
                            <th>Range</th>
                            <th>Opening Balance</th>
                            <th>Received</th>
                            <th>Used</th>
                            <th>Spoiled</th>
                            <th>Closing Balance</th>
                            <th>Batch Balance</th>
                            <th>Batch ID</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($data['client']); ?></td>
                                <td><?php echo e($data['siteid']); ?></td>
                                <td><?php echo e($data['network']); ?></td>
                                <td><?php echo e($data['SBU']); ?></td>
                                <td><?php echo e($data['date']); ?></td>
                                <td>
                                    <span class="soft-chip"><?php echo e($data['is_parent'] ? 'Parent Allocation' : 'Declaration'); ?></span>
                                </td>
                                <td><?php echo e($data['starting_range']); ?> - <?php echo e($data['ending_range']); ?></td>
                                <td><?php echo e(number_format($data['opening_balance'], 2)); ?></td>
                                <td><?php echo e(number_format($data['received'], 2)); ?></td>
                                <td><?php echo e(number_format($data['used'], 2)); ?></td>
                                <td><?php echo e(number_format($data['spoiled'], 2)); ?></td>
                                <td><?php echo e(number_format($data['closing_balance'], 2)); ?></td>
                                <td><?php echo e(number_format($data['batch_balance'], 2)); ?></td>
                                <td><?php echo e($data['batch_id'] ?? 'N/A'); ?></td>
                                <td><?php echo e($data['comments'] ?? 'No comment'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="8">Totals</th>
                            <th><?php echo e(number_format($docs->sum('received'), 2)); ?></th>
                            <th><?php echo e(number_format($docs->sum('used'), 2)); ?></th>
                            <th><?php echo e(number_format($docs->sum('spoiled'), 2)); ?></th>
                            <th><?php echo e(number_format($docs->sum('closing_balance'), 2)); ?></th>
                            <th><?php echo e(number_format($docs->sum('batch_balance'), 2)); ?></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
    function exportFaceValueDailyEntries() {
        const table = document.getElementById('faceValueDailyEntriesTable');
        if (!table) {
            return;
        }

        const workbook = XLSX.utils.table_to_book(table, { sheet: 'Daily Face Value Entries' });
        XLSX.writeFile(workbook, 'Daily_Face_Value_Entries.xlsx');
    }
    </script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/facevalues/clientfvreport.blade.php ENDPATH**/ ?>