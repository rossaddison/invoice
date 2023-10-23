$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    $('#btn_save_client_custom_fields').click(function () {         
        var url = $(location).attr('origin') + "/invoice/client/save_custom_fields";
        var custom = $('input[name^=custom],select[name^=custom]');
        $.ajax({ type: 'GET',
                 contentType: "application/json; charset=utf-8",
                 data: {
                        custom: custom.serializeArray()
                 },
                 url: url,
                 cache: false,
                 dataType: 'json',
                 success: function (data) {
                            var response = parsedata(data);
                            if (response.success === 1) {
                                 location.reload(true);
                                 custom.html(response.custom);
                                 alert('Successful');
                            }
                 },
                 error: function(xhr, status, error) {                         
                        console.warn(xhr.responseText);
                        alert('Status: ' + status + ' whilst saving client: ' + error.toString());
                 }
        });            
    });
});

    
        
    


