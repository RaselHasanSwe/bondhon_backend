<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Bondhon Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @stack('styles')
    <style>
        :root {
            --gold: #C9A227;
            --gold-light: #D4AF37;
            --sidebar-bg: #1a1a2e;
            --sidebar-hover: #16213e;
        }
        body { background: #f4f6fb; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: 250px; min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-brand h4 { color: var(--gold); font-weight: 700; margin: 0; }
        .sidebar-brand small { color: rgba(255,255,255,.5); font-size: 11px; }
        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,.7); padding: .65rem 1.25rem;
            border-radius: 0; display: flex; align-items: center; gap: .75rem;
            font-size: .875rem; transition: all .15s;
        }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: #fff; background: rgba(201,162,39,.15);
            border-left: 3px solid var(--gold);
        }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 20px; }
        .sidebar-footer {
            padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,.1);
        }
        .main-content { margin-left: 250px; min-height: 100vh; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: .875rem 1.5rem; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
        }
        .topbar h5 { margin: 0; font-weight: 600; color: #1f2937; }
        .content-area { padding: 1.5rem; }
        .stat-card {
            background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
            padding: 1.25rem; transition: box-shadow .15s;
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .table-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
        .badge-silver { background-color: #9ca3af; color: #fff; }
        .badge-gold { background-color: #C9A227; color: #fff; }
        .badge-platinum { background-color: #7c3aed; color: #fff; }
        .badge-free { background-color: #d1d5db; color: #374151; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h4>বন্ধন</h4>
        <small>Super Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('admin.web.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.web.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('admin.web.users') }}"
           class="nav-link {{ request()->routeIs('admin.web.users') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="{{ route('admin.web.plans') }}"
           class="nav-link {{ request()->routeIs('admin.web.plans') ? 'active' : '' }}">
            <i class="bi bi-layers"></i> Subscription Plans
        </a>
        <a href="{{ route('admin.web.subscriptions') }}"
           class="nav-link {{ request()->routeIs('admin.web.subscriptions') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Subscriptions & Sales
        </a>
        <hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1.25rem;">
        <a href="{{ route('admin.web.photos') }}"
           class="nav-link {{ request()->routeIs('admin.web.photos') ? 'active' : '' }}">
            <i class="bi bi-images"></i> Photo Moderation
            @php $pendingPhotos = \App\Models\ProfilePhoto::where('moderation_status','pending')->count(); @endphp
            @if($pendingPhotos > 0)
                <span class="badge bg-danger ms-auto">{{ $pendingPhotos }}</span>
            @endif
        </a>
        <a href="{{ route('admin.web.reports') }}"
           class="nav-link {{ request()->routeIs('admin.web.reports') ? 'active' : '' }}">
            <i class="bi bi-flag"></i> Reports
            @php $pendingReports = \App\Models\Report::where('status','pending')->count(); @endphp
            @if($pendingReports > 0)
                <span class="badge bg-danger ms-auto">{{ $pendingReports }}</span>
            @endif
        </a>
        <a href="{{ route('admin.web.broadcast') }}"
           class="nav-link {{ request()->routeIs('admin.web.broadcast') ? 'active' : '' }}">
            <i class="bi bi-megaphone"></i> Broadcast
        </a>
        <a href="{{ route('admin.web.contact-messages') }}"
           class="nav-link {{ request()->routeIs('admin.web.contact-messages') ? 'active' : '' }}">
            <i class="bi bi-envelope-open"></i> Contact Messages
            @php $newMessages = \App\Models\ContactMessage::where('status','new')->count(); @endphp
            @if($newMessages > 0)
                <span class="badge bg-danger ms-auto">{{ $newMessages }}</span>
            @endif
        </a>
        <hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1.25rem;">
        <a href="{{ route('admin.web.pages') }}"
           class="nav-link {{ request()->routeIs('admin.web.pages') || request()->routeIs('admin.web.pages.edit') ? 'active' : '' }}">
            <i class="bi bi-file-text"></i> Pages
        </a>
        <a href="{{ route('admin.web.settings') }}"
           class="nav-link {{ request()->routeIs('admin.web.settings') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Site Settings
        </a>
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

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <h5>@yield('page-title', 'Dashboard')</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background:var(--gold);color:#fff;">
                <i class="bi bi-shield-check me-1"></i>Admin
            </span>
        </div>
    </div>

    <div class="content-area">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>

