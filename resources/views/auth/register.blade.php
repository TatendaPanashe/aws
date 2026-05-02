@extends('layouts.frontpages')

@section('title')
Register
@endsection

@section('content')
<div class="auth-layout">
    <section class="auth-copy">
        <div class="hero-spotlight">Workspace Provisioning</div>
        <h1>Create a managed user profile.</h1>
        <p>
            Register a user with branch and network context so the workspace can route collections, permissions, and reporting correctly.
        </p>

        <div class="auth-list">
            <div class="auth-list-item">
                <i class="bi bi-people"></i>
                <div>
                    <strong>User roles</strong><br>
                    <small>Set up identities that align with actual branch responsibilities.</small>
                </div>
            </div>
            <div class="auth-list-item">
                <i class="bi bi-buildings"></i>
                <div>
                    <strong>Network and site mapping</strong><br>
                    <small>Attach users to the right operational context from day one.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <h2>Create Access</h2>
        <p class="auth-subtitle">Register the user details below.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="surname" class="form-label">{{ __('Surname') }}</label>
                <input id="surname" type="text" class="form-control @error('surname') is-invalid @enderror" name="surname" value="{{ old('surname') }}" required autocomplete="family-name">
                @error('surname')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="networkid" class="form-label">{{ __('Network Name') }}</label>
                    <input id="networkid" type="text" class="form-control @error('networkid') is-invalid @enderror" name="networkid" value="{{ old('networkid') }}" required>
                    @error('networkid')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="siteid" class="form-label">{{ __('Site Name') }}</label>
                    <input id="siteid" type="text" class="form-control @error('siteid') is-invalid @enderror" name="siteid" value="{{ old('siteid') }}" required>
                    @error('siteid')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">{{ __('Role') }}</label>
                <input id="role" type="text" class="form-control @error('role') is-invalid @enderror" name="role" value="admin" readonly required>
                @error('role')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="col-md-6 mb-4">
                    <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">{{ __('Register') }}</button>
        </form>
    </section>
</div>
@endsection
