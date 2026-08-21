import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initHomeCareInstallPrompt } from './homecare-install-prompt.js';

const DISMISS_STORAGE_KEY = 'homecare-install-dismissed-at';

// Module scope (not inside describe()) — matches column-resizer.test.ts's own
// SonarQube typescript:S7721 reasoning: doesn't close over anything test-local.
function buildBanner(): void {
    document.body.innerHTML = `
        <div id="homecare-install-banner" class="d-none">
            <span id="homecare-install-message-ios" class="d-none">ios message</span>
            <span id="homecare-install-message-generic" class="d-none">generic message</span>
            <button id="homecare-install-button" class="d-none">Install app</button>
            <button id="homecare-install-dismiss">Close</button>
        </div>
    `;
}

function banner(): HTMLElement {
    return document.getElementById('homecare-install-banner') as HTMLElement;
}

function stubMatchMedia(standalone: boolean): void {
    vi.stubGlobal('matchMedia', vi.fn().mockReturnValue({ matches: standalone }));
}

function stubUserAgent(userAgent: string): void {
    Object.defineProperty(navigator, 'userAgent', { value: userAgent, configurable: true });
}

describe('initHomeCareInstallPrompt', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        localStorage.clear();
        stubMatchMedia(false);
        stubUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120');
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('does nothing when the banner element is absent — an ordinary client guest', () => {
        expect(() => initHomeCareInstallPrompt()).not.toThrow();
        expect(document.getElementById('homecare-install-banner')).toBeNull();
    });

    it('stays hidden when already running installed (display-mode: standalone)', () => {
        buildBanner();
        stubMatchMedia(true);

        initHomeCareInstallPrompt();

        expect(banner().classList.contains('d-none')).toBe(true);
    });

    it('stays hidden when dismissed within the last 14 days', () => {
        buildBanner();
        localStorage.setItem(DISMISS_STORAGE_KEY, String(Date.now() - 5 * 24 * 60 * 60 * 1000));

        initHomeCareInstallPrompt();

        expect(banner().classList.contains('d-none')).toBe(true);
    });

    it('reveals the iOS instructions on an iOS user agent, after a 14+ day dismissal has expired', () => {
        buildBanner();
        localStorage.setItem(DISMISS_STORAGE_KEY, String(Date.now() - 20 * 24 * 60 * 60 * 1000));
        stubUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

        initHomeCareInstallPrompt();

        expect(banner().classList.contains('d-none')).toBe(false);
        expect(document.getElementById('homecare-install-message-ios')?.classList.contains('d-none')).toBe(false);
        expect(document.getElementById('homecare-install-message-generic')?.classList.contains('d-none')).toBe(true);
        expect(document.getElementById('homecare-install-button')?.classList.contains('d-none')).toBe(true);
    });

    it('stays hidden on a non-iOS browser that never fires beforeinstallprompt', () => {
        buildBanner();

        initHomeCareInstallPrompt();

        expect(banner().classList.contains('d-none')).toBe(true);
    });

    it('reveals the generic message and install button when beforeinstallprompt fires, and prompts on click', async () => {
        buildBanner();
        initHomeCareInstallPrompt();

        const prompt = vi.fn().mockResolvedValue(undefined);
        const event = new Event('beforeinstallprompt', { cancelable: true }) as Event & { prompt: () => Promise<void> };
        event.prompt = prompt;
        globalThis.dispatchEvent(event);

        expect(banner().classList.contains('d-none')).toBe(false);
        expect(document.getElementById('homecare-install-message-generic')?.classList.contains('d-none')).toBe(false);
        const button = document.getElementById('homecare-install-button') as HTMLButtonElement;
        expect(button.classList.contains('d-none')).toBe(false);

        button.click();
        await Promise.resolve();

        expect(prompt).toHaveBeenCalledOnce();
        expect(banner().classList.contains('d-none')).toBe(true);
    });

    it('clicking dismiss hides the banner and records a dismissal timestamp', () => {
        buildBanner();
        stubUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');
        initHomeCareInstallPrompt();
        expect(banner().classList.contains('d-none')).toBe(false);

        const before = Date.now();
        (document.getElementById('homecare-install-dismiss') as HTMLButtonElement).click();
        const after = Date.now();

        expect(banner().classList.contains('d-none')).toBe(true);
        const stored = Number.parseInt(localStorage.getItem(DISMISS_STORAGE_KEY) ?? '', 10);
        expect(stored).toBeGreaterThanOrEqual(before);
        expect(stored).toBeLessThanOrEqual(after);
    });
});
