import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    hideFullpageLoader,
    initPasswordMeter,
    initSimpleSelects,
    initTooltips,
    showFullpageLoader,
} from './scripts.js';

// scripts.ts auto-calls initializeScripts() at import time which registers a
// DOMContentLoaded listener — harmless in jsdom since readyState is 'complete'.

function makeLoader(): { loader: HTMLElement; error: HTMLElement; icon: HTMLElement } {
    const loader = document.createElement('div');
    loader.id = 'fullpage-loader';
    const error = document.createElement('div');
    error.id = 'loader-error';
    const icon = document.createElement('i');
    icon.id = 'loader-icon';
    document.body.append(loader, error, icon);
    return { loader, error, icon };
}

// ─── showFullpageLoader / hideFullpageLoader ───────────────────────────────

describe('showFullpageLoader', () => {
    beforeEach(() => { document.body.innerHTML = ''; vi.useFakeTimers(); });
    afterEach(() => { vi.useRealTimers(); });

    it('shows the loader element', () => {
        const { loader } = makeLoader();
        showFullpageLoader();
        expect(loader.style.display).toBe('block');
    });

    it('hides the error element', () => {
        const { error } = makeLoader();
        showFullpageLoader();
        expect(error.style.display).toBe('none');
    });

    it('adds icon-spin and removes text-danger on the icon', () => {
        const { icon } = makeLoader();
        icon.classList.add('text-danger');
        showFullpageLoader();
        expect(icon.classList.contains('icon-spin')).toBe(true);
        expect(icon.classList.contains('text-danger')).toBe(false);
    });

    it('reveals error element after 10 s timeout', () => {
        const { error } = makeLoader();
        showFullpageLoader();
        expect(error.style.display).toBe('none');
        vi.advanceTimersByTime(10_000);
        expect(error.style.display).toBe('block');
    });

    it('switches icon to text-danger and removes icon-spin after timeout', () => {
        const { icon } = makeLoader();
        showFullpageLoader();
        vi.advanceTimersByTime(10_000);
        expect(icon.classList.contains('text-danger')).toBe(true);
        expect(icon.classList.contains('icon-spin')).toBe(false);
    });

    it('does not throw when loader elements are absent', () => {
        expect(() => showFullpageLoader()).not.toThrow();
    });
});

describe('hideFullpageLoader', () => {
    beforeEach(() => { document.body.innerHTML = ''; });

    it('hides the loader element', () => {
        const { loader } = makeLoader();
        loader.style.display = 'block';
        hideFullpageLoader();
        expect(loader.style.display).toBe('none');
    });

    it('hides the error element', () => {
        const { error } = makeLoader();
        error.style.display = 'block';
        hideFullpageLoader();
        expect(error.style.display).toBe('none');
    });

    it('adds icon-spin on the icon', () => {
        const { icon } = makeLoader();
        hideFullpageLoader();
        expect(icon.classList.contains('icon-spin')).toBe(true);
    });

    it('does not throw when loader elements are absent', () => {
        expect(() => hideFullpageLoader()).not.toThrow();
    });
});

// ─── initTooltips ──────────────────────────────────────────────────────────

describe('initTooltips', () => {
    afterEach(() => { vi.unstubAllGlobals(); document.body.innerHTML = ''; });

    it('returns early without error when bootstrap is not on globalThis', () => {
        vi.stubGlobal('bootstrap', undefined);
        expect(() => initTooltips()).not.toThrow();
    });

    it('returns early when bootstrap.Tooltip is missing', () => {
        vi.stubGlobal('bootstrap', {});
        expect(() => initTooltips()).not.toThrow();
    });

    it('calls getOrCreateInstance for each [data-bs-toggle="tooltip"] element', () => {
        const getOrCreateInstance = vi.fn();
        vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance } });
        document.body.innerHTML = `
            <button data-bs-toggle="tooltip" title="A"></button>
            <span data-bs-toggle="tooltip" title="B"></span>`;
        initTooltips();
        expect(getOrCreateInstance).toHaveBeenCalledTimes(2);
    });

    it('ignores exceptions thrown by getOrCreateInstance', () => {
        const getOrCreateInstance = vi.fn().mockImplementation(() => { throw new Error('fail'); });
        vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance } });
        document.body.innerHTML = '<button data-bs-toggle="tooltip" title="X"></button>';
        expect(() => initTooltips()).not.toThrow();
    });
});

// ─── initSimpleSelects ─────────────────────────────────────────────────────

describe('initSimpleSelects', () => {
    afterEach(() => { vi.unstubAllGlobals(); document.body.innerHTML = ''; });

    it('returns early without error when TomSelect is not defined', () => {
        vi.stubGlobal('TomSelect', undefined);
        document.body.innerHTML = '<select class="simple-select"></select>';
        expect(() => initSimpleSelects()).not.toThrow();
    });

    it('instantiates TomSelect for each .simple-select element', () => {
        const TomSelectMock = vi.fn();
        vi.stubGlobal('TomSelect', TomSelectMock);
        document.body.innerHTML = `
            <select class="simple-select"></select>
            <select class="simple-select"></select>`;
        initSimpleSelects();
        expect(TomSelectMock).toHaveBeenCalledTimes(2);
    });

    it('skips elements already initialised (_tomselect flag)', () => {
        const TomSelectMock = vi.fn();
        vi.stubGlobal('TomSelect', TomSelectMock);
        const sel = document.createElement('select');
        sel.className = 'simple-select';
        (sel as any)._tomselect = true;
        document.body.appendChild(sel);
        initSimpleSelects();
        expect(TomSelectMock).not.toHaveBeenCalled();
    });

    it('accepts a root element to scope the query', () => {
        const TomSelectMock = vi.fn();
        vi.stubGlobal('TomSelect', TomSelectMock);
        const root = document.createElement('div');
        const sel = document.createElement('select');
        sel.className = 'simple-select';
        root.appendChild(sel);
        // sel is NOT in document.body — root scoping should still find it
        initSimpleSelects(root);
        expect(TomSelectMock).toHaveBeenCalledTimes(1);
    });
});

// ─── initPasswordMeter ─────────────────────────────────────────────────────

describe('initPasswordMeter', () => {
    beforeEach(() => { document.body.innerHTML = ''; });

    function makePasswordDOM(): {
        input: HTMLInputElement;
        m2: HTMLElement;
        m3: HTMLElement;
    } {
        document.body.innerHTML = `
            <input class="passwordmeter-input" type="password">
            <div class="passmeter-2"></div>
            <div class="passmeter-3"></div>`;
        return {
            input: document.querySelector('.passwordmeter-input') as HTMLInputElement,
            m2: document.querySelector('.passmeter-2') as HTMLElement,
            m3: document.querySelector('.passmeter-3') as HTMLElement,
        };
    }

    it('returns early without error when no .passwordmeter-input exists', () => {
        expect(() => initPasswordMeter()).not.toThrow();
    });

    it('shows both meters for a strong password (strength ≥ 4)', () => {
        const { input, m2, m3 } = makePasswordDOM();
        initPasswordMeter();
        input.value = 'Abcdef1!';     // length + lower + upper + digit + special = 5
        input.dispatchEvent(new Event('input'));
        expect(m2.style.display).toBe('block');
        expect(m3.style.display).toBe('block');
    });

    it('shows only meter-2 for a medium password (strength === 3)', () => {
        const { input, m2, m3 } = makePasswordDOM();
        initPasswordMeter();
        input.value = 'Abcdef1';      // length + lower + upper + digit = 4? Let me count: >=8 + lower + upper + digit = 4
        // To get exactly 3: length(<8) + lower + upper + digit (no special)
        input.value = 'Abc1';         // len=4(<8→0) + lower(1) + upper(1) + digit(1) = 3
        input.dispatchEvent(new Event('input'));
        expect(m2.style.display).toBe('block');
        expect(m3.style.display).toBe('none');
    });

    it('hides both meters for a weak password (strength < 3)', () => {
        const { input, m2, m3 } = makePasswordDOM();
        initPasswordMeter();
        input.value = 'abc';          // lower only = 1
        input.dispatchEvent(new Event('input'));
        expect(m2.style.display).toBe('none');
        expect(m3.style.display).toBe('none');
    });

    it('does not throw when meter elements are absent', () => {
        document.body.innerHTML = '<input class="passwordmeter-input" type="password">';
        const input = document.querySelector('.passwordmeter-input') as HTMLInputElement;
        initPasswordMeter();
        input.value = 'Password1!';
        expect(() => input.dispatchEvent(new Event('input'))).not.toThrow();
    });
});
