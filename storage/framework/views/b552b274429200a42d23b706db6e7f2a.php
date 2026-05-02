<?php $__env->startSection('title', 'Face Value History'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php
    $roleId = (int) Auth::user()->role_id;
    $isCourierClerk = $roleId === 7;
?>

<div class="card">
    <div class="card-body">
        <h1>Face Value History</h1>
        
        <?php if(session('error')): ?>
            <div class="alert alert-danger mt-3">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
            <div class="alert alert-success mt-3">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if($facevaluelist->isEmpty()): ?>
            <div class="alert alert-info">
                No face value records found.
            </div>
        <?php else: ?>
            <table class="table table-bordered" id="facevaluesTable">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Date</th>
                        <th>Received</th>
                        <th>Opening Stock</th>
                        <th>Face Values Used</th>
                        <th>Spoiled</th>
                        <th>Insurance</th>
                        <th>Channel</th>
                        <th>Closing Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $facevaluelist; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faceValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($faceValue->closing_balance > 0): ?>
                            <tr>
                                <td><?php echo e($faceValue->batch_id); ?> of <?php echo e($faceValue->id); ?></td>
                                <td><?php echo e($faceValue->created_at->format('Y-m-d')); ?></td>
                                <td><?php echo e($faceValue->received); ?></td>
                                <td><?php echo e($faceValue->opening_balance); ?></td>
                                <td><?php echo e($faceValue->used); ?></td>
                                <td><?php echo e($faceValue->spoiled); ?></td>
                                <td><?php echo e($faceValue->insurance_provider ?? 'N/A'); ?></td>
                                <td><?php echo e($faceValue->document_channel ?? 'Standard'); ?></td>
                                <td><?php echo e($faceValue->closing_balance); ?></td>
                                <td>
                                    <button onclick="allocate('<?php echo e($faceValue->starting); ?>','<?php echo e($faceValue->ending); ?>', '<?php echo e($faceValue->batch_balance); ?>', '<?php echo e($faceValue->batch_id); ?>', '<?php echo e($faceValue->id); ?>')" class="btn btn-primary btn-sm">
                                        Declare used FVs for # <?php echo e($faceValue->batch_id); ?>

                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php $__currentLoopData = $used; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allused): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($faceValue->id == $allused->parent_id): ?>
                                <tr>
                                    <td><?php echo e($allused->batch_id); ?> of <?php echo e($allused->parent_id); ?></td>
                                    <td><?php echo e($allused->created_at->format('Y-m-d')); ?></td>
                                    <td>0</td>
                                    <td><?php echo e($allused->opening_balance); ?></td>
                                    <td><?php echo e($allused->used); ?></td>
                                    <td><?php echo e($allused->spoiled); ?></td>
                                    <td><?php echo e($allused->insurance_provider ?? 'N/A'); ?></td>
                                    <td><?php echo e($allused->document_channel ?? 'Standard'); ?></td>
                                    <td><?php echo e($allused->closing_balance); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            
            <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
        <?php endif; ?>
    </div>
</div>

<!-- Declaration Modal -->
<div class="modal fade" id="allocateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Declaring Used Face Values</h5>
                    <p class="modal-title">Range: <span id="range"></span></p>
                    <p class="modal-title">Balance: <span id="thebalance"></span></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="row justify-content-center">
                <p id="error" class="text-danger"></p>
            </div>
            <div class="modal-body">
                <form action="<?php echo e(route('declare')); ?>" id="myForm" method="POST">
                    <?php echo csrf_field(); ?>
                    <input id="clerk_id" type="hidden" name="clerk_id" required>
                    <input id="batch_id" type="hidden" name="batch_id" required>
                    <input id="balanceval" type="hidden" name="balance" required>
                    <input id="batchbalance" type="hidden" step="0.01" value="0" name="batchbalance" required>

                    <div class="mb-3 col-md-12">
                        <label for="balance" class="form-label">Range Starting</label>
                        <input id="starting" readonly type="text" step="0.01" class="form-control" name="starting" required>
                    </div>
                    
                    <div class="mb-3 col-md-12">
                        <label for="balance" class="form-label">Range Ending</label>
                        <input id="ending" readonly type="text" step="0.01" class="form-control" name="ending" required>
                    </div>

                    <div class="mb-3">
                        <label for="used" class="form-label">Used</label>
                        <input id="used" onblur="check()" type="number" step="1" class="form-control" name="used" required>
                        <input id="fvid" type="hidden" name="fvid" required>
                    </div>
                   
                    <div class="mb-3">
                        <label for="spoiled" class="form-label">Spoiled</label>
                        <input id="spoiled" onblur="check()" type="number" value="0" step="1" class="form-control" name="spoiled" required>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment</label>
                        <textarea id="comment" class="form-control" name="comments" rows="3"></textarea>
                    </div>

                    <?php if($isCourierClerk): ?>
                        <div class="mb-3">
                            <label for="insurance_provider" class="form-label">Insurance</label>
                            <select id="insurance_provider" class="form-select" name="insurance_provider">
                                <option value="">Select insurer...</option>
                                <option value="Nicoz Diamond">Nicoz Diamond</option>
                                <option value="Champions">Champions</option>
                            </select>
                            <small class="text-muted">Choose the insurer this Courier face value usage relates to.</small>
                        </div>
                    <?php endif; ?>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="submitButton" class="btn btn-primary">Declare</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    function exportToExcel() {
        var table = document.getElementById('facevaluesTable');
        
        if (table) {
            var wb = XLSX.utils.table_to_book(table, { sheet: "Face Values History" });
            XLSX.writeFile(wb, 'FaceValuesHistory.xlsx');
        } else {
            console.log('Table not found!');
            alert('Table not found for export');
        }
    }
    
    function allocate(starting, ending, balance, batchid, fvid) {
        var range = starting + ' - ' + ending;
        $('#allocateModal').modal('show');
        $('#starting').val(starting);
        $('#ending').val(ending);
        $('#batch_id').val(batchid);
        $('#fvid').val(fvid);
        $('#balanceval').val(balance);
        $('#thebalance').text(balance);
        $('#range').html(range);
        // Reset form fields
        $('#used').val('');
        $('#spoiled').val('0');
        $('#comment').val('');
        if ($('#insurance_provider').length) {
            $('#insurance_provider').val('');
        }
        $('#error').html('');
        $('#submitButton').prop('disabled', false);
    }
    
    function check() {
        const submitButton = document.getElementById('submitButton');
        const spoiled = parseInt(document.getElementById('spoiled').value) || 0;
        const used = parseInt(document.getElementById('used').value) || 0;
        const balanceused = parseInt(document.getElementById('balanceval').value) || 0;
        var allused = spoiled + used;
        
        if (allused > balanceused) {
            document.getElementById('error').innerHTML = 'Used face values cannot be more than the balance';
            submitButton.disabled = true;
        } else {
            document.getElementById('error').innerHTML = '';
            submitButton.disabled = false;
        }
    }
    
    document.getElementById('myForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitButton');
        btn.disabled = true;
        btn.innerText = 'Processing...';
    });
</script>

<!-- Include XLSX library -->
<script src="<?php echo e(asset('assets/js/xlsx.full.min.js')); ?>"></script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/app/resources/views/facevalues/submitlist.blade.php ENDPATH**/ ?>