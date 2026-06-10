<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Widget\ButtonsToolbarFull;
use App\Widget\FormFields;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeService;

final class InvControllerUIDeps
{
    public function __construct(
        public readonly InvItemAllowanceChargeService $aciis,
        public readonly FormFields $formFields,
        public readonly ButtonsToolbarFull $buttonsToolbarFull,
        public readonly InvCustomFieldProcessor $customFieldProcessor,
    ) {
    }
}
