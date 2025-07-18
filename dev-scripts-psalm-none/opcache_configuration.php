<?php

declare(strict_types=1);

if (function_exists('opcache_get_configuration')) {
    $config = opcache_get_configuration();
    print_r($config);
} else {
    echo 'Opcache extension is not available.';
}
