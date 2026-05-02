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
         
        <div class="card-header">
            <h1>Facevalue Recalculation Module</h1>
            <h3>Clerk Name: {{$user->name}}</h3>
            <h4>Site: {{$user->site->site_name}}</h4>
        </div>
        <div class="card-body">
   

    <table class="table table-bordered datatable" id="facevaluesTable">
    <thead class="thead-dark">
            <tr>
                <th>Date</th>
                <th>Opening Balance</th>
                <th>Total Received</th>
                <th>Total Used</th>
                <th>Total Spoiled</th>
                <th>Closing Balance</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($docs as $data)
                <tr>
                    <td>{{ $data['date'] }}</td>
                    <td>{{ number_format($data ['opening_balance'], 2) }}</td>
                    <td>{{ number_format($data ['total_received'], 2) }}</td>
                    <td>{{ number_format($data ['total_used'], 2) }}</td>
                    <td>{{ number_format($data ['total_spoiled'], 2) }}</td>
                    <td>{{ number_format($data ['closing_balance'], 2) }}</td>
                    <td><button onclick="allocate('{{$data ['date']}}','{{ number_format($data ['opening_balance'], 2)}}', '{{number_format($data ['total_received'], 2)}}', '{{number_format($data ['total_used'], 2)}}', '{{number_format($data ['total_spoiled'], 2)}}','{{number_format($data ['closing_balance'], 2)}}' )" class="btn btn-warning" >Change</button>
                </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
    </div>
    </div>
</body>

</html>


<script>

    function allocate(date,opening_balance,total_received,total_used,total_spoiled,closing_balance) {
     //  alert('Allocation successful ' + range);
      
            $('#allocateModal').modal('show');
//     $('#starting').val(starting);
//     $('#ending').val(ending);
//     $('#batch_id').val(batchid);
//     $('#fvid').val(fvid);
//     $('#balanceval').val(balance);
//    // $('#thebalance').html(closingbalance);
   //$('#thebalance').text(balance);
 $('#thedatef').val(date);
   $('#theopening_balancef').val(opening_balance);
   $('#thetotal_receivedf').val(total_received);
   $('#thetotal_used').val(total_used);
   $('#thetotal_spoiledf').val(total_spoiled);
   $('#theclosing_balancef').val(closing_balance);


   $('#thedate').text(date);
   $('#theopening_balance').text(opening_balance);
   $('#thetotal_received').text(total_received);
   $('#thetotal_used').text(total_used);
   $('#thetotal_spoiled').text(total_spoiled);
   $('#theclosing_balance').text(closing_balance);
//     $('#range').html(range);
    }
</script>



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
                    
                    <form action="{{ route('facevalues.recalculate') }}" id="myForm" method="POST">
                        @csrf

                        <div class="row">
                          
                            <!-- <label for="received" class="form-label">Clerk Name</label> -->
                            <!-- <input id="clerk_name"  type="text" step="0.01" class="form-control" name="clerk_name" required> -->
                            <input id="thedatef"  type="hidden" step="0.01" class="form-control" name="thedatef" required>
                            <input id="theopening_balancef"  type="hidden" class="form-control" value="" name="theopening_balancef" required>
                            <input id="thetotal_receivedf"  type="hidden" class="form-control" value="" name="thetotal_receivedf" required>
                            <input id="thetotal_spoiledf"  type="hidden" step="0.01" class="form-control" value=" " name="thetotal_spoiledf" required>
                            <input id="theclosing_balancef"  type="hidden" step="0.01" class="form-control" value="" name="theclosing_balancef" required>
                            <input id="userid"  type="hidden" step="0.01" class="form-control" value="{{$user->id}} " name="userid" required>
                        <!-- </div> -->
                   <table class="table table-bordered table-hover align-middle mb-0">
            <tbody>
                <tr><th>Date</th><td><span id="thedate"></span></td></tr>
                <tr><th>Opening Balance</th><td><span id="theopening_balance"></span></td></tr>
                <tr><th>Total Received</th><td><span id="thetotal_received"></span></td></tr>
                <tr><th>Total Used</th>
                    <td>
                        <input id="thetotal_used" type="text" 
                               class="form-control" 
                               name="thetotal_usedf" required>
                    </td>
                </tr>
                <tr><th>Total Spoiled</th><td><span id="thetotal_spoiled"></span></td></tr>
                <tr><th>Closing Balance</th><td><span id="theclosing_balance"></span></td></tr>
            </tbody>
        </table>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" onclick="disappear()" id="submitButton" class="btn btn-primary">Declare</button>
                      <!-- <button type="button" onclick="check()" id="submitButton" class="btn btn-primary">Add to stock</button> -->
                   
                    </div>
                        </div>
                    </form>
                  </div>
                </div>
              </div>


@endsection
