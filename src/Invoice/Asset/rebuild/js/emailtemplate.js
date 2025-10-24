(function () {
    "use strict";

    // Safe parse helper equivalent to original parsedata
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Insert text at caret position (kept original behaviour)
    function insert_at_caret(areaId, text) {
        if (text === null || text === undefined) return;
        var txtarea = document.getElementById(areaId);
        if (!txtarea) return;
        var scrollPos = txtarea.scrollTop;
        var strPos = 0;
        var br = ((typeof txtarea.selectionStart !== 'undefined') ? "ff" : (document.selection ? "ie" : false));
        var range;

        if (br === "ie" && document.selection) {
            txtarea.focus();
            range = document.selection.createRange();
            range.moveStart('character', -txtarea.value.length);
            strPos = range.text.length;
        } else if (br === "ff") {
            strPos = txtarea.selectionStart;
        }

        var front = txtarea.value.substring(0, strPos);
        var back = txtarea.value.substring(strPos, txtarea.value.length);

        txtarea.value = front + text + back;
        strPos = strPos + text.length;

        if (br === "ie" && document.selection) {
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

    // Insert HTML tags into textarea (converted from jQuery version)
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
            default:
                return;
        }

        text_area = document.getElementById(destination_id);
        if (!text_area) return;

        if (document.selection !== undefined && document.selection.createRange) {
            text_area.focus();
            sel = document.selection.createRange();
            selectedText = sel.text;
        } else if (typeof text_area.selectionStart !== 'undefined') {
            startPos = text_area.selectionStart;
            endPos = text_area.selectionEnd;
            selectedText = text_area.value.substring(startPos, endPos);
        }

        // If <style> tag, prepend to whole content
        if (tag_type === 'text-css') {
            var replaceCss = text[0] + '\n\r' + text_area.value;
            text_area.value = replaceCss;
            if (typeof update_email_template_preview === 'function') update_email_template_preview();
            return true;
        }

        // If only one tag (no closing), insert and done
        if (text[1].length === 0) {
            insert_at_caret(destination_id, text[0]);
            if (typeof update_email_template_preview === 'function') update_email_template_preview();
            return true;
        }

        // If selection exists, replace; otherwise insert at caret
        if (!selectedText || !selectedText.length) {
            var both = text[0] + text[1];
            insert_at_caret(destination_id, both);
            if (typeof update_email_template_preview === 'function') update_email_template_preview();
        } else {
            replaceText = text[0] + selectedText + text[1];
            len = text_area.value.length;
            replace = text_area.value.substring(0, startPos) + replaceText + text_area.value.substring(endPos, len);
            text_area.value = replace;
            if (typeof update_email_template_preview === 'function') update_email_template_preview();
        }
    }

    // Toggle SMTP settings visibility
    function toggle_smtp_settings() {
        var emailSendMethodEl = document.getElementById('email_send_method');
        var method = emailSendMethodEl ? emailSendMethodEl.value : '';
        var div = document.getElementById('div-smtp-settings');
        if (!div) return;
        if (method === 'smtp') {
            div.style.display = '';
        } else {
            div.style.display = 'none';
        }
    }

    // Helper to set disabled property using the same semantics as original .prop('disabled', 'disabled') / .prop('disabled', false)
    function setDisabled(selector, disabled) {
        var el = document.querySelector(selector);
        if (!el) return;
        if (disabled) el.setAttribute('disabled', 'disabled');
        else el.removeAttribute('disabled');
    }

    // Update the invoice/quote tags select enable/disable state based on selected radio (mirrors original)
    function update_tags_enable_state(value) {
        if (value === 'quote') {
            setDisabled('#tags_invoice', true);
            setDisabled('#tags_quote', false);
        } else {
            setDisabled('#tags_invoice', false);
            setDisabled('#tags_quote', true);
        }
    }

    // Reset select element to no selection (for multi-selects)
    function clearSelect(elem) {
        if (!elem) return;
        if (elem.multiple) {
            Array.from(elem.options).forEach(function (opt) { opt.selected = false; });
            // For libraries like TomSelect you may need to call their API to clear; attempt common ones:
            if (elem._tomselect && typeof elem._tomselect.clear === 'function') elem._tomselect.clear();
            if (elem.tomselect && typeof elem.tomselect.clear === 'function') elem.tomselect.clear();
        } else {
            elem.value = '';
        }
    }

    // Initialize behaviour after DOM content is ready
    function init() {
        // email template type radio behaviour
        var email_template_type_el = document.getElementById('email_template_type');
        var email_template_type = email_template_type_el ? email_template_type_el.value : '';
        var emailTypeOptions = document.querySelectorAll('[name="email_template_type"]');

        function emailTypeClickHandler(ev) {
            var val = (ev.currentTarget && ev.currentTarget.value) || ev.target.value;
            // remove 'show' from any element, and reset parent select if parent is a select
            Array.from(document.querySelectorAll('.show')).forEach(function (el) {
                el.classList.remove('show');
                var parentSelect = el.closest('select');
                if (parentSelect) parentSelect.selectedIndex = 0;
            });
            // add show to the elements with class hidden-{value}
            Array.from(document.querySelectorAll('.hidden-' + val)).forEach(function (el) {
                el.classList.add('show');
            });
        }

        if (emailTypeOptions && emailTypeOptions.length) {
            emailTypeOptions.forEach(function (opt) {
                opt.addEventListener('click', emailTypeClickHandler, { passive: true });
            });

            if (!email_template_type) {
                // click first
                var first = emailTypeOptions[0];
                if (first && typeof first.click === 'function') first.click();
            } else {
                emailTypeOptions.forEach(function (opt) {
                    if (opt.value === email_template_type && typeof opt.click === 'function') {
                        opt.click();
                    }
                });
            }
        }

        // SMTP toggle
        var emailSendMethodEl = document.getElementById('email_send_method');
        if (emailSendMethodEl) {
            emailSendMethodEl.addEventListener('change', toggle_smtp_settings);
        }
        toggle_smtp_settings();

        // Generate cron key
        var btnGenerate = document.getElementById('btn_generate_cron_key');
        if (btnGenerate) {
            btnGenerate.addEventListener('click', function () {
                var btns = Array.from(document.querySelectorAll('.btn_generate_cron_key'));
                btns.forEach(function (b) { b.innerHTML = '<i class="fa fa-spin fa-spinner fa-margin"></i>'; });
                var url = location.origin + "/invoice/setting/get_cron_key";
                fetch(url, { method: 'GET', cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json().catch(function () { return {}; });
                    })
                    .then(function (data) {
                        var response = parsedata(data);
                        if (response && response.success === 1) {
                            var cronEl = document.getElementById('cron_key');
                            if (cronEl) cronEl.value = response.cron_key || '';
                            btns.forEach(function (b) { b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>'; });
                        } else {
                            btns.forEach(function (b) { b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>'; });
                        }
                    })
                    .catch(function (err) {
                        console.error('get_cron_key failed', err);
                        btns.forEach(function (b) { b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>'; });
                    });
            }, { passive: true });
        }

        // Initial radio checked handling and radio click binding
        var checkedRadio = document.querySelector('input[type="radio"]:checked');
        var inputValue = checkedRadio ? checkedRadio.value : '';
        update_tags_enable_state(inputValue);

        var radios = document.querySelectorAll('input[type="radio"]');
        radios.forEach(function (r) {
            r.addEventListener('click', function (ev) {
                var v = ev.currentTarget.value;
                update_tags_enable_state(v);
            }, { passive: true });
        });

        // btn-submit triggers form submit
        var btnSubmit = document.getElementById('btn-submit');
        if (btnSubmit) {
            btnSubmit.addEventListener('click', function () {
                var form = document.getElementById('form-settings');
                if (form) form.submit();
            }, { passive: true });
        }

        // Correct the height of the content area (approximate original outerHeight behaviour)
        var contentEl = document.getElementById('content');
        var htmlEl = document.documentElement;
        var documentHeight = htmlEl ? (htmlEl.offsetHeight || htmlEl.clientHeight) : (document.body ? document.body.clientHeight : 0);
        var navbarHeight = (document.querySelector('.navbar') || {}).offsetHeight || 0;
        var headerbarHeight = (document.getElementById('headerbar') || {}).offsetHeight || 0;
        var submenuHeight = (document.getElementById('submenu') || {}).offsetHeight || 0;
        var contentHeight = documentHeight - navbarHeight - headerbarHeight - submenuHeight;
        if (contentEl && (contentEl.offsetHeight || contentEl.clientHeight) < contentHeight) {
            contentEl.style.minHeight = contentHeight + 'px';
        }

        // Keep track of last taggable clicked
        Array.from(document.querySelectorAll('.taggable')).forEach(function (el) {
            el.addEventListener('focus', function () { window.lastTaggableClicked = this; }, { passive: true });
        });

        // taginv-select change: insert tag at caret then reset select
        Array.from(document.querySelectorAll('.taginv-select')).forEach(function (select) {
            select.addEventListener('change', function (event) {
                var sel = event.currentTarget;
                if (typeof window.lastTaggableClicked !== 'undefined' && window.lastTaggableClicked) {
                    insert_at_caret(window.lastTaggableClicked.id, sel.value);
                }
                // reset select (clear selection)
                clearSelect(sel);
                // prevent default selection propagation like original returning false
                event.preventDefault();
                return false;
            }, false);
        });

        // html-tag click -> insert tag into email template body
        Array.from(document.querySelectorAll('.html-tag')).forEach(function (el) {
            el.addEventListener('click', function () {
                var tag_type = el.getAttribute('data-tag-type') || el.dataset.tagType || el.getAttribute('data-tagType');
                var bodyEl = document.querySelector('.email-template-body');
                var body_id = bodyEl ? bodyEl.id : null;
                if (tag_type && body_id) insert_html_tag(tag_type, body_id);
            }, { passive: true });
        });

        // Password meter handling
        var passwordInputs = Array.from(document.querySelectorAll('.passwordmeter-input'));
        if (passwordInputs.length) {
            passwordInputs.forEach(function (password_input) {
                password_input.addEventListener('input', function () {
                    var strength = null;
                    try { if (typeof zxcvbn === 'function') strength = zxcvbn(password_input.value); } catch (e) { strength = null; }
                    Array.from(document.querySelectorAll('.passmeter-2, .passmeter-3')).forEach(function (el) { el.style.display = 'none'; });
                    if (strength && typeof strength.score === 'number') {
                        if (strength.score === 4) {
                            Array.from(document.querySelectorAll('.passmeter-2, .passmeter-3')).forEach(function (el) { el.style.display = ''; });
                        } else if (strength.score === 3) {
                            Array.from(document.querySelectorAll('.passmeter-2')).forEach(function (el) { el.style.display = ''; });
                        }
                    }
                }, { passive: true });
            });
        }

        // online payment gateway select change handler
        var onlinePaymentSelect = document.getElementById('online-payment-select');
        if (onlinePaymentSelect) {
            onlinePaymentSelect.addEventListener('change', function () {
                var driver = onlinePaymentSelect.value;
                Array.from(document.querySelectorAll('.gateway-settings')).forEach(function (el) {
                    if (!el.classList.contains('active-gateway')) el.classList.add('hidden');
                });
                var target = document.getElementById('gateway-settings-' + driver);
                if (target) {
                    target.classList.remove('hidden');
                    target.classList.add('active-gateway');
                }
            }, { passive: true });
        }
    }

    // Run init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // Already loaded
        init();
    }

})();