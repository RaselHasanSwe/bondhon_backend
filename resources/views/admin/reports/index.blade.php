@extends('admin.layout')
@section('title', 'Reports')
@section('page-title', 'Report Review Queue')

@section('content')
{{-- Status Filter --}}
<form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['pending' => 'Pending', 'dismissed' => 'Dismissed'] as $val => $label)
    <button type="submit" name="status" value="{{ $val }}"
            class="btn btn-sm {{ request('status', 'pending') === $val ? 'btn-warning' : 'btn-outline-secondary' }}"
            style="{{ request('status', 'pending') === $val ? 'background:var(--gold);border-color:var(--gold);color:#fff;' : '' }}">
        {{ $label }}
    </button>
    @endforeach
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Reporter</th>
                    <th>Reported User</th>
                    <th>Reason</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td class="text-muted">{{ $report->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $report->reporter?->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $report->reporter?->email }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-danger">{{ $report->reported?->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $report->reported?->email }}</div>
                    </td>
                    <td>
                        <span class="badge bg-warning text-dark">{{ str_replace('_', ' ', $report->reason) }}</span>
                    </td>
                    <td style="max-width:200px;">
                        <span title="{{ $report->description }}">
                            {{ $report->description ? \Illuminate\Support\Str::limit($report->description, 60) : '—' }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgeMap = ['pending'=>'danger','reviewed'=>'info','action_taken'=>'success','dismissed'=>'secondary'];
                        @endphp
                        <span class="badge bg-{{ $badgeMap[$report->status] ?? 'secondary' }}">
                            {{ str_replace('_', ' ', $report->status) }}
                        </span>
                    </td>
                    <td class="text-muted" style="font-size:11px;">{{ $report->created_at->format('d M Y') }}</td>
                    <td>
                        @if($report->status === 'pending')
                        <div class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.web.reports.dismiss', $report->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-sm btn-outline-secondary"
                                        onclick="return confirm('Dismiss this report?')">
                                    <i class="bi bi-x"></i> Dismiss
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.web.reports.ban', $report->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-sm btn-danger"
                                        onclick="return confirm('Ban this user? This action is severe.')">
                                    <i class="bi bi-ban"></i> Ban
                                </button>
                            </form>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No reports found for this status.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $reports->appends(request()->query())->links() }}
</div>
@endsection

