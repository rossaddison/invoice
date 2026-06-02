<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblPaymentMeansVO
{
    public function __construct(
        public string  $paymentMeansCode,
        public ?string $paymentId,
        public ?string $payeeFinancialAccountId,
        public ?string $payeeFinancialAccountSchemeId,
        public ?string $payeeFinancialAccountName,
        public ?string $cardPrimaryAccountNumberId,
        public ?string $cardHolderName,
        public ?string $paymentMandateId,
        public ?string $paymentMandatePayerAccountId,
    ) {}
}
