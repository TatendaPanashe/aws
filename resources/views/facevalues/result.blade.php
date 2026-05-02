@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<!-- resources/views/facevalues/result.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Value Result</title>
</head>
<body>
    <h1>Face Value Result</h1>

    <p><strong>Serial No. Range Start:</strong> {{ $faceValue->range_start }}</p>
    <p><strong>Serial No. Range End:</strong> {{ $faceValue->range_end }}</p>
    <p><strong>Opening Balance:</strong> {{ $faceValue->opening_balance }}</p>
    <p><strong>Face Values Used:</strong> {{ $faceValue->face_values_used }}</p>
    <p><strong>Closing Balance:</strong> {{ $faceValue->closing_balance }}</p>

    <a href="/face-values/create">Create New</a>
</body>
</html>
@endsection
