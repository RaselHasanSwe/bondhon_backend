/**
 * Global Tom Select helpers for Bondhon admin panel.
 * Include via admin layout; mark selects with data-tom-select.
 */
(function (window, document) {
    'use strict';

    const TOM_SELECT_VERSION = '2.3.1';
    const TOM_SELECT_CDN = `https://cdn.jsdelivr.net/npm/tom-select@${TOM_SELECT_VERSION}/dist`;

    const instances = new WeakMap();

    const defaultOptions = {
        create: false,
        allowEmptyOption: true,
        plugins: ['dropdown_input'],
        maxOptions: null,
    };

    function mergeOptions(base, extra) {
        return Object.assign({}, base, extra || {});
    }

    function buildOptions(el) {
        const options = mergeOptions(defaultOptions);

        if (el.dataset.tomSelectPlaceholder) {
            options.placeholder = el.dataset.tomSelectPlaceholder;
        }

        if (el.hasAttribute('data-tom-select-search')) {
            options.plugins = ['dropdown_input'];
        }

        if (el.hasAttribute('data-tom-select-clear')) {
            options.plugins = (options.plugins || []).concat(['clear_button']);
        }

        if (el.dataset.tomSelectNavigate !== undefined) {
            options.onChange = function (value) {
                if (value) {
                    window.location.href = value;
                }
            };
        }

        if (el.dataset.tomSelectConfig) {
            try {
                const parsed = JSON.parse(el.dataset.tomSelectConfig);
                Object.assign(options, parsed);
            } catch (error) {
                console.warn('Invalid data-tom-select-config JSON on', el, error);
            }
        }

        return options;
    }

    function init(el, customOptions) {
        if (!el || el.tagName !== 'SELECT' || instances.has(el)) {
            return instances.get(el) || null;
        }

        if (typeof window.TomSelect === 'undefined') {
            console.warn('TomSelect is not loaded.');
            return null;
        }

        const options = mergeOptions(buildOptions(el), customOptions);
        const instance = new window.TomSelect(el, options);
        instances.set(el, instance);

        return instance;
    }

    function destroy(el) {
        const instance = instances.get(el);
        if (!instance) {
            return;
        }

        instance.destroy();
        instances.delete(el);
    }

    function initAll(root) {
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('select[data-tom-select]').forEach(function (el) {
            if (!instances.has(el) && !el.disabled && el.offsetParent !== null) {
                init(el);
            }
        });
    }

    function refresh(el) {
        destroy(el);
        return init(el);
    }

    window.AdminTomSelect = {
        version: TOM_SELECT_VERSION,
        cdn: TOM_SELECT_CDN,
        init,
        destroy,
        refresh,
        initAll,
        get: function (el) {
            return instances.get(el) || null;
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    document.addEventListener('shown.bs.modal', function (event) {
        initAll(event.target);
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        event.target.querySelectorAll('select[data-tom-select]').forEach(function (el) {
            destroy(el);
        });
    });
})(window, document);
