<?php $__env->startSection('title'); ?>
Face Value Exceptions
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isRegularSupervisor = ($user->role_id == 3);
    $userSBU = $resolvedSbu ?? null;
    $showSbuFilter = in_array((int) $user->role_id, [1, 3, 5, 6], true);
    $isSbuLocked = in_array((int) $user->role_id, [3, 6], true) && filled($resolvedSbu ?? null);
?>

<div class="pagetitle">
    <h1>Face Value Exceptions</h1>
    <p>Monitor clerks with low remaining batch balances and review spoilage activity in the selected reporting window.</p>
</div>

<?php if($userSBU): ?>
    <div class="alert alert-info mb-4">
        <i class="bi bi-building"></i> <strong>Your SBU: <?php echo e($userSBU); ?></strong> - You are only viewing exception data for clerks in your SBU.
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Exception Report</h5>
                <div class="muted">Adjust the reporting window and low-balance threshold for exception monitoring.</div>
            </div>
            <a href="<?php echo e(route('facevalues.reports.hub')); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Reports Hub
            </a>
        </div>

        <form method="GET" action="<?php echo e(route('facevalues.reports.exceptions')); ?>" class="row g-3">
            <?php if($showSbuFilter): ?>
                <div class="col-md-3">
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
            <div class="col-md-3">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" id="startdate" name="startdate" class="form-control" value="<?php echo e($startDate->toDateString()); ?>">
            </div>
            <div class="col-md-3">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" id="enddate" name="enddate" class="form-control" value="<?php echo e($endDate->toDateString()); ?>">
            </div>
            <div class="col-md-3">
                <label for="threshold" class="form-label">Low Balance Threshold</label>
                <input type="number" step="0.01" id="threshold" name="threshold" class="form-control" value="<?php echo e(number_format($threshold, 2, '.', '')); ?>">
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo e(route('facevalues.reports.exceptions')); ?>" class="btn btn-secondary">Reset</a>
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

<div class="surface-grid two-up">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Low Balance Clerks</h5>
                    <div class="muted">Clerks whose current active stock is at or below the threshold.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-triangle"></i> Threshold alert</span>
            </div>

            <?php if($lowBalanceRows->isEmpty()): ?>
                <div class="empty-state">No clerks are currently below the selected threshold in your SBU.</div>
            <?php else: ?>
                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="faceValueLowBalanceTable">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Current Balance</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $lowBalanceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($row['clerk']); ?></td>
                                    <td><?php echo e($row['site']); ?></td>
                                    <td><?php echo e($row['network']); ?></td>
                                    <td><?php echo e(number_format($row['current_balance'], 2)); ?></td>
                                    <td><?php echo e($row['last_activity_at']); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Spoilage Register</h5>
                    <div class="muted">Entries with spoiled face values inside the selected reporting window.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-octagon"></i> Spoilage</span>
            </div>

            <?php if($spoiledRows->isEmpty()): ?>
                <div class="empty-state">No spoilage activity was recorded in the selected period for your SBU.</div>
            <?php else: ?>
                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="faceValueSpoilageTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Batch ID</th>
                                <th>Spoiled</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $spoiledRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($row['date']); ?></td>
                                    <td><?php echo e($row['clerk']); ?></td>
                                    <td><?php echo e($row['site']); ?></td>
                                    <td><?php echo e($row['network']); ?></td>
                                    <td><?php echo e($row['batch_id']); ?></td>
                                    <td><?php echo e(number_format($row['spoiled'], 2)); ?></td>
                                    <td><?php echo e($row['comments']); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/facevalues/reports/exceptions.blade.php ENDPATH**/ ?>