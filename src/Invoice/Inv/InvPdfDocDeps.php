<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;

final class InvPdfDocDeps
{
    public function __construct(
        public readonly CR $cR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly DLR $dlR,
        public readonly ICR $icR,
    ) {
    }
}
