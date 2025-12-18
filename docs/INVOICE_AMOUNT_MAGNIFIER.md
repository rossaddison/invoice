# Invoice Amount Magnifier

A pure Angular/JavaScript solution for enhancing invoice amount visibility through interactive magnification effects.

## Overview

The Invoice Amount Magnifier automatically detects and enhances numeric amounts in invoice tables, providing visual magnification on hover/click for improved readability and user experience.

## Features

### ✅ Smart Detection
- **Automatic targeting**: Scans for amount elements with specific CSS classes
- **Numeric validation**: Only magnifies elements containing valid numeric amounts
- **Pattern matching**: Uses regex pattern `^[\d,]+\.?\d*$` to identify amounts
- **Duplicate prevention**: Prevents re-initialization with `data-magnifier-initialized` attribute

### ✅ Visual Enhancement
- **Font magnification**: 1.4x size increase on interaction
- **Color-coded styling**: Context-aware colors based on amount type
- **Scale transformation**: 110% size increase with shadow effects  
- **Smooth animations**: 250ms CSS transitions for all effects

### ✅ Interactive Functionality
- **Hover effects**: Desktop mouse enter/leave magnification
- **Mobile support**: Touch/click to toggle magnification on mobile devices
- **Dynamic content**: Mutation observer handles AJAX-loaded content
- **Z-index management**: Magnified amounts appear above other elements

## Implementation

### Target Elements

The magnifier automatically targets invoice amounts with these CSS classes:

| Class | Purpose | Color Scheme |
|-------|---------|--------------|
| `.label.label-success` | Positive total amounts | Green border (`#28a745`) |
| `.label.label-warning` | Zero amounts | Yellow border (`#ffc107`) |  
| `.label.label-danger` | Incomplete payments | Red border (`#dc3545`) |

### Animation Effects

#### Magnification Properties
- **Font Size**: Increases by 40% (1.4x multiplier)
- **Font Weight**: Changes to `bold`
- **Border**: 2px solid with context color
- **Border Radius**: 6px for rounded corners
- **Padding**: 8px horizontal, 12px vertical
- **Transform**: `scale(1.1)` for 110% size increase
- **Box Shadow**: `0 4px 12px rgba(0,0,0,0.15)`
- **Z-Index**: 1000 for proper layering

#### Transition Timing
- **Duration**: 250ms for all animations
- **Easing**: `ease-in-out` for smooth transitions
- **Properties**: All style changes are animated

### Code Structure

#### Core Class: `InvoiceAmountMagnifier`

```javascript
class InvoiceAmountMagnifier {
    constructor() {
        this.magnificationFactor = 1.4;
        this.animationDuration = 250;
        this.initialize();
    }
    
    // Main initialization methods
    initialize()
    attachMagnifiersToAmounts()
    setupMutationObserver()
    
    // Element detection and validation
    isAmountElement(element)
    
    // Interaction handling
    addMagnificationBehavior(element)
    applyMagnification(element, originalStyles, borderColor, bgColor)
    removeMagnification(element, originalStyles)
}
```

#### Event Handling

1. **Mouse Events** (Desktop)
   - `mouseenter`: Apply magnification
   - `mouseleave`: Remove magnification

2. **Click Events** (Mobile/Touch)
   - `click`: Toggle magnification state
   - Prevents default action to avoid navigation

3. **Mutation Observer** (Dynamic Content)
   - Monitors DOM changes in table container
   - Reinitializes magnifiers for new elements
   - 100ms delay to ensure content is fully rendered

## Integration

### Location
- **File**: `resources/views/invoice/inv/index.php`
- **Position**: End of file, after modal declarations
- **Container**: `<div id="angular-amount-magnifier-app">`

### Dependencies
- **None**: Pure JavaScript implementation
- **Framework**: Works independently of Angular framework
- **CSS**: Self-contained styles included inline

### Auto-Initialization
```javascript
document.addEventListener('DOMContentLoaded', function() {
    new InvoiceAmountMagnifier();
});
```

## Performance Considerations

### Optimization Features
- **Event delegation**: Efficient event handling per element
- **Mutation observer**: Only monitors relevant DOM changes
- **Style preservation**: Stores original styles for restoration
- **Throttled reinitalization**: 100ms delay for dynamic content

### Memory Management
- **Original styles caching**: Prevents repeated style calculations
- **Observer cleanup**: Proper mutation observer lifecycle management
- **Event listener scope**: Properly scoped event handlers

## Customization

### Configuration Options

You can modify these properties in the constructor:

```javascript
constructor() {
    this.magnificationFactor = 1.4;  // Font size multiplier (1.4 = 40% increase)
    this.animationDuration = 250;    // Animation duration in milliseconds
}
```

### Color Scheme Customization

Modify the color assignments in `addMagnificationBehavior()`:

```javascript
if (element.classList.contains('label-success')) {
    borderColor = '#28a745';  // Green for positive amounts
    bgColor = '#d4edda';      // Light green background
}
```

### Animation Timing

Adjust transition properties:

```javascript
element.style.transition = `all ${this.animationDuration}ms ease-in-out`;
```

## Browser Compatibility

### Supported Features
- **CSS Transitions**: All modern browsers
- **Mutation Observer**: IE11+ and all modern browsers  
- **Event Listeners**: Universal browser support
- **CSS Transform**: All modern browsers with prefixes

### Fallback Behavior
- Graceful degradation on unsupported browsers
- Core functionality maintained without animations
- No JavaScript errors on legacy browsers

## Troubleshooting

### Common Issues

#### Magnifier Not Working
1. Check browser console for JavaScript errors
2. Verify target elements have correct CSS classes
3. Ensure DOM is fully loaded before initialization

#### Performance Issues
1. Monitor mutation observer frequency
2. Check for excessive DOM changes
3. Verify event listener efficiency

#### Style Conflicts
1. Check CSS specificity issues
2. Verify z-index layering
3. Ensure transition properties aren't overridden

### Debug Mode

Add console logging for debugging:

```javascript
isAmountElement(element) {
    const text = element.textContent?.trim() || '';
    const amountPattern = /^[\d,]+\.?\d*$/;
    const isAmount = amountPattern.test(text) && text.length > 0;
    console.log(`Element "${text}" is amount: ${isAmount}`); // Debug line
    return isAmount;
}
```

## Future Enhancements

### Potential Improvements
- **Keyboard navigation**: Arrow key navigation between amounts
- **Accessibility**: ARIA labels for screen readers
- **Animation presets**: Multiple animation styles
- **Touch gestures**: Swipe gestures for mobile
- **Configuration UI**: Admin panel for customization

### Integration Opportunities
- **Angular Components**: Full Angular directive implementation
- **Service Worker**: Offline functionality
- **Progressive Enhancement**: Feature detection and fallbacks
- **Analytics**: Usage tracking and optimization

## Related Files

### Angular Implementation
- `angular/src/app/amount-magnifier.directive.ts`
- `angular/src/app/invoice-amount-magnifier.service.ts` 
- `angular/src/app/invoice-amounts.component.ts`

### PHP Integration
- `resources/views/invoice/inv/index.php` (Lines 1177-1300)
- `src/Invoice/BaseController.php` (Flash message integration)

### Documentation
- `docs/INVOICE_AMOUNT_MAGNIFIER.md` (This file)

## Version History

### v1.0.0 (2025-12-17)
- Initial implementation
- Pure JavaScript/Angular solution
- Smart amount detection
- Color-coded magnification effects
- Mobile and desktop support
- Mutation observer for dynamic content
- Integration with inv/index.php

---

**Note**: This magnifier is designed specifically for invoice amount elements and automatically integrates with the existing Yii3 Bootstrap5 label system.