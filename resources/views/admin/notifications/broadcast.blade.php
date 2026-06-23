@extends('admin.layout')
@section('title', 'Broadcast Notification')
@section('page-title', 'Broadcast Notification')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-wrapper.form-control { padding: 0; border: none; }
    .ts-control { border: 1px solid #dee2e6; border-radius: .375rem; min-height: 38px; }
    #specific-users-section { display: none; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="table-card p-4">
            <h6 class="fw-bold mb-4" style="color:var(--gold)"><i class="bi bi-megaphone me-2"></i>Send Notification to Users</h6>


            <form method="POST" action="{{ route('admin.web.broadcast.send') }}" id="broadcastForm">
                @csrf
                {{-- Title --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Notification Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" required maxlength="150"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}" placeholder="e.g. Platform Update, New Feature Available…">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Message --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Message <span class="text-danger">*</span> <small class="text-muted">(max 500 chars)</small>
                    </label>
                    <textarea name="message" required maxlength="500" rows="4"
                              class="form-control @error('message') is-invalid @enderror"
                              placeholder="Write your notification message here…">{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Delivery Channel --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Delivery Channel <span class="text-danger">*</span></label>
                    <select name="channel" class="form-select @error('channel') is-invalid @enderror">
                        <option value="application" {{ old('channel', 'application') === 'application' ? 'selected' : '' }}>
                            📱 In-App Notification Only
                        </option>
                        <option value="email" {{ old('channel') === 'email' ? 'selected' : '' }}>
                            ✉️ Email Only
                        </option>
                        <option value="both" {{ old('channel') === 'both' ? 'selected' : '' }}>
                            📱 + ✉️ Both (In-App & Email)
                        </option>
                    </select>
                    @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Target Audience --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Target Audience <span class="text-danger">*</span></label>
                    <select name="target" id="targetSelect" class="form-select @error('target') is-invalid @enderror">
                        <option value="all" {{ old('target') === 'all' ? 'selected' : '' }}>All Users</option>
                        <option value="free" {{ old('target') === 'free' ? 'selected' : '' }}>Free Plan Users</option>
                        @foreach($plans as $plan)
                            @if($plan->plan_type)
                            <option value="{{ $plan->plan_type }}" {{ old('target') === $plan->plan_type ? 'selected' : '' }}>
                                {{ $plan->name }} — {{ ucfirst($plan->plan_type) }} Plan Users
                            </option>
                            @endif
                        @endforeach
                        <option value="specific" {{ old('target') === 'specific' ? 'selected' : '' }}>
                            🎯 Specific Users (Search &amp; Select)
                        </option>
                    </select>
                    @error('target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Specific Users Multi-Select --}}
                <div class="mb-4" id="specific-users-section">
                    <label class="form-label fw-semibold small">
                        Search &amp; Select Users <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">(type to search by name or email)</small>
                    </label>
                    <input type="hidden" name="user_ids" id="userIdsInput" value="{{ old('user_ids') }}">
                    <select id="userSearchSelect" multiple placeholder="Type to search users…" autocomplete="off"
                            class="form-control @error('user_ids') is-invalid @enderror"></select>
                    @error('user_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                <li class="mb-1"><strong>In-App</strong> notifications appear in the app bell icon in real-time.</li>
                <li class="mb-1"><strong>Email</strong> notifications are queued and delivered to users' inboxes.</li>
                <li class="mb-1">Only active, non-banned, and verified users receive the notification.</li>
                <li class="mb-1">Use <strong>Specific Users</strong> to target one or more individuals by name/email.</li>
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    const targetSelect = document.getElementById('targetSelect');
    const specificSection = document.getElementById('specific-users-section');
    const userIdsInput = document.getElementById('userIdsInput');

    // Show/hide specific users section
    function toggleSpecific() {
        if (targetSelect.value === 'specific') {
            specificSection.style.display = 'block';
        } else {
            specificSection.style.display = 'none';
        }
    }
    targetSelect.addEventListener('change', toggleSpecific);
    toggleSpecific(); // run on page load

    // Initialise TomSelect with remote search
    const ts = new TomSelect('#userSearchSelect', {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        plugins: ['remove_button'],
        load: function (query, callback) {
            if (!query.length) return callback();
            fetch('{{ route('admin.web.broadcast.users-search') }}?q=' + encodeURIComponent(query), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => callback(data))
            .catch(() => callback());
        },
        onChange: function (values) {
            userIdsInput.value = values.join(',');
        },
    });
})();
</script>
@endsection
