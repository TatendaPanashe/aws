<div>
    <!-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi -->
</div>
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
              <h5 class="card-title">Third Party Premiums</h5>

              <!-- Multi Columns Form -->
              <form class="row g-3" method="post" action="{{route('postthirdparty')}}">@csrf
                <div class="row">
                <div class="col-md-6">
                  <label for="inputName5" class="form-label">Date</label>
                  <input type="date" name="date" class="form-control" id="inputName5">
                </div>
                
              <div class="col-md-6">
                  <label for="inputState" class="form-label">Currency</label>
                  <select id="inputState" name="currency" class="form-select">
                    <option selected>Choose...</option>
                    <option>USD</option>
                    <option>ZWG</option>
                  </select>
                </div>
                </div>
                
                <div class="col-md-12">
                  <label for="inputState" class="form-label">Transaction Type</label>
                  <select id="inputState" name="transaction_type" class="form-select">
                    <option selected>Choose...</option>
                    <option>Cash</option>
                    <option>Swipe</option>
                    <option>Transfers</option>
                  </select>
                </div>
                <div>

                <div class="col-md-12">
                <label for="inputName5" class="form-label">Deposits</label>
                <input type="deposits" name="deposits" class="form-control" id="inputName5">
                </div>
                <div class="col-md-12">
                <label for="inputName5" class="form-label">Comments</label>
                <input type="deposits" name="deposits" class="form-control" id="inputName5">
                </div>
                <br><br><br><br>
                <div class="text-center">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form><!-- End Multi Columns Form -->

            </div>
          </div>

        </div>

        @endsection
