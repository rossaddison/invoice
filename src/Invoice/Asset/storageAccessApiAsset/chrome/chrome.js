$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    if (document.hasStorageAccess) {
        document.hasStorageAccess().then(hasAccess => {
            if (!hasAccess) {
                document.requestStorageAccess().then(() => {
                    console.log('Storage access granted!');
                    // Continue with storage operations
                }).catch(() => {
                    console.error('Storage access denied!');
                    // Handle the denied access
                });
            }
        }).catch(console.error);
    }
    
    // Copies the invoice to a specific client
    $(document).on('click', '#example', function () {        
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
});