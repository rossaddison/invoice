<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function googleTranslateLang
 * @var array $combined_array
 */

echo "<?php\n";
?>

declare(strict_types=1);

return [
<?php
    /**
     * @var string $key
     * @var string $value
     */
    foreach ($combined_array as $key => $value) {
        $rtrim = rtrim($value, ',');
        echo "'" . $key . "' => '" . $rtrim . "'," . "\n";
    }?>
];