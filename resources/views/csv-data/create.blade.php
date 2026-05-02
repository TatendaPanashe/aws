<div>
    <!-- Always remember that you are absolutely unique. Just like everyone else. - Margaret Mead -->
</div>
@extends('layouts.main')

@section('title')
Welcome
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Upload CSV File</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('csv-data.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label for="csv_file">Daily Premiums</label>
                                <input type="file" name="csv_file" id="csv_file" class="form-control">
                            </div>
<br><br>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection