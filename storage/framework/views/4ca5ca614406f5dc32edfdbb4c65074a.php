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
?>

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
                                <option value="<?php echo e($sbu); ?>" <?php echo e((string) $selectedSbu === (string) $sbu ? 'selected' : ''); ?>><?php echo e($sbu); ?></option>
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
                
            <span class="badge bg-primary"><i class="bi bi-piggy-bank me-1"></i> USD  Transactions</span>
            <table class="table table-striped datatable" id="usdTable">
    <thead>
        <tr>
            <th>Username</th>
            <th>Date</th>
            <th>Total Transactions</th>
            <th>Zinara Transactions</th>
            <th>Third Party Premiums</th>
            <th>Full Cover Premiums</th>
            <th>USD Cash</th>
            <th>USD Swipe</th>

            <th>Bank</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($transaction->username); ?></td>
            <td><?php echo e(\Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i')); ?></td>
            <td>$<?php echo e(number_format($transaction->insurance_transactions, 2)); ?></td>
            <td><?php echo e($transaction->zinara_fees ?? 0); ?></td>
            <td>$<?php echo e(number_format($transaction->third_party_premiums, 2)); ?></td>
            <td>$<?php echo e(number_format($transaction->full_cover_premiums, 2)); ?></td>
            <td>$<?php echo e(number_format($transaction->usd_cash, 2)); ?></td>
            <td>$<?php echo e(number_format($transaction->usd_swipe, 2)); ?></td>
            <td><?php echo e($transaction->bank); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<button class="btn btn-primary" onclick="exportUsdToExcel()">Export to Excel</button>
<!-- Export Button -->
</div>
<br><br>

<span class="badge bg-primary"><i class="bi bi-piggy-bank me-1"></i> ZWG  Transactions</span>
<table class="table table-striped datatable" id="zwgTable">
    <thead>
        <tr>
            <th>Username</th>
            <th>Date</th>
            <th>Insurance Transactions</th>
            <th>Zinara Transactions</th>
            <th>Third Party Premiums</th>
            <th>Full Cover Premiums</th>
            <th>ZWG Swipe</th>
            <th>ZWG Transfers</th>
            
            <th>Bank</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($transaction->username); ?></td>
            <td><?php echo e(\Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i')); ?></td>
            <td>$<?php echo e(number_format($transaction->zwg_insurance_transactions, 2)); ?></td>
            <td><?php echo e($transaction->zwg_zinara_fees ?? 0); ?></td>
            <td>$<?php echo e(number_format($transaction->zwg_third_party_premiums, 2)); ?></td>
            <td>$<?php echo e(number_format($transaction->zwg_full_cover_premiums, 2)); ?></td>
            <td>$<?php echo e(number_format($transaction->zwg_swipe, 2)); ?></td>
            <td><?php echo e($transaction->zwg_transfers ?? 0); ?></td>

            <td><?php echo e($transaction->bank); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<div>
<button class="btn btn-primary" onclick="exportZwgToExcel()">Export to Excel</button>
</div>
<!-- Export Button -->
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
    function exportZwgToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('zwgTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'Cummulative ZWG Network Report.xlsx');
        } else {
            console.log('Table not found!');
        }
    }
   
</script>

<script>
function exportUsdToExcel() {
    var table = document.getElementById('usdTable');
    if (table) {
        console.log('Table found!');

        // Create a workbook from the table data
        var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

        // Write the file and trigger download
        XLSX.writeFile(wb, 'Cummulative USD Network Report.xlsx');
    } else {
        console.log('Table not found!');
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/Reports/index.blade.php ENDPATH**/ ?>