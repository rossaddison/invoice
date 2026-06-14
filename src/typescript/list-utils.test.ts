import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';

const CLS_SUCCESS = 'badge bg-success';
const CLS_WARNING = 'badge bg-warning';
const CLS_DANGER  = 'badge bg-danger';
const ATTR_INIT   = 'magnifierInitialized';

type GlobalExt = Record<string, unknown>;

// Creates a fresh container + badge in the body and returns both.
function makeBadge(cls: string, text: string, containerId: string): HTMLElement {
    const container = document.createElement('div');
    container.id = containerId;
    const badge = document.createElement('span');
    badge.className = cls;
    badge.style.fontSize = '14px';
    badge.textContent = text;
    container.appendChild(badge);
    document.body.appendChild(container);
    return badge;
}

describe('AmountMagnifier', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    describe('attachMagnifiers — isAmount gating', () => {
        it('marks a badge with a valid integer amount as initialized', () => {
            const badge = makeBadge(CLS_SUCCESS, '100', 'tbl-a');
            const _m = new AmountMagnifier('tbl-a');
            expect(badge.dataset[ATTR_INIT]).toBe('true');
        });

        it('marks a badge with a comma-separated decimal amount as initialized', () => {
            const badge = makeBadge(CLS_SUCCESS, '1,234.56', 'tbl-b');
            const _m = new AmountMagnifier('tbl-b');
            expect(badge.dataset[ATTR_INIT]).toBe('true');
        });

        it('skips a badge whose text is not a valid amount', () => {
            const badge = makeBadge(CLS_SUCCESS, 'not-a-number', 'tbl-c');
            const _m = new AmountMagnifier('tbl-c');
            expect(badge.dataset[ATTR_INIT]).toBeUndefined();
        });

        it('skips a badge with empty text (isAmount length === 0 guard)', () => {
            const badge = makeBadge(CLS_SUCCESS, '', 'tbl-d');
            const _m = new AmountMagnifier('tbl-d');
            expect(badge.dataset[ATTR_INIT]).toBeUndefined();
        });

        it('skips a badge with text longer than 20 characters', () => {
            const badge = makeBadge(CLS_SUCCESS, '1,234,567,890.1234567', 'tbl-e');
            const _m = new AmountMagnifier('tbl-e');
            expect(badge.dataset[ATTR_INIT]).toBeUndefined();
        });

        it('skips a badge that is already initialized', () => {
            const badge = makeBadge(CLS_SUCCESS, '100', 'tbl-f');
            badge.dataset[ATTR_INIT] = 'true';
            const cursorBefore = badge.style.cursor;
            const _m = new AmountMagnifier('tbl-f');
            expect(badge.style.cursor).toBe(cursorBefore); // addBehavior not called again
        });
    });

    function initBadge(cls: string, containerId: string): HTMLElement {
        const badge = makeBadge(cls, '100', containerId);
        const _m = new AmountMagnifier(containerId);
        return badge;
    }

    describe('addBehavior — event handlers', () => {
        it('mouseenter on a bg-success badge applies success colours', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-a');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            // jsdom normalises hex colours to rgb() on read-back
            expect(badge.style.border).toBe('2px solid rgb(40, 167, 69)');
            expect(badge.style.backgroundColor).toBe('rgb(212, 237, 218)');
        });

        it('mouseenter on a bg-warning badge applies warning colours', () => {
            const badge = initBadge(CLS_WARNING, 'evt-b');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            expect(badge.style.border).toBe('2px solid rgb(255, 193, 7)');
            expect(badge.style.backgroundColor).toBe('rgb(255, 243, 205)');
        });

        it('mouseenter on a bg-danger badge applies danger colours', () => {
            const badge = initBadge(CLS_DANGER, 'evt-c');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            expect(badge.style.border).toBe('2px solid rgb(220, 53, 69)');
            expect(badge.style.backgroundColor).toBe('rgb(248, 215, 218)');
        });

        it('mouseleave after mouseenter restores original styles', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-d');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            expect(badge.style.borderRadius).toBe('6px'); // set by magnify
            badge.dispatchEvent(new MouseEvent('mouseleave'));
            expect(badge.style.borderRadius).not.toBe('6px'); // restored
        });

        it('second mouseenter while already hovered is a no-op', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-e');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            const borderAfterFirst = badge.style.border;
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            expect(badge.style.border).toBe(borderAfterFirst);
        });

        it('mouseleave when not hovered is a no-op', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-f');
            badge.dispatchEvent(new MouseEvent('mouseleave'));
            expect(badge.style.border).toBe('');
        });

        it('click when not hovered magnifies the badge', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-g');
            badge.dispatchEvent(new MouseEvent('click'));
            expect(badge.style.borderRadius).toBe('6px'); // set by magnify
        });

        it('click when hovered restores the badge', () => {
            const badge = initBadge(CLS_SUCCESS, 'evt-h');
            badge.dispatchEvent(new MouseEvent('mouseenter'));
            expect(badge.style.borderRadius).toBe('6px'); // magnified
            badge.dispatchEvent(new MouseEvent('click'));
            expect(badge.style.borderRadius).not.toBe('6px'); // restored
        });
    });

    describe('setupObserver', () => {
        it('falls back to .table-responsive when tableId is not found', () => {
            const container = document.createElement('div');
            container.className = 'table-responsive';
            const badge = document.createElement('span');
            badge.className = 'badge bg-success';
            badge.style.fontSize = '14px';
            badge.textContent = '50';
            container.appendChild(badge);
            document.body.appendChild(container);

            const _m = new AmountMagnifier('nonexistent-id');
            expect(badge.dataset[ATTR_INIT]).toBe('true');
        });

        it('does not throw when no container is found at all', () => {
            expect(() => new AmountMagnifier('no-table-no-responsive')).not.toThrow();
        });

        it('re-runs attachMagnifiers on childList mutation', async () => {
            vi.useFakeTimers();
            try {
                const container = document.createElement('div');
                container.id = 'mut-table';
                document.body.appendChild(container);
                const _m = new AmountMagnifier('mut-table');

                // Add a valid badge after construction — should be caught by observer
                const badge = document.createElement('span');
                badge.className = 'badge bg-success';
                badge.style.fontSize = '14px';
                badge.textContent = '77';
                container.appendChild(badge);

                await Promise.resolve(); // flush MutationObserver microtask
                vi.advanceTimersByTime(100); // fire the scheduled setTimeout

                expect(badge.dataset[ATTR_INIT]).toBe('true');
            } finally {
                vi.useRealTimers();
            }
        });
    });
});

describe('initGroupBySelect', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        vi.stubGlobal('location', { href: 'http://localhost/' });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('returns early without error when no .group-by-select exists', () => {
        expect(() => initGroupBySelect()).not.toThrow();
    });

    it('change event with a valid value updates location.href', () => {
        document.body.innerHTML = `
            <select class="group-by-select" data-base-url="/quotes">
                <option value="none">None</option>
                <option value="status">Status</option>
            </select>`;
        initGroupBySelect();
        const sel = document.querySelector<HTMLSelectElement>('.group-by-select')!;
        sel.value = 'status';
        sel.dispatchEvent(new Event('change'));
        expect((globalThis.location as { href: string }).href).toBe('/quotes?groupBy=status');
    });

    it('change event with an invalid value does not update location.href', () => {
        document.body.innerHTML = `
            <select class="group-by-select" data-base-url="/quotes">
                <option value="bad-value">Bad</option>
            </select>`;
        initGroupBySelect();
        const sel = document.querySelector<HTMLSelectElement>('.group-by-select')!;
        sel.value = 'bad-value';
        sel.dispatchEvent(new Event('change'));
        expect((globalThis.location as { href: string }).href).toBe('http://localhost/');
    });

    it('uses empty base when data-base-url attribute is absent', () => {
        document.body.innerHTML = `
            <select class="group-by-select">
                <option value="client">Client</option>
            </select>`;
        initGroupBySelect();
        const sel = document.querySelector<HTMLSelectElement>('.group-by-select')!;
        sel.value = 'client';
        sel.dispatchEvent(new Event('change'));
        expect((globalThis.location as { href: string }).href).toBe('?groupBy=client');
    });
});

function makeGroupTable(chevronClass: string): { header: HTMLElement; row1: HTMLElement; row2: HTMLElement } {
    document.body.innerHTML = `
        <table>
            <tr class="group-header">
                <td><span class="group-toggle-icon ${chevronClass}"></span></td>
            </tr>
            <tr class="data-row"><td>Row 1</td></tr>
            <tr class="data-row"><td>Row 2</td></tr>
            <tr class="group-header"><td>Next group</td></tr>
        </table>`;
    const header = document.querySelector<HTMLElement>('.group-header')!;
    const rows   = document.querySelectorAll<HTMLElement>('.data-row');
    return { header, row1: rows[0], row2: rows[1] };
}

describe('initGroupCollapsible', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        initGroupCollapsible();
    });

    it('toggleGroupRows collapses expanded rows and switches icon to chevron-right', () => {
        const { header, row1, row2 } = makeGroupTable('bi-chevron-down');
        const toggle = (globalThis as GlobalExt)['toggleGroupRows'] as (h: HTMLElement) => void;
        toggle(header);
        expect(row1.style.display).toBe('none');
        expect(row2.style.display).toBe('none');
        const icon = header.querySelector('.group-toggle-icon')!;
        expect(icon.classList.contains('bi-chevron-right')).toBe(true);
        expect(icon.classList.contains('bi-chevron-down')).toBe(false);
    });

    it('toggleGroupRows expands collapsed rows and switches icon to chevron-down', () => {
        const { header, row1, row2 } = makeGroupTable('bi-chevron-right');
        const toggle = (globalThis as GlobalExt)['toggleGroupRows'] as (h: HTMLElement) => void;
        toggle(header);
        expect(row1.style.display).toBe('');
        expect(row2.style.display).toBe('');
        const icon = header.querySelector('.group-toggle-icon')!;
        expect(icon.classList.contains('bi-chevron-down')).toBe(true);
        expect(icon.classList.contains('bi-chevron-right')).toBe(false);
    });

    it('toggleGroupRows returns early without error when no toggle icon is present', () => {
        document.body.innerHTML = '<table><tr class="group-header"><td>No icon</td></tr><tr><td>Row</td></tr></table>';
        const header = document.querySelector<HTMLElement>('.group-header')!;
        const toggle = (globalThis as GlobalExt)['toggleGroupRows'] as (h: HTMLElement) => void;
        expect(() => toggle(header)).not.toThrow();
    });

    it('toggleAllGroups(true) expands all collapsed headers', () => {
        const { row1 } = makeGroupTable('bi-chevron-right');
        const toggleAll = (globalThis as GlobalExt)['toggleAllGroups'] as (e: boolean) => void;
        toggleAll(true);
        expect(row1.style.display).toBe('');
    });

    it('toggleAllGroups(false) collapses all expanded headers', () => {
        const { row1 } = makeGroupTable('bi-chevron-down');
        const toggleAll = (globalThis as GlobalExt)['toggleAllGroups'] as (e: boolean) => void;
        toggleAll(false);
        expect(row1.style.display).toBe('none');
    });

    it('toggleAllGroups(null) toggles every header regardless of state', () => {
        const { row1 } = makeGroupTable('bi-chevron-down');
        const toggleAll = (globalThis as GlobalExt)['toggleAllGroups'] as (e: null) => void;
        toggleAll(null);
        expect(row1.style.display).toBe('none');
    });
});
