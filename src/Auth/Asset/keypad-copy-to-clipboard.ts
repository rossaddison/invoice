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

function handleDigitInput(digitBtn: HTMLElement): void {
    const otp = document.getElementById('code') as HTMLInputElement | null;
    if (!otp) return;
    const digit = digitBtn.dataset['digit'];
    if (!digit) return;
    if (otp.value.length < 6) {
        otp.value += digit;
    }
}

function handleClearOtp(): void {
    const codeEl = document.getElementById('code') as HTMLInputElement | null;
    if (codeEl) codeEl.value = '';
}

document.addEventListener('click', (event: Event): void => {
    const target = event.target;
    if (closestSafe(target, '#toggleSecret')) { void handleToggleSecret(); return; }
    if (closestSafe(target, '#copySecret')) { void handleCopySecret(); return; }
    const digitBtn = closestSafe(target, '.btn-digit');
    if (digitBtn) { handleDigitInput(digitBtn); return; }
    if (closestSafe(target, '.btn-clear-otp')) { handleClearOtp(); }
}, true);
