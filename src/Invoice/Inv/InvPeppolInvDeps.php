<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;

final class InvPeppolInvDeps
{
    public function __construct(
        public readonly IAR $iaR,
        public readonly IIR $iiR,
    ) {
    }
}
