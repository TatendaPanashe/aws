@extends('layouts.main')

@section('title')
    Welcome
@endsection

@section('content')
    @include('includes.header')
    @include('includes.sidebar')

    <div class="pagetitle">
    <span class="badge bg-primary"><i class="bi bi-star me-1"></i> Daily Collections</span>
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
                        <h5 class="card-title">Manage Daily Collection Reports</h5>
                        <div class="table-responsive">
                            <table class="table datatable table-striped table-dark" id="collectionsTable">
                                <thead>
                                    <tr class="table-success">
                                        <th scope="col">#</th>
                                        <th>Name</th>
                                        <th>Site Name</th>
                                        <th>Code</th>
                                        <th>Date</th>
                                        <th>Currency</th>
                                        <th>Bank</th>
                                        <th>USD Daily Collections</th>
                                        <th>USD Cash</th>
                                        <th>USD Swipe</th>
                                        <th>USD Transfers</th>
                                        <th>USD Deposits</th>
                                        <th>ZWG Daily Collections</th>
                                        <th>ZWG Cash</th>
                                        <th>ZWG Swipe</th>
                                        <th>ZWG Transfers</th>
                                        <th>ZWG Deposits</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sql as $collection)
                                        <tr>
                                            <th scope="row">{{ $loop->iteration }}</th>
                                            <td>{{ $collection->username }}</td>
                                            <td>{{ $collection->site_name }}</td>
                                            <td>{{ $collection->code }}</td>
                                            <td>{{ $collection->created_at }}</td>
                                            <td>{{ $collection->currency }}</td>
                                            <td>{{ $collection->bank }}</td>
                                            <td>{{ $collection->insurance_transactions }}</td>
                                            <td>{{ $collection->usd_cash }}</td>
                                            <td>{{ $collection->usd_swipe }}</td>
                                            <td>{{ $collection->usd_transfers }}</td>
                                            <td>{{ $collection->usd_total_deposited }}</td>
                                            <td>{{ $collection->zwg_insurance_transactions }}</td>
                                            <td>{{ $collection->zwg_cash }}</td>
                                            <td>{{ $collection->zwg_swipe }}</td>
                                            <td>{{ $collection->zwg_transfers }}</td>
                                            <td>{{ $collection->zwg_total_deposited }}</td>
                                            <td>{{ $collection->comments }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-primary mt-3" onclick="exportToExcel()">Export Daily Collection Sheet to Excel</button>
                    </div>
                </div>

                

            </div>
        </div>
    </section>

              
     <!-- Export Button -->
     
     <script>
    function exportToExcel() {
        // Get the table element by its ID
        var table = document.getElementById('collectionsTable');
        
        // Check if table is correctly selected
        if (table) {
            console.log('Table found!');
            
            // Create a workbook from the table data
            var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

            // Write the file and trigger download
            XLSX.writeFile(wb, 'Daily Collections Report.xlsx');
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