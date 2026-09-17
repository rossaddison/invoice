import { afterEach, describe, expect, it } from 'vitest';
import { initNavFlyoutSubmenu } from './nav-flyout-submenu.js';

function buildMenu(): void {
    document.body.innerHTML = `
        <ul class="dropdown-menu">
            <li class="dropdown-submenu">
                <span class="dropdown-item dropdown-submenu-toggle" tabindex="0">Company</span>
                <ul class="dropdown-menu dropdown-menu-submenu">
                    <li><a class="dropdown-item" href="/company">Company</a></li>
                </ul>
            </li>
            <li class="dropdown-submenu">
                <span class="dropdown-item dropdown-submenu-toggle" tabindex="0">Email</span>
                <ul class="dropdown-menu dropdown-menu-submenu">
                    <li><a class="dropdown-item" href="/email">Email Template</a></li>
                </ul>
            </li>
        </ul>
        <button id="outside-element">Outside</button>`;
}

function click(element: Element): void {
    element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
}

describe('initNavFlyoutSubmenu', () => {
    // Registers one document-level click/keydown listener pair for this
    // whole suite -- matching how it is really used (once per page load).
    // Calling it again per-test would stack duplicate listeners on the same
    // never-reset `document`, and unlike a plain idempotent action handler,
    // each extra listener here would independently re-toggle the .show
    // class on every click, flipping the end state test-by-test.
    initNavFlyoutSubmenu();

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('opens a submenu on clicking its toggle', () => {
        buildMenu();
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;

        click(toggle);

        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(true);
    });

    it('closes an open submenu on clicking its toggle again', () => {
        buildMenu();
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;

        click(toggle);
        click(toggle);

        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(false);
    });

    it('closes a sibling submenu when a different one opens', () => {
        buildMenu();
        const toggles = document.querySelectorAll('.dropdown-submenu-toggle');
        const [companyToggle, emailToggle] = [toggles[0] as HTMLElement, toggles[1] as HTMLElement];

        click(companyToggle);
        expect(companyToggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(true);

        click(emailToggle);

        expect(companyToggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(false);
        expect(emailToggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(true);
    });

    it('closes every open submenu on an outside click', () => {
        buildMenu();
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;
        click(toggle);
        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(true);

        click(document.getElementById('outside-element') as HTMLElement);

        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(false);
    });

    it('closes every open submenu on Escape', () => {
        buildMenu();
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;
        click(toggle);
        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(true);

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

        expect(toggle.closest('.dropdown-submenu')?.classList.contains('show')).toBe(false);
    });

    it('does not navigate when clicking a toggle (preventDefault called)', () => {
        buildMenu();
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;
        const event = new MouseEvent('click', { bubbles: true, cancelable: true });

        toggle.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
    });

    it('ignores clicks on a toggle whose submenu ancestor is missing', () => {
        document.body.innerHTML = '<span class="dropdown-item dropdown-submenu-toggle">Orphan</span>';
        const toggle = document.querySelector('.dropdown-submenu-toggle') as HTMLElement;

        expect(() => { click(toggle); }).not.toThrow();
    });
});
