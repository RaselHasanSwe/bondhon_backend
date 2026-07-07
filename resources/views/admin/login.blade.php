<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — {{ $siteName }}</title>
    @if($siteFavicon)
        <link rel="icon" href="{{ cfImage($siteFavicon) }}">
    @elseif($siteLogo)
        <link rel="icon" href="{{ cfImage($siteLogo) }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff; border-radius: 16px; padding: 2.5rem;
            width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        @media (max-width: 575.98px) {
            body { padding: 1rem; }
            .login-card { padding: 1.5rem; }
        }
        .brand-title { color: #C9A227; font-weight: 800; font-size: 2rem; }
        .btn-gold { background: #C9A227; color: #fff; border: none; }
        .btn-gold:hover { background: #a8891e; color: #fff; }
        .form-control:focus { border-color: #C9A227; box-shadow: 0 0 0 .2rem rgba(201,162,39,.25); }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <div class="brand-title">{{ $siteName }}</div>
        <p class="text-muted mb-0" style="font-size:.8rem;letter-spacing:.1em;">SUPER ADMIN PANEL</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.web.login.submit') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold small">Email Address</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email') }}" placeholder="admin@Enorsia.com" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="••••••••" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn-gold w-100 fw-semibold">Sign in to Admin Panel</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

