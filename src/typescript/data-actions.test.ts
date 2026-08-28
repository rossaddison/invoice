import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initDataActions } from './data-actions.js';

type GlobalExt = Record<string, unknown>;

describe('initDataActions', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        initDataActions();
    });

    it('calls showPicker on the [data-action="show-picker"] element', () => {
        document.body.innerHTML = '<input type="date" data-action="show-picker">';
        const input = document.querySelector('input')!;
        const showPicker = vi.fn();
        (input as unknown as GlobalExt)['showPicker'] = showPicker;
        input.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(showPicker).toHaveBeenCalled();
    });

    it('calls window.close for data-action="window-close"', () => {
        document.body.innerHTML = '<button data-action="window-close">Close</button>';
        const spy = vi.spyOn(globalThis, 'close').mockImplementation(() => {});
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalled();
        spy.mockRestore();
    });

    it('calls window.print for data-action="window-print"', () => {
        document.body.innerHTML = '<button data-action="window-print">Print</button>';
        const spy = vi.spyOn(globalThis, 'print').mockImplementation(() => {});
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalled();
        spy.mockRestore();
    });

    it('calls toggleCommalistPicker for data-action="toggle-commalist-picker"', () => {
        document.body.innerHTML = '<button data-action="toggle-commalist-picker">Toggle</button>';
        const spy = vi.fn();
        (globalThis as GlobalExt)['toggleCommalistPicker'] = spy;
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalled();
    });

    it('calls history.back for data-action="history-back"', () => {
        document.body.innerHTML = '<button data-action="history-back">Back</button>';
        const spy = vi.spyOn(globalThis.history, 'back').mockImplementation(() => {});
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalled();
        spy.mockRestore();
    });

    it('calls toggleAllGroups(true) for data-action="toggle-all-groups" data-expand="true"', () => {
        document.body.innerHTML = '<button data-action="toggle-all-groups" data-expand="true">Expand</button>';
        const spy = vi.fn();
        (globalThis as GlobalExt)['toggleAllGroups'] = spy;
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalledWith(true);
    });

    it('calls toggleAllGroups(false) for data-action="toggle-all-groups" data-expand="false"', () => {
        document.body.innerHTML = '<button data-action="toggle-all-groups" data-expand="false">Collapse</button>';
        const spy = vi.fn();
        (globalThis as GlobalExt)['toggleAllGroups'] = spy;
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(spy).toHaveBeenCalledWith(false);
    });

    it('does not throw when toggleAllGroups is not defined on globalThis', () => {
        document.body.innerHTML = '<button data-action="toggle-all-groups" data-expand="true">Expand</button>';
        delete (globalThis as GlobalExt)['toggleAllGroups'];
        expect(() =>
            document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true })),
        ).not.toThrow();
    });

    it('toggles the d-none class on the data-target for data-action="toggle-panel"', () => {
        document.body.innerHTML =
            '<button data-action="toggle-panel" data-target="#panel">Toggle</button>' +
            '<div id="panel" class="d-none">Content</div>';
        const panel = document.getElementById('panel')!;
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(panel.classList.contains('d-none')).toBe(false);
        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(panel.classList.contains('d-none')).toBe(true);
    });

    it('does not throw for toggle-panel when data-target matches nothing', () => {
        document.body.innerHTML = '<button data-action="toggle-panel" data-target="#missing">Toggle</button>';
        expect(() =>
            document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true })),
        ).not.toThrow();
    });

    it('copies the data-copy-target element text via data-action="copy-to-clipboard"', async () => {
        document.body.innerHTML =
            '<pre id="cmd">apk update</pre>' +
            '<button data-action="copy-to-clipboard" data-copy-target="#cmd">Copy</button>';
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });

        const button = document.querySelector('button')!;
        button.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await Promise.resolve();
        await Promise.resolve();

        expect(writeText).toHaveBeenCalledWith('apk update');
        expect(button.textContent).toBe('Copied!');
    });

    it('does not throw for copy-to-clipboard when navigator.clipboard is unavailable', () => {
        document.body.innerHTML =
            '<pre id="cmd">apk update</pre>' +
            '<button data-action="copy-to-clipboard" data-copy-target="#cmd">Copy</button>';
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
        expect(() =>
            document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true })),
        ).not.toThrow();
    });

    it('copies an <input> value (not its empty textContent) via data-copy-target-id', async () => {
        document.body.innerHTML =
            '<input id="secret" value="sk_live_12345">' +
            '<button data-action="copy-to-clipboard" data-copy-target-id="secret" ' +
            'data-copied-label="Copied!"><i class="bi bi-clipboard"></i></button>';
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });

        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await Promise.resolve();
        await Promise.resolve();

        expect(writeText).toHaveBeenCalledWith('sk_live_12345');
    });

    it('flashes the icon and aria-label/title, then restores them, for an icon-only copy button', async () => {
        document.body.innerHTML =
            '<input id="secret" value="sk_live_12345">' +
            '<button data-action="copy-to-clipboard" data-copy-target-id="secret" ' +
            'data-copied-label="Copied!" aria-label="Copy to clipboard" title="Copy to clipboard">' +
            '<i class="bi bi-clipboard"></i></button>';
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });
        vi.useFakeTimers();

        const button = document.querySelector('button')!;
        const icon = document.querySelector('i')!;
        button.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await vi.advanceTimersByTimeAsync(0);

        expect(icon.className).toBe('bi bi-clipboard-check');
        expect(button.getAttribute('aria-label')).toBe('Copied!');
        expect(button.getAttribute('title')).toBe('Copied!');

        await vi.advanceTimersByTimeAsync(2000);

        expect(icon.className).toBe('bi bi-clipboard');
        expect(button.getAttribute('aria-label')).toBe('Copy to clipboard');
        expect(button.getAttribute('title')).toBe('Copy to clipboard');

        vi.useRealTimers();
    });

    it('resolves data-copy-target-id over data-copy-target when both are present', async () => {
        document.body.innerHTML =
            '<pre id="wrong">wrong value</pre>' +
            '<input id="right" value="right value">' +
            '<button data-action="copy-to-clipboard" data-copy-target="#wrong" ' +
            'data-copy-target-id="right">Copy</button>';
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });

        document.querySelector('button')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await Promise.resolve();
        await Promise.resolve();

        expect(writeText).toHaveBeenCalledWith('right value');
    });

    it('prevents default when data-confirm dialog is cancelled', () => {
        document.body.innerHTML = '<a href="/delete" data-confirm="Are you sure?">Delete</a>';
        vi.spyOn(globalThis, 'confirm').mockReturnValue(false);
        const event = new MouseEvent('click', { bubbles: true, cancelable: true });
        document.querySelector('a')!.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
    });

    it('does not prevent default when data-confirm dialog is accepted', () => {
        document.body.innerHTML = '<a href="/delete" data-confirm="Are you sure?">Delete</a>';
        vi.spyOn(globalThis, 'confirm').mockReturnValue(true);
        const event = new MouseEvent('click', { bubbles: true, cancelable: true });
        document.querySelector('a')!.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(false);
    });

    it('submits the form when a select.native-reset filter changes', () => {
        document.body.innerHTML = `
            <form id="filter-form">
                <select class="native-reset"><option value="a">A</option></select>
            </form>`;
        const form = document.getElementById('filter-form') as HTMLFormElement;
        const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});
        document.querySelector('select')!.dispatchEvent(new Event('change', { bubbles: true }));
        expect(submitSpy).toHaveBeenCalled();
    });

    it('does not submit for a select without the native-reset class', () => {
        document.body.innerHTML = `
            <form id="other-form">
                <select class="some-other-select"><option value="a">A</option></select>
            </form>`;
        const form = document.getElementById('other-form') as HTMLFormElement;
        const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});
        document.querySelector('select')!.dispatchEvent(new Event('change', { bubbles: true }));
        expect(submitSpy).not.toHaveBeenCalled();
    });

    it('checks every checkbox in the data-target when select-all is checked', () => {
        document.body.innerHTML = `
            <input type="checkbox" id="select-all" data-action="select-all" data-target="#group">
            <div id="group">
                <input type="checkbox" id="a">
                <input type="checkbox" id="b">
            </div>`;
        const selectAll = document.getElementById('select-all') as HTMLInputElement;
        selectAll.checked = true;
        selectAll.dispatchEvent(new Event('change', { bubbles: true }));
        expect((document.getElementById('a') as HTMLInputElement).checked).toBe(true);
        expect((document.getElementById('b') as HTMLInputElement).checked).toBe(true);
    });

    it('unchecks every checkbox in the data-target when select-all is unchecked', () => {
        document.body.innerHTML = `
            <input type="checkbox" id="select-all" data-action="select-all" data-target="#group">
            <div id="group">
                <input type="checkbox" id="a" checked>
                <input type="checkbox" id="b" checked>
            </div>`;
        const selectAll = document.getElementById('select-all') as HTMLInputElement;
        selectAll.checked = false;
        selectAll.dispatchEvent(new Event('change', { bubbles: true }));
        expect((document.getElementById('a') as HTMLInputElement).checked).toBe(false);
        expect((document.getElementById('b') as HTMLInputElement).checked).toBe(false);
    });

    it('does not throw for select-all when data-target matches nothing', () => {
        document.body.innerHTML =
            '<input type="checkbox" id="select-all" data-action="select-all" data-target="#missing">';
        const selectAll = document.getElementById('select-all') as HTMLInputElement;
        selectAll.checked = true;
        expect(() => selectAll.dispatchEvent(new Event('change', { bubbles: true }))).not.toThrow();
    });
});
