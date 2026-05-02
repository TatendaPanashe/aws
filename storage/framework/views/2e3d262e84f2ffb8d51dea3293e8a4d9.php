<?php $__env->startSection('title'); ?>
Face Value Reports
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isRegularSupervisor = ($user->role_id == 3);
    $userSBU = null;
    
    if($user->site && $user->site->sbu) {
        $userSBU = $user->site->sbu;
    } elseif($user->network && $user->network->name) {
        $userSBU = $user->network->name;
    }
?>

<div class="pagetitle">
    <h1>Face Value Reports</h1>
    <p>
        <?php if($isZINARASupervisor): ?>
            ZINARA Supervisor reporting for face value stock, clerk allocations, batch movement, and operational exceptions.
        <?php elseif($isRegularSupervisor && $userSBU): ?>
            Supervisor reporting for SBU <?php echo e($userSBU); ?> face value stock, clerk allocations, batch movement, and operational exceptions.
        <?php else: ?>
            Supervisor reporting for face value stock, clerk allocations, batch movement, and operational exceptions.
        <?php endif; ?>
    </p>
</div>

<?php if($userSBU): ?>
    <div class="alert alert-info mb-4">
        <i class="bi bi-building"></i> <strong>Your SBU: <?php echo e($userSBU); ?></strong> - You are only viewing data for clerks in your SBU.
    </div>
<?php endif; ?>

<section class="section-hero mb-4">
    <div>
        <div class="hero-spotlight">ZINARA Reporting</div>
        <h2>Face value stock control, clerk usage, and exception monitoring from one reporting hub.</h2>
        <p>
            Start here to review stock received, stock allocated, current balances, and issue-focused reports for low balance and spoilage.
        </p>
    </div>

    <div class="hero-side-panel">
        <div class="hero-side-card">
            <span>Stock Overview</span>
            <strong><?php echo e($summaryCards[0]['value'] ?? '0'); ?></strong>
        </div>
        <div class="hero-side-card">
            <span>Active Clerks</span>
            <strong><?php echo e($summaryCards[3]['value'] ?? '0'); ?></strong>
        </div>
        <div class="hero-side-card">
            <span>Open Batches</span>
            <strong><?php echo e($summaryCards[4]['value'] ?? '0'); ?></strong>
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
                    <h5 class="card-title mb-1">Top Clerk Allocations</h5>
                    <div class="muted">Clerks who received the highest allocated face value volume.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> Allocation focus</span>
            </div>
            <?php if($allocationByClerk->isEmpty()): ?>
                <div class="empty-state">No allocation data is available yet for your SBU.</div>
            <?php else: ?>
                <canvas id="faceValueAllocationChart" style="max-height: 360px;"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title mb-3">Report Shortcuts</h5>
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
</div>

<?php if($allocationByClerk->isNotEmpty()): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.querySelector('#faceValueAllocationChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($allocationByClerk->pluck('label')->toArray(), 15, 512) ?>,
            datasets: [{
                label: 'Allocated Face Values',
                data: <?php echo json_encode($allocationByClerk->pluck('total')->toArray(), 15, 512) ?>,
                backgroundColor: 'rgba(15, 107, 110, 0.82)',
                borderRadius: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(17, 36, 47, 0.08)'
                    },
                    ticks: {
                        color: '#5f7274'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#5f7274'
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/facevalues/reports/hub.blade.php ENDPATH**/ ?>