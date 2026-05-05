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
                                  class="form-control @error('content') is-invalid @enderror">{!! old('content', $page->content) !!}</textarea>
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/css/suneditor.min.css">
@endpush

@section('scripts')
{{-- SunEditor — free, open-source rich text editor --}}
<script src="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/suneditor.min.js"></script>
<script>
(function () {
    var editor = SUNEDITOR.create(document.getElementById('content'), {
        height       : 600,
        width        : '100%',
        defaultStyle : 'font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 15px; color: #1F2937;',
        buttonList   : [
            ['undo', 'redo'],
            ['font', 'fontSize', 'formatBlock'],
            ['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
            ['fontColor', 'hiliteColor', 'textStyle'],
            ['removeFormat'],
            ['outdent', 'indent'],
            ['align', 'horizontalRule', 'list', 'lineHeight'],
            ['table', 'link', 'image', 'video'],
            ['fullScreen', 'showBlocks', 'codeView'],
            ['preview', 'print'],
        ],
        /* Allow extra tags used by the frontend prose renderer */
        addTagsWhitelist : 'figure|figcaption|details|summary|mark|kbd|s|del|ins|sup|sub',
        /* Keep class/id/style attributes so FAQ faq-item divs and custom classes survive saves */
        attributesWhitelist : {
            'all' : 'class|id|style'
        },
        imageFileInput : false,
        imageUrlInput  : true,
    });

    /* Sync the hidden textarea value before submit so the form sends the HTML */
    document.querySelector('form').addEventListener('submit', function () {
        editor.save();
    });
})();
</script>
@endsection

