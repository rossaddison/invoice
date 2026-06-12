<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;

final class InvEditLocationDeps
{
    public function __construct(
        public readonly ContractRepo $contractRepo,
        public readonly DelRepo $deliveryRepo,
        public readonly DLR $delRepo,
        public readonly paR $paR,
    ) {
    }
}
