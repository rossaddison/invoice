$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };    

    $(function () {
        var email_template_type = $('#email_template_type').val();
        var $email_template_type_options = $("[name=email_template_type]");
        $email_template_type_options.click(function () {
            // remove class "show" and deselect any selected elements.
            $(".show").removeClass("show").parent("select").each(function () {
                this.options.selectedIndex = 0;
            });
            // add show class to corresponding class
            $(".hidden-" + $(this).val()).addClass("show");
        });
        if (email_template_type === "") {
            $email_template_type_options.first().click();
        } else {
            $email_template_type_options.each(function () {
                if ($(this).val() === email_template_type) {
                    $(this).click();
                }
            });
        }
    });
    
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
                          $('#cron_key').val(response.cron_key);
                          btn.html('<i class="fa fa-recycle fa-margin"></i>');
                       }
            }
        });
    });    
    
    $(document).ready(function() {
        // find the type of template that has been loaded and enable/disable
        // the invoice and quote selects as required
        var inputValue = $('input[type="radio"]:checked').attr("value");
        if (inputValue === 'quote') {
            $('#tags_invoice').prop('disabled', 'disabled');
            $('#tags_quote').prop('disabled', false);
        } else {
            // inputValue === 'invoice'
            $('#tags_invoice').prop('disabled', false);
            $('#tags_quote').prop('disabled', 'disabled');
        }
        // if the radio input for 'type of template' gets clicked, check the
        // new value and enable/disable the invoice and quote selects as required.
    	$('input[type="radio"]').click(function() {
            var inputValue = $(this).attr("value");
            if (inputValue === 'quote') {
            	$('#tags_invoice').prop('disabled', 'disabled');
            	$('#tags_quote').prop('disabled', false);
            } else {
                // inputValue === 'invoice'
            	$('#tags_invoice').prop('disabled', false);
            	$('#tags_quote').prop('disabled', 'disabled');
            }
        });        
        
        $('#btn-submit').click(function () {
            $('#form-settings').submit();
        });
        
        $(document).ajaxComplete(function () {
            $('[name="_csrf"]').val(Cookies.get('_csrf'));
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

        // Enable clipboard toggles
        var clipboards = new ClipboardJS('.to-clipboard');

        // Keep track of the last "taggable" input/textarea
        $('.taggable').on('focus', function () {
            window.lastTaggableClicked = this;
        });

        // Template Tag handling
        // taginv-select used in template-tags-inv.php,
        // template-tags-quote.php, template-tags.php
        // The original tag-select has been modified
        $('.taginv-select').on('change', function (event) {
            var select = $(event.currentTarget);
            // Add the tag to the field
            if (typeof window.lastTaggableClicked !== 'undefined') {
                insert_at_caret(window.lastTaggableClicked.id, select.val());
            }
            // Reset the select and exit
            select.val([]);
            return false;
        });

        // HTML tags to email templates textarea
        $('.html-tag').click(function () {
            var tag_type = $(this).data('tagType');
            var body_id = $('.email-template-body').attr('id');
            insert_html_tag(tag_type, body_id);
        });

        // Email Template Preview handling
        var email_template_body_id = $('.email-template-body').attr('id');

        if ($('#email_template_preview').empty()) {
            update_email_template_preview();
        }

        $(email_template_body_id).bind('input propertychange', function () {
            update_email_template_preview();
        });

        $('#email-template-preview-reload').click(function () {
            update_email_template_preview();
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
    
    $(document).on('change', '#online-payment-select', function () {
        var online_payment_select = $('#online-payment-select');
        var driver = online_payment_select.val();           
        $('.gateway-settings:not(.active-gateway)').addClass('hidden');
        $('#gateway-settings-' + driver).removeClass('hidden').addClass('active-gateway');
    });
    
    // Insert text into textarea at Caret Position
    function insert_at_caret(areaId, text) {
        var txtarea = document.getElementById(areaId),
            scrollPos = txtarea.scrollTop,
            strPos = 0,
            br = ((txtarea.selectionStart || txtarea.selectionStart === '0') ?
                "ff" : (document.selection ? "ie" : false)),
            range;

        if (br === "ie") {
            txtarea.focus();
            range = document.selection.createRange();
            range.moveStart('character', -txtarea.value.length);
            strPos = range.text.length;
        } else if (br === "ff") {
            strPos = txtarea.selectionStart;
        }

        var front = (txtarea.value).substring(0, strPos),
            back = (txtarea.value).substring(strPos, txtarea.value.length);

        txtarea.value = front + text + back;
        strPos = strPos + text.length;
        if (br === "ie") {
            txtarea.focus();
            range = document.selection.createRange();
            range.moveStart('character', -txtarea.value.length);
            range.moveStart('character', strPos);
            range.moveEnd('character', 0);
            range.select();
        } else if (br === "ff") {
            txtarea.selectionStart = strPos;
            txtarea.selectionEnd = strPos;
            txtarea.focus();
        }
        txtarea.scrollTop = scrollPos;
    }

    function update_email_template_preview() {
        $('#email-template-preview').contents().find("body").html($('.email-template-body').val());
    }

    // Insert HTML tags into textarea
    function insert_html_tag(tag_type, destination_id) {
        var text, sel, text_area, selectedText, startPos, endPos, replace, replaceText, len;
        switch (tag_type) {
            case 'text-bold':
                text = ['<b>', '</b>'];
                break;
            case 'text-italic':
                text = ['<em>', '</em>'];
                break;
            case 'text-paragraph':
                text = ['<p>', '</p>'];
                break;
            case 'text-linebreak':
                text = ['<br>', ''];
                break;

            case 'text-h1':
                text = ['<h1>', '</h1>'];
                break;
            case 'text-h2':
                text = ['<h2>', '</h2>'];
                break;
            case 'text-h3':
                text = ['<h3>', '</h3>'];
                break;
            case 'text-h4':
                text = ['<h4>', '</h4>'];
                break;

            case 'text-code':
                text = ['<code>', '</code>'];
                break;
            case 'text-hr':
                text = ['<hr/>', ''];
                break;
            case 'text-css':
                text = ['<style></style>', ''];
                break;
        }

        // Get the selected text
        text_area = document.getElementById(destination_id);
        if (document.selection !== undefined) {
            text_area.focus();
            sel = document.selection.createRange();
            selectedText = sel.text;
        }
        else if (text_area.selectionStart !== '') {
            startPos = text_area.selectionStart;
            endPos = text_area.selectionEnd;
            selectedText = text_area.value.substring(startPos, endPos);
        }

        // Check if <style> should be added
        if (tag_type === 'text-css') {
            replace = text[0] + '\n\r' + text_area.value;
            $(text_area).val(replace);
            update_email_template_preview();
            return true;
        }

        // Check if there is only one HTML tag
        if (text[1].length === 0) {
            insert_at_caret(destination_id, text[0]);
            update_email_template_preview();
            return true;
        }

        // Check if text is selected, replace it or just insert the tag at cursor position
        if (!selectedText || !selectedText.length) {
            text = text[0] + text[1];
            insert_at_caret(destination_id, text);
            update_email_template_preview();
        } else {
            replaceText = text[0] + selectedText + text[1];
            len = text_area.value.length;
            replace = text_area.value.substring(0, startPos) + replaceText + text_area.value.substring(endPos, len);
            $(text_area).val(replace);
            update_email_template_preview();
        }
    }
});



    
        
    


