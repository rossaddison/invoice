<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;

final class InvEditFormDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly ICR $icR,
        public readonly PMR $pmRepo,
        public readonly IAR $iaR,
    ) {
    }
}
