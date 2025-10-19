(function () {
    "use strict";

    // Utility: parsedata equivalent used across other scripts
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try {
                const obj = JSON.parse(data);
                return obj && typeof obj === 'object' ? obj : {};
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    // Delegated click handlers that previously used jQuery
    document.addEventListener('click', function (e) {
        const target = e.target;

        // inv_to_pdf_confirm_with_custom_fields
        if (target.matches('#inv_to_pdf_confirm_with_custom_fields') || target.closest('#inv_to_pdf_confirm_with_custom_fields')) {
            const url = location.origin + "/invoice/inv/pdf/1";
            // Open in new tab
            window.open(url, '_blank');
            return;
        }

        if (target.matches('#inv_to_pdf_confirm_without_custom_fields') || target.closest('#inv_to_pdf_confirm_without_custom_fields')) {
            const url = location.origin + "/invoice/inv/pdf/0";
            window.open(url, '_blank');
            return;
        }

        if (target.matches('#inv_to_modal_pdf_confirm_with_custom_fields') || target.closest('#inv_to_modal_pdf_confirm_with_custom_fields')) {
            const url = location.origin + "/invoice/inv/pdf/1";
            const iframe = document.getElementById('modal-view-inv-pdf');
            if (iframe) iframe.setAttribute('src', url);
            // Show bootstrap modal if available
            const modalEl = document.getElementById('modal-layout-modal-pdf-inv');
            if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
            return;
        }

        if (target.matches('#inv_to_modal_pdf_confirm_without_custom_fields') || target.closest('#inv_to_modal_pdf_confirm_without_custom_fields')) {
            const url = location.origin + "/invoice/inv/pdf/0";
            const iframe = document.getElementById('modal-view-inv-pdf');
            if (iframe) iframe.setAttribute('src', url);
            const modalEl = document.getElementById('modal-layout-modal-pdf-inv');
            if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
            return;
        }
    });

    // Keep behaviour for discount inputs: convert jQuery keyup handlers to input event
    const invDiscountAmount = document.getElementById('inv_discount_amount');
    const invDiscountPercent = document.getElementById('inv_discount_percent');
    if (invDiscountAmount) {
        invDiscountAmount.addEventListener('input', function () {
            if (this.value.length > 0) {
                if (invDiscountPercent) {
                    invDiscountPercent.value = '0.00';
                    invDiscountPercent.disabled = true;
                }
            } else if (invDiscountPercent) {
                invDiscountPercent.disabled = false;
            }
        });
    }
    if (invDiscountPercent) {
        invDiscountPercent.addEventListener('input', function () {
            if (this.value.length > 0) {
                if (invDiscountAmount) {
                    invDiscountAmount.value = '0.00';
                    invDiscountAmount.disabled = true;
                }
            } else if (invDiscountAmount) {
                invDiscountAmount.disabled = false;
            }
        });
    }

    // NOTE: Previously the code initialised jQuery UI datepicker on focus.
    // To keep things basic and dependency-free we will prefer native HTML date inputs.
    // If you still want an enhanced cross-browser picker later, consider adding flatpickr.
    // For now, do nothing here; native <input type="date"> will handle picking on modern browsers.

    // If there is legacy code which expects a particular display format, ensure server-side
    // reading expects ISO-8601 (yyyy-mm-dd) from these inputs.

    // For elements that were using a class 'datepicker' we provide a minimal shim to
    // optionally initialise flatpickr if present (non-invasive):
    document.addEventListener('focusin', function (e) {
        const el = e.target;
        if (!el || !el.classList) return;
        if (el.classList.contains('datepicker')) {
            if (typeof flatpickr !== 'undefined') {
                // Initialize flatpickr if the page included it; preserve a sensible format
                if (!el._flatpickr) {
                    flatpickr(el, {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                        // Keep flatpickr usage minimal; recommend migrating formats server-side
                    });
                }
            }
            // Otherwise: do nothing and rely on native input[type="date"] if used.
        }
    });

    // Minimal helpers for opening URLs in new windows (used elsewhere)
    window.invoiceHelpers = window.invoiceHelpers || {};
    window.invoiceHelpers.openUrlInNewTab = function (url) {
        window.open(url, '_blank');
    };

})();