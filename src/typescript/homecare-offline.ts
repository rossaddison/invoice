/**
 * HomeCare Offline PWA — runs on inv/guest for a worker-scoped guest only
 * (no-ops entirely if the download button isn't in the DOM, i.e. an
 * ordinary client guest). Reads server-supplied translated strings from a
 * #homecare-offline-i18n JSON data-island (same pattern as
 * #inv-guest-filter-config in guest.php) rather than hardcoding English
 * text here.
 *
 * Three responsibilities:
 *  1. Register the service worker (src/typescript/sw.ts,
 *     built to public/sw.js) so the offline shell page is precached.
 *  2. Wire the "Download for Offline" button to fetch
 *     GET /client_invoices/offline-data and store the result via
 *     homecare-offline-db.ts.
 *  3. Silently re-run that same download in the background on every load
 *     of inv/guest — this is what keeps the offline copy fresh whenever
 *     the worker has connectivity, without them needing to remember to
 *     press anything (see docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md).
 */

import { saveOfflineSnapshot, type OfflineDataPayload } from './homecare-offline-db.js';

const DOWNLOAD_BTN_ID = 'homecare-offline-download-btn';
const STATUS_ID = 'homecare-offline-status';
const I18N_ISLAND_ID = 'homecare-offline-i18n';
const OFFLINE_DATA_URL = '/client_invoices/offline-data';

interface HomeCareOfflineMessages {
    success: string;
    failed: string;
}

function readMessages(): HomeCareOfflineMessages {
    const fallback: HomeCareOfflineMessages = {
        success: 'Downloaded %s invoice(s) for offline use.',
        failed: 'Unable to download invoices for offline use — please try again while connected.',
    };
    const island = document.getElementById(I18N_ISLAND_ID);
    if (!island?.textContent) return fallback;
    try {
        return JSON.parse(island.textContent) as HomeCareOfflineMessages;
    } catch {
        return fallback;
    }
}

async function downloadForOffline(): Promise<number | null> {
    const response = await fetch(OFFLINE_DATA_URL, { credentials: 'same-origin' });
    if (!response.ok) return null;
    const payload = (await response.json()) as OfflineDataPayload;
    await saveOfflineSnapshot(payload);
    return payload.invoices.length;
}

function registerServiceWorker(): void {
    if (!('serviceWorker' in navigator)) return;
    navigator.serviceWorker.register('/sw.js').catch((error: unknown) => {
        console.warn('HomeCare offline: service worker registration failed', error);
    });
}

function showStatus(text: string, ok: boolean): void {
    const status = document.getElementById(STATUS_ID);
    if (!status) return;
    status.textContent = text;
    status.classList.toggle('text-success', ok);
    status.classList.toggle('text-danger', !ok);
}

export function initHomeCareOffline(): void {
    const button = document.getElementById(DOWNLOAD_BTN_ID);
    if (!button) return; // not a worker-scoped guest — nothing to offer

    registerServiceWorker();

    // Silent background re-sync, best-effort — a failed silent refresh
    // just leaves whatever snapshot is already cached in place.
    downloadForOffline().catch(() => undefined);

    const messages = readMessages();
    button.addEventListener('click', () => {
        button.setAttribute('disabled', 'true');
        downloadForOffline()
            .then((count) => {
                if (count === null) {
                    showStatus(messages.failed, false);
                    return;
                }
                showStatus(messages.success.replace('%s', String(count)), true);
            })
            .catch(() => showStatus(messages.failed, false))
            .finally(() => button.removeAttribute('disabled'));
    });
}
