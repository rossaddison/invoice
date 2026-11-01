import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getSelectedDates, initMultiDatePicker } from './multi-date-picker.js';

const ID = 'modal_created_dates_calendar';

function dayButton(iso: string): HTMLElement {
    return document.querySelector<HTMLElement>(`[data-mdp-date="${iso}"]`)!;
}

describe('initMultiDatePicker', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2026, 10, 15)); // 15 Nov 2026
        document.body.innerHTML =
            `<div id="${ID}" data-summary-id="summary"></div><small id="summary"></small>`;
    });

    it('does nothing when the container is absent', () => {
        document.body.innerHTML = '';
        expect(() => initMultiDatePicker()).not.toThrow();
    });

    it('renders the current month with one button per day', () => {
        initMultiDatePicker();
        expect(document.getElementById(ID)!.textContent).toContain('November 2026');
        expect(document.querySelectorAll('[data-mdp-date]')).toHaveLength(30);
    });

    it('toggles days on and off and keeps them sorted', () => {
        initMultiDatePicker();
        dayButton('2026-11-20').click();
        dayButton('2026-11-03').click();
        const container = document.getElementById(ID)!;
        expect(getSelectedDates(container)).toEqual(['2026-11-03', '2026-11-20']);
        expect(dayButton('2026-11-03').getAttribute('aria-pressed')).toBe('true');

        dayButton('2026-11-03').click();
        expect(getSelectedDates(container)).toEqual(['2026-11-20']);
    });

    it('keeps selections across months', () => {
        initMultiDatePicker();
        dayButton('2026-11-30').click();
        document.querySelector<HTMLElement>('[data-mdp-nav="1"]')!.click();
        expect(document.getElementById(ID)!.textContent).toContain('December 2026');
        dayButton('2026-12-01').click();
        expect(getSelectedDates(document.getElementById(ID)!)).toEqual(['2026-11-30', '2026-12-01']);
    });

    it('wraps from January back to December of the previous year', () => {
        initMultiDatePicker();
        for (let i = 0; i < 11; i++) {
            document.querySelector<HTMLElement>('[data-mdp-nav="-1"]')!.click();
        }
        expect(document.getElementById(ID)!.textContent).toContain('December 2025');
    });

    it('writes the count and dates to the summary element', () => {
        initMultiDatePicker();
        dayButton('2026-11-03').click();
        dayButton('2026-11-04').click();
        expect(document.getElementById('summary')!.textContent).toBe('2: 2026-11-03, 2026-11-04');
    });

    it('does not attach duplicate listeners when initialised twice', () => {
        initMultiDatePicker();
        initMultiDatePicker();
        dayButton('2026-11-03').click();
        expect(getSelectedDates(document.getElementById(ID)!)).toEqual(['2026-11-03']);
    });
});
