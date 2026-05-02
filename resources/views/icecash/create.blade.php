<div>
    <!-- Simplicity is the ultimate sophistication. - Leonardo da Vinci -->
</div>
@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="card">
            <div class="card-body">
              <h5 class="card-title">Create Icecash Trans</h5>

              <!-- Multi Columns Form -->
              <form class="row g-3" action="{{route('posticecash')}}" method="post">@csrf
              <div class="col-md-4">
                  <label for="inputState" class="form-label">Currecy</label>
                  <select id="inputState" name="currency" class="form-select">
                    <option selected>Choose...</option>
                    <option>USD</option>
                    <option>ZWG</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label for="inputPassword5" class="form-label">Total Amount</label>
                  <input type="number" name="amount" class="form-control" id="inputPassword5" placeholder="">
                </div>

                <div class="row">
                <div class="col-md-4">
                  <label for="inputState" class="form-label">Transaction Type</label>
                  <select id="inputState" name="transaction_type" class="form-select">
                    <option selected>Select...</option>
                    <option>Cash</option>
                    <option>Swipe</option>
                    <option>Transfers</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label for="inputName5" class="form-label">No. Of Transactions</label>
                  <input type="number" name="transactions" class="form-control" id="inputName5" placeholder="">
                </div>
                </div>
                <div class="row">
                <div class="col-md-6">
                  <label for="inputName5" class="form-label">Amount Deposited</label>
                  <input type="number" name="deposits" class="form-control" id="inputName5" placeholder="">
                </div>
                <div class="col-md-6">
                  <label for="inputName5" class="form-label">Date</label>
                  <input type="date" name="date" class="form-control" id="inputName5" placeholder="">
                </div>
                </div>
               
                <div class="text-center">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form><!-- End Multi Columns Form -->

            </div>
          </div>

        </div>

        @endsection
