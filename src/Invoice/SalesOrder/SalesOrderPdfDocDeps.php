<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;

final class SalesOrderPdfDocDeps
{
    public function __construct(
        public readonly CR $cR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
    ) {
    }
}
