<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Client\ClientRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;

final class PaymentEditDeps
{
    public function __construct(
        public readonly PaymentRepository $pmtR,
        public readonly InvRepository $invR,
        public readonly InvAmountRepository $iaR,
        public readonly PaymentMethodRepository $pmtMethodR,
        public readonly PaymentCustomRepository $pcR,
        public readonly CustomFieldRepository $cfR,
        public readonly CustomValueRepository $cvR,
        public readonly ClientRepository $cR,
    ) {}
}
