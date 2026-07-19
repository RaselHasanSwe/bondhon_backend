<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ $siteName }}</title>
    @if($siteFavicon)
        <link rel="icon" href="{{ cfImage($siteFavicon) }}">
    @endif
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
            width: 250px; height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.1);
            flex-shrink: 0;
        }
        .sidebar-brand h4 { color: var(--gold); font-weight: 700; margin: 0; }
        .sidebar-brand small { color: rgba(255,255,255,.5); font-size: 11px; }
        .sidebar-nav {
            flex: 1; min-height: 0; padding: 1rem 0;
            overflow-y: auto; overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(201, 162, 39, .35) transparent;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
            margin: 4px 0;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .12);
            border-radius: 999px;
            transition: background .2s ease;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(201, 162, 39, .55);
        }
        .sidebar-nav:hover {
            scrollbar-color: rgba(201, 162, 39, .55) transparent;
        }
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
            flex-shrink: 0; margin-top: auto;
            background: var(--sidebar-bg);
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
        .sidebar-brand h4{color: rgba(255, 255, 255, .7)}
    </style>
</head>
<body>

@include('admin.partials.sidebar')

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <h5>@yield('page-title', 'Dashboard')</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background:var(--gold);color:#fff;">
                <i class="bi bi-shield-check me-1"></i>{{auth()->user()->name}}
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

