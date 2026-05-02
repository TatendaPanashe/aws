@php
  $user = Auth::user();
  $site = $user->site;
  $roleLabels = [
      1 => 'Admin',
      2 => 'Clerk',
      3 => 'Supervisor',
      4 => 'Manager',
      5 => 'Super User',
  ];
  $roleLabel = $roleLabels[$user->role_id] ?? 'Workspace User';
  $routeName = request()->route()?->getName();
  $pageLabel = $routeName
      ? str($routeName)->replace(['.', '-'], ' ')->title()
      : 'Operations Workspace';
@endphp

<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="workspace-header__intro">
    <a href="{{ route('home') }}" class="logo d-flex align-items-center text-decoration-none">
      <img src="{{asset('assets/img/gruma.png')}}" alt="GRUMA logo">
      <span>GRUMA</span>
    </a>

    <i class="bi bi-list toggle-sidebar-btn"></i>

    <div class="workspace-context">
      <span class="workspace-kicker">Command Center <strong>{{ $roleLabel }}</strong></span>
      <h1 class="workspace-title">{{ $pageLabel }}</h1>
      <p class="workspace-subtitle">
        {{ $site?->site_name ?? 'No site assigned' }}
        @if($site?->code)
          • {{ $site->code }}
        @endif
      </p>
    </div>
  </div>

  <div class="workspace-meta">
    <div class="header-stat">
      <span>Today</span>
      <strong>{{ now()->format('D, d M Y') }}</strong>
    </div>

    <div class="header-stat">
      <span>Role</span>
      <strong>{{ $roleLabel }}</strong>
    </div>

    @if($user->network)
      <span class="workspace-chip">
        <i class="bi bi-diagram-3"></i>
        {{ $user->network->name }}
      </span>
    @endif

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center mb-0">
        <li class="nav-item dropdown pe-1">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <span class="d-none d-md-block dropdown-toggle ps-2">{{ $user->name }}</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>{{ $user->name }}</h6>
              <span>{{ $user->email }}</span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('reset') }}">
                <i class="bi bi-key"></i>
                <span>Change Password</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>

              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </div>
</header>
