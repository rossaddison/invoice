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
});
