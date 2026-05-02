<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>@yield('title', 'GRUMA')</title>
  <meta name="description" content="GRUMA operations workspace for collections, face values, reporting, and controls.">
  <meta name="theme-color" content="#102d36">

  <link href="{{asset('assets/img/favicon.png')}}" rel="icon">
  <link href="{{asset('assets/img/apple-touch-icon.png')}}" rel="apple-touch-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="{{asset('assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{asset('assets/css/gruma-ui.css')}}" rel="stylesheet">
  @stack('styles')
</head>

<body class="front-body">
  <div class="front-shell">
    <header class="front-topbar">
      <div class="container">
        <div class="front-nav d-flex align-items-center justify-content-between flex-wrap">
          <a href="{{ route('/') }}" class="front-brand text-decoration-none">
            <img src="{{asset('assets/img/gruma.png')}}" alt="GRUMA logo">
            <div>
              <div class="front-brand-mark">GRUMA</div>
              <small>Operations Intelligence Workspace</small>
            </div>
          </a>

          <div class="front-nav-links">
            <span class="soft-chip"><i class="bi bi-shield-check"></i> Structured controls</span>
            <span class="soft-chip"><i class="bi bi-graph-up-arrow"></i> Real-time reporting</span>
            <a href="{{ route('login') }}" class="btn btn-primary">Secure Login</a>
          </div>
        </div>
      </div>
    </header>

    <main class="front-main">
      <div class="container">
        @yield('content')
      </div>
    </main>

    <footer class="front-footer">
      <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <strong>Global Risk Underwriting Managers</strong><br>
          <small>Collections, face value control, reporting, and operational oversight.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span class="soft-chip"><i class="bi bi-envelope"></i> info@gruma.co.zw</span>
          <span class="soft-chip"><i class="bi bi-telephone"></i> +263 242 708 401-05</span>
        </div>
      </div>
    </footer>
  </div>

  <script src="{{asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{asset('assets/js/gruma-ui.js')}}"></script>
  @stack('scripts')
</body>

</html>
