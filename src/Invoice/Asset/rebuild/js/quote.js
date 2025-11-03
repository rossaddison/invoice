// quote.js - Complete functionality restored from pre_jquery_deletion branch
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

    // Helper to get origin
    function getOrigin() {
        return window.location.origin;
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

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. DELETE QUOTE ITEM FUNCTIONALITY - class="btn_delete_item" on views/product/partial_item_table.php
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_delete_item') || e.target.closest('.btn_delete_item')) {
                var btn = e.target.matches('.btn_delete_item') ? e.target : e.target.closest('.btn_delete_item');
                var id = btn.getAttribute('data-id');
                
                if (typeof id === 'undefined' || id === null) {
                    var itemRow = btn.closest('.item');
                    if (itemRow) itemRow.remove();
                } else {
                    var url = getOrigin() + "/invoice/quote/delete_item/" + id;
                    
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

        // 2. BULK DELETE QUOTE ITEMS - .delete-items-confirm-quote
        document.addEventListener('click', function (e) {
            if (e.target.matches('.delete-items-confirm-quote') || e.target.closest('.delete-items-confirm-quote')) {
                var btn = document.querySelector('.delete-items-confirm-quote');
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
                    
                    var item_ids = [];
                    var checkboxes = document.querySelectorAll("input[name='item_ids[]']:checked");
                    checkboxes.forEach(function (checkbox) {
                        item_ids.push(parseInt(checkbox.value));
                    });

                    var params = new URLSearchParams();
                    item_ids.forEach(function (id) {
                        params.append('item_ids[]', id);
                    });

                    fetch('/invoice/quoteitem/multiple?' + params.toString(), {
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

        // 3. ADD ROW MODAL - .btn_add_row_modal
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_add_row_modal') || e.target.closest('.btn_add_row_modal')) {
                var absoluteUrl = new URL(window.location.href);
                var quote_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var url = getOrigin() + "/invoice/quoteitem/add/" + quote_id;
                var modalPlaceholder = document.getElementById('modal-placeholder-quoteitem');
                if (modalPlaceholder) {
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

        // 4. ADD QUOTE ITEM ROW - .btn_quote_item_add_row
        document.addEventListener('click', function (e) {
            if (e.target.matches('.btn_quote_item_add_row') || e.target.closest('.btn_quote_item_add_row')) {
                var newRow = document.getElementById('new_quote_item_row');
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

        // 5. ADD ROW - .btn_add_row (general)
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

        // 6. QUOTE ADD CLIENT - .quote_add_client
        document.addEventListener('click', function (e) {
            if (e.target.matches('.quote_add_client') || e.target.closest('.quote_add_client')) {
                var url = getOrigin() + "/invoice/add-a-client";
                var modalPlaceholder = document.getElementById('modal-placeholder-client');
                if (modalPlaceholder) {
                    fetch(url)
                        .then(function (response) {
                            return response.text();
                        })
                        .then(function (html) {
                            modalPlaceholder.innerHTML = html;
                        })
                        .catch(function (error) {
                            console.error('Client modal load error:', error);
                        });
                }
            }
        });

        // 7. SAVE CLIENT NOTE - #save_client_note
        document.addEventListener('click', function (e) {
            if (e.target.matches('#save_client_note') || e.target.closest('#save_client_note')) {
                var url = getOrigin() + "/invoice/client/save_client_note";
                var load = getOrigin() + "/invoice/client/load_client_notes";
                var client_id = document.getElementById('client_id');
                var client_note = document.getElementById('client_note');

                if (client_id && client_note) {
                    var params = new URLSearchParams();
                    params.append('client_id', client_id.value);
                    params.append('client_note', client_note.value);

                    fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        cache: 'default'
                    })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful
                            var controlGroups = document.querySelectorAll('.control-group');
                            controlGroups.forEach(function (group) {
                                group.classList.remove('error');
                            });
                            client_note.value = '';

                            // Reload all notes
                            var notesList = document.getElementById('notes_list');
                            if (notesList) {
                                var loadParams = new URLSearchParams();
                                loadParams.append('client_id', client_id.value);
                                fetch(load + '?' + loadParams.toString())
                                    .then(function (response) {
                                        return response.text();
                                    })
                                    .then(function (html) {
                                        notesList.innerHTML = html;
                                    })
                                    .catch(function (error) {
                                        console.error('Notes reload error:', error);
                                    });
                            }
                        } else {
                            // The validation was not successful
                            var controlGroups = document.querySelectorAll('.control-group');
                            controlGroups.forEach(function (group) {
                                group.classList.remove('error');
                            });

                            if (response.validation_errors) {
                                for (var key in response.validation_errors) {
                                    if (response.validation_errors.hasOwnProperty(key)) {
                                        var element = document.getElementById(key);
                                        if (element && element.parentNode) {
                                            element.parentNode.classList.add('has-error');
                                        }
                                    }
                                }
                            }
                        }
                    })
                    .catch(function (error) {
                        console.error('Save client note error:', error);
                        alert('Status: Error - ' + error.toString());
                    });
                }
            }
        });

        // 8. QUOTE TAX SUBMIT - #quote_tax_submit
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_tax_submit') || e.target.closest('#quote_tax_submit')) {
                var url = getOrigin() + "/invoice/quote/save_quote_tax_rate";
                var btn = document.querySelector('.quote_tax_submit');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
                
                var quote_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var tax_rate_id = document.getElementById('tax_rate_id');
                var include_item_tax = document.getElementById('include_item_tax');

                var params = new URLSearchParams();
                params.append('quote_id', quote_id);
                if (tax_rate_id) params.append('tax_rate_id', tax_rate_id.value);
                if (include_item_tax) params.append('include_item_tax', include_item_tax.value);

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
                    window.location = absoluteUrl.href;
                    window.location.reload();
                    alert(response.flash_message);
                })
                .catch(function (error) {
                    console.error('Quote tax submit error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 9. QUOTE CREATE CONFIRM - #quote_create_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_create_confirm') || e.target.closest('#quote_create_confirm')) {
                var url = getOrigin() + "/invoice/quote/create_confirm";
                var btn = document.querySelector('.quote_create_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var create_quote_client_id = document.getElementById('create_quote_client_id');
                var quote_group_id = document.getElementById('quote_group_id');
                var quote_password = document.getElementById('quote_password');

                var params = new URLSearchParams();
                if (create_quote_client_id) params.append('client_id', create_quote_client_id.value);
                if (quote_group_id) params.append('quote_group_id', quote_group_id.value);
                if (quote_password) params.append('quote_password', quote_password.value);

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
                    var message = response.message;
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(message);
                    }
                })
                .catch(function (error) {
                    console.error('Quote create error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 10. QUOTE WITH PURCHASE ORDER NUMBER CONFIRM - #quote_with_purchase_order_number_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_with_purchase_order_number_confirm') || e.target.closest('#quote_with_purchase_order_number_confirm')) {
                var url = getOrigin() + "/invoice/quote/approve";
                var btn = document.querySelector('.quote_with_purchase_order_number_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var url_key = document.getElementById('url_key');
                var quote_with_purchase_order_number = document.getElementById('quote_with_purchase_order_number');
                var quote_with_purchase_order_person = document.getElementById('quote_with_purchase_order_person');

                var params = new URLSearchParams();
                if (url_key) params.append('url_key', url_key.value);
                if (quote_with_purchase_order_number) params.append('client_po_number', quote_with_purchase_order_number.value);
                if (quote_with_purchase_order_person) params.append('client_po_person', quote_with_purchase_order_person.value);

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
                })
                .catch(function (error) {
                    console.error('Purchase order confirm error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 11. QUOTE TO INVOICE CONFIRM - #quote_to_invoice_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_to_invoice_confirm') || e.target.closest('#quote_to_invoice_confirm')) {
                var url = getOrigin() + "/invoice/quote/quote_to_invoice_confirm";
                var btn = document.querySelector('.quote_to_invoice_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var quote_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var client_id = document.getElementById('client_id');
                var group_id = document.getElementById('group_id');
                var password = document.getElementById('password');

                var params = new URLSearchParams();
                params.append('quote_id', quote_id);
                if (client_id) params.append('client_id', client_id.value);
                if (group_id) params.append('group_id', group_id.value);
                if (password) params.append('password', password.value);

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
                        alert(response.flash_message);
                    }
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                })
                .catch(function (error) {
                    console.error('Quote to invoice error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 12. QUOTE TO SALES ORDER CONFIRM - #quote_to_so_confirm
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_to_so_confirm') || e.target.closest('#quote_to_so_confirm')) {
                var url = getOrigin() + "/invoice/quote/quote_to_so_confirm";
                var btn = document.querySelector('.quote_to_so_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var quote_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var client_id = document.getElementById('client_id');
                var so_group_id = document.getElementById('so_group_id');
                var po_number = document.getElementById('po_number');
                var po_person = document.getElementById('po_person');
                var password = document.getElementById('password');

                var params = new URLSearchParams();
                params.append('quote_id', quote_id);
                if (client_id) params.append('client_id', client_id.value);
                if (so_group_id) params.append('group_id', so_group_id.value);
                if (po_number) params.append('po', po_number.value);
                if (po_person) params.append('person', po_person.value);
                if (password) params.append('password', password.value);

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
                        alert(response.flash_message);
                    }
                    if (response.success === 0) {
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        window.location = absoluteUrl.href;
                        window.location.reload();
                        alert(response.flash_message);
                    }
                })
                .catch(function (error) {
                    console.error('Quote to SO error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 13. QUOTE TO QUOTE CONFIRM - #quote_to_quote_confirm (Copy quote)
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_to_quote_confirm') || e.target.closest('#quote_to_quote_confirm')) {
                var url = getOrigin() + "/invoice/quote/quote_to_quote_confirm";
                var btn = document.querySelector('.quote_to_quote_confirm');
                var absoluteUrl = new URL(window.location.href);
                if (btn) btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';

                var quote_id = absoluteUrl.href.substring(absoluteUrl.href.lastIndexOf('/') + 1);
                var create_quote_client_id = document.getElementById('create_quote_client_id');
                var user_id = document.getElementById('user_id');

                var params = new URLSearchParams();
                params.append('quote_id', quote_id);
                if (create_quote_client_id) params.append('client_id', create_quote_client_id.value);
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
                        alert(response.flash_message);
                    }
                })
                .catch(function (error) {
                    console.error('Quote to quote error:', error);
                    alert('Status: Error - ' + error.toString());
                });
            }
        });

        // 14. QUOTE PDF WITH CUSTOM FIELDS - #quote_to_pdf_confirm_with_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_to_pdf_confirm_with_custom_fields') || e.target.closest('#quote_to_pdf_confirm_with_custom_fields')) {
                var url = getOrigin() + "/invoice/quote/pdf/1";
                window.open(url, '_blank');
            }
        });

        // 15. QUOTE PDF WITHOUT CUSTOM FIELDS - #quote_to_pdf_confirm_without_custom_fields
        document.addEventListener('click', function (e) {
            if (e.target.matches('#quote_to_pdf_confirm_without_custom_fields') || e.target.closest('#quote_to_pdf_confirm_without_custom_fields')) {
                var url = getOrigin() + "/invoice/quote/pdf/0";
                window.open(url, '_blank');
            }
        });

        // 16. QUOTE DISCOUNT AMOUNT KEYUP - #quote_discount_amount
        var quoteDiscountAmount = document.getElementById('quote_discount_amount');
        var quoteDiscountPercent = document.getElementById('quote_discount_percent');
        
        if (quoteDiscountAmount) {
            quoteDiscountAmount.addEventListener('keyup', function () {
                if (this.value.length > 0) {
                    if (quoteDiscountPercent) {
                        quoteDiscountPercent.value = '0.00';
                        quoteDiscountPercent.disabled = true;
                    }
                } else {
                    if (quoteDiscountPercent) {
                        quoteDiscountPercent.disabled = false;
                    }
                }
            });
        }

        // 17. QUOTE DISCOUNT PERCENT KEYUP - #quote_discount_percent
        if (quoteDiscountPercent) {
            quoteDiscountPercent.addEventListener('keyup', function () {
                if (this.value.length > 0) {
                    if (quoteDiscountAmount) {
                        quoteDiscountAmount.value = '0.00';
                        quoteDiscountAmount.disabled = true;
                    }
                } else {
                    if (quoteDiscountAmount) {
                        quoteDiscountAmount.disabled = false;
                    }
                }
            });
        }

        // 18. DATEPICKER INITIALIZATION - #datepicker
        var datepicker = document.getElementById('datepicker');
        if (datepicker) {
            datepicker.addEventListener('focus', function () {
                // Note: This would need a datepicker library like flatpickr or similar
                console.log('Datepicker focus - requires datepicker library integration');
            });
        }

        // 19. GENERAL DATEPICKER - .datepicker
        document.addEventListener('focus', function (e) {
            if (e.target.matches('.datepicker')) {
                // Note: This would need a datepicker library
                console.log('General datepicker focus - requires datepicker library integration');
            }
        }, true);

        // 20. TAGGABLE ELEMENTS - .taggable
        document.addEventListener('focus', function (e) {
            if (e.target.matches('.taggable')) {
                window.lastTaggableClicked = e.target;
            }
        }, true);

        // 21. TOOLTIPS INITIALIZATION - [data-bs-toggle="tooltip"]
        var tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(function (element) {
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    new bootstrap.Tooltip(element);
                }
            } catch (e) {
                console.error('Tooltip initialization error:', e);
            }
        });

        // 22. TAG SELECT HANDLING - .tag-select (Modern TomSelect implementation)
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