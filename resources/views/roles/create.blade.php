@extends('layouts.main')

@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="card">
    <div class="card-body">
    <h1>Create Role</h1>
    <form action="{{ route('roles.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="role_name" class="form-label">Role Name</label>
                <input type="text" class="form-control" id="role_name" name="role_name" required>
            </div>
            <div class="mb-3">
                <label for="role_description" class="form-label">Description</label>
                <textarea class="form-control" id="role_description" name="role_description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>
    </div>
@endsection
