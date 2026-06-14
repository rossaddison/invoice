console.log("Invoice TypeScript bundle loaded");

// Main TypeScript entry point for Invoice Application
import './htmx.js';
import { CreateCreditHandler } from './create-credit.js';
import { QuoteHandler } from './quote.js';
import { ClientHandler } from './client.js';
import { InvoiceHandler } from './invoice.js';
import { ProductHandler } from './product.js';
import { TaskHandler } from './tasks.js';
import { SalesOrderHandler } from './salesorder.js';
import { FamilyHandler } from './family.js';
import { SettingsHandler } from './settings.js';
import { initTooltips, initSimpleSelects, showFullpageLoader, hideFullpageLoader, initPasswordMeter } from './scripts.js';
import './family-commalist-picker.js';
import { initStripePayment } from './payment-stripe.js';
import { initAmazonPayment } from './payment-amazon.js';
import { initBraintreePayment } from './payment-braintree.js';
import { initTelegramProviderPopup } from './telegram-providers.js';
import { initStreetOrder } from './family-street-order.js';
import { initStepPopovers } from './google-translate-popover.js';
import { AllowanceChargeToggleHandler } from './allowance-charge-toggle.js';

/**
 * Initialize Invoice Application
 */
class InvoiceApp {
    readonly #handlers: ReadonlyArray<object>;

    constructor() {
        // Handlers self-register event listeners on document; array satisfies S1068 by reading .length below
        this.#handlers = [
            new CreateCreditHandler(),
            new QuoteHandler(),
            new ClientHandler(),
            new InvoiceHandler(),
            new ProductHandler(),
            new TaskHandler(),
            new SalesOrderHandler(),
            new FamilyHandler(),
            new SettingsHandler(),
        ];

        this.initializeTooltips();
        this.initializeTaggableFocus();

        // Initialize enhanced scripts functionality
        initTooltips();
        initSimpleSelects();
        initPasswordMeter();

        // Set up fullpage loader handlers
        this.initializeFullpageLoader();

        new AllowanceChargeToggleHandler(); // NOSONAR typescript:S1848 — constructor binds DOM event listeners; instantiation is the side effect

        console.log(
            `Invoice TypeScript App initialized with ${this.#handlers.length} core handlers`
        );
    }

    /**
     * Initialize Bootstrap tooltips — Bootstrap must be loaded before this runs.
     * Registration order in layout/invoice.php ensures bootstrap.bundle.js
     * executes before the IIFE, so (globalThis as any).bootstrap is available here.
     */
    private initializeTooltips(): void {
        const bs = (globalThis as any).bootstrap;
        if (!bs?.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            try {
                bs.Tooltip.getOrCreateInstance(element);
            } catch (error) {
                console.warn('Tooltip initialization failed:', error);
            }
        });
    }

    /**
     * Keep track of last taggable focused element
     */
    private initializeTaggableFocus(): void {
        document.addEventListener(
            'focus',
            (event: FocusEvent) => {
                const target = event.target as Element;
                if (target?.classList?.contains('taggable')) {
                    globalThis.lastTaggableClicked = target;
                }
            },
            true
        );
    }

    /**
     * Initialize fullpage loader functionality
     */
    private initializeFullpageLoader(): void {
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('ajax-loader')) {
                showFullpageLoader();
            }
            if (target.classList.contains('fullpage-loader-close')) {
                hideFullpageLoader();
            }
        });
    }
}

// Initialize the application when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const _app = new InvoiceApp();
        initStripePayment();
        initAmazonPayment();
        initBraintreePayment();
        initTelegramProviderPopup();
        initStreetOrder();
        initStepPopovers();
    });
} else {
    const _app = new InvoiceApp();
    initStripePayment();
    initAmazonPayment();
    initBraintreePayment();
    initTelegramProviderPopup();
    initStreetOrder();
    initStepPopovers();
}

// Export for potential external usage
export { InvoiceApp };
export { initInvIndex } from './inv-index.js';
export { initQuoteIndex } from './quote-index.js';
