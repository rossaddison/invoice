# Google Translate Generator — Dedupe Duplicate Strings Before Sending (August 2026)

`GeneratorGoogleTranslateController::performGoogleTranslation()`
(`/generator/googleTranslateLang/{app|diff}`) sends `en/app.php`'s
message values to Cloud Translation's `TranslateTextRequest`, batched
100 at a time. The Cloud Translation API bills every character sent,
including exact repeats — and `en/app.php` genuinely has keys that share
the identical English text (114 of its 2,245 keys, at the time of this
fix — several `*.save`-style keys all reading "Save", for example).
Every one of those repeats was being sent to, and billed by, the API
once per key.

## Fix

Two small pure helpers on the controller:

- `uniqueTranslatableValues(array $content): list<string>` — the
  distinct strings actually worth sending, via `array_unique()` over
  the message values.
- `combineKeysWithTranslatedValues(array $keys, array $values, array
  $valueTranslationMap): array` — reconstructs the full per-key result
  afterward, mapping every original value (translated or not) through
  its already-deduplicated translation, so every key that shared a
  value gets the same translated text back.

`performGoogleTranslation()` now translates `uniqueTranslatableValues()`
in the same 100-per-batch loop as before, builds a value→translation
map via `array_combine()`, then calls
`combineKeysWithTranslatedValues()` to produce the same
`$combined_array` shape the rendered output template already expects —
fully behavior-preserving, no change to the generated file's structure.
The success flash message now also reports how many unique strings were
actually sent, alongside the existing key count.

On the current `en/app.php` this trims ~983 of 71,244 characters
(~1.4%) off a full-file translate — modest on its own, but free, and
compounds with always preferring `type=diff` over `type=app` for
catch-up runs (a much larger lever — see the Google Translate cost
discussion this fix came out of, which found every existing locale is
currently missing several hundred keys each, making a `diff`-mode
catch-up run roughly 3.5x cheaper than a full `app`-mode retranslation
per locale).

## Verified

New `GeneratorGoogleTranslateControllerDedupeTest` (4 tests,
reflection-invoked against both pure helpers, matching
`RedirectControllerBotDetectionTest`'s established pattern for testing
a private method on a controller with injected dependencies the test
never needs): confirms duplicate values collapse to one entry, a
content array with no duplicates is untouched, every key sharing a
value gets the same translated value back, and a full
dedupe-translate-reconstruct round trip preserves every key. Full Testo
Unit suite: 998/998 passed (up from 994). Psalm `--no-cache` on both
changed files: no errors found. The network-calling half of
`performGoogleTranslation()` itself is not exercised by these tests —
only the deterministic logic either side of the actual API call.
