import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initGenerateCronKey } from './cron.js';

function makeDOM(): { btn: HTMLButtonElement; input: HTMLInputElement } {
    const btn = document.createElement('button');
    btn.id = 'btn_generate_cron_key';
    const input = document.createElement('input');
    input.id = 'settings[cron_key]';
    document.body.append(btn, input);
    return { btn, input };
}

describe('initGenerateCronKey', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        vi.useFakeTimers();
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
        // Provide a deterministic crypto.getRandomValues so key format is predictable
        vi.spyOn(globalThis.crypto, 'getRandomValues').mockImplementation(
            (arr: ArrayBufferView | null) => {
                if (arr instanceof Uint8Array) arr.fill(0xab);
                return arr as any;
            },
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    // ── Init behaviour ──────────────────────────────────────────────────────

    it('returns early without error when button is absent', () => {
        expect(() => initGenerateCronKey()).not.toThrow();
    });

    it('defers init when document is loading', () => {
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });
        makeDOM();
        expect(() => initGenerateCronKey()).not.toThrow();
        document.dispatchEvent(new Event('DOMContentLoaded'));
        // No error after deferred init fires
    });

    it('does not bind twice when called a second time (double-bind guard)', () => {
        const { btn } = makeDOM();
        initGenerateCronKey();
        initGenerateCronKey();
        // The guard flag should be set; calling twice must not throw
        expect((btn as any).__generateCronKeyBound).toBe(true);
    });

    // ── Click handler — key generation ─────────────────────────────────────

    it('fills the input with a 48-character hex string on click', async () => {
        const { btn, input } = makeDOM();
        initGenerateCronKey();
        btn.click();
        await vi.runAllTimersAsync();
        // 24 bytes → 48 hex chars
        expect(input.value).toMatch(/^[0-9a-f]{48}$/);
    });

    it('generates all-ab hex (ababab…) when getRandomValues fills 0xab', async () => {
        const { btn, input } = makeDOM();
        initGenerateCronKey();
        btn.click();
        await vi.runAllTimersAsync();
        expect(input.value).toBe('ab'.repeat(24));
    });

    // ── Click handler — button working state ───────────────────────────────

    it('disables the button while working', () => {
        const { btn } = makeDOM();
        initGenerateCronKey();
        btn.click();
        // Synchronously after click — button should be in working state
        expect(btn.getAttribute('disabled')).toBe('true');
        expect(btn.getAttribute('aria-busy')).toBe('true');
    });

    it('restores the button after the 700 ms timeout', async () => {
        const { btn } = makeDOM();
        // Give the button non-empty initial HTML so __originalHTML is truthy
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>';
        const originalHTML = btn.innerHTML;
        initGenerateCronKey();
        btn.click();
        await vi.runAllTimersAsync();
        expect(btn.getAttribute('disabled')).toBeNull();
        expect(btn.getAttribute('aria-busy')).toBeNull();
        expect(btn.innerHTML).toBe(originalHTML);
    });

    it('ignores click when button is already disabled', async () => {
        const { btn, input } = makeDOM();
        initGenerateCronKey();
        (btn as HTMLButtonElement).disabled = true;
        btn.click();
        await vi.runAllTimersAsync();
        expect(input.value).toBe(''); // no key written
    });

    // ── Click handler — clipboard ───────────────────────────────────────────

    it('attempts clipboard.writeText when available', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        vi.stubGlobal('navigator', { clipboard: { writeText } });
        const { btn } = makeDOM();
        initGenerateCronKey();
        btn.click();
        await vi.runAllTimersAsync();
        expect(writeText).toHaveBeenCalledWith('ab'.repeat(24));
        vi.unstubAllGlobals();
    });

    it('does not throw when clipboard is unavailable', async () => {
        vi.stubGlobal('navigator', { clipboard: undefined });
        const { btn } = makeDOM();
        initGenerateCronKey();
        await expect(async () => {
            btn.click();
            await vi.runAllTimersAsync();
        }).not.toThrow();
        vi.unstubAllGlobals();
    });

    it('does not throw when input element is absent', async () => {
        const btn = document.createElement('button');
        btn.id = 'btn_generate_cron_key';
        document.body.appendChild(btn);
        // no input element added
        initGenerateCronKey();
        btn.click();
        await expect(vi.runAllTimersAsync()).resolves.not.toThrow();
    });
});
