# Family Commalist Number Picker

A powerful, interactive number selection interface for the Yii3 Invoice Management System's family comma list field.

## Overview

The Family Commalist Number Picker provides an intuitive UI for selecting numbers 1-200 to populate the `family_commalist` field. Instead of manually typing comma-separated values, users can click numbers in a visual grid interface.

## Features

### 🎯 Core Functionality
- **Interactive Number Grid**: Click to select/deselect numbers 1-200
- **Real-time Updates**: Automatically syncs selections with the textarea
- **Visual Feedback**: Selected numbers highlighted in green
- **Persistent State**: Parses existing comma list values on load

### 📱 User Experience
- **Pagination**: 50 numbers per page for optimal performance
- **Quick Ranges**: Preset buttons for common number ranges
- **Bulk Operations**: Select/deselect entire pages or clear all
- **Responsive Design**: Works seamlessly on desktop and mobile
- **Bootstrap Integration**: Consistent styling with existing UI

### ⚡ Performance
- **Efficient Rendering**: Only renders visible page numbers
- **Optimized DOM**: Minimal re-renders on state changes
- **Memory Efficient**: Uses Set data structure for fast lookups

## Usage

### Basic Usage

1. **Open Family Form**: Navigate to Add/Edit Family page
2. **Show Picker**: Click "Show Number Picker" button below the comma list field
3. **Select Numbers**: Click any numbers you want to include
4. **Auto-Update**: The textarea automatically updates with your selections
5. **Submit Form**: Save as normal - the picker integrates seamlessly

### Quick Range Selection

Use the preset range buttons for common scenarios:
- **1-10**: Small product lines
- **11-25**: Medium collections
- **26-50**: Standard ranges
- **51-100**: Large collections
- **101-150**: Extended ranges  
- **151-200**: Full range collections

### Bulk Operations

- **Select Page**: Add all 50 numbers on current page
- **Deselect Page**: Remove all numbers from current page  
- **Clear All**: Remove all selections

## Technical Implementation

### Architecture

```
TypeScript Module (family-commalist-picker.ts)
├── FamilyCommalistPicker Class
│   ├── State Management (Set<number>)
│   ├── Pagination Logic (50 per page)
│   ├── DOM Rendering
│   └── Event Handling
├── Integration Functions
│   ├── toggleCommalistPicker()
│   └── initializeCommalistPicker()
└── Global Window Interface
```

### Key Components

#### `FamilyCommalistPicker` Class
- **State Management**: Uses `Set<number>` for efficient number storage
- **Pagination**: Divides 200 numbers into 4 pages of 50 each  
- **DOM Manipulation**: Dynamic HTML generation with event binding
- **Form Integration**: Syncs with existing textarea element

#### Integration Layer
- **Global Functions**: Exposed via `window` object for HTML onclick handlers
- **Auto-initialization**: Registers on DOM ready
- **Form Compatibility**: Triggers change/input events for validation

### File Structure

```
src/typescript/
└── family-commalist-picker.ts    # Main picker implementation

public/assets/css/
└── family-commalist-picker.css   # Styling and responsive design

resources/views/invoice/family/
└── _form.php                     # Form integration HTML

src/Invoice/Asset/rebuild/js/
└── invoice-typescript-iife.js    # Compiled bundle
```

## API Reference

### Public Methods

#### `toggleNumber(num: number): void`
Toggles selection state of a specific number.

#### `selectRange(start: number, end: number): void`  
Selects all numbers within the specified range (inclusive).

#### `clearAll(): void`
Removes all selected numbers.

#### `selectPage(): void`
Selects all numbers on the current page.

#### `deselectPage(): void`
Deselects all numbers on the current page.

#### `goToPage(page: number): void`
Navigates to a specific page (1-4).

#### `nextPage(): void` / `prevPage(): void`
Navigate between pages.

### Global Functions

#### `window.toggleCommalistPicker(): void`
Shows/hides the number picker interface.

#### `window.picker: FamilyCommalistPicker | null`
Global reference to the active picker instance.

## Styling & Theming

### CSS Classes

- `.family-commalist-picker`: Main container
- `.picker-header`: Header with title and controls
- `.quick-ranges`: Range selection buttons
- `.numbers-grid`: Scrollable number grid
- `.number-buttons`: Grid layout for numbers
- `.number-btn`: Individual number buttons
- `.pagination-controls`: Page navigation
- `.selected-preview`: Selected numbers display

### Responsive Breakpoints

- **Desktop**: Full grid layout with 50px buttons
- **Mobile** (`@media max-width: 768px`): Compact 45px buttons, stacked controls

### Theme Support

- **Light Mode**: Default Bootstrap styling
- **Dark Mode**: CSS variables for dark theme compatibility

## Integration Guide

### Adding to New Forms

1. **Include CSS**:
```html
<link rel="stylesheet" href="/assets/css/family-commalist-picker.css">
```

2. **Add HTML Structure**:
```php
<div class="mt-2">
    <button type="button" class="btn btn-outline-primary btn-sm" 
            onclick="toggleCommalistPicker()" id="toggle-picker-btn">
        <i class="bi bi-grid-3x3-gap"></i> Show Number Picker
    </button>
</div>

<div id="commalist-picker-container" class="mt-3" style="display: none;">
    <div class="alert alert-info">
        <small><i class="bi bi-info-circle"></i> Click numbers below to add them to your comma list.</small>
    </div>
</div>
```

3. **Ensure TypeScript Bundle**: Include the compiled `invoice-typescript-iife.js`

### Customization

#### Changing Number Range
Modify the constructor to change the total numbers:
```typescript
// Change from 200 to 500 numbers
this.numbers = Array.from({ length: 500 }, (_, i) => i + 1);
this.totalPages = 10; // 500 / 50
```

#### Custom Page Size
Adjust `numbersPerPage` property:
```typescript
private numbersPerPage: number = 25; // Smaller pages
```

#### Custom Styling
Override CSS variables:
```css
.family-commalist-picker {
    --primary-color: #your-brand-color;
    --success-color: #your-success-color;
}
```

## Browser Compatibility

- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript Features**: ES2024 features (compiled to compatible code)
- **CSS Features**: CSS Grid, Flexbox, CSS Custom Properties

## Performance Considerations

- **Memory**: ~2KB for storing 200 numbers in Set
- **DOM Elements**: Maximum 50 buttons rendered at once
- **Event Handlers**: Efficient onclick delegation
- **Bundle Size**: Adds ~8KB to TypeScript bundle

## Troubleshooting

### Common Issues

**Picker doesn't appear**
- Check if TypeScript bundle loaded successfully  
- Verify `family_commalist` textarea element exists
- Check browser console for JavaScript errors

**Numbers don't update textarea**
- Ensure textarea has correct ID (`family_commalist`)
- Check if form validation is interfering
- Verify change/input events are firing

**Styling issues**
- Confirm Bootstrap 5 is loaded
- Check if CSS file is included
- Verify no CSS conflicts with existing styles

### Debug Mode

Enable debug logging:
```javascript
// In browser console
window.picker.debug = true;
```

## Changelog

### v1.0.0 (December 2025)
- ✨ Initial release
- 🎯 Number selection grid (1-200)
- 📱 Responsive design
- ⚡ Pagination system
- 🎨 Bootstrap 5 integration
- 🔄 Real-time textarea sync

## Future Enhancements

### Planned Features
- **Custom Ranges**: User-defined number ranges
- **Search/Filter**: Find specific numbers quickly  
- **Keyboard Navigation**: Arrow keys and shortcuts
- **Undo/Redo**: Action history
- **Export/Import**: Save/load number sets
- **Templates**: Predefined number patterns

### Possible Improvements
- **Virtual Scrolling**: Handle larger number ranges
- **Multi-select Modes**: Different selection patterns
- **Accessibility**: Enhanced ARIA support
- **Animations**: Smooth transitions
- **Touch Gestures**: Swipe navigation on mobile

## Contributing

When contributing to the Family Commalist Picker:

1. **Test thoroughly** across different browsers
2. **Maintain TypeScript types** for better IDE support  
3. **Follow existing code style** and patterns
4. **Update documentation** for new features
5. **Consider accessibility** implications
6. **Test mobile responsiveness**

## License

Part of the Yii3 Invoice Management System - see main project license.