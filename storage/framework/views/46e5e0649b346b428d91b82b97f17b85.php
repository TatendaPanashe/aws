<?php $__env->startSection('title'); ?>
Welcome
<?php $__env->stopSection(); ?>



<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $selectedSbu = $selectedSbu ?? null;
    $canFilterBySbu = $canFilterBySbu ?? false;
    $isSbuLocked = $isSbuLocked ?? false;
    $sbuLabel = fn ($sbu) => $sbu === 'SBU1_SBU2' ? 'SBU1 & SBU2' : $sbu;
    $amountPill = function ($value, $tone = 'neutral') {
        $value = (float) $value;

        if ($value == 0.0) {
            return 'report-pill report-pill--neutral';
        }

        $tones = [
            'success' => 'report-pill report-pill--success',
            'warning' => 'report-pill report-pill--warning',
            'danger' => 'report-pill report-pill--danger',
            'info' => 'report-pill report-pill--info',
        ];

        return $tones[$tone] ?? 'report-pill report-pill--neutral';
    };
    $collectionStatus = function ($transactions, $deposits) {
        $transactions = (float) $transactions;
        $deposits = (float) $deposits;

        if ($transactions <= 0 && $deposits <= 0) {
            return [
                'label' => 'No Activity',
                'class' => 'report-pill report-pill--neutral',
                'row_class' => '',
            ];
        }

        if ($deposits >= $transactions) {
            return [
                'label' => 'Covered',
                'class' => 'report-pill report-pill--success',
                'row_class' => '',
            ];
        }

        if ($deposits > 0) {
            return [
                'label' => 'Partial Deposit',
                'class' => 'report-pill report-pill--warning',
                'row_class' => 'report-row--warning',
            ];
        }

        return [
            'label' => 'Undeposited',
            'class' => 'report-pill report-pill--danger',
            'row_class' => 'report-row--danger',
        ];
    };
?>

<?php if (! $__env->hasRenderedOnce('65157678-997b-438a-8ca6-ae0b9284c72a')): $__env->markAsRenderedOnce('65157678-997b-438a-8ca6-ae0b9284c72a'); ?>
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
    <h1>Network Collection Reports</h1>
    <p>Filter submitted activity by network, site, and date range to compare transaction movement across the operation.</p>
</div>

<div class="card">
            <div class="card-body">
              <h5 class="card-title">Filter Reports</h5>
              <div class="glass-note mb-3">
                Use the filters below to narrow the reporting window, then export the USD or ZWG tables when you need an offline working copy.
              </div>
            <form class="row g-3" method="post" action="<?php echo e(route('collectionreports')); ?>"><?php echo csrf_field(); ?>
                <?php if($canFilterBySbu): ?>
                    <div class="col-md-3">
                        <label for="sbu" class="form-label">SBU</label>
                        <select id="sbu" class="form-select" name="sbu" <?php echo e($isSbuLocked ? 'disabled' : ''); ?> onchange="handleSbuChange()">
                            <option value="">All SBUs</option>
                            <?php $__currentLoopData = $sbuOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sbu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($sbu); ?>" <?php echo e((string) $selectedSbu === (string) $sbu ? 'selected' : ''); ?>><?php echo e($sbuLabel($sbu)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if($isSbuLocked): ?>
                            <input type="hidden" name="sbu" value="<?php echo e($selectedSbu); ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="networkId" class="form-label">Network ID</label>
                    <select id="networkId" class="form-select" name="network" onchange="getSites()">
                        <option value="">Choose...</option>
                       
                    <?php $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($network->id); ?>" <?php echo e((string) request('network') === (string) $network->id ? 'selected' : ''); ?>><?php echo e($network->name); ?></option>
                     
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="siteId" class="form-label">Site ID</label>
                    <select id="siteSelect" name="site" class="form-select">
                        
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="startdate" id="startdate" value="<?php echo e(request('startdate')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" name="enddate" id="enddate" value="<?php echo e(request('enddate')); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo e(route('collectionreports')); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
              

            
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">USD Network Totals</h5>
                        <div class="muted">Grouped network totals aligned with the cumulative network report.</div>
                    </div>
                    <button class="btn btn-primary" onclick="exportTableToExcel('usdTable', 'Network_USD_Report.xlsx', 'USD Network Report')">
                        <i class="bi bi-download"></i> Export USD
                    </button>
                </div>

                <div class="report-legend">
                    <span class="report-pill report-pill--info"><i class="bi bi-piggy-bank me-1"></i> Collection Totals</span>
                    <span class="report-pill report-pill--success"><i class="bi bi-bank me-1"></i> Deposits Cover Collections</span>
                    <span class="report-pill report-pill--warning"><i class="bi bi-hourglass-split me-1"></i> Partial Deposit Coverage</span>
                    <span class="report-pill report-pill--danger"><i class="bi bi-exclamation-triangle me-1"></i> No Deposit Against Activity</span>
                </div>

                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="usdTable">
                        <thead>
                            <tr>
                                <th>Site</th>
                                
                                <th>Period</th>
                                <th>Insurance Transactions</th>
                                <th>Zinara Fees</th>
                                <th>Third Party Premiums</th>
                                <th>Full Cover Premiums</th>
                                <th>USD Deposits</th>
                                <th>USD Cash</th>
                                <th>USD Swipe</th>
                                <th>USD Transfers</th>
                                <th>USD Cash In Hand</th>
                               
                                <th>USD Debit Sales</th>
                                <th>USD Credit Sales</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php ($usdStatus = $collectionStatus($transaction->insurance_transactions, $transaction->usd_total_deposited)); ?>
                                <tr class="<?php echo e($usdStatus['row_class']); ?>">
                                    <td><?php echo e($transaction->site_name ?: optional($transaction->site)->site_name ?: 'Unassigned Site'); ?></td>
       
                                    <td><?php echo e($transaction->report_period ?? 'Selected Range'); ?></td>
                                    <td><span class="<?php echo e($amountPill($transaction->insurance_transactions, 'info')); ?>">$<?php echo e(number_format((float) $transaction->insurance_transactions, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zinara_fees, 'warning')); ?>"><?php echo e(number_format((float) ($transaction->zinara_fees ?? 0), 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->third_party_premiums, 'info')); ?>">$<?php echo e(number_format((float) $transaction->third_party_premiums, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->full_cover_premiums, 'info')); ?>">$<?php echo e(number_format((float) $transaction->full_cover_premiums, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_total_deposited, $transaction->usd_total_deposited >= $transaction->insurance_transactions ? 'success' : ((float) $transaction->usd_total_deposited > 0 ? 'warning' : 'danger'))); ?>">$<?php echo e(number_format((float) $transaction->usd_total_deposited, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_cash, 'success')); ?>">$<?php echo e(number_format((float) $transaction->usd_cash, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_swipe, 'info')); ?>">$<?php echo e(number_format((float) $transaction->usd_swipe, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_transfers, 'info')); ?>">$<?php echo e(number_format((float) $transaction->usd_transfers, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_cash_in_hand, 'warning')); ?>">$<?php echo e(number_format((float) $transaction->usd_cash_in_hand, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_debit_sales, 'warning')); ?>">$<?php echo e(number_format((float) $transaction->usd_debit_sales, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->usd_credit_sales, 'warning')); ?>">$<?php echo e(number_format((float) $transaction->usd_credit_sales, 2)); ?></span></td>
                                    <td><span class="<?php echo e($usdStatus['class']); ?>"><?php echo e($usdStatus['label']); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Totals</th>
                                <th></th>
                                <th></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('insurance_transactions'), 'info')); ?>">$<?php echo e(number_format((float) $transactions->sum('insurance_transactions'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zinara_fees'), 'warning')); ?>"><?php echo e(number_format((float) $transactions->sum('zinara_fees'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('third_party_premiums'), 'info')); ?>">$<?php echo e(number_format((float) $transactions->sum('third_party_premiums'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('full_cover_premiums'), 'info')); ?>">$<?php echo e(number_format((float) $transactions->sum('full_cover_premiums'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_total_deposited'), $transactions->sum('usd_total_deposited') >= $transactions->sum('insurance_transactions') ? 'success' : ((float) $transactions->sum('usd_total_deposited') > 0 ? 'warning' : 'danger'))); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_total_deposited'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_cash'), 'success')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_cash'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_swipe'), 'info')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_swipe'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_transfers'), 'info')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_transfers'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_cash_in_hand'), 'warning')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_cash_in_hand'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_debit_sales'), 'warning')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_debit_sales'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('usd_credit_sales'), 'warning')); ?>">$<?php echo e(number_format((float) $transactions->sum('usd_credit_sales'), 2)); ?></span></th>
                                <th>
                                    <?php ($usdTotalsStatus = $collectionStatus($transactions->sum('insurance_transactions'), $transactions->sum('usd_total_deposited'))); ?>
                                    <span class="<?php echo e($usdTotalsStatus['class']); ?>"><?php echo e($usdTotalsStatus['label']); ?></span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
<br><br>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">ZWG Network Totals</h5>
                        <div class="muted">Grouped network totals aligned with the cumulative network report.</div>
                    </div>
                    <button class="btn btn-primary" onclick="exportTableToExcel('zwgTable', 'Network_ZWG_Report.xlsx', 'ZWG Network Report')">
                        <i class="bi bi-download"></i> Export ZWG
                    </button>
                </div>

                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="zwgTable">
                        <thead>
                            <tr>
                                <th>Site</th>
                               
                                <th>Period</th>
                                <th>Insurance Transactions</th>
                                <th>Zinara Fees</th>
                                <th>Third Party Premiums</th>
                                <th>Full Cover Premiums</th>
                                <th>ZWG Deposits</th>
                                <th>ZWG Cash</th>
                                <th>ZWG Swipe</th>
                                <th>ZWG Transfers</th>
                                <th>ZWG Cash In Hand</th>
                               
                                <th>ZWG Debit Sales</th>
                                <th>ZWG Credit Sales</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php ($zwgStatus = $collectionStatus($transaction->zwg_insurance_transactions, $transaction->zwg_total_deposited)); ?>
                                <tr class="<?php echo e($zwgStatus['row_class']); ?>">
                                    <td><?php echo e($transaction->site_name ?: optional($transaction->site)->site_name ?: 'Unassigned Site'); ?></td>
                                    <td><?php echo e($transaction->report_period ?? 'Selected Range'); ?></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_insurance_transactions, 'info')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_insurance_transactions, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_zinara_fees, 'warning')); ?>"><?php echo e(number_format((float) ($transaction->zwg_zinara_fees ?? 0), 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_third_party_premiums, 'info')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_third_party_premiums, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_full_cover_premiums, 'info')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_full_cover_premiums, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_total_deposited, $transaction->zwg_total_deposited >= $transaction->zwg_insurance_transactions ? 'success' : ((float) $transaction->zwg_total_deposited > 0 ? 'warning' : 'danger'))); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_total_deposited, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_cash, 'success')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_cash, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_swipe, 'info')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_swipe, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_transfers, 'info')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_transfers, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_cash_in_hand, 'warning')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_cash_in_hand, 2)); ?></span></td>

                                    <td><span class="<?php echo e($amountPill($transaction->zwg_debit_sales, 'warning')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_debit_sales, 2)); ?></span></td>
                                    <td><span class="<?php echo e($amountPill($transaction->zwg_credit_sales, 'warning')); ?>">ZWG <?php echo e(number_format((float) $transaction->zwg_credit_sales, 2)); ?></span></td>
                                    <td><span class="<?php echo e($zwgStatus['class']); ?>"><?php echo e($zwgStatus['label']); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Totals</th>
                                <th></th>
                                <th></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_insurance_transactions'), 'info')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_insurance_transactions'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_zinara_fees'), 'warning')); ?>"><?php echo e(number_format((float) $transactions->sum('zwg_zinara_fees'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_third_party_premiums'), 'info')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_third_party_premiums'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_full_cover_premiums'), 'info')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_full_cover_premiums'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_total_deposited'), $transactions->sum('zwg_total_deposited') >= $transactions->sum('zwg_insurance_transactions') ? 'success' : ((float) $transactions->sum('zwg_total_deposited') > 0 ? 'warning' : 'danger'))); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_total_deposited'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_cash'), 'success')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_cash'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_swipe'), 'info')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_swipe'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_transfers'), 'info')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_transfers'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_cash_in_hand'), 'warning')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_cash_in_hand'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_debit_sales'), 'warning')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_debit_sales'), 2)); ?></span></th>
                                <th><span class="<?php echo e($amountPill($transactions->sum('zwg_credit_sales'), 'warning')); ?>">ZWG <?php echo e(number_format((float) $transactions->sum('zwg_credit_sales'), 2)); ?></span></th>
                                <th>
                                    <?php ($zwgTotalsStatus = $collectionStatus($transactions->sum('zwg_insurance_transactions'), $transactions->sum('zwg_total_deposited'))); ?>
                                    <span class="<?php echo e($zwgTotalsStatus['class']); ?>"><?php echo e($zwgTotalsStatus['label']); ?></span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            </div>
          </div>

        </div>


<script>


    function getSites(){
        var data = $("#networkId").val();
        var sbu = $("#sbu").val();
    //alert(data);
    $.ajax({
        url: '/getsites/' + data + (sbu ? '?sbu=' + encodeURIComponent(sbu) : ''), // Pass data via URL
            type: 'GET',
            success: function(response) {
                console.log(response); // Log the response
               // alert("Data received successfully! Check console.");
               $("#siteSelect").empty();
                $("#siteSelect").append('<option value=\"\">Select Site</option>');

                // Append new options from response
                $.each(response, function(index, site) {
                    var selected = String(site.id) === String(<?php echo json_encode(request('site'), 15, 512) ?>) ? ' selected' : '';
                    $("#siteSelect").append('<option value="' + site.id + '"' + selected + '>' + site.site_name + '=>'+site.code+'</option>');
                });
            },
            error: function(xhr, status, error) {
                console.error("Error: " + error);
                alert("Failed to fetch data.");
            }
    });
    }

    function handleSbuChange() {
        $("#networkId").val('');
        $("#siteSelect").empty();
        $("#siteSelect").append('<option value=\"\">Select Site</option>');
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('networkId').value) {
            getSites();
        }
    });
</script>

<script>
function exportTableToExcel(tableId, filename, sheetName) {
    const table = document.getElementById(tableId);
    if (!table) {
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: sheetName });
    XLSX.writeFile(workbook, filename);
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/app/resources/views/Reports/index.blade.php ENDPATH**/ ?>