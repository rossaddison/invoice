# Prometheus Menu Integration

A multi-tiered expandable Angular-inspired menu system integrated into the Performance section of the invoice layout for accessing Prometheus monitoring endpoints.

## Overview

The Prometheus Menu Integration provides seamless access to monitoring and metrics endpoints through an elegant, expandable submenu system within the main navigation Performance dropdown. The implementation uses Angular-inspired JavaScript architecture with smooth animations and responsive design.

## Features

### ✅ Multi-Tiered Menu System
- **Main Menu Item**: Expandable Prometheus Monitoring entry
- **Submenu Structure**: Three distinct monitoring endpoints
- **Visual Hierarchy**: Clear parent-child relationship with indentation
- **State Management**: Persistent expand/collapse state during session

### ✅ Angular-Inspired Architecture
- **Component-like Structure**: PrometheusMenuController class-based approach
- **Event Binding**: Modern event listener management
- **Lifecycle Management**: Proper initialization and cleanup
- **State Tracking**: Reactive state management for UI updates

### ✅ Smooth Animations
- **Expand/Collapse**: 300ms smooth transitions
- **Arrow Rotation**: Synchronized rotation with menu state
- **Slide Effects**: Items slide in/out with opacity changes
- **Hover Interactions**: 200ms transform and background animations

### ✅ Responsive Design
- **Mobile Optimization**: Adjusted spacing and layout for small screens
- **Touch Friendly**: Appropriate touch targets for mobile devices
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support
- **Cross-browser**: Compatible with modern browsers

## Implementation

### Location
- **File**: `resources/views/layout/invoice.php`
- **Section**: Performance dropdown menu
- **Position**: After existing performance diagnostic items
- **Visibility**: Available when debug mode is enabled (`$debugMode = true`)

### Menu Structure

#### Main Menu Item
```php
<div class="prometheus-main-item d-flex justify-content-between align-items-center" 
     onclick="togglePrometheusSubmenu()" 
     style="cursor: pointer; padding: 8px 12px; background: rgba(0,123,255,0.1); border-radius: 4px;">
    <span><i class="fas fa-chart-line"></i> Prometheus Monitoring</span>
    <i class="fas fa-chevron-down prometheus-arrow" id="prometheus-arrow"></i>
</div>
```

#### Submenu Items

| Item | Icon | Color | Description | Route |
|------|------|-------|-------------|-------|
| Dashboard | `fa-tachometer-alt` | Green (`#28a745`) | Real-time metrics | `prometheus/dashboard` |
| Raw Metrics | `fa-code` | Gray (`#6c757d`) | Prometheus format | `prometheus/metrics` |
| Health Check | `fa-heartbeat` | Blue (`#007bff`) | System status | `prometheus/health` |

### JavaScript Architecture

#### Core Controller Class

```javascript
class PrometheusMenuController {
    constructor() {
        this.isExpanded = false;
        this.animationDuration = 300;
        this.initializeEventListeners();
    }

    // Main Methods
    toggleSubmenu()           // Toggle expand/collapse state
    expandSubmenu()          // Handle expansion animation
    collapseSubmenu()        // Handle collapse animation
    handleKeyboard()         // Keyboard navigation support
    bindEvents()             // Event listener setup
}
```

#### Event Handling

1. **Click Events**
   - Main item click toggles submenu
   - Global function `togglePrometheusSubmenu()` for external access

2. **Keyboard Events**
   - **Escape**: Close expanded menu
   - **Enter/Space**: Toggle menu when focused
   - **Tab Navigation**: Proper focus management

3. **Hover Effects**
   - Submenu item hover animations
   - Background color transitions
   - Transform effects for visual feedback

### Animation System

#### Expansion Animation
```javascript
// Show submenu with smooth fade-in and slide-down
submenu.style.opacity = '0';
submenu.style.transform = 'translateY(-10px)';
submenu.style.transition = `all ${this.animationDuration}ms ease-in-out`;

requestAnimationFrame(() => {
    submenu.style.opacity = '1';
    submenu.style.transform = 'translateY(0)';
});
```

#### Visual State Changes
- **Arrow Rotation**: 180-degree rotation during expansion
- **Background Highlight**: Enhanced background color when expanded
- **Border Indicator**: Left border appears when active
- **Hover Effects**: Subtle transform and shadow on item hover

### Styling System

#### CSS Classes

```css
.prometheus-menu-container    // Main container
.prometheus-main-item        // Clickable main menu item
.prometheus-arrow           // Rotating chevron icon
.prometheus-submenu         // Hidden/shown submenu container
.prometheus-submenu-item    // Individual submenu items
```

#### Responsive Breakpoints

```css
@media (max-width: 768px) {
    // Mobile-specific adjustments
    // Hidden description text
    // Adjusted padding and margins
}
```

## Integration Details

### Bootstrap 5 Integration
- **DropdownItem::html()**: Custom HTML content within Bootstrap dropdown
- **Bootstrap Classes**: Proper integration with existing Bootstrap components
- **Icon System**: FontAwesome icons for visual consistency
- **Color Scheme**: Bootstrap color variables and utilities

### URL Generation
```php
$urlGenerator->generate('prometheus/dashboard')
$urlGenerator->generate('prometheus/metrics') 
$urlGenerator->generate('prometheus/health')
```

### Route Dependencies
Requires these routes to be configured in `config/common/routes/routes.php`:
- `prometheus/dashboard` → `PrometheusController::dashboard`
- `prometheus/metrics` → `PrometheusController::metrics`
- `prometheus/health` → `PrometheusController::health`

## Usage

### Accessing the Menu

1. **Navigate** to any page in the invoice application
2. **Enable Debug Mode** (ensure `$debugMode = true` in configuration)
3. **Locate Performance Dropdown** in the main navigation bar
4. **Click Performance** to open the dropdown menu
5. **Scroll Down** to find "Prometheus Monitoring" section
6. **Click** the Prometheus Monitoring item to expand submenu
7. **Select** desired monitoring endpoint from submenu

### Menu Interactions

#### Desktop Usage
- **Click**: Main item to toggle submenu
- **Hover**: Submenu items for visual feedback
- **Keyboard**: Tab to navigate, Enter/Space to activate, Escape to close

#### Mobile Usage
- **Touch**: Tap main item to toggle
- **Touch**: Tap submenu items to navigate
- **Scroll**: Natural scrolling within dropdown

### Endpoint Access

#### Dashboard
- **Purpose**: Complete metrics overview with charts and graphs
- **Format**: HTML dashboard interface  
- **Features**: Real-time updates, system information, health status

#### Raw Metrics
- **Purpose**: Prometheus-compatible metrics endpoint
- **Format**: Plain text metrics in Prometheus format
- **Usage**: For Prometheus server scraping, debugging

#### Health Check
- **Purpose**: System health and status information
- **Format**: JSON response with health indicators
- **Usage**: Monitoring systems, uptime checks, diagnostics

## Customization

### Animation Timing
```javascript
constructor() {
    this.animationDuration = 300; // Modify for faster/slower animations
}
```

### Visual Styling
```css
.prometheus-main-item {
    background: rgba(0,123,255,0.1); /* Change background color */
    border-radius: 4px;              /* Modify border radius */
    padding: 8px 12px;               /* Adjust padding */
}
```

### Color Scheme
Modify colors in the HTML structure:
```php
// Dashboard - Green
style="color: #28a745;"

// Raw Metrics - Gray  
style="color: #6c757d;"

// Health Check - Blue
style="color: #007bff;"
```

### Additional Menu Items
Add new submenu items by extending the HTML structure:
```php
<div class="prometheus-submenu-item">
    <a href="<?= $urlGenerator->generate('new/route') ?>" 
       class="dropdown-item d-flex align-items-center" 
       target="_blank">
        <i class="fas fa-new-icon me-2"></i>
        <span>New Item</span>
        <small class="text-muted ms-auto">Description</small>
    </a>
</div>
```

## Performance Considerations

### Optimization Features
- **Event Delegation**: Efficient event handling
- **RequestAnimationFrame**: Smooth animations using browser optimization
- **CSS Transitions**: Hardware-accelerated animations
- **Minimal DOM Queries**: Cached element references

### Memory Management
- **Event Cleanup**: Proper event listener management
- **Animation Cleanup**: Timeout cleanup for delayed operations
- **State Management**: Minimal memory footprint for state tracking

### Browser Compatibility
- **Modern Browsers**: Full feature support (Chrome, Firefox, Safari, Edge)
- **Fallback**: Graceful degradation on older browsers
- **Progressive Enhancement**: Core functionality without JavaScript

## Troubleshooting

### Common Issues

#### Menu Not Appearing
1. **Check Debug Mode**: Ensure `$debugMode = true`
2. **Verify Route Configuration**: Confirm Prometheus routes are registered
3. **Check JavaScript Errors**: Look for console errors preventing initialization

#### Animation Issues
1. **Browser Support**: Verify CSS transition support
2. **Performance**: Check for conflicting CSS transitions
3. **Timing**: Adjust `animationDuration` if animations seem slow/fast

#### Click Not Working
1. **Event Binding**: Ensure DOM is ready before initialization
2. **JavaScript Conflicts**: Check for other JavaScript interfering
3. **Element Selection**: Verify element IDs are unique

### Debug Mode

Enable console logging for troubleshooting:
```javascript
toggleSubmenu() {
    console.log('Toggling submenu, current state:', this.isExpanded);
    // ... rest of method
}
```

### CSS Debugging
Add temporary border to visualize menu structure:
```css
.prometheus-menu-container * {
    border: 1px solid red; /* Temporary debug borders */
}
```

## Security Considerations

### Access Control
- **Debug Mode Requirement**: Menu only visible when debug mode enabled
- **Route Protection**: Ensure Prometheus endpoints have appropriate access controls
- **External Links**: All monitoring links open in new tabs for security

### Content Security Policy
- **Inline Scripts**: Uses inline JavaScript (may require CSP adjustments)
- **External Resources**: FontAwesome icons should be whitelisted
- **Event Handlers**: Inline onclick handlers (consider moving to external scripts)

## Future Enhancements

### Potential Improvements
- **Configuration UI**: Admin panel for customizing menu items
- **Dynamic Loading**: AJAX loading of menu items based on available services
- **Themes**: Multiple color themes for different environments
- **Caching**: Menu state persistence across page loads

### Advanced Features
- **Real-time Status**: Live health indicators in menu items
- **Notifications**: Alert badges for critical system issues
- **Keyboard Shortcuts**: Global keyboard shortcuts for menu access
- **Context Menu**: Right-click context menu for additional options

## Related Files

### Implementation Files
- `resources/views/layout/invoice.php` (Lines 450-460, 940-1050)
- `src/Invoice/Prometheus/PrometheusController.php`
- `config/common/routes/routes.php` (Prometheus routes)

### Asset Dependencies
- **FontAwesome**: Icons for menu items
- **Bootstrap 5**: Dropdown and utility classes
- **CSS**: Custom styling for animations and responsive design

### Documentation
- `docs/PROMETHEUS_MENU_INTEGRATION.md` (This file)
- `docs/PROMETHEUS_INTEGRATION.md` (Related Prometheus setup)

## Version History

### v1.0.0 (2025-12-17)
- Initial implementation with multi-tiered expandable menu
- Angular-inspired JavaScript architecture
- Full responsive design and accessibility support
- Integration with Bootstrap 5 dropdown system
- Three monitoring endpoints: Dashboard, Raw Metrics, Health Check
- Smooth animations and hover effects
- Keyboard navigation support
- Mobile-optimized touch interactions

---

**Note**: This integration requires debug mode to be enabled and proper Prometheus route configuration. The menu provides intuitive access to monitoring functionality while maintaining the clean design of the existing Performance dropdown section.