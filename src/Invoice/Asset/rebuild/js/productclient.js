(function () {
    "use strict";

    /**
     * ProductClient Association JavaScript
     * Handles client selection, new client creation, and client group inheritance
     * Ready for TypeScript conversion in the future
     */

    // Client group suggestion management
    let suggestedClientGroup = null;

    // Initialize ProductClient association functionality
    function initializeProductClientAssociation() {
        const existingClientRadio = document.getElementById('existing_client');
        const newClientRadio = document.getElementById('new_client');
        const existingClientSection = document.getElementById('existing-client-section');
        const newClientSection = document.getElementById('new-client-section');
        const clientDropdown = document.getElementById('productclient-client_id');
        const clientGroupInput = document.getElementById('productclient-new_client_group');

        if (!existingClientRadio || !newClientRadio) return;

        // Toggle between existing and new client sections
        function toggleSections() {
            if (existingClientRadio.checked) {
                if (existingClientSection) existingClientSection.style.display = 'block';
                if (newClientSection) newClientSection.style.display = 'none';
            } else {
                if (existingClientSection) existingClientSection.style.display = 'none';
                if (newClientSection) newClientSection.style.display = 'block';
            }
        }

        // Handle client selection change to capture client group
        function handleClientSelectionChange() {
            if (!clientDropdown || !clientDropdown.value) return;
            
            const selectedClientId = clientDropdown.value;
            
            // Fetch client details to get client group
            fetch(`/invoice/client/view/${selectedClientId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.client && data.client.client_group) {
                    suggestedClientGroup = data.client.client_group;
                    // Update the new client group field with suggestion
                    if (clientGroupInput) {
                        clientGroupInput.value = suggestedClientGroup;
                        clientGroupInput.placeholder = `Suggested: ${suggestedClientGroup}`;
                        
                        // Show suggestion message
                        showClientGroupSuggestion(suggestedClientGroup);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching client details:', error);
            });
        }

        // Show client group suggestion message
        function showClientGroupSuggestion(group) {
            // Remove any existing suggestion message
            const existingSuggestion = document.querySelector('.client-group-suggestion');
            if (existingSuggestion) {
                existingSuggestion.remove();
            }

            if (!clientGroupInput) return;

            // Create suggestion message
            const suggestionDiv = document.createElement('div');
            suggestionDiv.className = 'client-group-suggestion alert alert-info mt-2 small';

            // Icon
            const icon = document.createElement('i');
            icon.className = 'fa fa-lightbulb-o';
            suggestionDiv.appendChild(icon);

            // Space between icon and text
            suggestionDiv.appendChild(document.createTextNode(' '));

            // "Suggestion:" label
            const strong = document.createElement('strong');
            strong.textContent = 'Suggestion:';
            suggestionDiv.appendChild(strong);

            // Space after label
            suggestionDiv.appendChild(document.createTextNode(' '));

            // Descriptive text with safe insertion of group name
            const messagePrefix = 'Client group "';
            const messageSuffix = '" will be used for remaining products.';
            suggestionDiv.appendChild(document.createTextNode(messagePrefix));
            suggestionDiv.appendChild(document.createTextNode(group));
            suggestionDiv.appendChild(document.createTextNode(messageSuffix));

            // Clear button
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'btn btn-sm btn-outline-secondary ms-2';
            clearButton.textContent = 'Clear Suggestion';
            clearButton.addEventListener('click', function () {
                if (typeof clearClientGroupSuggestion === 'function') {
                    clearClientGroupSuggestion();
                }
            });
            suggestionDiv.appendChild(clearButton);

            // Insert after the client group input
            clientGroupInput.parentNode.insertBefore(suggestionDiv, clientGroupInput.nextSibling);
        }

        // Clear client group suggestion
        window.clearClientGroupSuggestion = function() {
            suggestedClientGroup = null;
            if (clientGroupInput) {
                clientGroupInput.placeholder = '';
            }
            const suggestionDiv = document.querySelector('.client-group-suggestion');
            if (suggestionDiv) {
                suggestionDiv.remove();
            }
        };

        // Validate form before submission
        function validateForm() {
            if (existingClientRadio.checked) {
                // Validate existing client selection
                if (!clientDropdown || !clientDropdown.value) {
                    alert('Please select an existing client.');
                    return false;
                }
            } else {
                // Validate new client fields
                const nameInput = document.getElementById('productclient-new_client_name');
                const emailInput = document.getElementById('productclient-new_client_email');
                
                if (!nameInput || !nameInput.value.trim()) {
                    alert('Please enter the client name.');
                    nameInput?.focus();
                    return false;
                }
                
                if (emailInput && emailInput.value.trim()) {
                    // Basic email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailInput.value.trim())) {
                        alert('Please enter a valid email address.');
                        emailInput.focus();
                        return false;
                    }
                }
            }
            return true;
        }

        // Handle form submission
        function handleFormSubmission(event) {
            if (!validateForm()) {
                event.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            }
            
            return true;
        }

        // Set up event listeners
        existingClientRadio.addEventListener('change', toggleSections);
        newClientRadio.addEventListener('change', toggleSections);
        
        if (clientDropdown) {
            clientDropdown.addEventListener('change', handleClientSelectionChange);
        }

        const form = document.getElementById('ProductClientForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmission);
        }

        // Initialize sections visibility
        toggleSections();

        // Pre-populate client group if suggestion exists
        const existingSuggestion = clientGroupInput?.getAttribute('value');
        if (existingSuggestion && existingSuggestion.trim()) {
            suggestedClientGroup = existingSuggestion.trim();
            showClientGroupSuggestion(suggestedClientGroup);
        }
    }

    // Auto-save progress for multi-product association workflow
    function initializeProgressTracking() {
        const currentIndexElement = document.querySelector('[data-current-index]');
        const totalProductsElement = document.querySelector('[data-total-products]');
        
        if (currentIndexElement && totalProductsElement) {
            const currentIndex = parseInt(currentIndexElement.getAttribute('data-current-index')) || 0;
            const totalProducts = parseInt(totalProductsElement.getAttribute('data-total-products')) || 0;
            
            // Show progress
            updateProgressIndicator(currentIndex, totalProducts);
        }
    }

    // Update progress indicator
    function updateProgressIndicator(current, total) {
        const progressContainer = document.querySelector('.progress-container');
        if (!progressContainer) {
            // Create progress container
            const container = document.createElement('div');
            container.className = 'progress-container alert alert-info mb-3';
            container.innerHTML = `
                <h6><i class="fa fa-tasks"></i> Product-Client Association Progress</h6>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted">
                    Associating product <span class="current-step">1</span> of <span class="total-steps">1</span>
                    (<span class="remaining-count">0</span> remaining)
                </small>
            `;
            
            // Insert at the top of the form
            const form = document.getElementById('ProductClientForm');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(container, form);
            }
        }

        // Update progress values
        const progressBar = document.querySelector('.progress-bar');
        const currentStepSpan = document.querySelector('.current-step');
        const totalStepsSpan = document.querySelector('.total-steps');
        const remainingCountSpan = document.querySelector('.remaining-count');

        if (progressBar) {
            const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage.toString());
        }

        if (currentStepSpan) currentStepSpan.textContent = (current + 1).toString();
        if (totalStepsSpan) totalStepsSpan.textContent = total.toString();
        if (remainingCountSpan) remainingCountSpan.textContent = (total - current - 1).toString();
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeProductClientAssociation();
        initializeProgressTracking();
        
        // Add keyboard shortcuts for efficiency
        document.addEventListener('keydown', function(event) {
            // Alt + 1: Select existing client
            if (event.altKey && event.key === '1') {
                const existingRadio = document.getElementById('existing_client');
                if (existingRadio) {
                    existingRadio.checked = true;
                    existingRadio.dispatchEvent(new Event('change'));
                }
                event.preventDefault();
            }
            
            // Alt + 2: Create new client
            if (event.altKey && event.key === '2') {
                const newRadio = document.getElementById('new_client');
                if (newRadio) {
                    newRadio.checked = true;
                    newRadio.dispatchEvent(new Event('change'));
                }
                event.preventDefault();
            }
            
            // Ctrl + Enter: Submit form
            if (event.ctrlKey && event.key === 'Enter') {
                const form = document.getElementById('ProductClientForm');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
                event.preventDefault();
            }
        });
    });

    // Export functions for potential TypeScript conversion
    window.ProductClientAssociation = {
        clearClientGroupSuggestion: window.clearClientGroupSuggestion,
        updateProgressIndicator: updateProgressIndicator
    };

})();