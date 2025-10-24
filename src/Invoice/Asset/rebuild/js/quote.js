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

    // Helper: perform GET request with query params and return parsed JSON/text
    function getJson(url, params) {
        var u = url;
        if (params) {
            var sp = new URLSearchParams();
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (Array.isArray(v)) {
                    v.forEach(function (x) { sp.append(k, x); });
                } else {
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

    // Delegated click handler
    document.addEventListener('click', function (e) {
        var el = e.target;

        // Delete single item (static delegation works for elements added later too)
        var deleteBtn = el.closest('.btn_delete_item');
        if (deleteBtn) {
            (function (btn) {
                var id = btn.getAttribute('data-id');
                if (typeof id === 'undefined' || id === null) {
                    var parentItem = btn.closest('.item');
                    if (parentItem) parentItem.remove();
                    return;
                }
                var url = location.origin + "/invoice/quote/delete_item/" + encodeURIComponent(id);
                getJson(url, { id: id })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            // reload to reflect server state (mirrors original)
                            location.reload(true);
                            var parentItem = btn.closest('.item');
                            if (parentItem) parentItem.remove();
                            alert("Deleted");
                        } else {
                            console.warn('delete_item failed', response);
                        }
                    })
                    .catch(function (err) {
                        console.error('delete_item error', err);
                        alert('An error occurred while deleting item. See console for details.');
                    });
            })(deleteBtn);
            return;
        }

        // Delete multiple items (quote)
        var delMulti = el.closest('.delete-items-confirm-quote');
        if (delMulti) {
            (function (btn) {
                var originalHtml = btn.innerHTML;
                btn.innerHTML = '<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>';
                btn.disabled = true;
                var item_ids = Array.from(document.querySelectorAll("input[name='item_ids[]']:checked")).map(function (i) {
                    return parseInt(i.value, 10);
                }).filter(Boolean);

                getJson('/invoice/quoteitem/multiple', { item_ids: item_ids })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                            location.reload(true);
                        } else {
                            console.warn('quoteitem/multiple failed', response);
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                        }
                    })
                    .catch(function (err) {
                        console.error('quoteitem/multiple error', err);
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                        alert('An error occurred while deleting items. See console for details.');
                    });
            })(delMulti);
            return;
        }

        // Add row via modal (load modal content into placeholder)
        var addRowModalBtn = el.closest('.btn_add_row_modal');
        if (addRowModalBtn) {
            var absolute_url = new URL(location.href);
            var quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            var url = location.origin + "/invoice/quoteitem/add/" + encodeURIComponent(quote_id);
            var placeholder = document.getElementById('modal-placeholder-quoteitem');
            if (placeholder) {
                // load HTML into placeholder
                placeholder.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                fetch(url, { cache: 'no-store', credentials: 'same-origin' })
                    .then(function (r) { return r.text(); })
                    .then(function (html) { placeholder.innerHTML = html; })
                    .catch(function (err) { console.error('Failed to load quoteitem modal', err); });
            }
            return;
        }

        // Add new quote item row from template
        var btnQuoteItemAddRow = el.closest('.btn_quote_item_add_row');
        if (btnQuoteItemAddRow) {
            var template = document.getElementById('new_quote_item_row');
            var table = document.getElementById('item_table');
            if (template && table) {
                var clone = template.cloneNode(true);
                clone.removeAttribute('id');
                clone.classList.add('item');
                clone.style.display = '';
                table.appendChild(clone);
            }
            return;
        }

        // Add generic new row
        var addRowBtn = el.closest('.btn_add_row');
        if (addRowBtn) {
            var template2 = document.getElementById('new_row');
            var table2 = document.getElementById('item_table');
            if (template2 && table2) {
                var clone2 = template2.cloneNode(true);
                clone2.removeAttribute('id');
                clone2.classList.add('item');
                clone2.style.display = '';
                table2.appendChild(clone2);
            }
            return;
        }

        // Add client modal
        var addClientBtn = el.closest('.quote_add_client');
        if (addClientBtn) {
            var urlClient = location.origin + "/invoice/add-a-client";
            var placeholderClient = document.getElementById('modal-placeholder-client');
            if (placeholderClient) {
                placeholderClient.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                fetch(urlClient, { cache: 'no-store', credentials: 'same-origin' })
                    .then(function (r) { return r.text(); })
                    .then(function (html) { placeholderClient.innerHTML = html; })
                    .catch(function (err) { console.error('Failed to load add-a-client modal', err); });
            }
            return;
        }

        // quote_create_confirm
        var createConfirm = el.closest('#quote_create_confirm, .quote_create_confirm');
        if (createConfirm) {
            var urlCreate = location.origin + "/invoice/quote/create_confirm";
            var btn = document.querySelector('.quote_create_confirm') || createConfirm;
            var absolute_url_c = new URL(location.href);
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btn.disabled = true;
            }
            var payloadCreate = {
                client_id: (document.getElementById('create_quote_client_id') || {}).value || '',
                quote_group_id: (document.getElementById('quote_group_id') || {}).value || '',
                quote_password: (document.getElementById('quote_password') || {}).value || ''
            };
            getJson(urlCreate, payloadCreate)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_c;
                        window.location.reload();
                    }
                    var message = response.message;
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                        window.location = absolute_url_c;
                        window.location.reload();
                        if (message) alert(message);
                    }
                })
                .catch(function (err) {
                    console.error('create_confirm error', err);
                    if (btn) {
                        btn.innerHTML = orig || '';
                        btn.disabled = false;
                    }
                    alert('An error occurred while creating quote. See console for details.');
                });
            return;
        }

        // quote_with_purchase_order_number_confirm
        var poConfirm = el.closest('#quote_with_purchase_order_number_confirm, .quote_with_purchase_order_number_confirm');
        if (poConfirm) {
            var urlPo = location.origin + "/invoice/quote/approve";
            var btnPo = document.querySelector('.quote_with_purchase_order_number_confirm') || poConfirm;
            var absolute_url_po = new URL(location.href);
            if (btnPo) {
                var origPo = btnPo.innerHTML;
                btnPo.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btnPo.disabled = true;
            }
            var payloadPo = {
                url_key: (document.getElementById('url_key') || {}).value || '',
                client_po_number: (document.getElementById('quote_with_purchase_order_number') || {}).value || '',
                client_po_person: (document.getElementById('quote_with_purchase_order_person') || {}).value || ''
            };
            getJson(urlPo, payloadPo)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btnPo) btnPo.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_po;
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    console.error('approve error', err);
                    if (btnPo) {
                        btnPo.innerHTML = origPo || '';
                        btnPo.disabled = false;
                    }
                    alert('An error occurred while approving quote. See console for details.');
                });
            return;
        }

        // quote_to_invoice_confirm
        var toInvoice = el.closest('#quote_to_invoice_confirm, .quote_to_invoice_confirm');
        if (toInvoice) {
            var urlQI = location.origin + "/invoice/quote/quote_to_invoice_confirm";
            var btnQI = document.querySelector('.quote_to_invoice_confirm') || toInvoice;
            var absolute_url_qi = new URL(location.href);
            if (btnQI) {
                var origQi = btnQI.innerHTML;
                btnQI.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btnQI.disabled = true;
            }
            var quote_id = absolute_url_qi.href.substring(absolute_url_qi.href.lastIndexOf('/') + 1);
            var payloadQi = {
                quote_id: quote_id,
                client_id: (document.getElementById('client_id') || {}).value || '',
                group_id: (document.getElementById('group_id') || {}).value || '',
                password: (document.getElementById('password') || {}).value || ''
            };
            getJson(urlQI, payloadQi)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btnQI) btnQI.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_qi;
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    } else {
                        if (btnQI) btnQI.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_qi;
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    }
                })
                .catch(function (err) {
                    console.error('quote_to_invoice_confirm error', err);
                    if (btnQI) {
                        btnQI.innerHTML = origQi || '';
                        btnQI.disabled = false;
                    }
                    alert('An error occurred while converting quote to invoice. See console for details.');
                });
            return;
        }

        // quote_to_so_confirm
        var toSo = el.closest('#quote_to_so_confirm, .quote_to_so_confirm');
        if (toSo) {
            var urlQS = location.origin + "/invoice/quote/quote_to_so_confirm";
            var btnQS = document.querySelector('.quote_to_so_confirm') || toSo;
            var absolute_url_qs = new URL(location.href);
            if (btnQS) {
                var origQs = btnQS.innerHTML;
                btnQS.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btnQS.disabled = true;
            }
            var quote_id_so = absolute_url_qs.href.substring(absolute_url_qs.href.lastIndexOf('/') + 1);
            var payloadQs = {
                quote_id: quote_id_so,
                client_id: (document.getElementById('client_id') || {}).value || '',
                group_id: (document.getElementById('so_group_id') || {}).value || '',
                po: (document.getElementById('po_number') || {}).value || '',
                person: (document.getElementById('po_person') || {}).value || '',
                password: (document.getElementById('password') || {}).value || ''
            };
            getJson(urlQS, payloadQs)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btnQS) btnQS.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_qs;
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    } else {
                        if (btnQS) btnQS.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_qs;
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    }
                })
                .catch(function (err) {
                    console.error('quote_to_so_confirm error', err);
                    if (btnQS) {
                        btnQS.innerHTML = origQs || '';
                        btnQS.disabled = false;
                    }
                    alert('An error occurred while converting quote to SO. See console for details.');
                });
            return;
        }

        // quote_to_quote_confirm
        var toQuote = el.closest('#quote_to_quote_confirm, .quote_to_quote_confirm');
        if (toQuote) {
            var urlQQ = location.origin + "/invoice/quote/quote_to_quote_confirm";
            var btnQQ = document.querySelector('.quote_to_quote_confirm') || toQuote;
            var absolute_url_qq = new URL(location.href);
            if (btnQQ) {
                var origQq = btnQQ.innerHTML;
                btnQQ.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                btnQQ.disabled = true;
            }
            var quote_id_qq = absolute_url_qq.href.substring(absolute_url_qq.href.lastIndexOf('/') + 1);
            var payloadQq = {
                quote_id: quote_id_qq,
                client_id: (document.getElementById('create_quote_client_id') || {}).value || '',
                user_id: (document.getElementById('user_id') || {}).value || ''
            };
            getJson(urlQQ, payloadQq)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        if (btnQQ) btnQQ.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absolute_url_qq;
                        window.location.reload();
                        if (response.flash_message) alert(response.flash_message);
                    }
                })
                .catch(function (err) {
                    console.error('quote_to_quote_confirm error', err);
                    if (btnQQ) {
                        btnQQ.innerHTML = origQq || '';
                        btnQQ.disabled = false;
                    }
                    alert('An error occurred while copying quote. See console for details.');
                });
            return;
        }

        // quote_to_pdf_confirm_with_custom_fields
        if (el.closest('#quote_to_pdf_confirm_with_custom_fields')) {
            var urlPdf1 = location.origin + "/invoice/quote/pdf/1";
            window.open(urlPdf1, '_blank');
            return;
        }

        // quote_to_pdf_confirm_without_custom_fields
        if (el.closest('#quote_to_pdf_confirm_without_custom_fields')) {
            var urlPdf0 = location.origin + "/invoice/quote/pdf/0";
            window.open(urlPdf0, '_blank');
            return;
        }
    }, true);

    // Save client note
    document.addEventListener('click', function (e) {
        var el = e.target;
        var saveBtn = el.closest('#save_client_note');
        if (!saveBtn) return;
        var url = location.origin + "/invoice/client/save_client_note";
        var loadUrl = location.origin + "/invoice/client/load_client_notes";
        var client_id = (document.getElementById('client_id') || {}).value || '';
        var client_note = (document.getElementById('client_note') || {}).value || '';
        getJson(url, { client_id: client_id, client_note: client_note })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    // remove error classes
                    document.querySelectorAll('.control-group').forEach(function (g) { g.classList.remove('error'); });
                    var noteEl = document.getElementById('client_note');
                    if (noteEl) noteEl.value = '';
                    // reload notes list
                    var notesList = document.getElementById('notes_list');
                    if (notesList) {
                        var u = loadUrl + '?client_id=' + encodeURIComponent(client_id);
                        fetch(u, { cache: 'no-store', credentials: 'same-origin' })
                            .then(function (r) { return r.text(); })
                            .then(function (html) { notesList.innerHTML = html; })
                            .catch(function (err) { console.error('load_client_notes failed', err); });
                    }
                } else {
                    // show validation errors
                    document.querySelectorAll('.control-group').forEach(function (g) { g.classList.remove('error'); });
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

    // Quote tax submit
    document.addEventListener('click', function (e) {
        var el = e.target;
        var submit = el.closest('#quote_tax_submit');
        if (!submit) return;
        var url = location.origin + "/invoice/quote/save_quote_tax_rate";
        var btn = document.querySelector('.quote_tax_submit') || submit;
        var absolute_url = new URL(location.href);
        if (btn) {
            var orig = btn.innerHTML;
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            btn.disabled = true;
        }
        var quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
        var payload = {
            quote_id: quote_id,
            tax_rate_id: (document.getElementById('tax_rate_id') || {}).value || '',
            include_item_tax: (document.getElementById('include_item_tax') || {}).value || ''
        };
        getJson(url, payload)
            .then(function (data) {
                var response = parsedata(data);
                window.location = absolute_url;
                window.location.reload();
                if (response.flash_message) alert(response.flash_message);
            })
            .catch(function (err) {
                console.error('save_quote_tax_rate error', err);
                alert('An error occurred while saving quote tax rate. See console for details.');
            });
    }, true);

    // Discount inputs interlock
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (el && el.id === 'quote_discount_amount') {
            var percent = document.getElementById('quote_discount_percent');
            if (el.value.length > 0) {
                if (percent) { percent.value = '0.00'; percent.disabled = true; }
            } else { if (percent) percent.disabled = false; }
        }
        if (el && el.id === 'quote_discount_percent') {
            var amount = document.getElementById('quote_discount_amount');
            if (el.value.length > 0) {
                if (amount) { amount.value = '0.00'; amount.disabled = true; }
            } else { if (amount) amount.disabled = false; }
        }
    }, true);

    // Datepicker initialization: only if jQuery UI is present, call it via jQuery to preserve behaviour.
    document.addEventListener('focus', function (e) {
        var el = e.target;
        if (el && el.id === 'datepicker') {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.datepicker === 'function') {
                window.jQuery(el).datepicker({
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    dateFormat: 'dd-mm-yy'
                });
            }
        }
        if (el && el.classList && el.classList.contains('datepicker')) {
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

    // Keep track of last taggable
    document.addEventListener('focus', function (e) {
        var el = e.target;
        if (el && el.classList && el.classList.contains('taggable')) {
            window.lastTaggableClicked = el;
        }
    }, true);

    // Tooltips (bootstrap)
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (t) {
                try { new bootstrap.Tooltip(t); } catch (err) { /* ignore */ }
            });
        }

        // Initialize select UI for tag-select: if TomSelect is available we assume it has been initialized elsewhere.
        // Bind change handler for .tag-select (delegated)
        Array.from(document.querySelectorAll('.tag-select')).forEach(function (sel) {
            sel.addEventListener('change', function (event) {
                var select = event.currentTarget;
                if (typeof window.lastTaggableClicked !== 'undefined' && window.lastTaggableClicked) {
                    insert_at_caret(window.lastTaggableClicked.id, select.value);
                }
                // Reset the select value (for native or TomSelect)
                if (select._tomselect && typeof select._tomselect.clear === 'function') {
                    select._tomselect.clear();
                } else if (select.tomselect && typeof select.tomselect.clear === 'function') {
                    select.tomselect.clear();
                } else {
                    // native reset
                    if (select.multiple) {
                        Array.from(select.options).forEach(function (o) { o.selected = false; });
                    } else {
                        select.value = '';
                    }
                }
                event.preventDefault();
                return false;
            }, false);
        });
    });

})();