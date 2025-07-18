<?php

declare(strict_types=1);

if (function_exists('opcache_get_status')) {
    $enabled = opcache_get_status()['opcache_enabled'];
    echo $enabled ? "Opcache is enabled" : "Opcache is disabled";
} else {
    echo "Opcache extension is not available";
}
