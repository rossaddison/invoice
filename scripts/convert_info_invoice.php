<?php

/**
 * Conversion script v3: resources/views/invoice/info/en/invoice.php
 * Applies patterns 13a / 13b / 13c / 13d from _html_php_conventions.php.
 *
 * Rules enforced:
 *  - NoEncode::string() is used ONLY for Pattern 13c (<s> strikethrough).
 *  - The argument to NoEncode::string() is always a plain single-quoted string,
 *    never a heredoc/nowdoc.
 *  - nowdoc is used inside H::tag('pre',...) / H::tag('code',...) for multi-line
 *    code blocks — but never inside NoEncode::string().
 *  - For tables, ol, ul: bare echo with a nowdoc string (no H:: or NoEncode).
 *  - Complex <p> with mixed inline HTML: strip to plain text via textOf().
 *
 * Run:  php scripts/convert_info_invoice.php
 * Output: resources/views/invoice/info/en/invoice_converted.php
 */

declare(strict_types=1);

$srcFile = __DIR__ . '/../resources/views/invoice/info/en/invoice.php';
$dstFile = __DIR__ . '/../resources/views/invoice/info/en/invoice_converted.php';

$raw = file_get_contents($srcFile);
if ($raw === false) {
    fwrite(STDERR, "Cannot read $srcFile\n");
    exit(1);
}

// ── helpers ──────────────────────────────────────────────────────────────────

/**
 * Escape a plain-text string for use as a PHP single-quoted string literal.
 */
function sq(string $s): string
{
    return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $s) . "'";
}

/**
 * Make a heredoc-style block for multi-line / complex content.
 * Uses nowdoc (single-quoted) so no PHP interpolation.
 */
function nowdoc(string $s, string $label = 'TEXT'): string
{
    // Make sure the label doesn't appear in the content
    while (strpos($s, $label) !== false) {
        $label .= '_';
    }
    return "<<<'$label'\n$s\n$label";
}

/**
 * Return the inner HTML of a DOMNode as a string.
 */
function innerHtml(DOMNode $node): string
{
    $doc = $node->ownerDocument;
    if ($doc === null) {
        return '';
    }
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }
    return $html;
}

/**
 * Return text content (strip all tags).
 */
function textOf(DOMNode $node): string
{
    return $node->textContent;
}

/**
 * Return the decoded plain-text content of a node whose children are all
 * text/CDATA.  Use this for <pre> and <code> blocks so that DOMDocument's
 * saveHTML()-encoding (& -> &amp;, > -> &gt; …) does not get double-encoded
 * when the string is later passed to H::tag().
 */
function rawTextContent(DOMNode $node): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
            $out .= $child->nodeValue ?? '';
        } elseif ($child instanceof DOMElement) {
            $out .= rawTextContent($child);
        }
    }
    return $out;
}

/**
 * Decide whether a <p> node's first-and-only child (ignoring whitespace) is
 * a given tag, and return it (or null).
 *
 * @param non-empty-string $tag
 */
function singleChild(DOMElement $p, string $tag): ?DOMElement
{
    $kids = [];
    foreach ($p->childNodes as $c) {
        if ($c->nodeType === XML_TEXT_NODE && trim($c->nodeValue ?? '') === '') {
            continue;
        }
        $kids[] = $c;
    }
    if (count($kids) === 1 && $kids[0] instanceof DOMElement && $kids[0]->tagName === $tag) {
        return $kids[0];
    }
    return null;
}

// ── load HTML ────────────────────────────────────────────────────────────────

libxml_use_internal_errors(true);
$doc = new DOMDocument('1.0', 'UTF-8');
// Wrap in a div so loadHTML doesn't add html/body wrappers
$doc->loadHTML(
    '<?xml encoding="UTF-8"><div id="_root_">' . $raw . '</div>',
    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR
);
libxml_clear_errors();

$root = $doc->getElementById('_root_');
if ($root === null) {
    fwrite(STDERR, "Could not find root element\n");
    exit(1);
}

// ── generate PHP lines ────────────────────────────────────────────────────────

$lines = [];
$lines[] = '<?php';
$lines[] = '';
$lines[] = 'declare(strict_types=1);';
$lines[] = '';
$lines[] = 'use Yiisoft\\Html\\Html as H;';
$lines[] = 'use Yiisoft\\Html\\NoEncode;';
$lines[] = '';

/**
 * Convert a single top-level node into PHP echo statement(s).
 *
 * @return string[]
 */
function convertNode(DOMNode $node, DOMDocument $doc): array
{
    if ($node->nodeType === XML_TEXT_NODE) {
        $t = trim($node->nodeValue ?? '');
        if ($t === '') {
            return [];
        }
        // Standalone text outside a tag — wrap in an echo
        return ['echo ' . sq($t) . ';'];
    }

    if (!($node instanceof DOMElement)) {
        return [];
    }

    $tag = strtolower($node->tagName);

    // ── img (void) ──────────────────────────────────────────────────────────
    if ($tag === 'img') {
        $attrs = [];
        foreach ($node->attributes as $attr) {
            $attrs[] = "'" . $attr->name . "' => " . sq($attr->value);
        }
        return ['echo H::tag(\'img\', \'\', [' . implode(', ', $attrs) . ']);'];
    }

    // ── br (void) ───────────────────────────────────────────────────────────
    if ($tag === 'br') {
        return ["echo H::tag('br', '');"];
    }

    // ── h1 / h2 / h3 / h4 / h5 / h6 ───────────────────────────────────────
    if (in_array($tag, ['h1','h2','h3','h4','h5','h6'], true)) {
        $inner   = innerHtml($node);
        $stripped = strip_tags($inner);
        // Collect attributes
        $attrArr = [];
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $attrArr[] = "'" . $attr->name . "' => " . sq($attr->value);
            }
        }
        $attrStr = count($attrArr) > 0 ? ', [' . implode(', ', $attrArr) . ']' : '';
        // Check if it contains only an <a>
        if (preg_match('#^<a\s[^>]*>(.+)</a>$#si', trim($inner), $m)) {
            $aEl = null;
            foreach ($node->childNodes as $c) {
                if ($c instanceof DOMElement && strtolower($c->tagName) === 'a') {
                    $aEl = $c;
                    break;
                }
            }
            $href  = $aEl ? ($aEl->getAttribute('href') ?? '') : '';
            $text  = $stripped;
            return [
                "echo H::tag('$tag', H::a(" . sq($text) . ")->href(" . sq($href) . ")$attrStr);",
            ];
        }
        $safe = trim($inner);
        if (strpos($safe, '<') === false) {
            return ["echo H::tag('$tag', " . sq($stripped) . "$attrStr);"];
        }
        // Complex heading — emit raw HTML
        $fullHtml = trim((string)$doc->saveHTML($node));
        return ["echo " . nowdoc($fullHtml) . ";"];
    }

    // ── standalone <b> / <strong> ───────────────────────────────────────────
    if ($tag === 'b' || $tag === 'strong') {
        $inner   = innerHtml($node);
        $stripped = strip_tags($inner);
        // If inner is simple text
        if (strpos($inner, '<') === false) {
            return ["echo H::b(" . sq($stripped) . ");"];
        }
        // Complex bold — emit raw HTML
        $fullHtml = trim((string)$doc->saveHTML($node));
        return ["echo " . nowdoc($fullHtml) . ";"];
    }

    // ── standalone <code> ───────────────────────────────────────────────────
    if ($tag === 'code') {
        // Use rawTextContent to avoid double-encoding
        $hasChildElements = false;
        foreach ($node->childNodes as $c) {
            if ($c instanceof DOMElement) { $hasChildElements = true; break; }
        }
        if ($hasChildElements) {
            // Complex code — emit raw HTML
            $fullHtml = trim((string)$doc->saveHTML($node));
            return ["echo " . nowdoc($fullHtml) . ";"];
        }
        $codeText = rawTextContent($node);
        return ["echo H::tag('code', " . nowdoc(trim($codeText)) . ");"];
    }

    // ── standalone <pre> ────────────────────────────────────────────────────
    if ($tag === 'pre') {
        $inner = innerHtml($node);
        // Check for <code> inside — use rawTextContent to avoid double-encoding
        $firstChild = null;
        foreach ($node->childNodes as $c) {
            if ($c->nodeType === XML_TEXT_NODE && trim($c->nodeValue ?? '') === '') {
                continue;
            }
            $firstChild = $c;
            break;
        }
        if ($firstChild instanceof DOMElement && strtolower($firstChild->tagName) === 'code') {
            $codeText = rawTextContent($firstChild);
            return ["echo H::tag('pre', H::tag('code', " . nowdoc(trim($codeText)) . "));"];
        }
        // Plain pre: use rawTextContent to get decoded text
        $preText = rawTextContent($node);
        if (trim($preText) !== '') {
            // Check if there are child elements other than text (complex HTML inside pre)
            $hasChildElements = false;
            foreach ($node->childNodes as $c) {
                if ($c instanceof DOMElement) {
                    $hasChildElements = true;
                    break;
                }
            }
            if ($hasChildElements) {
                // Complex pre — emit raw HTML
                $fullHtml = trim((string)$doc->saveHTML($node));
                return ["echo " . nowdoc($fullHtml) . ";"];
            }
            return ["echo H::tag('pre', " . nowdoc(trim($preText)) . ");"];
        }
        // Fallback — emit raw HTML
        $fullHtml = trim((string)$doc->saveHTML($node));
        return ["echo " . nowdoc($fullHtml) . ";"];
    }

    // ── <p> ─────────────────────────────────────────────────────────────────
    if ($tag === 'p') {
        return convertP($node, $doc);
    }

    // ── <ol> / <ul> ─────────────────────────────────────────────────────────
    if ($tag === 'ol' || $tag === 'ul') {
        $inner = innerHtml($node);
        $inner = trim($inner);
        if ($inner === '') {
            return [];
        }
        $attrs = '';
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                if ($attr->name === 'class') {
                    $attrs = ", ['" . $attr->name . "' => '" . $attr->value . "']";
                }
            }
        }
        // List — emit raw HTML
        $fullHtml = trim((string)$doc->saveHTML($node));
        return ["echo " . nowdoc($fullHtml) . ";"];
    }

    // ── <table> ─────────────────────────────────────────────────────────────
    if ($tag === 'table') {
        $html = $doc->saveHTML($node);
        $html = preg_replace('#^<table[^>]*>#i', '', $html);
        $html = preg_replace('#</table>$#i', '', $html);
        $html = trim((string) $html);
        $full = trim((string) $doc->saveHTML($node));
        return ["echo " . nowdoc($full, 'TABLE') . ";"];
    }

    // ── anything else: pass through with NoEncode ──────────────────────────
    $html = trim((string) $doc->saveHTML($node));
    if ($html === '') {
        return [];
    }
    return ["echo " . nowdoc($html) . ";"];
}

/**
 * Convert a <p> element applying the appropriate 13a/13b/13c/13d pattern.
 *
 * @return string[]
 */
function convertP(DOMElement $p, DOMDocument $doc): array
{
    $inner   = trim(innerHtml($p));
    if ($inner === '') {
        return ["echo H::p('');"];
    }
    $text = trim(textOf($p));

    // ── PATTERN 13c — <p><s>...</s></p> ────────────────────────────────────
    // p whose ONLY non-whitespace child is a single <s>
    $sOnly = singleChild($p, 's');
    if ($sOnly !== null) {
        $sInner = trim(innerHtml($sOnly));
        if (strpos($sInner, '<') === false) {
            // Pattern 13c  — plain text, single string format
            $combined = '<s>' . rawTextContent($sOnly) . '</s>';
            return ["echo H::p(NoEncode::string(" . sq($combined) . "));"];
        }
        // has nested tags inside <s> — inline as string
        return ["echo H::p(NoEncode::string(" . sq("<s>$sInner</s>") . "));"];
    }

    // ── PATTERN 13a — <p><b><a href="...">text</a></b></p> ─────────────────
    // OR  <p><a href="...">text</a></p>  simplified form
    $bOnly = singleChild($p, 'b');
    if ($bOnly !== null) {
        $aOnly = singleChild($bOnly, 'a');
        if ($aOnly !== null && $aOnly instanceof DOMElement) {
            $href  = $aOnly->getAttribute('href');
            $label = trim(textOf($aOnly));
            if ($label !== '' && $href !== '') {
                return ["echo H::p(H::b(H::a(" . sq($label) . ")->href(" . sq($href) . ")));"];
            }
        }
    }

    // Plain <a> only, no wrapping <b>
    $aOnly = singleChild($p, 'a');
    if ($aOnly !== null && $aOnly instanceof DOMElement) {
        $href  = $aOnly->getAttribute('href');
        $label = trim(textOf($aOnly));
        if ($href !== '') {
            return ["echo H::p(H::a(" . sq($label) . ")->href(" . sq($href) . "));"];
        }
    }

    // ── PATTERN 13d — <p><pre><code>...</code></pre></p> ───────────────────
    $preOnly = singleChild($p, 'pre');
    if ($preOnly !== null && $preOnly instanceof DOMElement) {
        // Find <code> inside pre
        $codeEl = null;
        foreach ($preOnly->childNodes as $c) {
            if ($c instanceof DOMElement && strtolower($c->tagName) === 'code') {
                $codeEl = $c;
                break;
            }
        }
        if ($codeEl !== null) {
            $codeText = rawTextContent($codeEl);
            return ["echo H::p(H::pre(H::code(" . nowdoc(trim($codeText)) . ")));"];
        }
        // <p><pre>plain</pre></p> — Pattern 13d simplified (no <code> wrapper)
        $hasChildElements = false;
        foreach ($preOnly->childNodes as $c) {
            if ($c instanceof DOMElement) { $hasChildElements = true; break; }
        }
        if ($hasChildElements) {
            // Complex pre inside p — emit raw p+pre HTML
            $fullHtml = trim((string)$doc->saveHTML($p));
            return ["echo " . nowdoc($fullHtml) . ";"];
        }
        $preText = rawTextContent($preOnly);
        return ["echo H::p(H::tag('pre', " . nowdoc(trim($preText)) . "));"];
    }

    // ── PATTERN 13b — <p><b>heading</b> ... <pre>...</pre></p> ─────────────
    // Detect if p starts with <b>...</b> and contains a <pre>
    if (preg_match('#^<b>(.*?)</b>([\s\S]*)$#i', $inner, $m)) {
        $boldText  = strip_tags($m[1]);
        $rest      = trim($m[2]);
        // Does rest contain a <pre>?
        if (preg_match('#<pre[^>]*>([\s\S]*?)</pre>#i', $rest, $pm)) {
            $preContent = trim($pm[1]);
            // strip inner <code> wrapper if present
            if (preg_match('#^<code[^>]*>([\s\S]*)</code>$#i', $preContent, $cm)) {
                $preContent = trim($cm[1]);
            }
            // decode entities for use with H::code
            $preDecoded = html_entity_decode($preContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // strip any remaining tags from decoded content
            $preDecoded = strip_tags($preDecoded);
            return [
                "echo H::p(H::b(" . sq($boldText) . ")",
                " . H::pre(H::code(" . nowdoc($preDecoded) . ")));",
            ];
        }
    }

    // ── Paragraph with only <b>text</b> ─────────────────────────────────────
    if ($bOnly !== null && $bOnly instanceof DOMElement) {
        $bInner = trim(innerHtml($bOnly));
        if (strpos($bInner, '<') === false) {
            return ["echo H::p(H::b(" . sq(trim($text)) . "));"];
        }
        // b with nested HTML — emit raw p+b HTML
        $fullHtml = trim((string)$doc->saveHTML($p));
        return ["echo " . nowdoc($fullHtml) . ";"];
    }

    // ── Paragraph with only plain text ──────────────────────────────────────
    if (strpos($inner, '<') === false) {
        return ["echo H::p(" . sq($text) . ");"];
    }

    // ── Paragraph with mixed HTML — emit raw <p>...</p> ─────────────────────
    $fullHtml = trim((string)$doc->saveHTML($p));
    return ["echo " . nowdoc($fullHtml) . ";"];
}

// ── walk root children ───────────────────────────────────────────────────────

foreach ($root->childNodes as $child) {
    $result = convertNode($child, $doc);
    foreach ($result as $line) {
        $lines[] = $line;
    }
}

// ── insert echo H::br(); before each date heading ────────────────────────────
// A date heading is: echo H::p(H::b('…date…'));
// Match e.g. "1st April 2026", "28th March 2026", "7th January 2026", etc.
$datePattern = '/^echo H::p\(H::b\(\'(\d{1,2}(st|nd|rd|th) \w+ \d{4}|'
    . '\d{1,2} \w+ \d{4})\'\)\);$/';
$withBr = [];
foreach ($lines as $line) {
    if (preg_match($datePattern, trim($line))) {
        $withBr[] = "echo H::br();";
    }
    $withBr[] = $line;
}
$lines = $withBr;

// ── write output ─────────────────────────────────────────────────────────────

$phpCode = implode("\n", $lines) . "\n";
if (file_put_contents($dstFile, $phpCode) === false) {
    fwrite(STDERR, "Cannot write to $dstFile\n");
    exit(1);
}

echo "Written to $dstFile\n";
