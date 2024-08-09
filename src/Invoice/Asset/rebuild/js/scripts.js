"use strict";

$(document).ready(function () {

    // Automatical CSRF protection for all POST requests
    $.ajaxPrefilter(function (options) {
        if (options.type === 'post' || options.type === 'POST' || options.type === 'Post') {
            if (options.data === '') {
                options.data += '?_csrf=' + Cookies.get('_csrf_cookie');
            } else {
                options.data += '&_csrf=' + Cookies.get('_csrf_cookie');
            }
        }
    });

    $(document).ajaxComplete(function () {
        $('[name="_ip_csrf"]').val(Cookies.get('ip_csrf_cookie'));
    });

    // Correct the height of the content area
    var $content = $('#content'),
        $html = $('html');

    var documentHeight = $html.outerHeight(),
        navbarHeight = $('.navbar').outerHeight(),
        headerbarHeight = $('#headerbar').outerHeight(),
        submenuHeight = $('#submenu').outerHeight(),
        contentHeight = documentHeight - navbarHeight - headerbarHeight - submenuHeight;
    if ($content.outerHeight() < contentHeight) {
        $content.outerHeight(contentHeight);
    }

    // Dropdown Datepicker fix
    $html.click(function () {
        $('.dropdown-menu:visible').not('.datepicker').removeAttr('style');
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Select2 for all select inputs
    $('.simple-select').select2();
    
    // Fullpage loader
    $(document).on('click', '.ajax-loader', function () {
        $('#fullpage-loader').fadeIn(200);
        window.fullpageloaderTimeout = window.setTimeout(function () {
            $('#loader-error').fadeIn(200);
            $('#loader-icon').removeClass('fa-spin').addClass('text-danger');
        }, 10000);
    });

    $(document).on('click', '.fullpage-loader-close', function () {
        $('#fullpage-loader').fadeOut(200);
        $('#loader-error').hide();
        $('#loader-icon').addClass('fa-spin').removeClass('text-danger');
        clearTimeout(window.fullpageloaderTimeout);
    });

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
