@extends('admin.layout')
@section('title', 'Edit Option — ' . $option->label)
@section('page-title', 'Edit Select Option')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-6">
        <div class="table-card p-4">

            {{-- Breadcrumb --}}
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.web.select-options.index') }}" class="text-decoration-none">Select Options</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.web.select-options.index', ['group' => $option->group_key]) }}" class="text-decoration-none">
                            {{ $groups[$option->group_key] ?? $option->group_key }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Edit #{{ $option->id }}</li>
                </ol>
            </nav>

            <h5 class="fw-bold mb-1" style="color:var(--gold)">
                <i class="bi bi-pencil me-2"></i>Edit Option
            </h5>
            <p class="text-muted small mb-4">
                Group: <code>{{ $option->group_key }}</code>
                &nbsp;·&nbsp;
                Value: <code>{{ $option->value }}</code>
                <span class="text-warning ms-2">(value cannot be changed)</span>
                @if($isSelfNested)
                    &nbsp;·&nbsp;<span class="badge bg-secondary">Self-nested tree</span>
                @elseif($parentGroupKey)
                    &nbsp;·&nbsp;<span class="badge bg-info text-dark">Children of: {{ $groups[$parentGroupKey] ?? $parentGroupKey }}</span>
                @endif
            </p>

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

            <form method="POST" action="{{ route('admin.web.select-options.update', $option->id) }}">
                @csrf @method('PUT')

                {{-- Group selector --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Option Group <span class="text-danger">*</span>
                    </label>
                    <select name="group_key" class="form-select form-select-sm" id="editGroupKey"
                            onchange="handleGroupChange(this.value)">
                        @foreach($groups as $key => $label)
                            <option value="{{ $key }}" {{ old('group_key', $option->group_key) === $key ? 'selected' : '' }}>
                                {{ $label }} ({{ $key }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Moving to a different group will clear the parent assignment.</small>
                </div>

                {{-- Parent selector — cross-nested (parent in a different group) --}}
                @if($parentGroupKey && !$isSelfNested && $parentOptions->count())
                <div class="mb-3">
                    <label class="form-label fw-semibold small">
                        Parent — {{ $groups[$parentGroupKey] ?? $parentGroupKey }}
                    </label>
                    <select name="parent_id" class="form-select form-select-sm">
                        <option value="">— None —</option>
                        @foreach($parentOptions as $p)
                            <option value="{{ $p->id }}" {{ $option->parent_id == $p->id ? 'selected' : '' }}>
                                {{ $p->label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Which {{ $groups[$parentGroupKey] ?? $parentGroupKey }} does this belong to?</small>
                </div>
                @endif

                {{-- Parent selector — self-nested (parent in same group) --}}
                @if($isSelfNested && $parentOptions->count())
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Parent (optional — leave blank for root level)</label>
                    <select name="parent_id" class="form-select form-select-sm">
                        <option value="">— Root level (no parent) —</option>
                        @foreach($parentOptions as $p)
                            <option value="{{ $p->id }}" {{ $option->parent_id == $p->id ? 'selected' : '' }}>
                                {{ str_repeat('— ', $p->_depth ?? 0) }}{{ $p->label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Cannot select self or any descendant as parent.</small>
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                           value="{{ old('label', $option->label) }}" required maxlength="255">
                    @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">What users see in the dropdown.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Value (read-only)</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $option->value }}" readonly>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               value="{{ old('sort_order', $option->sort_order) }}" min="0">
                    </div>
                    <div class="col-6 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive"
                                   {{ $option->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="editIsActive">Active (visible in dropdowns)</label>
                        </div>
                    </div>
                </div>

                {{-- Metadata (display only) --}}
                @if($option->metadata)
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Metadata (read-only)</label>
                    <pre class="bg-light rounded p-2 small mb-0" style="font-size:.78rem">{{ json_encode($option->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning fw-semibold px-4"
                            style="background:var(--gold);border-color:var(--gold);color:#fff;">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                    <a href="{{ route('admin.web.select-options.index', ['group' => $option->group_key]) }}"
                       class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>

            {{-- Danger zone --}}
            <hr class="my-4">
            @php $childCount = \App\Models\SelectOption::where('parent_id', $option->id)->count(); @endphp
            <div class="p-3 rounded" style="border:1px solid #fee2e2;background:#fff5f5;">
                <h6 class="text-danger fw-bold mb-1">
                    <i class="bi bi-exclamation-triangle me-1"></i>Danger Zone
                </h6>
                <p class="small text-muted mb-2">
                    Deleting this option is permanent.
                    @if($childCount > 0)
                        <strong class="text-danger">This option has {{ $childCount }} child option(s) that will also be deleted.</strong>
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.web.select-options.destroy', $option->id) }}"
                      onsubmit="return confirm('Permanently delete \'{{ addslashes($option->label) }}\'?{{ $childCount > 0 ? ' This will also delete ' . $childCount . ' child option(s).' : '' }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Option
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- Side info panel --}}
    <div class="col-12 col-lg-4 col-xl-3">
        <div class="table-card p-3 mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>About this Option</h6>
            <table class="table table-sm table-borderless mb-0 small">
                <tr><td class="text-muted">ID</td><td><strong>{{ $option->id }}</strong></td></tr>
                <tr><td class="text-muted">Group</td><td><code>{{ $option->group_key }}</code></td></tr>
                <tr><td class="text-muted">Group Label</td><td>{{ $groups[$option->group_key] ?? '—' }}</td></tr>
                <tr><td class="text-muted">Value</td><td><code>{{ $option->value }}</code></td></tr>
                <tr>
                    <td class="text-muted">Parent</td>
                    <td>
                        @if($option->parent)
                            <a href="{{ route('admin.web.select-options.edit', $option->parent_id) }}" class="text-decoration-none small">
                                {{ $option->parent->label }}
                            </a>
                        @else
                            <span class="text-muted">— root —</span>
                        @endif
                    </td>
                </tr>
                <tr><td class="text-muted">Children</td><td>{{ $childCount }}</td></tr>
                <tr><td class="text-muted">Sort</td><td>{{ $option->sort_order }}</td></tr>
                <tr><td class="text-muted">Created</td><td>{{ $option->created_at->format('d M Y') }}</td></tr>
                <tr><td class="text-muted">Updated</td><td>{{ $option->updated_at->format('d M Y') }}</td></tr>
            </table>
        </div>

        {{-- Group nesting diagram --}}
        @if($parentGroupKey)
        <div class="table-card p-3 mb-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-diagram-3 me-2 text-secondary"></i>Nesting Structure</h6>
            <div class="small text-muted">
                @if($isSelfNested)
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-secondary">{{ $groups[$option->group_key] ?? $option->group_key }}</span>
                        <i class="bi bi-arrow-return-right"></i>
                        <span class="badge bg-secondary">{{ $groups[$option->group_key] ?? $option->group_key }}</span>
                        <small>(self)</small>
                    </div>
                    <p class="mb-0">Options in this group can be nested within each other.</p>
                @else
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-info text-dark">{{ $groups[$parentGroupKey] ?? $parentGroupKey }}</span>
                        <i class="bi bi-arrow-right"></i>
                        <span class="badge bg-warning text-dark">{{ $groups[$option->group_key] ?? $option->group_key }}</span>
                    </div>
                    <p class="mb-0">Each <strong>{{ $groups[$option->group_key] ?? $option->group_key }}</strong> belongs to a <strong>{{ $groups[$parentGroupKey] ?? $parentGroupKey }}</strong>.</p>
                @endif
            </div>
        </div>
        @endif

        <div class="table-card p-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb me-2 text-warning"></i>Tip</h6>
            <p class="small text-muted mb-0">
                <strong>Deactivating</strong> hides the option from user-facing dropdowns but keeps old saved data valid.
                <br><br>
                <strong>Deleting</strong> permanently removes the option and all its children. Old saved data may show the raw value key.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function handleGroupChange(newGroup) {
    const currentGroup = '{{ $option->group_key }}';
    if (newGroup !== currentGroup) {
        const parentField = document.querySelector('select[name="parent_id"]');
        if (parentField) {
            parentField.value = '';
            const note = document.getElementById('groupChangeNote');
            if (note) note.style.display = '';
        }
    }
}
</script>
@endpush

