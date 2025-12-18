import { Directive, ElementRef, HostListener, Input, Renderer2, OnInit } from '@angular/core';

@Directive({
  selector: '[appAmountMagnifier]',
  standalone: true
})
export class AmountMagnifierDirective implements OnInit {
  @Input() magnificationFactor: number = 1.5;
  @Input() animationDuration: number = 300;
  @Input() backgroundColor: string = 'rgba(255, 255, 255, 0.95)';
  @Input() borderColor: string = '#007bff';
  
  private originalStyles: { [key: string]: string } = {};
  private isHovered: boolean = false;

  constructor(
    private elementRef: ElementRef,
    private renderer: Renderer2
  ) {}

  ngOnInit() {
    this.initializeStyles();
  }

  private initializeStyles() {
    const element = this.elementRef.nativeElement;
    
    // Store original styles
    const computedStyle = window.getComputedStyle(element);
    this.originalStyles = {
      fontSize: computedStyle.fontSize,
      fontWeight: computedStyle.fontWeight,
      backgroundColor: computedStyle.backgroundColor,
      border: computedStyle.border,
      borderRadius: computedStyle.borderRadius,
      padding: computedStyle.padding,
      zIndex: computedStyle.zIndex,
      position: computedStyle.position,
      transform: computedStyle.transform,
      boxShadow: computedStyle.boxShadow
    };

    // Set base transition
    this.renderer.setStyle(element, 'transition', `all ${this.animationDuration}ms ease-in-out`);
    this.renderer.setStyle(element, 'cursor', 'pointer');
    this.renderer.addClass(element, 'amount-magnifiable');
  }

  @HostListener('mouseenter')
  onMouseEnter() {
    if (!this.isHovered) {
      this.isHovered = true;
      this.applyMagnification();
    }
  }

  @HostListener('mouseleave')
  onMouseLeave() {
    if (this.isHovered) {
      this.isHovered = false;
      this.removeMagnification();
    }
  }

  @HostListener('click')
  onClick() {
    // Toggle magnification on click for mobile/touch devices
    if (this.isHovered) {
      this.removeMagnification();
      this.isHovered = false;
    } else {
      this.applyMagnification();
      this.isHovered = true;
    }
  }

  private applyMagnification() {
    const element = this.elementRef.nativeElement;
    
    // Calculate new font size
    const currentFontSize = parseFloat(this.originalStyles.fontSize);
    const newFontSize = currentFontSize * this.magnificationFactor;
    
    // Apply magnified styles
    this.renderer.setStyle(element, 'font-size', `${newFontSize}px`);
    this.renderer.setStyle(element, 'font-weight', 'bold');
    this.renderer.setStyle(element, 'background-color', this.backgroundColor);
    this.renderer.setStyle(element, 'border', `2px solid ${this.borderColor}`);
    this.renderer.setStyle(element, 'border-radius', '6px');
    this.renderer.setStyle(element, 'padding', '8px 12px');
    this.renderer.setStyle(element, 'z-index', '1000');
    this.renderer.setStyle(element, 'position', 'relative');
    this.renderer.setStyle(element, 'transform', 'scale(1.1)');
    this.renderer.setStyle(element, 'box-shadow', '0 4px 12px rgba(0,0,0,0.15)');
  }

  private removeMagnification() {
    const element = this.elementRef.nativeElement;
    
    // Restore original styles
    Object.keys(this.originalStyles).forEach(property => {
      this.renderer.setStyle(element, property, this.originalStyles[property]);
    });
  }
}