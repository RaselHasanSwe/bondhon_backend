@extends('admin.layout')
@section('title', 'Notifications — ' . $user->name)
@section('page-title', 'User Notifications')

@section('content')

{{-- Breadcrumb --}}
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.web.users') }}" style="color:var(--gold)">Users</a></li>
        <li class="breadcrumb-item active">{{ $user->name }} — Notification History</li>
    </ol>
</nav>

{{-- User card --}}
<div class="table-card p-3 mb-4 d-flex align-items-center gap-3 flex-wrap">
    <div class="stat-icon" style="background:rgba(201,162,39,.15);color:var(--gold)">
        <i class="bi bi-person-circle fs-4"></i>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold">{{ $user->name }}</div>
        <div class="text-muted small">{{ $user->email }}</div>
    </div>
    <div class="d-flex gap-3 text-center">
        <div>
            <div class="fw-bold fs-5" style="color:var(--gold)">{{ number_format($totalCount) }}</div>
            <div class="text-muted" style="font-size:11px">Total</div>
        </div>
        <div>
            <div class="fw-bold fs-5 text-danger">{{ number_format($unreadCount) }}</div>
            <div class="text-muted" style="font-size:11px">Unread</div>
        </div>
        <div>
            <div class="fw-bold fs-5 text-success">{{ number_format($totalCount - $unreadCount) }}</div>
            <div class="text-muted" style="font-size:11px">Read</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="table-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.web.users.notifications', $user->id) }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-4">
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
        <div class="col-6 col-md-3">
            <label class="form-label fw-semibold small mb-1">Read Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                <option value="read"   {{ request('status') === 'read'   ? 'selected' : '' }}>Read</option>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm fw-semibold"
                    style="background:var(--gold);border-color:var(--gold);color:#fff;">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            @if(request()->hasAny(['type','status']))
                <a href="{{ route('admin.web.users.notifications', $user->id) }}"
                   class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
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
                <th style="width:140px">Type</th>
                <th>Title / Message</th>
                <th style="width:110px">User Read?</th>
                <th style="width:145px">Sent At</th>
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
                        <span class="badge bg-secondary fw-normal" style="font-size:10px">{{ $typeStr }}</span>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ Str::limit($title, 70) }}</div>
                        @if($message)
                            <div class="text-muted" style="font-size:11px">{{ Str::limit($message, 100) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($n->is_read)
                            <span class="badge bg-success-subtle text-success" style="font-size:10px">
                                <i class="bi bi-check2 me-1"></i>Read
                                @if($n->read_at)
                                    <br><span class="fw-normal text-muted" style="font-size:9px">
                                        {{ \Carbon\Carbon::parse($n->read_at)->format('d M Y H:i') }}
                                    </span>
                                @endif
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
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block mb-2 opacity-25"></i>
                        No notifications found for this user.
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
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @else
        <div class="px-3 py-2 border-top small text-muted">
            {{ $notifications->total() }} notification(s)
        </div>
    @endif
</div>

<div class="mt-3">
    <a href="{{ route('admin.web.users') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Users
    </a>
</div>
@endsection

