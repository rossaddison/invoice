<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\FromDropDown\FromDropDownRepository as FDR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\Worker\WorkerRepository as WR;

final class InvIndexNavDeps
{
    public function __construct(
        public readonly QR $qR,
        public readonly PMR $pmR,
        public readonly SOR $soR,
        public readonly DLR $dlR,
        public readonly UCR $ucR,
        public readonly ETR $etR,
        public readonly FDR $fdR,
        public readonly WR $wR,
        public readonly CSR $csR,
    ) {
    }
}
