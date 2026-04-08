# Language Flag Dropdown

## Overview

The language-selector dropdown in all three layout templates now displays a country flag image alongside each language name. The currently active locale's flag also appears on the toggler button itself, giving users immediate visual feedback about the selected language.

---

## Why Not Unicode Emoji?

Unicode Regional Indicator flag sequences (e.g. `🇬🇧`) rely on the operating system's emoji font. Windows (including all Chromium-based browsers on Windows) does not include a font with country flag glyphs, so the characters render as raw two-letter codes (`GB`, `ZA`) instead of flag images. A CDN-backed `<img>` tag is the only cross-platform solution.

---

## Architecture

### 1. `SettingRepository::getLocaleFlags()`

**File**: `src/Invoice/Setting/SettingRepository.php`

A new public method returns a `array<string, string>` mapping every supported locale code to its ISO 3166-1 alpha-2 country code (lowercase), as used by `flagcdn.com`:

```php
public function getLocaleFlags(): array
{
    return [
        'af-ZA' => 'za',
        'ar-BH' => 'bh',
        'az'    => 'az',
        'be-BY' => 'by',
        'bs'    => 'ba',
        // ... all 30 locales
    ];
}
```

Special cases:
- `en` → `gb` (English mapped to the United Kingdom flag)
- `gd-GB` → `gb-sct` (Scots Gaelic uses the Scottish flag sub-code supported by flagcdn.com)
- `ha-NG`, `ig-NG`, `yo-NG` all map to `ng` (Nigerian sub-languages share the Nigerian flag)

---

### 2. `LayoutViewInjection::getLayoutParameters()`

**File**: `src/ViewInjection/LayoutViewInjection.php`

The injection class was extended with three additions:

#### a) `use Yiisoft\Html\NoEncode`

Required so that HTML `<img>` strings are passed through `DropdownItem::link()` without being escaped by `Yiisoft\Html\Tag\Base\TagContentTrait`.

`TagContentTrait::content()` HTML-encodes plain `string` arguments by default. To bypass this it checks whether the value implements `NoEncodeStringableInterface`. `NoEncode::string()` wraps a raw HTML string in an object that satisfies that interface.

#### b) `$flagImg` closure

Builds a 16×12px `<img>` tag sourced from `flagcdn.com`:

```php
$flagImg = static fn(string $code): string
    => '<img src="https://flagcdn.com/16x12/' . $code . '.png"'
       . ' width="16" height="12" alt="' . $code . '"'
       . ' style="vertical-align:middle;margin-right:5px;">';
```

#### c) `$fl` closure

Wraps the flag image and label in a `NoEncode` object ready for `DropdownItem::link()`:

```php
$fl = static fn(string $locale, string $label): NoEncode
    => NoEncode::string($flagImg($flags[$locale] ?? 'un') . $label);
```

The fallback country code `'un'` renders the United Nations flag for any unmapped locale.

#### d) `$currentLocaleFlag`

Derives the active locale code from the existing `$localeSplitter` (`Yiisoft\I18n\Locale`) and looks it up in the flags map:

```php
$currentLocaleCode = ($localeSplitter->region() !== null && $localeSplitter->region() !== '')
    ? $localeSplitter->language() . '-' . $localeSplitter->region()
    : $localeSplitter->language();
$currentLocaleFlag = $flagImg($flags[$currentLocaleCode] ?? 'un');
```

#### e) Return array additions

Two new keys are passed to every layout view:

```php
'localeFlags'       => $flags,          // full map, available for custom use in views
'currentLocaleFlag' => $currentLocaleFlag, // <img> string for the toggler button
```

All 30 `DropdownItem::link(...)` label strings are prefixed using `$fl(...)`:

```php
'en' => DropdownItem::link($fl('en', 'English'), ...),
```

---

### 3. Layout Templates

**Files**:
- `resources/views/layout/templates/soletrader/main.php`
- `resources/views/layout/invoice.php`
- `resources/views/layout/guest.php`

Each template:

1. Declares `@var` annotations for the two new variables:
   ```php
   * @var string $currentLocaleFlag
   * @var array<string,string> $localeFlags
   ```

2. Updates `->togglerContent()` to display the current flag:
   - `main.php`: `->togglerContent($currentLocaleFlag)`
   - `invoice.php` / `guest.php`: `->togglerContent($currentLocaleFlag . ' ' . new I()->class('bi bi-translate'))`

The toggler uses `->encode(false)` internally (inside `Dropdown::renderToggler()` and `renderTogglerLink()`), so the raw `<img>` HTML is safe to pass as a plain string here.

---

## flagcdn.com

[flagcdn.com](https://flagcdn.com) is a free, open-source flag CDN. It supports:
- PNG at multiple sizes: `16x12`, `20x15`, `24x18`, `28x21`, `32x24`, `40x30`, `48x36`, `56x42`, `60x45`, `64x48`, `72x54`, `80x60`
- SVG via `/[code].svg`
- Sub-national flags (e.g. `gb-sct` for Scotland, `gb-wls` for Wales)

The 16×12px PNG size was chosen to match the standard line height of a Bootstrap dropdown item.

---

## Double-Encoding Fix (`partial_settings_storecove.php`)

While integrating the flags work, a related HTML-encoding bug was found and fixed in several settings partials. The pattern `->content(H::encode($str))` was used throughout, which caused double-encoding: `H::encode()` converts `&` → `&amp;`, then `Option::content()` encodes it again, producing `&amp;nbsp;` which renders as literal ampersand text in the browser.

**Fix**: Remove `H::encode()` from inside `Option::content()`. For options containing `&nbsp;` HTML entities, chain `->encode(false)` on the `Option` object instead:

```php
// Before (broken — &nbsp; renders as &amp;nbsp;)
echo new Option()->content(H::encode($cldr . str_repeat("&nbsp;", 2) . $country));

// After (correct)
echo new Option()->encode(false)->content($cldr . str_repeat("&nbsp;", 2) . $country);
```

Files fixed: `partial_settings_storecove.php`, `partial_settings_projects_tasks.php`, `partial_settings_qr_code.php`, `partial_settings_oauth2.php`.
