# Google Translate — All-Locales Diff Sweep, No More Manual Merge (August 2026)

The existing `type=diff` flow (`GET /generator/googleTranslateLang/diff`)
was already the cheap way to translate (see
`docs/GOOGLE_TRANSLATE_DEDUPE_COST_FIX_AUGUST_2026.md`), but it was still
entirely manual downstream of the API call: pick one locale from the
`google_translate_locale` Setting dropdown, run it, open
`resources/views/invoice/generator/output_overwrite/`, find the newly
timestamped `{locale}_diff_{timestamp}_diff_lang.php` file, and hand-copy
its contents into the existing `resources/messages/{locale}/app.php` —
one locale at a time, for every locale that needed catching up.

## What changed

New `GeneratorGoogleTranslateController::googleTranslateAllLocalesDiff()`
(`GET/POST /generator/googleTranslateAllLocalesDiff`, a new "Google
Translate All Locales (Diff)" item in the same debug-only Generator
dropdown as the existing app/diff/info actions) does the whole thing in
one request:

1. Loads `en/app.php` once.
2. `glob()`s every directory under `resources/messages/`.
3. For each one (skipping `en` itself and any directory with no
   `app.php` at all) — `translateOneLocaleDiff()`:
   - Diffs it against `en/app.php` (`array_diff_key()` — the same
     comparison `rebuildLocale()` already used for the single-locale
     flow).
   - If nothing's missing, reports "already up to date" and leaves the
     file untouched.
   - Otherwise translates only the missing keys via the same
     `translateContentBatch()` helper the single-locale flow uses
     (deduplicated before sending — see the dedupe-cost-fix doc above),
     using the locale directory's own name as the Google target
     language code (the same assumption the existing dropdown's values
     already relied on).
   - Merges the translated keys into the existing array, sorts the
     whole thing by key (`ksort(..., SORT_STRING)`), and writes
     `resources/messages/{locale}/app.php` back in place.
4. One locale's translation failure (API error, quota, network) is
   caught per-locale and reported in the summary flash message; it
   never aborts the rest of the sweep, and never partially overwrites
   that one locale's file.

## The escaping bug this also had to fix

`templates_protected/_app.php` and `_diff_lang.php` (the existing
single-locale output templates) build each line via raw string
concatenation: `"'" . $key . "' => '" . $value . "',"`. `en/app.php`
genuinely has 40 values containing an apostrophe (e.g. "Administrator's
Email Address") — feeding one of those through unescaped, or a
translated value that happens to contain one too, produces invalid PHP
syntax. New `writeLocaleAppPhp()` builds each line with
`var_export($key, true) . ' => ' . var_export($value, true)` instead —
correctly escaped regardless of what the string contains. The existing
templates were left as-is (still used by the single-locale flow); only
the new all-locales writer needed to get this right from the start.

## Scope / non-goals

- Locales that don't yet have a `resources/messages/{locale}/app.php`
  at all are skipped entirely, not created — a brand-new locale still
  needs the existing one-time `type=app` full-file translate first
  (Step 1-2 of the Settings → Google Translate instructions), the same
  as before.
- A locale already fully caught up is never rewritten, so an
  already-in-sync file's existing key order is left as it was — sorting
  only applies to the keys a sweep actually touches.
- `output_overwrite/` and the single-locale `app`/`diff` routes are
  unchanged and still available for a one-off single-locale run.

## Verified

New `GeneratorGoogleTranslateControllerAllLocalesTest` (6 tests, against
real temporary locale directories — no real API calls;
`TranslationServiceClient` mocked via Mockery, real `TranslateTextResponse`/
`Translation` protobuf objects built for the response rather than
mocked, since `Tests/bootstrap.php` already enables `DG\BypassFinals`
project-wide for exactly this kind of `final`-class mock): confirms the
apostrophe round-trips correctly, `en` itself and a directory with no
`app.php` are both skipped, an up-to-date locale is left untouched, a
locale with missing keys ends up merged/translated/sorted correctly,
and a translation failure is reported without touching that locale's
file. Full Testo Unit suite: 1004/1004 passed (up from 998). Full-project
Psalm `--no-cache`: no errors found. Route confirmed registered via
`php yii router/list`.
