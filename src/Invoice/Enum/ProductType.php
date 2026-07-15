<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Used in ..\resources\views\invoice\product\_form.php
// Accessible by Invoice ... Products

// Note: this enum format is simply used to build dropdown options and for
// type-safe comparisons in code. The value is stored as a plain string
// column on Product, not as a native enum column (same convention as
// StoreCoveTaxType).

enum ProductType: string
{
    case Product = 'product';
    case Service = 'service';
}
