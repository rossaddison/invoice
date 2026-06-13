<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;

final class IiacDeleteSubEntityDeps
{
    public function __construct(
        public readonly InvItemRepository $iiR,
        public readonly InvItemAmountRepository $iiaR,
        public readonly InvAllowanceChargeRepository $aciR,
        public readonly InvItemAllowanceChargeRepository $aciiR,
    ) {}
}
