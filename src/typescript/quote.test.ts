import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { QuoteHandler } from './quote.js';

/**
 * Regression coverage for a real production bug: approving a quote as an
 * observer (guest urlKey PO-number modal -> POST /invoice/quote/approve)
 * failed server-side (a stray so_id column blocked the SalesOrder insert --
 * see project_sales_order_amount_so_id_column_incident memory), but the
 * button still showed a checkmark identical to the success icon, because
 * handleQuotePurchaseOrderConfirm's error path called
 * setButtonLoadingOff(btn) without passing the button's own original HTML
 * -- that function's no-arg fallback happens to *also* render a checkmark.
 * A genuine failure was visually indistinguishable from success, which is
 * exactly what delayed diagnosing the real bug live.
 */
describe('QuoteHandler purchase-order approve confirm', () => {
    const originalButtonHtml = '<i class="bi bi-check-lg"></i> Submit';

    // QuoteHandler's constructor binds click listeners directly onto
    // `document` with no corresponding teardown -- constructing a fresh
    // instance per test would leave every earlier instance's listeners
    // still attached, so later clicks fire multiple overlapping handler
    // runs that race each other. One shared instance for the whole suite
    // avoids that; DOM content itself is still reset per test below.
    beforeAll(() => {
        new QuoteHandler();
    });

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="purchase-order-number">
                <input type="text" id="url_key" value="test-url-key">
                <input type="text" id="quote_with_purchase_order_number" value="PO123">
                <input type="text" id="quote_with_purchase_order_person" value="jim">
                <button class="quote_with_purchase_order_number_confirm"
                        id="quote_with_purchase_order_number_confirm" type="button">
                    ${originalButtonHtml}
                </button>
            </div>
        `;
        vi.stubGlobal('alert', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    function clickConfirmButton(): void {
        const btn = document.getElementById('quote_with_purchase_order_number_confirm');
        btn?.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    }

    it('restores the button to its own original markup, not a checkmark, when the server reports failure', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ success: 0 }), { status: 200 }),
        ));

        clickConfirmButton();

        await vi.waitFor(() => {
            expect(globalThis.alert).toHaveBeenCalled();
        });

        const btn = document.getElementById('quote_with_purchase_order_number_confirm');
        expect(btn?.innerHTML.trim()).toBe(originalButtonHtml);
        // The genuine success path's markup -- a bare, textless checkmark --
        // must never appear here.
        expect(btn?.innerHTML).not.toContain('<h2');
    });

    it('restores the button to its own original markup, not a checkmark, when the request itself fails', async () => {
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('network down')));

        clickConfirmButton();

        await vi.waitFor(() => {
            expect(globalThis.alert).toHaveBeenCalled();
        });

        const btn = document.getElementById('quote_with_purchase_order_number_confirm');
        expect(btn?.innerHTML.trim()).toBe(originalButtonHtml);
        expect(btn?.innerHTML).not.toContain('<h2');
    });

    it('shows the success checkmark when the server reports success', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ success: 1 }), { status: 200 }),
        ));
        // jsdom's Location#reload is a non-configurable WebIDL method --
        // vi.spyOn can't stub it. secureReload() calling the real thing is
        // harmless here (jsdom logs a "not implemented" navigation notice,
        // nothing more); the button's own markup update, which happens
        // synchronously before that call, is what this test verifies.

        clickConfirmButton();

        await vi.waitFor(() => {
            const btn = document.getElementById('quote_with_purchase_order_number_confirm');
            expect(btn?.innerHTML).toContain('<h2');
        });

        const btn = document.getElementById('quote_with_purchase_order_number_confirm');
        expect(btn?.innerHTML).toContain('bi-check-lg');
    });
});
