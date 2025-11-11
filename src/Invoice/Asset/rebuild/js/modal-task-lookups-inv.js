(function () {
    "use strict";

    // Safe JSON parse helper (mirrors original parsedata)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Hide already-selected tasks (based on .item-task-id values)
    function hideSelectedTasks() {
        var selectedTasks = [];
        document.querySelectorAll('.item-task-id').forEach(function (el) {
            var currentVal = el.value || "";
            if (currentVal.length) selectedTasks.push(parseInt(currentVal, 10));
        });

        var hiddenTasks = 0;
        document.querySelectorAll('.modal-task-id').forEach(function (el) {
            var idAttr = el.id || "";
            var idNum = parseInt(idAttr.replace('task-id-', ''), 10);
            if (!Number.isNaN(idNum) && selectedTasks.indexOf(idNum) !== -1) {
                // hide the row containing this modal-task-id
                var row = el.closest('tr') || el.parentElement && el.parentElement.parentElement;
                if (row) row.style.display = 'none';
                hiddenTasks++;
            }
        });

        var taskRows = document.querySelectorAll('.task-row');
        if (hiddenTasks >= taskRows.length) {
            var submitBtn = document.getElementById('task-modal-submit');
            if (submitBtn) submitBtn.style.display = 'none';
        }
    }

    // Toggle checkbox when clicking on row (unless click was on checkbox)
    function rowClickToggle(event) {
        var row = event.target.closest('#tasks_table tr, .task-row, .task');
        if (!row) return;
        if (event.target.type !== 'checkbox') {
            var checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox) {
                // Toggle and dispatch change
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    // Enable/disable select button based on checked tasks
    function updateSelectTaskButtonState(root) {
        var ctx = root || document;
        var anyChecked = ctx.querySelectorAll("input[name='task_ids[]']:checked").length > 0;
        document.querySelectorAll('.select-items-confirm-task').forEach(function (btn) {
            if (anyChecked) {
                btn.removeAttribute('disabled');
                btn.removeAttribute('aria-disabled');
                btn.disabled = false;
            } else {
                btn.setAttribute('disabled', 'true');
                btn.setAttribute('aria-disabled', 'true');
                btn.disabled = true;
            }
        });
    }

    // Handle confirm click: collect selected task ids and send to server, then populate items and reload
    function handleSelectItemsConfirmTask(btn) {
        var absolute_url = new URL(location.href);
        var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);

        var task_ids = Array.from(document.querySelectorAll("input[name='task_ids[]']:checked"))
            .map(function (el) { return parseInt(el.value, 10); })
            .filter(Boolean);

        if (task_ids.length === 0) return;

        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
        btn.disabled = true;

        var params = new URLSearchParams();
        task_ids.forEach(function (id) { params.append('task_ids[]', id); });
        params.append('inv_id', inv_id);

        fetch('/invoice/task/selection_inv?' + params.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                return res.text();
            })
            .then(function (text) {
                var data;
                try { data = JSON.parse(text); } catch (e) { data = text; }
                var tasks = parsedata(data);

                var productDefaultTaxRateId = null;
                var currentTaxRateId = null;

                for (var key in tasks) {
                    if (!Object.prototype.hasOwnProperty.call(tasks, key)) continue;
                    currentTaxRateId = tasks[key].tax_rate_id;
                    if (!currentTaxRateId) {
                        var defaultTaxEl = document.getElementById('default_item_tax_rate') || document.querySelector('#default_item_tax_rate');
                        productDefaultTaxRateId = defaultTaxEl ? defaultTaxEl.getAttribute('value') : '';
                    } else {
                        productDefaultTaxRateId = currentTaxRateId;
                    }

                    // Find last item row (matching original behaviour)
                    var last_tbody = document.querySelector('#item_table tbody:last-of-type') || document.querySelector('#item_table tbody');
                    if (!last_tbody) continue;

                    var nameEl = last_tbody.querySelector('input[name="item_name"]');
                    var descEl = last_tbody.querySelector('textarea[name="item_description"]');
                    var priceEl = last_tbody.querySelector('input[name="item_price"]');
                    var qtyEl = last_tbody.querySelector('input[name="item_quantity"]');
                    var taxEl = last_tbody.querySelector('select[name="item_tax_rate_id"]');
                    var taskIdEl = last_tbody.querySelector('input[name="item_task_id"]');

                    if (nameEl) nameEl.value = tasks[key].name || '';
                    if (descEl) descEl.value = tasks[key].description || '';
                    if (priceEl) priceEl.value = tasks[key].price || '';
                    if (qtyEl) qtyEl.value = '1';
                    if (taxEl) taxEl.value = productDefaultTaxRateId || '';
                    if (taskIdEl) taskIdEl.value = tasks[key].id || '';

                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                }

                // Reload as original code does to sync state
                location.reload(true);
            })
            .catch(function (err) {
                console.error('selection_inv failed', err);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('An error occurred while adding tasks to invoice. See console for details.');
            });
    }

    // Delegated event handling
    document.addEventListener('click', function (e) {
        var el = e.target;

        // Row toggle
        if (el.closest('#tasks_table tr, .task, .task-row')) {
            rowClickToggle(e);
            return;
        }

        // Confirm select tasks
        var confirmTask = el.closest('.select-items-confirm-task');
        if (confirmTask) {
            handleSelectItemsConfirmTask(confirmTask);
            return;
        }

        // Reset / load actions: these can be adapted if project loads the table via URL
        if (el.closest('#task-reset-button-inv')) {
            var product_table = document.querySelector('#tasks_table');
            if (product_table) {
                var lookup_url = location.origin + "/invoice/task/lookup";
                product_table.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                lookup_url += "?rt=true";
                setTimeout(function () {
                    // inline replacement for $.load: fetch and insert
                    fetch(lookup_url, { cache: 'no-store', credentials: 'same-origin' })
                        .then(function (r) { return r.text(); })
                        .then(function (html) { 
                            // Secure HTML insertion using DOMParser to prevent XSS
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const fragment = document.createDocumentFragment();
                            Array.from(doc.body.children).forEach(child => fragment.appendChild(child));
                            product_table.innerHTML = '';
                            product_table.appendChild(fragment);
                            updateSelectTaskButtonState(product_table); 
                        })
                        .catch(function (err) { console.error('task lookup load failed', err); });
                }, 50);
            }
            // enable button after reset
            document.querySelectorAll('.select-items-confirm-task').forEach(function (b) { b.removeAttribute('disabled'); });
            return;
        }
    }, true);

    // Delegated change handling (checkboxes / family selects)
    document.addEventListener('change', function (e) {
        var target = e.target;
        if (!target) return;

        if (target.matches("input[name='task_ids[]']")) {
            updateSelectTaskButtonState(target.closest('#tasks_table') || document);
            return;
        }
    }, true);

    // Bind Enter to search if needed (mirrors original keypress)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            var active = document.activeElement;
            if (active && active.id === 'filter_task_inv') {
                var btn = document.getElementById('filter-button-inv');
                if (btn) btn.click();
                e.preventDefault();
            }
        }
    }, true);

    // Initial run on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        hideSelectedTasks();
        updateSelectTaskButtonState();
    });

    // In case script is loaded after DOMContentLoaded
    hideSelectedTasks();
    updateSelectTaskButtonState();

})();