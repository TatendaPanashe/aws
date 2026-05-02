@extends('layouts.main')

@section('title')
Welcome
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')


<div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Search Records</div>

                    <div class="card-body">
                    <form action="{{route('csv-data.search')}}" method="GET">
                        <div class="row">
                             <div class="col-lg-6">
                                <div class="form-group">
                                <input type="text" name="agent" class="form-control" placeholder="Search by Name">
                    </div>
    </div>

    <div class="col-lg-6">
    <div class="form-group">
    <input type="text" name="location" placeholder="Search by Site" class="form-control">
    </div>
    </div>
    </div>


<div class="row">
    <div class="col-lg-6">
    <label class="form-label" for="start_date">From Date</label>
    <input type="date" name="to_date" id="to_date" class="form-control">
    </div>
    
    <div class="col-lg-6">
    <label class="form-label" for="end_date">To Date</label>
    <input type="date" name="from_date" id="from_date" class="form-control">
    </div>
</div>
<br>
    
    <button class="btn btn-primary" type="submit">Search</button>
</form>

@if ($results->isNotEmpty())
    <table class="table datatable" id="resultsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Site</th>
                <th>Date</th>
                <th>Reg Number</th>
                <th>Amount(ZWG)</th>
                </tr>
        </thead>
        <tbody>
            @foreach ($results as $result)
                <tr>
                    <td>{{ $result->agent }}</td>
                    <td>{{ $result->location }}</td>
                    <td>{{ $result->issue_date }}</td>
                    <td>{{ $result->vehicle_reg_no }}</td>
                    <td>{{ $result->amount }}</td>
                    </tr>
            @endforeach
        </tbody>
    </table>
  
@else
    <p>No results found.</p>
@endif
                          
</div>
<div>
<button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
</div>
</div>
</div>
</div>
</div>


<script>
    function exportToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('resultsTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'daily_collection.xlsx');
        } else {
            console.log('Table not found!');
        }
    }
    </script>

@endsection