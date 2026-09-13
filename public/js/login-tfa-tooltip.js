// Initializes the Bootstrap tooltips inside #tfa-badge on the login page.
//
// The login page doesn't load InvoiceCdnAsset (too heavy/invoice-specific
// for an unauthenticated page), so src/typescript/scripts.ts's own
// initTooltips() never runs here. bootstrap.bundle.js (loaded separately on
// this page) defines the Tooltip class but never auto-instantiates it from
// data-bs-toggle="tooltip" attributes on its own -- this is the smallest
// external file that can do that, kept as a static file (not an inline
// <script>) because CSP's script-src has no 'unsafe-inline'/nonce here.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#tfa-badge [data-bs-toggle="tooltip"]').forEach((el) => {
        try {
            globalThis.bootstrap.Tooltip.getOrCreateInstance(el);
        } catch (e) {
            // bootstrap.bundle.js not loaded/ready -- tooltip stays inert.
        }
    });
});
