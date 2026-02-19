/**
 * Invoice Amount Magnifier - Pure Angular Implementation
 * Automatically magnifies invoice amounts on hover for better visibility
 */

class InvoiceAmountMagnifier {
  private magnificationFactor: number = 1.4;
  private animationDuration: number = 250;
  private observer?: MutationObserver;

  constructor() {
    this.initializeWhenReady();
  }

  private initializeWhenReady() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initialize());
    } else {
      this.initialize();
    }
  }

  private initialize() {
    this.attachMagnifiersToAmounts();
    this.setupMutationObserver();
  }

  private attachMagnifiersToAmounts() {
    // Target amount elements in invoice table
    const amountSelectors = [
      '.label.label-success', // Total amounts (positive)
      '.label.label-warning', // Zero amounts  
      '.label.label-danger'   // Paid amounts (incomplete)
    ];

    amountSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach((element: Element) => {
        const htmlElement = element as HTMLElement;
        
        if (this.isAmountElement(htmlElement) && !htmlElement.dataset.magnifierInitialized) {
          this.addMagnificationBehavior(htmlElement);
          htmlElement.dataset.magnifierInitialized = 'true';
        }
      });
    });
  }

  private isAmountElement(element: HTMLElement): boolean {
    const text = element.textContent?.trim() || '';
    // Check if content contains numbers with optional decimals and commas
    const amountPattern = /^[\d,]+\.?\d*$/;
    return amountPattern.test(text) && text.length > 0;
  }

  private addMagnificationBehavior(element: HTMLElement) {
    // Determine colors based on label type
    let borderColor = '#007bff';
    let bgColor = 'rgba(255, 255, 255, 0.95)';
    
    if (element.classList.contains('label-success')) {
      borderColor = '#28a745';
      bgColor = '#d4edda';
    } else if (element.classList.contains('label-warning')) {
      borderColor = '#ffc107';
      bgColor = '#fff3cd';
    } else if (element.classList.contains('label-danger')) {
      borderColor = '#dc3545';
      bgColor = '#f8d7da';
    }

    // Store original styles
    const computedStyle = globalThis.getComputedStyle(element);
    const originalStyles = {
      fontSize:        computedStyle.fontSize,
      fontWeight:      computedStyle.fontWeight,
      backgroundColor: computedStyle.backgroundColor,
      border:          computedStyle.border,
      borderRadius:    computedStyle.borderRadius,
      padding:         computedStyle.padding,
      zIndex:          computedStyle.zIndex,
      position:        computedStyle.position,
      transform:       computedStyle.transform,
      boxShadow:       computedStyle.boxShadow
    };

    // Set base styles
    element.style.transition = `all ${this.animationDuration}ms ease-in-out`;
    element.style.cursor = 'pointer';
    element.classList.add('amount-magnifiable');

    let isHovered = false;

    // Mouse enter event
    element.addEventListener('mouseenter', () => {
      if (!isHovered) {
        isHovered = true;
        this.applyMagnification(element, originalStyles, borderColor, bgColor);
      }
    });

    // Mouse leave event  
    element.addEventListener('mouseleave', () => {
      if (isHovered) {
        isHovered = false;
        this.removeMagnification(element, originalStyles);
      }
    });

    // Click event for mobile/touch devices
    element.addEventListener('click', (e) => {
      e.preventDefault();
      if (isHovered) {
        this.removeMagnification(element, originalStyles);
        isHovered = false;
      } else {
        this.applyMagnification(element, originalStyles, borderColor, bgColor);
        isHovered = true;
      }
    });
  }

  private applyMagnification(element: HTMLElement, originalStyles: any, borderColor: string, bgColor: string) {
    const currentFontSize = Number.parseFloat(originalStyles.fontSize);
    const newFontSize = currentFontSize * this.magnificationFactor;
    
    element.style.fontSize = `${newFontSize}px`;
    element.style.fontWeight = 'bold';
    element.style.backgroundColor = bgColor;
    element.style.border = `2px solid ${borderColor}`;
    element.style.borderRadius = '6px';
    element.style.padding = '8px 12px';
    element.style.zIndex = '1000';
    element.style.position = 'relative';
    element.style.transform = 'scale(1.1)';
    element.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
  }

  private removeMagnification(element: HTMLElement, originalStyles: any) {
    Object.keys(originalStyles).forEach(property => {
      (element.style as any)[property] = originalStyles[property];
    });
  }

  private setupMutationObserver() {
    this.observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
          // Re-initialize magnification for new elements
          setTimeout(() => {
            this.attachMagnifiersToAmounts();
          }, 100);
        }
      });
    });

    // Observe table container for dynamic content changes
    const tableContainer = document.querySelector('.table-responsive') || document.body;
    this.observer.observe(tableContainer, {
      childList: true,
      subtree: true
    });
  }

  public destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
  }
}

// Auto-initialize when script loads
const invoiceAmountMagnifier = new InvoiceAmountMagnifier();

// Export for manual control if needed
(globalThis as any).InvoiceAmountMagnifier = InvoiceAmountMagnifier;