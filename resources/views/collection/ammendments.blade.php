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
                  <form method="GET" action="{{ route('dailycollection.ammendments') }}" class="mb-3">
                    <div class="row g-2 align-items-center">
                      <div class="col-auto">
                        <label for="filter_date" class="form-label mb-0">Filter by Date:</label>
                      </div>
                      <div class="col-auto">
                        <input type="date" name="date" id="date" class="form-control" value="{{ request('filter_date') }}">
                      </div>
                      <div class="col-auto">
                        <button type="submit" class="btn btn-success">Submit</button>
                      </div>
                    </div>
                  </form>
                    <div class="card-body">
                        <h5 class="card-title">Manage Daily Collection Reports</h5>
                        <div class="table-responsive">
                            <table class="table datatable table-striped" id="collectionsTable">
                                <thead>
                                    <tr class="table-success">
                                        <th scope="col">#</th>
                                    
                                        <th>Date</th>
                                        <th>Clerk</th>
                                        <th>Site</th>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sql as $collection)
                                        <tr>
                                            <th scope="row">{{ $loop->iteration }}</th>
                                            
                                            <td>{{ $collection->created_at }}</td>
                                            <td>{{ $collection->user->name }}</td>
                                            <td>{{ $collection->site->site_name}}</td>
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
                                            <td><a href="{{route('dailycollection.viewammendment',$collection->id)}}" class="btn btn-warning">Request ammendment</a></td>
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



    <script>
function allocate(
    id,
    date,
     currency,
     bank,
      insurance_transactions,
        usd_cash,
    usd_swipe,
     usd_transfers,
    usd_total_deposited,
        zwg_insurance_transactions,
    zwg_cash,
      zwg_swipe,
    zwg_transfers,
     zwg_total_deposited
   
    
) {
    // Show modal
    $('#allocateModal').modal('show');

    // Populate form fields
    $('#id').val(id);
 $('#currency').val(currency);
   $('#bank').val(bank);
       $('#insurance_transactions').val(insurance_transactions);
        $('#usd_cash').val(usd_cash);
    $('#usd_swipe').val(usd_swipe);
     $('#usd_transfers').val(usd_transfers);
    $('#usd_total_deposited').val(usd_total_deposited);
        $('#zwg_insurance_transactions').val(zwg_insurance_transactions);
    $('#zwg_cash').val(zwg_cash);
      $('#zwg_swipe').val(zwg_swipe);
    $('#zwg_transfers').val(zwg_transfers);
     $('#zwg_total_deposited').val(zwg_total_deposited);
    // Ensure created_at is formatted correctly (if passed as datetime)
    
     $('#thedate').text(date);

   
}
</script>


<!-- Allocation Modal -->
<div class="modal fade" id="allocateModal" tabindex="-1" aria-labelledby="allocateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form action="{{ route('dailycollection.ammendmentrequest') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="allocateModalLabel">
            Allocate Collection for <span id="thedate"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="clerk_id" id="clerk_id">
          <input type="hidden" name="id" id="id">

          <div class="container-fluid">
            <!-- Row 1 -->
            <div class="row g-3">
              <!-- <div class="col-md-6">
                <label class="form-label">Currency</label>
                <select name="currency" id="currency" class="form-select">
                  <option value="USD">USD</option>
                  <option value="ZWG">ZWG</option>
                </select>
              </div> -->

              <div class="col-md-6">
                <label class="form-label">Bank</label>
                <input type="text" name="bank" id="bank" class="form-control">
              </div>
            </div>

            <!-- Row 2 -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">Insurance Transactions</label>
                <input type="number" name="insurance_transactions" id="insurance_transactions" class="form-control" step="0.01">
              </div>

              <div class="col-md-6">
                <label class="form-label">USD Cash</label>
                <input type="number" name="usd_cash" id="usd_cash" class="form-control" step="0.01">
              </div>
            </div>

            <!-- Row 3 -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">USD Swipe</label>
                <input type="number" name="usd_swipe" id="usd_swipe" class="form-control" step="0.01">
              </div>

              <div class="col-md-6">
                <label class="form-label">USD Transfers</label>
                <input type="number" name="usd_transfers" id="usd_transfers" class="form-control" step="0.01">
              </div>
            </div>

            <!-- Row 4 -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">USD Total Deposited</label>
                <input type="number" name="usd_total_deposited" id="usd_total_deposited" class="form-control" step="0.01">
              </div>
              </div>
            <div class="row g-3 mt-2">

              <div class="col-md-6">
                <label class="form-label">ZWG Insurance Transactions</label>
                <input type="number" name="zwg_insurance_transactions" id="zwg_insurance_transactions" class="form-control" step="0.01">
              </div>
            </div>

            <!-- Row 5 -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">ZWG Cash</label>
                <input type="number" name="zwg_cash" id="zwg_cash" class="form-control" step="0.01">
              </div>

              <div class="col-md-6">
                <label class="form-label">ZWG Swipe</label>
                <input type="number" name="zwg_swipe" id="zwg_swipe" class="form-control" step="0.01">
              </div>
            </div>

            <!-- Row 6 -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">ZWG Transfers</label>
                <input type="number" name="zwg_transfers" id="zwg_transfers" class="form-control" step="0.01">
              </div>

              <div class="col-md-6">
                <label class="form-label">ZWG Total Deposited</label>
                <input type="number" name="zwg_total_deposited" id="zwg_total_deposited" class="form-control" step="0.01">
              </div>
            </div>

            <!-- Comments -->
            <div class="row g-3 mt-3">
              <div class="col-md-12">
                <label class="form-label">Comments (add new comments to your request)</label>
                <textarea name="comments" id="comments" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <p class="me-auto mb-0 fw-bold">
            Closing Balance: <span id="thebalance"></span>
          </p>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Allocation</button>
        </div>
      </form>
    </div>
  </div>
</div>



@endsection