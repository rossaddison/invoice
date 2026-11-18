<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Testo\Assert;
use Testo\Test;

/**
 * Cycle reads a column type such as 'string(34)' with an unanchored pattern, so a stray bracket
 * ('string(34))', 'datetime)') is silently ignored and nothing complains. It is still a typo in the
 * declaration of a live table, so every #[Column(type: '...')] must have balanced brackets.
 */
#[Test]
final class EntityColumnTypesTest
{
    /**
     * @return list<string> "relative/path.php: 'type'" for each declared column type with unbalanced brackets
     */
    private function unbalancedColumnTypes(): array
    {
        $root = dirname(__DIR__, 3);
        $found = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (!str_contains($source, 'Cycle\Annotated\Annotation\Column')) {
                continue;
            }
            if (preg_match_all("/#\\[Column\\([^\\]]*?type: *'([^']*)'/", $source, $matches) === 0) {
                continue;
            }
            foreach ($matches[1] as $type) {
                if (substr_count($type, '(') !== substr_count($type, ')')) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1) . ": '" . $type . "'";
                }
            }
        }
        return $found;
    }

    public function everyDeclaredColumnTypeHasBalancedBrackets(): void
    {
        Assert::same($this->unbalancedColumnTypes(), []);
    }

    public function theScanActuallyFindsColumnDeclarations(): void
    {
        // guards the guard: an empty scan would pass the test above for the wrong reason
        $root = dirname(__DIR__, 3);
        $source = (string) file_get_contents($root . '/src/Infrastructure/Persistence/UserInv/UserInv.php');

        Assert::true(preg_match_all("/#\\[Column\\([^\\]]*?type: *'([^']*)'/", $source) > 20);
    }
}
