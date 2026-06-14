@extends('admin.layout')
@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
@php
    $faceSession = $user->faceScanSession;
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="table-card p-4 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                    <div class="text-muted small">#{{ $user->id }} · {{ $user->email }}</div>
                </div>
                @if($user->trashed())
                    <span class="badge bg-secondary">Deleted</span>
                @elseif($user->is_banned)
                    <span class="badge bg-danger">Banned</span>
                @else
                    <span class="badge bg-success">Active</span>
                @endif
            </div>

            <div class="mb-2 small"><strong>Gender:</strong> {{ ucfirst($user->gender ?? '-') }}</div>
            <div class="mb-2 small"><strong>Role:</strong> {{ ucfirst($user->role) }}</div>
            <div class="mb-2 small"><strong>Plan:</strong> {{ ucfirst($user->subscription_plan) }}</div>
            <div class="mb-2 small"><strong>Profile ID:</strong> {{ $user->profile->profile_id ?? '—' }}</div>
            <div class="mb-2 small"><strong>Email Verified:</strong> {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y h:i A') : 'No' }}</div>
            <div class="mb-2 small"><strong>Joined:</strong> {{ $user->created_at->format('d M Y h:i A') }}</div>
        </div>

        <div class="table-card p-4">
            <h6 class="fw-bold mb-3">Actions</h6>
            <div class="d-grid gap-2">
                <form method="POST" action="{{ route('admin.web.users.ban-toggle', $user->id) }}">
                    @csrf
                    <button class="btn {{ $user->is_banned ? 'btn-success' : 'btn-danger' }} w-100">
                        {{ $user->is_banned ? 'Unban User' : 'Ban User' }}
                    </button>
                </form>

                @if($faceSession)
                    <form method="POST" action="{{ route('admin.web.users.face-scan-review', $user->id) }}">
                        @csrf
                        <input type="hidden" name="decision" value="approved">
                        <button class="btn btn-primary w-100" {{ $faceSession->status === 'approved' ? 'disabled' : '' }}>Approve Face Scan</button>
                    </form>
                    <form method="POST" action="{{ route('admin.web.users.face-scan-review', $user->id) }}">
                        @csrf
                        <input type="hidden" name="decision" value="rejected">
                        <button class="btn btn-outline-danger w-100">Reject Face Scan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="table-card p-4 mb-3">
            <h6 class="fw-bold mb-3">Face Scan Status</h6>
            @if($faceSession)
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge {{ $faceSession->status === 'approved' ? 'bg-success' : ($faceSession->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                        {{ ucfirst($faceSession->status) }}
                    </span>
                    @if($faceSession->completed_at)
                        <span class="badge bg-info text-dark">Submitted {{ $faceSession->completed_at->format('d M Y h:i A') }}</span>
                    @endif
                    @if($faceSession->reviewed_at)
                        <span class="badge bg-secondary">Reviewed {{ $faceSession->reviewed_at->format('d M Y h:i A') }}</span>
                    @endif
                </div>
                @if($faceSession->review_note)
                    <div class="alert alert-light border small mb-3">{{ $faceSession->review_note }}</div>
                @endif

                <div class="row g-3">
                    @forelse($faceSession->captures as $capture)
                        <div class="col-6 col-lg-4">
                            <div class="border rounded-3 p-2 h-100">
                                <img src="{{ asset('storage/' . $capture->image_path) }}" alt="{{ $capture->capture_key }}" class="img-fluid rounded-2 mb-2" style="aspect-ratio: 3/4; object-fit: cover; width: 100%;">
                                <div class="d-flex justify-content-between align-items-center small">
                                    <strong>{{ str_replace('-', ' ', ucfirst($capture->capture_key)) }}</strong>
                                    <span class="text-muted">{{ $capture->captured_at?->format('h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">No captures stored yet.</div>
                    @endforelse
                </div>
            @else
                <div class="text-muted">This user has not completed the face-scan step.</div>
            @endif
        </div>
    </div>
</div>
@endsection

