@php
    $badges = $sidebarBadges ?? [];
@endphp

<aside class="sidebar">
    <div class="sidebar-brand">
        <h4>{{ $siteName ? strtoupper($siteName) : strtoupper('Super Admin') }}</h4>
        <small>Super Admin Panel</small>
    </div>

    <nav class="sidebar-nav">
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.dashboard',
            'icon'  => 'bi-speedometer2',
            'label' => 'Dashboard',
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.users',
            'icon'  => 'bi-people',
            'label' => 'Users',
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.plans',
            'icon'  => 'bi-layers',
            'label' => 'Plans',
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.subscriptions',
            'icon'  => 'bi-credit-card',
            'label' => 'Subscriptions',
        ])

        <hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1.25rem;">

        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.photos',
            'icon'  => 'bi-images',
            'label' => 'Approvals',
            'badge' => $badges['pending_photos'] ?? 0,
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.reports',
            'icon'  => 'bi-flag',
            'label' => 'Reports',
            'badge' => $badges['pending_reports'] ?? 0,
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.account-disable-requests',
            'icon'  => 'bi-person-x',
            'label' => 'Ac. Disable Request',
            'badge' => $badges['pending_disable_requests'] ?? 0,
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.broadcast',
            'icon'  => 'bi-megaphone',
            'label' => 'Broadcast',
        ])
        @include('admin.partials.sidebar-link', [
            'route'        => 'admin.web.notifications.history',
            'routePattern' => ['admin.web.notifications.history', 'admin.web.notifications.view'],
            'icon'         => 'bi-bell',
            'label'        => 'Notifications',
            'style'        => 'font-size:15px;',
            'badge'        => $badges['unread_notifications'] ?? 0,
            'badgeClass'   => 'bg-secondary',
            'badgeStyle'   => 'font-size:10px',
            'badgeCap'     => 99,
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.contact-messages',
            'icon'  => 'bi-envelope-open',
            'label' => 'Messages',
            'badge' => $badges['new_messages'] ?? 0,
        ])

        <hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1.25rem;">

        @include('admin.partials.sidebar-link', [
            'route'        => 'admin.web.pages',
            'routePattern' => ['admin.web.pages', 'admin.web.pages.edit'],
            'icon'         => 'bi-file-text',
            'label'        => 'CMS / Pages',
        ])
        @include('admin.partials.sidebar-link', [
            'route'        => 'admin.web.select-options.index',
            'routePattern' => 'admin.web.select-options.*',
            'icon'         => 'bi-ui-checks-grid',
            'label'        => 'Select Options',
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.settings',
            'icon'  => 'bi-gear',
            'label' => 'Site Settings',
        ])
        @include('admin.partials.sidebar-link', [
            'route' => 'admin.web.change-password',
            'icon'  => 'bi-key',
            'label' => 'Change Password',
        ])
    </nav>

    <div class="sidebar-footer">
        <div class="text-white-50 small mb-2">{{ Auth::user()->name }}</div>
        <form method="POST" action="{{ route('admin.web.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </form>
    </div>
</aside>
