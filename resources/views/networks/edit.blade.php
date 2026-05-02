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
              <h5 class="card-title">Create Sites</h5>

              <!-- Multi Columns Form -->
              <form class="row g-3" action="{{route('updatesite')}}" method="post">
                @csrf
              
               
                <input type="hidden" name="id" value="{{ $site->id }}">
                <div class="col-md-12">
                  <label for="inputName" class="form-label">Name</label>
                  <input type="text" value="{{ $site->name }}" name="name" class="form-control" id="inputName" placeholder="Name">
                </div>
                <div class="col-md-12">
                  <label for="inputCity" class="form-label">City</label>
                  <input type="text" value="{{ $site->city }}" name="city" class="form-control" id="inputCity" placeholder="City">
                </div>
                <div class="col-md-12">
                  <label for="inputProvince" class="form-label">Province</label>
                  <input type="text" value="{{ $site->province }}" name="province" class="form-control" id="inputProvince" placeholder="Province">
                </div>
                <div class="col-md-12">
                  <label for="inputDescription" class="form-label">Description</label>
                  <textarea name="description" class="form-control" id="inputDescription" placeholder="Description">{{ $site->description }}</textarea>
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
