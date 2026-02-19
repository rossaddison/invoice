<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class ConfigurationFileNotFoundException extends RuntimeException
{
    public function __construct(string $filePath)
    {
        parent::__construct('Configuration file not found: ' . $filePath);
    }
}
