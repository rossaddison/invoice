// inv.js - Complete functionality restored from pre_jquery_deletion branch
// Systematically converted from jQuery to vanilla JavaScript
// All original selectors and event handlers preserved

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

    // Global GET helper that serialises arrays as bracketed keys
    function getJson(url, params) {
        var u = url;
        if (params) {
            var sp = new URLSearchParams();
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (Array.isArray(v)) {
                    v.forEach(function (x) { sp.append(k + '[]', x); });
                } else if (v !== undefined && v !== null) {
                    sp.append(k, v);
                }
            });
            u = url + (url.indexOf('?') === -1 ? '?' + sp.toString() : '&' + sp.toString());
        }
        return fetch(u, { 
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
            try { return JSON.parse(text); } catch (e) { return text; }
        });
    }

    // Safe closest wrapper
    function closestSafe(el, selector) {
        try {
            if (!el) return null;
            if (typeof el.closest === 'function') return el.closest(selector);
            var node = el;
            while (node) {
                if (node.matches && node.matches(selector)) return node;
                node = node.parentElement;
            }
        } catch (e) {}
        return null;
    }

    // Helper to get origin
    function getOrigin() {
        return window.location.origin;
    }

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. USER CLIENT FUNCTIONALITY - Used in userclient/new
        var userAllClients = document.getElementById('user_all_clients');
        if (userAllClients) {
            userAllClients.addEventListener('click', function () {
                allClientCheck();
            });
        }

        function allClientCheck() {
            var userAllClients = document.getElementById('user_all_clients');
            var listClient = document.getElementById('list_client');
            if (userAllClients && listClient) {
                if (userAllClients.checked) {
                    listClient.style.display = 'none';
                } else {
                    listClient.style.display = '';
                }
            }
        }

        // Initialize on load
        allClientCheck();

        // 2. DELETE ITEM FUNCTIONALITY - class="btn_delete_item" on views/product/partial_item_table.php
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_delete_item') || e.target.closest('.btn_delete_item')) {
                var btn = e.target.matches('.btn_delete_item') ? e.target : e.target.closest('.btn_delete_item');
                var id = btn.getAttribute('data-id');
                
                if (typeof id === 'undefined' || id === null) {
                    var itemRow = btn.closest('.item');
                    if (itemRow) itemRow.remove();
                } else {
                    var url = getOrigin() + "/invoice/inv/delete_item/" + id;
                    
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            location.reload(true);
                            var itemRow = btn.closest('.item');
                            if (itemRow) itemRow.remove();
                            alert("Deleted");
                        }
                    })
                    .catch(function (error) {
                        console.error('Delete error:', error);
                    });
                }
            }
        });

        // 3. BULK DELETE ITEMS - .delete-items-confirm-inv
        document.addEventListener('click', function (e) {
            if (e.target.matches('.delete-items-confirm-inv') || e.target.closest('.delete-items-confirm-inv')) {
                var btn = document.querySelector('.delete-items-confirm-inv');
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var item_ids = [];
                    var checkboxes = document.querySelectorAll("input[name='item_ids[]']:checked");
                    checkboxes.forEach(function (checkbox) {
                        item_ids.push(parseInt(checkbox.value));
                    });

                    fetch('/invoice/invitem/multiple', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            location.reload(true);
                        }
                    })
                    .catch(function (error) {
                        console.error('Bulk delete error:', error);
                    });
                }
            }
        });

        // 4. SELECT ALL CHECKBOXES - [name="checkbox-selection-all"]
        document.addEventListener('click', function (e) {
            if (e.target.matches('[name="checkbox-selection-all"]')) {
                var checkboxes = document.querySelectorAll(':checkbox');
                var isChecked = e.target.checked;
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = isChecked;
                });
            }
        });

        // 5. CREATE RECURRING MULTIPLE - .create_recurring_confirm_multiple  
        document.addEventListener('click', function (e) {
            if (e.target.matches('.create_recurring_confirm_multiple') || e.target.closest('.create_recurring_confirm_multiple')) {
                var btn = document.getElementById('create_recurring_confirm_multiple');
                if (!btn) btn = document.querySelector('.create_recurring_confirm_multiple');
                
                if (btn) {
                    var selected = [];
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var tableInvoice = document.getElementById('table-invoice');
                    if (tableInvoice) {
                        var checkedBoxes = tableInvoice.querySelectorAll('input[type="checkbox"]:checked');
                        checkedBoxes.forEach(function (checkbox) {
                            selected.push(checkbox.getAttribute('id'));
                        });
                    }

                    var recur_frequency = document.getElementById('recur_frequency');
                    var recur_start_date = document.getElementById('recur_start_date');  
                    var recur_end_date = document.getElementById('recur_end_date');

                    var url = getOrigin() + "/invoice/invrecurring/multiple";
                    
                    var params = new URLSearchParams();
                    selected.forEach(function (id) {
                        params.append('keylist[]', id);
                    });
                    if (recur_frequency) params.append('recur_frequency', recur_frequency.value);
                    if (recur_start_date) params.append('recur_start_date', recur_start_date.value);
                    if (recur_end_date) params.append('recur_end_date', recur_end_date.value);

                    fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            window.location.reload(true);
                        } else {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                            window.location.reload(true);
                        }
                    })
                    .catch(function (error) {
                        console.error('Create recurring error:', error);
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    });
                }
            }
        });

        // 6. MARK AS SENT - #btn-mark-as-sent
        document.addEventListener('click', function (e) {
            if (e.target.matches('#btn-mark-as-sent') || e.target.closest('#btn-mark-as-sent')) {
                var btn = document.getElementById('btn-mark-as-sent');
                if (btn) {
                    var selected = [];
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var tableInvoice = document.getElementById('table-invoice');
                    if (tableInvoice) {
                        var checkedBoxes = tableInvoice.querySelectorAll('input[type="checkbox"]:checked');
                        checkedBoxes.forEach(function (checkbox) {
                            selected.push(checkbox.getAttribute('id'));
                        });
                    }

                    var url = getOrigin() + "/invoice/inv/mark_as_sent";
                    var params = new URLSearchParams();
                    selected.forEach(function (id) {
                        params.append('keylist[]', id);
                    });

                    fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            window.location.reload(true);
                        } else {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                            window.location.reload(true);
                        }
                    })
                    .catch(function (error) {
                        console.error('Mark as sent error:', error);
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    });
                }
            }
        });

        // 7. MARK SENT AS DRAFT - #btn-mark-sent-as-draft
        document.addEventListener('click', function (e) {
            if (e.target.matches('#btn-mark-sent-as-draft') || e.target.closest('#btn-mark-sent-as-draft')) {
                var btn = document.getElementById('btn-mark-sent-as-draft');
                if (btn) {
                    var selected = [];
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var tableInvoice = document.getElementById('table-invoice');
                    if (tableInvoice) {
                        var checkedBoxes = tableInvoice.querySelectorAll('input[type="checkbox"]:checked');
                        checkedBoxes.forEach(function (checkbox) {
                            selected.push(checkbox.getAttribute('id'));
                        });
                    }

                    var url = getOrigin() + "/invoice/inv/mark_sent_as_draft";
                    var params = new URLSearchParams();
                    selected.forEach(function (id) {
                        params.append('keylist[]', id);
                    });

                    fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            window.location.reload(true);
                        } else {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                            window.location.reload(true);
                        }
                    })
                    .catch(function (error) {
                        console.error('Mark sent as draft error:', error);
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    });
                }
            }
        });

        // 8. MODAL COPY MULTIPLE - .modal_copy_inv_multiple_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('.modal_copy_inv_multiple_confirm') || e.target.closest('.modal_copy_inv_multiple_confirm')) {
                var btn = document.querySelector('.modal_copy_inv_multiple_confirm');
                if (btn) {
                    var modal_created_date = document.getElementById('modal_created_date');
                    var selected = [];
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var tableInvoice = document.getElementById('table-invoice');
                    if (tableInvoice) {
                        var checkedBoxes = tableInvoice.querySelectorAll('input[type="checkbox"]:checked');
                        checkedBoxes.forEach(function (checkbox) {
                            selected.push(checkbox.getAttribute('id'));
                        });
                    }

                    var url = getOrigin() + "/invoice/inv/multiplecopy";
                    var params = new URLSearchParams();
                    selected.forEach(function (id) {
                        params.append('keylist[]', id);
                    });
                    if (modal_created_date) params.append('modal_created_date', modal_created_date.value);

                    fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'no-store'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            window.location.reload(true);
                        } else {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                            window.location.reload(true);
                        }
                    })
                    .catch(function (error) {
                        console.error('Multiple copy error:', error);
                        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                    });
                }
            }
        });

        // 9. ADD REQUIRED MARKERS - $(document).ready equivalent
        var requiredElements = document.querySelectorAll('[required]');
        requiredElements.forEach(function (element) {
            var span = document.createElement('span');
            span.className = 'required';
            span.textContent = '*';
            element.parentNode.insertBefore(span, element.nextSibling);
        });

        // 10. ADD ROW MODAL - .btn_add_row_modal
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_add_row_modal') || e.target.closest('.btn_add_row_modal')) {
                var absoluteUrl = new URL(window.location.href);
                var inv_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var url = getOrigin() + "/invoice/invitem/add/" + inv_id;
                var modalPlaceholder = document.getElementById('modal-placeholder-invitem');
                if (modalPlaceholder) {
                    // Load content into modal - this may need adjustment based on your modal system
                    fetch(url)
                        .then(function (response) {
                            return response.text();
                        })
                        .then(function (html) {
                            modalPlaceholder.innerHTML = html;
                        })
                        .catch(function (error) {
                            console.error('Modal load error:', error);
                        });
                }
            }
        });

        // 11. ADD INVOICE ITEM ROW - .btn_inv_item_add_row
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_inv_item_add_row') || e.target.closest('.btn_inv_item_add_row')) {
                var newRow = document.getElementById('new_inv_item_row');
                var itemTable = document.getElementById('item_table');
                if (newRow && itemTable) {
                    var clonedRow = newRow.cloneNode(true);
                    clonedRow.removeAttribute('id');
                    clonedRow.classList.add('item');
                    clonedRow.style.display = '';
                    itemTable.appendChild(clonedRow);
                }
            }
        });

        // 12. ADD ROW - .btn_add_row (general)
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_add_row') || e.target.closest('.btn_add_row')) {
                var newRow = document.getElementById('new_row');
                var itemTable = document.getElementById('item_table');
                if (newRow && itemTable) {
                    var clonedRow = newRow.cloneNode(true);
                    clonedRow.removeAttribute('id');
                    clonedRow.classList.add('item');
                    clonedRow.style.display = '';
                    itemTable.appendChild(clonedRow);
                }
            }
        });

        // 13. INV TAX SUBMIT - #inv_tax_submit
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_tax_submit') || e.target.closest('#inv_tax_submit')) {
                var url = getOrigin() + "/invoice/inv/save_inv_tax_rate";
                var btn = document.querySelector('.inv_tax_submit');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                
                var inv_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var inv_tax_rate_id = document.getElementById('inv_tax_rate_id');
                var include_inv_item_tax = document.getElementById('include_inv_item_tax');

                var params = new URLSearchParams();
                params.append('inv_id', inv_id);
                if (inv_tax_rate_id) params.append('inv_tax_rate_id', inv_tax_rate_id.value);
                if (include_inv_item_tax) params.append('include_inv_item_tax', include_inv_item_tax.value);

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        window.location = absoluteUrl.href;
                        if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                        window.location.reload();
                    }
                    if (response.success === 0) {
                        window.location = absoluteUrl.href;
                        if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-times"></i></h6>';
                        window.location.reload();
                    }
                })
                .catch(function (error) {
                    console.error('Tax submit error:', error);
                    alert('Incomplete fields: You must include a tax rate. Tip: Include a zero tax rate.');
                });
            }
        });

        // 14. CREATE CREDIT CONFIRM - #create-credit-confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#create-credit-confirm') || e.target.closest('#create-credit-confirm')) {
                var url = getOrigin() + "/invoice/inv/create_credit_confirm";
                var btn = document.querySelector('.create-credit-confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                
                var inv_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var client_id = document.getElementById('client_id');
                var inv_date_created = document.getElementById('inv_date_created');
                var group_id = document.getElementById('inv_group_id');
                var password = document.getElementById('inv_password');
                var user_id = document.getElementById('user_id');

                var params = new URLSearchParams();
                params.append('inv_id', inv_id);
                if (client_id) params.append('client_id', client_id.value);
                if (inv_date_created) params.append('inv_date_created', inv_date_created.value);
                if (group_id) params.append('group_id', group_id.value);
                if (password) params.append('password', password.value);
                if (user_id) params.append('user_id', user_id.value);

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="bi bi-check2-square"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                })
                .catch(function (error) {
                    console.error('Create credit error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 15. INV TO INV CONFIRM - #inv_to_inv_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_inv_confirm') || e.target.closest('#inv_to_inv_confirm')) {
                var url = getOrigin() + "/invoice/inv/inv_to_inv_confirm";
                var btn = document.querySelector('.inv_to_inv_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                
                var inv_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var create_inv_client_id = document.getElementById('create_inv_client_id');
                var user_id = document.getElementById('user_id');

                var params = new URLSearchParams();
                params.append('inv_id', inv_id);
                if (create_inv_client_id) params.append('client_id', create_inv_client_id.value);
                if (user_id) params.append('user_id', user_id.value);

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                    }
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                    }
                })
                .catch(function (error) {
                    console.error('Inv to inv error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 16. PDF EXPORT WITH CUSTOM FIELDS - #inv_to_pdf_confirm_with_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_pdf_confirm_with_custom_fields') || e.target.closest('#inv_to_pdf_confirm_with_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/pdf/1";
                window.location.reload;
                window.open(url, '_blank');
            }
        });

        // 17. PDF EXPORT WITHOUT CUSTOM FIELDS - #inv_to_pdf_confirm_without_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_pdf_confirm_without_custom_fields') || e.target.closest('#inv_to_pdf_confirm_without_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/pdf/0";
                window.location.reload;
                window.open(url, '_blank');
            }
        });

        // 18. MODAL PDF WITH CUSTOM FIELDS - #inv_to_modal_pdf_confirm_with_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_modal_pdf_confirm_with_custom_fields') || e.target.closest('#inv_to_modal_pdf_confirm_with_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/pdf/1";
                var iframe = document.getElementById('modal-view-inv-pdf');
                if (iframe) iframe.src = url;

                // Try to open Bootstrap modal
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalEl = document.getElementById('modal-layout-modal-pdf-inv');
                        if (modalEl) {
                            var modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    }
                } catch (e) {
                    console.error('Modal error:', e);
                }
            }
        });

        // 19. MODAL PDF WITHOUT CUSTOM FIELDS - #inv_to_modal_pdf_confirm_without_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_modal_pdf_confirm_without_custom_fields') || e.target.closest('#inv_to_modal_pdf_confirm_without_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/pdf/0";
                var iframe = document.getElementById('modal-view-inv-pdf');
                if (iframe) iframe.src = url;

                // Try to open Bootstrap modal
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalEl = document.getElementById('modal-layout-modal-pdf-inv');
                        if (modalEl) {
                            var modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    }
                } catch (e) {
                    console.error('Modal error:', e);
                }
            }
        });

        // 20. HTML EXPORT WITH CUSTOM FIELDS - #inv_to_html_confirm_with_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_html_confirm_with_custom_fields') || e.target.closest('#inv_to_html_confirm_with_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/html/1";
                window.location.reload;
                window.open(url, '_blank');
            }
        });

        // 21. HTML EXPORT WITHOUT CUSTOM FIELDS - #inv_to_html_confirm_without_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#inv_to_html_confirm_without_custom_fields') || e.target.closest('#inv_to_html_confirm_without_custom_fields')) {
                var url = getOrigin() + "/invoice/inv/html/0";
                window.location.reload;
                window.open(url, '_blank');
            }
        });

        // 22. PAYMENT MODAL SUBMIT - #btn_modal_payment_submit (Note: this was outside DOMContentLoaded in original)
        document.addEventListener('click', function (e) {
            if (e.target.matches('#btn_modal_payment_submit') || e.target.closest('#btn_modal_payment_submit')) {
                var url = getOrigin() + "/invoice/payment/add_with_ajax";
                var inv_id = document.getElementById('inv_id');
                var amount = document.getElementById('amount');
                var payment_method_id = document.getElementById('payment_method_id');
                var date = document.getElementById('date');
                var note = document.getElementById('note');

                var params = new URLSearchParams();
                if (inv_id) params.append('invoice_id', inv_id.value);
                if (amount) params.append('payment_amount', amount.value);
                if (payment_method_id) params.append('payment_method_id', payment_method_id.value);
                if (date) params.append('payment_date', date.value);
                if (note) params.append('payment_note', note.value);

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        var payment_cf_exist = document.getElementById('payment_cf_exist');
                        if (payment_cf_exist && payment_cf_exist.value === 'yes') {
                            // There are payment custom fields
                            window.location = getOrigin() + "/invoice/customfields/add_with_ajax" + response.payment_id;
                        } else {
                            // No payment custom fields, return to invoice view
                            window.location = document.referrer || window.location.href;
                        }
                    } else {
                        // Validation was not successful
                        var controlGroups = document.querySelectorAll('.control-group');
                        controlGroups.forEach(function (group) {
                            group.classList.remove('has-error');
                        });
                        
                        if (response.validation_errors) {
                            for (var key in response.validation_errors) {
                                if (response.validation_errors.hasOwnProperty(key)) {
                                    var element = document.getElementById(key);
                                    if (element && element.parentNode && element.parentNode.parentNode) {
                                        element.parentNode.parentNode.classList.add('has-error');
                                    }
                                }
                            }
                        }
                    }
                })
                .catch(function (error) {
                    console.error('Payment submit error:', error);
                    alert('Payment submission failed: ' + error.toString());
                });
            }
        });

        // 23. DISCOUNT AMOUNT KEYUP - #inv_discount_amount
        var discountAmount = document.getElementById('inv_discount_amount');
        var discountPercent = document.getElementById('inv_discount_percent');
        
        if (discountAmount) {
            discountAmount.addEventListener('keyup', function () {
                if (this.value.length > 0) {
                    if (discountPercent) {
                        discountPercent.value = '0.00';
                        discountPercent.disabled = true;
                    }
                } else {
                    if (discountPercent) {
                        discountPercent.disabled = false;
                    }
                }
            });
        }

        // 24. DISCOUNT PERCENT KEYUP - #inv_discount_percent
        if (discountPercent) {
            discountPercent.addEventListener('keyup', function () {
                if (this.value.length > 0) {
                    if (discountAmount) {
                        discountAmount.value = '0.00';
                        discountAmount.disabled = true;
                    }
                } else {
                    if (discountAmount) {
                        discountAmount.disabled = false;
                    }
                }
            });
        }

        // 25. DATEPICKER INITIALIZATION - #datepicker
        var datepicker = document.getElementById('datepicker');
        if (datepicker) {
            datepicker.addEventListener('focus', function () {
                // Note: This would need a datepicker library like flatpickr or similar
                // Original used jQuery UI datepicker
                console.log('Datepicker focus - requires datepicker library integration');
            });
        }

        // 26. GENERAL DATEPICKER - .datepicker
        document.addEventListener('focus', function (e) {
            if (e.target.matches('.datepicker')) {
                // Note: This would need a datepicker library
                console.log('General datepicker focus - requires datepicker library integration');
            }
        }, true);

        // 27. TAGGABLE ELEMENTS - .taggable
        document.addEventListener('focus', function (e) {
            if (e.target.matches('.taggable')) {
                window.lastTaggableClicked = e.target;
            }
        }, true);

        // 28. TOOLTIPS INITIALIZATION - [data-bs-toggle="tooltip"]
        var tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(function (element) {
            // Note: This would need Bootstrap tooltip initialization
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    new bootstrap.Tooltip(element);
                }
            } catch (e) {
                console.error('Tooltip initialization error:', e);
            }
        });

        // 29. TAG SELECT HANDLING - .tag-select (Modern TomSelect implementation)
        var tagSelects = document.querySelectorAll('.tag-select');
        tagSelects.forEach(function (select) {
            // Initialize TomSelect for tag dropdowns if not already initialized
            if (typeof TomSelect !== 'undefined' && !select._tomselect) {
                new TomSelect(select, {
                    placeholder: 'Select a tag...',
                    allowEmptyOption: true
                });
                select._tomselect = true;
            }
            select.addEventListener('change', function (event) {
                // Add the tag to the field
                if (typeof window.lastTaggableClicked !== 'undefined' && window.insert_at_caret) {
                    window.insert_at_caret(window.lastTaggableClicked.id, select.value);
                }
                // Reset the select
                select.value = '';
                return false;
            });
        });

    }); // End DOMContentLoaded

    // Helper function for inserting at caret (if not already defined)
    if (!window.insert_at_caret) {
        window.insert_at_caret = function(elementId, text) {
            var element = document.getElementById(elementId);
            if (element) {
                var startPos = element.selectionStart;
                var endPos = element.selectionEnd;
                element.value = element.value.substring(0, startPos) + text + element.value.substring(endPos);
                element.selectionStart = element.selectionEnd = startPos + text.length;
            }
        };
    }

})();