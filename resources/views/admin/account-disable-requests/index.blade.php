@extends('admin.layout')
@section('title', 'Account Disable Requests')
@section('page-title', 'Account Disable Request Queue')

@section('content')
{{-- Status Filter --}}
<form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['pending' => 'Pending', 'action_taken' => 'Action Taken', 'dismissed' => 'Dismissed'] as $val => $label)
    <button type="submit" name="status" value="{{ $val }}"
            class="btn btn-sm {{ $status === $val ? 'btn-warning' : 'btn-outline-secondary' }}"
            style="{{ $status === $val ? 'background:var(--gold);border-color:var(--gold);color:#fff;' : '' }}">
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
                    <th>User</th>
                    <th>Request Type</th>
                    <th>User Message</th>
                    <th>Status</th>
                    <th>Admin Action</th>
                    <th>Admin Message</th>
                    <th>Reviewed By</th>
                    <th>Date</th>
                    <th style="min-width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                <tr>
                    <td class="text-muted">{{ $item->id }}</td>
                    <td>
                        <div class="fw-semibold">
                            <a href="{{ route('admin.web.users.show', $item->user_id) }}" class="text-decoration-none">
                                {{ $item->user?->name ?? '—' }}
                            </a>
                        </div>
                        <div class="text-muted" style="font-size:11px;">{{ $item->user?->email }}</div>
                        @if($item->user)
                            <div class="d-flex flex-wrap gap-1 mt-1" style="font-size:10px;">
                                @if($item->isReactivated() || ($item->user->is_active && ! $item->user->is_banned))
                                    <span class="badge bg-success">Active</span>
                                    @if($item->isReactivated())
                                        <span class="badge bg-info text-dark">Reactivated</span>
                                    @endif
                                @elseif($item->user->is_banned)
                                    <span class="badge bg-danger">Banned</span>
                                @else
                                    <span class="badge bg-warning text-dark">Disabled</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-info text-dark">{{ $item->request_type?->label() ?? '—' }}</span>
                    </td>
                    <td style="max-width:160px;">
                        <span title="{{ $item->message }}">
                            {{ \Illuminate\Support\Str::limit($item->message, 50) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgeMap = ['pending'=>'danger','action_taken'=>'success','dismissed'=>'secondary'];
                        @endphp
                        <span class="badge bg-{{ $badgeMap[$item->status->value ?? $item->status] ?? 'secondary' }}">
                            {{ $item->status?->label() ?? str_replace('_', ' ', $item->status) }}
                        </span>
                    </td>
                    <td>
                        @if($item->admin_action)
                            @php
                                $actionBadge = match($item->admin_action->value) {
                                    'banned' => 'danger',
                                    'disabled' => 'warning',
                                    'reactivated' => 'success',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $actionBadge }} {{ in_array($item->admin_action->value, ['disabled'], true) ? 'text-dark' : '' }}">
                                {{ $item->admin_action->label() }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="max-width:160px;">
                        @if($item->admin_message)
                            <span title="{{ $item->admin_message }}">
                                {{ \Illuminate\Support\Str::limit($item->admin_message, 50) }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:11px;">
                        {{ $item->reviewer?->name ?? '—' }}
                        @if($item->reviewed_at)
                            <div>{{ $item->reviewed_at->format('d M Y H:i') }}</div>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:11px;">{{ $item->created_at->format('d M Y') }}</td>
                    <td>
                        @if($item->isPending())
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal" data-bs-target="#disableModal{{ $item->id }}">
                                    <i class="bi bi-slash-circle"></i> Disable
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#banModal{{ $item->id }}">
                                    <i class="bi bi-ban"></i> Ban
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#dismissModal{{ $item->id }}">
                                    <i class="bi bi-x"></i> Dismiss
                                </button>
                            </div>
                        @elseif($item->canReactivate())
                            <button type="button" class="btn btn-sm btn-success"
                                    data-bs-toggle="modal" data-bs-target="#reactivateModal{{ $item->id }}">
                                <i class="bi bi-check-circle"></i> Reactivate
                            </button>
                        @elseif($item->isReactivated())
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Reactivated</span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No account disable requests found for this status.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($requests as $item)
    @if($item->isPending())
    <div class="modal fade" id="disableModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.web.account-disable-requests.disable', $item->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-slash-circle text-warning me-2"></i>Disable Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Disable <strong>{{ $item->user?->name }}</strong>'s account. They will be logged out until reactivated.
                        </p>
                        <label for="disable_admin_message_{{ $item->id }}" class="form-label fw-semibold small">
                            Reason / Message <span class="text-danger">*</span>
                        </label>
                        <textarea id="disable_admin_message_{{ $item->id }}" name="admin_message" rows="4"
                                  class="form-control" required minlength="10" maxlength="2000"
                                  placeholder="Explain why the account is being disabled…"></textarea>
                        <small class="text-muted">Sent to the user in notification and email.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm">Disable Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="banModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.web.account-disable-requests.ban', $item->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="bi bi-ban me-2"></i>Ban Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Permanently ban <strong>{{ $item->user?->name }}</strong>. This is a severe action.
                        </p>
                        <label for="ban_admin_message_{{ $item->id }}" class="form-label fw-semibold small">
                            Ban Reason / Message <span class="text-danger">*</span>
                        </label>
                        <textarea id="ban_admin_message_{{ $item->id }}" name="admin_message" rows="4"
                                  class="form-control" required minlength="10" maxlength="2000"
                                  placeholder="Explain why the account is being banned…"></textarea>
                        <small class="text-muted">Sent to the user in notification and email.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Ban Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dismissModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.web.account-disable-requests.dismiss', $item->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Dismiss Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Dismiss this request without changing the user's account status.
                        </p>
                        <label for="dismiss_admin_message_{{ $item->id }}" class="form-label fw-semibold small">
                            Admin Note <span class="text-muted">(optional)</span>
                        </label>
                        <textarea id="dismiss_admin_message_{{ $item->id }}" name="admin_message" rows="3"
                                  class="form-control" maxlength="2000"
                                  placeholder="Optional note for the user…"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Dismiss</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($item->canReactivate())
    <div class="modal fade" id="reactivateModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.web.account-disable-requests.reactivate', $item->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title text-success"><i class="bi bi-check-circle me-2"></i>Reactivate Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Reactivate <strong>{{ $item->user?->name }}</strong>'s account so they can sign in again.
                        </p>
                        <label for="reactivate_admin_message_{{ $item->id }}" class="form-label fw-semibold small">
                            Message to User <span class="text-muted">(optional)</span>
                        </label>
                        <textarea id="reactivate_admin_message_{{ $item->id }}" name="admin_message" rows="3"
                                  class="form-control" maxlength="2000"
                                  placeholder="Optional note for the user…"></textarea>
                        <small class="text-muted">Sent to the user in notification and email.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle me-1"></i>Reactivate Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

<div class="mt-4">
    {{ $requests->appends(request()->query())->links() }}
</div>
@endsection
