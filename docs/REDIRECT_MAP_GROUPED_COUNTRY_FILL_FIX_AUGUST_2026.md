# Redirect Choropleth Map — Multi-Path Countries Never Colored (August 2026)

`GET /redirect-map` (`RedirectController::map()`) renders an all-time
click choropleth for the homepage's tracked `/go/github` link, using
`flekschas/simple-world-map` as the base SVG. Spotted from a real
screenshot: the "Top countries" table listed the US with the most clicks
(7) and GB second (2), but the map itself showed only Kazakhstan and
India shaded blue — both far down the list at 1 click each. The US and
GB weren't merely under-shaded, they were entirely gray, indistinguishable
from a country with zero clicks.

## Root cause

`buildCountryStyle()` emitted one rule per country:
`#{country_code} { fill: ...; }`. That works for a country whose SVG
shape is a single `<path id="xx">` element (Kazakhstan, India,
Uzbekistan, Singapore all are). But `world-map.svg` represents 37
countries — including the US and GB — as a `<g id="xx">` wrapping
*multiple* `<path>` children (mainland plus outlying islands/territories:
the US's mainland + Alaska + Hawaii, GB's mainland + smaller islands).

Setting `fill` on the `<g>` never reaches those children. The stylesheet
also has a base rule, `path { fill: #e5e5e5; stroke: #ffffff;
stroke-width: 0.5; }`, which matches every `<path>` element directly —
and in CSS, a value that matches an element directly always wins over a
value the element would otherwise inherit from a less-specific ancestor
match, regardless of which rule has the higher selector specificity.
Since the `#{code}` rule for a `<g>`-wrapped country only ever set fill
on the group, never on its child `<path>`s, the base rule's direct match
on those children always won — silently, with no error anywhere, since
the CSS was perfectly valid, just never doing what it looked like it
should.

## Fix

Changed the emitted rule to `#{code}, #{code} path { fill: ...; }` — the
second half reaches descendant `<path>` elements directly, at higher
specificity than the base rule, so `<g>`-wrapped countries color
correctly. For a single-`<path>` country the added `#{code} path` half
simply never matches anything (a `<path>` has no `<path>` descendants),
so already-working countries are unaffected.

## Verified

New `RedirectControllerCountryStyleTest` (5 tests, reflection-invoked
against the private method, matching
`RedirectControllerBotDetectionTest`'s established pattern for this
class): confirms a `<g>`-wrapped country (`us`) now gets colored, a
single-`<path>` country (`in`) is unaffected, the gradient interpolation
between max and non-max counts still holds, the empty-counts default
fill is unchanged, and a country code that sanitizes to nothing is still
skipped. Full Testo Unit suite: 994/994 passed (up from 989). Psalm
`--no-cache` on both changed files: no errors found.
