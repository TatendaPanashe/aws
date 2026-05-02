@extends('layouts.main')

@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
    <div class="card">
    <div class="card-body">
        <h5 class="card-title">Roles List</h5>
        <a href="{{ route('roles.create') }}" class="btn btn-primary mb-3">Create Role</a>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td>{{ $role->id }}</td>
                    <td>{{ $role->role_name }}</td>
                    <td>{{ $role->role_description }}</td>
                    <td>
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
