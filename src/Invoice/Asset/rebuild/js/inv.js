$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    // Used in userclient/new
    $('#user_all_clients').click(function () {
        all_client_check();
    });
    
    function all_client_check() {
        if ($('#user_all_clients').is(':checked')) {
            $('#list_client').hide();
        } else {
            $('#list_client').show();
        }
    }
        
    all_client_check();
            
    // class="btn_delete_item" on views/product/partial_item_table.php
    $('.btn_delete_item').click(function () {
            var id = $(this).attr('data-id');  
            if (typeof id === 'undefined') {
                $(this).parents('.item').remove();
            } else {
                var url = $(location).attr('origin') + "/invoice/inv/delete_item/"+id;
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
    
    $(document).on('click', '.delete-items-confirm-inv', function () {
        var btn = $('.delete-items-confirm-inv');
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
                 url: '/invoice/invitem/multiple',
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
    
    $(document).ready(function() {
       $("[required]").after("<span class='required'>*</span>");
    });
     
    $('.btn_add_row_modal').click(function () {
    var absolute_url = new URL($(location).attr('href'));
    inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1); 
    var url = $(location).attr('origin') + "/invoice/invitem/add/"+inv_id;
    $('#modal-placeholder-invitem').on("load",url);  
    });

    $('.btn_inv_item_add_row').click(function () {
    $('#new_inv_item_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();            
    });
    
    // class="btn_add_row" on views/inv/partial_item_table.php
    $('.btn_add_row').click(function () {
    $('#new_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();            
    });
    
    // id="inv_tax_submit" in drop down menu on views/inv/view.php
    $(document).on('click', '#inv_tax_submit', function () {
    var url = $(location).attr('origin') + "/invoice/inv/save_inv_tax_rate";
    var btn = $('.inv_tax_submit');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    //take the inv id from the public url
    inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                   inv_id: inv_id,
                   inv_tax_rate_id: $('#inv_tax_rate_id').val(),
                   include_inv_item_tax: $('#include_inv_item_tax').val()
            },
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                       var response = parsedata(data);
                       if (response.success === 1) {                                   
                          window.location = absolute_url;
                          btn.html('<h6 class="text-center"><i class="fa fa-check"></i></h6>');
                          window.location.reload();                                                
                       }
                       if (response.success === 0) {                                   
                          window.location = absolute_url;
                          btn.html('<h6 class="text-center"><i class="fa fa-check"></i></h6>');
                          window.location.reload();                                                
                       }
            },
            error: function() {
                alert('Incomplete fields: You must include a tax rate. Tip: Include a zero tax rate.');
            }
    });
    });
    
    // id="create_credit_confirm button on views/inv/modal_create_credit.php
    $(document).on('click', '#create-credit-confirm', function () {
    var url = $(location).attr('origin') + "/invoice/inv/create_credit_confirm";
    var btn = $('.create-credit-confirm');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    //take the inv id from the public url
    inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                    inv_id: inv_id,
                    client_id: $('#client_id').val(),
                    inv_date_created: $('#inv_date_created').val(),
                    group_id: $('#inv_group_id').val(),
                    password: $('#inv_password').val(),
                    user_id: $('#user_id').val()
            },                
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                var response =  parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and inv was created
                    btn.html('<h2 class="text-center"><i class="bi bi-check2-square">                                                                                                                                                                                                                                                                                                                                                                                                                           "></i></h2>');                        
                    window.location = absolute_url;
                    window.location.reload();
                    alert(response.flash_message);
                }
                if (response.success === 0) {
                    btn.html('<h2 class="text-center"><i class="fa fa-times"></i></h2>');                        
                    window.location = absolute_url;
                    window.location.reload();
                    // Display the 'unsuccessful' message
                    alert(response.flash_message);
                }    
            },
            error: function(xhr, status, error) {                         
                console.warn(xhr.responseText);
                alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });  

    // Copies the invoice to a specific client
    $(document).on('click', '#inv_to_inv_confirm', function () {        
        var url = $(location).attr('origin') + "/invoice/inv/inv_to_inv_confirm";
        var btn = $('.inv_to_inv_confirm');
        var absolute_url = new URL($(location).attr('href'));
        btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
        //take the inv id from the public url
        inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);        
        $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                inv_id: inv_id,
                client_id: $('#create_inv_client_id').val(),
                user_id: $('#user_id').val()
            },                            
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and inv was created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                        }
                        if (response.success === 0) {
                            // The validation was unsuccessful
                            btn.html('<h2 class="text-center"><i class="fa fa-times"></i></h2>');                        
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
    
    // id="inv_to_pdf_confirm_with_custom_fields button on views/inv/modal_inv_to_pdf.php
    $(document).on('click', '#inv_to_pdf_confirm_with_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/inv/pdf/1";    
            window.location.reload;
            window.open(url, '_blank');            
    }); 

    // id="inv_to_pdf_confirm_without_custom_fields button on views/inv/modal_inv_to_pdf.php
    $(document).on('click', '#inv_to_pdf_confirm_without_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/inv/pdf/0";    
            window.location.reload;
            window.open(url, '_blank');
    });
    
    // id="inv_to_html_confirm_with_custom_fields button on views/inv/modal_inv_to_html.php
    $(document).on('click', '#inv_to_html_confirm_with_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/inv/html/1";    
            window.location.reload;
            window.open(url, '_blank');            
    }); 

    // id="inv_to_html_confirm_without_custom_fields button on views/inv/modal_inv_to_html.php
    $(document).on('click', '#inv_to_html_confirm_without_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/inv/html/0";    
            window.location.reload;
            window.open(url, '_blank');
    });

    $('#inv_discount_amount').keyup(function () {
    if (this.value.length > 0) {
        $('#inv_discount_percent').prop('value', 0.00);
        $('#inv_discount_percent').prop('disabled', true);
    } else {
        $('#inv_discount_percent').prop('disabled', false);
    }
    });
    
    $('#inv_discount_percent').keyup(function () {
    if (this.value.length > 0) {
        $('#inv_discount_amount').prop('value', 0.00);
        $('#inv_discount_amount').prop('disabled', true);
    } else {
        $('#inv_discount_amount').prop('disabled', false);
    }
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

$('#btn_modal_payment_submit').click(function () {
    var url = $(location).attr('origin') + "/invoice/payment/add_with_ajax";
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                invoice_id: $('#inv_id').val(),
                payment_amount: $('#amount').val(),
                payment_method_id: $('#payment_method_id').val(),
                payment_date: $('#date').val(),
                payment_note: $('#note').val()
            },
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {   
                var response =  parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and payment was added
                    if ($('#payment_cf_exist').val() === 'yes') {
                        // There are payment custom fields, display the payment form
                        // to allow completing the custom fields
                        window.location = $(location).attr('origin') + "/invoice/customfields/add_with_ajax" + response.payment_id;
                    }
                    else {
                        // There are no payment custom fields, return to invoice view
                        window.location = "<?php echo $_SERVER['HTTP_REFERER']; ?>";
                    }
                }
                else {
                    // The validation was not successful
                    $('.control-group').removeClass('has-error');
                    for (var key in response.validation_errors) {
                        if(response.validation_errors.hasOwnProperty(key)) {
                            $('#' + key).parent().parent().addClass('has-error');
                        }
                    }
                }
            }
        });
});

        
    


