/**
 * Converted from jQuery to vanilla JavaScript
 * jQuery has been removed from this project
 */

document.addEventListener('DOMContentLoaded', function () {
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
    document.addEventListener('click', function (e) {
        if (e.target.id === 'toggleSecret' || e.target.closest('#toggleSecret')) {
            const secretInput = document.getElementById('secretInput');
            const eyeIcon = document.getElementById('eyeIcon');
            let secretInputType = '';
            let eyeIconClass = '';
            
            if (secretInput.getAttribute('type') === 'password') {
                secretInput.setAttribute('type', 'text');
                secretInputType = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
                eyeIconClass = eyeIcon.getAttribute('class');
            } else {
                secretInput.setAttribute('type', 'password');
                secretInputType = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
                eyeIconClass = eyeIcon.getAttribute('class');
            }
            
            const url = window.location.origin + "/ajaxShowSetup";
            const params = new URLSearchParams({
                secretInputType: secretInputType,
                eyeIconClass: eyeIconClass
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);           
                if (response.success === 1) {                           
                    secretInput.setAttribute('type', response.secretInputType);
                    eyeIcon.setAttribute('class', response.eyeIconClass);
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
            });
        }
    });

    // Copy Secret
    document.addEventListener('click', function (e) {
        if (e.target.id === 'copySecret' || e.target.closest('#copySecret')) {
            const secretInput = document.getElementById('secretInput');
            
            // Use modern Clipboard API if available, fallback to execCommand
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(secretInput.value)
                    .then(() => {
                        console.log('Secret copied to clipboard');
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                        // Fallback to execCommand
                        fallbackCopySecret(secretInput);
                    });
            } else {
                // Fallback for older browsers
                fallbackCopySecret(secretInput);
            }
        }
    });
    
    function fallbackCopySecret(secretInput) {
        const originalType = secretInput.getAttribute('type');
        secretInput.setAttribute('type', 'text'); // Temporarily show text to copy hidden value
        secretInput.select();
        document.execCommand('copy');
        secretInput.setAttribute('type', originalType); // Restore original type
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-digit')) {
            const otp = document.getElementById('code');
            const digit = e.target.getAttribute('data-digit');
            if (otp && otp.value.length < 6) {
                otp.value = otp.value + digit;
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-clear-otp')) {
            const codeInput = document.getElementById('code');
            if (codeInput) {
                codeInput.value = '';
            }
        }
    });
});