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
    
     // class="btn_delete_item" on views/product/partial_item_table.php
    document.querySelectorAll('.btn_delete_item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');  
            if (typeof id === 'undefined') {
                this.closest('.item')?.remove();
            } else {
                const url = window.location.origin + "/invoice/quote/delete_item/"+id;
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
        if (e.target.closest('.delete-items-confirm-quote')) {
            const btn = document.querySelector('.delete-items-confirm-quote');
            btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
            const item_ids = [];
            document.querySelectorAll("input[name='item_ids[]']:checked").forEach(function (checkbox) {
                item_ids.push(parseInt(checkbox.value));
            });
            
            const params = new URLSearchParams();
            item_ids.forEach(id => params.append('item_ids[]', id));
            
            fetch('/invoice/quoteitem/multiple?' + params.toString(), {
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
     
    document.querySelectorAll('.btn_add_row_modal').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const absolute_url = new URL(window.location.href);
            const quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1); 
            const url = window.location.origin + "/invoice/quoteitem/add/"+quote_id;
            const modalPlaceholder = document.getElementById('modal-placeholder-quoteitem');
            if (modalPlaceholder) {
                console.log('Load URL:', url);
            }
        });
    });

    document.querySelectorAll('.btn_quote_item_add_row').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const newRow = document.getElementById('new_quote_item_row');
            if (newRow) {
                const clone = newRow.cloneNode(true);
                clone.removeAttribute('id');
                clone.classList.add('item');
                clone.style.display = 'block';
                document.getElementById('item_table').appendChild(clone);
            }
        });
    });
    
    // class="btn_add_row" on views/quote/partial_item_table.php
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

    document.querySelectorAll('.quote_add_client').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const url = window.location.origin + "/invoice/add-a-client";
            const modalPlaceholder = document.getElementById('modal-placeholder-client');
            if (modalPlaceholder) {
                console.log('Load URL:', url);
            }
        });
    });

    const saveClientNoteBtn = document.getElementById('save_client_note');
    if (saveClientNoteBtn) {
        saveClientNoteBtn.addEventListener('click', function () {
            const url = window.location.origin + "/invoice/client/save_client_note";
            const load = window.location.origin + "/invoice/client/load_client_notes";
            const client_id = document.getElementById('client_id').value;
            
            const params = new URLSearchParams({
                client_id: client_id,
                client_note: document.getElementById('client_note').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful
                    document.querySelectorAll('.control-group').forEach(el => {
                        el.classList.remove('error');
                    });
                    document.getElementById('client_note').value = '';
                    
                    // Reload all notes
                    const notesList = document.getElementById('notes_list');
                    if (notesList) {
                        const noteParams = new URLSearchParams({ client_id: client_id });
                        fetch(load + '?' + noteParams.toString())
                            .then(response => response.text())
                            .then(html => {
                                notesList.innerHTML = html;
                                console.log(html);
                            });
                    }
                } else {
                    // The validation was not successful
                    document.querySelectorAll('.control-group').forEach(el => {
                        el.classList.remove('error');
                    });
                    for (var key in response.validation_errors) {
                        const elem = document.getElementById(key);
                        if (elem && elem.parentElement) {
                            elem.parentElement.classList.add('has-error');
                        }
                    }
                }
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        });
    }

    // id="quote_tax_submit" in drop down menu on views/quote/view.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_tax_submit' || e.target.closest('#quote_tax_submit')) {
            const url = window.location.origin + "/invoice/quote/save_quote_tax_rate";
            const btn = document.querySelector('.quote_tax_submit');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                quote_id: quote_id,
                tax_rate_id: document.getElementById('tax_rate_id').value,
                include_item_tax: document.getElementById('include_item_tax').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                window.location = absolute_url;
                window.location.reload();
                alert(response.flash_message); 
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });

    // id="quote_create_confirm button on views/quote/modal_create_quote.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_create_confirm' || e.target.closest('#quote_create_confirm')) {
            const url = window.location.origin + "/invoice/quote/create_confirm";
            const btn = document.querySelector('.quote_create_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            
            const params = new URLSearchParams({
                client_id: document.getElementById('create_quote_client_id').value,
                quote_group_id: document.getElementById('quote_group_id').value,
                quote_password: document.getElementById('quote_password').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and quote was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                }
                const message = response.message;
                if (response.success === 0) {
                    // The validation was unsuccessful and inv was not created
                    btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(message);
                }  
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });
    
    // id="quote_with_purchase_order_number_confirm button on views/quote/modal_purchase_order_number.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_with_purchase_order_number_confirm' || e.target.closest('#quote_with_purchase_order_number_confirm')) {
            const url = window.location.origin + "/invoice/quote/approve";
            const btn = document.querySelector('.quote_with_purchase_order_number_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            
            const params = new URLSearchParams({
                url_key: document.getElementById('url_key').value,
                client_po_number: document.getElementById('quote_with_purchase_order_number').value,
                client_po_person: document.getElementById('quote_with_purchase_order_person').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and quote with purchase order number was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
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

    // Creates the invoice
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_to_invoice_confirm' || e.target.closest('#quote_to_invoice_confirm')) {
            const url = window.location.origin + "/invoice/quote/quote_to_invoice_confirm";
            const btn = document.querySelector('.quote_to_invoice_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                quote_id: quote_id,
                client_id: document.getElementById('client_id').value,
                group_id: document.getElementById('group_id').value,
                password: document.getElementById('password').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and invoice was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(response.flash_message);
                }
                if (response.success === 0) {
                    // The validation was not successful created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
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
    
    // Creates the purchase order
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_to_so_confirm' || e.target.closest('#quote_to_so_confirm')) {
            const url = window.location.origin + "/invoice/quote/quote_to_so_confirm";
            const btn = document.querySelector('.quote_to_so_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                quote_id: quote_id,
                client_id: document.getElementById('client_id').value,
                group_id: document.getElementById('so_group_id').value,
                po: document.getElementById('po_number').value,
                person: document.getElementById('po_person').value,
                password: document.getElementById('password').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and invoice was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(response.flash_message);
                }
                if (response.success === 0) {
                    // The validation was not successfully created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
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
   
    // Copies the quote to a specific client
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_to_quote_confirm' || e.target.closest('#quote_to_quote_confirm')) {
            const url = window.location.origin + "/invoice/quote/quote_to_quote_confirm";
            const btn = document.querySelector('.quote_to_quote_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            const quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            const params = new URLSearchParams({
                quote_id: quote_id,
                client_id: document.getElementById('create_quote_client_id').value,
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
                    // The validation was successful and quote was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
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
    
    // PDF generation handlers
    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_to_pdf_confirm_with_custom_fields' || e.target.closest('#quote_to_pdf_confirm_with_custom_fields')) {
            const url = window.location.origin + "/invoice/quote/pdf/1";    
            window.open(url, '_blank');
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.id === 'quote_to_pdf_confirm_without_custom_fields' || e.target.closest('#quote_to_pdf_confirm_without_custom_fields')) {
            const url = window.location.origin + "/invoice/quote/pdf/0";    
            window.open(url, '_blank');
        }
    });

    const quoteDiscountAmount = document.getElementById('quote_discount_amount');
    if (quoteDiscountAmount) {
        quoteDiscountAmount.addEventListener('keyup', function () {
            const quoteDiscountPercent = document.getElementById('quote_discount_percent');
            if (this.value.length > 0) {
                quoteDiscountPercent.value = '0.00';
                quoteDiscountPercent.disabled = true;
            } else {
                quoteDiscountPercent.disabled = false;
            }
        });
    }
    
    const quoteDiscountPercent = document.getElementById('quote_discount_percent');
    if (quoteDiscountPercent) {
        quoteDiscountPercent.addEventListener('keyup', function () {
            const quoteDiscountAmount = document.getElementById('quote_discount_amount');
            if (this.value.length > 0) {
                quoteDiscountAmount.value = '0.00';
                quoteDiscountAmount.disabled = true;
            } else {
                quoteDiscountAmount.disabled = false;
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
