<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Municipal Transport System' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <span class="brand-mark"><i class="bi bi-signpost-2-fill"></i></span>
                <div><strong>TRANSIT<br>DESK</strong><small>Municipal Operations</small></div>
            </div>

            @if (auth()->user()->role === 'operator')
                <div class="nav-label">WORKSPACE</div>
                @can('operator portal')
                    <a class="nav-link {{ request()->routeIs('dashboard', 'operator.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('operator.profile') ? 'active' : '' }}" href="{{ route('operator.profile') }}"><i class="bi bi-person-vcard"></i> My operator profile</a>
                    <a class="nav-link {{ request()->routeIs('operator.vehicle') ? 'active' : '' }}" href="{{ route('operator.vehicle') }}"><i class="bi bi-truck"></i> My vehicle</a>
                @endcan
                <div class="nav-label">LOCATION</div>
                <a class="nav-link {{ request()->routeIs('operator.live-location') ? 'active' : '' }}" href="{{ route('operator.live-location') }}"><i class="bi bi-geo-alt"></i> Live Location</a>
                @can('operator applications')
                    <a class="nav-link {{ request()->routeIs('operator.applications.*') ? 'active' : '' }}" href="{{ route('operator.applications.index') }}"><i class="bi bi-file-earmark-text"></i> Applications</a>
                @endcan
                @can('operator permits')
                    <a class="nav-link {{ request()->routeIs('operator.permits.*') ? 'active' : '' }}" href="{{ route('operator.permits.index') }}"><i class="bi bi-card-checklist"></i> My permits</a>
                @endcan
                @can('operator franchises')
                    <a class="nav-link {{ request()->routeIs('operator.franchises.*') ? 'active' : '' }}" href="{{ route('operator.franchises.index') }}"><i class="bi bi-award"></i> My franchise</a>
                @endcan
                @can('operator renewals')
                    <a class="nav-link {{ request()->routeIs('operator.renewals.*') ? 'active' : '' }}" href="{{ route('operator.renewals.index') }}"><i class="bi bi-arrow-repeat"></i> Renewals</a>
                @endcan
                @can('operator violations')
                    <a class="nav-link {{ request()->routeIs('operator.violations.*') ? 'active' : '' }}" href="{{ route('operator.violations.index') }}"><i class="bi bi-exclamation-triangle"></i> My violations</a>
                @endcan
                @can('operator notifications')
                    <div class="nav-label">ACCOUNT</div>
                    <a class="nav-link {{ request()->routeIs('operator.notifications.index') ? 'active' : '' }}" href="{{ route('operator.notifications.index') }}"><i class="bi bi-bell"></i> Notifications</a>
                @endcan
                <a class="nav-link {{ request()->routeIs('profile.*', 'operator.profile') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My profile</a>
            @elseif (auth()->user()->role === 'vehicle_owner')
                <div class="nav-label">WORKSPACE</div>
                @canany(['vehicle owner portal', 'view dashboard'])
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.profile') ? 'active' : '' }}" href="{{ route('vehicle-owner.profile') }}"><i class="bi bi-person-vcard"></i> My profile</a>
                @endcanany
                @canany(['vehicle owner vehicles', 'view vehicles'])
                    <div class="nav-label">VEHICLES</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.index', 'vehicle-owner.vehicles.show') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.index') }}"><i class="bi bi-truck"></i> My Vehicles</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.location') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.index') }}"><i class="bi bi-geo-alt"></i> My Vehicle Location</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.create') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.create') }}"><i class="bi bi-plus-circle"></i> Register Vehicle</a>
                @endcanany
                @canany(['vehicle owner applications', 'view applications'])
                    <div class="nav-label">APPLICATIONS</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.applications.index', 'vehicle-owner.applications.show') ? 'active' : '' }}" href="{{ route('vehicle-owner.applications.index') }}"><i class="bi bi-file-earmark-text"></i> My Applications</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.applications.create') ? 'active' : '' }}" href="{{ route('vehicle-owner.applications.create') }}"><i class="bi bi-file-earmark-plus"></i> New Application</a>
                @endcanany
                @canany(['vehicle owner permits', 'view permits'])
                    <div class="nav-label">PERMITS & FRANCHISE</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.permits.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.permits.index') }}"><i class="bi bi-card-checklist"></i> My Permits</a>
                @endcanany
                @canany(['vehicle owner franchises', 'view franchises'])
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.franchises.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.franchises.index') }}"><i class="bi bi-award"></i> My Franchise</a>
                @endcanany
                @canany(['vehicle owner renewals', 'view renewals'])
                    <div class="nav-label">COMPLIANCE</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.renewals.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.renewals.index') }}"><i class="bi bi-arrow-repeat"></i> Renewals</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.violations.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.violations.index') }}"><i class="bi bi-exclamation-triangle"></i> Violations</a>
                @endcanany
                @canany(['vehicle owner notifications', 'view notifications'])
                    <div class="nav-label">ACCOUNT</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.notifications.index') ? 'active' : '' }}" href="{{ route('vehicle-owner.notifications.index') }}"><i class="bi bi-bell"></i> Notifications</a>
                @endcanany
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My profile</a>
            @else
            <div class="nav-label">WORKSPACE</div>
            @can('view dashboard')
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            @endcan
            @can('view applications')
                <a class="nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}"><i class="bi bi-file-earmark-text"></i> Applications</a>
            @endcan
            @if (in_array(auth()->user()->role, ['admin', 'staff'], true) && auth()->user()->can('view vehicles'))
                <a class="nav-link {{ request()->routeIs('live-map.index') ? 'active' : '' }}" href="{{ route('live-map.index') }}"><i class="bi bi-geo-alt"></i> Live Transport Map</a>
            @endif

            @can('view reports')
                <div class="nav-label">REPORTING</div>
                <a class="nav-link {{ request()->routeIs('reports.index', 'reports.show') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
            @endcan
            @can('view my reports')
                <a class="nav-link {{ request()->routeIs('reports.create') ? 'active' : '' }}" href="{{ route('reports.create') }}"><i class="bi bi-geo-alt"></i> Create report</a>
                <a class="nav-link {{ request()->routeIs('reports.mine') ? 'active' : '' }}" href="{{ route('reports.mine') }}"><i class="bi bi-person-lines-fill"></i> My reports</a>
            @endcan

            @canany(['view operators', 'view vehicles', 'view franchises', 'view permits', 'view renewals', 'view violations'])
                <div class="nav-label">TRANSPORT INFORMATION</div>
            @endcanany
            @can('view operators')
                <a class="nav-link {{ request()->routeIs('operators.*') ? 'active' : '' }}" href="{{ route('operators.index') }}"><i class="bi bi-person-vcard"></i> Operators</a>
            @endcan
            @can('view vehicles')
                <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><i class="bi bi-truck"></i> Vehicles</a>
            @endcan
            @can('view franchises')
                <a class="nav-link {{ request()->routeIs('franchises.*') ? 'active' : '' }}" href="{{ route('franchises.index') }}"><i class="bi bi-award"></i> Franchises</a>
            @endcan
            @can('view permits')
                <a class="nav-link {{ request()->routeIs('permits.*') ? 'active' : '' }}" href="{{ route('permits.index') }}"><i class="bi bi-card-checklist"></i> Permits</a>
            @endcan
            @can('view renewals')
                <a class="nav-link {{ request()->routeIs('renewals.*') ? 'active' : '' }}" href="{{ route('renewals.index') }}"><i class="bi bi-arrow-repeat"></i> Renewals</a>
            @endcan
            @can('view violations')
                <a class="nav-link {{ request()->routeIs('violations.*') ? 'active' : '' }}" href="{{ route('violations.index') }}"><i class="bi bi-exclamation-triangle"></i> Violations</a>
            @endcan

            <div class="nav-label">ACCOUNT</div>
            @can('view notifications')
                <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i> Notifications</a>
            @endcan
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i class="bi bi-person-circle"></i> My profile</a>

            @can('manage users')
                <div class="nav-label">ADMINISTRATION</div>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people"></i> User management</a>
                <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}"><i class="bi bi-key"></i> Permissions</a>
                <span class="nav-link disabled"><i class="bi bi-clock-history"></i> Audit Logs <span class="soon">soon</span></span>
            @endcan
            @endif
        </aside>

        <main class="main">
            <header class="topbar">
                <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
                <div class="breadcrumb">Municipal Transport Office <span>/</span> {{ $title ?? 'Dashboard' }}</div>
                <div class="top-actions">
                    <span class="role-pill">{{ strtoupper(auth()->user()->roleLabel()) }}</span>
                    <a href="{{ route('profile.show') }}" class="user-menu text-decoration-none text-dark">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="icon-btn" title="Log out"><i class="bi bi-box-arrow-right"></i></button>
                    </form>
                </div>
            </header>

            <section class="content">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </section>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
