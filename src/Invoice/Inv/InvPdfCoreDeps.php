<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvPdfCoreDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly IAR $iaR,
        public readonly GR $gR,
        public readonly SOR $soR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
