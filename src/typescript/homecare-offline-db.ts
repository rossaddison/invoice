/**
 * HomeCare Offline PWA — shared IndexedDB storage helper.
 *
 * A single object store holding one "snapshot" record (the whole payload
 * from GET /client_invoices/offline-data, replaced wholesale on every
 * download — never merged/patched, since this is a read-only reference
 * tool with nothing to reconcile). Imported by both homecare-offline.ts
 * (writes, on inv/guest) and homecare-offline-shell.ts (reads, on the
 * offline shell page) — deliberately its own tiny module rather than a
 * shared class, so each of those two esbuild entry points only pulls in
 * what it actually calls.
 *
 * See docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md for the full design.
 */

export interface OfflineInvoiceItem {
    name: string | null;
    description: string | null;
    quantity: number | null;
    productUnit: string | null;
    note: string | null;
}

export interface OfflineInvoiceClient {
    fullName: string;
    address1: string | null;
    address2: string | null;
    buildingNumber: string | null;
    city: string | null;
    state: string | null;
    zip: string | null;
    country: string | null;
    phone: string | null;
    mobile: string | null;
    email: string;
}

export interface OfflineInvoice {
    id: number;
    number: string | null;
    statusId: number;
    statusLabel: string;
    dateCreated: string | null;
    dateDue: string | null;
    note: string | null;
    doNotSend: boolean;
    doNotSendReason: string;
    urlKey: string;
    client: OfflineInvoiceClient | null;
    items: OfflineInvoiceItem[];
}

export interface OfflineDataPayload {
    downloadedAt: string;
    invoices: OfflineInvoice[];
}

const DB_NAME = 'homecare-offline';
const DB_VERSION = 1;
const STORE_NAME = 'snapshot';
const SNAPSHOT_KEY = 'current';

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME);
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error as Error);
    });
}

export async function saveOfflineSnapshot(payload: OfflineDataPayload): Promise<void> {
    const db = await openDb();
    try {
        await new Promise<void>((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put(payload, SNAPSHOT_KEY);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error as Error);
        });
    } finally {
        db.close();
    }
}

export async function loadOfflineSnapshot(): Promise<OfflineDataPayload | null> {
    const db = await openDb();
    try {
        return await new Promise<OfflineDataPayload | null>((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const req = tx.objectStore(STORE_NAME).get(SNAPSHOT_KEY);
            req.onsuccess = () => resolve((req.result as OfflineDataPayload | undefined) ?? null);
            req.onerror = () => reject(req.error as Error);
        });
    } finally {
        db.close();
    }
}
