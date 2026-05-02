<?php $__env->startSection('title'); ?>
<?php echo e($chartTitle); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $chartHeight = max(560, ($comparisonRows->count() * 56) + 120);
?>

<div class="pagetitle">
    <h1><?php echo e($chartTitle); ?></h1>
    <p><?php echo e($chartDescription); ?> Active period: <?php echo e($periodLabel); ?>.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Chart Scope</h5>
                <div class="muted">Choose the month, year, view mode, and optional network for this chart page.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('budgets.index', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode])); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Budget Review
                </a>
                <?php if($currency === 'usd'): ?>
                    <a href="<?php echo e(route('budgets.charts.zwg', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode])); ?>" class="btn btn-primary">
                        <i class="bi bi-wallet2"></i> ZWG Chart Page
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(route('budgets.charts.usd', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode])); ?>" class="btn btn-primary">
                        <i class="bi bi-cash-stack"></i> USD Chart Page
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <form class="row g-3" method="get" action="<?php echo e($currency === 'usd' ? route('budgets.charts.usd') : route('budgets.charts.zwg')); ?>">
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select id="year" class="form-select" name="year">
                    <?php for($y = Carbon\Carbon::now()->year - 5; $y <= Carbon\Carbon::now()->year + 5; $y++): ?>
                        <option value="<?php echo e($y); ?>" <?php echo e($year == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select id="month" class="form-select" name="month">
                    <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo e($m); ?>" <?php echo e($month == $m ? 'selected' : ''); ?>>
                            <?php echo e(Carbon\Carbon::create(null, $m, 1)->format('F')); ?>

                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="view_mode" class="form-label">View</label>
                <select id="view_mode" class="form-select" name="view_mode">
                    <option value="monthly" <?php echo e($viewMode === 'monthly' ? 'selected' : ''); ?>>Monthly</option>
                    <option value="ytd" <?php echo e($viewMode === 'ytd' ? 'selected' : ''); ?>>Year-to-Date</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="network_id" class="form-label">Network</label>
                <select id="network_id" class="form-select" name="network_id">
                    <option value="">All Networks</option>
                    <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($network->id); ?>" <?php echo e((string) $networkId === (string) $network->id ? 'selected' : ''); ?>>
                            <?php echo e($network->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?php echo e($currency === 'usd' ? route('budgets.charts.usd') : route('budgets.charts.zwg')); ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if($comparisonRows->isEmpty()): ?>
    <div class="empty-state">
        No site budgets or actual collections were found for <?php echo e($periodLabel); ?> in the selected scope.
    </div>
<?php else: ?>
    <section class="metric-grid mb-4">
        <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="metric-card">
                <span class="metric-label"><i class="<?php echo e($card['icon']); ?>"></i> <?php echo e($card['label']); ?></span>
                <strong class="metric-value"><?php echo e($card['value']); ?></strong>
                <div class="metric-note"><?php echo e($card['note']); ?></div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1"><?php echo e($chartTitle); ?></h5>
                    <div class="muted"><?php echo e($periodLabel); ?> comparison by site.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> Horizontal comparison</span>
            </div>

            <div class="d-flex gap-3 flex-wrap mb-3">
                <div class="soft-chip">
                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: <?php echo e($budgetColor); ?>;"></span>
                    Key: <?php echo e($budgetLabel); ?>

                </div>
                <div class="soft-chip">
                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: <?php echo e($actualColor); ?>;"></span>
                    Key: <?php echo e($actualLabel); ?>

                </div>
            </div>

            <div style="position: relative; height: <?php echo e($chartHeight); ?>px;">
                <canvas id="<?php echo e($chartId); ?>"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Site Breakdown</h5>
                    <div class="muted">Detailed values backing the chart.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-table"></i> Comparison table</span>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Network</th>
                            <th><?php echo e($budgetLabel); ?></th>
                            <th><?php echo e($actualLabel); ?></th>
                            <th>Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $comparisonRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($row['site_name']); ?></td>
                                <td><?php echo e($row['network_name']); ?></td>
                                <?php if($currency === 'usd'): ?>
                                    <td>$<?php echo e(number_format($row['budgeted_amount_usd'], 2)); ?></td>
                                    <td>$<?php echo e(number_format($row['actual_usd'], 2)); ?></td>
                                    <td class="<?php echo e($row[$varianceKey] >= 0 ? 'text-success' : 'text-danger'); ?>">
                                        $<?php echo e(number_format($row[$varianceKey], 2)); ?>

                                    </td>
                                <?php else: ?>
                                    <td>ZWG <?php echo e(number_format($row['budgeted_amount_zwg'], 2)); ?></td>
                                    <td>ZWG <?php echo e(number_format($row['actual_zwg'], 2)); ?></td>
                                    <td class="<?php echo e($row[$varianceKey] >= 0 ? 'text-success' : 'text-danger'); ?>">
                                        ZWG <?php echo e(number_format($row[$varianceKey], 2)); ?>

                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        if (!document.querySelector('#<?php echo e($chartId); ?>')) {
            return;
        }

        new Chart(document.querySelector('#<?php echo e($chartId); ?>'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartData['labels'], 15, 512) ?>,
                datasets: [
                    {
                        label: <?php echo json_encode($budgetLabel, 15, 512) ?>,
                        data: <?php echo json_encode($chartData['budget'], 15, 512) ?>,
                        backgroundColor: <?php echo json_encode($budgetColor, 15, 512) ?>,
                        borderRadius: 12
                    },
                    {
                        label: <?php echo json_encode($actualLabel, 15, 512) ?>,
                        data: <?php echo json_encode($chartData['actual'], 15, 512) ?>,
                        backgroundColor: <?php echo json_encode($actualColor, 15, 512) ?>,
                        borderRadius: 12
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(17, 36, 47, 0.08)'
                        },
                        ticks: {
                            color: '#5f7274'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            color: '#5f7274',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/budgets/chart.blade.php ENDPATH**/ ?>