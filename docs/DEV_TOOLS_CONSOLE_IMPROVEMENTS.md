# Dev Tools Console Improvements — `m.php`

**June 2026**

## Overview

A series of improvements to the `m.php` browser console that ship after the
initial [Dev Tools Web UI](DEV_TOOLS_WEB_UI.md) rewrite, covering ANSI colour
fidelity, terminal hyperlinks, clipboard support, and live SonarCloud rule
filtering.

---

## ANSI Colour Rendering

### Windows `proc_open` environment fix

`putenv()` in PHP only updates the **CRT** (C-runtime) environment block.
Windows `CreateProcess` (used by `proc_open`) reads the **Win32** environment
block — a separate copy — so child processes never saw `FORCE_COLOR=1` from
`putenv()`. Fix: collect the full environment with `getenv()`, merge the
colour-forcing vars, and pass the array explicitly as the 5th argument to
`proc_open()`:

```php
$baseEnv  = is_array($e = getenv()) ? $e : [];
$childEnv = array_merge($baseEnv, [
    'FORCE_COLOR'    => '1',   // Node/npm tools + Symfony Console ≥ 5.4
    'CLICOLOR_FORCE' => '1',   // many Unix-style CLIs
    'TERM'           => 'xterm-256color',
    'COLORTERM'      => 'truecolor',
]);
proc_open('cmd /c ' . $cmd . ' 2>&1', $descriptors, $pipes, __DIR__, $childEnv);
```

Explicit `--ansi` / `--colors=always` flags were also added to every Composer,
PHPUnit, PHP-CS-Fixer, PHPCS, and Rector command to bypass TTY detection
entirely for tools that ignore environment variables.

### Background colour codes

`ANSI_STYLES` previously mapped only foreground codes (30–37, 90–97).
Psalm and other tools use background codes for their summary blocks
(e.g. `\x1b[42m` = green background for "No errors found!").
Added:

| Range | Codes | Notes |
|-------|-------|-------|
| Standard backgrounds | 40–47 | black → white |
| Default background | 49 | reset (no-op) |
| Bright backgrounds | 100–107 | bright variants |
| Underline | 4 | `text-decoration:underline` |
| Attribute resets | 22, 23, 24 | bold/italic/underline off (no-op) |

Background spans receive `padding:.1em .35em; border-radius:3px` so they
render as pills rather than bare highlighted characters.

---

## OSC 8 Terminal Hyperlinks

Tools such as `composer outdated` emit **OSC 8 hyperlinks**
(`ESC ] 8 ; ; URL ST … text … ESC ] 8 ; ; ST`) to make package names
clickable in modern terminals. The previous `ansiToHtml()` only handled
`ESC [` (CSI/SGR) sequences and let OSC sequences appear as visible garbage
(e.g. `]8;;https://github.com/…\packagename]8;;\`).

### Fix

Pre-process OSC 8 sequences before the SGR split using temporary
`\x02URL\x02text\x03` markers (control characters that survive
`esc()` HTML-escaping unchanged), then restore them as `<a>` tags after SGR
processing:

```js
// Pre-process OSC 8 → markers
const ST = '(?:\x07|\x1b\\\\)';
raw = raw.replace(
    new RegExp('\x1b\\]8;;([^\x07\x1b]*?)' + ST + '([\\s\\S]*?)\x1b\\]8;;' + ST, 'g'),
    (_m, url, text) => url ? `\x02${url}\x02${text}\x03` : text
);
// … SGR processing …
// Restore markers as anchors
html = html.replace(
    /\x02([^\x02\x03]*)\x02([\s\S]*?)\x03/g,
    (_m, url, linkHtml) =>
        `<a href="${url}" target="_blank" rel="noopener"
            style="color:#79c0ff;text-decoration:underline">${linkHtml}</a>`
);
```

Package links in `composer outdated`, `composer require-checker`, and similar
commands now appear as underlined blue anchors that open the GitHub page in a
new tab.

---

## Copy Button

A **Copy** button was added to the output panel header alongside the existing
close button. Clicking it writes the panel's plain-text content (via
`textContent`, not `innerHTML` — so no ANSI span tags are copied) to the
clipboard using the Clipboard API, then briefly shows "Copied!" in green
before reverting after 1.8 s.

```js
function copyOutput() {
    navigator.clipboard.writeText(
        document.getElementById('out-pre').textContent
    ).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.textContent = 'Copied!';
        btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.replace('btn-outline-success', 'btn-outline-secondary');
        }, 1800);
    });
}
```

---

## SonarCloud — Live Cascading Rule-Key Dropdowns

The **Filter by Rule Key** command previously required the user to type a full
rule key (e.g. `php:S1192`) into a free-text input. It now presents two
cascading dropdowns populated from a live SonarCloud query.

### How it works

1. The modal opens and immediately calls `?api=failing_rules` (GET).
2. The endpoint runs `php sonar-issues.php --grouped` via `proc_open`,
   parses every output line matching `lang:S####  count  description`,
   and returns JSON grouped by language:

```json
{
    "php":        { "1192": "String literals duplicated 3+", "3776": "Cognitive complexity" },
    "typescript": { "7764": "globalThis not window" }
}
```

3. The **language dropdown** is populated with only the languages that have
   current failures.
4. Selecting a language immediately repopulates the **rule-number dropdown**
   with only the `S####` numbers that are actually failing for that language —
   each option shows the rule number and its description.
5. On submit, the two selected values are combined as `{lang}:S{num}` and
   passed to `sonar-issues.php --rule=`.

### Implementation notes

- Live data is stored on the `<select>` element as `langSel._liveData` (a JS
  property, not a `data-` attribute) to avoid serialisation overhead.
- If no SonarCloud token is set the rule dropdown shows "Set SonarCloud token
  first".
- The `paramCascade` field on a command definition is a generic mechanism;
  other commands can adopt the same pattern.

---

## Files Changed

| File | Change |
|------|--------|
| `m.php` | All of the above |