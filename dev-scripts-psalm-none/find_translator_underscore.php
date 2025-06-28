<?php

declare(strict_types=1);
/**
 * Recursively search for $translator->translate('...') or $translator->translate("...")
 * where the key contains an underscore, and replace all underscores in the key with a full stop.
 * The script updates files in place.
 */

$searchDirs = [__DIR__ . '/src', __DIR__ . '/resources']; // Adjust/add directories as needed

function replaceTranslatorKeys($dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'phtml', 'twig'])) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        $modified = false;
        // Callback replacement: replace all underscores in the matched key with a dot
        $contents = preg_replace_callback(
            "/(\\\$translator->translate\(['\"])([^'\"]*_[^'\"]*)(['\"]\))/",
            function ($m) use (&$modified) {
                $replacedKey = str_replace('_', '.', $m[2]);
                $modified = true;
                return $m[1] . $replacedKey . $m[3];
            },
            $contents
        );

        if ($modified) {
            file_put_contents($file->getPathname(), $contents);
            echo 'Updated: ' . $file->getPathname() . "\n";
        }
    }
}

foreach ($searchDirs as $dir) {
    if (is_dir($dir)) {
        replaceTranslatorKeys($dir);
    }
}

echo "Done.\n";
