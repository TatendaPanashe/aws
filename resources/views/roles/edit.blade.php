@extends('layouts.main')

@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Edit Role</h5>
        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="role_name" class="form-label">Role Name</label>
                <input type="text" class="form-control" id="role_name" name="role_name" value="{{ $role->role_name }}" required>
            </div>
            <div class="mb-3">
                <label for="role_description" class="form-label">Description</label>
                <textarea class="form-control" id="role_description" name="role_description">{{ $role->role_description }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
@endsection
