import { beforeEach, describe, expect, it } from 'vitest';
import { applyCodeMode, handleClearOtp, handleDigitInput } from './keypad-copy-to-clipboard.js';

// keypad-copy-to-clipboard.ts auto-registers 'click'/'change'/'DOMContentLoaded'
// listeners at import time -- harmless in jsdom, same reasoning as
// scripts.test.ts's own note about scripts.ts.

function makeVerifyPage(): { code: HTMLInputElement; digitPad: HTMLElement } {
    document.body.innerHTML = `
        <input id="code" type="tel" minlength="6" maxlength="6" size="6">
        <div id="digitPad">
            <button type="button" class="btn-digit" data-digit="1">1</button>
        </div>
        <input type="checkbox" id="backupCodeToggle">
    `;
    return {
        code: document.getElementById('code') as HTMLInputElement,
        digitPad: document.getElementById('digitPad') as HTMLElement,
    };
}

function digitButton(digit: string): HTMLElement {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-digit';
    btn.dataset['digit'] = digit;
    return btn;
}

describe('applyCodeMode', () => {
    beforeEach(() => { makeVerifyPage(); });

    it('defaults the field to a 6-digit numeric TOTP shape', () => {
        applyCodeMode(false);
        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.maxLength).toBe(6);
        expect(code.minLength).toBe(6);
        expect(code.size).toBe(6);
        expect(code.type).toBe('tel');
    });

    it('switches to an 8-character backup-code shape', () => {
        applyCodeMode(true);
        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.maxLength).toBe(8);
        expect(code.minLength).toBe(8);
        expect(code.size).toBe(8);
        // tel's numeric-only mobile keypad can't type a backup code's hex
        // letters (0-9A-F) -- text instead.
        expect(code.type).toBe('text');
    });

    it('clears whatever was already typed when switching modes', () => {
        const code = document.getElementById('code') as HTMLInputElement;
        code.value = '123456';
        applyCodeMode(true);
        expect(code.value).toBe('');
    });

    it('hides the digit pad in backup-code mode', () => {
        applyCodeMode(true);
        const digitPad = document.getElementById('digitPad') as HTMLElement;
        expect(digitPad.hidden).toBe(true);
    });

    it('shows the digit pad back in TOTP mode', () => {
        applyCodeMode(true);
        applyCodeMode(false);
        const digitPad = document.getElementById('digitPad') as HTMLElement;
        expect(digitPad.hidden).toBe(false);
    });

    it('does nothing (no throw) when #code is missing from the page', () => {
        document.body.innerHTML = '';
        expect(() => applyCodeMode(false)).not.toThrow();
    });
});

describe('handleDigitInput', () => {
    beforeEach(() => { makeVerifyPage(); });

    it('appends the clicked digit to #code', () => {
        handleDigitInput(digitButton('7'));
        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.value).toBe('7');
    });

    it('stops at maxLength (6) in the default TOTP mode', () => {
        const code = document.getElementById('code') as HTMLInputElement;
        code.value = '123456';
        handleDigitInput(digitButton('7'));
        // The exact bug this fixes: a hardcoded `< 6` would have behaved
        // the same here by coincidence, but only reading maxLength keeps
        // this correct once applyCodeMode(true) below raises it to 8.
        expect(code.value).toBe('123456');
    });

    it('allows up to maxLength (8) once switched to backup-code mode', () => {
        applyCodeMode(true);
        const code = document.getElementById('code') as HTMLInputElement;
        code.value = '1234567';
        handleDigitInput(digitButton('8'));
        expect(code.value).toBe('12345678');
    });

    it('stops at maxLength (8) in backup-code mode too', () => {
        applyCodeMode(true);
        const code = document.getElementById('code') as HTMLInputElement;
        code.value = '12345678';
        handleDigitInput(digitButton('9'));
        expect(code.value).toBe('12345678');
    });

    it('ignores a button with no data-digit', () => {
        const btn = document.createElement('button');
        handleDigitInput(btn);
        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.value).toBe('');
    });
});

describe('handleClearOtp', () => {
    beforeEach(() => { makeVerifyPage(); });

    it('empties #code', () => {
        const code = document.getElementById('code') as HTMLInputElement;
        code.value = '123456';
        handleClearOtp();
        expect(code.value).toBe('');
    });
});

describe('#backupCodeToggle change event wiring', () => {
    beforeEach(() => { makeVerifyPage(); });

    it('switches #code to backup-code mode when checked', () => {
        const toggle = document.getElementById('backupCodeToggle') as HTMLInputElement;
        toggle.checked = true;
        toggle.dispatchEvent(new Event('change', { bubbles: true }));

        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.maxLength).toBe(8);
        expect(code.type).toBe('text');
    });

    it('switches #code back to TOTP mode when unchecked', () => {
        const toggle = document.getElementById('backupCodeToggle') as HTMLInputElement;
        toggle.checked = true;
        toggle.dispatchEvent(new Event('change', { bubbles: true }));
        toggle.checked = false;
        toggle.dispatchEvent(new Event('change', { bubbles: true }));

        const code = document.getElementById('code') as HTMLInputElement;
        expect(code.maxLength).toBe(6);
        expect(code.type).toBe('tel');
    });

    it('ignores change events from unrelated elements', () => {
        const code = document.getElementById('code') as HTMLInputElement;
        code.maxLength = 6;
        code.dispatchEvent(new Event('change', { bubbles: true }));
        expect(code.maxLength).toBe(6);
    });
});
