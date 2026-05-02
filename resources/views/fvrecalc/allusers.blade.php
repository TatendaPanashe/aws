@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="container">
    <h1 class="mb-4"> Face Value Users</h1>
    <!-- <a href="{{ route('teams.create') }}" class="btn btn-primary mb-3">Add User</a> Link to create user -->
    @if (session('success'))
      <div class="alert alert-success">
        {{ session('success') }}
      </div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger">
        {{ session('error') }}
      </div>
    @endif
    <table class="table datatable table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                
                <th>Role</th>
                <th>Site</th>
                <th>Network</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    
                    <td>{{ $user->role->role_name }}</td>
                    <td>{{ $user->site->site_name }}</td>
                    <td>{{ $user->network->name }}</td>
                    <td>
                        <a href="{{ route('facevalues.getuser', $user->id) }}" class="btn btn-warning btn-sm">View History</a>
                       
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection