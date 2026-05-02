<?php $__env->startSection('title', 'Allocate Face Values'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('includes.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <br><br>
            <h1 class="mb-4"><span class="badge bg-primary"><i class="bi bi-person me-1"></i>Allocate Face Values</span></h1>
            
            <?php if(isset($userSBU) && $userSBU): ?>
                <div class="alert alert-info">
                    <i class="bi bi-building"></i> <strong>Your SBU: <?php echo e($userSBU); ?></strong> - You can only see and allocate to clerks in your SBU.
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Batch ID</span>
                            <h3 class="mb-0"><?php echo e($supervisorfacevalues->id ?? 'N/A'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Received</span>
                            <h3 class="mb-0"><?php echo e(number_format($supervisorfacevalues->received ?? 0, 0)); ?></h3>
                        </div>
                    </div>
                </div>  
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Balance</span>
                            <h3 class="mb-0 text-success"><?php echo e(number_format($supervisorfacevalues->balance ?? 0, 0)); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Range</span>
                            <h3 class="mb-0"><?php echo e($supervisorfacevalues->starting ?? ''); ?> - <?php echo e($supervisorfacevalues->ending ?? ''); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if($clerks->isEmpty()): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>No clerks available for allocation in your SBU (<?php echo e($userSBU ?? 'Unknown'); ?>).</strong><br>
                Please ensure clerks are assigned to sites/networks in your SBU.
            </div>
        <?php else: ?>
            <?php $__currentLoopData = $clerks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo e($user->name); ?> <?php echo e($user->surname ?? ''); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Email:</strong> <?php echo e($user->email); ?>

                        </div>
                        <div class="col-md-3">
                            <strong>Role:</strong> 
                            <?php if($user->role_id == 7): ?>
                                <span class="badge bg-info">ZINARA Clerk</span>
                            <?php elseif($user->role_id == 2): ?>
                                <span class="badge bg-secondary">Clerk</span>
                            <?php else: ?>
                                <?php echo e($user->role->role_name ?? 'Unknown'); ?>

                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Site:</strong> <?php echo e($user->site->site_name ?? 'No Site Assigned'); ?>

                            <?php if($user->site && $user->site->sbu): ?>
                                <br><small class="text-muted">SBU: <?php echo e($user->site->sbu); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Network:</strong> <?php echo e($user->network->name ?? 'Unassigned'); ?>

                        </div>
                    </div>
                    
                    <div class="text-end mb-3">
                        <button onclick="allocate('<?php echo e(addslashes($user->name . ' ' . ($user->surname ?? ''))); ?>','<?php echo e($user->id); ?>', '<?php echo e($user->closing_balance ?? 0); ?>')" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Allocate Face Values
                        </button>
                    </div>
                    
                    <?php
                        $userAllocations = $allocations->where('assigned_to', $user->id);
                    ?>
                    
                    <?php if($userAllocations->count() > 0): ?>
                        <hr>
                        <h6>Previous Allocations</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Range</th>
                                        <th>Allocated</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $userAllocations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($allot->id); ?></td>
                                        <td><?php echo e($allot->starting); ?> - <?php echo e($allot->ending); ?></td>
                                        <td><?php echo e(number_format($allot->allocated, 0)); ?></td>
                                        <td><?php echo e($allot->created_at ? $allot->created_at->format('Y-m-d H:i:s') : 'Not Available'); ?></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>

<!-- Allocation Modal -->
<div class="modal fade" id="allocateModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title">Allocate Face Values</h5>
                    <p class="mb-0">Clerk Name: <strong><span id="thename"></span></strong></p>
                    <p class="mb-0">Current Balance: <strong><span id="thebalance"></span></strong></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Batch Information:</strong> 
                    Batch ID: <?php echo e($supervisorfacevalues->id ?? 'N/A'); ?> | 
                    Available Balance: <strong><?php echo e(number_format($supervisorfacevalues->balance ?? 0, 0)); ?></strong> |
                    Current Range: <?php echo e($supervisorfacevalues->new_starting ?? $supervisorfacevalues->starting ?? ''); ?> - <?php echo e($supervisorfacevalues->ending ?? ''); ?>

                </div>
                
                <form action="<?php echo e(route('allocation')); ?>" id="myForm" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Clerk Name</label>
                        <input id="clerk_name" type="text" class="form-control" readonly required>
                        <input id="clerk_id" type="hidden" name="clerk_id" required>
                        <input id="batch_id" type="hidden" value="<?php echo e($supervisorfacevalues->id ?? ''); ?>" name="batch_id" required>
                        <input id="batchbalance" type="hidden" value="<?php echo e($supervisorfacevalues->balance ?? 0); ?>" name="batchbalance" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Range Starting</label>
                                <input id="starting" readonly value="<?php echo e($supervisorfacevalues->new_starting ?? $supervisorfacevalues->starting ?? ''); ?>" type="text" class="form-control bg-light" name="starting" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Range Ending</label>
                                <input id="ending" readonly type="text" class="form-control bg-light" name="ending" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount to Allocate</label>
                        <input id="received" oninput="calculateEndingRange()" type="number" step="1" class="form-control" name="received" required min="1" max="<?php echo e($supervisorfacevalues->balance ?? 0); ?>" placeholder="Enter number of face values to allocate">
                        <small class="text-muted">Maximum: <?php echo e(number_format($supervisorfacevalues->balance ?? 0, 0)); ?> units</small>
                        <div id="errorMsg" class="text-danger mt-1"></div>
                        <input type="hidden" name="used" value="0">
                        <input type="hidden" id="balanceField" value="<?php echo e($supervisorfacevalues->balance ?? 0); ?>" name="balance" required>
                        <input type="hidden" id="new_starting" name="new_starting" required>
                    </div>
                   
                    <div class="mb-3">
                        <label class="form-label">Calculated Range Ending</label>
                        <div class="alert alert-secondary">
                            <strong>Preview:</strong> <span id="rangePreview">-</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitButton" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Confirm Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    function allocate(clerkname, clerkid, closingbalance) {
        // Reset form
        document.getElementById('myForm').reset();
        document.getElementById('errorMsg').innerHTML = '';
        document.getElementById('submitButton').disabled = false;
        document.getElementById('submitButton').innerHTML = '<i class="bi bi-check-circle"></i> Confirm Allocation';
        document.getElementById('rangePreview').innerHTML = '-';
        document.getElementById('ending').value = '';
        document.getElementById('new_starting').value = '';
        
        // Set values
        $('#allocateModal').modal('show');
        $('#clerk_name').val(clerkname);
        $('#clerk_id').val(clerkid);
        $('#thebalance').text(closingbalance || '0');
        $('#thename').text(clerkname);
        
        // Get the starting value again (it might have been reset)
        var startingVal = document.getElementById('starting').value;
        console.log('Starting value:', startingVal);
    }
    
    function calculateEndingRange() {
        const received = parseInt(document.getElementById('received').value, 10);
        const batchbalance = parseInt(document.getElementById('batchbalance').value, 10);
        let startingSerial = document.getElementById('starting').value;
        const submitButton = document.getElementById('submitButton');
        const errorMsg = document.getElementById('errorMsg');
        
        console.log('Calculating range...');
        console.log('Received:', received);
        console.log('Batch balance:', batchbalance);
        console.log('Starting serial:', startingSerial);
        
        // Validate amount
        if (isNaN(received) || received <= 0) {
            errorMsg.innerHTML = "Please enter a valid allocation amount.";
            submitButton.disabled = true;
            document.getElementById('ending').value = '';
            document.getElementById('new_starting').value = '';
            document.getElementById('rangePreview').innerHTML = '-';
            return false;
        }
        
        if (received > batchbalance) {
            errorMsg.innerHTML = "Allocation amount cannot exceed available balance of " + batchbalance + " units.";
            submitButton.disabled = true;
            document.getElementById('ending').value = '';
            document.getElementById('new_starting').value = '';
            document.getElementById('rangePreview').innerHTML = '-';
            return false;
        }
        
        if (!startingSerial) {
            errorMsg.innerHTML = "No starting range available for this batch.";
            submitButton.disabled = true;
            return false;
        }
        
        errorMsg.innerHTML = "";
        
        // Extract prefix (letters at the beginning)
        let prefix = '';
        let suffix = '';
        let numericPart = '';
        
        // Find where the numbers start
        for (let i = 0; i < startingSerial.length; i++) {
            if (!isNaN(parseInt(startingSerial[i]))) {
                prefix = startingSerial.substring(0, i);
                break;
            }
        }
        
        // If no prefix found, default to empty
        if (prefix === '') {
            prefix = '';
        }
        
        // Find where the numbers end (for suffix)
        let numberStartIndex = -1;
        let numberEndIndex = -1;
        
        for (let i = 0; i < startingSerial.length; i++) {
            if (!isNaN(parseInt(startingSerial[i]))) {
                if (numberStartIndex === -1) {
                    numberStartIndex = i;
                }
                numberEndIndex = i;
            }
        }
        
        if (numberStartIndex !== -1) {
            numericPart = startingSerial.substring(numberStartIndex, numberEndIndex + 1);
            suffix = startingSerial.substring(numberEndIndex + 1);
        } else {
            errorMsg.innerHTML = "Invalid starting range format. Could not find numeric part.";
            submitButton.disabled = true;
            return false;
        }
        
        console.log('Prefix:', prefix);
        console.log('Numeric part:', numericPart);
        console.log('Suffix:', suffix);
        
        if (!numericPart) {
            errorMsg.innerHTML = "Invalid starting range format. Could not find numeric part.";
            submitButton.disabled = true;
            return false;
        }
        
        const numericValue = parseInt(numericPart, 10);
        
        if (isNaN(numericValue)) {
            errorMsg.innerHTML = "Invalid starting range format. Numeric part is not valid.";
            submitButton.disabled = true;
            return false;
        }
        
        // Calculate new ending serial
        const newNumber = numericValue + received - 1;
        const endingSerial = prefix + newNumber + suffix;
        
        // Calculate next starting serial for remaining balance
        const nextStartNumber = newNumber + 1;
        const newStartingSerial = prefix + nextStartNumber + suffix;
        
        console.log('Ending serial:', endingSerial);
        console.log('New starting serial:', newStartingSerial);
        
        // Update fields
        document.getElementById('ending').value = endingSerial;
        document.getElementById('new_starting').value = newStartingSerial;
        document.getElementById('rangePreview').innerHTML = 
            '<span class="text-primary">' + startingSerial + '</span> → ' +
            '<span class="text-success">' + endingSerial + '</span>';
        
        submitButton.disabled = false;
        return true;
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("myForm");
        const submitBtn = document.getElementById("submitButton");

        form.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                return false;
            }
        });

        form.addEventListener("submit", function(event) {
            const received = parseInt(document.getElementById('received').value, 10);
            const ending = document.getElementById('ending').value;
            
            console.log('Form submit - Received:', received, 'Ending:', ending);
            
            if (isNaN(received) || received <= 0) {
                event.preventDefault();
                document.getElementById('errorMsg').innerHTML = "Please enter a valid allocation amount.";
                return false;
            }
            
            if (!ending) {
                event.preventDefault();
                document.getElementById('errorMsg').innerHTML = "Please calculate the ending range first.";
                return false;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Allocating...';
            return true;
        });
        
        // Debug: log the starting value when modal opens
        const startingInput = document.getElementById('starting');
        if (startingInput) {
            console.log('Initial starting value:', startingInput.value);
        }
    });
</script>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .badge {
        font-size: 0.9rem;
        padding: 5px 10px;
    }
    .modal-lg {
        max-width: 800px;
    }
    #rangePreview {
        font-family: monospace;
        font-size: 1.1em;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
</style>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/macbookair/Documents/Projects/gruma-5/resources/views/supervisorfacevalues/allocate.blade.php ENDPATH**/ ?>