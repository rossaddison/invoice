// Public-site cookie consent banner (resources/views/layout/templates/
// soletrader/main.php's own #cookie-consent-banner markup). Shows once
// for a visitor with no existing `cookie_consent` cookie; Accept/Decline
// both just record the choice in a first-party cookie (1 year) so the
// banner doesn't ask again -- this app currently sets only strictly-
// necessary cookies (session, CSRF, language preference), so there is
// nothing non-essential to actually switch on/off yet. This is what
// makes privacypolicy.php's existing "Cookies Policy / Notice Acceptance
// Cookies" paragraph literally true rather than aspirational boilerplate
// (see feedback thread 2026-09-12: no consent mechanism existed at all
// before this file).
//
// Compiled to src/Asset/rebuild/js/cookie-consent-iife.js by
// build:typescript:cookie-consent.

const COOKIE_NAME = 'cookie_consent';
const MAX_AGE_SECONDS = 60 * 60 * 24 * 365;

function hasConsentCookie(): boolean {
    return document.cookie
        .split(';')
        .some((part) => part.trim().startsWith(`${COOKIE_NAME}=`));
}

function setConsentCookie(value: 'accepted' | 'declined'): void {
    const secure = globalThis.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie =
        `${COOKIE_NAME}=${value}; max-age=${MAX_AGE_SECONDS}; path=/; SameSite=Lax${secure}`;
}

function hideBanner(banner: HTMLElement): void {
    banner.hidden = true;
}

document.addEventListener('DOMContentLoaded', (): void => {
    const banner = document.getElementById('cookie-consent-banner');
    if (banner && !hasConsentCookie()) {
        banner.hidden = false;
    }
});

document.addEventListener('click', (event: Event): void => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const banner = document.getElementById('cookie-consent-banner');
    if (!banner) return;

    if (target.closest('#btn-cookie-consent-accept')) {
        setConsentCookie('accepted');
        hideBanner(banner);
    } else if (target.closest('#btn-cookie-consent-decline')) {
        setConsentCookie('declined');
        hideBanner(banner);
    }
});
