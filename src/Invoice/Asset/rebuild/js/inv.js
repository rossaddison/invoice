(function () {
    "use strict";

    // Safe parse helper (mirrors original parsedata)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Global GET helper that serialises arrays as bracketed keys (key[]=v1&key[]=v2)
    function getJson(url, params) {
        var u = url;
        if (params) {
            var sp = new URLSearchParams();
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (Array.isArray(v)) {
                    // Append as key[] so server parses it as an array (matches jQuery behavior)
                    v.forEach(function (x) { sp.append(k + '[]', x); });
                } else if (v !== undefined && v !== null) {
                    sp.append(k, v);
                }
            });
            u = url + (url.indexOf('?') === -1 ? '?' + sp.toString() : '&' + sp.toString());
        }
        return fetch(u, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                return res.text();
            })
            .then(function (text) {
                try { return JSON.parse(text); } catch (e) { return text; }
            });
    }

    // Small helper: safe closest wrapper (some nodes may not have closest or be SVG)
    function closestSafe(el, selector) {
        try {
            if (!el) return null;
            if (typeof el.closest === 'function') return el.closest(selector);
            // fallback: walk up parents
            var node = el;
            while (node) {
                if (node.matches && node.matches(selector)) return node;
                node = node.parentElement;
            }
        } catch (e) {
            return null;
        }
        return null;
    }

    // Find checked ids in #table-invoice
    function getCheckedInvoiceIds() {
        var selected = [];
        var table = document.getElementById('table-invoice');
        if (!table) return selected;
        table.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
            if (cb.id) selected.push(cb.id);
        });
        return selected;
    }

    // Delegated click handler for many inv actions
    document.addEventListener('click', function (e) {
        var el = e.target;

        // btn-mark-as-sent
        var markAsSent = closestSafe(el, '#btn-mark-as-sent');
        if (markAsSent) {
            var btn = document.getElementById('btn-mark-as-sent');
            var originalHtml = btn ? btn.innerHTML : null;
            if (btn) { btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>'; btn.disabled = true; }

            var selected = getCheckedInvoiceIds();

            getJson(location.origin + "/invoice/inv/mark_as_sent", { keylist: selected })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location.reload(true);
                    } else {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location.reload(true);
                    }
                })
                .catch(function (err) {
                    console.error('mark_as_sent error', err);
                    if (btn) { btn.innerHTML = originalHtml || ''; btn.disabled = false; }
                    alert('An error occurred. See console for details.');
                });
            return;
        }

        // btn-mark-sent-as-draft
        var markDraft = closestSafe(el, '#btn-mark-sent-as-draft');
        if (markDraft) {
            var btnD = document.getElementById('btn-mark-sent-as-draft');
            var originalHtmlD = btnD ? btnD.innerHTML : null;
            if (btnD) { btnD.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>'; btnD.disabled = true; }

            var selectedD = getCheckedInvoiceIds();
            getJson(location.origin + "/invoice/inv/mark_sent_as_draft", { keylist: selectedD })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btnD) btnD.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location.reload(true);
                    } else {
                        if (btnD) btnD.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location.reload(true);
                    }
                })
                .catch(function (err) {
                    console.error('mark_sent_as_draft error', err);
                    if (btnD) { btnD.innerHTML = originalHtmlD || ''; btnD.disabled = false; }
                    alert('An error occurred. See console for details.');
                });
            return;
        }

        // Create recurring invoice functionality - matching the successful patterns
        var createRecurring = closestSafe(el, '.create_recurring_confirm_multiple');
        if (createRecurring) {
            var btn = document.querySelector('.create_recurring_confirm_multiple') || createRecurring;
            var orig = btn ? btn.innerHTML : null;
            if (btn) { 
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>'; 
                btn.disabled = true; 
            }

            // Get selected invoice checkboxes
            var selected = getCheckedInvoiceIds();
            if (selected.length === 0) {
                alert('Please select invoices to create recurring invoices.');
                if (btn) { 
                    btn.innerHTML = orig || ''; 
                    btn.disabled = false; 
                }
                return;
            }

            var recur_frequency = (document.getElementById('recur_frequency') || {}).value || '';
            var recur_start_date = (document.getElementById('recur_start_date') || {}).value || '';
            var recur_end_date = (document.getElementById('recur_end_date') || {}).value || '';

            // Validate required fields
            if (!recur_frequency || !recur_start_date) {
                alert('Please select frequency and start date.');
                if (btn) { 
                    btn.innerHTML = orig || ''; 
                    btn.disabled = false; 
                }
                return;
            }

            getJson(location.origin + "/invoice/invrecurring/multiple", {
                keylist: selected,
                recur_start_date: recur_start_date,
                recur_end_date: recur_end_date,
                recur_frequency: recur_frequency
            })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        // Close modal and reload page
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                var modalEl = document.getElementById('create-recurring-multiple');
                                if (modalEl) {
                                    var modalInstance = bootstrap.Modal.getInstance(modalEl);
                                    if (modalInstance) modalInstance.hide();
                                }
                            }
                        } catch (e) {}
                        setTimeout(function() {
                            window.location.reload(true);
                        }, 500);
                    } else {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        alert('Failed to create recurring invoices. Please try again.');
                        if (btn) { 
                            btn.innerHTML = orig || ''; 
                            btn.disabled = false; 
                        }
                    }
                })
                .catch(function (err) {
                    console.error('invrecurring/multiple error', err);
                    if (btn) { 
                        btn.innerHTML = orig || ''; 
                        btn.disabled = false; 
                    }
                    alert('An error occurred while creating recurring invoices. See console for details.');
                });
            return;
        }

        // modal_copy_inv_multiple_confirm
        var copyMultiple = closestSafe(el, '.modal_copy_inv_multiple_confirm');
        if (copyMultiple) {
            var btn = document.querySelector('.modal_copy_inv_multiple_confirm') || copyMultiple;
            var orig = btn ? btn.innerHTML : null;
            if (btn) { btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>'; btn.disabled = true; }
            var modal_created_date = (document.getElementById('modal_created_date') || {}).value || '';
            var selected = getCheckedInvoiceIds();
            getJson(location.origin + "/invoice/inv/multiplecopy", { keylist: selected, modal_created_date: modal_created_date })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location.reload(true);
                    } else {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location.reload(true);
                    }
                })
                .catch(function (err) {
                    console.error('multiplecopy error', err);
                    if (btn) { btn.innerHTML = orig || ''; btn.disabled = false; }
                    alert('An error occurred. See console for details.');
                });
            return;
        }

        // Copy invoice confirm (inv_to_inv_confirm)
        var invToInv = closestSafe(el, '#inv_to_inv_confirm') || closestSafe(el, '.inv_to_inv_confirm');
        if (invToInv) {
            var url = location.origin + "/invoice/inv/inv_to_inv_confirm";
            var btn = document.querySelector('.inv_to_inv_confirm') || invToInv;
            var absolute_url = new URL(location.href);
            if (btn) { btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>'; btn.disabled = true; }
            var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            var payload = {
                inv_id: inv_id,
                client_id: (document.getElementById('create_inv_client_id') || {}).value || '',
                user_id: (document.getElementById('user_id') || {}).value || ''
            };
            getJson(url, payload)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        // Redirect to the newly created invoice
                        if (response.new_invoice_id) {
                            window.location = location.origin + "/invoice/inv/view/" + response.new_invoice_id;
                        } else {
                            // Fallback to reload current page if new_invoice_id not provided
                            window.location = absolute_url;
                            window.location.reload();
                        }
                    } else {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location = absolute_url;
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    console.error('inv_to_inv_confirm error', err);
                    if (btn) { btn.innerHTML = ''; btn.disabled = false; }
                    alert('An error occurred. See console for details.');
                });
            return;
        }

        // inv to pdf / html and modal iframe handlers
        if (closestSafe(el, '#inv_to_pdf_confirm_with_custom_fields')) {
            var url = location.origin + "/invoice/inv/pdf/1";
            window.open(url, '_blank');
            return;
        }
        if (closestSafe(el, '#inv_to_pdf_confirm_without_custom_fields')) {
            var url0 = location.origin + "/invoice/inv/pdf/0";
            window.open(url0, '_blank');
            return;
        }
        if (closestSafe(el, '#inv_to_modal_pdf_confirm_with_custom_fields')) {
            var url1 = location.origin + "/invoice/inv/pdf/1";
            var iframe = document.getElementById('modal-view-inv-pdf');
            if (iframe) iframe.setAttribute('src', url1);
            try { if (typeof bootstrap !== 'undefined' && bootstrap.Modal) { var m = document.getElementById('modal-layout-modal-pdf-inv'); if (m) new bootstrap.Modal(m).show(); } } catch (e) {}
            return;
        }
        if (closestSafe(el, '#inv_to_modal_pdf_confirm_without_custom_fields')) {
            var url2 = location.origin + "/invoice/inv/pdf/0";
            var iframe2 = document.getElementById('modal-view-inv-pdf');
            if (iframe2) iframe2.setAttribute('src', url2);
            try { if (typeof bootstrap !== 'undefined' && bootstrap.Modal) { var m2 = document.getElementById('modal-layout-modal-pdf-inv'); if (m2) new bootstrap.Modal(m2).show(); } } catch (e) {}
            return;
        }
        if (closestSafe(el, '#inv_to_html_confirm_with_custom_fields')) {
            var url3 = location.origin + "/invoice/inv/html/1";
            window.open(url3, '_blank');
            return;
        }
        if (closestSafe(el, '#inv_to_html_confirm_without_custom_fields')) {
            var url4 = location.origin + "/invoice/inv/html/0";
            window.open(url4, '_blank');
            return;
        }

        // Payment modal submit (button with id btn_modal_payment_submit)
        var btnPayment = closestSafe(el, '#btn_modal_payment_submit');
        if (btnPayment) {
            var url = location.origin + "/invoice/payment/add_with_ajax";
            var payload = {
                invoice_id: (document.getElementById('inv_id') || {}).value || '',
                payment_amount: (document.getElementById('amount') || {}).value || '',
                payment_method_id: (document.getElementById('payment_method_id') || {}).value || '',
                payment_date: (document.getElementById('date') || {}).value || '',
                payment_note: (document.getElementById('note') || {}).value || ''
            };
            getJson(url, payload)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if ((document.getElementById('payment_cf_exist') || {}).value === 'yes') {
                            window.location = location.origin + "/invoice/customfields/add_with_ajax" + (response.payment_id || '');
                        } else {
                            try {
                                if (document.referrer) window.location = document.referrer;
                            } catch (e) { window.location.reload(); }
                        }
                    } else {
                        // show validation errors
                        Array.from(document.querySelectorAll('.control-group')).forEach(function (g) { g.classList.remove('has-error'); });
                        if (response.validation_errors) {
                            Object.keys(response.validation_errors).forEach(function (key) {
                                var elKey = document.getElementById(key);
                                if (elKey && elKey.parentElement && elKey.parentElement.parentElement) {
                                    elKey.parentElement.parentElement.classList.add('has-error');
                                }
                            });
                        }
                    }
                })
                .catch(function (err) {
                    console.error('payment add error', err);
                    alert('An error occurred while adding payment. See console for details.');
                });
            return;
        }

        // Add Invoice Tax functionality - matching the quote.js pattern
        var invTaxSubmit = el.closest('#inv_tax_submit');
        if (invTaxSubmit) {
            e.preventDefault();
            
            var url = location.origin + "/invoice/inv/save_inv_tax_rate";
            var btn = invTaxSubmit;
            var absolute_url = new URL(location.href);
            
            if (btn) {
                var origTax = btn.innerHTML;
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btn.disabled = true;
            }
            
            // Get invoice id from URL
            var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            var payloadTax = {
                inv_id: inv_id,
                inv_tax_rate_id: (document.getElementById('inv_tax_rate_id') || {}).value || '',
                include_inv_item_tax: (document.getElementById('include_inv_item_tax') || {}).value || ''
            };
            
            getJson(url, payloadTax)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    } else {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    }
                })
                .catch(function (err) {
                    console.error('inv_tax_submit error', err);
                    if (btn) {
                        btn.innerHTML = origTax || '';
                        btn.disabled = false;
                    }
                    alert('An error occurred while adding invoice tax. Please try again.');
                });
            return;
        }

        // delete-items-confirm-inv (modal confirm button for deleting items)
        var deleteItemsConfirm = closestSafe(el, '.delete-items-confirm-inv') || closestSafe(el, '#delete-items-confirm-inv');
        if (deleteItemsConfirm) {
            var btn = document.querySelector('.delete-items-confirm-inv') || deleteItemsConfirm;
            var orig = btn ? btn.innerHTML : null;
            if (btn) { 
                btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>'; 
                btn.disabled = true; 
            }

            // Get selected item checkboxes from the modal table
            var selected = [];
            var modal = document.getElementById('delete-items');
            if (modal) {
                modal.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
                    if (cb.value) selected.push(cb.value);
                });
            }

            // Get invoice ID from current URL
            var currentUrl = new URL(location.href);
            var inv_id = currentUrl.pathname.split('/').pop();

            if (selected.length === 0) {
                alert('Please select items to delete.');
                if (btn) { 
                    btn.innerHTML = orig || ''; 
                    btn.disabled = false; 
                }
                return;
            }

            getJson(location.origin + "/invoice/invitem/multiple", { 
                item_ids: selected,
                inv_id: inv_id 
            })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    if (btn) btn.innerHTML = '<i class="fa fa-check"></i>';
                    // Close modal and reload page
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            var modalEl = document.getElementById('delete-items');
                            if (modalEl) {
                                var modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) modalInstance.hide();
                            }
                        }
                    } catch (e) {}
                    setTimeout(function() {
                        window.location.reload(true);
                    }, 500);
                } else {
                    if (btn) btn.innerHTML = '<i class="fa fa-times"></i>';
                    alert('Failed to delete items. Please try again.');
                    if (btn) { 
                        btn.innerHTML = orig || ''; 
                        btn.disabled = false; 
                    }
                }
            })
            .catch(function (err) {
                console.error('delete items error', err);
                if (btn) { 
                    btn.innerHTML = orig || ''; 
                    btn.disabled = false; 
                }
                alert('An error occurred while deleting items. See console for details.');
            });
            return;
        }

    }, true);

    // Delegated handler for saving client notes etc.
    document.addEventListener('click', function (e) {
        var el = e.target;
        var saveBtn = closestSafe(el, '#save_client_note');
        if (!saveBtn) return;
        var url = location.origin + "/invoice/client/save_client_note";
        var loadUrl = location.origin + "/invoice/client/load_client_notes";
        var client_id = (document.getElementById('client_id') || {}).value || '';
        var client_note = (document.getElementById('client_note') || {}).value || '';
        getJson(url, { client_id: client_id, client_note: client_note })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    Array.from(document.querySelectorAll('.control-group')).forEach(function (g) { g.classList.remove('error'); });
                    var noteEl = document.getElementById('client_note');
                    if (noteEl) noteEl.value = '';
                    var notesList = document.getElementById('notes_list');
                    if (notesList) {
                        var u = loadUrl + '?client_id=' + encodeURIComponent(client_id);
                        fetch(u, { cache: 'no-store', credentials: 'same-origin' })
                            .then(function (r) { return r.text(); })
                            .then(function (html) { notesList.innerHTML = html; })
                            .catch(function (err) { console.error('load_client_notes failed', err); });
                    }
                } else {
                    Array.from(document.querySelectorAll('.control-group')).forEach(function (g) { g.classList.remove('error'); });
                    if (response.validation_errors) {
                        Object.keys(response.validation_errors).forEach(function (key) {
                            var elm = document.getElementById(key);
                            if (elm && elm.parentElement) elm.parentElement.classList.add('has-error');
                        });
                    }
                }
            })
            .catch(function (err) {
                console.error('save_client_note error', err);
                alert('Status: error An error occurred');
            });
    }, true);

    // Input listeners for discount fields (mirrors original interlock behavior)
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el) return;
        if (el.id === 'inv_discount_amount') {
            var percent = document.getElementById('inv_discount_percent');
            if (percent) {
                if (el.value.length > 0) { percent.value = '0.00'; percent.disabled = true; } else { percent.disabled = false; }
            }
        } else if (el.id === 'inv_discount_percent') {
            var amount = document.getElementById('inv_discount_amount');
            if (amount) {
                if (el.value.length > 0) { amount.value = '0.00'; amount.disabled = true; } else { amount.disabled = false; }
            }
        }
    }, true);

    // Datepicker focus handlers: call jQuery UI datepicker if available (keeps original behaviour)
    document.addEventListener('focus', function (e) {
        var el = e.target;
        if (!el) return;
        if (el.id === 'datepicker') {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.datepicker === 'function') {
                window.jQuery(el).datepicker({
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    dateFormat: 'dd-mm-yy'
                });
            }
        }
        if (el.classList && el.classList.contains('datepicker')) {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.datepicker === 'function') {
                window.jQuery(el).datepicker({
                    beforeShow: function () {
                        setTimeout(function () {
                            Array.from(document.querySelectorAll('.datepicker')).forEach(function (d) {
                                d.style.zIndex = '9999';
                            });
                        }, 0);
                    }
                });
            }
        }
    }, true);

    // Keep track of last taggable focused element
    document.addEventListener('focus', function (e) {
        var el = e.target;
        if (el && el.classList && el.classList.contains('taggable')) {
            window.lastTaggableClicked = el;
        }
    }, true);

    // Initialize tooltips (bootstrap)
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (t) {
                try { new bootstrap.Tooltip(t); } catch (err) { /* ignore */ }
            });
        }
    });

    // Handle create credit confirm button - id="create_credit_confirm" on views/inv/modal_create_credit.php
    document.addEventListener('click', function (e) {
        var el = e.target;
        if (el && el.id === 'create-credit-confirm') {
            e.preventDefault();
            
            var url = location.origin + "/invoice/inv/create_credit_confirm";
            var btn = document.querySelector('.create-credit-confirm');
            var absolute_url = new URL(location.href);
            
            if (btn) {
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            }
            
            // Take the inv id from the public url
            var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            
            // Get form values
            var client_id = (document.getElementById('client_id') || {}).value || '';
            var inv_date_created = (document.getElementById('inv_date_created') || {}).value || '';
            var group_id = (document.getElementById('inv_group_id') || {}).value || '';
            var password = (document.getElementById('inv_password') || {}).value || '';
            var user_id = (document.getElementById('user_id') || {}).value || '';
            
            getJson(url, {
                inv_id: inv_id,
                client_id: client_id,
                inv_date_created: inv_date_created,
                group_id: group_id,
                password: password,
                user_id: user_id
            })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and inv was created
                    if (btn) {
                        btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';
                    }
                    location.href = absolute_url.href;
                    location.reload();
                    alert(response.flash_message);
                }
                if (response.success === 0) {
                    if (btn) {
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    }
                    location.href = absolute_url.href;
                    location.reload();
                    // Display the 'unsuccessful' message
                    alert(response.flash_message);
                }
            })
            .catch(function (error) {
                console.warn('Create credit error:', error);
                alert('Status: error - An error: ' + error.toString());
            });
        }
    }, true);

})();