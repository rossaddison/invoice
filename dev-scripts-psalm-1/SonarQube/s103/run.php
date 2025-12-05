<?php

declare(strict_types=1);

/**
 * run.php
 *
 * Shorten ONLY the VALUES in simple single-line PHP translation entries:
 *   'key' => 'very long value .. .',
 *
 * Additional rule:
 *   - If the key contains more than 4 segments (segments are pieces split by '.' or ':'
 *     and the delimiter is kept with the segment), then the value will be placed on the
 *     next line after the " => " (the arrow is left on the key line).
 *
 * Priority rules for splitting values (applied in order):
 *   1) Split on colon ':' first (':' kept attached to the fragment)
 *   2) Then split each fragment on full stop '.' ('.' kept attached)
 *   3) Any fragment still longer than allowed is split into chunks of up to N words
 *      (default N=5)
 *
 * Continuation formatting:
 *   - The arrow spacing is normalized to " => " (one space either side of =>). 
 *   - Continuation lines start with a single TAB character before the concatenation operator,
 *     e.g. "\t. 'rest... '" (and the first value line for "value-on-next-line" case starts with
 *     a single tab then the quoted fragment, no leading dot).
 *
 * Usage:
 *   php run.php [--words=N] [--dry-run] [--fix] file1.php [...]
 *
 * Options:
 *   --words=N    Number of words per fragments (default 5)
 *   --dry-run    Print proposed edits (no writes)
 *   --fix        Apply edits in-place (creates <file>.bak)
 *
 * Notes:
 *  - Only handles simple single-line "'key' => 'value'," entries. 
 *  - Keys are not modified except for normalizing the spacing around => to a single space.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI\n");
    exit(2);
}

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);

$options = [
    'words' => 5,
    'dry_run' => false,
    'fix' => false,
    'files' => []
];

while (($arg = current($argv)) !== false) {
    if ($arg === '-h' || $arg === '--help') {
        show_help();
        exit(0);
    }
    if (strpos($arg, '--words=') === 0) {
        $options['words'] = (int) substr($arg, 8);
        next($argv);
        continue;
    }
    if ($arg === '--words') {
        next($argv);
        $nextArg = current($argv);
        $options['words'] = ($nextArg !== false) ? (int) $nextArg : 5;
        next($argv);
        continue;
    }
    if ($arg === '--dry-run') {
        $options['dry_run'] = true;
        next($argv);
        continue;
    }
    if ($arg === '--fix') {
        $options['fix'] = true;
        next($argv);
        continue;
    }
    $options['files'][] = $arg;
    next($argv);
}

if (($options['files'] ??  []) === []) {
    fwrite(STDERR, "No files\n");
    exit(2);
}

foreach ($options['files'] as $file) {
    process_file($file, $options['words'], $options['fix'], $options['dry_run']);
}

function show_help(): void
{
    echo "Usage: php shorten.php [--words=N] [--dry-run] [--fix] file1.php [... ]\n";
    echo "  --words=N   words per fragment (default 5)\n";
    echo "  --dry-run   show proposed edits only\n";
    echo "  --fix       write changes in-place (creates <file>.bak)\n";
}

/* ---------- core ---------- */

function process_file(string $path, int $words, bool $fix, bool $dry_run): void
{
    if (!is_file($path)) {
        fwrite(STDERR, "Missing: $path\n");
        return;
    }
    $orig = file_get_contents($path);
    if ($orig === false) {
        fwrite(STDERR, "Read fail: $path\n");
        return;
    }
    $lines = preg_split("/\r\n|\n|\r/", $orig);
    if ($lines === false) {
        fwrite(STDERR, "Split fail: $path\n");
        return;
    }

    $out = [];
    $changed = false;
    foreach ($lines as $ln) {
        $new = process_line_values_only($ln, $words);
        if ($new === $ln) {
            $out[] = $ln;
        } else {
            if (is_array($new)) {
                foreach ($new as $l) {
                    $out[] = $l;
                }
            } else {
                $out[] = $new;
            }
            $changed = true;
        }
    }

    $new_text = implode("\n", $out) . "\n";
    if (! $changed) {
        if ($dry_run) {
            echo "No changes: {$path}\n";
        }
        return;
    }

    if ($dry_run) {
        echo "Would modify: {$path}\n--- Proposed content for {$path} ---\n";
        echo $new_text;
        echo "--- End proposed content for {$path} ---\n";
        return;
    }

    if ($fix) {
        $bak = $path . '.bak';
        if (! @copy($path, $bak)) {
            fwrite(STDERR, "Could not create backup $bak\n");
            return;
        }
        $w = @file_put_contents($path, $new_text, LOCK_EX);
        if ($w === false) {
            fwrite(STDERR, "Write failed for {$path}\n");
            if (is_file($bak)) {
                @copy($bak, $path);
            }
            return;
        }
        echo "Updated: {$path} (backup: {$bak})\n";
        return;
    }

    // default: print
    echo "--- {$path} ---\n";
    echo $new_text;
}

/**
 * @return string|array<int,string>
 */
function process_line_values_only(string $line, int $maxWords)
{
    // Capture lhs exactly (key + arrow + spacing) and the quoted value and trailing bits. 
    // 1: indent (optional)
    // 2: full lhs up to the value opening quote (we preserve keys exactly except spacing normalization)
    // 3: value quote char
    // 4: raw value content (inside quotes)
    // 5: suffix (comma/spaces)
    // 6: trailing remainder (comments etc)
    $pat = '#^(\s*)([\'"][^\'"]*[\'"]\s*=>\s*)([\'"])(? P<value>(? :\\\\.|(? !\\3).)*)\\3(\s*,?\s*)(.*)$#u';
    $m = [];
    if (preg_match($pat, $line, $m) !== 1) {
        return $line;
    }

    $indent = $m[1];
    $lhs = $m[2];               // preserve key text content; we'll normalize spacing around the arrow
    $valQuote = $m[3];
    $rawValue = $m['value'];
    $suffix = $m[5];
    $trail = $m[6];

    // Extract key content to count segments (without changing the original key text)
    // Match the key inside the lhs (quoted)
    $km = [];
    if (preg_match('/^[\s]*([\'"])(?P<key>.*)\1\s*=>\s*$/u', $lhs, $km) !== 1) {
        // should not happen; fall back to default behavior
        $lhs_normalized = preg_replace('/\s*=>\s*$/', ' => ', $lhs) ??  $lhs;
        $keyUn = '';
    } else {
        $lhs_normalized = preg_replace('/\s*=>\s*$/', ' => ', $lhs) ?? $lhs;
        $keyQuote = $km[1];
        $rawKeyInside = $km['key'];
        $keyUn = ($keyQuote === "'")
            ? str_replace(["\\\\", "\\'"], ["\\", "'"], $rawKeyInside)
            : stripcslashes($rawKeyInside);
    }

    // Unescape value for safe manipulation
    if ($valQuote === "'") {
        $value = str_replace(["\\\\", "\\'"], ["\\", "'"], $rawValue);
    } else {
        $value = stripcslashes($rawValue);
    }

    /** @var array $frags */
    $frags = split_value_priority($value, $maxWords);

    // If key has more than 4 segments (split on '.' or ':' keeping delimiter attached),
    // force value-on-next-line behavior
    $force_next_line = false;
    if ($keyUn !== '') {
        // count segments produced by split_and_keep_delim for ':' first, then '.'
        $segments = [];
        if (strpos($keyUn, ':') !== false) {
            $parts = split_and_keep_delim($keyUn, ':');
            /**
             * @var string $p
             */
            foreach ($parts as $p) {
                if (strpos($p, '.') !== false) {
                    /**
                     * @var string $s
                     */
                    foreach (split_and_keep_delim($p, '.') as $s) {
                        $segments[] = $s;
                    }
                } else {
                    $segments[] = $p;
                }
            }
        } elseif (strpos($keyUn, '.') !== false) {
            $segments = split_and_keep_delim($keyUn, '.');
        } else {
            // no punctuation: simple fallback split on non-word characters
            $splitResult = preg_split('/\s+/u', $keyUn);
            $segments = ($splitResult !== false) ? $splitResult : [$keyUn];
        }
        if (count($segments) > 4) {
            $force_next_line = true;
        }
    }

    // If only one fragment and not forcing break, and original line already had " => " spacing, keep original line
    if (! $force_next_line && count($frags) === 1) {
        // if spacing around arrow is already " => " no change
        if (preg_match('/=>\s/', $lhs) === 1) {
            return $line;
        }
        // otherwise return a reconstructed single line with normalized spacing
        $reconstructed = rtrim($lhs_normalized) .  $valQuote . $rawValue . $valQuote .  $suffix . $trail;
        return $reconstructed;
    }

    // Escape fragments for embedding
    $escaped = array_map(function (string $s) use ($valQuote): string {
        if ($valQuote === "'") {
            $s = str_replace("\\", "\\\\", $s);
            $s = str_replace("'", "\\'", $s);
            return $s;
        }
        $s = str_replace("\\", "\\\\", $s);
        $s = str_replace('"', '\\"', $s);
        $s = str_replace('$', '\\$', $s);
        return $s;
    }, $frags);

    $out = [];

    if ($force_next_line) {
        // Put only the lhs (key =>) on the first line (normalized spacing), then value lines starting with a TAB
        $lhs_line = rtrim($lhs_normalized);
        $out[] = $lhs_line; // ends with ' => '

        // First value line: start with one tab then the quoted first fragment (no leading dot)
        $out[] = "\t" . $valQuote . $escaped[0] . $valQuote;

        // Subsequent fragments: tab then concatenation
        for ($i = 1; $i < count($escaped); $i++) {
            $lineOut = "\t" . '. ' . $valQuote .  $escaped[$i] . $valQuote;
            if ($i === count($escaped) - 1) {
                $lineOut .= $suffix .  $trail;
            }
            $out[] = $lineOut;
        }

        // If only one fragment, suffix/trail must be appended to the first value line (index 1)
        if (count($escaped) === 1) {
            $lastIdx = count($out) - 1;
            $out[$lastIdx] .= $suffix . $trail;
        }
    } else {
        // Default behavior: place first fragment on same line as lhs, continuations with tab + ".  'fragment'"
        $lhs_for_first = rtrim($lhs_normalized);
        $firstLine = $lhs_for_first . ' ' . $valQuote . $escaped[0] . $valQuote;
        $out[] = $firstLine;
        $contIndent = "\t";
        for ($i = 1; $i < count($escaped); $i++) {
            $lineOut = $contIndent . '. ' . $valQuote .  $escaped[$i] . $valQuote;
            if ($i === count($escaped) - 1) {
                $lineOut .= $suffix . $trail;
            }
            $out[] = $lineOut;
        }
        if (count($escaped) === 1) {
            $out[0] .= $suffix . $trail;
        }
    }

    return $out;
    
    /**
 * @return array<int,string>
 */
function split_value_priority(string $text, int $maxWords): array
{
    $text = trim($text);
    if ($text === '') {
        return [''];
    }

    // 1) split at colons first (keep ':' attached)
    $colon_segments = split_and_keep_delim($text, ':');

    // 2) split each colon segment at full-stops, then by word count
    $result = split_segments_by_punctuation_and_words($colon_segments, $maxWords);

    // 3) ensure non-final fragments end with a space if needed
    return ensure_trailing_spaces($result);
}

/**
 * @param array<int,string> $segments
 * @return array<int,string>
 */
function split_segments_by_punctuation_and_words(array $segments, int $maxWords): array
{
    $result = [];
    foreach ($segments as $seg) {
        $dot_segments = split_and_keep_delim($seg, '.');
        foreach ($dot_segments as $dseg) {
            $dseg = trim($dseg);
            if ($dseg === '') {
                continue;
            }
            $fragments = split_segment_by_word_count($dseg, $maxWords);
            foreach ($fragments as $frag) {
                $result[] = $frag;
            }
        }
    }
    return $result;
}

/**
 * @return array<int,string>
 */
function split_segment_by_word_count(string $segment, int $maxWords): array
{
    $words = preg_split('/\s+/u', $segment);
    if ($words === false) {
        return [$segment];
    }
    
    if (count($words) <= $maxWords) {
        return [$segment];
    }

    $fragments = [];
    for ($i = 0; $i < count($words); $i += $maxWords) {
        $chunk = array_slice($words, $i, $maxWords);
        $fragments[] = implode(' ', $chunk);
    }
    return $fragments;
}

/**
 * @param array<int,string> $fragments
 * @return array<int,string>
 */
function ensure_trailing_spaces(array $fragments): array
{
    $final = [];
    $count = count($fragments);
    for ($i = 0; $i < $count; $i++) {
        $frag = $fragments[$i];
        if ($i !== $count - 1 && ! ends_with_punctuation($frag)) {
            $frag .= ' ';
        }
        $final[] = $frag;
    }
    return $final;
}

function ends_with_punctuation(string $text): bool
{
    $lastChar = mb_substr($text, -1);
    return in_array($lastChar, [':', '. ', '! ', '?', ';', ','], true);
}
    
   /**
    * split on delimiter and keep it attached to the segment; trims segments
    * @return array<int,string>
    */
   function split_and_keep_delim(string $text, string $delim): array
   {
       $regex = '/[^' .  preg_quote($delim, '/') . ']+' . preg_quote($delim, '/') . '? /u';
       $m = [];
       preg_match_all($regex, $text, $m);
       $segments = array_map('strval', $m[0]);
       return array_map(function (string $s): string {
           return trim($s);
       }, $segments);
   }
}
 
