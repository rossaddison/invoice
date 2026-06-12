<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
use App\Invoice\Upload\UploadRepository as UPR;

final class InvPeppolNetworkDeps
{
    public function __construct(
        public readonly ContractRepo $contractRepo,
        public readonly DelRepo $delRepo,
        public readonly DelPartyRepo $delPartyRepo,
        public readonly unpR $unpR,
        public readonly UPR $upR,
    ) {
    }
}
