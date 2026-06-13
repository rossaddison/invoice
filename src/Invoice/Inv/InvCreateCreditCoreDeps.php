<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;

final class InvCreateCreditCoreDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly GR $gR,
        public readonly IIR $iiR,
        public readonly IIAR $iiaR,
    ) {
    }
}
