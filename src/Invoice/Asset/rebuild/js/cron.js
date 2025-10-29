/*!
 * generate-cron-key.js
 *
 * Vanilla ES5 script that wires the button with id "btn_generate_cron_key"
 * to generate a secure random cron key, fill the input with id "settings[cron_key]",
 * attempt to copy it to the clipboard (with a fallback to document.execCommand('copy')),
 * and provide simple visual feedback on the button (spinner -> check -> restore).
 *
 * Usage: include this script on the settings page (after the DOM elements),
 * or let it auto-init on DOMContentLoaded.
 */

(function () {
    'use strict';

    function generateSecureHex(bytes) {
        bytes = bytes || 24; // default 24 bytes -> 48 hex chars
        var arr = null;
        if (typeof window !== 'undefined' && (window.crypto || window.msCrypto)) {
            var cryptoObj = window.crypto || window.msCrypto;
            try {
                arr = new Uint8Array(bytes);
                cryptoObj.getRandomValues(arr);
            } catch (e) {
                arr = null;
            }
        }

        if (!arr) {
            // Fallback to Math.random() (not cryptographically secure) if crypto unavailable
            arr = new Uint8Array(bytes);
            for (var i = 0; i < bytes; i++) {
                arr[i] = Math.floor(Math.random() * 256);
            }
        }

        var hex = [];
        for (var j = 0; j < arr.length; j++) {
            var h = arr[j].toString(16);
            if (h.length === 1) {
                h = '0' + h;
            }
            hex.push(h);
        }
        return hex.join('');
    }

    function setButtonWorkingState(button, working) {
        if (!button) {
            return;
        }
        if (working) {
            try {
                // store original markup so we can restore
                button.setAttribute('data-original-html', button.innerHTML);
            } catch (e) {
                // ignore
            }
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('disabled', 'true');
            button.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';
        } else {
            var original = button.getAttribute('data-original-html');
            if (original) {
                button.innerHTML = original;
                button.removeAttribute('data-original-html');
            }
            button.removeAttribute('aria-busy');
            button.removeAttribute('disabled');
        }
    }

    function tryCopyToClipboard(text, inputElement, onSuccess, onFail) {
        // Prefer modern clipboard API
        if (navigator && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(function () {
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }
            })["catch"](function (err) {
                // fallback to execCommand
                fallbackCopy(inputElement, onSuccess, onFail);
            });
        } else {
            // fallback to execCommand
            fallbackCopy(inputElement, onSuccess, onFail);
        }
    }

    function fallbackCopy(inputElement, onSuccess, onFail) {
        try {
            if (!inputElement) {
                if (typeof onFail === 'function') {
                    onFail();
                }
                return;
            }
            // Select the input's value
            var currentSelectionStart = null;
            var currentSelectionEnd = null;
            try {
                // save selection if possible
                currentSelectionStart = inputElement.selectionStart;
                currentSelectionEnd = inputElement.selectionEnd;
            } catch (e) {
                // ignore
            }

            inputElement.focus();
            inputElement.select();

            var successful = false;
            try {
                successful = document.execCommand('copy');
            } catch (e) {
                successful = false;
            }

            // restore selection if we saved it
            try {
                if (typeof currentSelectionStart === 'number' && typeof currentSelectionEnd === 'number') {
                    inputElement.setSelectionRange(currentSelectionStart, currentSelectionEnd);
                }
            } catch (e) {
                // ignore
            }

            if (successful) {
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }
            } else {
                if (typeof onFail === 'function') {
                    onFail();
                }
            }
        } catch (err) {
            if (typeof onFail === 'function') {
                onFail();
            }
        }
    }

    function handleGenerateClick(button) {
        try {
            setButtonWorkingState(button, true);

            var newKey = generateSecureHex(24);

            var input = document.getElementById('settings[cron_key]');
            if (input && typeof input.value !== 'undefined') {
                input.value = newKey;
            } else {
                // input not found; still generate and bail
                // restore button after short delay
                window.setTimeout(function () {
                    setButtonWorkingState(button, false);
                }, 600);
                return;
            }

            // Attempt to copy to clipboard, show check icon on success
            tryCopyToClipboard(newKey, input, function onSuccess() {
                // show check icon briefly
                try {
                    button.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
                } catch (e) {
                    // ignore
                }
                window.setTimeout(function () {
                    setButtonWorkingState(button, false);
                }, 700);
            }, function onFail() {
                // copy failed; show recycle icon briefly then restore
                try {
                    button.innerHTML = '<i class="fa fa-recycle fa-margin" aria-hidden="true"></i>';
                } catch (e) {
                    // ignore
                }
                window.setTimeout(function () {
                    setButtonWorkingState(button, false);
                }, 700);
            });
        } catch (err) {
            // ensure we restore button state
            window.setTimeout(function () {
                setButtonWorkingState(button, false);
            }, 700);
        }
    }

    function initGenerateCronKey() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGenerateCronKey);
            return;
        }

        var btn = document.getElementById('btn_generate_cron_key');
        if (!btn) {
            // nothing to wire up
            return;
        }

        // Avoid double-binding
        try {
            if (btn.__generateCronKeyBound) {
                return;
            }
            btn.__generateCronKeyBound = true;
        } catch (e) {
            // ignore
        }

        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            // do nothing if disabled
            if (btn.disabled) {
                return;
            }
            handleGenerateClick(btn);
        });
    }

    // Expose init for manual invocation if desired
    try {
        window.initGenerateCronKey = initGenerateCronKey;
    } catch (e) {
        // ignore if cannot attach
    }

    // Auto-init
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        initGenerateCronKey();
    } else {
        document.addEventListener('DOMContentLoaded', initGenerateCronKey);
    }
})();