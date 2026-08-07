/**
 * HomeCare Offline PWA — the offline shell page's own renderer
 * (resources/views/invoice/inv/offline.php). Its own small esbuild entry
 * point (see package.json's build:typescript:homecare-shell), deliberately
 * NOT part of the main invoice-typescript-iife.js bundle — this is exactly
 * what the service worker precaches (src/typescript/sw.ts), so keeping it
 * small keeps the precached payload small.
 *
 * Reads whatever was last saved to IndexedDB by homecare-offline.ts
 * (running on inv/guest, while online) and renders it — this page never
 * makes a network request of its own, online or off. Builds DOM elements
 * directly rather than string-concatenated HTML, since this data (client
 * name/address/notes) is free text a client or worker entered.
 */

import { loadOfflineSnapshot, type OfflineInvoice } from './homecare-offline-db.js';

const ROOT_ID = 'homecare-offline-root';
const I18N_ISLAND_ID = 'homecare-offline-shell-i18n';

interface HomeCareOfflineShellMessages {
    empty: string;
    downloadedAt: string;
}

function readMessages(): HomeCareOfflineShellMessages {
    const fallback: HomeCareOfflineShellMessages = {
        empty: 'No invoices downloaded yet. Connect to the internet and tap "Download for Offline" from your invoice list first.',
        downloadedAt: 'Downloaded',
    };
    const island = document.getElementById(I18N_ISLAND_ID);
    if (!island?.textContent) return fallback;
    try {
        return JSON.parse(island.textContent) as HomeCareOfflineShellMessages;
    } catch {
        return fallback;
    }
}

function textEl(tag: string, className: string, text: string): HTMLElement {
    const el = document.createElement(tag);
    el.className = className;
    el.textContent = text;
    return el;
}

function nonEmptyParts(parts: (string | null)[]): string[] {
    return parts.filter((part): part is string => !!part && part.trim() !== '');
}

function renderClientBlock(client: NonNullable<OfflineInvoice['client']>): DocumentFragment {
    const block = document.createDocumentFragment();
    block.appendChild(textEl('div', 'client', client.fullName));

    const addressParts = nonEmptyParts([client.buildingNumber, client.address1, client.address2, client.city, client.state, client.zip, client.country]);
    if (addressParts.length > 0) {
        block.appendChild(textEl('div', 'address', addressParts.join(', ')));
    }

    const contactParts = nonEmptyParts([client.phone, client.mobile, client.email]);
    if (contactParts.length > 0) {
        block.appendChild(textEl('div', 'contact', contactParts.join(' · ')));
    }

    return block;
}

function renderItemLine(item: OfflineInvoice['items'][number]): HTMLElement {
    const li = document.createElement('li');
    const quantityPrefix = item.quantity !== null ? `${item.quantity} × ` : '';
    li.textContent = quantityPrefix + (item.name ?? '');
    if (item.note) {
        const noteSpan = document.createElement('span');
        noteSpan.className = 'note';
        noteSpan.textContent = ' — ' + item.note;
        li.appendChild(noteSpan);
    }
    return li;
}

function renderItemList(items: OfflineInvoice['items']): HTMLElement {
    const list = document.createElement('ul');
    list.className = 'items';
    for (const item of items) {
        list.appendChild(renderItemLine(item));
    }
    return list;
}

function renderInvoiceCard(inv: OfflineInvoice): HTMLElement {
    const card = document.createElement('div');
    card.className = 'offline-card';

    card.appendChild(textEl('h2', '', inv.number ?? '#'));
    card.appendChild(textEl('span', 'status', inv.statusLabel));

    if (inv.doNotSend) {
        card.appendChild(textEl('div', 'note', '🚫 ' + inv.doNotSendReason));
    }
    if (inv.client) {
        card.appendChild(renderClientBlock(inv.client));
    }
    if (inv.note) {
        card.appendChild(textEl('div', 'note', inv.note));
    }
    if (inv.items.length > 0) {
        card.appendChild(renderItemList(inv.items));
    }

    return card;
}

async function renderOfflineShell(): Promise<void> {
    const root = document.getElementById(ROOT_ID);
    if (!root) return;

    const messages = readMessages();
    const snapshot = await loadOfflineSnapshot();

    if (!snapshot || snapshot.invoices.length === 0) {
        root.appendChild(textEl('div', 'offline-empty', messages.empty));
        return;
    }

    const downloadedAt = document.createElement('div');
    downloadedAt.className = 'offline-downloaded-at';
    downloadedAt.textContent = `${messages.downloadedAt}: ${new Date(snapshot.downloadedAt).toLocaleString()}`;
    root.appendChild(downloadedAt);

    for (const inv of snapshot.invoices) {
        root.appendChild(renderInvoiceCard(inv));
    }
}

function domReady(): Promise<void> {
    if (document.readyState !== 'loading') {
        return Promise.resolve();
    }
    return new Promise((resolve) => {
        document.addEventListener('DOMContentLoaded', () => resolve(), { once: true });
    });
}

// Top-level await instead of an async IIFE/void call (typescript:S7785) —
// this file builds as an ES module (package.json's
// build:typescript:homecare-shell, --format=esm) and loads via
// <script type="module"> (resources/views/invoice/inv/offline.php),
// specifically to allow this. Module scripts are deferred by the HTML spec
// on their own, so no separate ->defer() is needed.
await domReady();
await renderOfflineShell();
