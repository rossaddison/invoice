(function () {
    "use strict";

    // Safe parse helper (mirrors original parsedata)
    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    // Toggle visibility of SMTP settings based on #email_send_method value
    function toggle_smtp_settings() {
        var emailSendMethodEl = document.getElementById('email_send_method');
        var div = document.getElementById('div-smtp-settings');
        if (!div || !emailSendMethodEl) return;
        if (emailSendMethodEl.value === 'smtp') {
            div.style.display = '';
        } else {
            div.style.display = 'none';
        }
    }

    // Generate fingerprint / client metrics for FPH
    function handleFphGenerateClick() {
        var url = location.origin + "/invoice/setting/fphgenerate";
        var params = new URLSearchParams({
            userAgent: navigator.userAgent,
            width: window.screen.width,
            height: window.screen.height,
            scalingFactor: Math.round(window.devicePixelRatio * 100) / 100,
            colourDepth: window.screen.colorDepth,
            windowInnerWidth: window.innerWidth,
            windowInnerHeight: window.innerHeight
        });

        fetch(url + '?' + params.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                return res.json().catch(function () { return {}; });
            })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    // IDs in original jQuery have brackets, use getElementById for them
                    var el;
                    el = document.getElementById('settings[fph_client_browser_js_user_agent]');
                    if (el) el.value = response.userAgent || '';
                    el = document.getElementById('settings[fph_client_device_id]');
                    if (el) el.value = response.deviceId || '';
                    el = document.getElementById('settings[fph_screen_width]');
                    if (el) el.value = response.width || '';
                    el = document.getElementById('settings[fph_screen_height]');
                    if (el) el.value = response.height || '';
                    el = document.getElementById('settings[fph_screen_scaling_factor]');
                    if (el) el.value = response.scalingFactor || '';
                    el = document.getElementById('settings[fph_screen_colour_depth]');
                    if (el) el.value = response.colourDepth || '';
                    el = document.getElementById('settings[fph_timestamp]');
                    if (el) el.value = response.timestamp || '';
                    el = document.getElementById('settings[fph_window_size]');
                    if (el) el.value = response.windowSize || '';
                    el = document.getElementById('settings[fph_gov_client_user_id]');
                    if (el) el.value = response.userUuid || '';
                }
            })
            .catch(function (err) {
                console.error('FPH generate failed', err);
            });
    }

    // Generate cron key
    function handleGenerateCronKeyClick() {
        var btns = Array.from(document.querySelectorAll('.btn_generate_cron_key'));
        btns.forEach(function (b) {
            b.innerHTML = '<i class="fa fa-spin fa-spinner fa-margin"></i>';
        });

        var url = location.origin + "/invoice/setting/get_cron_key";
        fetch(url, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                return res.json().catch(function () { return {}; });
            })
            .then(function (data) {
                var response = parsedata(data);
                if (response.success === 1) {
                    // class .cron_key may appear on multiple elements
                    Array.from(document.querySelectorAll('.cron_key')).forEach(function (el) {
                        if ('value' in el) el.value = response.cronkey || '';
                    });
                    btns.forEach(function (b) {
                        b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>';
                    });
                } else {
                    // restore UI if not successful
                    btns.forEach(function (b) {
                        b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>';
                    });
                }
            })
            .catch(function (err) {
                console.error('get_cron_key failed', err);
                btns.forEach(function (b) {
                    b.innerHTML = '<i class="fa fa-recycle fa-margin"></i>';
                });
            });
    }

    // Submit settings form
    function handleSettingsSubmitClick() {
        var form = document.getElementById('form-settings');
        if (form) form.submit();
    }

    // Online payment select change handler (show/hide gateway settings)
    function handleOnlinePaymentSelectChange() {
        var sel = document.getElementById('online-payment-select');
        if (!sel) return;
        var driver = sel.value;
        Array.from(document.querySelectorAll('.gateway-settings')).forEach(function (el) {
            if (!el.classList.contains('active-gateway')) el.classList.add('hidden');
        });
        var target = document.getElementById('gateway-settings-' + driver);
        if (target) {
            target.classList.remove('hidden');
            target.classList.add('active-gateway');
        }
    }

    // Wire up handlers on DOMContentLoaded (and initialise state)
    document.addEventListener('DOMContentLoaded', function () {
        // initialise SMTP toggle
        toggle_smtp_settings();

        // email_send_method change
        var emailSendMethodEl = document.getElementById('email_send_method');
        if (emailSendMethodEl) emailSendMethodEl.addEventListener('change', toggle_smtp_settings);

        // FPH generate button
        var fphBtn = document.getElementById('btn_fph_generate');
        if (fphBtn) fphBtn.addEventListener('click', function (e) { e.preventDefault(); handleFphGenerateClick(); });

        // Generate cron key
        var cronBtn = document.getElementById('btn_generate_cron_key');
        if (cronBtn) cronBtn.addEventListener('click', function (e) { e.preventDefault(); handleGenerateCronKeyClick(); });

        // Submit
        var submitBtn = document.getElementById('btn-submit');
        if (submitBtn) submitBtn.addEventListener('click', function (e) { e.preventDefault(); handleSettingsSubmitClick(); });

        // Online payment select
        var onlineSel = document.getElementById('online-payment-select');
        if (onlineSel) onlineSel.addEventListener('change', function () { handleOnlinePaymentSelectChange(); });

        // Run online payment handler once to ensure initial state
        handleOnlinePaymentSelectChange();
    });

})();