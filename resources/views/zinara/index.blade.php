@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')




   <!-- Zinara Card -->
   <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                
                </div>

                <div class="card-body">
                  <h5 class="card-title">Face Values</h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    
                    </div>
                    <div class="ps-3">
                      <h6>Used:</h6>

                      <h6>Left:</h6>
                      
                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Sales Card -->

@endsection