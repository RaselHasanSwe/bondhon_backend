@extends('admin.layout')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')

{{-- Stats Row 1 --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe;">
                    <i class="bi bi-people-fill" style="color:#2563eb;"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Users</div>
                    <div class="fs-4 fw-bold">{{ number_format($stats['total_users']) }}</div>
                </div>
            </div>
            <div class="mt-2 small text-success">
                <i class="bi bi-arrow-up-short"></i>+{{ $stats['new_users_today'] }} today
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5;">
                    <i class="bi bi-person-check-fill" style="color:#059669;"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Users</div>
                    <div class="fs-4 fw-bold">{{ number_format($stats['active_users']) }}</div>
                </div>
            </div>
            <div class="mt-2 small text-danger">
                <i class="bi bi-slash-circle"></i> {{ $stats['banned_users'] }} banned
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7;">
                    <i class="bi bi-credit-card-fill" style="color:#d97706;"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Subscriptions</div>
                    <div class="fs-4 fw-bold">{{ number_format($stats['active_subscriptions']) }}</div>
                </div>
            </div>
            <div class="mt-2 small text-muted">
                <i class="bi bi-clock"></i> currently active
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fce7f3;">
                    <i class="bi bi-currency-exchange" style="color:#be185d;"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fs-4 fw-bold">৳{{ number_format($stats['total_revenue_bdt']) }}</div>
                </div>
            </div>
            <div class="mt-2 small text-success">
                <i class="bi bi-calendar3"></i> ৳{{ number_format($stats['this_month_revenue']) }} this month
            </div>
        </div>
    </div>
</div>

{{-- Stats Row 2 --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#ede9fe;">
                    <i class="bi bi-image" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Photos</div>
                    <div class="fs-4 fw-bold text-warning">{{ $stats['pending_photos'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fee2e2;">
                    <i class="bi bi-flag-fill" style="color:#dc2626;"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Reports</div>
                    <div class="fs-4 fw-bold text-danger">{{ $stats['pending_reports'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Recent Subscriptions --}}
    <div class="col-lg-7">
        <div class="table-card p-0">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Recent Subscriptions</h6>
                <a href="{{ route('admin.web.subscriptions') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>User</th><th>Plan</th><th>Amount</th><th>Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSubscriptions as $sub)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $sub->user->name }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $sub->user->email }}</div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $sub->plan }}">{{ ucfirst($sub->plan) }}</span>
                            </td>
                            <td>৳{{ number_format($sub->amount_bdt) }}</td>
                            <td>{{ $sub->expires_at?->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No subscriptions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Revenue by Plan --}}
    <div class="col-lg-5">
        <div class="table-card p-3">
            <h6 class="mb-3 fw-semibold">Revenue by Plan</h6>
            @forelse($revenueByPlan as $item)
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-{{ $item->plan }}">{{ ucfirst($item->plan) }}</span>
                    <span class="small text-muted">{{ $item->count }} subscribers</span>
                </div>
                <strong>৳{{ number_format($item->revenue) }}</strong>
            </div>
            @php
                $total = $revenueByPlan->sum('revenue');
                $pct = $total > 0 ? round(($item->revenue / $total) * 100) : 0;
            @endphp
            <div class="progress mb-2" style="height:6px;">
                <div class="progress-bar" style="width:{{ $pct }}%;background:var(--gold);"></div>
            </div>
            @empty
            <p class="text-muted small">No revenue data yet.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

