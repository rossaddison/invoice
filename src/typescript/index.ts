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
import { initInvIndex } from './inv-index.js';
import { initQuoteIndex } from './quote-index.js';

/**
 * Initialize Invoice Application
 */
class InvoiceApp {
    readonly #createCreditHandler: CreateCreditHandler;
    readonly #quoteHandler: QuoteHandler;
    readonly #clientHandler: ClientHandler;
    readonly #invoiceHandler: InvoiceHandler;
    readonly #productHandler: ProductHandler;
    readonly #taskHandler: TaskHandler;
    readonly #salesOrderHandler: SalesOrderHandler;
    readonly #familyHandler: FamilyHandler;
    readonly #settingsHandler: SettingsHandler;

    constructor() {
        // Initialize handlers (stored as properties to keep event listeners active)
        this.#createCreditHandler = new CreateCreditHandler();
        this.#quoteHandler = new QuoteHandler();
        this.#clientHandler = new ClientHandler();
        this.#invoiceHandler = new InvoiceHandler();
        this.#productHandler = new ProductHandler();
        this.#taskHandler = new TaskHandler();
        this.#salesOrderHandler = new SalesOrderHandler();
        this.#familyHandler = new FamilyHandler();
        this.#settingsHandler = new SettingsHandler();

        this.initializeTooltips();
        this.initializeTaggableFocus();

        // Initialize enhanced scripts functionality
        initTooltips();
        initSimpleSelects();
        initPasswordMeter();

        // Set up fullpage loader handlers
        this.initializeFullpageLoader();

        console.log(
            'Invoice TypeScript App initialized with all core handlers: Quote, Client, Invoice, Product, Task, SalesOrder, Family, and Settings'
        );
    }

    /**
     * Initialize Bootstrap tooltips — Bootstrap must be loaded before this runs.
     * Registration order in layout/invoice.php ensures bootstrap.bundle.js
     * executes before the IIFE, so (window as any).bootstrap is available here.
     */
    private initializeTooltips(): void {
        const bs = (window as any).bootstrap;
        if (!bs?.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            try {
                bs.Tooltip.getOrCreateInstance(element as Element);
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
                    window.lastTaggableClicked = target;
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
        new InvoiceApp();
        initStripePayment();
        initAmazonPayment();
        initBraintreePayment();
        initTelegramProviderPopup();
        initStreetOrder();
        initStepPopovers();
    });
} else {
    new InvoiceApp();
    initStripePayment();
    initAmazonPayment();
    initBraintreePayment();
    initTelegramProviderPopup();
    initStreetOrder();
    initStepPopovers();
}

// Export for potential external usage
export { InvoiceApp, initInvIndex, initQuoteIndex };
