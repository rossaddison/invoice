$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };

    // id="salesorder_to_pdf_confirm_with_custom_fields button on views/salesorder/modal_salesorder_to_pdf.php
    $(document).on('click', '#salesorder_to_pdf_confirm_with_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/salesorder/pdf/1";    
            window.open(url, '_blank');
    }); 

    // id="salesorder_to_pdf_confirm_without_custom_fields button on views/salesorder/modal_salesorder_to_pdf.php
    $(document).on('click', '#salesorder_to_pdf_confirm_without_custom_fields', function () {
            var url = $(location).attr('origin') + "/invoice/salesorder/pdf/0";    
            window.open(url, '_blank');
    });
    
    // Creates the invoice
    $(document).on('click', '#so_to_invoice_confirm', function () {        
        var url = $(location).attr('origin') + "/invoice/salesorder/so_to_invoice_confirm";
        var btn = $('.so_to_invoice_confirm');
        var absolute_url = new URL($(location).attr('href'));
        btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
        $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                so_id: $('#so_id').val(),
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
                            alert('Invoice created from Sales Order!');
                        }
                        if (response.success === 0) {
                            // The validation was not successful created
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');                        
                            window.location = absolute_url;
                            window.location.reload();
                            alert('Invoice NOT created from Sales Order! Duplicate Invoice. Copy your Sales Order to another Sales Order and then copy to invoice. Each Sales Order must have a matching invoice.');
                        }    
                        
            },
            error: function(xhr, status, error) {                         
                        console.warn(xhr.responseText);
                        alert('Status: ' + status + ' An error: ' + error.toString());
            }
        });
    });
});

    
        
    


