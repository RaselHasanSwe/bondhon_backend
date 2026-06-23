@extends('admin.layout')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="table-card p-4">
            <h5 class="fw-semibold mb-4">
                <i class="bi bi-key me-2" style="color:var(--gold)"></i>Change Your Password
            </h5>

            <form method="POST" action="{{ route('admin.web.change-password.submit') }}">
                @csrf

                <div class="mb-3">
                    <label for="current_password" class="form-label fw-medium">Current Password</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="form-control @error('current_password') is-invalid @enderror"
                        placeholder="Enter your current password"
                        autocomplete="current-password"
                        required
                    >
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label fw-medium">New Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control @error('new_password') is-invalid @enderror"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        required
                        minlength="8"
                    >
                    @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="new_password_confirmation" class="form-label fw-medium">Confirm New Password</label>
                    <input
                        type="password"
                        id="new_password_confirmation"
                        name="new_password_confirmation"
                        class="form-control"
                        placeholder="Repeat new password"
                        autocomplete="new-password"
                        required
                        minlength="8"
                    >
                </div>

                <button type="submit" class="btn w-100 fw-semibold" style="background:var(--gold);color:#fff;border-radius:8px;padding:.6rem 0;">
                    <i class="bi bi-shield-check me-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

