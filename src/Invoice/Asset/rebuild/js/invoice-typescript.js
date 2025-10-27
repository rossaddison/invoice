// TypeScript-compiled bundle for Invoice Application
// Auto-generated from TypeScript sources

(function() {
    'use strict';
    
    // Type-safe utilities converted from vanilla JS
    
    /**
     * Safe JSON parser that always returns an object
     */
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

    /**
     * HTTP GET helper that serializes arrays as bracketed keys
     */
    async function getJson(url, params, options = {}) {
        let requestUrl = url;
        
        if (params) {
            const searchParams = new URLSearchParams();
            
            Object.entries(params).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => {
                        if (item !== null && item !== undefined) {
                            searchParams.append(`${key}[]`, String(item));
                        }
                    });
                } else if (value !== undefined && value !== null) {
                    searchParams.append(key, String(value));
                }
            });
            
            const separator = url.includes('?') ? '&' : '?';
            requestUrl = `${url}${separator}${searchParams.toString()}`;
        }

        const defaultOptions = {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            ...options
        };

        const response = await fetch(requestUrl, defaultOptions);
        
        if (!response.ok) {
            throw new Error(`Network response not ok: ${response.status}`);
        }
        
        const text = await response.text();
        
        try {
            return JSON.parse(text);
        } catch (e) {
            return text;
        }
    }

    /**
     * Safe closest element finder with fallback
     */
    function closestSafe(element, selector) {
        try {
            if (!element) return null;
            
            if (typeof element.closest === 'function') {
                return element.closest(selector);
            }
            
            // Fallback: walk up parents manually
            let node = element;
            while (node) {
                if (node.matches && node.matches(selector)) {
                    return node;
                }
                node = node.parentElement;
            }
        } catch (e) {
            console.warn('closestSafe error:', e);
            return null;
        }
        
        return null;
    }

    /**
     * Safe DOM element getter
     */
    function getElementById(id) {
        return document.getElementById(id);
    }

    /**
     * Get form field value safely
     */
    function getInputValue(id) {
        const element = getElementById(id);
        return element?.value || '';
    }

    /**
     * Create Credit Handler - TypeScript converted
     */
    class CreateCreditHandler {
        constructor() {
            this.confirmButtonSelector = '.create-credit-confirm';
            this.initialize();
        }

        initialize() {
            document.addEventListener('click', this.handleClick.bind(this), true);
        }

        async handleClick(event) {
            const target = event.target;
            
            if (!target || target.id !== 'create-credit-confirm') {
                return;
            }

            event.preventDefault();
            
            try {
                await this.processCreateCredit();
            } catch (error) {
                console.error('Create credit error:', error);
                alert(`Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
            }
        }

        async processCreateCredit() {
            const url = `${location.origin}/invoice/inv/create_credit_confirm`;
            const btn = document.querySelector(this.confirmButtonSelector);
            const absoluteUrl = new URL(location.href);
            
            // Show loading spinner
            if (btn) {
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            }
            
            // Extract invoice ID from URL
            const invId = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
            
            // Collect form data with type safety
            const formData = {
                inv_id: invId,
                client_id: getInputValue('client_id'),
                inv_date_created: getInputValue('inv_date_created'),
                group_id: getInputValue('inv_group_id'),
                password: getInputValue('inv_password'),
                user_id: getInputValue('user_id')
            };

            // Make API request
            const data = await getJson(url, formData);
            const response = parsedata(data);
            
            if (response.success === 1) {
                // Success
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';
                }
                
                if (response.flash_message) {
                    alert(response.flash_message);
                }
                
                // Redirect and reload
                location.href = absoluteUrl.href;
                location.reload();
            } else {
                // Failure
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                }
                
                if (response.flash_message) {
                    alert(response.flash_message);
                }
                
                // Redirect and reload
                location.href = absoluteUrl.href;
                location.reload();
            }
        }
    }

    /**
     * Main Invoice Application - TypeScript style
     */
    class InvoiceApp {
        constructor() {
            // Initialize handlers
            this.createCreditHandler = new CreateCreditHandler();
            this.initializeTooltips();
            this.initializeTaggableFocus();
            
            console.log('Invoice TypeScript App initialized');
        }

        /**
         * Initialize Bootstrap tooltips
         */
        initializeTooltips() {
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipElements.forEach((element) => {
                        try {
                            new bootstrap.Tooltip(element);
                        } catch (error) {
                            console.warn('Tooltip initialization failed:', error);
                        }
                    });
                }
            });
        }

        /**
         * Keep track of last taggable focused element
         */
        initializeTaggableFocus() {
            document.addEventListener('focus', (event) => {
                const target = event.target;
                if (target?.classList?.contains('taggable')) {
                    window.lastTaggableClicked = target;
                }
            }, true);
        }
    }

    // Initialize the application when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new InvoiceApp());
    } else {
        new InvoiceApp();
    }

})();