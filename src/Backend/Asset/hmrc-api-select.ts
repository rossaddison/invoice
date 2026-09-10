// backend/hmrc "Select API to exercise" control: sets the select's own
// <form>'s action from the chosen option's data-route and enables/disables
// the Go button accordingly, then navigates there on click.
//
// Extracted from inline onchange/onclick attributes -- this app's CSP is
// script-src 'self' with no 'unsafe-inline', so those never actually ran;
// confirmed live 2026-09-10 (the Go button stayed disabled no matter what
// was picked). Delegated listeners instead, matching
// keypad-copy-to-clipboard.ts's own precedent for the same CSP reason.
// Compiled to src/Backend/Asset/rebuild/js/hmrc-api-select-iife.js by
// build:typescript:backend.

function updateApiSelectState(select: HTMLSelectElement): void {
    const form = document.getElementById('api-select-form') as HTMLFormElement | null;
    const goBtn = document.getElementById('btn-go') as HTMLButtonElement | null;
    if (!form || !goBtn) return;

    const selectedOption = select.options[select.selectedIndex] as HTMLOptionElement | undefined;
    const route = selectedOption?.dataset['route'] ?? '';

    form.action = route || '#';
    goBtn.disabled = route === '';
}

document.addEventListener('change', (event: Event): void => {
    const target = event.target;
    if (target instanceof HTMLSelectElement && target.id === 'api-context') {
        updateApiSelectState(target);
    }
});

document.addEventListener('click', (event: Event): void => {
    const target = event.target;
    if (!(target instanceof Element) || !target.closest('#btn-go')) return;

    const form = document.getElementById('api-select-form') as HTMLFormElement | null;
    if (form && form.action && form.action !== location.href) {
        location.href = form.action;
    }
});
