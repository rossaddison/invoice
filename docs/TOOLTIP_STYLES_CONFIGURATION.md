# Tooltip Styles Configuration

## Overview

This document describes the location and configuration of tooltip styles used throughout the invoice application. Tooltips provide contextual information when users hover over elements, and their styling is centrally managed for consistency.

## File Location

**Primary CSS File:**
```
src/Invoice/Asset/invoice/css/style.css
```

**Asset Bundle Configuration:**
```
src/Invoice/Asset/InvoiceAsset.php
```

## Tooltip CSS Classes

### `.tooltip` (Lines 4066-4088)

The main tooltip container class that controls the overall tooltip appearance and behavior.

**Key Properties:**
```css
.tooltip {
    position: absolute;
    z-index: 1070;
    display: block;
    font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-style: normal;
    font-weight: 400;
    line-height: 1.428571429;
    text-align: left;
    font-size: 18px;  /* Enhanced from 12px for better readability */
    filter: alpha(opacity=0);
    opacity: 0;
}
```

**Notable Features:**
- High z-index (1070) ensures tooltips appear above other content
- Opacity: 0 by default (shown via `.tooltip.in` state)
- Font size enlarged to 18px for improved visibility

### `.tooltip-inner` (Lines 4165-4173)

The inner content container of the tooltip that holds the actual text.

**Key Properties:**
```css
.tooltip-inner {
    max-width: 300px;     /* Increased from 200px */
    padding: 6px 12px;    /* Increased from 3px 8px */
    color: #fff;
    text-align: center;
    background-color: #000;
    border-radius: 4px;
    font-size: 18px;      /* Added for enhanced readability */
    font-weight: 500;     /* Added for better visibility */
}
```

**Notable Features:**
- Black background with white text for high contrast
- Max-width increased to accommodate larger font size
- Padding doubled for better spacing
- Font weight set to 500 (medium) for improved legibility

### `.tooltip-arrow` (Lines 4173-4180)

Defines the directional arrow that points from the tooltip to the target element.

**Key Properties:**
```css
.tooltip-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border-color: transparent;
    border-style: solid;
}
```

Additional arrow positioning classes exist for different tooltip orientations:
- `.tooltip.top .tooltip-arrow`
- `.tooltip.bottom .tooltip-arrow`
- `.tooltip.left .tooltip-arrow`
- `.tooltip.right .tooltip-arrow`
- `.tooltip.top-left .tooltip-arrow`
- `.tooltip.top-right .tooltip-arrow`
- `.tooltip.bottom-left .tooltip-arrow`
- `.tooltip.bottom-right .tooltip-arrow`

## How Tooltips Are Loaded

### Asset Bundle Registration

The tooltip styles are loaded globally through the `InvoiceAsset` bundle:

```php
// src/Invoice/Asset/InvoiceAsset.php

public string|null $basePath = '@assets';
public string|null $baseUrl = '@assetsUrl';
public string|null $sourcePath = '@invoice-asset';

public array $css = [
    'invoice/css/style.css',  // Contains tooltip styles
    'invoice/css/yii3i.css',
    // ... other CSS files
];
```

### Layout Integration

The asset bundle is registered in layout files (e.g., `layout/invoice.php`), making tooltip styles available application-wide:

```php
<?php $this->beginPage() ?>
<!-- Asset bundle registration occurs here -->
<?php $this->endPage() ?>
```

## Recent Enhancements (January 2026)

The following improvements were made to enhance tooltip readability:

### Font Size Increase
- **Before:** 12px (base tooltip) / no explicit size (tooltip-inner)
- **After:** 18px (both `.tooltip` and `.tooltip-inner`)
- **Improvement:** 50% larger text for better visibility

### Spacing Improvements
- **Max-width:** 200px → 300px (50% increase)
- **Padding:** 3px 8px → 6px 12px (doubled)
- **Benefit:** More comfortable reading experience with better text spacing

### Typography Enhancement
- **Font-weight:** Added 500 (medium weight) to `.tooltip-inner`
- **Benefit:** Improved text visibility and legibility

## Local Tooltip Style Overrides

Some views have local tooltip style overrides for specific use cases:

### Invoice Index (`resources/views/invoice/inv/index.php`)
```css
.tooltip.show .tooltip-inner {
    font-size: 2em !important;
    font-weight: bold !important;
    padding: 10px 15px !important;
}
```

### Quote Index (`resources/views/invoice/quote/index.php`)
```css
.tooltip.show .tooltip-inner {
    font-size: 2em !important;
    font-weight: bold !important;
    padding: 10px 15px !important;
}
```

**Note:** These view-specific overrides use `!important` to ensure they take precedence over global styles when tooltips are in the `.show` state.

## Modifying Tooltip Styles

### Global Changes
To modify tooltip styles globally across the entire application:

1. Edit `src/Invoice/Asset/invoice/css/style.css`
2. Locate the tooltip classes (lines 4066-4180)
3. Modify the desired properties
4. Clear browser cache to see changes

### View-Specific Changes
To modify tooltip styles for a specific view only:

1. Add a `<style>` block in the view file
2. Use specific selectors with `!important` if needed to override global styles
3. Target `.tooltip.show .tooltip-inner` for active tooltip states

## Browser Compatibility

The tooltip styles use standard CSS properties with legacy browser support:

- **Opacity:** Uses both `filter: alpha(opacity)` (IE8-9) and standard `opacity`
- **Font-family:** System font stack for native appearance across platforms
- **Border-radius:** Widely supported modern property

## Best Practices

### When Creating Tooltips
1. Keep tooltip text concise (max 2-3 lines)
2. Use sentence case for tooltip content
3. Ensure tooltips don't obscure important UI elements
4. Test tooltip positioning on different screen sizes

### When Modifying Styles
1. Test changes across different browsers
2. Verify tooltip readability on various backgrounds
3. Ensure sufficient contrast for accessibility (WCAG 2.1)
4. Consider mobile/touch device behavior

## Related Documentation

- [TypeScript Build Process](TYPESCRIPT_BUILD_PROCESS.md) - Asset compilation
- [NetBeans IDE Guide](NETBEANS_IDE25_GUIDE.md) - Development environment setup

## Troubleshooting

### Tooltips Not Appearing
- Check browser console for JavaScript errors
- Verify Bootstrap JavaScript is loaded
- Ensure `data-bs-toggle="tooltip"` attribute is present
- Verify tooltip initialization in JavaScript

### Style Changes Not Visible
- Clear browser cache (Ctrl+F5)
- Check for local style overrides with `!important`
- Verify InvoiceAsset.php is registered in the layout
- Check for CSS compilation errors

### Font Size Too Large/Small
- Modify `.tooltip` and `.tooltip-inner` font-size properties
- Consider responsive font sizes using `clamp()` for fluid scaling
- Test on various screen resolutions before committing changes

---

**Last Updated:** January 2, 2026  
**Maintainer:** Development Team  
**Related Files:**
- `src/Invoice/Asset/invoice/css/style.css` (lines 4066-4180)
- `src/Invoice/Asset/InvoiceAsset.php`
- `resources/views/invoice/inv/index.php`
- `resources/views/invoice/quote/index.php`
