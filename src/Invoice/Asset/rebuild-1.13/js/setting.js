$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    toggle_smtp_settings();

    $('#email_send_method').change(function () {
        toggle_smtp_settings();
    });

    function toggle_smtp_settings() {
        email_send_method = $('#email_send_method').val();

        if (email_send_method === 'smtp') {
            $('#div-smtp-settings').show();
        } else {
            $('#div-smtp-settings').hide();
        }
    }
        
    $(document).on('click', '#btn_generate_cron_key', function () {
        var btn = $('.btn_generate_cron_key');      
        btn.html('<i class="fa fa-spin fa-spinner fa-margin"></i>');
        var url = $(location).attr('origin') + "/invoice/setting/get_cron_key";
        $.ajax({ type: 'GET',
            contentType: "application/json; charset=utf-8",
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                       var response = parsedata(data);           
                       if (response.success === 1) {                           
                          $('.cron_key').val(response.cronkey);
                          btn.html('<i class="fa fa-recycle fa-margin"></i>');
                       }
            }
        });
    });    
    
    $(document).ready(function() {
        $('#btn-submit').click(function () {
            $('#form-settings').submit();
        });
    });
    
    $(document).on('change', '#online-payment-select', function () {
        var online_payment_select = $('#online-payment-select');
        var driver = online_payment_select.val();           
        $('.gateway-settings:not(.active-gateway)').addClass('hidden');
        $('#gateway-settings-' + driver).removeClass('hidden').addClass('active-gateway');
    });
});

    
        
    


