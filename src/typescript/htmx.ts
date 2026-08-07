// src/typescript/htmx.ts
// Imports htmx from the npm package (htmx.org).
// Imported by index.ts and compiled into invoice-typescript-iife.js via:
//   npm run build:typescript

import htmxLib from 'htmx.org';

declare global {
    // var (not `interface Window`) — TypeScript only extends the type of
    // globalThis itself for ambient global `var`/`function`/`class`
    // declarations, not for `interface Window` augmentations. This app's
    // own convention is globalThis, never window (typescript:S7764), so
    // the declaration needs to match that, not the DOM's Window type.
    var htmx: typeof htmxLib;
}

// Pin htmx on globalThis so inline hx-on:: handlers and other scripts can reach it.
globalThis.htmx = htmxLib;

export default htmxLib;
