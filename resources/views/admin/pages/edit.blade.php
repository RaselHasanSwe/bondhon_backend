@extends('admin.layout')
@section('title', 'Edit Page — ' . $page->title)
@section('page-title', 'Edit Page: ' . $page->title)

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-9">
        <form method="POST" action="{{ route('admin.web.pages.update', $page->id) }}">
            @csrf
            @method('PUT')

            <div class="table-card p-4 mb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" required
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $page->title) }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $page->sort_order) }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
                                   {{ old('is_published', $page->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="is_published">Published</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Content</label>
                        <textarea id="content" name="content" rows="20"
                                  class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="table-card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color:var(--gold)"><i class="bi bi-search me-2"></i>SEO</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Meta Title <small class="text-muted">(max 160 chars)</small></label>
                        <input type="text" name="meta_title" maxlength="160"
                               class="form-control @error('meta_title') is-invalid @enderror"
                               value="{{ old('meta_title', $page->meta_title) }}">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Meta Description <small class="text-muted">(max 320 chars)</small></label>
                        <textarea name="meta_description" maxlength="320" rows="2"
                                  class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $page->meta_description) }}</textarea>
                        @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning fw-semibold px-4" style="background:var(--gold);border-color:var(--gold);color:#fff;">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
                <a href="{{ route('admin.web.pages') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <div class="col-12 col-xl-3">
        <div class="table-card p-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Page Info</h6>
            <dl class="small mb-0">
                <dt class="text-muted">Slug</dt>
                <dd><code>{{ $page->slug }}</code></dd>
                <dt class="text-muted">Public URL (frontend)</dt>
                <dd class="text-break"><small>/{{ str_replace('_', '-', $page->slug) }}</small></dd>
                <dt class="text-muted">API Endpoint</dt>
                <dd class="text-break"><small>/api/v1/pages/{{ $page->slug }}</small></dd>
                <dt class="text-muted">Created</dt>
                <dd>{{ $page->created_at->format('d M Y') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    height: 600,
    menubar: 'file edit view insert format tools table',
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | ' +
             'alignleft aligncenter alignright alignjustify | ' +
             'bullist numlist outdent indent | link image table | code fullscreen | help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 15px; }',
    skin: 'oxide',
    content_css: 'default',
});
</script>
@endsection

