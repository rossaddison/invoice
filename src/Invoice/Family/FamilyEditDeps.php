<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\FamilyCustom\FamilyCustomRepository as fcR;

final class FamilyEditDeps
{
    public function __construct(
        public readonly fR $fR,
        public readonly fcR $fcR,
        public readonly cfR $cfR,
        public readonly cvR $cvR,
        public readonly cpR $cpR,
        public readonly csR $csR,
    ) {}
}
