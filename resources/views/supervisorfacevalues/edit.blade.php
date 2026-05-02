@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
    <div class="container">
        <h1>Edit Face Value Record</h1>

        <form action="{{ route('facevalues.update', $facevalue->id) }}" method="POST">
            @csrf
            @method('PUT') {{-- Use PUT method for updates --}}

            <div class="form-group">
                <label for="received">Received:</label>
                <input type="number" class="form-control" id="received" name="received" value="{{ $facevalue->received }}">
            </div>

            <div class="form-group">
                <label for="used">Used:</label>
                <input type="number" class="form-control" id="used" name="used" value="{{ $facevalue->used }}">
            </div>

            <div class="form-group">
                <label for="spoiled">Spoiled:</label>
                <input type="number" class="form-control" id="spoiled" name="spoiled" value="{{ $facevalue->spoiled }}">
            </div>

            <div class="form-group">
                <label for="comments">Comments:</label>
                <textarea class="form-control" id="comments" name="comments">{{ $facevalue->comments }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Record</button>
            <a href="{{ route('facevaluelist') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection