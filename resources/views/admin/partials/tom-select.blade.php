{{-- Global Tom Select styles for admin panel --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    /*
     * Tom Select Bootstrap 5 theme removes borders from .ts-control when the
     * wrapper has .form-select — the border belongs on the wrapper instead.
     * Do not set border:none on the wrapper or the control will look borderless.
     */
    .ts-wrapper.form-select,
    .ts-wrapper.form-select-sm,
    .ts-wrapper.form-control {
        padding: 0;
        background: var(--bs-body-bg, #fff);
        border: var(--bs-border-width, 1px) solid var(--bs-border-color, #dee2e6);
        border-radius: var(--bs-border-radius, 0.375rem);
    }

    .ts-wrapper.form-select-sm {
        border-radius: var(--bs-border-radius-sm, 0.25rem);
    }

    .ts-wrapper.form-select .ts-control,
    .ts-wrapper.form-select-sm .ts-control,
    .ts-wrapper.form-control .ts-control {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        min-height: 31px;
        font-size: 0.875rem;
    }

    .ts-wrapper.form-select-sm .ts-control {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    .ts-wrapper.form-select.focus,
    .ts-wrapper.form-select-sm.focus,
    .ts-wrapper.form-control.focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .ts-dropdown {
        z-index: 1060;
    }
</style>
