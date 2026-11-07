import { afterEach, describe, expect, it, vi } from 'vitest';
import { collapseAllocationSidesOnPhones } from './alloc-collapse.js';

const markup = (): string =>
    '<div id="a"><details class="alloc-collapsible" open><summary>x</summary></details></div>' +
    '<div id="b"><details class="alloc-collapsible" open><summary>y</summary></details></div>' +
    '<details class="other" open><summary>z</summary></details>';

function screenIsPhone(phone: boolean): void {
    vi.stubGlobal('matchMedia', (query: string) => ({ matches: phone, media: query }));
}

describe('collapseAllocationSidesOnPhones', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('closes every folded allocation line on a phone-width screen', () => {
        document.body.innerHTML = markup();
        screenIsPhone(true);

        collapseAllocationSidesOnPhones();

        expect(document.querySelectorAll('details.alloc-collapsible[open]').length).toBe(0);
    });

    it('leaves them open on a desktop', () => {
        document.body.innerHTML = markup();
        screenIsPhone(false);

        collapseAllocationSidesOnPhones();

        expect(document.querySelectorAll('details.alloc-collapsible[open]').length).toBe(2);
    });

    it('closes only inside the given root, so a house expanded elsewhere stays open', () => {
        document.body.innerHTML = markup();
        screenIsPhone(true);

        collapseAllocationSidesOnPhones(document.getElementById('a')!);

        expect(document.querySelector('#a details')!.hasAttribute('open')).toBe(false);
        expect(document.querySelector('#b details')!.hasAttribute('open')).toBe(true);
    });

    it('never touches other details elements', () => {
        document.body.innerHTML = markup();
        screenIsPhone(true);

        collapseAllocationSidesOnPhones();

        expect(document.querySelector('details.other')!.hasAttribute('open')).toBe(true);
    });

    it('does nothing where matchMedia is unavailable', () => {
        document.body.innerHTML = markup();
        vi.stubGlobal('matchMedia', undefined);

        collapseAllocationSidesOnPhones();

        expect(document.querySelectorAll('details.alloc-collapsible[open]').length).toBe(2);
    });
});