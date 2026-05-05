@extends('admin.layout')
@section('title', 'Broadcast Notification')
@section('page-title', 'Broadcast Notification')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="table-card p-4">
            <h6 class="fw-bold mb-4" style="color:var(--gold)"><i class="bi bi-megaphone me-2"></i>Send Notification to Users</h6>

            <form method="POST" action="{{ route('admin.web.broadcast.send') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Notification Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" required maxlength="150"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}" placeholder="e.g. Platform Update, New Feature Available…">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Message <span class="text-danger">*</span> <small class="text-muted">(max 500 chars)</small>
                    </label>
                    <textarea name="message" required maxlength="500" rows="4"
                              class="form-control @error('message') is-invalid @enderror"
                              placeholder="Write your notification message here…">{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Target Audience <span class="text-danger">*</span></label>
                    <select name="target" class="form-select @error('target') is-invalid @enderror">
                        <option value="all" {{ old('target') === 'all' ? 'selected' : '' }}>All Users</option>
                        <option value="free" {{ old('target') === 'free' ? 'selected' : '' }}>Free Plan Users</option>
                        @foreach($plans as $plan)
                            @if($plan->plan_type)
                            <option value="{{ $plan->plan_type }}" {{ old('target') === $plan->plan_type ? 'selected' : '' }}>
                                {{ $plan->name }} — {{ ucfirst($plan->plan_type) }} Plan Users
                            </option>
                            @endif
                        @endforeach
                    </select>
                    @error('target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-warning fw-semibold px-5"
                        style="background:var(--gold);border-color:var(--gold);color:#fff;"
                        onclick="return confirm('Send this notification to the selected audience?')">
                    <i class="bi bi-send me-1"></i>Send Notification
                </button>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="table-card p-4 mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Important Notes</h6>
            <ul class="small text-muted mb-0 ps-3">
                <li class="mb-1">Notifications are delivered <strong>in-app</strong> and via <strong>WebSocket (Reverb)</strong>.</li>
                <li class="mb-1">Only active, non-banned, and verified users receive the notification.</li>
                <li class="mb-1">Large audiences may take a few seconds to process.</li>
                <li class="mb-1">Notifications cannot be recalled once sent.</li>
            </ul>
        </div>
        <div class="table-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>User Audience Stats</h6>
            @php
                $totalUsers   = \App\Models\User::where('is_banned', false)->whereNotNull('email_verified_at')->count();
                $freeUsers    = \App\Models\User::where('is_banned', false)->whereNotNull('email_verified_at')->where('subscription_plan', 'free')->count();
                $premiumUsers = $totalUsers - $freeUsers;
            @endphp
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">All verified users</span>
                <strong>{{ $totalUsers }}</strong>
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Free plan users</span>
                <strong>{{ $freeUsers }}</strong>
            </div>
            <div class="d-flex justify-content-between small">
                <span class="text-muted">Premium users</span>
                <strong class="text-warning">{{ $premiumUsers }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection

