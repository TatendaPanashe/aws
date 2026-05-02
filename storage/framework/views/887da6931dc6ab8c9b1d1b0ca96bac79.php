<?php $__env->startSection('title'); ?>
Dashboard
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $roleLabels = [
        1 => 'Admin',
        2 => 'Clerk',
        3 => 'Supervisor',
        4 => 'Manager',
        5 => 'Super User',
        6 => 'Courier Supervisor',
        7 => 'Courier Clerk',
    ];
    $roleLabel = $roleLabels[$user->role_id] ?? 'Workspace User';
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    $hasCharts = count($lineChartLabels) || count($barChartLabels) || count($networkReportLabels);
?>

<div class="pagetitle">
    <h1>Operational Dashboard</h1>
    <p>
        <?php if($isZINARAUser): ?>
            Courier-focused dashboard for face value management, declarations, and stock tracking.
        <?php else: ?>
            Use this command center to track collection performance, workload, and review priorities across your current reporting scope.
        <?php endif; ?>
    </p>
</div>

<section class="dashboard-hero mb-4">
    <div>
        <div class="hero-spotlight">
            <?php if($isZINARAUser): ?>
                Courier Performance View
            <?php else: ?>
                GRUMA Performance View
            <?php endif; ?>
        </div>
        <h2><?php echo e($roleLabel); ?> workspace with immediate operational context.</h2>
        <p>
            <?php if($isZINARASupervisor): ?>
                Monitor face value stock, allocations to clerks, declaration activity, and spoilage across your Courier clerks.
            <?php elseif($isZINARAClerk): ?>
                Track your face value declarations, remaining stock balances, and declaration history.
            <?php else: ?>
                Focus on the signals that matter first: recent collection momentum, pending review work, active sites, and the fastest routes back into capture and reporting.
            <?php endif; ?>
        </p>
    </div>

    <div class="hero-side-panel">
        <?php $__currentLoopData = $dashboardSpotlights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $spotlight): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="hero-side-card">
                <span><?php echo e($spotlight['label']); ?></span>
                <strong><?php echo e($spotlight['value']); ?></strong>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>

<section class="metric-grid mb-4">
    <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="metric-card">
            <span class="metric-label"><i class="<?php echo e($card['icon']); ?>"></i> <?php echo e($card['label']); ?></span>
            <strong class="metric-value"><?php echo e($card['value']); ?></strong>
            <div class="metric-note"><?php echo e($card['note']); ?></div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Quick Actions</h5>
                <div class="muted">Shortcuts to the highest-value actions for your current role.</div>
            </div>
            <span class="workspace-chip"><i class="bi bi-lightning-charge"></i> Role-aware shortcuts</span>
        </div>

        <div class="quick-action-grid">
            <?php $__currentLoopData = $quickActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($action['route']); ?>" class="quick-action-card text-decoration-none">
                    <div class="quick-action-icon"><i class="<?php echo e($action['icon']); ?>"></i></div>
                    <h3><?php echo e($action['label']); ?></h3>
                    <p><?php echo e($action['description']); ?></p>
                    <span class="soft-chip">Open</span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<?php if($isZINARAUser): ?>
    
    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Face Value Declarations Trend</h5>
                        <div class="muted">Daily used and spoiled face values over the last 30 days.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-calendar-week"></i> Rolling 30 days</span>
                </div>
                <canvas id="zinaraLineChart" style="max-height: 360px;"></canvas>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Stock vs Allocated</h5>
                        <div class="muted">Current face value stock distribution across clerks.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-pie-chart"></i> Current status</span>
                </div>
                <canvas id="zinaraPieChart" style="max-height: 360px;"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Clerk Stock Balances</h5>
                    <div class="muted">Current face value balances by clerk (top 10).</div>
                </div>
                <span class="soft-chip"><i class="bi bi-people"></i> Active clerks</span>
            </div>
            <canvas id="zinaraBarChart" style="max-height: 380px;"></canvas>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Recent Face Value Declarations</h5>
                    <div class="muted">Latest declaration activity in your scope.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-clock-history"></i> Recent entries</span>
            </div>

            <?php if($recentDeclarations->isEmpty()): ?>
                <div class="empty-state">No recent face value declarations found.</div>
            <?php else: ?>
                <div class="table-responsive table-shell">
                    <table class="table activity-table">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Batch ID</th>
                                <th>Used</th>
                                <th>Spoiled</th>
                                <th>Balance After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $recentDeclarations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $declaration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($declaration->clerk_name ?? 'Unknown'); ?></strong></td>
                                    <td><?php echo e($declaration->site_name ?? 'N/A'); ?></td>
                                    <td><?php echo e($declaration->batch_id ?? 'N/A'); ?></td>
                                    <td><?php echo e(number_format($declaration->used ?? 0, 0)); ?></td>
                                    <td><?php echo e(number_format($declaration->spoiled ?? 0, 0)); ?></td>
                                    <td><?php echo e(number_format($declaration->closing_balance ?? 0, 0)); ?></td>
                                    <td><?php echo e(\Carbon\Carbon::parse($declaration->created_at)->format('d M Y H:i')); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if($isZINARASupervisor): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Low Stock Alerts</h5>
                    <div class="muted">Clerks with face value balance below threshold (20 units).</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-triangle"></i> Needs attention</span>
            </div>

            <?php if($lowStockAlerts->isEmpty()): ?>
                <div class="empty-state">All clerks have healthy stock levels.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Current Balance</th>
                                <th>Last Activity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $lowStockAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($alert->clerk_name); ?></td>
                                    <td><?php echo e($alert->site_name); ?></td>
                                    <td><span class="badge bg-warning"><?php echo e(number_format($alert->balance, 0)); ?> units</span></td>
                                    <td><?php echo e($alert->last_activity ?? 'No activity'); ?></td>
                                    <td>
                                        <a href="<?php echo e(route('supervisorfacevalues.allocate', ['id' => $alert->batch_id])); ?>" class="btn btn-sm btn-primary">
                                            Allocate Stock
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    
    <?php if($hasCharts): ?>
        <div class="surface-grid two-up mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Daily Collection Trend</h5>
                            <div class="muted">Last 30 days of submitted USD and ZWG activity.</div>
                        </div>
                        <span class="soft-chip"><i class="bi bi-calendar-week"></i> Rolling 30 days</span>
                    </div>
                    <canvas id="lineChart" style="max-height: 360px;"></canvas>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Network Mix</h5>
                            <div class="muted">Compare transaction volume across the strongest networks in view.</div>
                        </div>
                        <span class="soft-chip"><i class="bi bi-diagram-3"></i> Network ranking</span>
                    </div>
                    <canvas id="networkChart" style="max-height: 360px;"></canvas>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Site Distribution</h5>
                        <div class="muted">Where submitted activity is currently concentrated.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-building"></i> Top active sites</span>
                </div>
                <canvas id="barChart" style="max-height: 380px;"></canvas>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state mb-4">
            No collection data is available yet for the current reporting scope. Once transactions are submitted, the dashboard charts will appear here.
        </div>
    <?php endif; ?>

    <div class="surface-grid two-up">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Recent Activity</h5>
                        <div class="muted">Latest collection submissions in your current scope.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-clock-history"></i> Freshest entries</span>
                </div>

                <?php if($recentCollections->isEmpty()): ?>
                    <div class="empty-state">No recent submissions found yet.</div>
                <?php else: ?>
                    <div class="table-responsive table-shell">
                        <table class="table activity-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Site</th>
                                    <th>USD</th>
                                    <th>ZWG</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $recentCollections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($entry->username ?: 'Unknown User'); ?></strong><br>
                                            <small><?php echo e($entry->bank ?: 'No bank recorded'); ?></small>
                                         </td>
                                        <td><?php echo e($entry->site_name ?: 'Unassigned Site'); ?></td>
                                        <td>$<?php echo e(number_format($entry->insurance_transactions ?? 0, 2)); ?></td>
                                        <td>ZWG <?php echo e(number_format($entry->zwg_insurance_transactions ?? 0, 2)); ?></td>
                                        <td><?php echo e(\Carbon\Carbon::parse($entry->created_at)->format('d M Y H:i')); ?></td>
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
                <h5 class="card-title">Operational Notes</h5>

                <div class="glass-note mb-3">
                    <strong>Pending amendments:</strong> <?php echo e(number_format($pendingAmendments)); ?> request<?php echo e($pendingAmendments == 1 ? '' : 's'); ?> currently waiting for action.
                </div>

                <div class="quick-action-grid">
                    <article class="quick-action-card">
                        <div class="quick-action-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                        <h3>Collection Pulse</h3>
                        <p>Monitor the 30-day trend charts to see whether activity is consolidating into a few sites or spreading across the network.</p>
                    </article>

                    <article class="quick-action-card">
                        <div class="quick-action-icon"><i class="bi bi-shield-check"></i></div>
                        <h3>Control Reminder</h3>
                        <p>Keep amendment queues short and face value balances reconciled so reporting stays reliable and easier to audit.</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if($hasCharts || $isZINARAUser): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sharedGridColor = "rgba(17, 36, 47, 0.08)";
        const sharedTickColor = "#5f7274";

        const chartBaseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "top",
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        color: "#173138",
                        font: {
                            family: "Space Grotesk"
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: sharedGridColor
                    },
                    ticks: {
                        color: sharedTickColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: sharedTickColor
                    }
                }
            }
        };

        <?php if($isZINARAUser): ?>
            // ZINARA Line Chart
            if (document.querySelector("#zinaraLineChart")) {
                new Chart(document.querySelector("#zinaraLineChart"), {
                    type: "line",
                    data: {
                        labels: <?php echo json_encode($zinaraLineChartLabels ?? [], 15, 512) ?>,
                        datasets: [
                            {
                                label: "Used Face Values",
                                data: <?php echo json_encode($zinaraUsedData ?? [], 15, 512) ?>,
                                borderColor: "#d97745",
                                backgroundColor: "rgba(217, 119, 69, 0.15)",
                                fill: true,
                                tension: 0.28
                            },
                            {
                                label: "Spoiled Face Values",
                                data: <?php echo json_encode($zinaraSpoiledData ?? [], 15, 512) ?>,
                                borderColor: "#b91c1c",
                                backgroundColor: "rgba(185, 28, 28, 0.12)",
                                fill: true,
                                tension: 0.28
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            // ZINARA Pie Chart
            if (document.querySelector("#zinaraPieChart")) {
                new Chart(document.querySelector("#zinaraPieChart"), {
                    type: "pie",
                    data: {
                        labels: <?php echo json_encode($zinaraPieLabels ?? ['In Stock', 'Allocated', 'Used/Spoiled']) ?>,
                        datasets: [
                            {
                                data: <?php echo json_encode($zinaraPieData ?? [0, 0, 0]) ?>,
                                backgroundColor: ["#0f6b6e", "#d97745", "#b91c1c"],
                                borderRadius: 12
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: {
                                    font: {
                                        family: "Space Grotesk"
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ZINARA Bar Chart
            if (document.querySelector("#zinaraBarChart")) {
                new Chart(document.querySelector("#zinaraBarChart"), {
                    type: "bar",
                    data: {
                        labels: <?php echo json_encode($clerkBalanceLabels ?? [], 15, 512) ?>,
                        datasets: [
                            {
                                label: "Current Balance",
                                data: <?php echo json_encode($clerkBalanceData ?? [], 15, 512) ?>,
                                backgroundColor: "rgba(15, 107, 110, 0.8)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }
        <?php else: ?>
            // Regular GRUMA Charts
            if (document.querySelector("#lineChart")) {
                new Chart(document.querySelector("#lineChart"), {
                    type: "line",
                    data: {
                        labels: <?php echo json_encode($lineChartLabels, 15, 512) ?>,
                        datasets: [
                            {
                                label: "USD Collections",
                                data: <?php echo json_encode($lineChartUsdData, 15, 512) ?>,
                                borderColor: "#0f6b6e",
                                backgroundColor: "rgba(15, 107, 110, 0.15)",
                                fill: true,
                                tension: 0.28
                            },
                            {
                                label: "ZWG Collections",
                                data: <?php echo json_encode($lineChartZwgData, 15, 512) ?>,
                                borderColor: "#d97745",
                                backgroundColor: "rgba(217, 119, 69, 0.12)",
                                fill: true,
                                tension: 0.28
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            if (document.querySelector("#networkChart")) {
                new Chart(document.querySelector("#networkChart"), {
                    type: "bar",
                    data: {
                        labels: <?php echo json_encode($networkReportLabels, 15, 512) ?>,
                        datasets: [
                            {
                                label: "USD",
                                data: <?php echo json_encode($networkReportUsdData, 15, 512) ?>,
                                backgroundColor: "rgba(15, 107, 110, 0.8)",
                                borderRadius: 12
                            },
                            {
                                label: "ZWG",
                                data: <?php echo json_encode($networkReportZwgData, 15, 512) ?>,
                                backgroundColor: "rgba(217, 119, 69, 0.75)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            if (document.querySelector("#barChart")) {
                new Chart(document.querySelector("#barChart"), {
                    type: "bar",
                    data: {
                        labels: <?php echo json_encode($barChartLabels, 15, 512) ?>,
                        datasets: [
                            {
                                label: "USD",
                                data: <?php echo json_encode($barChartUsdData, 15, 512) ?>,
                                backgroundColor: "rgba(15, 107, 110, 0.82)",
                                borderRadius: 12
                            },
                            {
                                label: "ZWG",
                                data: <?php echo json_encode($barChartZwgData, 15, 512) ?>,
                                backgroundColor: "rgba(143, 199, 187, 0.9)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }
        <?php endif; ?>
    });
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/app/resources/views/index.blade.php ENDPATH**/ ?>