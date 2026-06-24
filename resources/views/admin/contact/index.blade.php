@extends('admin.layout')
@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
{{-- Stats row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4" style="color:var(--gold)">{{ $unreadCount }}</div>
            <div class="small text-muted">Unread</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="fw-bold fs-4 text-secondary">{{ $messages->total() }}</div>
            <div class="small text-muted">Total Messages</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email or subject…"
               value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">-- status --</option>
            <option value="new"     {{ request('status') === 'new'     ? 'selected' : '' }}>Unread</option>
            <option value="read"    {{ request('status') === 'read'    ? 'selected' : '' }}>Read</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-warning" style="background:var(--gold);border-color:var(--gold);color:#fff;">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="{{ route('admin.web.contact-messages') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="table-card">
    @if($messages->isEmpty())
        <div class="p-5 text-center text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No contact messages found.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px"></th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($messages as $msg)
                    <tr class="{{ $msg->status === 'new' ? 'table-warning bg-opacity-25' : '' }}">
                        <td>
                            @if($msg->status === 'new')
                                <span class="badge bg-danger" title="New"><i class="bi bi-circle-fill" style="font-size:8px;"></i></span>
                            @elseif($msg->status === 'read')
                                <i class="bi bi-envelope-open text-muted"></i>
                            @else
                                <i class="bi bi-reply text-success"></i>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $msg->name }}</div>
                            <div class="text-muted">{{ $msg->email }}</div>
                        </td>
                        <td class="fw-medium">{{ Str::limit($msg->subject, 50) }}</td>
                        <td class="text-muted">{{ Str::limit($msg->message, 80) }}</td>
                        <td>
                            @if($msg->status === 'new')
                                <span class="badge bg-danger">New</span>
                            @elseif($msg->status === 'read')
                                <span class="badge bg-secondary">Read</span>
                            @else
                                <span class="badge bg-success">Replied</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $msg->created_at->format('d M Y, H:i') }}</td>
                        <td class="text-end">
                            {{-- View full message via modal trigger --}}
                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#msgModal{{ $msg->id }}">
                                <i class="bi bi-eye"></i>
                            </button>

                            @if($msg->status === 'new')
                            <form method="POST" action="{{ route('admin.web.contact-messages.read', $msg->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Mark as read">
                                    <i class="bi bi-check"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.web.contact-messages.delete', $msg->id) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this message?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    {{-- Full message modal --}}
                    <div class="modal fade" id="msgModal{{ $msg->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title fw-bold">
                                        <i class="bi bi-envelope me-2" style="color:var(--gold)"></i>{{ $msg->subject }}
                                    </h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row small mb-3">
                                        <dt class="col-sm-2 text-muted">From</dt>
                                        <dd class="col-sm-10">{{ $msg->name }} &lt;{{ $msg->email }}&gt;</dd>
                                        <dt class="col-sm-2 text-muted">Received</dt>
                                        <dd class="col-sm-10">{{ $msg->created_at->format('d M Y, H:i:s') }}</dd>
                                    </dl>
                                    <div class="bg-light rounded p-3" style="white-space:pre-wrap;line-height:1.7;font-size:.9rem;">{{ $msg->message }}</div>
                                </div>
                                <div class="modal-footer gap-2">
                                    @if($msg->status === 'new')
                                    <form method="POST" action="{{ route('admin.web.contact-messages.read', $msg->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-check me-1"></i>Mark Read
                                        </button>
                                    </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($messages->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $messages->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

