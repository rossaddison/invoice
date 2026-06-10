<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;

final class InvViewMetaDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly ETR $etR,
        public readonly PMR $pmR,
        public readonly TRR $trR,
        public readonly UNR $unR,
    ) {
    }
}
