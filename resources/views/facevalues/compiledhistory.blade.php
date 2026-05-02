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
            @foreach($docs as $data)
                <tr>
                    <td>{{ $data['date'] }}</td>
                    <td>{{ number_format($data ['opening_balance'], 2) }}</td>
                    <td>{{ number_format($data ['total_received'], 2) }}</td>
                    <td>{{ number_format($data ['total_used'], 2) }}</td>
                    <td>{{ number_format($data ['total_spoiled'], 2) }}</td>
                    <td>{{ number_format($data ['closing_balance'], 2) }}</td>
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
