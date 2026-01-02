(function () {
    "use strict";

    function initTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (el) {
            try {
                // Dispose existing tooltip instance if present
                var existingTooltip = bootstrap.Tooltip.getInstance(el);
                if (existingTooltip) {
                    existingTooltip.dispose();
                }
                // Create new tooltip with enhanced options
                new bootstrap.Tooltip(el, {
                    html: false,
                    trigger: 'hover focus',
                    delay: { show: 300, hide: 100 },
                    animation: true,
                    placement: 'auto'
                });
            } catch (e) { /* ignore init errors */ }
        });
    }

    function initSimpleSelects(root) {
        if (typeof TomSelect === 'undefined') return;
        (root || document).querySelectorAll('.simple-select').forEach(function (el) {
            if (!el._tomselect) {
                // eslint-disable-next-line no-new
                new TomSelect(el, {});
                el._tomselect = true;
            }
        });
    }

    function initTagSelects(root) {
        if (typeof TomSelect === 'undefined') return;
        (root || document).querySelectorAll('.tag-select').forEach(function (el) {
            if (!el._tomselect) {
                // eslint-disable-next-line no-new
                new TomSelect(el, {
                    placeholder: 'Select a tag...',
                    allowEmptyOption: true
                });
                el._tomselect = true;
            }
        });
    }

    // Loader helpers (simple show/hide with a fallback to the original UX)
    function showFullpageLoader() {
        var loader = document.getElementById('fullpage-loader');
        var loaderError = document.getElementById('loader-error');
        var loaderIcon = document.getElementById('loader-icon');

        if (loader) loader.style.display = 'block';
        if (loaderError) loaderError.style.display = 'none';
        if (loaderIcon) {
            loaderIcon.classList.add('fa-spin');
            loaderIcon.classList.remove('text-danger');
        }
        // set timeout to show error state if stays too long
        window.fullpageloaderTimeout = window.setTimeout(function () {
            if (loaderError) loaderError.style.display = 'block';
            if (loaderIcon) {
                loaderIcon.classList.remove('fa-spin');
                loaderIcon.classList.add('text-danger');
            }
        }, 10000);
    }

    function hideFullpageLoader() {
        var loader = document.getElementById('fullpage-loader');
        var loaderError = document.getElementById('loader-error');
        var loaderIcon = document.getElementById('loader-icon');

        if (loader) loader.style.display = 'none';
        if (loaderError) loader.style.display = 'none';
        if (loaderIcon) {
            loaderIcon.classList.add('fa-spin');
            loaderIcon.classList.remove('text-danger');
        }
        if (window.fullpageloaderTimeout) {
            clearTimeout(window.fullpageloaderTimeout);
            window.fullpageloaderTimeout = null;
        }
    }

    // Password meter input handling (uses zxcvbn if available)
    function initPasswordMeters() {
        var inputs = Array.from(document.querySelectorAll('.passwordmeter-input'));
        if (!inputs.length) return;
        inputs.forEach(function (password_input) {
            password_input.addEventListener('input', function () {
                var strength = null;
                try {
                    if (typeof zxcvbn === 'function') strength = zxcvbn(password_input.value);
                } catch (e) { strength = null; }
                // hide both by default
                Array.from(document.querySelectorAll('.passmeter-2, .passmeter-3')).forEach(function (el) {
                    el.style.display = 'none';
                });
                if (strength && typeof strength.score === 'number') {
                    if (strength.score === 4) {
                        Array.from(document.querySelectorAll('.passmeter-2, .passmeter-3')).forEach(function (el) {
                            el.style.display = '';
                        });
                    } else if (strength.score === 3) {
                        Array.from(document.querySelectorAll('.passmeter-2')).forEach(function (el) {
                            el.style.display = '';
                        });
                    }
                }
            }, { passive: true });
        });
    }

    // Delegated event handlers
    document.addEventListener('click', function (e) {
        var el = e.target;

        // ajax-loader: show fullpage loader
        if (el.closest && el.closest('.ajax-loader')) {
            showFullpageLoader();
            return;
        }

        // fullpage-loader-close: hide fullpage loader
        if (el.closest && el.closest('.fullpage-loader-close')) {
            hideFullpageLoader();
            return;
        }
    }, true);

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function () {
        initTooltips();
        initSimpleSelects();
        initTagSelects();
        initPasswordMeters();
        // ensure initial loader state is hidden
        hideFullpageLoader();
    });

    // In case script is loaded after DOMContentLoaded already fired
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // run init synchronously
        initTooltips();
        initSimpleSelects();
        initTagSelects();
        initPasswordMeters();
    }

    // Make functions globally available for other scripts
    window.initTooltips = initTooltips;
    window.initSimpleSelects = initSimpleSelects;
    window.initTagSelects = initTagSelects;
    window.showFullpageLoader = showFullpageLoader;
    window.hideFullpageLoader = hideFullpageLoader;

})();