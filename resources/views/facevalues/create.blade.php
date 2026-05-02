<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
<!-- resources/views/facevalues/create.blade.php -->
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
    <title>Face Value Entry</title>
</head>
<body>
    <div class="card">
        <div class="card-body">
    <h1>Face Values Center</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('postcash') }}" method="POST">
        @csrf
<div class="row">
    <div class="col-lg-4">
        <div>
            <label for="opening_balance" class="form-label">Opening Stock:</label>
            <input type="number" class="form-control" name="opening_balance" id="opening_balance" value="{{ $openingStock }}" required>
        </div>
        </div>
        <div class="col-lg-4">
        <div>
            <label for="face_values_used" class="form-label">Received:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
        <div class="col-lg-4">
        <div>
            <label for="face_values_used" class="form-label">Face Values Used:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
        </div>
<div class="row">
    <div class="col-lg-3">
        <div>
            <label for="face_values_used" class="form-label">Spoiled:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
        <div class="col-lg-3">
        <div>
            <label for="face_values_used" class="form-label">Post Face Values:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
        <div class="col-lg-3">
        <div>
            <label for="face_values_used" class="form-label">NICOZ Face Values:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
        <div class="col-lg-3">
        <div>
            <label for="face_values_used" class="form-label">Comments:</label>
            <input type="number" class="form-control" name="face_values_used" id="face_values_used" required>
        </div>
        </div>
</div>
        <br>
<br>

        <button class="btn btn-primary form-label"type="submit">Submit</button>
    </form>
    </div>
    </div>
</body>
</html>

@endsection
