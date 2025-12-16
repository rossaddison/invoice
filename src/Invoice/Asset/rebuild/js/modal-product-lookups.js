// Modal Product Lookups - Vanilla JavaScript Implementation
// Based on jQuery patterns from pre_jquery_deletion branch

(function() {
    'use strict';

    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object') return data;
        if (typeof data === 'string') {
            try {
                return JSON.parse(data);
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    // Secure DOM content helper to prevent XSS
    function setSecureButtonContent(button, tagName, className, iconClass) {
        if (!button) return;
        
        // Clear existing content safely
        while (button.firstChild) {
            button.removeChild(button.firstChild);
        }
        
        // Create elements securely
        var element = document.createElement(tagName);
        if (className) element.className = className;
        
        var icon = document.createElement('i');
        if (iconClass) icon.className = iconClass;
        
        element.appendChild(icon);
        button.appendChild(element);
    }

    function init() {
        initializeComponents();
    }

    function initializeComponents() {
        // Initialize TomSelect (replaces jQuery select2)
        if (typeof TomSelect !== 'undefined') {
            document.querySelectorAll('.simple-select').forEach(function(el) {
                if (!el._tomselect) {
                    new TomSelect(el, {});
                    el._tomselect = true;
                }
            });
        }
        updateButtonStates();
    }

    function updateButtonStates() {
        var at_least_one_checked;
        if (document.querySelectorAll("input[name='product_ids[]']:checked").length > 0) {
            at_least_one_checked = true;
        } else {
            at_least_one_checked = false;
        }
        
        if (at_least_one_checked) {
            var quoteBtn = document.querySelector('.select-items-confirm-quote');
            var invBtn = document.querySelector('.select-items-confirm-inv');
            if (quoteBtn) quoteBtn.removeAttribute('disabled');
            if (invBtn) invBtn.removeAttribute('disabled');
        } else {
            var quoteBtn = document.querySelector('.select-items-confirm-quote');
            var invBtn = document.querySelector('.select-items-confirm-inv');
            if (quoteBtn) quoteBtn.setAttribute('disabled', 'true');
            if (invBtn) invBtn.setAttribute('disabled', 'true');
        }
    }

    // Event delegation for clicks
    document.addEventListener('click', function(e) {
        var target = e.target;

        if (target.closest('.select-items-confirm-quote')) {
            e.preventDefault();
            handleQuoteConfirm();
            return;
        }

        if (target.closest('.select-items-confirm-inv')) {
            e.preventDefault();
            handleInvoiceConfirm();
            return;
        }

        var productRow = target.closest('.product');
        if (productRow && target.type !== 'checkbox') {
            var checkbox = productRow.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.click();
            }
            return;
        }
    });

    // Handle checkbox clicks - matching jQuery pattern
    document.addEventListener('click', function(e) {
        if (e.target.matches("input[name='product_ids[]']")) {
            updateButtonStates();
        }
    });

    function handleQuoteConfirm() {
        var absolute_url = new URL(window.location.href);
        var btn = document.querySelector('.select-items-confirm-quote');
        setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-spin fa-spinner');
        var product_ids = [];
        // Safely extract and validate quote_id
        var quote_id = absolute_url.pathname.substring(absolute_url.pathname.lastIndexOf('/') + 1);
        quote_id = quote_id.replace(/[^0-9]/g, ''); // Sanitize to only numbers
        
        document.querySelectorAll("input[name='product_ids[]']:checked").forEach(function(input) {
            product_ids.push(parseInt(input.value));
        });

        // Build URL with proper query parameters
        var url = '/invoice/product/selection_quote?quote_id=' + quote_id;
        product_ids.forEach(function(id) {
            url += '&product_ids[]=' + id;
        });
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            var products = parsedata(data);
            var productDefaultTaxRateId = Object.create(null);
            var currentTaxRateId = Object.create(null);
            
            console.log('Processing', Object.keys(products).length, 'products for quote');
            for (var key in products) {
                console.log('Processing product key:', key);
                // Sanitize remote data before use
                var product = products[key];
                if (!product || typeof product !== 'object') continue;
                
                currentTaxRateId = product.tax_rate_id;
                if (!currentTaxRateId) {
                    var defaultTaxRateEl = document.getElementById('default_item_tax_rate');
                    productDefaultTaxRateId = defaultTaxRateEl ? defaultTaxRateEl.getAttribute('value') : null;
                } else {
                    productDefaultTaxRateId = currentTaxRateId;
                }
                
                // Get the last tbody element (matches jQuery $('#item_table tbody:last'))
                // This should find the table with id 'item_table' and then select the last tbody within it
                var last_item_row = document.querySelector('#item_table tbody:last-of-type');
                
                if (last_item_row) {
                    var itemName = last_item_row.querySelector('input[name=item_name]');
                    if (itemName) itemName.value = product.product_name;
                    
                    var itemDesc = last_item_row.querySelector('textarea[name=item_description]');
                    if (itemDesc) itemDesc.value = product.product_description;
                    
                    var itemPrice = last_item_row.querySelector('input[name=item_price]');
                    if (itemPrice) itemPrice.value = product.product_price;
                    
                    var itemQty = last_item_row.querySelector('input[name=item_quantity]');
                    if (itemQty) itemQty.value = '1';
                    
                    var itemTaxRate = last_item_row.querySelector('select[name=item_tax_rate_id]');
                    if (itemTaxRate) itemTaxRate.value = productDefaultTaxRateId;
                    
                    var itemProductId = last_item_row.querySelector('input[name=item_product_id]');
                    if (itemProductId) itemProductId.value = product.id;
                    
                    var itemUnitId = last_item_row.querySelector('select[name=item_product_unit_id]');
                    if (itemUnitId) itemUnitId.value = product.unit_id;
                }
                
                setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-check');
            }
            window.location.reload(true);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function handleInvoiceConfirm() {
        var absolute_url = new URL(window.location.href);
        var btn = document.querySelector('.select-items-confirm-inv');
        setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-spin fa-spinner');
        var product_ids = [];
        var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
        
        document.querySelectorAll("input[name='product_ids[]']:checked").forEach(function(input) {
            product_ids.push(parseInt(input.value));
        });

        // Build URL with proper query parameters
        var url = '/invoice/product/selection_inv?inv_id=' + inv_id;
        product_ids.forEach(function(id) {
            url += '&product_ids[]=' + id;
        });
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            var products = parsedata(data);
            var productDefaultTaxRateId = Object.create(null);
            var currentTaxRateId = Object.create(null);
            
            for (var key in products) {
                var product = products[key];
                if (!product || typeof product !== 'object') continue;
                
                currentTaxRateId = product.tax_rate_id;
                if (!currentTaxRateId) {
                    var defaultTaxRateEl = document.getElementById('default_item_tax_rate');
                    productDefaultTaxRateId = defaultTaxRateEl ? defaultTaxRateEl.getAttribute('value') : null;
                } else {
                    productDefaultTaxRateId = currentTaxRateId;
                }
                
                // Get the last tbody element (matches jQuery $('#item_table tbody:last'))
                // This should find the table with id 'item_table' and then select the last tbody within it
                var last_item_row = document.querySelector('#item_table tbody:last-of-type');
                
                if (last_item_row) {
                    var itemName = last_item_row.querySelector('input[name=item_name]');
                    if (itemName) itemName.value = product.product_name;
                    
                    var itemDesc = last_item_row.querySelector('textarea[name=item_description]');
                    if (itemDesc) itemDesc.value = product.product_description;
                    
                    var itemPrice = last_item_row.querySelector('input[name=item_price]');
                    if (itemPrice) itemPrice.value = product.product_price;
                    
                    var itemQty = last_item_row.querySelector('input[name=item_quantity]');
                    if (itemQty) itemQty.value = '1';
                    
                    var itemTaxRate = last_item_row.querySelector('select[name=item_tax_rate_id]');
                    if (itemTaxRate) itemTaxRate.value = productDefaultTaxRateId;
                    
                    var itemProductId = last_item_row.querySelector('input[name=item_product_id]');
                    if (itemProductId) itemProductId.value = product.id;
                    
                    var itemUnitId = last_item_row.querySelector('select[name=item_product_unit_id]');
                    if (itemUnitId) itemUnitId.value = product.unit_id;
                }
                
                setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-check');
            }
            window.location.reload(true);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
