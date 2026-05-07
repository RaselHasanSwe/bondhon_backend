@extends('admin.layout')
@section('title', 'Select Options — ' . ($groups[$group] ?? $group))
@section('page-title', 'Dynamic Select Options')

@push('styles')
<style>
    .opt-row { border-left: 3px solid transparent; transition: border .15s; }
    .opt-row:hover { border-left-color: var(--gold); background: #fafafa; }
    .opt-row.inactive { opacity: .55; }
    .opt-row .act-btns { opacity: 0; transition: opacity .15s; }
    .opt-row:hover .act-btns { opacity: 1; }
    .depth-badge { font-size: .68rem; padding: .15em .4em; vertical-align: middle; }
</style>
@endpush

@section('content')

{{-- ── Top Bar: Group Selector + Actions ── --}}
<div class="table-card p-3 mb-3">
    <div class="row align-items-end g-2">
        <div class="col-12 col-sm-5 col-md-4">
            <label class="form-label fw-semibold small mb-1">
                <i class="bi bi-list-ul me-1"></i>Option Group
            </label>
            <select class="form-select form-select-sm" onchange="window.location.href=this.value">
                <option value="{{ route('admin.web.select-options.index', ['group' => 'all']) }}"
                    {{ $group === 'all' ? 'selected' : '' }}>
                    — All Groups ({{ array_sum($groupCounts->toArray()) }}) —
                </option>
                @foreach($groups as $key => $label)
                    <option value="{{ route('admin.web.select-options.index', ['group' => $key]) }}"
                        {{ $group === $key ? 'selected' : '' }}>
                        {{ $label }}{{ ($groupCounts[$key] ?? 0) > 0 ? ' (' . $groupCounts[$key] . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-7 col-md-8 d-flex align-items-end gap-2 flex-wrap">
            <div>
                <span class="fw-bold" style="color:var(--gold);font-size:1rem">
                    {{ $group === 'all' ? 'All Groups' : ($groups[$group] ?? $group) }}
                </span>
                <span class="text-muted small ms-2">
                    @if($group !== 'all') key: <code>{{ $group }}</code> @endif
                    @if($isCrossNested)
                        &nbsp;·&nbsp;<span class="badge bg-info text-dark">Children of: <strong>{{ $groups[$parentGroupKey] ?? $parentGroupKey }}</strong></span>
                    @elseif($isSelfNested)
                        &nbsp;·&nbsp;<span class="badge bg-secondary">Self-nested tree</span>
                    @endif
                    &nbsp;·&nbsp; {{ count($options) }} option(s)
                </span>
            </div>
            <div class="ms-auto d-flex align-items-center justify-content-between gap-3">
                @if($group !== 'all')
                    @if(isset($canAdd) && !$canAdd)
                        <button class="btn btn-sm btn-warning fw-semibold" style="background:var(--gold);border-color:var(--gold);color:#fff;" disabled
                                title="Cannot add: parent options are required first">
                            <i class="bi bi-plus-lg me-1"></i>Add Option
                        </button>
                    @else
                        <button class="btn btn-sm btn-warning fw-semibold"
                                style="background:var(--gold);border-color:var(--gold);color:#fff;"
                                data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i>Add Option
                        </button>
                    @endif
                @endif

                <form method="GET" action="{{ route('admin.web.select-options.index') }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="group" value="{{ $group }}">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Search id, label, value, parent..." value="{{ request('q') }}">
                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Search"><i class="bi bi-search"></i></button>
                    @if(request('q'))
                        <a href="{{ route('admin.web.select-options.index', ['group' => $group]) }}" class="btn btn-sm btn-outline-secondary" title="Clear search"><i class="bi bi-x-lg"></i></a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    @if($isSelfNested)
    <div class="alert alert-secondary alert-dismissible fade show py-2 small mb-0 mt-2" role="alert">
        <i class="bi bi-diagram-3 me-1"></i>
        <strong>Self-nested group</strong> — options can have parent options within the same group (tree structure, max depth: {{ $maxDepth }}).
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Options Table ── --}}
<div class="table-card">
    @if(empty($options))
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
            @if($group !== 'all')
                No options yet for this group. Click <strong>Add Option</strong> to create the first one.
            @else
                No options found.
            @endif
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:55px">#</th>
                        @if($group === 'all')
                            <th>Group</th>
                        @endif
                        <th>Label</th>
                        <th>Value <small class="text-muted fw-normal">(key)</small></th>
                        @if($isCrossNested)
                            <th>Parent ({{ $groups[$parentGroupKey] ?? $parentGroupKey }})</th>
                        @elseif($isSelfNested)
                            <th>Parent</th>
                        @endif
                        <th style="width:80px" class="text-center">Sort</th>
                        <th style="width:90px" class="text-center">Status</th>
                        <th style="width:180px" class="text-end pe-3">Actions</th>
                    </tr>
            </thead>
            <tbody>
            @foreach($options as $opt)
                @php $depth = $opt->_depth ?? 0; @endphp
                <tr class="opt-row {{ $opt->is_active ? '' : 'inactive' }}">
                    <td class="ps-3 text-muted small">{{ $opt->id }}</td>
                    @if($group === 'all')
                        <td>
                            <a href="{{ route('admin.web.select-options.index', ['group' => $opt->group_key]) }}"
                               class="badge text-decoration-none"
                               style="background:rgba(201,162,39,.15);color:var(--gold);border:1px solid rgba(201,162,39,.3)">
                                {{ $groups[$opt->group_key] ?? $opt->group_key }}
                            </a>
                        </td>
                    @endif
                    <td>
                        @if($depth > 0)
                            <span style="display:inline-block;width:{{ $depth * 16 }}px"></span>
                            <span class="text-muted me-1">└</span>
                        @endif
                        <span class="{{ $depth === 0 ? 'fw-semibold' : '' }}">{{ $opt->label }}</span>
                        @if($depth > 0)
                            <span class="badge bg-light text-secondary depth-badge border ms-1">L{{ $depth }}</span>
                        @endif
                    </td>
                    <td><code class="small">{{ $opt->value }}</code></td>
                    @if($isCrossNested || $isSelfNested)
                        <td>
                            @if($opt->parent)
                                <span class="badge" style="background:rgba(201,162,39,.15);color:var(--gold);border:1px solid rgba(201,162,39,.3)">
                                    {{ $opt->parent->label }}
                                </span>
                            @else
                                <span class="text-muted small">— root —</span>
                            @endif
                        </td>
                    @endif
                    <td class="text-center text-muted small">{{ $opt->sort_order }}</td>
                    <td class="text-center">
                        @if($opt->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end pe-3 act-btns" style="width: 185px;">
                        {{-- Add Child button for self-nested groups --}}
                        @if($isSelfNested && $depth < $maxDepth - 1)
                            <button class="btn btn-sm btn-outline-success py-0 px-2"
                                    title="Add child under {{ $opt->label }}"
                                    onclick="openAddChild({{ $opt->id }}, '{{ addslashes($opt->label) }}')">
                                <i class="bi bi-node-plus" style="font-size:.8rem"></i>
                            </button>
                        @endif
                        <form method="POST" action="{{ route('admin.web.select-options.toggle', $opt->id) }}"
                              class="d-inline" onsubmit="return confirm('Toggle active status?')">
                            @csrf
                            <button class="btn btn-sm {{ $opt->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} py-0 px-2"
                                    title="{{ $opt->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi bi-{{ $opt->is_active ? 'toggle-off' : 'toggle-on' }}" style="font-size:.8rem"></i>
                            </button>
                        </form>
                        <a href="{{ route('admin.web.select-options.edit', $opt->id) }}"
                           class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                            <i class="bi bi-pencil" style="font-size:.8rem"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.web.select-options.destroy', $opt->id) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($opt->label) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete">
                                <i class="bi bi-trash" style="font-size:.8rem"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Add Option Modal ── --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.web.select-options.store') }}">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2" style="color:var(--gold)"></i>
                        <span id="modalTitle">Add Option</span>
                        <small class="fw-normal text-muted fs-6"> — {{ $groups[$group] ?? $group }}</small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="group_key" value="{{ $group }}">

                    {{-- Cross-nested: parent from a different group --}}
                    @if($isCrossNested)
                        @if($parentOptions->isEmpty())
                            <div class="alert alert-warning small">
                                <strong>Parent required</strong> — This group requires a parent option from <strong>{{ $groups[$parentGroupKey] ?? $parentGroupKey }}</strong>, but none exist yet.
                                <br>
                                Please add at least one <a href="{{ route('admin.web.select-options.index', ['group' => $parentGroupKey]) }}">{{ $groups[$parentGroupKey] ?? $parentGroupKey }}</a> option first.
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    Parent — {{ $groups[$parentGroupKey] ?? $parentGroupKey }}
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="parent_id" class="form-select form-select-sm" required>
                                    <option value="">— Select parent —</option>
                                    @foreach($parentOptions as $p)
                                        <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Which {{ $groups[$parentGroupKey] ?? $parentGroupKey }} does this belong to?</small>
                            </div>
                        @endif
                    @endif

                    {{-- Self-nested: parent from same group --}}
                    @if($isSelfNested)
                    <div class="mb-3" id="parentFieldWrap">
                        <label class="form-label fw-semibold small">Parent <small class="text-muted fw-normal">(optional — leave blank for root level)</small></label>
                        {{-- Hidden input used when "Add Child" is clicked --}}
                        <div id="parentFixed" class="d-none">
                            <input type="hidden" name="parent_id" id="parentIdFixed">
                            <div class="border rounded px-3 py-2 bg-light small d-flex justify-content-between align-items-center">
                                <span>Child of: <strong id="parentLabelFixed"></strong></span>
                                <button type="button" class="btn-close btn-sm" onclick="clearParent()" title="Remove parent"></button>
                            </div>
                        </div>
                        <div id="parentSelect">
                            <select name="parent_id" id="parentIdSelect" class="form-select form-select-sm">
                                <option value="">— Root level (no parent) —</option>
                                @foreach($options as $po)
                                    @if(($po->_depth ?? 0) < $maxDepth - 1)
                                    <option value="{{ $po->id }}" {{ old('parent_id') == $po->id ? 'selected' : '' }}>
                                        {{ str_repeat('— ', $po->_depth ?? 0) }}{{ $po->label }}
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank to add at the root. Max depth: {{ $maxDepth }}.</small>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="addLabel" class="form-control form-control-sm"
                               placeholder="e.g. West Bengal" required maxlength="255" value="{{ old('label') }}">
                        <small class="text-muted">What users see in the dropdown.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            Value <span class="text-danger">*</span>
                            <small class="text-muted fw-normal">(machine key — lowercase, underscores)</small>
                        </label>
                        <input type="text" name="value" id="addValue" class="form-control form-control-sm"
                               placeholder="e.g. west_bengal" required maxlength="100"
                               pattern="[a-z0-9_'\-]+" title="Lowercase letters, numbers, underscores and hyphens only"
                               value="{{ old('value') }}">
                        <small class="text-muted">Stored in the database. Best not to change after creation.</small>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm"
                                   value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive"
                                       {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="addIsActive">Active (visible in dropdowns)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(isset($canAdd) && !$canAdd)
                        <button type="button" class="btn btn-sm btn-warning fw-semibold" disabled
                                style="background:var(--gold);border-color:var(--gold);color:#fff;" title="Cannot save: parent options are required first">
                            <i class="bi bi-plus-lg me-1"></i>Save Option
                        </button>
                    @else
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold"
                                style="background:var(--gold);border-color:var(--gold);color:#fff;">
                            <i class="bi bi-plus-lg me-1"></i>Save Option
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-slug label → value
const addLabel = document.getElementById('addLabel');
const addValue = document.getElementById('addValue');
if (addLabel && addValue) {
    addLabel.addEventListener('input', function () {
        if (!addValue.dataset.edited) {
            addValue.value = this.value
                .toLowerCase()
                .replace(/[^a-z0-9\s_'\-]/g, '')
                .replace(/\s+/g, '_')
                .replace(/_+/g, '_');
        }
    });
    addValue.addEventListener('input', function () {
        this.dataset.edited = '1';
    });
}

// "Add Child" button logic — pre-fills parent in modal
function openAddChild(parentId, parentLabel) {
    const fixed  = document.getElementById('parentFixed');
    const select = document.getElementById('parentSelect');
    const hiddenInput = document.getElementById('parentIdFixed');
    const labelEl    = document.getElementById('parentLabelFixed');
    const titleEl    = document.getElementById('modalTitle');

    if (fixed && select && hiddenInput && labelEl) {
        hiddenInput.value    = parentId;
        labelEl.textContent  = parentLabel;
        fixed.classList.remove('d-none');
        select.classList.add('d-none');
        // disable the select so only the hidden field is submitted
        const sel = select.querySelector('select');
        if (sel) sel.disabled = true;
    }
    if (titleEl) titleEl.textContent = 'Add Child Option';

    // clear label/value inputs
    if (addLabel) { addLabel.value = ''; }
    if (addValue) { addValue.value = ''; delete addValue.dataset.edited; }

    new bootstrap.Modal(document.getElementById('addModal')).show();
}

function clearParent() {
    const fixed  = document.getElementById('parentFixed');
    const select = document.getElementById('parentSelect');
    const titleEl = document.getElementById('modalTitle');
    if (fixed)  fixed.classList.add('d-none');
    if (select) {
        select.classList.remove('d-none');
        const sel = select.querySelector('select');
        if (sel) { sel.disabled = false; sel.value = ''; }
    }
    if (titleEl) titleEl.textContent = 'Add Option';
}

// Reset modal state when it's dismissed
document.getElementById('addModal')?.addEventListener('hidden.bs.modal', function () {
    clearParent();
});

// Re-open add modal if validation failed
@if($errors->any() && old('group_key') === $group)
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });
@endif
</script>
@endpush
