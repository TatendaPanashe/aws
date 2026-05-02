@extends('layouts.frontpages')

@section('title')
Login
@endsection

@section('content')
<div class="auth-layout">
    <section class="auth-copy">
        <div class="hero-spotlight">Secure Access</div>
        <h1>Enter the operations workspace.</h1>
        <p>
            Sign in to manage daily collections, monitor face value stock, review branch activity, and keep reporting aligned across your network.
        </p>

        <div class="auth-list">
            <div class="auth-list-item">
                <i class="bi bi-shield-lock"></i>
                <div>
                    <strong>Controlled access</strong><br>
                    <small>Role-aware navigation for clerks, supervisors, managers, admins, and super users.</small>
                </div>
            </div>
            <div class="auth-list-item">
                <i class="bi bi-diagram-3"></i>
                <div>
                    <strong>Branch visibility</strong><br>
                    <small>Move from branch-level capture to network-level reporting without switching tools.</small>
                </div>
            </div>
            <div class="auth-list-item">
                <i class="bi bi-graph-up-arrow"></i>
                <div>
                    <strong>Operational insight</strong><br>
                    <small>Use live dashboards and structured reports instead of static spreadsheets.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <h2>Login to GRUMA</h2>
        <p class="auth-subtitle">Use your assigned email and password to open the workspace.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                @error('password')
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                </div>

                @if (Route::has('password.request'))
                    <a class="text-decoration-none" href="{{ route('password.request') }}">
                        {{ __('Forgot Your Password?') }}
                    </a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-100">{{ __('Login') }}</button>
        </form>
    </section>
</div>
@endsection
