@extends('layouts.main')

@section('title')
Change Password
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Change Password') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('passchange') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Current Password') }}</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">{{ __('New Password') }}</label>
                            <input id="confirm_password" type="password" 
                                   class="form-control @error('confirm_password') is-invalid @enderror" 
                                   name="confirm_password" required autocomplete="new-password">
                            @error('confirm_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
                            <input id="confirm_password_confirmation" type="password" class="form-control" 
                                   name="confirm_password_confirmation" required autocomplete="new-password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Update Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection