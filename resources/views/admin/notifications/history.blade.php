@extends('admin.layout')
@section('title', 'Admin Notification History')
@section('page-title', 'Admin Notification History')

@section('content')
<div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-4 small">
    <i class="bi bi-info-circle-fill"></i>
    <span>Only admin-sent notifications are shown here (Broadcasts, Photo Approvals/Rejections, Subscription Expiry).
    To view all notifications for a specific user, go to <a href="{{ route('admin.web.users') }}" style="color:inherit;font-weight:600;">Users</a> → <i class="bi bi-bell"></i> icon.</span>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4" style="color:var(--gold)">{{ number_format($totalCount) }}</div>
            <div class="text-muted small">Total Sent</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4 text-danger">{{ number_format($unreadCount) }}</div>
            <div class="text-muted small">Unread</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4 text-success">{{ number_format($totalCount - $unreadCount) }}</div>
            <div class="text-muted small">Read</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4 text-primary">{{ $notifications->lastPage() }}</div>
            <div class="text-muted small">Pages</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="table-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.web.notifications.history') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold small mb-1">Search User</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Name or email…">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label fw-semibold small mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $t)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-semibold small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                <option value="read"   {{ request('status') === 'read'   ? 'selected' : '' }}>Read</option>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-warning fw-semibold"
                    style="background:var(--gold);border-color:var(--gold);color:#fff;">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            @if(request()->hasAny(['search','type','status']))
                <a href="{{ route('admin.web.notifications.history') }}"
                   class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
            <a href="{{ route('admin.web.broadcast') }}"
               class="btn btn-sm btn-outline-warning ms-auto" style="color:var(--gold);border-color:var(--gold);">
                <i class="bi bi-megaphone me-1"></i>Send
            </a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
            <tr>
                <th style="width:36px">#</th>
                <th>User</th>
                <th>Type</th>
                <th>Title / Message</th>
                <th style="width:110px">User Read?</th>
                <th style="width:130px">Sent At</th>
                <th style="width:70px" class="text-center">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($notifications as $n)
                @php
                    $decoded = is_string($n->data) ? json_decode($n->data, true) : $n->data;
                    $data    = is_array($decoded) ? $decoded : [];
                    $title   = $data['title']   ?? '—';
                    $message = $data['message'] ?? '';
                    $typeStr = ucwords(str_replace('_', ' ', $n->type));
                @endphp
                <tr class="{{ $n->is_read ? '' : 'table-warning' }}">
                    <td class="text-muted">{{ $notifications->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-semibold">{{ $n->user_name }}</div>
                        <div class="text-muted" style="font-size:11px">{{ $n->user_email }}</div>
                    </td>
                    <td>
                        <span class="badge bg-secondary fw-normal" style="font-size:10px">{{ $typeStr }}</span>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ Str::limit($title, 60) }}</div>
                        @if($message)
                            <div class="text-muted" style="font-size:11px">{{ Str::limit($message, 80) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($n->is_read)
                            <span class="badge bg-success-subtle text-success" style="font-size:10px">
                                <i class="bi bi-check2 me-1"></i>Read
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger" style="font-size:10px">
                                <i class="bi bi-dot me-1"></i>Unread
                            </span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:11px">
                        {{ \Carbon\Carbon::parse($n->created_at)->format('d M Y H:i') }}
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.web.notifications.view', $n->id) }}"
                           class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px;">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block mb-2 opacity-25"></i>
                        No notifications found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
            <div class="small text-muted">
                Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }}
                of {{ $notifications->total() }} notifications
            </div>
            <div>
                {{ $notifications->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    @else
        <div class="px-3 py-2 border-top small text-muted">
            {{ $notifications->total() }} notification(s)
        </div>
    @endif
</div>
@endsection

