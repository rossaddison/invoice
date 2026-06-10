<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository as ACSOR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class SoViewRelationDeps
{
    public function __construct(
        public readonly ACSOIR $acsoiR,
        public readonly ACSOR $acsoR,
        public readonly DR $dR,
        public readonly QR $qR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
