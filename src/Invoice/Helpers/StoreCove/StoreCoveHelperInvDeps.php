<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use App\Invoice\{
    ClientPeppol\ClientPeppolRepository as cpR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    PostalAddress\PostalAddressRepository as paR,
    SalesOrder\SalesOrderRepository as SOR,
};

final class StoreCoveHelperInvDeps
{
    public function __construct(
        public readonly SOR $soR,
        public readonly IIAR $iiaR,
        public readonly paR $paR,
        public readonly cpR $cpR,
    ) {}
}
