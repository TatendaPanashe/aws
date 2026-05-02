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
              
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Name of Site</label>
                  <input type="text" value="{{ $site->site_name }}" name="site_name" class="form-control" id="inputPassword5" placeholder="site name">
                </div>
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Name of Site</label>
                  <input type="text" value="{{ $site->code_name }}" name="code_name" class="form-control" id="inputSiteName" placeholder="code name">
                </div>
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Site Code</label>
                  <input type="text" value="{{ $site->code }}" name="code" class="form-control" id="inputSiteCode" placeholder="code">
                </div>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="{{ $site->id }}">
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Site Description</label>
                  <textarea type="text"  name="site_description" class="form-control" id="inputPassword5" placeholder="site description">
                  {{$site->site_description}}
                </textarea>
                </div>

                  <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">POS Number</label>
                  <input type="text" value="{{ $site->POS }}" name="POS" class="form-control" id="inputSiteCode" placeholder="POS Number">
                </div>

                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Bank</label>
                  <input type="text" value="{{ $site->bank }}" name="bank" class="form-control" id="inputSiteCode" placeholder="Bank">
                </div>
            
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">SBU</label>
              <select type="text" name="sbu" class="form-control" id="inputSiteCode" placeholder="Bank">
                    <option selected value="{{ $site->sbu }}">{{ $site->sbu }}</option>
                    <option value="SBU1">SBU1</option>
                    <option value="SBU2">SBU2</option>
                  </select>                
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
