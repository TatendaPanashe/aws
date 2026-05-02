@extends('layouts.main')

@section('title')
    Welcome
@endsection

@section('content')
    @include('includes.header')
    @include('includes.sidebar')

    <div class="pagetitle">
    <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Daily Transactions</span>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</a></li>
                <li class="breadcrumb-item">Tables</li>
                <li class="breadcrumb-item active">Data</li>
            </ol>
        </nav>
    </div><section class="section" style="background-image: {{ asset('assets/img/background.png') }} background-repeat: repeat; background-position: center; background-size: 50%;">
        <div class="row">
            <div class="col-lg-12">
              

            <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Manage Daily Transactions Reports</h5>
                        <div class="table-responsive">
                            <table class="table datatable table-striped table-dark"   id="premiumsTable">
                                <thead>
                                    <tr class="table-success">
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th>Date</th>
                                        <th>Site Name</th>
                                        <th>Code</th>
                                        <th scope="col">USD 3rd Party Premiums</th>
                                        <th scope="col">USD Comprehensive</th>
                                        <th scope="col">USD ZinaraFees</th>
                                        <th scope="col">ZWG 3rd Party Premiums</th>
                                        <th scope="col">ZWG Comprehensive</th>
                                        <th scope="col">ZWG Zinara Fees</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sql as $collection)
                                        <tr>
                                            <th scope="row">{{ $loop->iteration }}</th>
                                            <td>{{ $collection->username }}</td>
                                            <td>{{ $collection->created_at }}</td>
                                            <td>{{ $collection->site_name }}</td>
                                            <td>{{ $collection->code }}</td>
                                            <td>{{ $collection->third_party_premiums }}</td>
                                            <td>{{ $collection->full_cover_premiums }}</td>
                                            <td>{{ $collection->zinara_fees }}</td>
                                            <td>{{ $collection->zwg_third_party_premiums }}</td>
                                            <td>{{ $collection->zwg_full_cover_premiums }}</td>
                                            <td>{{ $collection->zwg_zinara_fees }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-primary mt-3" onclick="zwgexportToExcel()">Export Transactions to Excel</button>
                        </div>
                </div>


            </div>
        </div>
    </section>
                <script>
                    function zwgexportToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('premiumsTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'Daily Premiums Report.xlsx');
        } else {
            console.log('Table not found!');
        }
    }
    </script>
    </div>
          </div>

        </div>
      </div>
    </section>



@endsection