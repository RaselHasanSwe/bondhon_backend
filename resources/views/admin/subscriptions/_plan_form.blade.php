@use(App\Services\SubscriptionFeatureService)
@php
    $groupedFeatures = SubscriptionFeatureService::groupedDefinitions();
    $planFeatures    = isset($plan) ? ($plan->features ?? []) : [];
    $defs            = SubscriptionFeatureService::definitions();

    // Returns the current (old/saved/default) value for a feature key
    $fval = function(string $key) use ($planFeatures, $defs) {
        $fromOld = old("features.{$key}");
        if ($fromOld !== null) return $fromOld;
        if (array_key_exists($key, $planFeatures)) return $planFeatures[$key];
        return $defs[$key]['default'] ?? null;
    };

    // Tier → icon + colour classes
    $tierMeta = [
        'free'     => ['icon' => '🆓', 'badge' => 'bg-secondary',               'label' => 'Free'],
        'silver'   => ['icon' => '🥈', 'badge' => 'bg-light text-dark border',   'label' => 'Silver'],
        'gold'     => ['icon' => '🥇', 'badge' => 'bg-warning text-dark',        'label' => 'Gold'],
        'platinum' => ['icon' => '💎', 'badge' => 'bg-primary',                  'label' => 'Platinum'],
    ];

    // Group icon map
    $groupIcons = [
        'Discovery & Search'    => 'bi-search',
        'Communication'         => 'bi-chat-dots',
        'Visibility & Insights' => 'bi-eye',
        'Profile Promotion'     => 'bi-megaphone',
        'Photos & Privacy'      => 'bi-images',
        'Trust & Verification'  => 'bi-shield-check',
        'Reports & Analytics'   => 'bi-bar-chart',
        'Support'               => 'bi-headset',
        'Notifications'         => 'bi-bell',
    ];
@endphp

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1 — Basic Information                                         --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="mb-3 pb-3 border-bottom">
    <h6 class="fw-bold text-uppercase mb-3" style="font-size:.7rem; letter-spacing:.08em; color:#6c757d;">
        <i class="bi bi-info-circle me-1"></i> Basic Information
    </h6>

    {{-- Plan Name --}}
    <div class="mb-2">
        <label class="form-label small fw-semibold mb-1">
            Plan Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name"
               class="form-control form-control-sm @error('name') is-invalid @enderror"
               value="{{ old('name', $plan->name ?? '') }}"
               placeholder="e.g. Gold — 3 Months"
               required>
        <div class="form-text" style="font-size:.68rem;">
            Use a descriptive name: <em>Tier — Duration</em> (e.g. Silver — 1 Year).
        </div>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Tier + Sort Order in one row --}}
    <div class="row g-2 mb-2">
        <div class="col-8">
            <label class="form-label small fw-semibold mb-1">
                Subscription Tier <span class="text-danger">*</span>
            </label>
            <select name="plan_type"
                    class="form-select form-select-sm @error('plan_type') is-invalid @enderror"
                    required>
                <option value="">
                    — Select a tier —
                </option>
                @foreach($types as $cPlanType)
                    <option value="{{$cPlanType->id}}" {{ old('plan_type') == $cPlanType->id ? 'selected' : '' }}
                        {{isset($plan) && $plan->plan_type == $cPlanType->id ? 'selected' : ''}}
                        >
                        {{ ucfirst($cPlanType->name) }}
                    </option>
                @endforeach
            </select>
            <div class="form-text" style="font-size:.68rem;">
                Determines the badge colour shown on the user's account.
            </div>
            @error('plan_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-4">
            <label class="form-label small fw-semibold mb-1">Sort Order</label>
            <input type="number" name="sort_order"
                   class="form-control form-control-sm"
                   value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                   min="0">
        </div>
    </div>

    {{-- Description --}}
    <div class="mb-0">
        <label class="form-label small fw-semibold mb-1">Description</label>
        <input type="text" name="description"
               class="form-control form-control-sm"
               value="{{ old('description', $plan->description ?? '') }}"
               placeholder="Short tagline shown to users on the subscription page">
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2 — Pricing & Duration                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="mb-3 pb-3 border-bottom">
    <h6 class="fw-bold text-uppercase mb-3" style="font-size:.7rem; letter-spacing:.08em; color:#6c757d;">
        <i class="bi bi-currency-exchange me-1"></i> Pricing &amp; Duration
    </h6>

    {{-- Price --}}
    <div class="mb-2">
        <label class="form-label small fw-semibold mb-1">
            Price (BDT) <span class="text-danger">*</span>
        </label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">৳</span>
            <input type="number" name="price_bdt"
                   class="form-control @error('price_bdt') is-invalid @enderror"
                   value="{{ old('price_bdt', $plan->price_bdt ?? '') }}"
                   min="0" placeholder="1200" required>
            @error('price_bdt')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size:.68rem;">Set to 0 for the Free (non-purchasable) plan.</div>
    </div>

    {{-- Duration qty + unit --}}
    <div class="row g-2 mb-1">
        <div class="col-5">
            <label class="form-label small fw-semibold mb-1">
                Duration Qty <span class="text-danger">*</span>
            </label>
            <input type="number" name="duration_qty"
                   id="duration_qty_{{ $plan->id ?? 'new' }}"
                   class="form-control form-control-sm @error('duration_qty') is-invalid @enderror"
                   value="{{ old('duration_qty', $plan->duration_qty ?? 1) }}"
                   min="0" required>
            @error('duration_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-4">
            <label class="form-label small fw-semibold mb-1">
                Unit <span class="text-danger">*</span>
            </label>
            <select name="duration_unit"
                    id="duration_unit_{{ $plan->id ?? 'new' }}"
                    class="form-select form-select-sm" required>
                @foreach(['hour' => 'Hour(s)', 'day' => 'Day(s)', 'month' => 'Month(s)', 'year' => 'Year(s)'] as $val => $lbl)
                    <option value="{{ $val }}"
                        {{ old('duration_unit', $plan->duration_unit ?? 'month') === $val ? 'selected' : '' }}>
                        {{ $lbl }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-3 d-flex align-items-end">
            {{-- Quick-fill presets --}}
            <div class="dropdown w-100">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm w-100 dropdown-toggle"
                        data-bs-toggle="dropdown"
                        title="Quick-fill duration presets">
                    <i class="bi bi-lightning"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="font-size:.78rem; min-width:130px;">
                    <li><h6 class="dropdown-header" style="font-size:.65rem;">Quick Presets</h6></li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="fillDuration('{{ $plan->id ?? 'new' }}',1,'month');return false;">
                           1 Month
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="fillDuration('{{ $plan->id ?? 'new' }}',3,'month');return false;">
                           3 Months
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="fillDuration('{{ $plan->id ?? 'new' }}',6,'month');return false;">
                           6 Months
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="fillDuration('{{ $plan->id ?? 'new' }}',1,'year');return false;">
                           1 Year
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="form-text" style="font-size:.68rem; margin-top:2px;">
        Standard durations: 1 month · 3 months · 6 months · 1 year
    </div>

    {{-- Active toggle --}}
    <div class="mt-2">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="is_active"
                   id="is_active_{{ $plan->id ?? 'new' }}"
                   {{ old('is_active', isset($plan) ? $plan->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label small fw-semibold" for="is_active_{{ $plan->id ?? 'new' }}">
                Active — visible to users on the subscription page
            </label>
        </div>
        <div class="form-text" style="font-size:.68rem;">
            Inactive plans (e.g. Free) are hidden from the public purchase list but remain in the admin panel.
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 3 — Feature Permissions (Superadmin only)                    --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div>
    <div class="d-flex align-items-center gap-2 mb-1">
        <h6 class="fw-bold text-uppercase mb-0" style="font-size:.7rem; letter-spacing:.08em; color:#6c757d;">
            <i class="bi bi-sliders me-1"></i> Feature Permissions
        </h6>
        <span class="badge bg-danger" style="font-size:.58rem; letter-spacing:.03em;">SUPERADMIN</span>
    </div>
    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.72rem; border-left:3px solid #ffc107;">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Only features toggled ON here will be shown to users</strong> in the frontend plan cards.
        Leave a feature OFF to hide it from this plan entirely.
        &nbsp;|&nbsp; Qty: <strong>0</strong> = disabled &nbsp; <strong>-1</strong> = unlimited
    </div>

    @foreach($groupedFeatures as $groupName => $features)
    @php $groupIcon = $groupIcons[$groupName] ?? 'bi-gear'; @endphp
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-header py-1 px-3 d-flex align-items-center gap-2"
             style="background:#f8f9fa; font-size:.72rem; cursor:pointer;"
             data-bs-toggle="collapse"
             data-bs-target="#fg_{{ Str::slug($groupName) }}_{{ $plan->id ?? 'new' }}"
             aria-expanded="true">
            <i class="bi {{ $groupIcon }} text-secondary"></i>
            <span class="fw-semibold text-uppercase" style="letter-spacing:.05em; color:#495057;">
                {{ $groupName }}
            </span>
            <span class="badge bg-secondary ms-auto" style="font-size:.6rem;">
                {{ count($features) }} features
            </span>
            <i class="bi bi-chevron-down ms-1" style="font-size:.6rem;"></i>
        </div>
        <div class="collapse show" id="fg_{{ Str::slug($groupName) }}_{{ $plan->id ?? 'new' }}">
            <div class="card-body py-2 px-3">
                @foreach($features as $key => $def)
                @php $val = $fval($key); $serial = $loop->iteration; @endphp

                @if($def['type'] === 'bool')
                    {{-- ── Boolean toggle ────────────────────────────────── --}}
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-muted fw-bold flex-shrink-0" style="font-size:.65rem; min-width:18px; text-align:right;">{{ $serial }}.</span>
                        <div class="form-check form-switch mb-0 flex-shrink-0">
                            <input class="form-check-input" type="checkbox"
                                   role="switch"
                                   name="features[{{ $key }}]"
                                   value="1"
                                   id="feat_{{ $key }}_{{ $plan->id ?? 'new' }}"
                                   {{ $val ? 'checked' : '' }}>
                        </div>
                        <label class="small mb-0 flex-grow-1"
                               for="feat_{{ $key }}_{{ $plan->id ?? 'new' }}"
                               style="font-size:.78rem; cursor:pointer;">
                            {{ $def['label'] }}
                        </label>
                    </div>

                @elseif($def['type'] === 'qty')
                    {{-- ── Quantity limit ─────────────────────────────────── --}}
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-muted fw-bold" style="font-size:.65rem; min-width:18px; text-align:right;">{{ $serial }}.</span>
                        <label class="small mb-0 flex-grow-1" style="font-size:.78rem;">
                            {{ $def['label'] }}
                        </label>
                        <div class="input-group input-group-sm" style="width:120px;">
                            <input type="number"
                                   name="features[{{ $key }}]"
                                   class="form-control form-control-sm text-center"
                                   value="{{ is_numeric($val) ? (int)$val : (int)($def['default'] ?? 0) }}"
                                   min="-1"
                                   title="0 = disabled · -1 = unlimited">
                            <span class="input-group-text px-1" style="font-size:.65rem; min-width:38px;">
                                @if(isset($def['period'])) /{{ $def['period'] }} @else total @endif
                            </span>
                        </div>
                    </div>

                @elseif($def['type'] === 'enum')
                    {{-- ── Enum select ─────────────────────────────────────── --}}
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-muted fw-bold" style="font-size:.65rem; min-width:18px; text-align:right;">{{ $serial }}.</span>
                        <label class="small mb-0 flex-grow-1" style="font-size:.78rem;">
                            {{ $def['label'] }}
                        </label>
                        <select name="features[{{ $key }}]"
                                class="form-select form-select-sm"
                                style="width:120px;">
                            @foreach($def['options'] as $opt)
                                <option value="{{ $opt }}" {{ $val == $opt ? 'selected' : '' }}>
                                    {{ ucfirst($opt) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Quick-fill JS ──────────────────────────────────────────────────────── --}}
<script>
function fillDuration(planId, qty, unit) {
    document.getElementById('duration_qty_'  + planId).value = qty;
    document.getElementById('duration_unit_' + planId).value = unit;
}
</script>
