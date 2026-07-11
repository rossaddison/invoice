<?php

declare(strict_types=1);

use App\Invoice\Mcp\InvoiceCapabilities;
use App\Invoice\Mcp\InvoiceCapabilitiesInterface;

return [
    InvoiceCapabilitiesInterface::class => InvoiceCapabilities::class,
];
