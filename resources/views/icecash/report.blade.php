@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')

<!DOCTYPE html>
<html>
<head>
    <title>ICR</title>
</head>
<body>
    <h1></h1>


    <div class="card">
        <div class="card-header">
        Icecash Report Search
        </div>
        <div class="card-body">

        
    <form action="{{route('posticereport')}}" method="post">@csrf
     <div class="row">
            <div class="col-lg-6">
        <label class="form-label" for="start_date">Start Date:</label>
        <input  class="form-control" type="date" id="start_date" name="start_date"><br><br>
        </div>
        <div class="col-lg-6">
        <label class="form-label" for="end_date">End Date:</label>
        <input class="form-control"  type="date" id="end_date" name="end_date"><br><br>
        </div>
     </div>
     <div>
        <label class="form-label" for="currency" class="align-items-center">Currency:</label>
        <select name="currency">
            <option>All</option>
            <option>USD</option>
            <option>ZWG</option>
        </select><br><br>
    </div>
        <button class="btn btn-primary" type="submit">Search</button>
    
    </form>
    </div>
    </div>
</body>
</html>
@endsection