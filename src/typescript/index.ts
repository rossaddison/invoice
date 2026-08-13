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
import { initAdyenPayment } from './payment-adyen.js';
import { initBacsQuickPay } from './bacs-quickpay.js';
import { initTelegramProviderPopup } from './telegram-providers.js';
import { initStreetOrder } from './family-street-order.js';
import { initStepPopovers } from './google-translate-popover.js';
import { AllowanceChargeToggleHandler } from './allowance-charge-toggle.js';
import { initFlashMessageTimer } from './flash-message-timer.js';
import { initFamilyFormValidation } from './family-form-validation.js';
import { initDataActions } from './data-actions.js';
import { initHtmxHooks } from './htmx-hooks.js';
import { initCustomFieldPosition } from './customfield-position.js';
import { initJavascriptAnalysisFaq, initCodeceptionChecklistFaq } from './faq-pages.js';
import { initInvIndex } from './inv-index.js';
import { initQuoteIndex } from './quote-index.js';
import { initSalesOrderIndex } from './salesorder-index.js';
import { initE164PhoneFields } from './phone-e164.js';
import { initHomeCareOffline } from './homecare-offline.js';

declare global {
    // var (not `interface Window`) — see htmx.ts for why.
    var NProgress:
        | {
              start(): void;
              done(): void;
          }
        | undefined;
}

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

        // CSP-motivated migrations off inline <script>/onclick — each no-ops
        // on pages without its target elements, so safe to call everywhere.
        initFlashMessageTimer();
        initFamilyFormValidation();
        initDataActions();
        initHtmxHooks();
        initCustomFieldPosition();
        initJavascriptAnalysisFaq();
        initCodeceptionChecklistFaq();
        initE164PhoneFields();
        globalThis.NProgress?.start();
        globalThis.NProgress?.done();
        if (document.getElementById('table-invoice')) {
            initInvIndex();
        }
        if (document.getElementById('table-invoice-guest')) {
            initInvIndex('table-invoice-guest', 'inv-guest-filter-config');
        }
        initHomeCareOffline(); // no-ops unless the worker-only download button is present
        if (document.getElementById('table-quote')) {
            initQuoteIndex();
        }
        if (document.getElementById('table-quote-guest')) {
            initQuoteIndex('table-quote-guest');
        }
        if (document.getElementById('table-salesorder')) {
            initSalesOrderIndex();
        }
        if (document.getElementById('table-salesorder-guest')) {
            initSalesOrderIndex('table-salesorder-guest');
        }

        console.log(
            `Invoice TypeScript App initialized with ${this.#handlers.length} core handlers`
        );
    }

    /**
     * Initialize Bootstrap tooltips — Bootstrap must be loaded before this runs.
     * Registration order in layout/invoice.php ensures bootstrap.bundle.js
     * executes before the IIFE, so globalThis.bootstrap is available here.
     */
    private initializeTooltips(): void {
        const bs = globalThis.bootstrap;
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
        initAdyenPayment();
        initBacsQuickPay();
        initTelegramProviderPopup();
        initStreetOrder();
        initStepPopovers();
    });
} else {
    const _app = new InvoiceApp();
    initStripePayment();
    initAmazonPayment();
    initBraintreePayment();
    initAdyenPayment();
    initBacsQuickPay();
    initTelegramProviderPopup();
    initStreetOrder();
    initStepPopovers();
}

// Export for potential external usage
export { InvoiceApp, initInvIndex, initQuoteIndex, initSalesOrderIndex };
