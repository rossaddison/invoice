// src/typescript/htmx.ts
// Imports htmx from the npm package (htmx.org).
// Imported by index.ts and compiled into invoice-typescript-iife.js via:
//   npm run build:typescript

import htmx from 'htmx.org';

declare global {
    interface Window {
        htmx: typeof htmx;
    }
}

// Pin htmx on globalThis so inline hx-on:: handlers and other scripts can reach it.
globalThis.htmx = htmx;

export default htmx;
