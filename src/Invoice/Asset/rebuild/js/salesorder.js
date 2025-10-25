(function () {
    "use strict";

    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
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

    document.addEventListener('DOMContentLoaded', initSelects);

    document.addEventListener('click', function (e) {
        const btn = e.target;

        // Open sales order modal
        const open = btn.closest('.open-salesorder-modal');
        if (open) {
            const url = open.dataset.url || (location.origin + '/invoice/salesorder/modal');
            const target = document.getElementById(open.dataset.target || 'modal-placeholder-salesorder');
            if (!target) return;
            fetch(url, { cache: 'no-store' })
                .then(r => r.text())
                .then(html => {
                    target.innerHTML = html;
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
            const action = form.getAttribute('action') || (location.origin + '/invoice/salesorder/save');
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

})();