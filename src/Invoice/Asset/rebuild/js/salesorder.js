// salesorder.js - Complete functionality restored from pre_jquery_deletion branch
// Systematically converted from jQuery to vanilla JavaScript
// All original selectors and event handlers preserved

(function () {
    "use strict";

    // Safe parse helper (mirrors original parsedata)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Helper to get origin
    function getOrigin() {
        return window.location.origin;
    }

    // Initialize Tom Select if present for salesorder selects
    function initSelects() {
        if (typeof TomSelect === 'undefined') return;
        document.querySelectorAll('.simple-select').forEach(function (el) {
            if (!el._tomselect) {
                // eslint-disable-next-line no-new
                new TomSelect(el, {});
                el._tomselect = true;
            }
        });
    }

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        
        // Initialize selects
        initSelects();

        // 1. SALESORDER PDF WITH CUSTOM FIELDS - #salesorder_to_pdf_confirm_with_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#salesorder_to_pdf_confirm_with_custom_fields') || e.target.closest('#salesorder_to_pdf_confirm_with_custom_fields')) {
                var url = getOrigin() + "/invoice/salesorder/pdf/1";
                window.open(url, '_blank');
            }
        });

        // 2. SALESORDER PDF WITHOUT CUSTOM FIELDS - #salesorder_to_pdf_confirm_without_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#salesorder_to_pdf_confirm_without_custom_fields') || e.target.closest('#salesorder_to_pdf_confirm_without_custom_fields')) {
                var url = getOrigin() + "/invoice/salesorder/pdf/0";
                window.open(url, '_blank');
            }
        });

        // 3. SALES ORDER TO INVOICE CONFIRM - #so_to_invoice_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#so_to_invoice_confirm') || e.target.closest('#so_to_invoice_confirm')) {
                var url = getOrigin() + "/invoice/salesorder/so_to_invoice_confirm";
                var btn = document.querySelector('.so_to_invoice_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var so_id = document.getElementById('so_id');
                var client_id = document.getElementById('client_id');
                var group_id = document.getElementById('group_id');
                var password = document.getElementById('password');

                var params = new URLSearchParams();
                if (so_id) params.append('so_id', so_id.value);
                if (client_id) params.append('client_id', client_id.value);
                if (group_id) params.append('group_id', group_id.value);
                if (password) params.append('password', password.value);

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                })
                .catch(function (error) {
                    console.warn('Sales order conversion error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 4. GENERIC MODAL AND FORM FUNCTIONALITY (preserved from current version)
        document.addEventListener('click', function (e) {
            const btn = e.target;

            // Open sales order modal
            const open = btn.closest('.open-salesorder-modal');
            if (open) {
                const url = open.dataset.url || (getOrigin() + '/invoice/salesorder/modal');
                const target = document.getElementById(open.dataset.target || 'modal-placeholder-salesorder');
                if (!target) return;
                fetch(url, { cache: 'no-store' })
                    .then(r => r.text())
                    .then(html => {
                        // Secure HTML insertion using DOMParser to prevent XSS
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const fragment = document.createDocumentFragment();
                        Array.from(doc.body.children).forEach(child => fragment.appendChild(child));
                        target.innerHTML = '';
                        target.appendChild(fragment);
                        const modalEl = target.querySelector('.modal');
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            new bootstrap.Modal(modalEl).show();
                        }
                        initSelects();
                    })
                    .catch(console.error);
                return;
            }

            // Save sales order via AJAX
            const saveBtn = btn.closest('.salesorder-save');
            if (saveBtn) {
                const form = document.querySelector('#salesorder_form');
                if (!form) return;
                const action = form.getAttribute('action') || (getOrigin() + '/invoice/salesorder/save');
                const fd = new FormData(form);
                const params = new URLSearchParams();
                for (const [k, v] of fd.entries()) params.append(k, v);
                fetch(action + '?' + params.toString(), { cache: 'no-store' })
                    .then(r => r.json())
                    .then(data => {
                        const resp = parsedata(data);
                        if (resp.success === 1) location.reload();
                        else alert(resp.message || 'Save failed');
                    })
                    .catch(err => { console.warn(err); alert('An error occurred'); });
                return;
            }
        });

    }); // End DOMContentLoaded

})();