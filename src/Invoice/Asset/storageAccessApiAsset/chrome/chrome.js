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

    // Run when DOM is ready (mirrors $(function(){...}) )
    function init() {

        // Storage Access API usage (keeps original behavior)
        if (document.hasStorageAccess) {
            document.hasStorageAccess().then(function (hasAccess) {
                if (!hasAccess) {
                    document.requestStorageAccess().then(function () {
                        console.log('Storage access granted!');
                        // Continue with storage operations
                    }).catch(function () {
                        console.error('Storage access denied!');
                        // Handle the denied access
                    });
                }
            }).catch(function (err) { console.error(err); });
        }

        // Delegate click for element with id="#example"
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('#example');
            if (!trigger) return;

            // Prevent default action if this is a button inside a form or link
            e.preventDefault();

            var url = location.origin + "/invoice/inv/inv_to_inv_confirm";

            // gather buttons with class .inv_to_inv_confirm (matches original selector '.inv_to_inv_confirm')
            var btns = Array.from(document.querySelectorAll('.inv_to_inv_confirm'));
            // show spinner state on all matching buttons (mirrors original btn.html(...))
            btns.forEach(function (b) {
                b.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            });

            var absolute_url = new URL(location.href);
            var inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);

            // Collect form field values, matching original selectors #create_inv_client_id and #user_id
            var createInvClientEl = document.getElementById('create_inv_client_id');
            var userIdEl = document.getElementById('user_id');
            var client_id = createInvClientEl ? createInvClientEl.value : '';
            var user_id = userIdEl ? userIdEl.value : '';

            // Build query params (original jQuery used data:{...} with GET)
            var params = new URLSearchParams({
                inv_id: inv_id,
                client_id: client_id,
                user_id: user_id
            });

            fetch(url + '?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                    // try parse JSON, fallback to text
                    return res.text();
                })
                .then(function (text) {
                    var data;
                    try { data = JSON.parse(text); } catch (e) { data = text; }
                    var response = parsedata(data);

                    if (response.success === 1) {
                        // set check UI and reload like original
                        btns.forEach(function (b) {
                            b.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        });
                        window.location = absolute_url;
                        window.location.reload();
                    } else if (response.success === 0) {
                        btns.forEach(function (b) {
                            b.innerHTML = '<h2 class="text-center"><i class="fa fa-times"></i></h2>';
                        });
                        window.location = absolute_url;
                        window.location.reload();
                    } else {
                        // Unexpected response shape — restore button UI and log
                        btns.forEach(function (b) {
                            b.innerHTML = '<h6 class="text-center"><i class="fa fa-spinner"></i></h6>';
                        });
                        console.warn('inv_to_inv_confirm: unexpected response', response);
                    }
                })
                .catch(function (err) {
                    console.warn(err && err.message ? err.message : err);
                    // restore button UI on error
                    btns.forEach(function (b) {
                        b.innerHTML = '<h6 class="text-center"><i class="fa fa-spinner"></i></h6>';
                    });
                    alert('Status: error An error occurred: ' + (err && err.message ? err.message : err));
                });
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

})();