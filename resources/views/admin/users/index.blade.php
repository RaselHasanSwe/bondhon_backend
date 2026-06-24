@extends('admin.layout')
@section('title', 'Users')
@section('page-title', 'User Management')

@section('content')

    {{-- Search / Filter --}}
    <div class="table-card p-3 mb-3">
        <form method="GET" action="{{ route('admin.web.users') }}" class="row g-2">
            <div class="col-sm-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by name or email…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-3">
                <select name="plan" class="form-select form-select-sm">
                    <option value="">--plans--</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan')==$plan->id ? 'selected':'' }}>{{ ucfirst($plan->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">--role--</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ request('role')==$role ? 'selected':'' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">--status--</option>
                    <option value="active" {{ request('status')=='active'   ? 'selected':'' }}>Active</option>
                    <option value="banned" {{ request('status')=='banned'   ? 'selected':'' }}>Banned</option>
                    {{-- <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Deleted</option> --}}
                </select>
            </div>
            <div class="col-sm-2 d-flex gap-1">
                <button class="btn btn-sm btn-dark flex-fill">Filter</button>
                <a href="{{ route('admin.web.users') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="table-card p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">
                Users <span class="badge bg-secondary ms-1">{{ $users->total() }}</span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 small align-middle">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Plan</th>
                    <th>Plan Expires</th>
                    <th>Joined</th>
                    <th>Face Status</th>
                    <th>Role</th>
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="text-muted">#{{ $user->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $user->name }}</div>
                        </td>
                        <td>
                            {{ $user->email }}
                            @if($user->email_verified_at)
                                <div class="text-muted" style="font-size:.72rem;">
                                    @if($user->email_verified_at)
                                        <span class="text-success"><i class="bi bi-patch-check-fill"></i> Email Verified</span>
                                    @else
                                        <span class="text-danger">Email not verified</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ ucfirst($user->gender ?? '-') }}</td>
                        <td>
                        <span class="badge badge-{{ $user->subscription_plan }}">
                            {{ ucfirst($user->subscription_plan) }}
                        </span>
                        </td>
                        <td>
                            @if($user->subscription_expires_at)
                                <span class="{{ $user->subscription_expires_at->isFuture() ? 'text-success' : 'text-danger' }}">
                                {{ $user->subscription_expires_at->format('d M Y') }}
                            </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            @if($user->faceScanSession)
                                @php($faceStatus = $user->faceScanSession->status)
                                <span class="badge {{ $faceStatus === 'approved' ? 'bg-success' : ($faceStatus === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                <i class="bi {{ $faceStatus === 'approved' ? 'bi-shield-check' : ($faceStatus === 'rejected' ? 'bi-shield-x' : 'bi-hourglass-split') }} me-1"></i>
                                {{ ucfirst($faceStatus) }}
                            </span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Not Given</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge
                                {{ $user->role === 'admin' ? 'bg-danger' : ($user->role === 'user' ? 'bg-primary' : 'bg-secondary') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($user->trashed())
                                <span class="badge bg-secondary">Deleted</span>
                            @elseif($user->is_banned)
                                <span class="badge bg-danger">Banned</span>
                            @elseif($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif

                            @if($user->role === 'user')
                            <a href="{{ route('admin.web.users.show', $user->id) }}"
                               class="btn btn-xs btn-outline-primary me-1"
                               style="font-size:11px;padding:2px 8px;" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.web.users.notifications', $user->id) }}"
                               class="btn btn-xs btn-outline-secondary"
                               style="font-size:11px;padding:2px 8px;" title="View Notifications">
                                <i class="bi bi-bell"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection

