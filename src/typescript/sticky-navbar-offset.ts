/**
 * Keeps the --sticky-content-top CSS custom property in sync with the
 * invoice navbar's actual rendered height, whenever the navbar itself is
 * sticky (Settings > Bootstrap5 > Layout Invoice Navbar Sticky).
 *
 * resources/views/layout/invoice.php seeds --sticky-content-top with a
 * static var(--navbar-height) (50px, src/Invoice/Asset/invoice/css/
 * variables.css) so anything reading it has a sane value before this runs
 * — but that's only a single-line-navbar guess. The real navbar wraps onto
 * a second line well before every dropdown fits (confirmed live
 * 2026-09-04: at a fairly ordinary viewport width, FAQ's/Generator/
 * Performance filled the first line and Platform/the emoji dropdowns
 * wrapped to a second), which the static value can't know about — so
 * anything sticking to --sticky-content-top (e.g. inv/index's sticky grid
 * header, src/Invoice/Asset/invoice/css/overrides.css) ended up tucked
 * partly under that second row instead of sitting flush below the navbar.
 *
 * ResizeObserver (not just a load/resize listener) because the navbar's
 * height can also change from things that aren't a viewport resize at
 * all — a locale switch changing label lengths, a dropdown-heavy menu
 * item's text wrapping differently, browser font-size zoom.
 *
 * No-ops entirely when the navbar isn't sticky (selector matches nothing)
 * — --sticky-content-top just keeps the 0px the layout already gave it in
 * that case, correctly.
 */
export function initStickyNavbarOffset(): void {
    const navbar = document.querySelector<HTMLElement>('nav.navbar.sticky-top');
    if (!navbar) return;

    const root = document.documentElement;
    const applyHeight = (): void => {
        root.style.setProperty('--sticky-content-top', `${navbar.offsetHeight}px`);
    };

    applyHeight();

    if (typeof ResizeObserver === 'undefined') {
        // No-op fallback for the rare browser without ResizeObserver — the
        // static var(--navbar-height) from the layout's own <style> block
        // stays in effect instead, same as before this module existed.
        return;
    }
    new ResizeObserver(applyHeight).observe(navbar);
}
