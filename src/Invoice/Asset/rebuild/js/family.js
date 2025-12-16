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

    // Handle product generation modal opening
    function handleGenerateProducts() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name="family_ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            alert('Please select at least one family to generate products.');
            return false;
        }
        
        // Load tax rates and units for dropdowns
        loadTaxRatesAndUnits();
        
        // Get family data and preview products
        const familyData = getFamilyDataFromCheckedBoxes(checkedBoxes);
        updateProductsPreview(familyData);
        
        return true; // Allow modal to open
    }
    
    // Get family data from checked boxes by reading table row data
    function getFamilyDataFromCheckedBoxes(checkedBoxes) {
        const familyData = [];
        
        checkedBoxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (!row) return;
            
            const cells = row.querySelectorAll('td');
            if (cells.length >= 5) {
                const familyId = checkbox.value;
                const familyName = cells[2]?.textContent?.trim() || '';
                const commalist = cells[3]?.textContent?.trim() || '';
                const productPrefix = cells[4]?.textContent?.trim() || '';
                
                if (commalist && productPrefix) {
                    familyData.push({
                        id: familyId,
                        name: familyName,
                        commalist: commalist,
                        productPrefix: productPrefix
                    });
                }
            }
        });
        
        return familyData;
    }
    
    // Update products preview in modal
    function updateProductsPreview(familyData) {
        const previewDiv = document.getElementById('products-preview');
        if (!previewDiv) return;
        
        if (familyData.length === 0) {
            previewDiv.innerHTML = '<p class="text-warning">⚠️ No valid families selected (families must have both comma list and product prefix)</p>';
            return;
        }
        
        let previewHtml = '<div class="row">';
        let totalProducts = 0;
        
        familyData.forEach(family => {
            const items = family.commalist.split(',').map(item => item.trim()).filter(item => item);
            totalProducts += items.length;
            
            previewHtml += `<div class="col-md-6 mb-3">`;
            previewHtml += `<div class="card">`;
            previewHtml += `<div class="card-header bg-info text-white">`;
            previewHtml += `<strong>🏠 ${family.name}</strong> (${items.length} products)`;
            previewHtml += `</div>`;
            previewHtml += `<div class="card-body p-2">`;
            
            items.forEach(item => {
                const productName = `${family.productPrefix} ${item}`;
                previewHtml += `<div class="badge bg-success me-1 mb-1">${productName}</div>`;
            });
            
            previewHtml += `</div></div></div>`;
        });
        
        previewHtml += '</div>';
        previewHtml += `<div class="alert alert-info mt-3"><strong>📊 Total products to generate: ${totalProducts}</strong></div>`;
        
        previewDiv.innerHTML = previewHtml;
    }
    
    // Load tax rates and units from API
    function loadTaxRatesAndUnits() {
        // Load tax rates
        fetch('/invoice/taxrate/search', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const taxRateSelect = document.getElementById('tax_rate_id');
            if (taxRateSelect && data) {
                populateSelect(taxRateSelect, data, 'Select Tax Rate');
            }
        })
        .catch(error => console.error('Error loading tax rates:', error));
        
        // Load units
        fetch('/invoice/unit/search', {
            method: 'GET', 
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const unitSelect = document.getElementById('unit_id');
            if (unitSelect && data) {
                populateSelect(unitSelect, data, 'Select Unit');
            }
        })
        .catch(error => console.error('Error loading units:', error));
    }
    
    // Handle actual product generation when modal confirm button is clicked
    function processProductGeneration() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name="family_ids[]"]:checked');
        const taxRateId = document.getElementById('tax_rate_id')?.value;
        const unitId = document.getElementById('unit_id')?.value;
        
        if (!taxRateId || !unitId) {
            alert('Please select both tax rate and unit.');
            return false;
        }
        
        // Prepare form data
        const formData = new FormData();
        Array.from(checkedBoxes).forEach(checkbox => {
            formData.append('family_ids[]', checkbox.value);
        });
        formData.append('tax_rate_id', taxRateId);
        formData.append('unit_id', unitId);
        
        // Add CSRF token
        const csrfInput = document.querySelector('input[name="_csrf"]');
        if (csrfInput) {
            formData.append('_csrf', csrfInput.value);
        }
        
        // Disable confirm button during processing
        const confirmBtn = document.getElementById('confirm-generate-products');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '⏳ Generating...';
        }
        
        // Send request to generate products
        fetch('/invoice/family/generate_products', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Successfully generated ${data.count || 0} products from selected families.`);
                // Close modal and reload page
                const modal = document.getElementById('generate-products-modal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                }
                window.location.reload();
            } else {
                alert('❌ Error generating products: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Network error occurred while generating products.');
        })
        .finally(() => {
            // Re-enable confirm button
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = '✅ Generate Products';
            }
        });
        
        return false;
    }

    // Initialize event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {\n        // Handle generate products button click\n        const generateBtn = document.getElementById('btn-generate-products');\n        if (generateBtn) {\n            generateBtn.addEventListener('click', function(e) {\n                e.preventDefault();\n                const result = handleGenerateProducts();\n                if (!result) {\n                    e.stopPropagation();\n                    return false;\n                }\n            });\n        }\n        \n        // Handle modal confirm button click\n        const confirmBtn = document.getElementById('confirm-generate-products');\n        if (confirmBtn) {\n            confirmBtn.addEventListener('click', function(e) {\n                e.preventDefault();\n                processProductGeneration();\n            });\n        }\n        \n        // Update preview when families are selected/deselected\n        document.addEventListener('change', function(e) {\n            if (e.target.type === 'checkbox' && e.target.name === 'family_ids[]') {\n                const modal = document.getElementById('generate-products-modal');\n                if (modal && modal.classList.contains('show')) {\n                    const checkedBoxes = document.querySelectorAll('input[type=\"checkbox\"][name=\"family_ids[]\"]:checked');\n                    const familyData = getFamilyDataFromCheckedBoxes(checkedBoxes);\n                    updateProductsPreview(familyData);\n                }\n            }\n        });\n    });\n\n    // Request helper: GET with query params, returns parsed response (object or {})
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