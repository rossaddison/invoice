<?php

declare(strict_types=1);

$file = __DIR__ . '/resources/messages/en/app_used.php';

if (!file_exists($file)) {
    fwrite(STDERR, "File not found: $file\n");
    exit(1);
}

$array = include $file;
if (!is_array($array)) {
    fwrite(STDERR, "File did not return an array: $file\n");
    exit(1);
}

ksort($array);

file_put_contents($file, "<?php\nreturn " . var_export($array, true) . ";\n");

echo "Array sorted by key and saved to $file\n";
