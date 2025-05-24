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
    
    $(document).on('click', '#btn_fph_generate', function () {
        var url = $(location).attr('origin') + "/invoice/setting/fphgenerate";
        var userAgent = navigator.userAgent;
        $.ajax({
            type: 'GET',
            data: {
                userAgent: userAgent,
                width: window.screen.width,
                height: window.screen.height,
                scalingFactor: Math.round(window.devicePixelRatio * 100) / 100,  
                colourDepth: window.screen.colorDepth,
                // window size: width x height
                windowInnerWidth: window.innerWidth,
                windowInnerHeight: window.innerHeight
            },
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    $('#settings\\[fph_client_browser_js_user_agent\\]').val(response.userAgent);
                    $('#settings\\[fph_client_device_id\\]').val(response.deviceId);
                    $('#settings\\[fph_screen_width\\]').val(response.width);
                    $('#settings\\[fph_screen_height\\]').val(response.height);
                    $('#settings\\[fph_screen_scaling_factor\\]').val(response.scalingFactor);
                    $('#settings\\[fph_screen_colour_depth\\]').val(response.colourDepth);
                    $('#settings\\[fph_timestamp\\]').val(response.timestamp);
                    $('#settings\\[fph_window_size\\]').val(response.windowSize);
                    $('#settings\\[fph_gov_client_user_id\\]').val(response.userUuid);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
            }
        });
    });
        
    $(document).on('click', '#btn_generate_cron_key', function () {
        var btn = $('.btn_generate_cron_key');      
        btn.html('<i class="fa fa-spin fa-spinner fa-margin"></i>');
        var url = $(location).attr('origin') + "/invoice/setting/get_cron_key";
        $.ajax({ type: 'GET',
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