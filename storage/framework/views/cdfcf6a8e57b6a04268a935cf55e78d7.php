<div>
    <!-- Walk as if you are kissing the Earth with your feet. - Thich Nhat Hanh -->
</div>
<!-- resources/views/facevalues/history.blade.php -->



<?php $__env->startSection('title'); ?>
Welcome
<?php $__env->stopSection(); ?>



<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Face Value History</title>
    <script src="<?php echo e(asset('assets/js/xlsx.full.min.js')); ?>"></script>
</head>
<body>
    <div class="card">
        <div class="card-body">
    <h1>Face Value History</h1>

    <table class="datatable" id="facevaluesTable">
    <thead class="thead-dark">
            <tr>
                <th>Date</th>
                <th>Opening Balance</th>
                <th>Total Received</th>
                <th>Total Used</th>
                <th>Total Spoiled</th>
                <th>Closing Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($data['date']); ?></td>
                    <td><?php echo e(number_format($data ['opening_balance'], 2)); ?></td>
                    <td><?php echo e(number_format($data ['total_received'], 2)); ?></td>
                    <td><?php echo e(number_format($data ['total_used'], 2)); ?></td>
                    <td><?php echo e(number_format($data ['total_spoiled'], 2)); ?></td>
                    <td><?php echo e(number_format($data ['closing_balance'], 2)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
    </div>
    </div>
</body>

</html>

<script>
    function exportToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('facevaluesTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'FaceValuesHistory.xlsx');
        } else {
            console.log('Table not found!');
        }
    }
    </script>
</script>



<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/facevalues/compiledhistory.blade.php ENDPATH**/ ?>