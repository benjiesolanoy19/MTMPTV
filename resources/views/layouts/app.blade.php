<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Municipal Transport System' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/portal-theme.css') }}" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <script>
        try {
            if (window.localStorage.getItem('portal-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-preference-collapsed');
            }
        } catch (error) {
            console.warn('Sidebar preference could not be restored.', error);
        }
    </script>
</head>
<body>
    <div class="shell flex min-h-screen">
        <aside class="sidebar" id="sidebar" aria-label="Main navigation">
            <div class="brand">
                <span class="brand-mark"><i data-lucide="signpost-big" class="" aria-hidden="true"></i></span>
                <div><strong>TRANSIT<br>DESK</strong><small>Municipal Operations</small></div>
                <button class="sidebar-collapse" id="sidebar-collapse" type="button" aria-label="Collapse sidebar" title="Collapse sidebar" aria-expanded="true"><i data-lucide="panel-left-close" class="" aria-hidden="true"></i></button>
            </div>

            @if (auth()->user()->role === 'admin')
                <div class="nav-label">ADMINISTRATOR</div>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard" aria-hidden="true"></i> Dashboard</a>
                <div class="nav-label">USER MANAGEMENT</div>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i data-lucide="users-round" aria-hidden="true"></i> Users</a>
                <a class="nav-link {{ request()->routeIs('administrator-applications.*') ? 'active' : '' }}" href="{{ route('administrator-applications.index') }}"><i data-lucide="shield-check" aria-hidden="true"></i> Administrator applications</a>
                <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}"><i data-lucide="key-round" aria-hidden="true"></i> Roles / permissions</a>
                <div class="nav-label">REPORT MANAGEMENT</div>
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i data-lucide="chart-no-axes-column-increasing" aria-hidden="true"></i> All reports</a>
                <a class="nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}"><i data-lucide="file-text" aria-hidden="true"></i> Transport applications</a>
                <div class="nav-label">TRANSPORT RECORDS</div>
                <a class="nav-link {{ request()->routeIs('operators.*') ? 'active' : '' }}" href="{{ route('operators.index') }}"><i data-lucide="contact" aria-hidden="true"></i> Operators</a>
                <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><i data-lucide="truck" aria-hidden="true"></i> Vehicles</a>
                <a class="nav-link {{ request()->routeIs('franchises.*') ? 'active' : '' }}" href="{{ route('franchises.index') }}"><i data-lucide="award" aria-hidden="true"></i> Franchises</a>
                <a class="nav-link {{ request()->routeIs('permits.*') ? 'active' : '' }}" href="{{ route('permits.index') }}"><i data-lucide="clipboard-check" aria-hidden="true"></i> Permits</a>
                <a class="nav-link {{ request()->routeIs('renewals.*') ? 'active' : '' }}" href="{{ route('renewals.index') }}"><i data-lucide="refresh-cw" aria-hidden="true"></i> Renewals</a>
                <a class="nav-link {{ request()->routeIs('violations.*') ? 'active' : '' }}" href="{{ route('violations.index') }}"><i data-lucide="triangle-alert" aria-hidden="true"></i> Violations</a>
                <div class="nav-label">SYSTEM MANAGEMENT</div>
                <a class="nav-link {{ request()->routeIs('system-activity.*') ? 'active' : '' }}" href="{{ route('system-activity.index') }}"><i data-lucide="history" aria-hidden="true"></i> System activity</a>
                <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i data-lucide="bell" aria-hidden="true"></i> Notifications</a>
                <div class="nav-label">ACCOUNT</div>
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i data-lucide="circle-user-round" aria-hidden="true"></i> My profile</a>
            @elseif (auth()->user()->role === 'operator')
                <div class="nav-label">WORKSPACE</div>
                @can('operator portal')
                    <a class="nav-link {{ request()->routeIs('dashboard', 'operator.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard" class="" aria-hidden="true"></i> Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('operator.profile') ? 'active' : '' }}" href="{{ route('operator.profile') }}"><i data-lucide="contact" class="" aria-hidden="true"></i> My operator profile</a>
                    <a class="nav-link {{ request()->routeIs('operator.vehicle') ? 'active' : '' }}" href="{{ route('operator.vehicle') }}"><i data-lucide="truck" class="" aria-hidden="true"></i> My vehicle</a>
                @endcan
                <div class="nav-label">LOCATION</div>
                <a class="nav-link {{ request()->routeIs('operator.live-location') ? 'active' : '' }}" href="{{ route('operator.live-location') }}"><i data-lucide="map-pin" class="" aria-hidden="true"></i> Live Location</a>
                @can('operator applications')
                    <a class="nav-link {{ request()->routeIs('operator.applications.*') ? 'active' : '' }}" href="{{ route('operator.applications.index') }}"><i data-lucide="file-text" class="" aria-hidden="true"></i> Applications</a>
                @endcan
                @can('operator permits')
                    <a class="nav-link {{ request()->routeIs('operator.permits.*') ? 'active' : '' }}" href="{{ route('operator.permits.index') }}"><i data-lucide="clipboard-check" class="" aria-hidden="true"></i> My permits</a>
                @endcan
                @can('operator franchises')
                    <a class="nav-link {{ request()->routeIs('operator.franchises.*') ? 'active' : '' }}" href="{{ route('operator.franchises.index') }}"><i data-lucide="award" class="" aria-hidden="true"></i> My franchise</a>
                @endcan
                @can('operator renewals')
                    <a class="nav-link {{ request()->routeIs('operator.renewals.*') ? 'active' : '' }}" href="{{ route('operator.renewals.index') }}"><i data-lucide="refresh-cw" class="" aria-hidden="true"></i> Renewals</a>
                @endcan
                @can('operator violations')
                    <a class="nav-link {{ request()->routeIs('operator.violations.*') ? 'active' : '' }}" href="{{ route('operator.violations.index') }}"><i data-lucide="triangle-alert" class="" aria-hidden="true"></i> My violations</a>
                @endcan
                @can('operator notifications')
                    <div class="nav-label">ACCOUNT</div>
                    <a class="nav-link {{ request()->routeIs('operator.notifications.index') ? 'active' : '' }}" href="{{ route('operator.notifications.index') }}"><i data-lucide="bell" class="" aria-hidden="true"></i> Notifications</a>
                @endcan
                <a class="nav-link {{ request()->routeIs('administrator-application.*') ? 'active' : '' }}" href="{{ route('administrator-application.create') }}"><i data-lucide="shield-plus" aria-hidden="true"></i> Apply as Administrator</a>
                <a class="nav-link {{ request()->routeIs('profile.*', 'operator.profile') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i data-lucide="circle-user-round" class="" aria-hidden="true"></i> My profile</a>
            @elseif (auth()->user()->role === 'vehicle_owner')
                <div class="nav-label">WORKSPACE</div>
                @canany(['vehicle owner portal', 'view dashboard'])
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard" class="" aria-hidden="true"></i> Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.profile') ? 'active' : '' }}" href="{{ route('vehicle-owner.profile') }}"><i data-lucide="contact" class="" aria-hidden="true"></i> My profile</a>
                @endcanany
                @canany(['vehicle owner vehicles', 'view vehicles'])
                    <div class="nav-label">VEHICLES</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.index', 'vehicle-owner.vehicles.show') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.index') }}"><i data-lucide="truck" class="" aria-hidden="true"></i> My Vehicles</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.location') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.index') }}"><i data-lucide="map-pin" class="" aria-hidden="true"></i> My Vehicle Location</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.vehicles.create') ? 'active' : '' }}" href="{{ route('vehicle-owner.vehicles.create') }}"><i data-lucide="circle-plus" class="" aria-hidden="true"></i> Register Vehicle</a>
                @endcanany
                @canany(['vehicle owner applications', 'view applications'])
                    <div class="nav-label">APPLICATIONS</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.applications.index', 'vehicle-owner.applications.show') ? 'active' : '' }}" href="{{ route('vehicle-owner.applications.index') }}"><i data-lucide="file-text" class="" aria-hidden="true"></i> My Applications</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.applications.create') ? 'active' : '' }}" href="{{ route('vehicle-owner.applications.create') }}"><i data-lucide="file-plus-2" class="" aria-hidden="true"></i> New Application</a>
                @endcanany
                @canany(['vehicle owner permits', 'view permits'])
                    <div class="nav-label">PERMITS & FRANCHISE</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.permits.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.permits.index') }}"><i data-lucide="clipboard-check" class="" aria-hidden="true"></i> My Permits</a>
                @endcanany
                @canany(['vehicle owner franchises', 'view franchises'])
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.franchises.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.franchises.index') }}"><i data-lucide="award" class="" aria-hidden="true"></i> My Franchise</a>
                @endcanany
                @canany(['vehicle owner renewals', 'view renewals'])
                    <div class="nav-label">COMPLIANCE</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.renewals.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.renewals.index') }}"><i data-lucide="refresh-cw" class="" aria-hidden="true"></i> Renewals</a>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.violations.*') ? 'active' : '' }}" href="{{ route('vehicle-owner.violations.index') }}"><i data-lucide="triangle-alert" class="" aria-hidden="true"></i> Violations</a>
                @endcanany
                @canany(['vehicle owner notifications', 'view notifications'])
                    <div class="nav-label">ACCOUNT</div>
                    <a class="nav-link {{ request()->routeIs('vehicle-owner.notifications.index') ? 'active' : '' }}" href="{{ route('vehicle-owner.notifications.index') }}"><i data-lucide="bell" class="" aria-hidden="true"></i> Notifications</a>
                @endcanany
                <a class="nav-link {{ request()->routeIs('administrator-application.*') ? 'active' : '' }}" href="{{ route('administrator-application.create') }}"><i data-lucide="shield-plus" aria-hidden="true"></i> Apply as Administrator</a>
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i data-lucide="circle-user-round" class="" aria-hidden="true"></i> My profile</a>
            @else
            <div class="nav-label">WORKSPACE</div>
            @can('view dashboard')
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard" class="" aria-hidden="true"></i> Dashboard</a>
            @endcan
            @can('view applications')
                <a class="nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}"><i data-lucide="file-text" class="" aria-hidden="true"></i> Applications</a>
            @endcan
            @if (in_array(auth()->user()->role, ['admin', 'staff'], true) && auth()->user()->can('view vehicles'))
                <a class="nav-link {{ request()->routeIs('live-map.index') ? 'active' : '' }}" href="{{ route('live-map.index') }}"><i data-lucide="map-pin" class="" aria-hidden="true"></i> Live Transport Map</a>
            @endif

            @can('view reports')
                <div class="nav-label">REPORTING</div>
                <a class="nav-link {{ request()->routeIs('reports.index', 'reports.show') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i data-lucide="chart-no-axes-column-increasing" class="" aria-hidden="true"></i> Reports</a>
            @endcan
            @can('view my reports')
                <a class="nav-link {{ request()->routeIs('reports.create') ? 'active' : '' }}" href="{{ route('reports.create') }}"><i data-lucide="map-pin" class="" aria-hidden="true"></i> Create report</a>
                <a class="nav-link {{ request()->routeIs('reports.mine') ? 'active' : '' }}" href="{{ route('reports.mine') }}"><i data-lucide="contact-round" class="" aria-hidden="true"></i> My reports</a>
            @endcan
            <a class="nav-link {{ request()->routeIs('administrator-application.*') ? 'active' : '' }}" href="{{ route('administrator-application.create') }}"><i data-lucide="shield-plus" aria-hidden="true"></i> Apply as Administrator</a>

            @canany(['view operators', 'view vehicles', 'view franchises', 'view permits', 'view renewals', 'view violations'])
                <div class="nav-label">TRANSPORT INFORMATION</div>
            @endcanany
            @can('view operators')
                <a class="nav-link {{ request()->routeIs('operators.*') ? 'active' : '' }}" href="{{ route('operators.index') }}"><i data-lucide="contact" class="" aria-hidden="true"></i> Operators</a>
            @endcan
            @can('view vehicles')
                <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><i data-lucide="truck" class="" aria-hidden="true"></i> Vehicles</a>
            @endcan
            @can('view franchises')
                <a class="nav-link {{ request()->routeIs('franchises.*') ? 'active' : '' }}" href="{{ route('franchises.index') }}"><i data-lucide="award" class="" aria-hidden="true"></i> Franchises</a>
            @endcan
            @can('view permits')
                <a class="nav-link {{ request()->routeIs('permits.*') ? 'active' : '' }}" href="{{ route('permits.index') }}"><i data-lucide="clipboard-check" class="" aria-hidden="true"></i> Permits</a>
            @endcan
            @can('view renewals')
                <a class="nav-link {{ request()->routeIs('renewals.*') ? 'active' : '' }}" href="{{ route('renewals.index') }}"><i data-lucide="refresh-cw" class="" aria-hidden="true"></i> Renewals</a>
            @endcan
            @can('view violations')
                <a class="nav-link {{ request()->routeIs('violations.*') ? 'active' : '' }}" href="{{ route('violations.index') }}"><i data-lucide="triangle-alert" class="" aria-hidden="true"></i> Violations</a>
            @endcan

            <div class="nav-label">ACCOUNT</div>
            @can('view notifications')
                <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i data-lucide="bell" class="" aria-hidden="true"></i> Notifications</a>
            @endcan
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}"><i data-lucide="circle-user-round" class="" aria-hidden="true"></i> My profile</a>

            @can('manage users')
                <div class="nav-label">ADMINISTRATION</div>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i data-lucide="users-round" class="" aria-hidden="true"></i> User management</a>
                <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}"><i data-lucide="key-round" class="" aria-hidden="true"></i> Permissions</a>
                <span class="nav-link disabled"><i data-lucide="history" class="" aria-hidden="true"></i> Audit Logs <span class="soon">soon</span></span>
            @endcan
            @endif
        </aside>

        <main class="main min-w-0 flex-1">
            <header class="topbar">
                <button class="menu-btn" id="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false"><i data-lucide="menu" class="" aria-hidden="true"></i></button>
                <div class="breadcrumb">Municipal Transport Office <span>/</span> {{ $title ?? 'Dashboard' }}</div>
                <div class="top-actions">
                    @if($canViewTopbarNotifications)
                        @php
                            $allNotificationsRoute = match (auth()->user()->role) {
                                'operator' => route('operator.notifications.index'),
                                'vehicle_owner' => route('vehicle-owner.notifications.index'),
                                default => route('notifications.index'),
                            };
                        @endphp
                        <div class="notification-center" id="notification-center">
                            <button class="icon-btn notification-trigger" id="notification-trigger" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notification-panel" title="Notifications">
                                <i data-lucide="bell" aria-hidden="true"></i>
                                <span class="notification-badge" id="notification-badge" @if($topbarUnreadCount < 1) hidden @endif>{{ $topbarUnreadCount > 99 ? '99+' : $topbarUnreadCount }}</span>
                            </button>
                            <section class="notification-panel" id="notification-panel" aria-labelledby="notification-heading" hidden>
                                <header class="notification-panel-head">
                                    <div><h2 id="notification-heading">Notifications</h2><span id="notification-summary">{{ $topbarUnreadCount ? $topbarUnreadCount.' unread' : 'You\'re all caught up' }}</span></div>
                                    <button type="button" class="notification-mark-all" id="notification-mark-all" @if($topbarUnreadCount < 1) disabled @endif>Mark all as read</button>
                                </header>
                                <div class="notification-list" id="notification-list" aria-live="polite">
                                    @forelse($topbarNotifications as $notification)
                                        <article class="notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}" data-notification-id="{{ $notification->id }}" data-read-url="{{ route('notifications.read', $notification, false) }}" data-action-url="{{ $notification->safe_action_url ?? '' }}">
                                            <span class="notification-state" aria-label="{{ $notification->read_at ? 'Read' : 'Unread' }}"></span>
                                            <div class="notification-copy">
                                                <strong>{{ $notification->title }}</strong>
                                                <p>{{ $notification->message }}</p>
                                                <div class="notification-meta"><span class="notification-type">{{ $notification->type }}</span><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time></div>
                                            </div>
                                            @if($notification->safe_action_url)
                                                <a class="notification-open-link" href="{{ $notification->safe_action_url }}" aria-label="Open notification"><i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                                            @else
                                                <button type="button" class="notification-read-button" data-mark-notification aria-label="Mark notification as read" @if($notification->read_at) hidden @endif><i data-lucide="check" aria-hidden="true"></i></button>
                                            @endif
                                        </article>
                                    @empty
                                        <div class="notification-empty"><i data-lucide="bell-ring" aria-hidden="true"></i><strong>You're all caught up</strong><span>No new notifications.</span></div>
                                    @endforelse
                                </div>
                                <footer class="notification-panel-foot"><a href="{{ $allNotificationsRoute }}">View all notifications <i data-lucide="arrow-right" aria-hidden="true"></i></a></footer>
                            </section>
                        </div>
                    @endif
                    <span class="role-pill">{{ strtoupper(auth()->user()->roleLabel()) }}</span>
                    <a href="{{ route('profile.show') }}" class="user-menu text-decoration-none text-dark">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="icon-btn" title="Log out"><i data-lucide="log-out" class="" aria-hidden="true"></i></button>
                    </form>
                </div>
            </header>

            <section class="content mx-auto w-full max-w-screen-2xl">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i data-lucide="circle-check" class="me-2" aria-hidden="true"></i>{{ session('success') }}
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
    <div class="sidebar-scrim" id="sidebar-scrim" aria-hidden="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js" defer></script>
    <script>
        (() => {
            const shell = document.querySelector('.shell');
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            const sidebarScrim = document.getElementById('sidebar-scrim');
            const collapseToggle = document.getElementById('sidebar-collapse');
            const notificationCenter = document.getElementById('notification-center');
            const notificationTrigger = document.getElementById('notification-trigger');
            const notificationPanel = document.getElementById('notification-panel');
            const notificationList = document.getElementById('notification-list');
            const notificationBadge = document.getElementById('notification-badge');
            const notificationSummary = document.getElementById('notification-summary');
            const notificationMarkAll = document.getElementById('notification-mark-all');
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const renderIcons = () => window.lucide?.createIcons({ attrs: { 'stroke-width': 1.8 } });
            const setCollapsedState = (isCollapsed, persist = false) => {
                shell.classList.toggle('sidebar-collapsed', isCollapsed);
                document.documentElement.classList.toggle('sidebar-preference-collapsed', isCollapsed);
                collapseToggle.setAttribute('aria-expanded', String(!isCollapsed));
                collapseToggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
                collapseToggle.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
                replaceIcon(collapseToggle, isCollapsed ? 'panel-left-open' : 'panel-left-close');
                sidebar.querySelectorAll('.nav-link').forEach((link) => {
                    if (isCollapsed) {
                        const label = link.textContent.trim().replace(/\s+soon$/i, '');
                        link.title = label;
                        if (link.matches('a')) link.setAttribute('aria-label', label);
                    } else {
                        link.removeAttribute('title');
                        link.removeAttribute('aria-label');
                    }
                });
                if (persist) {
                    try {
                        window.localStorage.setItem('portal-sidebar-collapsed', String(isCollapsed));
                    } catch (error) {
                        console.warn('Sidebar preference could not be saved.', error);
                    }
                }
            };
            const replaceIcon = (element, name) => {
                const icon = document.createElement('i');
                icon.dataset.lucide = name;
                icon.setAttribute('aria-hidden', 'true');
                element.replaceChildren(icon);
                renderIcons();
            };
            const notificationPreviewUrl = @json(route('notifications.preview'));
            const notificationReadAllUrl = @json(route('notifications.read-all'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const updateNotificationCount = (count) => {
                const unreadCount = Math.max(0, Number(count) || 0);
                notificationBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                notificationBadge.hidden = unreadCount === 0;
                notificationSummary.textContent = unreadCount ? `${unreadCount} unread` : "You're all caught up";
                notificationMarkAll.disabled = unreadCount === 0;
            };
            const markNotificationRead = async (item) => {
                const response = await fetch(item.dataset.readUrl, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                });
                if (!response.ok) throw new Error('Unable to mark notification as read.');
                const data = await response.json();
                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-state')?.setAttribute('aria-label', 'Read');
                const markButton = item.querySelector('[data-mark-notification]');
                if (markButton) markButton.hidden = true;
                updateNotificationCount(data.unread_count);
            };
            const renderNotificationPreview = (data) => {
                notificationList.replaceChildren();
                if (!data.notifications.length) {
                    const empty = document.createElement('div');
                    empty.className = 'notification-empty';
                        const icon = document.createElement('i');
                        icon.dataset.lucide = 'bell-ring';
                        icon.setAttribute('aria-hidden', 'true');
                        const heading = document.createElement('strong');
                        heading.textContent = "You're all caught up";
                        const message = document.createElement('span');
                        message.textContent = 'No new notifications.';
                        empty.append(icon, heading, message);
                        notificationList.append(empty);
                } else {
                    data.notifications.forEach((notification) => {
                        const item = document.createElement('article');
                        item.className = `notification-item ${notification.read_at ? 'is-read' : 'is-unread'}`;
                        item.dataset.notificationId = notification.id;
                        item.dataset.readUrl = notification.read_url;
                        item.dataset.actionUrl = notification.action_url || '';

                        const state = document.createElement('span');
                        state.className = 'notification-state';
                        state.setAttribute('aria-label', notification.read_at ? 'Read' : 'Unread');
                        const copy = document.createElement('div');
                        copy.className = 'notification-copy';
                        const title = document.createElement('strong');
                        title.textContent = notification.title;
                        const message = document.createElement('p');
                        message.textContent = notification.message;
                        const meta = document.createElement('div');
                        meta.className = 'notification-meta';
                        const type = document.createElement('span');
                        type.className = 'notification-type';
                        type.textContent = notification.type;
                        const time = document.createElement('time');
                        time.textContent = notification.created_at;
                        meta.append(type, time);
                        copy.append(title, message, meta);
                        item.append(state, copy);

                        if (notification.action_url) {
                            const link = document.createElement('a');
                            link.className = 'notification-open-link';
                            link.href = notification.action_url;
                            link.setAttribute('aria-label', 'Open notification');
                            const icon = document.createElement('i');
                            icon.dataset.lucide = 'arrow-up-right';
                            icon.setAttribute('aria-hidden', 'true');
                            link.append(icon);
                            item.append(link);
                        } else if (!notification.read_at) {
                            const markButton = document.createElement('button');
                            markButton.type = 'button';
                            markButton.className = 'notification-read-button';
                            markButton.dataset.markNotification = '';
                            markButton.setAttribute('aria-label', 'Mark notification as read');
                            const icon = document.createElement('i');
                            icon.dataset.lucide = 'check';
                            icon.setAttribute('aria-hidden', 'true');
                            markButton.append(icon);
                            item.append(markButton);
                        }
                        notificationList.append(item);
                    });
                }
                renderIcons();
                updateNotificationCount(data.unread_count);
            };
            const refreshNotificationPreview = async () => {
                try {
                    const response = await fetch(notificationPreviewUrl, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) throw new Error('Unable to load notifications.');
                    renderNotificationPreview(await response.json());
                } catch (error) {
                    console.error(error);
                }
            };
            const setNotificationsOpen = (isOpen) => {
                notificationTrigger.setAttribute('aria-expanded', String(isOpen));
                notificationPanel.hidden = !isOpen;
                if (isOpen) {
                    refreshNotificationPreview();
                    if (!reducedMotion && window.gsap) {
                        window.gsap.fromTo(notificationPanel, { autoAlpha: 0, y: -8 }, { autoAlpha: 1, y: 0, duration: .2, ease: 'power2.out' });
                    }
                }
            };
            if (notificationCenter) {
                notificationTrigger.addEventListener('click', () => {
                    setNotificationsOpen(notificationPanel.hidden);
                });
                notificationMarkAll.addEventListener('click', async () => {
                    notificationMarkAll.disabled = true;
                    try {
                        const response = await fetch(notificationReadAllUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        });
                        if (!response.ok) throw new Error('Unable to mark notifications as read.');
                        const data = await response.json();
                        notificationList.querySelectorAll('.notification-item').forEach((item) => {
                            item.classList.remove('is-unread');
                            item.classList.add('is-read');
                            item.querySelector('.notification-state')?.setAttribute('aria-label', 'Read');
                            const markButton = item.querySelector('[data-mark-notification]');
                            if (markButton) markButton.hidden = true;
                        });
                        updateNotificationCount(data.unread_count);
                    } catch (error) {
                        console.error(error);
                        notificationMarkAll.disabled = false;
                    }
                });
                notificationList.addEventListener('click', async (event) => {
                    const item = event.target.closest('.notification-item');
                    if (!item || !item.classList.contains('is-unread')) return;
                    const openLink = event.target.closest('.notification-open-link');
                    const markButton = event.target.closest('[data-mark-notification]');
                    if (!openLink && !markButton) return;
                    event.preventDefault();
                    try {
                        await markNotificationRead(item);
                        if (openLink) window.location.assign(openLink.href);
                    } catch (error) {
                        console.error(error);
                    }
                });
                document.addEventListener('click', (event) => {
                    if (!notificationCenter.contains(event.target)) setNotificationsOpen(false);
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !notificationPanel.hidden) {
                        setNotificationsOpen(false);
                        notificationTrigger.focus();
                    }
                });
            }
            setCollapsedState(document.documentElement.classList.contains('sidebar-preference-collapsed'));

            const setMobileNavigation = (isOpen) => {
                sidebar.classList.toggle('open', isOpen);
                sidebarScrim.classList.toggle('open', isOpen);
                document.body.classList.toggle('nav-open', isOpen);
                menuToggle.setAttribute('aria-expanded', String(isOpen));
                menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            };

            menuToggle.addEventListener('click', () => {
                setMobileNavigation(!sidebar.classList.contains('open'));
            });
            sidebarScrim.addEventListener('click', () => setMobileNavigation(false));
            sidebar.addEventListener('click', (event) => {
                if (event.target.closest('a.nav-link')) setMobileNavigation(false);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setMobileNavigation(false);
            });
            window.matchMedia('(max-width: 900px)').addEventListener('change', () => setMobileNavigation(false));

            document.addEventListener('DOMContentLoaded', () => {
                renderIcons();

                if (reducedMotion) return;

                const cards = document.querySelectorAll('.stat-card');
                if (window.gsap && cards.length) {
                    window.gsap.from(cards, {
                        autoAlpha: 0,
                        y: 12,
                        duration: 0.45,
                        stagger: 0.07,
                        ease: 'power2.out',
                        clearProps: 'all',
                    });
                }

                if (window.AOS) {
                    document.querySelectorAll('.page-head, .panel').forEach((element, index) => {
                        element.dataset.aos = 'fade-up';
                        element.dataset.aosDelay = index ? '60' : '0';
                    });
                    window.AOS.init({ duration: 480, easing: 'ease-out-cubic', once: true, offset: 24 });
                }

                if (window.gsap) {
                    document.querySelectorAll('.modal').forEach((modal) => {
                        modal.addEventListener('show.bs.modal', () => {
                            const dialog = modal.querySelector('.modal-dialog');
                            if (dialog) window.gsap.fromTo(dialog, { autoAlpha: 0, y: 12, scale: .985 }, { autoAlpha: 1, y: 0, scale: 1, duration: .24, ease: 'power2.out' });
                        });
                    });
                    document.querySelectorAll('.dropdown').forEach((dropdown) => {
                        dropdown.addEventListener('shown.bs.dropdown', () => {
                            const menu = dropdown.querySelector('.dropdown-menu');
                            if (menu) window.gsap.fromTo(menu, { autoAlpha: 0, y: -4 }, { autoAlpha: 1, y: 0, duration: .16, ease: 'power1.out' });
                        });
                    });
                }

                const isMobile = window.matchMedia('(max-width: 900px)').matches;
                if (window.Lenis && !isMobile) {
                    const lenis = new window.Lenis({
                        autoRaf: true,
                        prevent: (node) => Boolean(node.closest('.sidebar, .modal, .dropdown-menu, form, input, textarea, select, [contenteditable="true"]')),
                    });
                    window.addEventListener('pagehide', () => lenis.destroy(), { once: true });
                }
            });

            collapseToggle.addEventListener('click', () => {
                setCollapsedState(!shell.classList.contains('sidebar-collapsed'), true);
            });
        })();
    </script>
</body>
</html>
