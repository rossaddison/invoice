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

/**
 * The house's address only. A worker is never given the client's name, phone or email (data
 * protection), so the snapshot no longer carries them.
 */
export interface OfflineInvoiceClient {
    address1: string | null;
    address2: string | null;
    buildingNumber: string | null;
    city: string | null;
    state: string | null;
    zip: string | null;
    country: string | null;
}

/** Which side of the house is whose; absent from a snapshot downloaded before sides were added. */
export interface OfflineInvoiceSide {
    side: string;
    sideLabel: string;
    workerName: string;
    mine: boolean;
    status: string;
    statusLabel: string;
    carded: boolean;
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
    /** Absent from a snapshot downloaded before sides were added. */
    sides?: OfflineInvoiceSide[];
}

export interface OfflineDataPayload {
    downloadedAt: string;
    invoices: OfflineInvoice[];
}

const DB_NAME = 'homecare-offline';
// v2: a snapshot saved before client names, phones and emails were removed still holds them, so
// moving to v2 throws the old store away; the worker downloads a fresh one.
const DB_VERSION = 2;
const STORE_NAME = 'snapshot';
const SNAPSHOT_KEY = 'current';

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = (event) => {
            const db = request.result;
            if (event.oldVersion > 0 && db.objectStoreNames.contains(STORE_NAME)) {
                db.deleteObjectStore(STORE_NAME);
            }
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
