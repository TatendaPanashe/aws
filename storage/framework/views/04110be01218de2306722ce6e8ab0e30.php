<?php $__env->startSection('title'); ?>
Reports Hub
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="pagetitle">
    <h1>Reports Hub</h1>
    <p>Open the core operational reports from one place and review the signals that matter before drilling into detail.</p>
</div>

<section class="section-hero mb-4">
    <div>
        <div class="hero-spotlight">Reporting Center</div>
        <h2>Operational reporting for collections, face values, budgets, and application premiums.</h2>
        <p>
            Use this page as the reporting front door: inspect the latest collection trend, application mix, operational coverage, and jump directly into the detailed report you need.
        </p>
    </div>

    <div class="hero-side-panel">
        <div class="hero-side-card">
            <span>Sites In 30-Day Window</span>
            <strong><?php echo e($operationalHighlights['sites']); ?></strong>
        </div>
        <div class="hero-side-card">
            <span>Users In 30-Day Window</span>
            <strong><?php echo e($operationalHighlights['users']); ?></strong>
        </div>
        <div class="hero-side-card">
            <span>Imported Applications</span>
            <strong><?php echo e($summaryCards[3]['value'] ?? '0'); ?></strong>
        </div>
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

<div class="surface-grid two-up mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Collection Trend</h5>
                    <div class="muted">USD and ZWG activity over the last 30 days.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-calendar-range"></i> Rolling 30 days</span>
            </div>
            <canvas id="reportsCollectionTrend" style="max-height: 360px;"></canvas>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Application Status Mix</h5>
                    <div class="muted">Where uploaded application records are currently concentrated.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-pie-chart"></i> CSV status spread</span>
            </div>
            <canvas id="reportsStatusMix" style="max-height: 360px;"></canvas>
        </div>
    </div>
</div>

<div class="surface-grid two-up mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Top Application Locations</h5>
                    <div class="muted">Locations with the highest number of uploaded application records.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-geo-alt"></i> Location mix</span>
            </div>
            <canvas id="reportsLocationMix" style="max-height: 360px;"></canvas>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title mb-3">Coverage Snapshot</h5>
            <div class="quick-action-grid">
                <article class="quick-action-card">
                    <div class="quick-action-icon"><i class="bi bi-diagram-3"></i></div>
                    <h3>Networks Covered</h3>
                    <p><?php echo e($operationalHighlights['networks']); ?> network<?php echo e($operationalHighlights['networks'] === '1' ? '' : 's'); ?> represented in the 30-day collection window.</p>
                </article>
                <article class="quick-action-card">
                    <div class="quick-action-icon"><i class="bi bi-bank"></i></div>
                    <h3>Budget Records</h3>
                    <p><?php echo e($operationalHighlights['budgets']); ?> budget record<?php echo e($operationalHighlights['budgets'] === '1' ? '' : 's'); ?> currently available for comparison.</p>
                </article>
                <article class="quick-action-card">
                    <div class="quick-action-icon"><i class="bi bi-upc-scan"></i></div>
                    <h3>Face Value Entries</h3>
                    <p><?php echo e($operationalHighlights['face_values']); ?> face value movement record<?php echo e($operationalHighlights['face_values'] === '1' ? '' : 's'); ?> captured so far.</p>
                </article>
                <article class="quick-action-card">
                    <div class="quick-action-icon"><i class="bi bi-people"></i></div>
                    <h3>Total Users</h3>
                    <p><?php echo e($operationalHighlights['users_total']); ?> user account<?php echo e($operationalHighlights['users_total'] === '1' ? '' : 's'); ?> currently available in the workspace.</p>
                </article>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Available Reports</h5>
                <div class="muted">Jump directly into the detailed report that matches the question you are trying to answer.</div>
            </div>
            <span class="soft-chip"><i class="bi bi-lightning-charge"></i> Direct access</span>
        </div>

        <div class="quick-action-grid">
            <?php $__currentLoopData = $reportCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($report['route']); ?>" class="quick-action-card text-decoration-none">
                    <div class="quick-action-icon"><i class="<?php echo e($report['icon']); ?>"></i></div>
                    <h3><?php echo e($report['title']); ?></h3>
                    <p><?php echo e($report['description']); ?></p>
                    <span class="soft-chip"><?php echo e($report['chip']); ?></span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartBaseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "top",
                labels: {
                    color: "#173138",
                    usePointStyle: true,
                    boxWidth: 12,
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
                    color: "rgba(17, 36, 47, 0.08)"
                },
                ticks: {
                    color: "#5f7274"
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: "#5f7274"
                }
            }
        }
    };

    if (document.querySelector("#reportsCollectionTrend")) {
        new Chart(document.querySelector("#reportsCollectionTrend"), {
            type: "line",
            data: {
                labels: <?php echo json_encode($collectionTrendLabels, 15, 512) ?>,
                datasets: [
                    {
                        label: "USD",
                        data: <?php echo json_encode($collectionTrendUsd, 15, 512) ?>,
                        borderColor: "#0f6b6e",
                        backgroundColor: "rgba(15, 107, 110, 0.14)",
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: "ZWG",
                        data: <?php echo json_encode($collectionTrendZwg, 15, 512) ?>,
                        borderColor: "#d97745",
                        backgroundColor: "rgba(217, 119, 69, 0.14)",
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: chartBaseOptions
        });
    }

    if (document.querySelector("#reportsStatusMix")) {
        new Chart(document.querySelector("#reportsStatusMix"), {
            type: "doughnut",
            data: {
                labels: <?php echo json_encode($statusBreakdown->pluck('label')->toArray(), 15, 512) ?>,
                datasets: [{
                    data: <?php echo json_encode($statusBreakdown->pluck('count')->toArray(), 15, 512) ?>,
                    backgroundColor: [
                        "#0f6b6e",
                        "#d97745",
                        "#8fc7bb",
                        "#204f5c",
                        "#f0b56d",
                        "#6f8f96"
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: "#173138",
                            usePointStyle: true,
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }

    if (document.querySelector("#reportsLocationMix")) {
        new Chart(document.querySelector("#reportsLocationMix"), {
            type: "bar",
            data: {
                labels: <?php echo json_encode($locationBreakdown->pluck('label')->toArray(), 15, 512) ?>,
                datasets: [{
                    label: "Applications",
                    data: <?php echo json_encode($locationBreakdown->pluck('count')->toArray(), 15, 512) ?>,
                    backgroundColor: "rgba(15, 107, 110, 0.82)",
                    borderRadius: 12
                }]
            },
            options: chartBaseOptions
        });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/Reports/hub.blade.php ENDPATH**/ ?>