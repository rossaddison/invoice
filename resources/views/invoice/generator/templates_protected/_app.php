<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function google_translate_lang
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
        echo "'" . $key . "' => '" . $value . "',\n";
    }?>
];