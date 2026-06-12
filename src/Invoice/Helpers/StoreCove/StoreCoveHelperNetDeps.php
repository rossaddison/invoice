<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use App\Invoice\{
    Contract\ContractRepository as ContractRepo,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryParty\DeliveryPartyRepository as DelPartyRepo,
    ProductProperty\ProductPropertyRepository as ppR,
    UnitPeppol\UnitPeppolRepository as unpR,
    Upload\UploadRepository as upR,
};

final class StoreCoveHelperNetDeps
{
    public function __construct(
        public readonly ContractRepo $contractRepo,
        public readonly DelRepo $delRepo,
        public readonly DelPartyRepo $delPartyRepo,
        public readonly unpR $unpR,
        public readonly upR $upR,
        public readonly ppR $ppR,
    ) {}
}
