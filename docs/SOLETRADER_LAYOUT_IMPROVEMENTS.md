# Soletrader Layout Improvements

## Overview

The soletrader layout template (`resources/views/layout/templates/soletrader/main.php`) received six visual improvements to the navbar, brand, nav icons, footer, and active-state indicator. All changes are purely presentational — no routing, controller, or data logic was altered.

---

## 1. Navbar Gradient

**Before**: `bg-light` Bootstrap utility class — renders a flat light-grey background.  
**After**: Deep navy-to-blue diagonal gradient via inline `addCssStyle()`:

```php
->addCssStyle([
    'background' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
    'color'      => 'white',
    ...
])
```

The `bg-light` class and a stray `color: red` debug style were removed at the same time.

---

## 2. Brand Spacing and Colour

**Before**: `str_repeat('&nbsp;', 7)` was prepended to the brand label to create left-padding.  
**After**: Replaced with proper CSS on the brand attributes:

```php
->brandAttributes([
    'style' => 'font-size: ' . $fontSize . 'px; font-family: ' . $font
              . '; padding-left: 12px; color: #fff;',
])
```

This removes invisible non-breaking space characters from the HTML and makes the brand text white to match the gradient background.

---

## 3. Colourised Nav Icons

Bootstrap Icons in the guest-visible navbar items were given contextual colour classes to create visual hierarchy at a glance:

| Nav Item | Icon | Class |
|---|---|---|
| Accreditations | `bi-patch-check` | `text-success` |
| Gallery | `bi-images` | `text-warning` |
| Team | `bi-people-fill` | `text-info` |
| Testimonial | `bi-file-ruled` | `text-secondary` |
| Pricing | `bi-tags-fill` | `text-danger` |
| Privacy / Terms | icons | `text-muted` |
| Sign Up | icon | `text-warning` |

---

## 4. Footer Gradient

**Before**: `bg-dark` Bootstrap utility class — flat dark background.  
**After**: The same three-stop navy gradient as the navbar, applied via an inline `style` attribute on the `<footer>` tag:

```php
echo Html::openTag('footer', [
    'class' => 'mt-auto py-3',
    'style' => 'background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);',
]);
```

This creates a consistent dark-navy frame — navbar at the top, footer at the bottom.

---

## 5. Social Icon Hover Colours

Social links in the footer previously showed no hover feedback. A `<style>` block injected before `$this->endBody()` adds per-brand hover colours:

```css
footer a { transition: opacity 0.2s; }
footer a:hover { opacity: 1 !important; }
footer a:hover .bi-github   { color: #f0f6fc; }
footer a:hover .bi-slack    { color: #e01e5a; }
footer a:hover .bi-facebook { color: #1877f2; }
footer a:hover .bi-twitter  { color: #1da1f2; }
footer a:hover .bi-whatsapp { color: #25d366; }
footer a:hover .bi-linkedin { color: #0a66c2; }
```

Colours are taken from each platform's official brand guidelines.

---

## 6. Active Nav-Link Indicator

Active navbar links now show a visible underline to indicate the current page:

```css
.navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.4); }
.navbar .nav-link { color: rgba(255,255,255,0.8) !important; transition: color 0.15s; }
.navbar .nav-link:hover { color: #fff !important; }
.navbar .nav-link.active { color: #fff !important; border-bottom: 2px solid rgba(255,255,255,0.7); }
```

The `box-shadow` adds subtle depth below the sticky navbar. The `!important` overrides are required because Bootstrap's own specificity rules on `.navbar-light .nav-link` would otherwise win.

---

## File Modified

`resources/views/layout/templates/soletrader/main.php`
