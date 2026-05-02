<?php $__env->startSection('title'); ?>
Cumulative Face Value Report
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $roleId = (int) Auth::user()->role_id;
    $showSbuFilter = in_array($roleId, [1, 3, 5, 6], true);
    $isSbuLocked = in_array($roleId, [3, 6], true) && filled($resolvedSbu ?? null);
    $openingClass = function ($value) {
        if ((float) $value < 0) {
            return 'report-pill report-pill--danger';
        }

        if ((float) $value == 0.0) {
            return 'report-pill report-pill--neutral';
        }

        return 'report-pill report-pill--success';
    };

    $receivedClass = fn ($value) => (float) $value > 0 ? 'report-pill report-pill--info' : 'report-pill report-pill--neutral';
    $usedClass = fn ($value) => (float) $value > 0 ? 'report-pill report-pill--warning' : 'report-pill report-pill--neutral';
    $spoiledClass = fn ($value) => (float) $value > 0 ? 'report-pill report-pill--danger' : 'report-pill report-pill--neutral';

    $closingMeta = function ($closingBalance, $spoiled) {
        $closingBalance = (float) $closingBalance;
        $spoiled = (float) $spoiled;

        if ($closingBalance < 0) {
            return [
                'label' => 'Shortfall',
                'class' => 'report-pill report-pill--danger',
                'row_class' => 'report-row--danger',
            ];
        }

        if ($closingBalance < 20 && $spoiled > 0) {
            return [
                'label' => 'Low + Spoilage',
                'class' => 'report-pill report-pill--danger',
                'row_class' => 'report-row--danger',
            ];
        }

        if ($closingBalance < 20) {
            return [
                'label' => 'Low Balance',
                'class' => 'report-pill report-pill--warning',
                'row_class' => 'report-row--warning',
            ];
        }

        if ($spoiled > 0) {
            return [
                'label' => 'Spoilage Logged',
                'class' => 'report-pill report-pill--warning',
                'row_class' => 'report-row--warning',
            ];
        }

        return [
            'label' => 'Healthy',
            'class' => 'report-pill report-pill--success',
            'row_class' => '',
        ];
    };
?>

<?php if (! $__env->hasRenderedOnce('190d0113-0b40-450a-964c-5564acde4700')): $__env->markAsRenderedOnce('190d0113-0b40-450a-964c-5564acde4700'); ?>
    <style>
        .report-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .report-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6.5rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .report-pill--success {
            background: rgba(22, 101, 52, 0.12);
            border-color: rgba(22, 101, 52, 0.18);
            color: #166534;
        }

        .report-pill--warning {
            background: rgba(180, 83, 9, 0.12);
            border-color: rgba(180, 83, 9, 0.18);
            color: #b45309;
        }

        .report-pill--danger {
            background: rgba(185, 28, 28, 0.12);
            border-color: rgba(185, 28, 28, 0.18);
            color: #b91c1c;
        }

        .report-pill--info {
            background: rgba(8, 145, 178, 0.12);
            border-color: rgba(8, 145, 178, 0.18);
            color: #155e75;
        }

        .report-pill--neutral {
            background: rgba(100, 116, 139, 0.12);
            border-color: rgba(100, 116, 139, 0.18);
            color: #475569;
        }

        .report-row--warning td {
            background: rgba(245, 158, 11, 0.06) !important;
        }

        .report-row--danger td {
            background: rgba(239, 68, 68, 0.06) !important;
        }
    </style>
<?php endif; ?>

<div class="pagetitle">
    <h1>Cumulative Face Value Report</h1>
    <p>Compare opening stock, received, used, spoiled, and closing balances across a selected date range for the supervisor’s clerks.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Cumulative Report</h5>
                <div class="muted">Select a date range and optionally narrow the report to a single clerk.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('facevalues.reports.hub')); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Reports Hub
                </a>
                <a href="<?php echo e(route('facevalues.reports.stock')); ?>" class="btn btn-primary">
                    <i class="bi bi-box-seam"></i> Stock Report
                </a>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('cumulativefvreport')); ?>" class="row g-3">
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
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo e($startDate->toDateString()); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo e($endDate->toDateString()); ?>">
            </div>
            <div class="col-md-3">
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
                <a href="<?php echo e(route('cumulativefvreport')); ?>" class="btn btn-secondary">Reset</a>
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
        No cumulative face value activity was found between <?php echo e($startDate->toDateString()); ?> and <?php echo e($endDate->toDateString()); ?> in the selected scope.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Clerk Balance Summary</h5>
                    <div class="muted"><?php echo e($startDate->toFormattedDateString()); ?> to <?php echo e($endDate->toFormattedDateString()); ?> cumulative face value movement.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportCumulativeFaceValues()">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>

            <div class="report-legend">
                <span class="report-pill report-pill--success">Healthy Balance</span>
                <span class="report-pill report-pill--info">Stock Received</span>
                <span class="report-pill report-pill--warning">Usage Or Low Balance</span>
                <span class="report-pill report-pill--danger">Spoilage Or Shortfall</span>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="cumulativeFaceValuesTable">
                    <thead>
                        <tr>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Network</th>
                            <th>SBU</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Opening Balance</th>
                            <th>Total Received</th>
                            <th>Total Used</th>
                            <th>Total Spoiled</th>
                            <th>Closing Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($status = $closingMeta($data['closing_balance'], $data['total_spoiled'])); ?>
                            <tr class="<?php echo e($status['row_class']); ?>">
                                <td><?php echo e($data['client']); ?></td>
                                <td><?php echo e($data['site']); ?></td>
                                <td><?php echo e($data['network']); ?></td>
                                <td><?php echo e($data['SBU']); ?></td>
                                <td><?php echo e($data['start_date']); ?></td>
                                <td><?php echo e($data['end_date']); ?></td>
                                <td><span class="<?php echo e($openingClass($data['opening_balance'])); ?>"><?php echo e(number_format($data['opening_balance'], 2)); ?></span></td>
                                <td><span class="<?php echo e($receivedClass($data['total_received'])); ?>"><?php echo e(number_format($data['total_received'], 2)); ?></span></td>
                                <td><span class="<?php echo e($usedClass($data['total_used'])); ?>"><?php echo e(number_format($data['total_used'], 2)); ?></span></td>
                                <td><span class="<?php echo e($spoiledClass($data['total_spoiled'])); ?>"><?php echo e(number_format($data['total_spoiled'], 2)); ?></span></td>
                                <td><span class="<?php echo e($status['class']); ?>"><?php echo e(number_format($data['closing_balance'], 2)); ?></span></td>
                                <td>
                                    <span class="<?php echo e($status['class']); ?>"><?php echo e($status['label']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6">Totals</th>
                            <th><span class="<?php echo e($openingClass($docs->sum('opening_balance'))); ?>"><?php echo e(number_format($docs->sum('opening_balance'), 2)); ?></span></th>
                            <th><span class="<?php echo e($receivedClass($docs->sum('total_received'))); ?>"><?php echo e(number_format($docs->sum('total_received'), 2)); ?></span></th>
                            <th><span class="<?php echo e($usedClass($docs->sum('total_used'))); ?>"><?php echo e(number_format($docs->sum('total_used'), 2)); ?></span></th>
                            <th><span class="<?php echo e($spoiledClass($docs->sum('total_spoiled'))); ?>"><?php echo e(number_format($docs->sum('total_spoiled'), 2)); ?></span></th>
                            <th>
                                <?php ($totalStatus = $closingMeta($docs->sum('closing_balance'), $docs->sum('total_spoiled'))); ?>
                                <span class="<?php echo e($totalStatus['class']); ?>"><?php echo e(number_format($docs->sum('closing_balance'), 2)); ?></span>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
    function exportCumulativeFaceValues() {
        const table = document.getElementById('cumulativeFaceValuesTable');
        if (!table) {
            return;
        }

        const workbook = XLSX.utils.table_to_book(table, { sheet: 'Cumulative Face Values' });
        XLSX.writeFile(workbook, 'Cumulative_Face_Value_Report.xlsx');
    }
    </script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/facevalues/cumulativefvreport.blade.php ENDPATH**/ ?>