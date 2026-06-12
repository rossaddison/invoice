<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\{
    ClientPeppol\ClientPeppolRepository as cpR,
    InvAmount\InvAmountRepository as IAR,
    InvItem\InvItemRepository as IIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    PostalAddress\PostalAddressRepository as paR,
    SalesOrder\SalesOrderRepository as SOR,
};

final class PeppolHelperInvDeps
{
    public function __construct(
        public readonly SOR $soR,
        public readonly IAR $iaR,
        public readonly IIAR $iiaR,
        public readonly IIR $iiR,
        public readonly paR $paR,
        public readonly cpR $cpR,
    ) {}
}
