@extends('layouts.main')

@section('title', 'Face Value History')

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $roleId = (int) Auth::user()->role_id;
    $isCourierClerk = $roleId === 7;
@endphp

<div class="card">
    <div class="card-body">
        <h1>Face Value History</h1>
        
        @if(session('error'))
            <div class="alert alert-danger mt-3">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif

        @if($facevaluelist->isEmpty())
            <div class="alert alert-info">
                No face value records found.
            </div>
        @else
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
                    @foreach ($facevaluelist as $faceValue)
                        @if($faceValue->closing_balance > 0)
                            <tr>
                                <td>{{ $faceValue->batch_id }} of {{ $faceValue->id }}</td>
                                <td>{{ $faceValue->created_at->format('Y-m-d') }}</td>
                                <td>{{ $faceValue->received }}</td>
                                <td>{{ $faceValue->opening_balance }}</td>
                                <td>{{ $faceValue->used }}</td>
                                <td>{{ $faceValue->spoiled }}</td>
                                <td>{{ $faceValue->insurance_provider ?? 'N/A' }}</td>
                                <td>{{ $faceValue->document_channel ?? 'Standard' }}</td>
                                <td>{{ $faceValue->closing_balance }}</td>
                                <td>
                                    <button onclick="allocate('{{ $faceValue->starting }}','{{ $faceValue->ending }}', '{{ $faceValue->batch_balance }}', '{{ $faceValue->batch_id }}', '{{ $faceValue->id }}')" class="btn btn-primary btn-sm">
                                        Declare used FVs for # {{ $faceValue->batch_id }}
                                    </button>
                                </td>
                            </tr>
                        @endif
                        
                        @foreach ($used as $allused)
                            @if($faceValue->id == $allused->parent_id)
                                <tr>
                                    <td>{{ $allused->batch_id }} of {{ $allused->parent_id }}</td>
                                    <td>{{ $allused->created_at->format('Y-m-d') }}</td>
                                    <td>0</td>
                                    <td>{{ $allused->opening_balance }}</td>
                                    <td>{{ $allused->used }}</td>
                                    <td>{{ $allused->spoiled }}</td>
                                    <td>{{ $allused->insurance_provider ?? 'N/A' }}</td>
                                    <td>{{ $allused->document_channel ?? 'Standard' }}</td>
                                    <td>{{ $allused->closing_balance }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            
            <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
        @endif
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
                <form action="{{ route('declare') }}" id="myForm" method="POST">
                    @csrf
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

                    @if($isCourierClerk)
                        <div class="mb-3">
                            <label for="insurance_provider" class="form-label">Insurance</label>
                            <select id="insurance_provider" class="form-select" name="insurance_provider">
                                <option value="">Select insurer...</option>
                                <option value="Nicoz Diamond">Nicoz Diamond</option>
                                <option value="Champions">Champions</option>
                            </select>
                            <small class="text-muted">Choose the insurer this Courier face value usage relates to.</small>
                        </div>
                    @endif

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
<script src="{{ asset('assets/js/xlsx.full.min.js') }}"></script>

@endsection
