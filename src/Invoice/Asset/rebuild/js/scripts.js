"use strict";

$(document).ready(function () {    
    // used in inv/guest quote/partial_item_table inv/partial_item_table
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Select2 for all select inputs. used in modal_product_lookups.js
    $('.simple-select').select2();
    
    // used in inv/index quote/index and numberous other places e.g. $toolbarReset
    $(document).on('click', '.ajax-loader', function () {
        $('#fullpage-loader').fadeIn(200);
        window.fullpageloaderTimeout = window.setTimeout(function () {
            $('#loader-error').fadeIn(200);
            $('#loader-icon').removeClass('fa-spin').addClass('text-danger');
        }, 10000);
    });
    
    // used in resources/views/layout/invoice
    $(document).on('click', '.fullpage-loader-close', function () {
        $('#fullpage-loader').fadeOut(200);
        $('#loader-error').hide();
        $('#loader-icon').addClass('fa-spin').removeClass('text-danger');
        clearTimeout(window.fullpageloaderTimeout);
    });
    
    // used in emailtemplate.js
    var password_input = $('.passwordmeter-input');
    if (password_input) {
        password_input.on('input', function(){
            var strength = zxcvbn(password_input.val());

            $('.passmeter-2, .passmeter-3').hide();
            if (strength.score === 4) {
                $('.passmeter-2, .passmeter-3').show();
            } else if (strength.score === 3) {
                $('.passmeter-2').show();
            }
        });
    }
});
