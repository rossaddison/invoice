// Public-site cookie consent banner (resources/views/layout/templates/
// soletrader/main.php's own #cookie-consent-banner markup). Shows once
// for a visitor with no consent on file; Accept/Decline both just
// record the choice in a first-party cookie (1 year) so the banner
// doesn't ask again -- this app currently sets only strictly-necessary
// cookies (session, CSRF, language preference), so there is nothing
// non-essential to actually switch on/off yet. This is what makes
// privacypolicy.php's existing "Cookies Policy / Notice Acceptance
// Cookies" paragraph literally true rather than aspirational
// boilerplate (see feedback thread 2026-09-12: no consent mechanism
// existed at all before this file).
//
// This file exports its pure logic (everything except the two
// document.addEventListener calls at the bottom) so it can be unit
// tested directly -- see cookie-consent.test.ts -- while still
// compiling to a single self-contained IIFE
// (src/Asset/rebuild/js/cookie-consent-iife.js, via
// build:typescript:cookie-consent) for the page to load as a plain
// <script src>, matching hmrc-api-select.ts's own standalone-bundle
// precedent (this is NOT part of the big src/typescript/index.ts
// bundle -- the public site layout never loads that one).

const COOKIE_NAME = 'cookie_consent';

// Bump this whenever the set of cookies this app actually uses changes
// in a way a visitor's earlier choice didn't cover -- e.g. if a future
// PR adds a real analytics or marketing cookie. A cookie value tagged
// with an older version is treated as "no answer on file", so the
// banner reappears and asks again rather than an old blanket "accept"
// silently being stretched to cover a category the visitor never
// actually saw.
const CONSENT_VERSION = 'v1';

const MAX_AGE_SECONDS = 60 * 60 * 24 * 365;

// Session-only fallback, used only when document.cookie itself turns
// out to be unwritable (some strict private-browsing modes, or an
// extension blocking cookie writes entirely). Without this, a visitor
// in that situation would see the banner reappear on every single page
// load of the same visit, with nothing able to remember their choice.
// It still won't survive to their next visit -- there is genuinely
// nowhere more durable left to remember "no" in once cookies
// themselves are blocked, and reappearing on the NEXT visit is the
// honest, safe default rather than silently giving up and hiding it
// forever.
const SESSION_FALLBACK_KEY = 'cookie_consent_dismissed_this_session';

export type ConsentValue = 'accepted' | 'declined';

declare global {
    interface Navigator {
        // Global Privacy Control (globalprivacycontrol.org) -- a
        // browser/extension-sent signal, legally recognized as a valid
        // opt-out request under CCPA/CPRA (California) and several
        // other US state privacy laws. Not in TypeScript's own DOM
        // lib yet, hence declaring it here rather than casting at each
        // use site.
        globalPrivacyControl?: boolean;
    }
}

/**
 * Reads back this visitor's consent choice, or null if there isn't one
 * on file for the CURRENT `CONSENT_VERSION` (an absent cookie, an
 * unparseable value, and an old-version value are all treated
 * identically -- all three mean "ask again").
 */
export function readConsentCookie(): ConsentValue | null {
    const match = document.cookie
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith(`${COOKIE_NAME}=`));
    if (!match) return null;

    const rawValue = match.slice(COOKIE_NAME.length + 1);
    const [choice, version] = decodeURIComponent(rawValue).split(':');
    if (version !== CONSENT_VERSION) return null;

    return choice === 'accepted' || choice === 'declined' ? choice : null;
}

/**
 * Writes the consent cookie and immediately reads it back to confirm
 * the write actually took -- document.cookie assignments fail silently
 * (no exception, no return value) when cookies are blocked, so reading
 * it back is the only reliable way to detect that.
 */
export function writeConsentCookie(value: ConsentValue): boolean {
    const secure = globalThis.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie =
        `${COOKIE_NAME}=${value}:${CONSENT_VERSION}; max-age=${MAX_AGE_SECONDS}`
        + `; path=/; SameSite=Lax${secure}`;
    return readConsentCookie() === value;
}

/** Whether the browser itself is asking, on this visitor's behalf, to be treated as opted out. */
export function hasGlobalPrivacyControlOptOut(): boolean {
    return globalThis.navigator.globalPrivacyControl === true;
}

function rememberSessionDismissal(): void {
    try {
        globalThis.sessionStorage.setItem(SESSION_FALLBACK_KEY, '1');
    } catch {
        // sessionStorage can throw in the same blocked-storage cases --
        // nothing further to fall back to; the banner will simply
        // reappear on the next page load, which is the safe default.
    }
}

export function wasDismissedThisSession(): boolean {
    try {
        return globalThis.sessionStorage.getItem(SESSION_FALLBACK_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * Fades the banner out instead of vanishing it instantly (a real user
 * report during testing read the instant disappearance as "did that
 * even do anything?"), then removes it from layout once the fade
 * actually finishes. The transitionend listener has a hard timeout
 * fallback -- prefers-reduced-motion strips the CSS transition in some
 * browsers, and the banner must never end up permanently stuck
 * half-faded because that event never fires.
 */
export function hideBanner(banner: HTMLElement): void {
    banner.classList.add('cookie-consent-hiding');
    const finish = (): void => {
        banner.hidden = true;
        banner.classList.remove('cookie-consent-hiding');
    };
    banner.addEventListener('transitionend', finish, { once: true });
    globalThis.setTimeout(finish, 300);
}

export function showBanner(banner: HTMLElement): void {
    banner.classList.remove('cookie-consent-hiding');
    banner.hidden = false;
}

/** Records Accept/Decline (cookie, with the session fallback above) and hides the banner. */
export function applyChoice(banner: HTMLElement, value: ConsentValue): void {
    if (!writeConsentCookie(value)) {
        rememberSessionDismissal();
    }
    hideBanner(banner);
}

/**
 * Wires the banner's actual page behaviour: show it once on load
 * (unless GPC already answered on the visitor's behalf, or it was
 * already dismissed earlier this session as the blocked-cookie
 * fallback), and delegate Accept/Decline/reopen clicks. Exported
 * separately from the module-level call at the bottom so
 * cookie-consent.test.ts can invoke it against a jsdom document
 * without relying on a real DOMContentLoaded firing.
 */
export function initCookieConsent(): void {
    document.addEventListener('DOMContentLoaded', (): void => {
        const banner = document.getElementById('cookie-consent-banner');
        if (!banner) return;

        if (hasGlobalPrivacyControlOptOut() && readConsentCookie() === null) {
            // Honor the browser's own opt-out signal without making the
            // visitor click anything -- write the same "declined" cookie
            // a manual click would, and never show the banner at all.
            writeConsentCookie('declined');
            return;
        }

        if (readConsentCookie() === null && !wasDismissedThisSession()) {
            showBanner(banner);
        }
    });

    document.addEventListener('click', (event: Event): void => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const banner = document.getElementById('cookie-consent-banner');
        if (!banner) return;

        if (target.closest('#btn-cookie-consent-accept')) {
            applyChoice(banner, 'accepted');
        } else if (target.closest('#btn-cookie-consent-decline')) {
            applyChoice(banner, 'declined');
        } else if (target.closest('#cookie-consent-reopen')) {
            // The footer's persistent "Cookie preferences" link -- the
            // only way back once the banner has already been dismissed
            // once; re-showing it doesn't touch the cookie until the
            // visitor actually clicks Accept/Decline again.
            showBanner(banner);
        }
    });
}

initCookieConsent();
