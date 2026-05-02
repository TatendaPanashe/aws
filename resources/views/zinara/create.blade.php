@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="card">
    <div class="card-header">
        Zinara
    </div>
    <div class="card-body">
    <form class="row g-3" action="" method="post">@csrf
              <div class="row">
                <div class="col-md-6">
                  <label for="inputPassword5" class="form-label">Start Range</label>
                  <input type="number" name="amount" class="form-control" id="inputPassword5" placeholder="">
                </div>
                <div class="col-md-8">
                  <label for="inputName5" class="form-label">End Range</label>
                  <input type="number" name="transactions" class="form-control" id="inputName5" placeholder="">
                </div>
                </div>
               
               
                <div class="col-md-12">
                  <label for="inputName5" class="form-label">Date</label>
                  <input type="date" name="date" class="form-control" id="inputName5" placeholder="">
                </div>
               
               
                <div class="text-center">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form><!-- End Multi Columns Form -->
    </div>
</div>

@endsection