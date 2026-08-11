{{-- Global Tom Select styles for admin panel --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-wrapper.form-select,
    .ts-wrapper.form-select-sm,
    .ts-wrapper.form-control {
        padding: 0;
        border: none;
        background: transparent;
    }

    .ts-wrapper .ts-control {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        min-height: 31px;
        font-size: 0.875rem;
        background: #fff;
    }

    .ts-wrapper.form-select .ts-control,
    .ts-wrapper.form-select-sm .ts-control {
        min-height: 31px;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    .ts-wrapper.focus .ts-control {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    .ts-dropdown {
        z-index: 1060;
    }
</style>
