(function () {
    "use strict";

    // Parse JSON response safely (mirrors original parsedata)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Helper: enable/disable select buttons based on product checkboxes
    function updateSelectButtonsState(root) {
        var anyChecked = (root || document).querySelectorAll("input[name='product_ids[]']:checked").length > 0;
        document.querySelectorAll('.select-items-confirm-quote, .select-items-confirm-inv').forEach(function (btn) {
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

    // Initialize Tom Select replacement for .simple-select if TomSelect is present
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

    // Utility: fetch HTML and place into an element
    function loadHtmlInto(url, element) {
        if (!element) return Promise.reject(new Error('No element to load into'));
        element.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        return fetch(url, { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('Network response was not ok: ' + r.status);
                return r.text();
            })
            .then(function (html) {
                element.innerHTML = html;
                initSimpleSelects(element);
                // After inserting content, ensure select buttons state is correct
                updateSelectButtonsState(element);
                return html;
            });
    }

    // CLICK: handle confirm for quote selection
    function handleSelectItemsConfirmQuote(btn) {
        var absolute_url = new URL(location.href);
        var product_ids = Array.from(document.querySelectorAll("input[name='product_ids[]']:checked")).map(function (el) {
            return parseInt(el.value, 10);
        }).filter(Boolean);
        var quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);

        if (product_ids.length === 0) return;

        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
        btn.disabled = true;

        var params = new URLSearchParams();
        product_ids.forEach(function (id) { params.append('product_ids[]', id); });
        params.append('quote_id', quote_id);

        fetch('/invoice/product/selection_quote?' + params.toString(), {
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
                var products = parsedata(data);
                var productDefaultTaxRateId = null;
                var currentTaxRateId = null;

                for (var key in products) {
                    if (!Object.prototype.hasOwnProperty.call(products, key)) continue;
                    currentTaxRateId = products[key].tax_rate_id;
                    if (!currentTaxRateId) {
                        var defaultTaxEl = document.getElementById('default_item_tax_rate') || document.querySelector('#default_item_tax_rate');
                        productDefaultTaxRateId = defaultTaxEl ? defaultTaxEl.getAttribute('value') : '';
                    } else {
                        productDefaultTaxRateId = currentTaxRateId;
                    }

                    var last_tbody = document.querySelector('#item_table tbody:last-of-type') || document.querySelector('#item_table tbody');
                    if (!last_tbody) continue;

                    // find inputs inside the last tbody (mirrors original intent)
                    var nameEl = last_tbody.querySelector('input[name="item_name"]');
                    var descEl = last_tbody.querySelector('textarea[name="item_description"]');
                    var priceEl = last_tbody.querySelector('input[name="item_price"]');
                    var qtyEl = last_tbody.querySelector('input[name="item_quantity"]');
                    var taxEl = last_tbody.querySelector('select[name="item_tax_rate_id"]');
                    var prodIdEl = last_tbody.querySelector('input[name="item_product_id"]');
                    var unitEl = last_tbody.querySelector('select[name="item_product_unit_id"]');

                    if (nameEl) nameEl.value = products[key].product_name || '';
                    if (descEl) descEl.value = products[key].product_description || '';
                    if (priceEl) priceEl.value = products[key].product_price || '';
                    if (qtyEl) qtyEl.value = '1';
                    if (taxEl) taxEl.value = productDefaultTaxRateId || '';
                    if (prodIdEl) prodIdEl.value = products[key].id || '';
                    if (unitEl) unitEl.value = products[key].unit_id || '';

                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                }

                // Mirror original behavior: reload to ensure server/client are in sync
                location.reload(true);
            })
            .catch(function (err) {
                console.error('selection_quote failed', err);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('An error occurred while adding products to quote. See console for details.');
            });
    }

    // CLICK: handle confirm for invoice selection
    function handleSelectItemsConfirmInv(btn) {
        var absolute_url = new URL(location.href);
        var product_ids = Array.from(document.querySelectorAll("input[name='product_ids[]']:checked")).map(function (el) {
            return parseInt(el.value, 10);
        }).filter(Boolean);
        var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);

        if (product_ids.length === 0) return;

        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
        btn.disabled = true;

        var params = new URLSearchParams();
        product_ids.forEach(function (id) { params.append('product_ids[]', id); });
        params.append('inv_id', inv_id);

        fetch('/invoice/product/selection_inv?' + params.toString(), {
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
                var products = parsedata(data);
                var productDefaultTaxRateId = null;
                var currentTaxRateId = null;

                for (var key in products) {
                    if (!Object.prototype.hasOwnProperty.call(products, key)) continue;
                    currentTaxRateId = products[key].tax_rate_id;
                    if (!currentTaxRateId) {
                        var defaultTaxEl = document.getElementById('default_item_tax_rate') || document.querySelector('#default_item_tax_rate');
                        productDefaultTaxRateId = defaultTaxEl ? defaultTaxEl.getAttribute('value') : '';
                    } else {
                        productDefaultTaxRateId = currentTaxRateId;
                    }

                    var last_tbody = document.querySelector('#item_table tbody:last-of-type') || document.querySelector('#item_table tbody');
                    if (!last_tbody) continue;

                    var nameEl = last_tbody.querySelector('input[name="item_name"]');
                    var descEl = last_tbody.querySelector('textarea[name="item_description"]');
                    var priceEl = last_tbody.querySelector('input[name="item_price"]');
                    var qtyEl = last_tbody.querySelector('input[name="item_quantity"]');
                    var taxEl = last_tbody.querySelector('select[name="item_tax_rate_id"]');
                    var prodIdEl = last_tbody.querySelector('input[name="item_product_id"]');
                    var unitEl = last_tbody.querySelector('select[name="item_product_unit_id"]');

                    if (nameEl) nameEl.value = products[key].product_name || '';
                    if (descEl) descEl.value = products[key].product_description || '';
                    if (priceEl) priceEl.value = products[key].product_price || '';
                    if (qtyEl) qtyEl.value = '1';
                    if (taxEl) taxEl.value = productDefaultTaxRateId || '';
                    if (prodIdEl) prodIdEl.value = products[key].id || '';
                    if (unitEl) unitEl.value = products[key].unit_id || '';

                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                }

                // Mirror original behavior: reload to ensure server/client are in sync
                location.reload(true);
            })
            .catch(function (err) {
                console.error('selection_inv failed', err);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('An error occurred while adding products to invoice. See console for details.');
            });
    }

    // Toggle checkbox when clicking on a product row (mirrors original)
    function rowToggleHandler(e) {
        var row = e.target.closest('.product');
        if (!row) return;
        if (e.target.type !== 'checkbox') {
            var checkbox = row.querySelector(':scope input[type="checkbox"]');
            if (checkbox) {
                checkbox.click();
            }
        }
    }

    // Reset helpers for quote/inv that reload the lookup table
    function handleProductReset(targetSelector, filterProductSelector, filterFamilySelector, enableButtonSelector) {
        var product_table = document.querySelector(targetSelector);
        if (!product_table) return;
        var lookup_url = location.origin + "/invoice/product/lookup";
        product_table.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        lookup_url += "?fp=''&ff=''&rt=true";
        setTimeout(function () {
            loadHtmlInto(lookup_url, product_table).catch(function (err) { console.error(err); });
        }, 50);
        // Reset filters and enable button if content loaded
        var enableBtn = document.querySelector(enableButtonSelector);
        if (enableBtn) enableBtn.removeAttribute('disabled');
        var fpEl = document.querySelector(filterProductSelector);
        if (fpEl) fpEl.value = "";
        var ffEl = document.querySelector(filterFamilySelector);
        if (ffEl) ffEl.value = "";
    }

    // Filter handlers for quote/inv search
    function handleFilterButton(targetSelector, filterProductSelector, filterFamilySelector, enableButtonSelector) {
        var product_table = document.querySelector(targetSelector);
        if (!product_table) return;
        var fp = (document.querySelector(filterProductSelector) || { value: '' }).value || '';
        var ff = (document.querySelector(filterFamilySelector) || { value: 0 }).value || 0;
        fp = window.encodeURIComponent(fp);
        var lookup_url = location.origin + "/invoice/product/lookup";
        product_table.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        var enableBtn = document.querySelector(enableButtonSelector);
        if (enableBtn) enableBtn.setAttribute('disabled', 'true');

        if (fp && ff > 0) { lookup_url += "?fp=" + fp + "&ff=" + ff; }
        else if (fp && ff === 0) { lookup_url += "?fp=" + fp; }

        console.log(lookup_url);
        setTimeout(function () {
            loadHtmlInto(lookup_url, product_table).catch(function (err) { console.error(err); });
            if (enableBtn) enableBtn.removeAttribute('disabled');
        }, 50);
    }

    // Filter family change handlers
    function handleFilterFamilyChange(targetSelector, filterProductSelector, filterFamilySelector, enableButtonSelector) {
        var product_table = document.querySelector(targetSelector);
        if (!product_table) return;
        var lookup_url = location.origin + "/invoice/product/lookup";
        var btn = document.querySelector(enableButtonSelector);
        if (btn) {
            btn.innerHTML = '<h6 class="text-center" ><i class="fa fa-check"> Submit </i></h6>';
            btn.setAttribute('disabled', 'true');
        }
        product_table.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        var ff = (document.querySelector(filterFamilySelector) || { value: 0 }).value || 0;
        var fp = (document.querySelector(filterProductSelector) || { value: '' }).value || '';
        fp = window.encodeURIComponent(fp);

        if (ff > 0) lookup_url += "?ff=" + ff;
        if (fp && ff > 0) lookup_url += "&fp" + fp;
        if (fp && ff === 0) lookup_url += "?fp" + fp + "&ff=" + ff;

        setTimeout(function () {
            loadHtmlInto(lookup_url, product_table).catch(function (err) { console.error(err); });
            if (product_table) {
                var enableBtn = document.querySelector(enableButtonSelector);
                if (enableBtn) enableBtn.removeAttribute('disabled');
            }
        }, 250);
    }

    // Bind Enter to product search if search field is focused
    function keydownHandler(e) {
        if (e.key === 'Enter') {
            var active = document.activeElement;
            if (active && active.id === 'filter_product_quote') {
                var el = document.getElementById('filter-button-quote');
                if (el) el.click();
                e.preventDefault();
            }
            if (active && active.id === 'filter_product_inv') {
                var el2 = document.getElementById('filter-button-inv');
                if (el2) el2.click();
                e.preventDefault();
            }
        }
    }

    // Delegated event handlers (document-level)
    document.addEventListener('click', function (e) {
        var el = e.target;

        // Openers (the modal loader is handled elsewhere; if modals are dynamically loaded via this file, we handle clicks to load)
        var opener = el.closest('.open-product-lookup, .product-lookup-link');
        if (opener) {
            // If the project uses a modal loader that targets #product-lookup-table inside a modal placeholder,
            // use the opener's data attributes to construct load target and URL if present.
            var product_table = document.querySelector('#product-lookup-table');
            var lookup_url = opener.dataset.url || (location.origin + "/invoice/product/lookup");
            // Optionally include data attributes for filter values
            loadHtmlInto(lookup_url, product_table).catch(function (err) { console.error(err); });
            return;
        }

        // Confirm quote
        var confirmQuote = el.closest('.select-items-confirm-quote');
        if (confirmQuote) {
            handleSelectItemsConfirmQuote(confirmQuote);
            return;
        }

        // Confirm invoice
        var confirmInv = el.closest('.select-items-confirm-inv');
        if (confirmInv) {
            handleSelectItemsConfirmInv(confirmInv);
            return;
        }

        // Row toggle (click on row toggles checkbox, unless click was on checkbox)
        if (el.closest('.product')) {
            rowToggleHandler(e);
            return;
        }

        // Reset buttons
        if (el.closest('#product-reset-button-quote')) {
            handleProductReset('#product-lookup-table', '#filter_product_quote', '#filter_family_quote', '.select-items-confirm-quote');
            return;
        }
        if (el.closest('#product-reset-button-inv')) {
            handleProductReset('#product-lookup-table', '#filter_product_inv', '#filter_family_inv', '.select-items-confirm-inv');
            return;
        }

        // Filter buttons
        if (el.closest('#filter-button-quote')) {
            handleFilterButton('#product-lookup-table', '#filter_product_quote', '#filter_family_quote', '.select-items-confirm-quote');
            return;
        }
        if (el.closest('#filter-button-inv')) {
            handleFilterButton('#product-lookup-table', '#filter_product_inv', '#filter_family_inv', '.select-items-confirm-inv');
            return;
        }
    }, true);

    // Delegated change handler for product checkboxes and family selects
    document.addEventListener('change', function (e) {
        var target = e.target;
        if (!target) return;

        // If product checkbox changed - update buttons
        if (target.matches("input[name='product_ids[]']")) {
            updateSelectButtonsState(target.closest('#product-lookup-table') || document);
            return;
        }

        // Family select changes
        if (target.matches('#filter_family_quote')) {
            handleFilterFamilyChange('#product-lookup-table', '#filter_product_quote', '#filter_family_quote', '.select-items-confirm-quote');
            return;
        }
        if (target.matches('#filter_family_inv')) {
            handleFilterFamilyChange('#product-lookup-table', '#filter_product_inv', '#filter_family_inv', '.select-items-confirm-inv');
            return;
        }
    }, true);

    // Bind Enter key
    document.addEventListener('keydown', keydownHandler, true);

    // On initial load, make sure selects and button state are initialized
    document.addEventListener('DOMContentLoaded', function () {
        initSimpleSelects();
        updateSelectButtonsState();
    });

})();