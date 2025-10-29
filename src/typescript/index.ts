console.log("Invoice TypeScript bundle loaded");

// Main TypeScript entry point for Invoice Application
import { CreateCreditHandler } from './create-credit.js';
import { QuoteHandler } from './quote.js';
import { ClientHandler } from './client.js';
import { InvoiceHandler } from './invoice.js';
import { ProductHandler } from './product.js';
import { SalesOrderHandler } from './salesorder.js';
import { FamilyHandler } from './family.js';
import { SettingsHandler } from './settings.js';
import { initTooltips, initSimpleSelects, showFullpageLoader, hideFullpageLoader, initPasswordMeter } from './scripts.js';

/**
 * Initialize Invoice Application
 */
class InvoiceApp {
    private readonly _createCreditHandler: CreateCreditHandler;
    private readonly _quoteHandler: QuoteHandler;
    private readonly _clientHandler: ClientHandler;
    private readonly _invoiceHandler: InvoiceHandler;
    private readonly _productHandler: ProductHandler;
    private readonly _salesOrderHandler: SalesOrderHandler;
    private readonly _familyHandler: FamilyHandler;
    private readonly _settingsHandler: SettingsHandler;

    constructor() {
        // Initialize handlers (stored as properties to keep event listeners active)
        this._createCreditHandler = new CreateCreditHandler();
        this._quoteHandler = new QuoteHandler();
        this._clientHandler = new ClientHandler();
        this._invoiceHandler = new InvoiceHandler();
        this._productHandler = new ProductHandler();
        this._salesOrderHandler = new SalesOrderHandler();
        this._familyHandler = new FamilyHandler();
        this._settingsHandler = new SettingsHandler();

        this.initializeTooltips();
        this.initializeTaggableFocus();
        
        // Initialize enhanced scripts functionality
        initTooltips();
        initSimpleSelects();
        initPasswordMeter();
        
        // Set up fullpage loader handlers
        this.initializeFullpageLoader();

        console.log(
            'Invoice TypeScript App initialized with all core handlers: Quote, Client, Invoice, Product, SalesOrder, Family, and Settings'
        );
    }

    /**
     * Initialize Bootstrap tooltips
     */
    private initializeTooltips(): void {
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipElements.forEach(element => {
                    try {
                        new bootstrap.Tooltip(element as Element);
                    } catch (error) {
                        console.warn('Tooltip initialization failed:', error);
                    }
                });
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
    document.addEventListener('DOMContentLoaded', () => new InvoiceApp());
} else {
    new InvoiceApp();
}

// Export for potential external usage
export { InvoiceApp };
