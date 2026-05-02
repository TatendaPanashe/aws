@extends('layouts.main')

@section('title', 'Create User')

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    
    // Get SBU3 network for auto-selection
    $sbu3Network = null;
    if ($isZINARAUser && isset($networks)) {
        $sbu3Network = $networks->firstWhere('name', 'SBU3');
    }
@endphp

<div class="card">
    <div class="card-body">
        <h1 class="mb-4">Create User</h1>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if($isZINARAUser)
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>ZINARA Mode:</strong> You can only create ZINARA Clerk accounts. Network and Site will be auto-filtered to Courier (SBU3) sites.
            </div>
        @endif

        <form action="{{ route('teams.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 col-md-6">
                    <label for="surname" class="form-label">Surname</label>
                    <input type="text" class="form-control @error('surname') is-invalid @enderror" name="surname" id="surname" value="{{ old('surname') }}">
                    @error('surname')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 col-md-12">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control @error('role') is-invalid @enderror" name="role" id="role" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            @if($isZINARAUser)
                                {{-- ZINARA users can only create ZINARA Clerk (role_id = 7) --}}
                                @if($role->id == 7)
                                    <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->role_name }}
                                    </option>
                                @endif
                            @elseif($isZINARASupervisor)
                                {{-- ZINARA Supervisor can only create ZINARA Clerk --}}
                                @if($role->id == 7)
                                    <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->role_name }}
                                    </option>
                                @endif
                            @else
                                <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                    {{ $role->role_name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('role')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="networkid" class="form-label">Network</label>
                    @if($isZINARAUser && $sbu3Network)
                        {{-- ZINARA users see disabled network field with SBU3 pre-selected --}}
                        <input type="text" class="form-control" value="{{ $sbu3Network->name }} (Courier Network)" disabled>
                        <input type="hidden" name="networkid" value="{{ $sbu3Network->id }}">
                        <small class="text-muted">Network is automatically set to Courier (SBU3) for ZINARA users.</small>
                    @else
                        <select class="form-control @error('networkid') is-invalid @enderror" id="networkSelect" onchange="getSites()" name="networkid" required>
                            <option value="">Select Network</option>
                            @foreach($networks as $network)
                                <option value="{{ $network->id }}" {{ old('networkid') == $network->id ? 'selected' : '' }}>
                                    {{ $network->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('networkid')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="siteid" class="form-label">Site</label>
                    <select class="form-control @error('siteid') is-invalid @enderror" id="sitelist" name="siteid" required>
                        <option value="">Select Site</option>
                        @if($isZINARAUser && $sbu3Network)
                            {{-- For ZINARA users, show only SBU3 sites --}}
                            @foreach($sites as $site)
                                @if($site->sbu == 'SBU3' || $site->network_id == $sbu3Network->id)
                                    <option value="{{ $site->id }}" {{ old('siteid') == $site->id ? 'selected' : '' }}>
                                        {{ $site->site_name }} @if($site->sbu) ({{ $site->sbu }}) @endif
                                    </option>
                                @endif
                            @endforeach
                        @else
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ old('siteid') == $site->id ? 'selected' : '' }}>
                                    {{ $site->site_name }} @if($site->sbu) ({{ $site->sbu }}) @endif
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('siteid')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="At least 8 characters, 1 uppercase letter and 1 number" required>
                    @error('password')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Confirm your password" required>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <a href="{{ route('teams.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    function getSites() {
        var networkId = document.getElementById('networkSelect').value;
        
        $("#sitelist").empty();
        $("#sitelist").append('<option value="">Loading sites...</option>');
        
        if (networkId) {
            $.ajax({
                method: 'GET',
                url: '/teams/getsites/' + networkId,
                success: function(data) {
                    $("#sitelist").empty();
                    $("#sitelist").append('<option value="">Select Site</option>');
                    
                    if (data.length > 0) {
                        $.each(data, function(i, item) {
                            $("#sitelist").append('<option value="' + item.id + '">' + item.site_name + '</option>');
                        });
                    } else {
                        $("#sitelist").append('<option value="">No sites available for this network</option>');
                    }
                },
                error: function() {
                    $("#sitelist").empty();
                    $("#sitelist").append('<option value="">Error loading sites</option>');
                }
            });
        } else {
            $("#sitelist").empty();
            $("#sitelist").append('<option value="">Select Network First</option>');
        }
    }
    
    // Preserve selected site if editing
    @if(old('siteid'))
    $(document).ready(function() {
        var selectedSite = "{{ old('siteid') }}";
        if(selectedSite) {
            setTimeout(function() {
                $('#sitelist').val(selectedSite);
            }, 500);
        }
    });
    @endif
    
    // For ZINARA users, trigger site load on page load if network is pre-selected
    @if($isZINARAUser && $sbu3Network)
    $(document).ready(function() {
        // Sites are already loaded in the select for ZINARA users
        console.log('ZINARA mode active - Sites filtered to SBU3 only');
    });
    @endif
</script>

@endsection