$(function () {
    function parsedata(data) {             
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
          try {
            const obj = JSON.parse(data);
            return obj && typeof obj === 'object' ? obj : {};
          } catch (e) {
            return {};
          }
        }
        return {};
    }
    
    // Toggle Secret Visibility
    $(document).on('click', '#toggleSecret', function () {
        var secretInput = $('#secretInput');
        var eyeIcon = $('#eyeIcon');
        var secretInputType = '';
        var eyeIconClass = '';
        if (secretInput.attr('type') === 'password') {
            secretInputType = secretInput.attr('type', 'text');
            eyeIcon.removeClass('bi-eye').addClass('bi-eye-slash');
            eyeIconClass = eyeIcon.attr('class');
        } else {
            secretInputType = secretInput.attr('type', 'password');
            eyeIcon.removeClass('bi-eye-slash').addClass('bi-eye');
            eyeIconClass = eyeIcon.attr('class');
        }
        var url = $(location).attr('origin') + "/ajaxShowSetup";
        $.ajax({ 
            type: 'GET',
            data: {
                secretInputType: secretInputType,
                eyeIconClass: eyeIconClass, 
            },
            url: url,
            cache: false,
            dataType: 'json',
            success: function (data) {
                       var response = parsedata(data);           
                       if (response.success === 1) {                           
                          $('#secretInput').attr('type', response.secretInputType);
                          $('#eyeIcon').attr('class', response.eyeIconClass);
                       }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown, jqXHR.responseText);
            }
            
        });
    });

    // Copy Secret
    $(document).on('click', '#copySecret', function () {
        var secretInput = $('#secretInput');
        secretInput.attr('type', 'text'); // Temporarily show text to copy hidden value
        secretInput.select();
        document.execCommand('copy');
        secretInput.attr('type', 'password'); // Re-hide if needed
        // Optional: show feedback (e.g., change button icon/text briefly)
    });

    $(document).on('click', '.btn-digit', function () {
        var $otp = $('#code');
        var digit = $(this).data('digit');
        if ($otp.val().length < 6) $otp.val($otp.val() + digit);
    });

    $(document).on('click', '.btn-clear-otp', function () {
        $('#code').val('');
    });
});