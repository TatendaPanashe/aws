@extends('layouts.main')

@section('title', 'Allocate Face Values')

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="card">
    <div class="card-body">
        <div class="row">
            <br><br>
            <h1 class="mb-4"><span class="badge bg-primary"><i class="bi bi-person me-1"></i>Allocate Face Values</span></h1>
            
            @if(isset($userSBU) && $userSBU)
                <div class="alert alert-info">
                    <i class="bi bi-building"></i>
                    <strong>Your SBU: {{ $userSBU }}</strong>
                    @if((string) preg_replace('/\s+/', '', strtoupper($userSBU)) === 'SBU3')
                        - Courier supervisors can only allocate to SBU3 users.
                    @else
                        - You can allocate this batch to any clerk in the system.
                    @endif
                </div>
            @endif
            
            <div class="row">
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Batch ID</span>
                            <h3 class="mb-0">{{ $supervisorfacevalues->id ?? 'N/A' }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Received</span>
                            <h3 class="mb-0">{{ number_format($supervisorfacevalues->received ?? 0, 0) }}</h3>
                        </div>
                    </div>
                </div>  
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Balance</span>
                            <h3 class="mb-0 text-success">{{ number_format($supervisorfacevalues->balance ?? 0, 0) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Range</span>
                            <h3 class="mb-0">{{ $supervisorfacevalues->starting ?? '' }} - {{ $supervisorfacevalues->ending ?? '' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($clerks->isEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>No clerks available for allocation{{ isset($userSBU) ? ' in your current scope (' . $userSBU . ')' : '' }}.</strong><br>
                Please ensure the target clerks are active and assigned correctly.
            </div>
        @else
            @foreach ($clerks as $user)
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $user->name }} {{ $user->surname ?? '' }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Email:</strong> {{ $user->email }}
                        </div>
                        <div class="col-md-3">
                            <strong>Role:</strong> 
                            @if($user->role_id == 7)
                                <span class="badge bg-info">ZINARA Clerk</span>
                            @elseif($user->role_id == 2)
                                <span class="badge bg-secondary">Clerk</span>
                            @else
                                {{ $user->role->role_name ?? 'Unknown' }}
                            @endif
                        </div>
                        <div class="col-md-3">
                            <strong>Site:</strong> {{ $user->site->site_name ?? 'No Site Assigned' }}
                            @if($user->site && $user->site->sbu)
                                <br><small class="text-muted">SBU: {{ $user->site->sbu }}</small>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <strong>Network:</strong> {{ $user->network->name ?? 'Unassigned' }}
                        </div>
                    </div>
                    
                    <div class="text-end mb-3">
                        <button onclick="allocate('{{ addslashes($user->name . ' ' . ($user->surname ?? '')) }}','{{$user->id}}', '{{ $user->closing_balance ?? 0 }}')" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Allocate Face Values
                        </button>
                    </div>
                    
                    @php
                        $userAllocations = $allocations->where('assigned_to', $user->id);
                    @endphp
                    
                    @if($userAllocations->count() > 0)
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
                                    @foreach ($userAllocations as $allot)
                                    <tr>
                                        <td>{{ $allot->id }}</td>
                                        <td>{{ $allot->starting }} - {{$allot->ending}}</td>
                                        <td>{{ number_format($allot->allocated, 0) }}</td>
                                        <td>{{ $allot->created_at ? $allot->created_at->format('Y-m-d H:i:s') : 'Not Available' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        @endif
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
                    Batch ID: {{ $supervisorfacevalues->id ?? 'N/A' }} | 
                    Available Balance: <strong>{{ number_format($supervisorfacevalues->balance ?? 0, 0) }}</strong> |
                    Current Range: {{ $supervisorfacevalues->new_starting ?? $supervisorfacevalues->starting ?? '' }} - {{ $supervisorfacevalues->ending ?? '' }}
                </div>
                
                <form action="{{ route('allocation') }}" id="myForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Clerk Name</label>
                        <input id="clerk_name" type="text" class="form-control" readonly required>
                        <input id="clerk_id" type="hidden" name="clerk_id" required>
                        <input id="batch_id" type="hidden" value="{{ $supervisorfacevalues->id ?? '' }}" name="batch_id" required>
                        <input id="batchbalance" type="hidden" value="{{ $supervisorfacevalues->balance ?? 0 }}" name="batchbalance" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Range Starting</label>
                                <input id="starting" readonly value="{{ $supervisorfacevalues->new_starting ?? $supervisorfacevalues->starting ?? '' }}" type="text" class="form-control bg-light" name="starting" required>
                                <small class="text-muted">Format: Letters + Numbers + Check Digit (last character)</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Range Ending (Auto-calculated)</label>
                                <input id="ending" readonly type="text" class="form-control bg-light" name="ending" required>
                                <small class="text-muted">The check digit will be preserved from the starting range</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount to Allocate</label>
                        <input id="received" oninput="calculateEndingRange()" type="number" step="1" class="form-control" name="received" required min="1" max="{{ $supervisorfacevalues->balance ?? 0 }}" placeholder="Enter number of face values to allocate">
                        <small class="text-muted">Maximum: {{ number_format($supervisorfacevalues->balance ?? 0, 0) }} units</small>
                        <div id="errorMsg" class="text-danger mt-1"></div>
                        <input type="hidden" name="used" value="0">
                        <input type="hidden" id="balanceField" value="{{ $supervisorfacevalues->balance ?? 0 }}" name="balance" required>
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
        document.getElementById('received').value = '';
        
        // Set values
        $('#allocateModal').modal('show');
        $('#clerk_name').val(clerkname);
        $('#clerk_id').val(clerkid);
        $('#thebalance').text(closingbalance || '0');
        $('#thename').text(clerkname);
    }
    
    function calculateEndingRange() {
        const received = parseInt(document.getElementById('received').value, 10);
        const batchbalance = parseInt(document.getElementById('batchbalance').value, 10);
        let startingSerial = document.getElementById('starting').value;
        const submitButton = document.getElementById('submitButton');
        const errorMsg = document.getElementById('errorMsg');
        
        // Reset on invalid input
        if (isNaN(received) || received <= 0 || !startingSerial) {
            document.getElementById('ending').value = '';
            document.getElementById('new_starting').value = '';
            document.getElementById('rangePreview').innerHTML = '-';
            errorMsg.innerHTML = '';
            submitButton.disabled = false;
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
        
        errorMsg.innerHTML = "";
        
        // IMPORTANT: Remove the last character (check digit) for calculation
        // The last character is a check digit that should be ignored in numeric calculation
        const checkDigit = startingSerial.slice(-1);
        const baseSerial = startingSerial.slice(0, -1);
        
        // Extract prefix (letters) and numeric part from baseSerial (without check digit)
        let prefix = '';
        let numericPart = '';
        
        // Find where numbers start in the base serial
        for (let i = 0; i < baseSerial.length; i++) {
            if (!isNaN(parseInt(baseSerial[i], 10))) {
                prefix = baseSerial.substring(0, i);
                numericPart = baseSerial.substring(i);
                break;
            }
        }
        
        // If no prefix found, the entire baseSerial is numeric
        if (numericPart === '') {
            numericPart = baseSerial;
            prefix = '';
        }
        
        // If still no numeric part, show error
        if (numericPart === '') {
            errorMsg.innerHTML = "Invalid starting range format. Could not extract numeric part.";
            submitButton.disabled = true;
            return false;
        }
        
        // Parse numeric value
        const numericValue = parseInt(numericPart, 10);
        
        if (isNaN(numericValue)) {
            errorMsg.innerHTML = "Invalid starting range format. Numeric part is not valid.";
            submitButton.disabled = true;
            return false;
        }
        
        // Calculate new ending numeric value
        const newNumericValue = numericValue + received - 1;
        
        // Preserve number of digits (pad with leading zeros)
        const paddedNewNumeric = String(newNumericValue).padStart(numericPart.length, '0');
        
        // Build the ending serial WITHOUT check digit first, then add the original check digit
        const endingWithoutCheck = prefix + paddedNewNumeric;
        const endingSerial = endingWithoutCheck + checkDigit;
        
        // Calculate next starting serial for remaining balance
        const nextStartNumeric = newNumericValue + 1;
        const paddedNextStart = String(nextStartNumeric).padStart(numericPart.length, '0');
        const nextStartingWithoutCheck = prefix + paddedNextStart;
        const nextStartingSerial = nextStartingWithoutCheck + checkDigit;
        
        // Update fields
        document.getElementById('ending').value = endingSerial;
        document.getElementById('new_starting').value = nextStartingSerial;
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
    .text-muted {
        font-size: 0.75rem;
    }
</style>

@endsection