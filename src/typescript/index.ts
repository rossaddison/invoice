// Main TypeScript entry point for Invoice Application
import { CreateCreditHandler } from './create-credit.js';

/**
 * Initialize Invoice Application
 */
class InvoiceApp {
  private readonly createCreditHandler: CreateCreditHandler;

  constructor() {
    // Initialize handlers
    this.createCreditHandler = new CreateCreditHandler();
    this.initializeTooltips();
    this.initializeTaggableFocus();
    
    console.log('Invoice TypeScript App initialized');
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