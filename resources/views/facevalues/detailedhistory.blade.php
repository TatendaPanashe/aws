<div>
    <!-- Walk as if you are kissing the Earth with your feet. - Thich Nhat Hanh -->
</div>
<!-- resources/views/facevalues/history.blade.php -->
@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')

<!DOCTYPE html>
<html>
<head>
    <title>Face Value History</title>
    <script src="{{ asset('assets/js/xlsx.full.min.js') }}"></script>
</head>
<body>
    <div class="card">
        <div class="card-body">
        <h1>Face Value History</h1>
@php $firstIteration = true; @endphp

@foreach ($facevaluelist as $faceValue)
    @if($faceValue->closing_balance > 0 )
        <table class="table" id="facevaluesTable">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Date</th>
                    <th>Received</th>
                    <th>Opening Stock</th>
                    <th>Face Values Used</th>
                    <th>Spoiled</th>
                    <th>Closing Stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $faceValue->batch_id }} 0f {{$faceValue->id}}</td>
                    <td>{{ $faceValue->created_at->format('Y-m-d') }}</td>
                    <td>{{ $faceValue->received }}</td>
                    <td>{{ $faceValue->opening_balance }}</td>
                    <td>{{ $faceValue->used }}</td>
                    <td>{{ $faceValue->spoiled }}</td>
                    <td>{{ $faceValue->closing_balance }}</td>
                    <td>
                        @if($firstIteration)
                            <button onclick="allocate('{{ $faceValue->starting }}','{{ $faceValue->ending }}', '{{ $faceValue->batch_balance }}', '{{ $faceValue->batch_id }}', '{{ $faceValue->id }}')" class="btn btn-primary btn-sm">
                                Declare used FVs for # {{ $faceValue->batch_id }}
                            </button> 
                            @php $firstIteration = false; @endphp
                        @endif
                    </td>
                </tr>

                @foreach ($used as $allused)
                    @if($faceValue->id == $allused->parent_id)
                        <tr>
                            <td>{{ $allused->batch_id }} 0f {{$allused->parent_id}}</td>
                            <td>{{ $allused->created_at->format('Y-m-d') }}</td>
                            <td>0</td>
                            <td>{{ $allused->opening_balance }}</td>
                            <td>{{ $allused->used }}</td>
                            <td>{{ $allused->spoiled }}</td>
                            <td>{{ $allused->closing_balance }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach

    <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
    </div>
    </div>
</body>





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

                        <div class="row">
                            <!-- <label for="received" class="form-label">Clerk Name</label> -->
                            <!-- <input id="clerk_name"  type="text" step="0.01" class="form-control" name="clerk_name" required> -->
                            <input id="clerk_id"  type="hidden" step="0.01" class="form-control" name="clerk_id" required>
                            <input id="batch_id"  type="hidden" class="form-control" value="" name="batch_id" required>
                            <input id="balanceval"  type="hidden" class="form-control" value="" name="balance" required>
                            <input id="batchbalance"  type="hidden" step="0.01" class="form-control" value=" $supervisorfacevalues->balance " name="batchbalance" required>
                        <!-- </div> -->
                    

                        <div class="mb-3 col-md-6">
                            <label  for="balance" class="form-label">Range Starting</label>
                            <input id="starting" readonly value="$supervisorfacevalues->new_starting " type="text" step="0.01" class="form-control" name="starting" required>
                        </div>

                       
                       
                        
                        <div class="mb-3 col-md-6">
                            <label  for="balance" class="form-label">Range Ending </label>
                            <input id="ending" readonly type="text" step="0.01" class="form-control" name="ending" required>
                        </div>

                        <div class="mb-3">
                            <span class="text-dange" id="error"> </span>
                            <label for="received" class="form-label">Used</label>
                            <input id="used" onblur="addToSerial()" type="text" step="0.01" class="form-control" name="used" required>
                            <!-- <input id="batchid"  type="hidden" step="0.01" class="form-control" name="batch_id" required> -->
                            <input id="fvid"  type="hidden" step="0.01" class="form-control" value="" name="fvid" required>
                        </div>
                       
                 
                        <div class="mb-3">
                            <label for="balance" class="form-label">Spoiled</label>
                            
                            <input id="spoiled" type="number" value="0"  step="0.01" class="form-control" name="spoiled" required>
                        </div>

                        <div class="mb-3">
                            <label for="balance" class="form-label">Comment</label>
                            <textarea id="comment" class="form-control" name="comment" required></textarea>
                            <!-- <input id="spoiled" type="number" value="0"  step="0.01" class="form-control" name="spoiled" required> -->
                        </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" id="submitButton" class="btn btn-primary">Add to stock</button>
                    </div>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
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
    <script>
 function allocate(starting, ending , balance, batchid,fvid) {
     //  alert('Allocation successful ' + range);
       var range = starting + ' - ' + ending;
            $('#allocateModal').modal('show');
    $('#starting').val(starting);
    $('#ending').val(ending);
    $('#batch_id').val(batchid);
    $('#fvid').val(fvid);
    $('#balanceval').val(balance);
   // $('#thebalance').html(closingbalance);
   $('#thebalance').text(balance);
    $('#range').html(range);
    }
    </script>



@endsection
