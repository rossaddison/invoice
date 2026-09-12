import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    applyChoice,
    hasGlobalPrivacyControlOptOut,
    hideBanner,
    initCookieConsent,
    readConsentCookie,
    showBanner,
    wasDismissedThisSession,
    writeConsentCookie,
} from './cookie-consent.js';

/** jsdom keeps cookies across tests unless explicitly cleared. */
function clearAllCookies(): void {
    document.cookie.split(';').forEach((c) => {
        const name = c.split('=')[0]?.trim();
        if (name) document.cookie = `${name}=; max-age=0; path=/`;
    });
}

describe('readConsentCookie / writeConsentCookie', () => {
    beforeEach(() => {
        clearAllCookies();
    });

    it('returns null when no cookie is set', () => {
        expect(readConsentCookie()).toBeNull();
    });

    it('round-trips an accepted choice', () => {
        expect(writeConsentCookie('accepted')).toBe(true);
        expect(readConsentCookie()).toBe('accepted');
    });

    it('round-trips a declined choice', () => {
        expect(writeConsentCookie('declined')).toBe(true);
        expect(readConsentCookie()).toBe('declined');
    });

    it('treats an old-version cookie value as no consent on file', () => {
        // Simulates a visitor who consented under a previous
        // CONSENT_VERSION, e.g. before a new cookie category existed --
        // must be re-asked, not silently carried forward.
        document.cookie = 'cookie_consent=accepted%3Av0; path=/';
        expect(readConsentCookie()).toBeNull();
    });

    it('treats an unparseable cookie value as no consent on file', () => {
        document.cookie = 'cookie_consent=garbage; path=/';
        expect(readConsentCookie()).toBeNull();
    });
});

describe('hasGlobalPrivacyControlOptOut', () => {
    afterEach(() => {
        // @ts-expect-error -- test-only cleanup of a property this file itself declares
        delete navigator.globalPrivacyControl;
    });

    it('is false when the browser sends no signal', () => {
        expect(hasGlobalPrivacyControlOptOut()).toBe(false);
    });

    it('is true when the browser sends navigator.globalPrivacyControl = true', () => {
        Object.defineProperty(navigator, 'globalPrivacyControl', {
            value: true,
            configurable: true,
        });
        expect(hasGlobalPrivacyControlOptOut()).toBe(true);
    });
});

describe('showBanner / hideBanner', () => {
    let banner: HTMLElement;

    beforeEach(() => {
        document.body.innerHTML = '<div id="cookie-consent-banner" hidden></div>';
        banner = document.getElementById('cookie-consent-banner') as HTMLElement;
    });

    it('showBanner clears the hidden attribute', () => {
        showBanner(banner);
        expect(banner.hidden).toBe(false);
    });

    it('hideBanner sets the hidden attribute once the fade timeout elapses', () => {
        vi.useFakeTimers();
        showBanner(banner);

        hideBanner(banner);
        expect(banner.classList.contains('cookie-consent-hiding')).toBe(true);
        expect(banner.hidden).toBe(false); // still fading, not yet hidden

        vi.advanceTimersByTime(300);
        expect(banner.hidden).toBe(true);
        expect(banner.classList.contains('cookie-consent-hiding')).toBe(false);

        vi.useRealTimers();
    });

    it('hideBanner hides immediately once transitionend actually fires', () => {
        showBanner(banner);
        hideBanner(banner);

        banner.dispatchEvent(new Event('transitionend'));

        expect(banner.hidden).toBe(true);
    });
});

describe('applyChoice', () => {
    let banner: HTMLElement;

    beforeEach(() => {
        clearAllCookies();
        document.body.innerHTML = '<div id="cookie-consent-banner"></div>';
        banner = document.getElementById('cookie-consent-banner') as HTMLElement;
    });

    it('writes the cookie and hides the banner on a successful write', () => {
        applyChoice(banner, 'accepted');
        expect(readConsentCookie()).toBe('accepted');
        banner.dispatchEvent(new Event('transitionend'));
        expect(banner.hidden).toBe(true);
    });

    it('falls back to the session flag when the cookie write does not take', () => {
        // Simulates a browser silently discarding cookie writes.
        const setter = vi.spyOn(document, 'cookie', 'set').mockImplementation(() => {});
        const getter = vi.spyOn(document, 'cookie', 'get').mockReturnValue('');

        applyChoice(banner, 'declined');

        expect(wasDismissedThisSession()).toBe(true);

        setter.mockRestore();
        getter.mockRestore();
    });
});

describe('initCookieConsent', () => {
    beforeEach(() => {
        clearAllCookies();
        sessionStorage.clear();
        document.body.innerHTML = `
            <div id="cookie-consent-banner" hidden>
                <button id="btn-cookie-consent-accept">Accept</button>
                <button id="btn-cookie-consent-decline">Decline</button>
            </div>
            <a id="cookie-consent-reopen" href="#">Cookie preferences</a>
        `;
        initCookieConsent();
    });

    it('shows the banner on DOMContentLoaded when there is no consent on file', () => {
        document.dispatchEvent(new Event('DOMContentLoaded'));
        const banner = document.getElementById('cookie-consent-banner') as HTMLElement;
        expect(banner.hidden).toBe(false);
    });

    it('does not show the banner when Global Privacy Control opts the visitor out', () => {
        Object.defineProperty(navigator, 'globalPrivacyControl', {
            value: true,
            configurable: true,
        });

        document.dispatchEvent(new Event('DOMContentLoaded'));

        const banner = document.getElementById('cookie-consent-banner') as HTMLElement;
        expect(banner.hidden).toBe(true);
        expect(readConsentCookie()).toBe('declined');

        // @ts-expect-error -- test-only cleanup
        delete navigator.globalPrivacyControl;
    });

    it('clicking Accept records the cookie', () => {
        document.dispatchEvent(new Event('DOMContentLoaded'));
        document
            .getElementById('btn-cookie-consent-accept')
            ?.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(readConsentCookie()).toBe('accepted');
    });

    it('clicking Decline records the cookie', () => {
        document.dispatchEvent(new Event('DOMContentLoaded'));
        document
            .getElementById('btn-cookie-consent-decline')
            ?.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(readConsentCookie()).toBe('declined');
    });

    it('clicking the reopen link shows the banner again without touching the cookie', () => {
        writeConsentCookie('accepted');
        document.dispatchEvent(new Event('DOMContentLoaded'));
        const banner = document.getElementById('cookie-consent-banner') as HTMLElement;
        expect(banner.hidden).toBe(true); // already consented, stayed hidden

        document
            .getElementById('cookie-consent-reopen')
            ?.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(banner.hidden).toBe(false);
        expect(readConsentCookie()).toBe('accepted'); // unchanged
    });
});
