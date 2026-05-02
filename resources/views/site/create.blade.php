@extends('layouts.main')

@section('title', 'Create Site')

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    
    // Get the SBU3 network ID for auto-selection
    $sbu3Network = null;
    if ($isZINARAUser && isset($networks)) {
        $sbu3Network = $networks->firstWhere('name', 'SBU3');
    }
@endphp

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Create Site</h5>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form class="row g-3" action="{{ route('postsite') }}" method="post">
            @csrf
            
            {{-- Network field - Hidden for ZINARA users, visible for others --}}
            @if(!$isZINARAUser)
                <div class="col-md-12">
                    <label for="network_id" class="form-label">Network</label>
                    <select type="text" required name="network_id" class="form-control" id="network_id">
                        <option value="">Select Network</option>
                        @foreach($networks as $network)
                            <option value="{{ $network->id }}" {{ old('network_id') == $network->id ? 'selected' : '' }}>
                                {{ $network->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('network_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            @else
                {{-- Hidden input for ZINARA users --}}
                @if($sbu3Network)
                    <input type="hidden" name="network_id" value="{{ $sbu3Network->id }}">
                @endif
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>ZINARA Mode:</strong> Sites will be automatically created under the Courier network (SBU3).
                </div>
            @endif
            
            <div class="col-md-12">
                <label for="site_name" class="form-label">Name of Site</label>
                <input type="text" name="site_name" class="form-control" id="site_name" placeholder="Site name" value="{{ old('site_name') }}" required>
                @error('site_name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="site_description" class="form-label">Site Description</label>
                <textarea type="text" name="site_description" class="form-control" id="site_description" placeholder="Site description" rows="3">{{ old('site_description') }}</textarea>
                @error('site_description')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="code_name" class="form-label">Code Name</label>
                <input type="text" name="code_name" class="form-control" id="code_name" placeholder="Code name" value="{{ old('code_name') }}">
                @error('code_name')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="code" class="form-label">Code</label>
                <input type="text" name="code" class="form-control" id="code" placeholder="Site code" value="{{ old('code') }}">
                @error('code')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="POS" class="form-label">POS Number</label>
                <input type="text" name="POS" class="form-control" id="POS" placeholder="POS Number" value="{{ old('POS') }}">
                @error('POS')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="bank" class="form-label">Bank</label>
                <input type="text" name="bank" class="form-control" id="bank" placeholder="Bank" value="{{ old('bank') }}">
                @error('bank')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12">
                <label for="sbu" class="form-label">SBU</label>
                @if(!$isZINARAUser)
                    <select type="text" required name="sbu" class="form-control" id="sbu">
                        <option value="">Select SBU</option>
                        <option value="SBU1" {{ old('sbu') == 'SBU1' ? 'selected' : '' }}>SBU1</option>
                        <option value="SBU2" {{ old('sbu') == 'SBU2' ? 'selected' : '' }}>SBU2</option>
                        <option value="SBU3" {{ old('sbu') == 'SBU3' ? 'selected' : '' }}>SBU3 (Courier)</option>
                    </select>
                @else
                    <input type="text" class="form-control" value="SBU3 (Courier) - Auto-selected for ZINARA" disabled>
                    <input type="hidden" name="sbu" value="SBU3">
                    <small class="text-muted">SBU is automatically set to SBU3 (Courier) for ZINARA users.</small>
                @endif
                @error('sbu')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
                <a href="{{ route('sites') }}" class="btn btn-info">View All Sites</a>
            </div>
        </form>
    </div>
</div>

@if($isZINARAUser)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('ZINARA mode active - Network and SBU are auto-configured');
    });
</script>
@endif

@endsection