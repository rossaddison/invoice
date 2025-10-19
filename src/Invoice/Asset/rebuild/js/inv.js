/**
 * Converted from jQuery to vanilla JavaScript
 * jQuery has been removed from this project
 */

document.addEventListener('DOMContentLoaded', function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    }
    
    // Used in userclient/new
    const userAllClients = document.getElementById('user_all_clients');
    if (userAllClients) {
        userAllClients.addEventListener('click', function () {
            all_client_check();
        });
    }
    
    function all_client_check() {
        const userAllClients = document.getElementById('user_all_clients');
        const listClient = document.getElementById('list_client');
        if (userAllClients && listClient) {
            if (userAllClients.checked) {
                listClient.style.display = 'none';
            } else {
                listClient.style.display = 'block';
            }
        }
    }
        
    all_client_check();
            
    // class="btn_delete_item" on views/product/partial_item_table.php
    document.querySelectorAll('.btn_delete_item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');  
            if (typeof id === 'undefined') {
                this.closest('.item')?.remove();
            } else {
                const url = window.location.origin + "/invoice/inv/delete_item/"+id;
                const params = new URLSearchParams({ id: id });
                
                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json; charset=utf-8' }
                })
                .then(response => response.json())
                .then(data => {
                    const response = parsedata(data);
                    if (response.success === 1) {
                        location.reload(true);
                        this.closest('.item')?.remove();
                        alert("Deleted");
                    }
                });
            }        
        });
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.closest('.delete-items-confirm-inv')) {
            const btn = document.querySelector('.delete-items-confirm-inv');
            btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
            const item_ids = [];
            document.querySelectorAll("input[name='item_ids[]']:checked").forEach(function (checkbox) {
                item_ids.push(parseInt(checkbox.value));
            });
            
            const params = new URLSearchParams();
            item_ids.forEach(id => params.append('item_ids[]', id));
            
            fetch('/invoice/invitem/multiple?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    location.reload(true);
                }
            });
        }
    });
    
    document.addEventListener('click', function (e) {
        const target = e.target.closest('[name="checkbox-selection-all"]');
        if (target) {
            const c = target.checked;
            document.querySelectorAll(':checkbox').forEach(function (checkbox) {
                checkbox.checked = c;
            });
        }
    });
    
    // Used in inv/index to select checkboxed invoices that are to be marked as recurring instead of individually marking them as recurring
    // The invoices will appear in the invrecurring/index
    // search class(.)create_recurring_confirm_multiple on inv/modal_create_recurring_multiple.php
    document.addEventListener('click', function (e) {
        if (e.target.closest('.create_recurring_confirm_multiple')) {
            const btn = document.getElementById('create_recurring_confirm_multiple');
            const selected = [];
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            
            document.querySelectorAll('#table-invoice input[type="checkbox"]:checked').forEach(function(checkbox) {
                selected.push(checkbox.getAttribute('id'));
            });
            
            const recur_frequency = document.getElementById('recur_frequency').value;
            const recur_start_date = document.getElementById('recur_start_date').value;
            const recur_end_date = document.getElementById('recur_end_date').value;
            const url = window.location.origin + "/invoice/invrecurring/multiple";
            
            const params = new URLSearchParams({
                recur_start_date: recur_start_date,
                recur_end_date: recur_end_date,
                recur_frequency: recur_frequency
            });
            selected.forEach(key => params.append('keylist[]', key));
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload(true);
                }    
                if (response.success === 0) { 
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload(true);
                }
            });
        }
    });
    
    // Used in inv/index to select invoices that are to be marked as sent instead of literally sending them by email
    document.addEventListener('click', function (e) {
        if (e.target.id === 'btn-mark-as-sent' || e.target.closest('#btn-mark-as-sent')) {
            const btn = document.getElementById('btn-mark-as-sent');
            const selected = [];
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            
            document.querySelectorAll('#table-invoice input[type="checkbox"]:checked').forEach(function(checkbox) {
                selected.push(checkbox.getAttribute('id'));
            });
            
            const url = window.location.origin + "/invoice/inv/mark_as_sent";
            const params = new URLSearchParams();
            selected.forEach(key => params.append('keylist[]', key));
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload(true);
                }    
                if (response.success === 0) { 
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload(true);                                                
                }
            });
        }
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.id === 'btn-mark-sent-as-draft' || e.target.closest('#btn-mark-sent-as-draft')) {
            const btn = document.getElementById('btn-mark-sent-as-draft');
            const selected = [];
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            
            document.querySelectorAll('#table-invoice input[type="checkbox"]:checked').forEach(function(checkbox) {
                selected.push(checkbox.getAttribute('id'));
            });
            
            const url = window.location.origin + "/invoice/inv/mark_sent_as_draft";
            const params = new URLSearchParams();
            selected.forEach(key => params.append('keylist[]', key));
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload(true);
                }    
                if (response.success === 0) { 
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload(true);                                                
                }
            });
        }
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.closest('.modal_copy_inv_multiple_confirm')) {
            const btn = document.querySelector('.modal_copy_inv_multiple_confirm');
            const modal_created_date = document.getElementById('modal_created_date').value;
            const selected = [];
            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
            
            document.querySelectorAll('#table-invoice input[type="checkbox"]:checked').forEach(function(checkbox) {
                selected.push(checkbox.getAttribute('id'));
            });
            
            const url = window.location.origin + "/invoice/inv/multiplecopy";
            const params = new URLSearchParams({
                modal_created_date: modal_created_date
            });
            selected.forEach(key => params.append('keylist[]', key));
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    window.location.reload(true);
                }    
                if (response.success === 0) { 
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    window.location.reload(true);
                }
            });
        }
    });
    
    // Add required asterisk to required fields
    document.querySelectorAll("[required]").forEach(function(elem) {
        const span = document.createElement('span');
        span.className = 'required';
        span.textContent = '*';
        elem.parentNode.insertBefore(span, elem.nextSibling);
    });
     
    document.querySelectorAll('.btn_add_row_modal').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const absolute_url = new URL(window.location.href);
            const inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1); 
            const url = window.location.origin + "/invoice/invitem/add/"+inv_id;
            const modalPlaceholder = document.getElementById('modal-placeholder-invitem');
            if (modalPlaceholder) {
                // Note: jQuery's .on("load", url) is not standard. This may need backend adjustment.
                // For now, we'll just log the url
                console.log('Load URL:', url);
            }
        });
    });

    document.querySelectorAll('.btn_inv_item_add_row').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const newRow = document.getElementById('new_inv_item_row');
            if (newRow) {
                const clone = newRow.cloneNode(true);
                clone.removeAttribute('id');
                clone.classList.add('item');
                clone.style.display = 'block';
                document.getElementById('item_table').appendChild(clone);
            }
        });
    });
    
    // class="btn_add_row" on views/inv/partial_item_table.php
    document.querySelectorAll('.btn_add_row').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const newRow = document.getElementById('new_row');
            if (newRow) {
                const clone = newRow.cloneNode(true);
                clone.removeAttribute('id');
                clone.classList.add('item');
                clone.style.display = 'block';
                document.getElementById('item_table').appendChild(clone);
            }
        });
    });
    
    // id="inv_tax_submit" in drop down menu on views/inv/modal_add_inv_tax.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_tax_submit' || e.target.closest('#inv_tax_submit')) {
            const url = window.location.origin + "/invoice/inv/save_inv_tax_rate";
            const btn = document.querySelector('.inv_tax_submit');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                inv_id: inv_id,
                inv_tax_rate_id: document.getElementById('inv_tax_rate_id').value,
                include_inv_item_tax: document.getElementById('include_inv_item_tax').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {                                   
                    window.location = absolute_url;
                    btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                    window.location.reload();                                                
                }
                if (response.success === 0) {                                   
                    window.location = absolute_url;
                    btn.innerHTML = '<h6 class="text-center"><i class="fa fa-times"></i></h6>';
                    window.location.reload();                                                
                }
            })
            .catch(error => {
                alert('Incomplete fields: You must include a tax rate. Tip: Include a zero tax rate.');
            });
        }
    });
    
    // id="create_credit_confirm button on views/inv/modal_create_credit.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'create-credit-confirm' || e.target.closest('#create-credit-confirm')) {
            const url = window.location.origin + "/invoice/inv/create_credit_confirm";
            const btn = document.querySelector('.create-credit-confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                inv_id: inv_id,
                client_id: document.getElementById('client_id').value,
                inv_date_created: document.getElementById('inv_date_created').value,
                group_id: document.getElementById('inv_group_id').value,
                password: document.getElementById('inv_password').value,
                user_id: document.getElementById('user_id').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(response.flash_message);
                }
                if (response.success === 0) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(response.flash_message);
                }    
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });

    // Copies the invoice to a specific client
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_inv_confirm' || e.target.closest('#inv_to_inv_confirm')) {
            const url = window.location.origin + "/invoice/inv/inv_to_inv_confirm";
            const btn = document.querySelector('.inv_to_inv_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                inv_id: inv_id,
                client_id: document.getElementById('create_inv_client_id').value,
                user_id: document.getElementById('user_id').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                }
                if (response.success === 0) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                }
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });
    
    // PDF generation handlers
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_pdf_confirm_with_custom_fields' || e.target.closest('#inv_to_pdf_confirm_with_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/pdf/1";    
            window.location.reload;
            window.open(url, '_blank');
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_pdf_confirm_without_custom_fields' || e.target.closest('#inv_to_pdf_confirm_without_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/pdf/0";    
            window.location.reload;
            window.open(url, '_blank');
        }
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_modal_pdf_confirm_with_custom_fields' || e.target.closest('#inv_to_modal_pdf_confirm_with_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/pdf/1";    
            
            // Set the iframe src to the URL of the PDF
            const iframe = document.getElementById('modal-view-inv-pdf');
            if (iframe) {
                iframe.setAttribute('src', url);
            }

            // Open the modal using Bootstrap 5 API
            const modalElement = document.getElementById('modal-layout-modal-pdf-inv');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_html_confirm_with_custom_fields' || e.target.closest('#inv_to_html_confirm_with_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/html/1";    
            window.location.reload;
            window.open(url, '_blank');
        }
    });
    
    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_modal_pdf_confirm_without_custom_fields' || e.target.closest('#inv_to_modal_pdf_confirm_without_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/pdf/0";    
            
            const iframe = document.getElementById('modal-view-inv-pdf');
            if (iframe) {
                iframe.setAttribute('src', url);
            }

            const modalElement = document.getElementById('modal-layout-modal-pdf-inv');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.id === 'inv_to_html_confirm_without_custom_fields' || e.target.closest('#inv_to_html_confirm_without_custom_fields')) {
            const url = window.location.origin + "/invoice/inv/html/0";    
            window.location.reload;
            window.open(url, '_blank');
        }
    });

    const invDiscountAmount = document.getElementById('inv_discount_amount');
    if (invDiscountAmount) {
        invDiscountAmount.addEventListener('keyup', function () {
            const invDiscountPercent = document.getElementById('inv_discount_percent');
            if (this.value.length > 0) {
                invDiscountPercent.value = '0.00';
                invDiscountPercent.disabled = true;
            } else {
                invDiscountPercent.disabled = false;
            }
        });
    }
    
    const invDiscountPercent = document.getElementById('inv_discount_percent');
    if (invDiscountPercent) {
        invDiscountPercent.addEventListener('keyup', function () {
            const invDiscountAmount = document.getElementById('inv_discount_amount');
            if (this.value.length > 0) {
                invDiscountAmount.value = '0.00';
                invDiscountAmount.disabled = true;
            } else {
                invDiscountAmount.disabled = false;
            }
        });
    }

    // Datepicker initialization
    // TODO: Replace jQuery UI datepicker with a vanilla JS alternative like flatpickr
    // For now, initialize only if jQuery and datepicker are available
    const datepickerElem = document.getElementById('datepicker');
    if (datepickerElem && typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.datepicker !== 'undefined') {
        datepickerElem.addEventListener('focus', function () {
            window.jQuery(this).datepicker({               
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                dateFormat: 'dd-mm-yy'
            });
        });
    }

    // Datepicker for dynamically added elements
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.datepicker !== 'undefined') {
        document.body.addEventListener('focus', function (e) {
            if (e.target.classList.contains('datepicker')) {
                window.jQuery(e.target).datepicker({
                    beforeShow: function() {
                        setTimeout(function(){
                            const datepickers = document.querySelectorAll('.datepicker');
                            datepickers.forEach(dp => dp.style.zIndex = '9999');
                        }, 0);
                    }      
                });
            }
        }, true);
    }

    // Keep track of the last "taggable" input/textarea
    document.querySelectorAll('.taggable').forEach(function(elem) {
        elem.addEventListener('focus', function () {
            window.lastTaggableClicked = this;
        });
    });
    
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Template Tag handling
    // TODO: Replace select2 with a vanilla JS alternative like Tom Select or Choices.js
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
        window.jQuery('.tag-select').select2().on('change', function (event) {
            const select = event.currentTarget;
            // Add the tag to the field
            if (typeof window.lastTaggableClicked !== 'undefined') {
                insert_at_caret(window.lastTaggableClicked.id, select.value);
            }
            // Reset the select and exit
            select.value = '';
            return false;
        });
    }
});

// Payment submission handler
const btnModalPaymentSubmit = document.getElementById('btn_modal_payment_submit');
if (btnModalPaymentSubmit) {
    btnModalPaymentSubmit.addEventListener('click', function () {
        function parsedata(data) {             
            if (!data) return {};
            if (typeof data === 'object') return data;
            if (typeof data === 'string') return JSON.parse(data);
            return {};
        }
        
        const url = window.location.origin + "/invoice/payment/add_with_ajax";
        const params = new URLSearchParams({
            invoice_id: document.getElementById('inv_id').value,
            payment_amount: document.getElementById('amount').value,
            payment_method_id: document.getElementById('payment_method_id').value,
            payment_date: document.getElementById('date').value,
            payment_note: document.getElementById('note').value
        });
        
        fetch(url + '?' + params.toString(), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
        .then(response => response.json())
        .then(data => {
            const response = parsedata(data);
            if (response.success === 1) {
                // The validation was successful and payment was added
                const paymentCfExist = document.getElementById('payment_cf_exist');
                if (paymentCfExist && paymentCfExist.value === 'yes') {
                    // There are payment custom fields, display the payment form
                    window.location = window.location.origin + "/invoice/customfields/add_with_ajax" + response.payment_id;
                }
                else {
                    // There are no payment custom fields, return to invoice view
                    window.location = document.referrer || window.location.href;
                }
            }
            else {
                // The validation was not successful
                document.querySelectorAll('.control-group').forEach(el => {
                    el.classList.remove('has-error');
                });
                for (var key in response.validation_errors) {
                    if(response.validation_errors.hasOwnProperty(key)) {
                        const elem = document.getElementById(key);
                        if (elem && elem.parentElement && elem.parentElement.parentElement) {
                            elem.parentElement.parentElement.classList.add('has-error');
                        }
                    }
                }
            }
        });
    });
}
