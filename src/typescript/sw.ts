/**
 * HomeCare Offline PWA service worker (see
 * docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md). Built to public/sw.js via its
 * own esbuild entry point (package.json's build:typescript:sw) — cannot
 * join the main invoice-typescript-iife.js bundle, since a service worker
 * runs in a separate global scope (no DOM, no window) and must be served
 * from a fixed, predictable URL for its registration scope to work at all.
 *
 * Deliberately minimal "app shell" precaching, nothing more: only the
 * offline-shell page and its own small dedicated JS bundle are cached.
 * Every other request — including inv/guest's own server-rendered HTML,
 * which carries a live CSRF token and session state — passes straight
 * through to the network, never intercepted or served stale. See
 * PaypalPaymentService-style "why not cache the real page" reasoning in
 * Guest.php's guestOffline() docblock for the full rationale.
 *
 * Type-checked separately (tsconfig.sw.json) — this file is excluded from
 * the main tsconfig.json, whose DOM lib conflicts with the worker globals
 * (window vs. self) a service worker needs. tsconfig.sw.json deliberately
 * carries no DOM/WebWorker lib entry at all — the triple-slash reference
 * below is the standard way to bring in ServiceWorkerGlobalScope's types
 * without TypeScript's own "WebWorker" lib redeclaring the already-global
 * `self` as the less specific WorkerGlobalScope and conflicting.
 */

/// <reference lib="webworker" />

export {};

const sw = self as unknown as ServiceWorkerGlobalScope;

const CACHE_NAME = 'homecare-offline-shell-v1';
const SHELL_URL = '/client_invoices/offline';
const SHELL_JS_URL = '/homecare-offline-shell.js';
const PRECACHE_URLS = [SHELL_URL, SHELL_JS_URL];

sw.addEventListener('install', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)),
    );
    void sw.skipWaiting();
});

sw.addEventListener('activate', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))),
        ),
    );
    void sw.clients.claim();
});

sw.addEventListener('fetch', (event: FetchEvent) => {
    const url = new URL(event.request.url);
    if (event.request.method !== 'GET' || !PRECACHE_URLS.includes(url.pathname)) {
        return; // pass-through — see this file's own docblock
    }
    event.respondWith(
        caches.match(event.request).then((cached) => cached ?? fetch(event.request)),
    );
});
