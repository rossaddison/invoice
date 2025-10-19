(function () {
    "use strict";

    // Load email addresses into a target container via AJAX (fetch)
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.load-email-addresses');
        if (!trigger) return;

        const url = trigger.dataset.url || (location.origin + '/invoice/email/addresses');
        const targetId = trigger.dataset.target || 'email-addresses-container';
        const target = document.getElementById(targetId);
        if (!target) return;

        fetch(url, { cache: 'no-store' })
            .then(function (res) { return res.text(); })
            .then(function (html) { target.innerHTML = html; })
            .catch(function (err) { console.error('Failed to load email addresses', err); });
    });

})();
