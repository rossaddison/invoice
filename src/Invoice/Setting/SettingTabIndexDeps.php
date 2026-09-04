<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ER;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PM;
use App\Invoice\TaxRate\TaxRateRepository as TR;

final class SettingTabIndexDeps
{
    public function __construct(
        public readonly ER $eR,
        public readonly GR $gR,
        public readonly PM $pm,
        public readonly TR $tR,
        public readonly CSR $csR,
    ) {
    }
}
