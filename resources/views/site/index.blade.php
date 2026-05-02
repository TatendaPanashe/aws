@extends('layouts.main')

@section('title', 'All Sites')

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
@endphp

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="card-title mb-1">All Sites</h5>
                            @if($isZINARAUser)
                                <div class="muted">Showing Courier/ZINARA sites (SBU3) only</div>
                            @endif
                        </div>
                        <a href="{{ route('getsite') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Site
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($sites->isEmpty())
                        <div class="empty-state text-center py-5">
                            <i class="bi bi-building fs-1 text-muted"></i>
                            <p class="mt-3">No sites found.</p>
                            @if($isZINARAUser)
                                <p class="text-muted">Create a site under SBU3 (Courier) to get started.</p>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table datatable table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">Site Name</th>
                                        <th scope="col">Network</th>
                                        <th scope="col">Code</th>
                                        <th scope="col">Site Description</th>
                                        <th scope="col">Supervised By</th>
                                        <th scope="col">POS ID</th>
                                        <th scope="col">Bank</th>
                                        <th scope="col">SBU</th>
                                        @if($isZINARASupervisor)
                                            <th scope="col">ZINARA Clerks</th>
                                        @endif
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sites as $site)
                                    <tr>
                                        <td>{{ $site->site_name }}</td>
                                        <td>{{ $site->network->name ?? 'N/A' }}</td>
                                        <td>{{ $site->code ?? 'N/A' }}</td>
                                        <td>{{ Str::limit($site->site_description, 50) ?? 'N/A' }}</td>
                                        <td>{{ $site->user->name ?? 'Unassigned' }}</td>
                                        <td>{{ $site->POS ?? 'N/A' }}</td>
                                        <td>{{ $site->bank ?? 'N/A' }}</td>
                                        <td>
                                            @if($site->sbu == 'SBU3')
                                                <span class="badge bg-info">SBU3 (Courier)</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $site->sbu ?? 'N/A' }}</span>
                                            @endif
                                        </td>
                                        @if($isZINARASupervisor)
                                            <td>
                                                @php
                                                    // Get ZINARA clerks assigned to this site
                                                    $zinaraClerks = App\Models\User::where('siteid', $site->id)
                                                        ->where('role_id', 7)
                                                        ->where('created_by', Auth::id())
                                                        ->get();
                                                @endphp
                                                
                                                @if($zinaraClerks->count() > 0)
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-people"></i> {{ $zinaraClerks->count() }} Clerk(s)
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @foreach($zinaraClerks as $clerk)
                                                                <li>
                                                                    <a class="dropdown-item" href="#">
                                                                        <i class="bi bi-person"></i> 
                                                                        {{ $clerk->name }} {{ $clerk->surname }}
                                                                        <br>
                                                                        <small class="text-muted">{{ $clerk->email }}</small>
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="bi bi-person-x"></i> No clerks assigned
                                                    </span>
                                                    <a href="{{ route('teams.create') }}?site_id={{ $site->id }}" class="btn btn-sm btn-success mt-1">
                                                        <i class="bi bi-plus"></i> Add Clerk
                                                    </a>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('showsite', $site) }}" class="btn btn-primary btn-sm" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('editsite', $site) }}" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('destroysite') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $site->id }}">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this site?')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@if($isZINARASupervisor)
<script>
    // Optional: Add any JavaScript for additional functionality
    document.addEventListener('DOMContentLoaded', function() {
        // You can add tooltips or other enhancements here
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endif

@endsection