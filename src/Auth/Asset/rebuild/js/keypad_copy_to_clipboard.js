// Converted from jQuery to vanilla JS.
// Preserves original selectors (# and .) and behavior.
(function () {
    "use strict";

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

    function closestSafe(el, selector) {
        try {
            if (!el) return null;
            if (typeof el.closest === 'function') return el.closest(selector);
        } catch (e) { /* ignore */ }
        return null;
    }

    // Delegated click handling to mirror $(document).on(...)
    document.addEventListener('click', function (event) {
        var target = event.target;

        // Toggle Secret Visibility
        var toggle = closestSafe(target, '#toggleSecret');
        if (toggle) {
            try {
                var secretInput = document.getElementById('secretInput');
                var eyeIcon = document.getElementById('eyeIcon');
                if (!secretInput) return;

                // Determine new type and toggle classes
                var newType = secretInput.getAttribute('type') === 'password' ? 'text' : 'password';

                if (eyeIcon) {
                    if (newType === 'text') {
                        eyeIcon.classList.remove('bi-eye');
                        eyeIcon.classList.add('bi-eye-slash');
                    } else {
                        eyeIcon.classList.remove('bi-eye-slash');
                        eyeIcon.classList.add('bi-eye');
                    }
                }

                // Prepare values to send to server
                var eyeIconClass = eyeIcon ? eyeIcon.getAttribute('class') : '';
                var params = new URLSearchParams({
                    secretInputType: newType,
                    eyeIconClass: eyeIconClass
                });

                var url = location.origin + "/ajaxShowSetup";

                fetch(url + '?' + params.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                        return res.text();
                    })
                    .then(function (text) {
                        var response = parsedata(text);
                        if (response.success === 1) {
                            if (secretInput && response.secretInputType) {
                                secretInput.setAttribute('type', response.secretInputType);
                            }
                            if (eyeIcon && response.eyeIconClass) {
                                eyeIcon.setAttribute('class', response.eyeIconClass);
                            }
                        }
                    })
                    .catch(function (err) {
                        console.error('AJAX error:', err);
                    });
            } catch (e) {
                console.error('toggleSecret handler error', e);
            }
            return;
        }

        // Copy Secret
        var copyBtn = closestSafe(target, '#copySecret');
        if (copyBtn) {
            try {
                var secretEl = document.getElementById('secretInput');
                if (!secretEl) return;

                var originalType = secretEl.getAttribute('type');
                // Temporarily make visible to copy value
                secretEl.setAttribute('type', 'text');

                // Use navigator.clipboard when available
                var valueToCopy = secretEl.value || secretEl.textContent || '';
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(valueToCopy).catch(function (err) {
                        // fallback to old execCommand approach
                        try {
                            secretEl.select();
                            document.execCommand('copy');
                        } catch (e) {
                            console.error('copy fallback failed', e);
                        }
                    });
                } else {
                    // fallback: select and execCommand
                    try {
                        secretEl.select();
                        document.execCommand('copy');
                    } catch (e) {
                        console.error('copy failed', e);
                    }
                }

                // Restore original type
                secretEl.setAttribute('type', originalType || 'password');
                // Optional: provide UI feedback here (not included)
            } catch (e) {
                console.error('copySecret handler error', e);
            }
            return;
        }

        // Digit input buttons for OTP
        var digitBtn = closestSafe(target, '.btn-digit');
        if (digitBtn) {
            try {
                var otp = document.getElementById('code');
                if (!otp) return;
                var digit = digitBtn.getAttribute('data-digit');
                if (!digit) return;
                // Enforce max length 6 (like original)
                if ((otp.value || '').length < 6) {
                    otp.value = (otp.value || '') + digit;
                }
            } catch (e) {
                console.error('btn-digit handler error', e);
            }
            return;
        }

        // Clear OTP
        var clearBtn = closestSafe(target, '.btn-clear-otp');
        if (clearBtn) {
            try {
                var codeEl = document.getElementById('code');
                if (codeEl) codeEl.value = '';
            } catch (e) {
                console.error('btn-clear-otp handler error', e);
            }
            return;
        }
    }, true);
})();