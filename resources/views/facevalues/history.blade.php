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

    <table class="datatable" id="facevaluesTable">
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
            </tr>
        </thead>
        <tbody>
            @foreach ($faceValues as $faceValue)
                <tr>
                    <td>{{ $faceValue->batch_id}}</td>
                    <td>{{ $faceValue->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $faceValue->received }}</td>
                    <td>{{ $faceValue->opening_balance }}</td>
                    <td>{{ $faceValue->used }}</td>
                    <td>{{ $faceValue->spoiled }}</td>
                    <td>{{ $faceValue->insurance_provider ?? 'N/A' }}</td>
                    <td>{{ $faceValue->document_channel ?? 'Standard' }}</td>
                    <td>{{ $faceValue->closing_balance }}</td>
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



@endsection
