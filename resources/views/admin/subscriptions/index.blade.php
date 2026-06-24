@extends('admin.layout')
@section('title', 'Subscriptions')
@section('page-title', 'Subscriptions')

@section('content')

{{-- Revenue Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card text-center">
            <div class="text-muted small mb-1">Total Revenue</div>
            <div class="fs-3 fw-bold" style="color:var(--gold);">৳{{ number_format($summary['total_revenue']) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card text-center">
            <div class="text-muted small mb-1">This Month</div>
            <div class="fs-3 fw-bold text-success">৳{{ number_format($summary['month_revenue']) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card text-center">
            <div class="text-muted small mb-1">Active Subscriptions</div>
            <div class="fs-3 fw-bold text-primary">{{ number_format($summary['active_count']) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card text-center">
            <div class="text-muted small mb-1">Total Sold</div>
            <div class="fs-3 fw-bold">{{ number_format($summary['total_count']) }}</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="table-card p-3 mb-3">
    <form method="GET" action="{{ route('admin.web.subscriptions') }}" class="row g-2">
        <div class="col-sm-2">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search by name or email…" value="{{ request('search') }}">
        </div>
        <div class="col-sm-1">
            <select name="plan" class="form-select form-select-sm">
                <option value="">All Plans</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" {{ request('plan')==$plan->id ? 'selected':'' }}>{{ ucfirst($plan->name) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-1">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active"  {{ request('status')=='active'  ?'selected':'' }}>Active</option>
                <option value="pending" {{ request('status')=='pending' ?'selected':'' }}>Pending</option>
                <option value="expired" {{ request('status')=='expired' ?'selected':'' }}>Expired</option>
            </select>
        </div>
        <div class="col-sm-2">
            <input type="date" name="from" class="form-control form-control-sm"
                    value="{{ request('from') }}" title="From Date">
        </div>
         <div class="col-sm-2">
            <input type="date" name="to" class="form-control form-control-sm"
                    value="{{ request('to') }}" title="To Date">
        </div>
        <div class="col-sm-2 d-flex gap-1">
            <button class="btn btn-sm btn-dark flex-fill">Filter</button>
            <a href="{{ route('admin.web.subscriptions') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Subscriptions Table --}}
<div class="table-card p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            All Subscriptions <span class="badge bg-secondary ms-1">{{ $subscriptions->total() }}</span>
        </h6>
        @if($filteredSummary['revenue'] > 0 || $filteredSummary['active_subscriptions'] > 0 || $filteredSummary['total_sold'] > 0)
            <div class="d-flex flex-wrap gap-2 mt-2">

                @if($filteredSummary['revenue'] > 0)
                <div class="d-flex align-items-center gap-2 bg-info border rounded px-3">
                    <i class="ti ti-currency-taka text-success fs-5"></i>
                    <div>
                        <div class="text-white" style="font-size:13px;">Revenue: ৳{{ number_format($filteredSummary['revenue']) }}</div>
                    </div>
                </div>
                @endif

                @if($filteredSummary['active_subscriptions'] > 0)
                <div class="d-flex align-items-center gap-2 bg-success border rounded px-3">
                    <i class="ti ti-refresh text-info fs-5"></i>
                    <div>
                        <div class="text-white" style="font-size:11px;">Active subscriptions: {{ $filteredSummary['active_subscriptions'] }}</div>
                    </div>
                </div>
                @endif

                @if($filteredSummary['total_sold'] > 0)
                <div class="d-flex align-items-center gap-2 bg-warning border rounded px-3">
                    <i class="ti ti-shopping-bag text-warning fs-5"></i>
                    <div>
                        <div class="text-white" style="font-size:11px;">Total sold qty: {{ $filteredSummary['total_sold'] }}</div>
                    </div>
                </div>
                @endif

            </div>
            @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
            <thead class="table-light">
                <tr>
                    <th>User</th><th>Plan</th><th>Package</th>
                    <th>Amount</th><th>Payment Method</th><th>Status</th>
                    <th>Starts</th><th>Expires</th><th>Transaction ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $sub->user->name }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $sub->user->email }}</div>
                    </td>
                    <td><span class="badge badge-{{ $sub->plan }}">{{ ucfirst($sub->plan) }}</span></td>
                    <td>{{ $sub->subscriptionPlan?->name ?? '—' }}</td>
                    <td class="fw-semibold">৳{{ number_format($sub->amount_bdt) }}</td>
                    <td>{{ $sub->payment_method ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match($sub->status) {
                                'active'   => 'bg-success',
                                'pending'  => 'bg-warning text-dark',
                                'expired'  => 'bg-secondary',
                                'refunded' => 'bg-info text-dark',
                                default    => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($sub->status) }}</span>
                    </td>
                    <td>{{ $sub->starts_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        @if($sub->expires_at)
                            <span class="{{ $sub->expires_at->isFuture() ? 'text-success' : 'text-danger' }}">
                                {{ $sub->expires_at->format('d M Y') }}
                            </span>
                        @else —
                        @endif
                    </td>
                    <td>
                        <code class="small" style="font-size:.68rem;">
                            {{ Str::limit($sub->transaction_id, 20) }}
                        </code>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No subscriptions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $subscriptions->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection

