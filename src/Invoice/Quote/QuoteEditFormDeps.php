<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    QuoteCustom\QuoteCustomRepository as QCR,
};
use App\User\UserRepository as UR;

final class QuoteEditFormDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly QCR $qcR,
        public readonly UR $uR,
    ) {}
}
