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

/** 'digit' for the 6-box OTP group, 'hex' for the 8-box recovery-code group. */
type BoxCharset = 'digit' | 'hex';

function sanitizeForCharset(raw: string, charset: BoxCharset): string {
    return charset === 'digit'
        ? raw.replace(/\D/g, '')
        : raw.replace(/[^0-9a-fA-F]/g, '').toUpperCase();
}

/**
 * A row of single-character boxes mirroring into the real, FormModel-bound
 * #code input (kept in the DOM, permanently visually hidden via
 * .otp-hidden-field) — so validation, error display, and submission all
 * keep working against the exact same field name the backend already
 * expects, unchanged. verify.php mounts two of these sharing one hidden
 * input — 6 digit-only boxes for the common OTP case, 8 hex-character
 * boxes (0-9A-F, confirmed against
 * RecoveryCodeService::generateBackupCodes()) for backup recovery codes
 * — and shows exactly one at a time via handleToggleRecoveryCode().
 * setup.php mounts only the digit-only one.
 */
class OtpBoxInput {
    private readonly boxes: HTMLInputElement[];

    constructor(
        container: HTMLElement,
        private readonly hiddenInput: HTMLInputElement,
        private readonly charset: BoxCharset = 'digit',
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
        box.value = sanitizeForCharset(digit, this.charset).slice(-1);
        this.boxes[index + 1]?.focus();
        this.syncHidden();
    }

    clear(): void {
        this.boxes.forEach((box) => { box.value = ''; });
        this.syncHidden();
    }

    focusFirst(): void {
        this.boxes[0]?.focus();
    }

    private handleInput(index: number): void {
        const box = this.boxes[index];
        if (!box) return;
        // Keep only the last character typed — a box holds exactly one.
        box.value = sanitizeForCharset(box.value, this.charset).slice(-1);
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
        const cleaned = sanitizeForCharset(pasted, this.charset);
        if (cleaned === '') return;
        e.preventDefault();

        let lastFilled = index;
        cleaned.split('').forEach((char, offset) => {
            const box = this.boxes[index + offset];
            if (box) {
                box.value = char;
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
// (both delegated document click handlers) can reach whichever group(s)
// are live on the current page — setup.php only ever has otpBoxInput;
// verify.php has both, with getActiveBoxInput() picking whichever isn't
// currently hidden.
let otpBoxInput: OtpBoxInput | null = null;
let recoveryBoxInput: OtpBoxInput | null = null;

function initOtpBoxes(): void {
    const hiddenInput = document.getElementById('code') as HTMLInputElement | null;
    if (!hiddenInput) return;

    const otpContainer = document.getElementById('otp-boxes');
    if (otpContainer) otpBoxInput = new OtpBoxInput(otpContainer, hiddenInput, 'digit');

    const recoveryContainer = document.getElementById('recovery-boxes');
    if (recoveryContainer) recoveryBoxInput = new OtpBoxInput(recoveryContainer, hiddenInput, 'hex');
}

function getActiveBoxInput(): OtpBoxInput | null {
    const recoveryWrap = document.getElementById('recovery-boxes-wrap');
    if (recoveryWrap && !recoveryWrap.classList.contains('d-none')) return recoveryBoxInput;
    return otpBoxInput;
}

function handleDigitInput(digitBtn: HTMLElement): void {
    const digit = digitBtn.dataset['digit'];
    if (!digit) return;

    const active = getActiveBoxInput();
    if (active) {
        active.appendDigit(digit);
        return;
    }

    // No boxes on this page — fall back to appending directly, matching
    // the pre-boxes behaviour.
    const otp = document.getElementById('code') as HTMLInputElement | null;
    if (otp && otp.value.length < 6) {
        otp.value += digit;
    }
}

function handleClearOtp(): void {
    const active = getActiveBoxInput();
    if (active) {
        active.clear();
        return;
    }
    const codeEl = document.getElementById('code') as HTMLInputElement | null;
    if (codeEl) codeEl.value = '';
}

/**
 * verify.php only: swaps which box group is shown — the 6-digit OTP boxes
 * by default, or the 8-character recovery-code boxes. Both write into the
 * same real #code input either way; only which group is visible changes.
 * Absent on setup.php, which is 6-digit-only and has no recovery-code
 * concept.
 */
function handleToggleRecoveryCode(): void {
    const otpWrap = document.getElementById('otp-boxes-wrap');
    const recoveryWrap = document.getElementById('recovery-boxes-wrap');
    const codeField = document.getElementById('code') as HTMLInputElement | null;
    const toggleBtn = document.getElementById('toggle-recovery-code');
    const header = document.getElementById('otp-header');
    if (!otpWrap || !recoveryWrap || !codeField || !toggleBtn) return;

    const showingOtp = !otpWrap.classList.contains('d-none');
    codeField.value = '';
    otpBoxInput?.clear();
    recoveryBoxInput?.clear();

    if (showingOtp) {
        otpWrap.classList.add('d-none');
        recoveryWrap.classList.remove('d-none');
        toggleBtn.textContent = toggleBtn.dataset['useCodeLabel'] ?? toggleBtn.textContent;
        if (header) header.textContent = header.dataset['recoveryHeader'] ?? header.textContent;
        recoveryBoxInput?.focusFirst();
    } else {
        recoveryWrap.classList.add('d-none');
        otpWrap.classList.remove('d-none');
        toggleBtn.textContent = toggleBtn.dataset['useRecoveryLabel'] ?? toggleBtn.textContent;
        if (header) header.textContent = header.dataset['otpHeader'] ?? header.textContent;
        otpBoxInput?.focusFirst();
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
