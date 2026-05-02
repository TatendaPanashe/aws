@extends('layouts.main')

@section('title', 'Users Management')

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
@endphp

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="mb-1">Users Management</h1>
                    @if($isZINARASupervisor)
                        <div class="muted">Managing ZINARA Clerks under your supervision</div>
                    @elseif($isZINARAClerk)
                        <div class="muted">Your account information</div>
                    @else
                        <div class="muted">Manage system users and their roles</div>
                    @endif
                </div>
                @if($isZINARASupervisor)
                    <a href="{{ route('teams.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Create ZINARA Clerk
                    </a>
                @elseif(!$isZINARAClerk)
                    <a href="{{ route('teams.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Add User
                    </a>
                @endif
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($users->isEmpty())
                <div class="empty-state text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <p class="mt-3">No users found.</p>
                    @if($isZINARASupervisor)
                        <p class="text-muted">Click the "Create ZINARA Clerk" button to add clerks under your supervision.</p>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table datatable table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>SBU</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $index => $userItem)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $userItem->name }} {{ $userItem->surname ?? '' }}</strong>
                                        @if($userItem->created_by == Auth::id() && $isZINARASupervisor)
                                            <br>
                                            <small class="text-success">Created by you</small>
                                        @endif
                                    </td>
                                    <td>{{ $userItem->email }}</td>
                                    <td>
                                        @if($userItem->role_id == 6)
                                            <span class="badge bg-primary">ZINARA Supervisor</span>
                                        @elseif($userItem->role_id == 7)
                                            <span class="badge bg-info">ZINARA Clerk</span>
                                        @elseif($userItem->role_id == 2)
                                            <span class="badge bg-secondary">Clerk</span>
                                        @elseif($userItem->role_id == 3)
                                            <span class="badge bg-warning">Supervisor</span>
                                        @elseif($userItem->role_id == 5)
                                            <span class="badge bg-danger">Super User</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $userItem->role->role_name ?? 'Unknown' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($userItem->site)
                                            {{ $userItem->site->site_name }}
                                            @if($userItem->site->sbu == 'SBU3')
                                                <br>
                                                <small class="text-info">(Courier)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $userItem->network->name ?? 'Not assigned' }}</td>
                                    <td>
                                        @if($userItem->site && $userItem->site->sbu == 'SBU3')
                                            <span class="badge bg-info">SBU3 (Courier)</span>
                                        @elseif($userItem->site && $userItem->site->sbu)
                                            <span class="badge bg-secondary">{{ $userItem->site->sbu }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($userItem->is_active ?? true)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Blocked</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('teams.edit', $userItem->id) }}" class="btn btn-warning btn-sm" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="{{ route('teams.resetpwd', $userItem->id) }}" class="btn btn-info btn-sm" title="Reset Password" onclick="return confirm('Reset password for {{ $userItem->name }}?')">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <form action="{{ route('block', $userItem->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $userItem->is_active ?? true ? 'block' : 'unblock' }} this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="{{ ($userItem->is_active ?? true) ? 'Block User' : 'Unblock User' }}">
                                                    <i class="bi bi-{{ ($userItem->is_active ?? true) ? 'person-x' : 'person-check' }}"></i>
                                                </button>
                                            </form>
                                            @if($isZINARASupervisor && $userItem->role_id == 7)
                                                <a href="{{ route('facevalues.reports.trace') }}?clerk_id={{ $userItem->id }}" class="btn btn-primary btn-sm" title="View Face Value History">
                                                    <i class="bi bi-upc-scan"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($isZINARASupervisor && $users->count() > 0)
                    <div class="mt-3 text-muted">
                        <i class="bi bi-info-circle"></i>
                        <small>ZINARA Clerks: {{ $users->where('role_id', 7)->count() }} active | 
                        Total clerks under supervision: {{ $users->count() }}</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
        cursor: pointer;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .empty-state {
        padding: 60px 20px;
    }
    .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    .muted {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>
@endpush

@endsection