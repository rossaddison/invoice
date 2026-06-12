<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    Family\FamilyRepository as FR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
};

final class QuoteViewRenderDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly TRR $trR,
        public readonly FR $fR,
        public readonly UNR $uR,
        public readonly TASKR $taskR,
    ) {}
}
