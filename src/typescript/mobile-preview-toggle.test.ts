import { afterEach, describe, expect, it, vi } from 'vitest';
import { MobilePreviewToggle } from './mobile-preview-toggle.js';

/**
 * Deliberately light: most behavioral branches (activate/deactivate,
 * collapse/restore, the watchPopup interval, style-injection idempotency)
 * are already exercised in full through inv-index.test.ts, which drives
 * this exact class via initInvIndex(). This file mainly confirms the
 * class still works when instantiated directly, the way calendar.ts uses
 * it, plus the one branch not covered anywhere else (a blocked popup).
 */
describe('MobilePreviewToggle', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = '';
        document.head.innerHTML = '';
    });

    it('renders a toggle button and side tab, and injects mp-styles once', () => {
        new MobilePreviewToggle();

        expect(document.querySelector('.mp-btn')).not.toBeNull();
        expect(document.querySelector('.mp-side-tab')).not.toBeNull();
        expect(document.getElementById('mp-styles')).not.toBeNull();
    });

    it('opens a 390px-wide popup window on click', () => {
        const open = vi.spyOn(globalThis, 'open').mockReturnValue({ closed: false } as WindowProxy);
        new MobilePreviewToggle();

        (document.querySelector('.mp-btn') as HTMLButtonElement).click();

        expect(open).toHaveBeenCalledWith(
            globalThis.location.href,
            'mp-preview',
            expect.stringContaining('width=390'),
        );
    });

    it('leaves the button in its off state when the popup is blocked', () => {
        vi.spyOn(globalThis, 'open').mockReturnValue(null);
        new MobilePreviewToggle();

        const btn = document.querySelector('.mp-btn') as HTMLButtonElement;
        btn.click();

        expect(btn.classList.contains('mp-on')).toBe(false);
        expect(btn.querySelector('span')?.textContent).toBe('📱 Mobile Preview');
    });
});
