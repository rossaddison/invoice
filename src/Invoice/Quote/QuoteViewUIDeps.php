<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    AllowanceCharge\AllowanceChargeRepository as ACR,
    Client\ClientRepository as CR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    ProductImage\ProductImageRepository as PIR,
    Project\ProjectRepository as PROJECTR,
};

final class QuoteViewUIDeps
{
    public function __construct(
        public readonly DLR $dlR,
        public readonly PIR $piR,
        public readonly PROJECTR $projectR,
        public readonly ACR $acR,
        public readonly CR $cR,
        public readonly GR $gR,
    ) {}
}
