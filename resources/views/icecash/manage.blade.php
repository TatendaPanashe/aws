<div>
    <!-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi -->
</div>
@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Value History</title>
    <script src="{{ asset('assets/js/xlsx.full.min.js') }}"></script>
</head>
<body>


    <div class="pagetitle">
      <h1>Icecash</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.html">Home</a></li>
          <li class="breadcrumb-item">Tables</li>
          <li class="breadcrumb-item active">Data</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Manage</h5>
             <h1>Icecash Transactions</h1>

              <!-- Table with stripped rows -->
              <table class="table datatable" id="icecashTable">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th data-type="date" data-format="YYYY/DD/MM">Transactions</th>
                    <th>Deposits</th>
                   
                  </tr>
                </thead>
                <tbody>
                    @foreach($sql as $icecash)
                  <tr>
                    <td>{{$icecash->date}}</td>
                    <td>{{$icecash->currency}}</td>
                    <td>{{$icecash->amount}}</td>
                    <td>{{$icecash->deposits}}</td>
                    <td>{{$icecash->transactions}}</td>
                    
                  </tr>
                  @endforeach
                </tbody>
              </table>
              <!-- End Table with stripped rows -->


              <button class="btn btn-primary" onclick="exportToExcel()">Export to Excel</button>
     <!-- Export Button -->
     
     <script>
    function exportToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('icecashTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'icecash.xlsx');
        } else {
            console.log('Table not found!');
        }
    }
    </script>
</script>
            </div>
          </div>

        </div>
      </div>
    </section>



@endsection