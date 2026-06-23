@extends('admin.layout')
@section('title', 'Edit Plan — {{ $plan->name }}')
@section('page-title', 'Edit Subscription Plan')

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-3" style="font-size:.78rem;">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.web.plans') }}">Subscription Plans</a>
                </li>
                <li class="breadcrumb-item active">Edit: {{ $plan->name }}</li>
            </ol>
        </nav>

        <div class="table-card p-4">
            {{-- Header --}}
            <div class="d-flex align-items-start justify-content-between mb-1 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square me-1 text-warning"></i>
                        Editing: {{ $plan->name }}
                    </h6>
                    <p class="text-muted mb-0" style="font-size:.72rem;">
                        Plan ID #{{ $plan->id }} &middot; Created {{ $plan->created_at->diffForHumans() }}
                    </p>
                </div>
                @php
                    $tierColors = ['free'=>'bg-secondary','silver'=>'bg-light text-dark border','gold'=>'bg-warning text-dark','platinum'=>'bg-primary'];
                    $tierIcons  = ['free'=>'🆓','silver'=>'🥈','gold'=>'🥇','platinum'=>'💎'];
                    $tc = $tierColors[$plan->plan_type] ?? 'bg-secondary';
                    $ti = $tierIcons[$plan->plan_type] ?? '⭐';
                @endphp
                <span class="badge {{ $tc }} fs-6 px-3 py-1">
                    {{ $ti }} {{ ucfirst($plan->plan_type) }}
                </span>
            </div>

            <hr class="my-3">

            @if($errors->any())
                <div class="alert alert-danger small py-2 px-3 mb-3">
                    <strong><i class="bi bi-exclamation-triangle me-1"></i> Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.web.plans.update', $plan->id) }}">
                @csrf @method('PUT')
                @include('admin.subscriptions._plan_form')

                <div class="d-flex gap-2 mt-4 pt-2 border-top">
                    <button type="submit"
                            class="btn btn-sm fw-semibold flex-fill"
                            style="background:var(--gold);color:#fff;">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.web.plans') }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

@endsection

