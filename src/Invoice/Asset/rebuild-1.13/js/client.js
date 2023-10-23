$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };

    // id="client_create_confirm button on views/invoice/client/modal_create_client.php
    $(document).on('click', '#client_create_confirm', function () {
    var url = $(location).attr('origin') + "/invoice/client/create_confirm";
    var btn = $('.client_create_confirm');
    var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                client_name: $('#client_name').val(),
                client_surname: $('#client_surname').val(),
                client_email: $('#client_email').val(),
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
    
    // id="save_client_note_new button on views/invoice/client/view.php
    $(document).on('click', '#save_client_note_new', function () {
    var url = $(location).attr('origin') + "/invoice/client/save_client_note_new";
    var url_note_list = $(location).attr('origin') + "/invoice/client/load_client_notes";
    var btn_note = $('.save_client_note');
    var absolute_url = new URL($(location).attr('href'));
    btn_note.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    $.ajax({type: 'GET',
            contentType: "application/json; charset=utf-8",
            data: {
                client_id: $('#client_id').val(),
                client_note: $('#client_note').val()
            },                
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                        var response =  parsedata(data);
                        if (response.success === 1) {
                            // The validation was successful and quote was created
                            btn_note.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');
                            $('#client_note').val('');
                            $('#notes_list').load(url_note_list,
                            {
                                client_id: $('#client_id').val()
                            }, function (response) {
                                console.log(response); 
                            });
                            window.location = absolute_url;
                            window.location.reload();
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
});
