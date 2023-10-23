$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };

    if ($('#discount_percent').val()) {
        $('#discount_amount').prop('disabled', true);
    }
    if ($('#discount_amount').val()) {
        $('#discount_percent').prop('disabled', true);
    }
    
     // class="btn_delete_item" on views/product/partial_item_table.php
    $('.btn_delete_item').click(function () {
            var id = $(this).attr('data-id');  
            if (typeof id === 'undefined') {
                $(this).parents('.item').remove();
            } else {
                var url = $(location).attr('origin') + "/invoice/quote/delete_item/"+id;
                $.ajax({ type: 'GET',
                         contentType: "application/json; charset=utf-8",
                         data: {
                            id: id
                         },
                         url: url,
                         cache: false,
                         dataType: 'json',
                         success: function (data) {
                                    var response = parsedata(data);
                                    if (response.success === 1) {
                                        location.reload(true);
                                        $(this).parents('.item').remove();
                                        alert("Deleted");
                                    }
                        }
                });
            }        
    });
    
    $(document).on('click', '.delete-items-confirm-quote', function () {
        var btn = $('.delete-items-confirm-quote');
        btn.html('<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>');
        var item_ids = [];
        $("input[name='item_ids[]']:checked").each(function () {
            item_ids.push(parseInt($(this).val()));
        });
        $.ajax({ type: 'GET',
                 contentType: "application/json; charset=utf-8",
                 data: {
                        item_ids: item_ids
                       },
                 url: '/invoice/quoteitem/multiple',
                 cache: false,
                 dataType: 'json',
                 success: function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');
                        location.reload(true);
                    }
                 }
        });
    });
     
    $('.btn_add_row_modal').click(function () {
    var absolute_url = new URL($(location).attr('href'));
    quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1); 
    var url = $(location).attr('origin') + "/invoice/quoteitem/add/"+quote_id;
    $('#modal-placeholder-quoteitem').on("load",url);  
    });

    $('.btn_quote_item_add_row').click(function () {
    $('#new_quote_item_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();            
    });
    
    // class="btn_add_row" on views/quote/partial_item_table.php
    $('.btn_add_row').click(function () {
    $('#new_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();            
    });

    $('.quote_add_client').click(function () {            
    var url = $(location).attr('origin') + "/invoice/add-a-client";
    $('#modal-placeholder-client').on("load",url);
    });

    $('#save_client_note').click(function () {
    var url = $(location).attr('origin') + "/invoice/client/save_client_note";
    var load = $(location).attr('origin') + "/invoice/client/load_client_notes";
    var client_id = $('#client_id').val();
    $.ajax({ type: 'GET',
         contentType: "application/json; charset=utf-8",
         data: {
            client_id: client_id,
            client_note: $('#client_note').val()
         },
         url: url,
         cache: true,
         dataType: 'json',
         success: function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        // The validation was successful
                        $('.control-group').removeClass('error');
                        $('#client_note').val('');
                        // Reload all notes
                        $('#notes_list').on("load",load,
                            {
                                client_id: client_id
                            }, function (response) {
                                    console.log(response);
                            });
                    } else {
                        // The validation was not successful
                        $('.control-group').removeClass('error');
                        for (var key in response.validation_errors) {
                            $('#' + key).parent().addClass('has-error');
                        }
                    }
         },
         error: function(xhr, status, error) {                         
                console.warn(xhr.responseText);
                alert('Status: ' + status + ' An error: ' + error.toString());
         }
    });            
    });

    // id="quote_tax_submit" in drop down menu on views/quote/view.php
    $(document).on('click', '#quote_tax_submit', function () {
    var url = $(location).attr('origin') + "/invoice/quote/save_quote_tax_rate";
    var btn = $('.quote_tax_submit');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    //take the quote id from the public url
    quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                   quote_id: quote_id,
                   tax_rate_id: $('#tax_rate_id').val(),
                   include_item_tax: $('#include_item_tax').val()
            },
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                       var response = parsedata(data);
                       if (response.success === 1) {                                   
                          window.location = absolute_url;
                          window.location.reload();                                                
                       } 
            },
            error: function() {
                alert('Incomplete fields: You must include a tax rate. Tip: Include a zero tax rate.');
            }
    });
    });

    // id="quote_create_confirm button on views/quote/modal_create_quote.php
    $(document).on('click', '#quote_create_confirm', function () {
    var url = $(location).attr('origin') + "/invoice/quote/create_confirm";
    var btn = $('.quote_create_confirm');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                        client_id: $('#create_quote_client_id').val(),
                        quote_group_id: $('#quote_group_id').val(),
                        quote_password: $('#quote_password').val()
            },                
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and quote was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                        }
                        message = response.message;
                        if (response.success === 0) {
                            // The validation was unsuccessful and inv was not created
                            btn.html('<h6 class="text-center"><i class="fa fa-check"></i></h6>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert(message);
                       }  
            },
            error: function(xhr, status, error) {                         
                console.warn(xhr.responseText);
                alert('Status: ' + status + ' An error: ' + error.toString());
            }
            
        });
    });
    
    // id="quote_with_purchase_order_number_confirm button on views/quote/modal_purchase_order_number.php associated with submit button
    $(document).on('click', '#quote_with_purchase_order_number_confirm', function () {
    var url = $(location).attr('origin') + "/invoice/quote/approve";
    var btn = $('.quote_with_purchase_order_number_confirm');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                url_key: $('#url_key').val(),
                client_po_number: $('#quote_with_purchase_order_number').val(),
                client_po_person: $('#quote_with_purchase_order_person').val()
            },                
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and quote with purchase order number was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                        }
            },
            error: function(xhr, status, error) {                         
                console.warn(xhr.responseText);
                alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });

    // Creates the invoice
    $(document).on('click', '#quote_to_invoice_confirm', function () {        
        var url = $(location).attr('origin') + "/invoice/quote/quote_to_invoice_confirm";
        var btn = $('.quote_to_invoice_confirm');
        var absolute_url = new URL($(location).attr('href'));
        btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
        //take the quote id from the public url
        quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);        
        $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                quote_id: quote_id,
                client_id: $('#client_id').val(),
                group_id: $('#group_id').val(),
                password: $('#password').val()
            },                            
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and invoice was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert('Invoice created from Quote!');
                        }
                        if (response.success === 0) {
                            // The validation was not successful created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert('Invoice NOT created from Quote! Duplicate Invoice. Copy your Quote to another quote and then copy to invoice. Each quote must have a matching invoice.');
                        }    
                        
            },
            error: function(xhr, status, error) {                         
                        console.warn(xhr.responseText);
                        alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });
    
    // Creates the purchase order
    $(document).on('click', '#quote_to_so_confirm', function () {        
        var url = $(location).attr('origin') + "/invoice/quote/quote_to_so_confirm";
        var btn = $('.quote_to_so_confirm');
        var absolute_url = new URL($(location).attr('href'));
        btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
        //take the quote id from the public url
        quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);        
        $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                quote_id: quote_id,
                client_id: $('#client_id').val(),
                group_id: $('#so_group_id').val(),
                po: $('#po_number').val(),
                person: $('#po_person').val(),
                password: $('#password').val()
            },                            
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and invoice was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert('Sales Order created from Quote and you entered your Purchase Order Number!');
                        }
                        if (response.success === 0) {
                            // The validation was not successfully created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert('Sales Order not created from Quote! Duplicate Sales Order. Copy your Quote to another quote and then copy to sales order. Each quote must have a matching sales order.');
                        }    
                        
            },
            error: function(xhr, status, error) {                         
                        console.warn(xhr.responseText);
                        alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });
   
    // Copies the quote to a specific client
    $(document).on('click', '#quote_to_quote_confirm', function () {        
        var url = $(location).attr('origin') + "/invoice/quote/quote_to_quote_confirm";
        var btn = $('.quote_to_quote_confirm');
        var absolute_url = new URL($(location).attr('href'));
        btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
        //take the quote id from the public url
        quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);        
        $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                quote_id: quote_id,
                client_id: $('#create_quote_client_id').val(),
                user_id: $('#user_id').val()
            },                            
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and quote was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                        }
            },
            error: function(xhr, status, error) {                         
                        console.warn(xhr.responseText);
                        alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });
    
    // id="quote_to_pdf_confirm_with_custom_fields button on views/quote/modal_quote_to_pdf.php
    $(document).on('click', '#quote_to_pdf_confirm_with_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/quote/pdf/1";    
            window.open(url, '_blank');
    }); 

    // id="quote_to_pdf_confirm_without_custom_fields button on views/quote/modal_quote_to_pdf.php
    $(document).on('click', '#quote_to_pdf_confirm_without_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/quote/pdf/0";    
            window.open(url, '_blank');
    });

    $('#discount_amount').keyup(function () {
    if (this.value.length > 0) {
        $('#discount_percent').prop('disabled', true);
    } else {
        $('#discount_percent').prop('disabled', false);
    }
    });
    $('#discount_percent').keyup(function () {
    if (this.value.length > 0) {
        $('#discount_amount').prop('disabled', true);
    } else {
        $('#discount_amount').prop('disabled', false);
    }
    });

    var fixHelper = function (e, tr) {
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function (index) {
        $(this).width($originals.eq(index).width());
    });
    return $helper;
    };
    $('#item_table').sortable({
    helper: fixHelper,
    items: 'tbody'
    });  

    $('#datepicker').on('focus', function () {
            $(this).datepicker({               
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                dateFormat: 'dd-mm-yy'
            });
    });

    $('body').on('focus', '.datepicker', function () {
            $(this).datepicker({
                beforeShow: function() {
                    setTimeout(function(){
                    $('.datepicker').css('z-index','9999');
                    }, );
                }      
            });
    });

    // Keep track of the last "taggable" input/textarea
    $('.taggable').on('focus', function () {
    window.lastTaggableClicked = this;
    });
    
    $('[data-toggle="tooltip"]').tooltip();

    // Template Tag handling
    $('.tag-select').select2().on('change', function (event) {
    var select = $(event.currentTarget);
    // Add the tag to the field
    if (typeof window.lastTaggableClicked !== 'undefined') {
        insert_at_caret(window.lastTaggableClicked.id, select.val());
    }
    // Reset the select and exit
    select.val([]);
    return false;
    });
});

    
        
    


