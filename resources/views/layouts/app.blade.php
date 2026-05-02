<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GRUMA') }}</title>
    <link href="{{asset('assets/img/favicon.png')}}" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{asset('assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
    <link href="{{asset('assets/css/gruma-ui.css')}}" rel="stylesheet">
    @stack('styles')
</head>
<body class="auth-body">
    <div class="front-shell">
        <header class="front-topbar">
            <div class="container">
                <div class="front-nav d-flex align-items-center justify-content-between flex-wrap">
                    <a href="{{ url('/') }}" class="front-brand text-decoration-none">
                        <img src="{{asset('assets/img/gruma.png')}}" alt="GRUMA logo">
                        <div>
                            <div class="front-brand-mark">GRUMA</div>
                            <small>Secure operations workspace</small>
                        </div>
                    </a>

                    <div class="front-nav-links">
                        @guest
                            @if (Route::has('login'))
                                <a class="btn btn-secondary" href="{{ route('login') }}">{{ __('Login') }}</a>
                            @endif
                        @else
                            <span class="soft-chip"><i class="bi bi-person-circle"></i> {{ Auth::user()->name }}</span>
                            <a class="btn btn-primary" href="{{ route('home') }}">Open Dashboard</a>
                        @endguest
                    </div>
                </div>
            </div>
        </header>

        <main class="front-main">
            <div class="container">
            @yield('content')
            </div>
        </main>
    </div>

    <script src="{{asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('assets/js/gruma-ui.js')}}"></script>
    @stack('scripts')
</body>
</html>
