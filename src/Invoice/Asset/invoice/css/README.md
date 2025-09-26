# InvoicePlane CSS Reorganization

This directory contains a proposed reorganization of the InvoicePlane CSS architecture, breaking down the monolithic `style.css` file into a more maintainable and modular structure.

## File Structure

The CSS has been organized into 6 logical files:

### 1. `variables.css` - CSS Custom Properties
**Purpose**: Centralized design tokens and configuration
- Color palette and theme colors
- Typography settings (fonts, sizes, line heights)
- Spacing scale and sizing variables
- Border radius, shadows, and z-index scale
- Component-specific variables (buttons, forms, tables)
- Dark mode variable overrides

**Import first** - All other files depend on these variables.

### 2. `base.css` - Foundation Styles
**Purpose**: Reset, normalize, and base element styling
- CSS normalize/reset rules
- Basic HTML element styling (typography, forms, tables)
- Global box-sizing and focus management
- Print media base styles

**Import second** - Provides the foundation for all components.

### 3. `layout.css` - Structural Layout
**Purpose**: Page layout, grid system, and structural patterns
- Main layout structure (sidebar, content areas, header)
- Navigation layout and responsive behavior
- Grid system and containers
- Flexbox layouts and positioning utilities

**Import third** - Establishes the page structure.

### 4. `components.css` - UI Components
**Purpose**: Reusable UI component styles
- Buttons and form controls
- Tables and data display components
- Modals, alerts, and notifications
- Labels, badges, and status indicators
- Navigation components (dropdowns, tabs)
- Progress bars and loaders

**Import fourth** - Builds the interactive elements.

### 5. `utilities.css` - Utility Classes
**Purpose**: Single-purpose utility classes
- Spacing utilities (margins, padding)
- Text utilities (alignment, color, weight)
- Display and visibility utilities
- Position and float utilities
- Border and sizing utilities
- Flexbox utilities

**Import fifth** - Provides modifier classes.

### 6. `overrides.css` - Framework Overrides
**Purpose**: Third-party library customizations and overrides
- Bootstrap component overrides
- Datepicker customizations
- Dark theme specific overrides
- Print media overrides
- Framework-specific accessibility improvements

**Import last** - Applies final customizations and overrides.

## Recommended Import Order

When implementing this structure, import the files in this order:

```css
/* 1. Variables first - provides design tokens */
@import 'variables.css';

/* 2. Base styles - foundation and reset */
@import 'base.css';

/* 3. Layout - structural patterns */
@import 'layout.css';

/* 4. Components - UI building blocks */
@import 'components.css';

/* 5. Utilities - modifier classes */
@import 'utilities.css';

/* 6. Overrides - final customizations */
@import 'overrides.css';
```

Or in an asset bundle configuration (PHP):

```php
public array $css = [
    'invoice/css/variables.css',
    'invoice/css/base.css',
    'invoice/css/layout.css',
    'invoice/css/components.css',
    'invoice/css/utilities.css',
    'invoice/css/overrides.css',
    // ... other CSS files
];
```

## Migration Status

This is a **demonstration and planning** version. The original `style.css` file has not been modified and remains fully functional.

### What's Been Migrated
- Core CSS variables extracted from SCSS files
- Base normalize/reset styles from style.css
- Key layout components (sidebar, main content, navigation)
- Primary UI components (buttons, forms, tables, labels)
- Common utility classes
- Datepicker and framework overrides

### What Still Needs Migration
Each file contains TODO comments indicating areas that need further work:

- **variables.css**: Additional design tokens for datepicker, modals, alerts
- **base.css**: Complete typography elements, additional form types
- **layout.css**: Full Bootstrap grid system, responsive utilities
- **components.css**: Complete Bootstrap components (modals, navigation, cards)
- **utilities.css**: Complete spacing scale, additional responsive utilities
- **overrides.css**: Select2, additional Bootstrap overrides

## Original Source Mapping

- **Variables**: `src/Invoice/Asset/invoice/scss/_ip_variables.scss`
- **Base styles**: `src/Invoice/Asset/invoice/css/style.css` (lines 1-300, normalize section)
- **Layout**: `src/Invoice/Asset/invoice/css/style.css` (layout sections ~8280-8340)
- **Components**: `src/Invoice/Asset/invoice/css/style.css` (component sections throughout)
- **Utilities**: `src/Invoice/Asset/invoice/css/style.css` (utility sections)
- **Overrides**: `src/Invoice/Asset/invoice/css/style.css` (datepicker ~7630-8061) and `src/Invoice/Asset/invoiceDark/sass/_custom_styles.scss`

## Benefits of This Structure

1. **Maintainability**: Easier to find and modify specific types of styles
2. **Modularity**: Can load only needed components for different pages
3. **Collaboration**: Multiple developers can work on different files simultaneously
4. **Performance**: Can optimize loading order and critical CSS
5. **Debugging**: Easier to isolate issues to specific functional areas
6. **Documentation**: Each file has a clear, single responsibility

## Implementation Strategy

1. **Phase 1**: Test this structure alongside existing CSS (non-breaking)
2. **Phase 2**: Gradually migrate remaining styles from `style.css`
3. **Phase 3**: Update asset bundles to use new structure
4. **Phase 4**: Remove original `style.css` after thorough testing

## Dark Theme Support

The reorganized structure includes improved dark theme support:
- CSS custom properties with dark mode overrides in `variables.css`
- Dark theme specific overrides in `overrides.css`
- Existing dark theme styles from `invoiceDark` integrated appropriately

## Browser Support

This structure uses CSS custom properties (CSS variables) which are supported in:
- Chrome 49+
- Firefox 31+
- Safari 9.1+
- Edge 16+

For older browsers, consider using PostCSS with a custom properties plugin to provide fallbacks.