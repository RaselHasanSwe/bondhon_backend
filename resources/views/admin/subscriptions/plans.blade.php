@extends('admin.layout')
@section('title', 'Subscription Plans')
@section('page-title', 'Subscription Plans')
@use(App\Services\SubscriptionFeatureService)

@section('content')

{{-- ── Page intro ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted small mb-0">
            Manage all purchasable plans. Only <strong>active</strong> plans appear on the frontend.
            Feature permissions are controlled exclusively by <span class="badge bg-danger" style="font-size:.6rem;">SUPERADMIN</span>.
        </p>
    </div>
    <a href="#create-plan" class="btn btn-sm fw-semibold" style="background:var(--gold);color:#fff;">
        <i class="bi bi-plus-lg me-1"></i> New Plan
    </a>
</div>

<div class="row g-4">

    {{-- ── Left column: Existing Plans list ──────────────────────────────── --}}
    <div class="col-lg-8 order-lg-1 order-2">
        <div class="table-card p-0">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-list-ul me-1 text-muted"></i>
                    All Plans
                    <span class="badge bg-secondary ms-1">{{ $plans->count() }}</span>
                </h6>
                <small class="text-muted" style="font-size:.7rem;">
                    Sorted by sort_order → tier → duration
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 small align-middle">
                    <thead class="table-light" style="font-size:.72rem;">
                        <tr>
                            <th>#</th>
                            <th>Plan Name</th>
                            <th>Tier</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Subs</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                        @php
                            $tierColors = [
                                'free'     => 'bg-secondary',
                                'silver'   => 'bg-light text-dark border',
                                'gold'     => 'bg-warning text-dark',
                                'platinum' => 'bg-primary',
                            ];
                            $tierIcons = [
                                'free' => '🆓', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎',
                            ];
                            $tc = $tierColors[$plan->plan_type] ?? 'bg-secondary';
                            $ti = $tierIcons[$plan->plan_type] ?? '⭐';
                        @endphp
                        <tr>
                            <td class="text-muted" style="font-size:.7rem;">{{ $plan->sort_order }}</td>
                            <td>
                                <div class="fw-semibold">{{ $plan->name }}</div>
                                @if($plan->description)
                                    <div class="text-muted" style="font-size:.7rem;">
                                        {{ Str::limit($plan->description, 55) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $tc }}" style="font-size:.65rem;">
                                    {{ $ti }} {{ ucfirst($plan->plan_type ?: '—') }}
                                </span>
                            </td>
                            <td class="fw-semibold">
                                @if($plan->price_bdt > 0)
                                    ৳{{ number_format($plan->price_bdt) }}
                                @else
                                    <span class="text-muted">Free</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->duration_qty > 0)
                                    {{ $plan->duration_qty }} {{ $plan->duration_unit }}(s)
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $plan->subscriptions_count }}
                                </span>
                            </td>
                            <td>
                                @if($plan->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.web.plans.edit', $plan->id) }}"
                                   class="btn btn-xs btn-outline-primary btn-sm py-0 px-2 me-1"
                                   title="Edit plan">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('admin.web.plans.destroy', $plan->id) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete «{{ $plan->name }}»? Active subscriptions will not be affected.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger btn-sm py-0 px-2" title="Delete plan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No plans yet. Create your first plan using the form →
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Feature preview cards for active plans ──────────────────── --}}
        @foreach($plans->where('is_active', true) as $plan)
        @php
            $planFeatures = $plan->features ?? [];
            $defs = SubscriptionFeatureService::definitions();
            $tierColors = ['free'=>'bg-secondary','silver'=>'bg-light text-dark border','gold'=>'bg-warning text-dark','platinum'=>'bg-primary'];
            $tierIcons  = ['free'=>'🆓','silver'=>'🥈','gold'=>'🥇','platinum'=>'💎'];
            $tc = $tierColors[$plan->plan_type] ?? 'bg-secondary';
            $ti = $tierIcons[$plan->plan_type] ?? '⭐';
        @endphp
        <div class="table-card p-3 mt-3">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="fw-semibold">{{ $plan->name }}</span>
                <span class="badge {{ $tc }}" style="font-size:.65rem;">{{ $ti }} {{ ucfirst($plan->plan_type) }}</span>
                <span class="text-muted small">
                    ৳{{ number_format($plan->price_bdt) }}
                    / {{ $plan->duration_qty }} {{ $plan->duration_unit }}(s)
                </span>
            </div>
            <div class="row g-1">
                @foreach($planFeatures as $fkey => $fval)
                    @if(!is_int($fkey) && isset($defs[$fkey]))
                    <div class="col-6 col-md-4">
                        <span class="small" style="font-size:.7rem;">
                            @if($defs[$fkey]['type'] === 'bool')
                                @if($fval)
                                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                                @else
                                    <i class="bi bi-x-circle text-secondary me-1"></i>
                                @endif
                                {{ $defs[$fkey]['label'] }}
                            @elseif($defs[$fkey]['type'] === 'qty')
                                <i class="bi bi-hash text-primary me-1"></i>
                                {{ $defs[$fkey]['label'] }}:
                                <strong>{{ $fval == -1 ? '∞' : $fval }}</strong>
                            @elseif($defs[$fkey]['type'] === 'enum')
                                <i class="bi bi-tag me-1"></i>
                                {{ $defs[$fkey]['label'] }}: <strong>{{ ucfirst($fval) }}</strong>
                            @endif
                        </span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Right column: Create New Plan ──────────────────────────────── --}}
    <div class="col-lg-4 order-lg-2 order-1" id="create-plan">
        <div class="table-card p-4">
            <h6 class="fw-semibold mb-1">
                <i class="bi bi-plus-circle me-1 text-success"></i> Create New Plan
            </h6>
            <p class="text-muted mb-3" style="font-size:.72rem;">
                Fill in all three sections below, then click <strong>Create Plan</strong>.
            </p>

            @if($errors->any())
                <div class="alert alert-danger small py-2 px-3 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.web.plans.store') }}">
                @csrf
                @include('admin.subscriptions._plan_form')
                <button type="submit"
                        class="btn btn-sm w-100 mt-3 fw-semibold"
                        style="background:var(--gold);color:#fff;">
                    <i class="bi bi-plus-lg me-1"></i> Create Plan
                </button>
            </form>
        </div>
    </div>

</div>

@endsection

