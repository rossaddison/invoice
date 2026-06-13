<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;

final class InvPeppolCoreDeps
{
    public function __construct(
        public readonly InvRepository $invRepo,
        public readonly IIAR $iiaR,
        public readonly cpR $cpR,
        public readonly DLR $dlR,
        public readonly paR $paR,
        public readonly SOR $soR,
    ) {
    }
}
