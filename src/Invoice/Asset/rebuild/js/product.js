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

    // Filter table rows by SKU (mirrors original tableFunction)
    function tableFunction() {
        var inputEl = document.getElementById('filter_product_sku');
        if (!inputEl) return;
        var input = inputEl.value || '';
        var filter = input.toUpperCase();
        var table = document.getElementById("table-product");
        if (!table) return;
        var tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (var i = 0; i < tr.length; i++) {
            // product_sku is 3rd column or index 2
            var td = tr[i].getElementsByTagName("td")[2];
            if (td) {
                var txtValue = td.textContent || td.innerText || '';
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    // Perform the product search request and update UI like original jQuery did
    function submitProductFilters(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();

        var url = location.origin + "/invoice/product/search";
        var btns = Array.from(document.querySelectorAll('.product_filters_submit'));
        // show spinner on all matching buttons (mirrors original behaviour)
        btns.forEach(function (b) {
            b.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        });

        var productSku = (document.getElementById('filter_product_sku') || { value: '' }).value || '';
        var params = new URLSearchParams({ product_sku: productSku });

        fetch(url + '?' + params.toString(), {
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
                try { data = JSON.parse(text); } catch (err) { data = text; }
                var response = parsedata(data);
                if (response && response.success === 1) {
                    tableFunction();
                    // hide the summary bar (use querySelector to match classes precisely)
                    var summary = document.querySelector('.mt-3.me-3.summary.text-end');
                    if (summary) summary.style.visibility = 'hidden';
                    btns.forEach(function (b) {
                        b.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                    });
                } else {
                    btns.forEach(function (b) {
                        b.innerHTML = '<h6 class="text-center"><i class="fa fa-error"></i></h6>';
                    });
                    if (response && response.message) alert(response.message);
                }
            })
            .catch(function (err) {
                console.error('product search failed', err);
                btns.forEach(function (b) {
                    b.innerHTML = '<h6 class="text-center"><i class="fa fa-error"></i></h6>';
                });
                alert('An error occurred while searching products. See console for details.');
            });
    }

    // Delegate click on #product_filters_submit (matches original delegated handler)
    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el) return;
        var trigger = el.closest('#product_filters_submit');
        if (trigger) {
            submitProductFilters(e);
        }
    }, true);

    // Initialize on DOM ready: nothing else necessary but expose tableFunction if needed elsewhere
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // no-op for now
        });
    } else {
        // already loaded
    }

    // Export tableFunction to global scope in case other scripts call it (keeps parity with original)
    window.productTableFilter = tableFunction;

})();