@extends('admin.layout')
@section('title', 'Photo Moderation')
@section('page-title', 'Photo Moderation Queue')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <p class="mb-0 text-muted small">
        Showing <strong>{{ $photos->total() }}</strong> pending photo(s) awaiting review.
    </p>
</div>

@if($photos->isEmpty())
<div class="table-card p-5 text-center text-muted">
    <i class="bi bi-check-circle fs-1 text-success d-block mb-3"></i>
    <h5>All caught up!</h5>
    <p>No photos are pending moderation.</p>
</div>
@else
<div class="row g-3">
    @foreach($photos as $photo)
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="stat-card h-100 d-flex flex-column">
            {{-- Photo --}}
            <div class="ratio ratio-1x1 mb-3" style="border-radius:8px;overflow:hidden;background:#f1f1f1;">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}"
                     alt="Photo {{ $photo->id }}"
                     style="object-fit:cover;width:100%;height:100%;"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
            </div>

            {{-- User info --}}
            <div class="mb-3">
                <div class="fw-semibold small">{{ $photo->user?->name ?? '—' }}</div>
                <div class="text-muted small text-truncate">{{ $photo->user?->email ?? '—' }}</div>
                <div class="text-muted" style="font-size:11px;">Submitted {{ $photo->created_at->diffForHumans() }}</div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 mt-auto">
                <form method="POST" action="{{ route('admin.web.photos.action', $photo->id) }}" class="flex-fill">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-sm btn-success w-100">
                        <i class="bi bi-check-lg me-1"></i>Approve
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-danger flex-fill"
                        onclick="showRejectModal({{ $photo->id }})">
                    <i class="bi bi-x-lg me-1"></i>Reject
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $photos->links() }}
</div>
@endif

{{-- Reject Reason Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" id="rejectForm">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="modal-header">
                    <h6 class="modal-title">Reject Photo</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold small">Reason <small class="text-muted">(optional)</small></label>
                    <textarea name="reason" rows="3" class="form-control"
                              placeholder="e.g. Inappropriate content, not a real photo…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showRejectModal(photoId) {
    document.getElementById('rejectForm').action =
        '{{ route("admin.web.photos.action", "__ID__") }}'.replace('__ID__', photoId);
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection

