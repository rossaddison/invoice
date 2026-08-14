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

/**
 * Six single-digit boxes mirroring into the real, FormModel-bound
 * #code input (kept in the DOM, just visually hidden via
 * .otp-hidden-field) — so validation, error display, and the existing
 * digit-pad buttons/appendDigit()/clear() all keep working against the
 * exact same field name the backend already expects, unchanged.
 *
 * A pasted value longer than the box count (an 8-character recovery
 * code, which is hex — 0-9A-F, not purely numeric, confirmed against
 * RecoveryCodeService::generateBackupCodes()) is written straight to
 * the hidden field instead of being split across boxes, since the boxes
 * are digit-only and can't represent it.
 */
class OtpBoxInput {
    private readonly boxes: HTMLInputElement[];

    constructor(
        container: HTMLElement,
        private readonly hiddenInput: HTMLInputElement,
    ) {
        this.boxes = Array.from(container.querySelectorAll<HTMLInputElement>('.otp-box'));
        this.boxes.forEach((box, index) => {
            box.addEventListener('input', () => { this.handleInput(index); });
            box.addEventListener('keydown', (e) => { this.handleKeydown(e, index); });
            box.addEventListener('paste', (e) => { this.handlePaste(e, index); });
        });
    }

    /** Used by the existing digit-pad buttons — fills the first empty box. */
    appendDigit(digit: string): void {
        const index = this.boxes.findIndex((box) => box.value === '');
        if (index === -1) return;
        const box = this.boxes[index];
        if (!box) return;
        box.value = digit;
        this.boxes[index + 1]?.focus();
        this.syncHidden();
    }

    clear(): void {
        this.boxes.forEach((box) => { box.value = ''; });
        this.hiddenInput.value = '';
        this.boxes[0]?.focus();
    }

    private handleInput(index: number): void {
        const box = this.boxes[index];
        if (!box) return;
        // Keep only the last digit typed — a box holds exactly one character.
        box.value = box.value.replace(/\D/g, '').slice(-1);
        if (box.value !== '') {
            this.boxes[index + 1]?.focus();
        }
        this.syncHidden();
    }

    private handleKeydown(e: KeyboardEvent, index: number): void {
        const box = this.boxes[index];
        if (e.key === 'Backspace' && box?.value === '' && index > 0) {
            this.boxes[index - 1]?.focus();
        } else if (e.key === 'ArrowLeft' && index > 0) {
            e.preventDefault();
            this.boxes[index - 1]?.focus();
        } else if (e.key === 'ArrowRight' && index < this.boxes.length - 1) {
            e.preventDefault();
            this.boxes[index + 1]?.focus();
        }
    }

    private handlePaste(e: ClipboardEvent, index: number): void {
        const pasted = e.clipboardData?.getData('text') ?? '';
        const digits = pasted.replace(/\D/g, '');
        if (digits === '') return;
        e.preventDefault();

        if (pasted.trim().length > this.boxes.length) {
            // Longer than the box count — most likely a pasted recovery
            // code (alphanumeric, not digit-only) rather than an OTP.
            // Boxes can't represent that; hand the raw pasted text
            // straight to the real field instead.
            this.boxes.forEach((box) => { box.value = ''; });
            this.hiddenInput.value = pasted.trim();
            return;
        }

        let lastFilled = index;
        digits.split('').forEach((digit, offset) => {
            const box = this.boxes[index + offset];
            if (box) {
                box.value = digit;
                lastFilled = index + offset;
            }
        });
        this.boxes[Math.min(lastFilled + 1, this.boxes.length - 1)]?.focus();
        this.syncHidden();
    }

    private syncHidden(): void {
        this.hiddenInput.value = this.boxes.map((box) => box.value).join('');
    }
}

// Module-level so the digit-pad buttons and the recovery-code toggle
// (both delegated document click handlers) can reach whichever OtpBoxInput
// is live on the current page — at most one of setup.php/verify.php's
// boxes exists per page load.
let otpBoxInput: OtpBoxInput | null = null;

function initOtpBoxes(): void {
    const container = document.getElementById('otp-boxes');
    const hiddenInput = document.getElementById('code') as HTMLInputElement | null;
    if (!container || !hiddenInput) return;
    otpBoxInput = new OtpBoxInput(container, hiddenInput);
}

function handleDigitInput(digitBtn: HTMLElement): void {
    const digit = digitBtn.dataset['digit'];
    if (!digit) return;

    if (otpBoxInput) {
        otpBoxInput.appendDigit(digit);
        return;
    }

    // No boxes on this page (or the recovery-code field is showing instead)
    // — fall back to appending directly, matching the pre-boxes behaviour.
    const otp = document.getElementById('code') as HTMLInputElement | null;
    if (otp && otp.value.length < 6) {
        otp.value += digit;
    }
}

function handleClearOtp(): void {
    if (otpBoxInput) {
        otpBoxInput.clear();
        return;
    }
    const codeEl = document.getElementById('code') as HTMLInputElement | null;
    if (codeEl) codeEl.value = '';
}

/**
 * verify.php only: swaps between the 6-box OTP view and the plain
 * recovery-code text field (the same real #code input either way —
 * only its visible presentation changes). Absent on setup.php, which
 * is 6-digit-only and has no recovery-code concept.
 */
function handleToggleRecoveryCode(): void {
    const boxesWrap = document.getElementById('otp-boxes-wrap');
    const codeField = document.getElementById('code') as HTMLInputElement | null;
    const toggleBtn = document.getElementById('toggle-recovery-code');
    if (!boxesWrap || !codeField || !toggleBtn) return;

    // The field's own <label for="code"> stays in the DOM (screen-reader
    // association), but is otp-hidden-field by default same as the input
    // itself — only shown, alongside the input, while the recovery-code
    // field is the one actually in use.
    const codeLabel = document.querySelector<HTMLElement>('label[for="code"]');

    const showingBoxes = !boxesWrap.classList.contains('d-none');
    codeField.value = '';
    otpBoxInput?.clear();

    if (showingBoxes) {
        boxesWrap.classList.add('d-none');
        codeField.classList.remove('otp-hidden-field');
        codeLabel?.classList.remove('otp-hidden-field');
        toggleBtn.textContent = toggleBtn.dataset['useCodeLabel'] ?? toggleBtn.textContent;
        codeField.focus();
    } else {
        boxesWrap.classList.remove('d-none');
        codeField.classList.add('otp-hidden-field');
        codeLabel?.classList.add('otp-hidden-field');
        toggleBtn.textContent = toggleBtn.dataset['useRecoveryLabel'] ?? toggleBtn.textContent;
        boxesWrap.querySelector<HTMLInputElement>('.otp-box')?.focus();
    }
}

document.addEventListener('click', (event: Event): void => {
    const target = event.target;
    if (closestSafe(target, '#toggleSecret')) { void handleToggleSecret(); return; }
    if (closestSafe(target, '#copySecret')) { void handleCopySecret(); return; }
    if (closestSafe(target, '#toggle-recovery-code')) { handleToggleRecoveryCode(); return; }
    const digitBtn = closestSafe(target, '.btn-digit');
    if (digitBtn) { handleDigitInput(digitBtn); return; }
    if (closestSafe(target, '.btn-clear-otp')) { handleClearOtp(); }
}, true);

document.addEventListener('DOMContentLoaded', initOtpBoxes);

// Fades out the "TFA enabled" login-page badge after 2 seconds. Extracted
// from src/Auth/Controller/AuthController.php's inline fadeOutJS
// (Html::script()) so script-src no longer needs 'unsafe-inline'.
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('tfa-badge');
    if (badge) {
        setTimeout(() => badge.classList.add('hidden'), 2000);
    }
});
