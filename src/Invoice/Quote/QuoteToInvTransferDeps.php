<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvItemAmount\InvItemAmountService,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
};

final class QuoteToInvTransferDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly ACIIR $aciiR,
        public readonly IIAR $iiaR,
        public readonly InvItemAmountService $iiaS,
        public readonly QIAR $qiaR,
    ) {}
}
