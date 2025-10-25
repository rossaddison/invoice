(function () {
    "use strict";

    // Robust parse helper (matches original parsedata behaviour)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try {
                return JSON.parse(data);
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    // Populate a <select> element with options from an object { key: value, ... }
    function populateSelect(selectEl, items, promptText) {
        if (!selectEl) return;
        // Clear existing options
        selectEl.innerHTML = '';
        // Add prompt/none option
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = promptText || 'None';
        selectEl.appendChild(opt);

        if (!items) return;

        // items may be an array or object. Prefer object/associative.
        if (Array.isArray(items)) {
            items.forEach(function (v, i) {
                var o = document.createElement('option');
                o.value = i;
                o.textContent = v;
                selectEl.appendChild(o);
            });
        } else {
            Object.keys(items).forEach(function (key) {
                var o = document.createElement('option');
                o.value = key;
                o.textContent = items[key];
                selectEl.appendChild(o);
            });
        }
    }

    // Request helper: GET with query params, returns parsed response (object or {})
    function getJson(url, params) {
        var u = url;
        if (params && Object.keys(params).length > 0) {
            u += (url.indexOf('?') === -1 ? '?' : '&') + new URLSearchParams(params).toString();
        }
        return fetch(u, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.text(); })
            .then(function (text) { return parsedata(text); });
    }

    // Handler: when primary category changes, load secondary categories
    function onPrimaryChange() {
        var primarySelect = document.getElementById('family-category-primary-id');
        if (!primarySelect) return;
        var primaryCategoryId = primarySelect.value || '';
        var url = location.origin + "/invoice/family/secondaries/" + encodeURIComponent(primaryCategoryId);

        getJson(url, { category_primary_id: primaryCategoryId })
            .then(function (response) {
                if (response && response.success === 1) {
                    var secondaryCategories = response.secondary_categories || {};
                    var secondaryDropdown = document.getElementById('family-category-secondary-id');
                    populateSelect(secondaryDropdown, secondaryCategories, 'None');

                    // Optionally, trigger change on secondary to cascade populate family names
                    if (secondaryDropdown) {
                        var evt = new Event('change', { bubbles: true });
                        secondaryDropdown.dispatchEvent(evt);
                    }
                } else {
                    // In failure case, clear secondary and family name selects
                    populateSelect(document.getElementById('family-category-secondary-id'), {}, 'None');
                    populateSelect(document.getElementById('family-name'), {}, 'None');
                }
            })
            .catch(function (err) {
                console.error('Error loading secondary categories', err);
            });
    }

    // Handler: when secondary category changes, load family names
    function onSecondaryChange() {
        var secondarySelect = document.getElementById('family-category-secondary-id');
        if (!secondarySelect) return;
        var secondaryCategoryId = secondarySelect.value || '';
        var url = location.origin + "/invoice/family/names/" + encodeURIComponent(secondaryCategoryId);

        getJson(url, { category_secondary_id: secondaryCategoryId })
            .then(function (response) {
                if (response && response.success === 1) {
                    var familyNames = response.family_names || {};
                    var familyNameDropdown = document.getElementById('family-name');
                    populateSelect(familyNameDropdown, familyNames, 'None');
                } else {
                    populateSelect(document.getElementById('family-name'), {}, 'None');
                }
            })
            .catch(function (err) {
                console.error('Error loading family names', err);
            });
    }

    // Wire up listeners on DOMContentLoaded and trigger initial population
    document.addEventListener('DOMContentLoaded', function () {
        var primarySelect = document.getElementById('family-category-primary-id');
        var secondarySelect = document.getElementById('family-category-secondary-id');

        if (primarySelect) {
            primarySelect.addEventListener('change', onPrimaryChange, false);
            // Trigger initial change to populate secondaries on load (if selection exists)
            // Use a microtask to ensure other scripts have run if necessary
            Promise.resolve().then(function () { onPrimaryChange(); });
        }

        if (secondarySelect) {
            secondarySelect.addEventListener('change', onSecondaryChange, false);
            // Trigger initial load of family names if secondary already has a value
            Promise.resolve().then(function () { onSecondaryChange(); });
        }
    });
})();