// Auth page keypad: OTP digit input, secret visibility toggle, clipboard copy.
// Compiled to src/Auth/Asset/rebuild/js/keypad_copy_to_clipboard.js by build:typescript:auth.

interface ToggleSecretResponse {
    success?: number;
    secretInputType?: string;
    eyeIconClass?: string;
}

const TOGGLE_SECRET_PATH = '/ajaxShowSetup';

function isToggleResponse(val: unknown): val is ToggleSecretResponse {
    return val !== null && typeof val === 'object';
}

function parseToggleResponse(text: string): ToggleSecretResponse {
    try {
        const obj: unknown = JSON.parse(text);
        return isToggleResponse(obj) ? obj : {};
    } catch (e) {
        console.warn('JSON parse error in toggle response:', e);
        return {};
    }
}

function closestSafe(el: EventTarget | null, selector: string): HTMLElement | null {
    if (!(el instanceof Element)) return null;
    return el.closest<HTMLElement>(selector);
}

async function handleToggleSecret(): Promise<void> {
    const secretInput = document.getElementById('secretInput') as HTMLInputElement | null;
    const eyeIcon = document.getElementById('eyeIcon');
    if (!secretInput) return;

    const newType = secretInput.type === 'password' ? 'text' : 'password';

    if (eyeIcon) {
        eyeIcon.classList.toggle('bi-eye', newType !== 'text');
        eyeIcon.classList.toggle('bi-eye-slash', newType === 'text');
    }

    const params = new URLSearchParams({
        inputType: newType,
        eyeIconClass: eyeIcon?.className ?? '',
    });

    try {
        const res = await fetch(
            `${location.origin}${TOGGLE_SECRET_PATH}?${params.toString()}`,
            { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } }
        );
        if (!res.ok) throw new Error(`Server error: ${res.status}`);
        const data = parseToggleResponse(await res.text());
        if (data.success === 1) {
            if (data.secretInputType) secretInput.type = data.secretInputType;
            if (eyeIcon && data.eyeIconClass) eyeIcon.className = data.eyeIconClass;
        }
    } catch (e) {
        console.error('toggleSecret request failed:', e);
    }
}

async function handleCopySecret(): Promise<void> {
    const secretEl = document.getElementById('secretInput') as HTMLInputElement | null;
    if (!secretEl) return;

    const originalType = secretEl.type;
    secretEl.type = 'text';
    const valueToCopy = secretEl.value || secretEl.textContent || '';

    try {
        await navigator.clipboard.writeText(valueToCopy);
    } catch (e) {
        console.warn('Clipboard write failed:', e);
    } finally {
        secretEl.type = originalType || 'password';
    }
}

export function handleDigitInput(digitBtn: HTMLElement): void {
    const otp = document.getElementById('code') as HTMLInputElement | null;
    if (!otp) return;
    const digit = digitBtn.dataset['digit'];
    if (!digit) return;
    // otp.maxLength, not a hardcoded 6 -- respects whichever mode
    // applyCodeMode() below currently has the field in (a TOTP code is 6
    // digits, a backup recovery code is 8 alphanumeric characters; see
    // AuthTfaHelper::sanitizeAndValidateCode() for the same two lengths
    // enforced server-side).
    if (otp.value.length < otp.maxLength) {
        otp.value += digit;
    }
}

export function handleClearOtp(): void {
    const codeEl = document.getElementById('code') as HTMLInputElement | null;
    if (codeEl) codeEl.value = '';
}

/**
 * Switches the #code field between its two valid shapes -- a 6-digit
 * numeric TOTP code (the default) and an 8-character alphanumeric backup
 * recovery code -- via the "Use a backup recovery code instead" checkbox
 * (#backupCodeToggle) on the TFA verify page. Without this, the field's
 * maxlength stayed fixed at 8 for both cases, meaning nothing stopped a
 * TOTP code being over-typed to 8 digits even though the server (see
 * AuthTfaHelper::sanitizeAndValidateCode()) only ever accepts exactly 6 or
 * exactly 8, never anything in between.
 *
 * The digit pad (#digitPad) is hidden in backup-code mode: it only ever
 * enters 0-9, which can't produce the letters a real backup code's hex
 * alphabet (0-9A-F) needs, so leaving it visible there would invite a
 * doomed attempt to click one out.
 */
export function applyCodeMode(usingBackupCode: boolean): void {
    const otp = document.getElementById('code') as HTMLInputElement | null;
    const digitPad = document.getElementById('digitPad');
    if (!otp) return;

    const length = usingBackupCode ? 8 : 6;
    // minLength must never sit above the current maxLength even for an
    // instant -- the browser throws (aborting the rest of this function,
    // silently leaving type/size/value untouched too) if it does. Since
    // minLength always equals maxLength here, dropping minLength to 0
    // first makes every assignment order safe, whichever direction
    // length is moving. jsdom (this file's own vitest suite) does NOT
    // enforce this and let the original, wrong `minLength = length;
    // maxLength = length;` order pass all 15 tests -- only caught live,
    // via a real Chromium/Playwright run against the actual compiled
    // output, which is what actually threw
    // "The minLength provided (8) is greater than the maximum bound (6)".
    otp.minLength = 0;
    otp.maxLength = length;
    otp.minLength = length;
    otp.size = length;
    // tel brings up a numeric-only keypad on mobile -- wrong for a backup
    // code's hex letters, so text in that mode instead.
    otp.type = usingBackupCode ? 'text' : 'tel';
    otp.value = '';

    if (digitPad) {
        digitPad.hidden = usingBackupCode;
    }
}

document.addEventListener('click', (event: Event): void => {
    const target = event.target;
    if (closestSafe(target, '#toggleSecret')) { void handleToggleSecret(); return; }
    if (closestSafe(target, '#copySecret')) { void handleCopySecret(); return; }
    const digitBtn = closestSafe(target, '.btn-digit');
    if (digitBtn) { handleDigitInput(digitBtn); return; }
    if (closestSafe(target, '.btn-clear-otp')) { handleClearOtp(); }
}, true);

document.addEventListener('change', (event: Event): void => {
    const target = event.target;
    if (target instanceof HTMLInputElement && target.id === 'backupCodeToggle') {
        applyCodeMode(target.checked);
    }
});

// Fades out the "TFA enabled" login-page badge after 2 seconds. Extracted
// from src/Auth/Controller/AuthController.php's inline fadeOutJS
// (Html::script()) so script-src no longer needs 'unsafe-inline'.
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('tfa-badge');
    if (badge) {
        setTimeout(() => badge.classList.add('hidden'), 2000);
    }
});
