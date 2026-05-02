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
              <h5 class="card-title">Create Networks</h5>

              <!-- Multi Columns Form -->
              <form class="row g-3" action="{{route('networks.store')}}" method="post">@csrf
              
                <div class="col-md-12">
                  <label for="inputPassword5" class="form-label">Name of Network</label>
                  <input type="text" name="name" class="form-control" id="inputPassword5" placeholder=" name">
                </div>

                <div class="col-md-12">
                  <label for="inputCity" class="form-label">City</label>
                  <select type="text" name="city" class="form-control" id="inputCity" placeholder="city">
                  <option value="Harare">Harare</option>
    <option value="Masvingo">Masvingo</option>
    <option value="Bulawayo">Bulawayo</option>
    <option value="Chitungwiza">Chitungwiza</option>
    <option value="Mutare">Mutare</option>
    <option value="Gweru">Gweru</option>
    <option value="Nkayi">Nkayi</option>
    <option value="Kwekwe">Kwekwe</option>
    <option value="Norton">Norton</option>
    <option value="Kadoma">Kadoma</option>
    <option value="Chegutu">Chegutu</option>
    <option value="Chinhoyi">Chinhoyi</option>
    <option value="Marondera">Marondera</option>
    <option value="Sakubva">Sakubva</option>
    <option value="Bindura">Bindura</option>
    <option value="Hwange">Hwange</option>
    <option value="Beitbridge">Beitbridge</option>
    <option value="Chiredzi">Chiredzi</option>
    <option value="Rusape">Rusape</option>
    <option value="Zvishavane">Zvishavane</option>
    <option value="Chipinge">Chipinge</option>
    <option value="Karoi">Karoi</option>
    <option value="Victoria Falls">Victoria Falls</option>
    <option value="Redcliff">Redcliff</option>
    <option value="Mukumbura">Mukumbura</option>
    <option value="Gwanda">Gwanda</option>
    <option value="Lupane">Lupane</option>
                  </select>
                </div>

                <!-- <div class="col-md-12">
                  <label for="inputProvince" class="form-label">Province</label>
                  <input type="text" name="province" class="form-control" id="inputProvince" placeholder="province">
                </div> -->

                <div class="col-md-12">
                  <label for="inputDescription" class="form-label">Description</label>
                  <textarea name="description" class="form-control" id="inputDescription" placeholder="description"></textarea>
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
