/**
 * HomeCare Offline PWA — "Add to Home Screen" install nudge, worker-only
 * (no-ops entirely if #homecare-install-banner isn't in the DOM, same
 * gating pattern as homecare-offline.ts's own download button). Exists
 * because of a real, verified risk documented in
 * docs/HOMECARE_OFFLINE_PWA_DATA_FLOW_AUGUST_2026.md's "Out of scope"
 * section: iOS Safari evicts IndexedDB/Cache Storage after 7 days
 * without a visit unless the site is added to the home screen, and
 * nothing here previously nudged a worker to do that — they were just
 * using an ordinary bookmarked tab.
 *
 * iOS Safari never fires `beforeinstallprompt` at all (a platform
 * limitation, not a bug here) — there is no programmatic way to trigger
 * its install sheet, so the two paths are genuinely different:
 *  - Chrome/Edge/Android: capture the real event, reveal a button that
 *    calls its own .prompt().
 *  - iOS Safari: reveal static "tap Share, then Add to Home Screen"
 *    instructions instead — the only thing this file can offer there.
 * Both skip entirely if already running installed (display-mode:
 * standalone, or the legacy iOS-only navigator.standalone flag), or if
 * the worker dismissed the banner within the last 14 days.
 */

const BANNER_ID = 'homecare-install-banner';
const IOS_MESSAGE_ID = 'homecare-install-message-ios';
const GENERIC_MESSAGE_ID = 'homecare-install-message-generic';
const BUTTON_ID = 'homecare-install-button';
const DISMISS_ID = 'homecare-install-dismiss';
const DISMISS_STORAGE_KEY = 'homecare-install-dismissed-at';
const DISMISS_SUPPRESS_DAYS = 14;

interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
}

function isStandalone(): boolean {
    const nav = navigator as Navigator & { standalone?: boolean };
    return globalThis.matchMedia('(display-mode: standalone)').matches || nav.standalone === true;
}

function isIos(): boolean {
    return /iPad|iPhone|iPod/.test(navigator.userAgent);
}

function recentlyDismissed(): boolean {
    let raw: string | null;
    try {
        raw = localStorage.getItem(DISMISS_STORAGE_KEY);
    } catch {
        return false; // storage disabled (private browsing etc.) — never suppress
    }
    if (!raw) return false;
    const dismissedAt = Number.parseInt(raw, 10);
    if (Number.isNaN(dismissedAt)) return false;
    const elapsedDays = (Date.now() - dismissedAt) / (1000 * 60 * 60 * 24);
    return elapsedDays < DISMISS_SUPPRESS_DAYS;
}

function markDismissed(): void {
    try {
        localStorage.setItem(DISMISS_STORAGE_KEY, String(Date.now()));
    } catch {
        // Private browsing / storage disabled — banner just reappears next load, harmless.
    }
}

// Named module-scope reference so a repeated call removes its own
// previous listener before adding a new one — this file's own guard
// against ever double-registering on the shared global object, the
// same failure mode a naive inline-closure listener would risk if this
// function were ever called more than once (also what made the
// vitest suite's global-event test reliable, but that's a side effect
// of fixing a real robustness gap, not the reason for it).
let installPromptHandler: ((event: Event) => void) | null = null;

export function initHomeCareInstallPrompt(): void {
    const banner = document.getElementById(BANNER_ID);
    if (!banner) return; // not a worker-scoped guest, or markup absent

    document.getElementById(DISMISS_ID)?.addEventListener('click', () => {
        markDismissed();
        banner.classList.add('d-none');
    });

    if (installPromptHandler) {
        globalThis.removeEventListener('beforeinstallprompt', installPromptHandler);
        installPromptHandler = null;
    }

    if (isStandalone() || recentlyDismissed()) return;

    installPromptHandler = (event: Event): void => {
        event.preventDefault();
        const deferredPrompt = event as BeforeInstallPromptEvent;
        const button = document.getElementById(BUTTON_ID);
        document.getElementById(GENERIC_MESSAGE_ID)?.classList.remove('d-none');
        button?.classList.remove('d-none');
        button?.addEventListener('click', () => {
            void deferredPrompt.prompt();
            banner.classList.add('d-none');
        }, { once: true });
        banner.classList.remove('d-none');
    };
    globalThis.addEventListener('beforeinstallprompt', installPromptHandler);

    if (isIos()) {
        document.getElementById(IOS_MESSAGE_ID)?.classList.remove('d-none');
        banner.classList.remove('d-none');
    }
}
