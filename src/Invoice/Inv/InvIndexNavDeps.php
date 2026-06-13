<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\UserClient\UserClientRepository as UCR;

final class InvIndexNavDeps
{
    public function __construct(
        public readonly QR $qR,
        public readonly PMR $pmR,
        public readonly SOR $soR,
        public readonly DLR $dlR,
        public readonly UCR $ucR,
    ) {
    }
}
