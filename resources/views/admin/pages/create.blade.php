@extends('admin.layout')
@section('title', 'Create Page')
@section('page-title', 'Create Page')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-9">
        <form id="page-edit-form" method="POST" action="{{ route('admin.web.pages.store') }}">
            @csrf
            @method('POST')

            <div class="table-card p-4 mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" required
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order') }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2 me-3">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
                                   {{ old('is_published') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="is_published">Published</label>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="show_in_menu" name="show_in_menu"
                                   {{ old('show_in_menu') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="show_in_menu">Show In Website Menu</label>
                        </div>

                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Content</label>
                        <textarea id="content" name="content" rows="20"
                                  class="form-control @error('content') is-invalid @enderror">{!! old('content') !!}</textarea>
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
                               value="{{ old('meta_title') }}">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Meta Description <small class="text-muted">(max 320 chars)</small></label>
                        <textarea name="meta_description" maxlength="320" rows="2"
                                  class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description') }}</textarea>
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

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/suneditor@2.47.3/dist/css/suneditor.min.css">
@endpush

@section('scripts')
{{-- SunEditor — free, open-source rich text editor --}}
<script src="https://cdn.jsdelivr.net/npm/suneditor@2.47.3/dist/suneditor.min.js"></script>
<script>
(function () {
    var textarea = document.getElementById('content');
    if (!textarea) {
        console.error('[SunEditor] textarea#content not found');
        return;
    }

    var editor = SUNEDITOR.create(textarea, {
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

    /*
     * CRITICAL FIX: Use the specific form ID (#page-edit-form) rather than
     * document.querySelector('form') which would incorrectly select the sidebar
     * logout form that appears first in the DOM.
     * We also call editor.save() synchronously before the browser collects
     * form field values so the textarea gets the latest HTML content.
     */
    var pageForm = document.getElementById('page-edit-form');
    if (pageForm) {
        pageForm.addEventListener('submit', function (e) {
            editor.save();          // syncs editor HTML → textarea.value
        });
    }
})();
</script>
@endsection

