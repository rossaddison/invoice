<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Testo\Assert;
use Testo\Test;

/**
 * Aliasing is a security measure as well as a convenience. Paths are resolved through Yiisoft\Aliases\Aliases (@root, @public, @img, @uploads ...), injected or taken from
 * SettingRepository, never from where a file happens to sit. An alias pins a path to one named, fixed root, so it
 * cannot drift outside it when the base directory moves or a web-root value is not what was assumed. A path built from __DIR__, getcwd() or
 * $_SERVER['DOCUMENT_ROOT'] breaks as soon as the base directory or the web root is changed, which is what
 * happened to the PDF watermark images.
 *
 * An alias depends on a base alias. The base (@root) is defined once, in config, and every other alias is relative
 * to it (@public, @img = @public/img ...). Most of the code frozen below defeats that by building its own
 * new Aliases([...]) whose base is dirname(__DIR__): each one is a separate base, so changing the real one does
 * not reach it. config/ is not scanned, because that is where the single base belongs.
 *
 * This is a ratchet. The code that already builds paths this way is frozen, per file, in
 * Fixtures/alias-offenders.txt (path TAB count). The tests fail when
 *  - a file has more of these than its baseline (a new one, or a new offending file);
 *  - a file has fewer (or none) than its baseline: lower or delete the line so the list only ever shrinks.
 *
 * The scan reads PHP tokens, so comments, docblocks and strings (translations mention __DIR__ as text) are not
 * counted. __FILE__ is not flagged: basename(__FILE__, '.php') names a template, it does not locate one.
 */
#[Test]
final class AliasUsageRatchetTest
{
    private const string BASELINE = __DIR__ . '/Fixtures/alias-offenders.txt';

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    /** @return array<string, int> repository-relative path => number of non-alias path constructions */
    private function offenders(): array
    {
        $found = [];
        foreach (['src', 'resources/views'] as $directory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->root() . '/' . $directory, FilesystemIterator::SKIP_DOTS),
            );
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $count = $this->occurrences((string) file_get_contents($file->getPathname()));
                if ($count > 0) {
                    $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($this->root()) + 1);
                    $found[$relative] = $count;
                }
            }
        }
        ksort($found);
        return $found;
    }

    private function occurrences(string $source): int
    {
        $tokens = \PhpToken::tokenize($source);
        $count = 0;
        foreach ($tokens as $index => $token) {
            if ($token->id === T_DIR) {
                $count++;
            } elseif ($token->id === T_STRING && strtolower($token->text) === 'getcwd' && $this->isCall($tokens, $index)) {
                $count++;
            } elseif (in_array($token->text, ["'DOCUMENT_ROOT'", '"DOCUMENT_ROOT"'], true)) {
                $count++;
            }
        }
        return $count;
    }

    /** @param list<\PhpToken> $tokens */
    private function isCall(array $tokens, int $index): bool
    {
        for ($next = $index + 1; $next < count($tokens); $next++) {
            if ($tokens[$next]->id === T_WHITESPACE) {
                continue;
            }
            return $tokens[$next]->text === '(';
        }
        return false;
    }

    /** @return array<string, int> */
    private function baseline(): array
    {
        $baseline = [];
        $lines = file(self::BASELINE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines === false ? [] : $lines as $line) {
            $parts = explode("\t", $line);
            $baseline[$parts[0]] = (int) ($parts[1] ?? 0);
        }
        return $baseline;
    }

    public function noFileBuildsMorePathsFromItsOwnLocationThanItsBaseline(): void
    {
        $baseline = $this->baseline();
        $worse = [];
        foreach ($this->offenders() as $path => $count) {
            if ($count > ($baseline[$path] ?? 0)) {
                $worse[$path] = $count . ' (baseline ' . ($baseline[$path] ?? 0) . ')';
            }
        }

        Assert::same($worse, [], 'Use the injected Aliases (@root, @public, @img, @uploads) instead of __DIR__/getcwd()');
    }

    public function theBaselineHoldsOnlyWhatIsStillThere(): void
    {
        $current = $this->offenders();
        $stale = [];
        foreach ($this->baseline() as $path => $count) {
            if (($current[$path] ?? 0) < $count) {
                $stale[$path] = 'now ' . ($current[$path] ?? 0) . ', baseline ' . $count;
            }
        }

        Assert::same($stale, [], 'Fewer now: lower or delete these lines in Fixtures/alias-offenders.txt');
    }

    public function theScanCountsCodeAndIgnoresCommentsAndStrings(): void
    {
        $code = '<?php $a = __DIR__ . "/x"; $b = getcwd(); $c = $_SERVER[\'DOCUMENT_ROOT\'];'
            . ' // __DIR__ in a comment' . "\n" . '/* getcwd() */ $d = \'__DIR__\'; $e = basename(__FILE__);';

        Assert::same($this->occurrences($code), 3);
        Assert::same($this->occurrences('<?php $_SERVER["DOCUMENT_ROOT"];'), 1);
    }

    public function theScanFindsTheOffendersItIsMeantToGuard(): void
    {
        Assert::true(count($this->offenders()) > 20);
        Assert::true(isset($this->offenders()['src/Invoice/Helpers/MpdfHelper.php']));
    }
}
