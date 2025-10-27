// Main TypeScript entry point for Invoice Application
import { CreateCreditHandler } from './create-credit.js';
import { QuoteHandler } from './quote.js';
import { ClientHandler } from './client.js';
import { InvoiceHandler } from './invoice.js';

/**
 * Initialize Invoice Application
 */
class InvoiceApp {
  private readonly _createCreditHandler: CreateCreditHandler;
  private readonly _quoteHandler: QuoteHandler;
  private readonly _clientHandler: ClientHandler;
  private readonly _invoiceHandler: InvoiceHandler;

  constructor() {
    // Initialize handlers (stored as properties to keep event listeners active)
    this._createCreditHandler = new CreateCreditHandler();
    this._quoteHandler = new QuoteHandler();
    this._clientHandler = new ClientHandler();
    this._invoiceHandler = new InvoiceHandler();
    
    this.initializeTooltips();
    this.initializeTaggableFocus();
    
    console.log('Invoice TypeScript App initialized with Quote, Client, and Invoice handlers');
  }

  /**
   * Initialize Bootstrap tooltips
   */
  private initializeTooltips(): void {
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach((element) => {
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
    document.addEventListener('focus', (event: FocusEvent) => {
      const target = event.target as Element;
      if (target?.classList?.contains('taggable')) {
        window.lastTaggableClicked = target;
      }
    }, true);
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