<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AllowanceCharge implements XmlSerializable
{
    public function __construct(private readonly bool $chargeIndicator, private readonly ?int $allowanceChargeReasonCode, private readonly ?string $allowanceChargeReason, private readonly ?int $multiplierFactorNumeric, private readonly ?float $baseAmount, private readonly float $amount, private readonly ?TaxTotal $taxTotal, private readonly ?TaxCategory $taxCategory)
    {
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC.'ChargeIndicator' => $this->chargeIndicator ? 'true' : 'false',
        ]);

        if (null !== $this->allowanceChargeReasonCode) {
            $writer->write([
                Schema::CBC.'AllowanceChargeReasonCode' => $this->allowanceChargeReasonCode,
            ]);
        }

        if (null !== $this->allowanceChargeReason) {
            $writer->write([
                Schema::CBC.'AllowanceChargeReason' => $this->allowanceChargeReason,
            ]);
        }

        if (null !== $this->multiplierFactorNumeric) {
            $writer->write([
                Schema::CBC.'MultiplierFactorNumeric' => $this->multiplierFactorNumeric,
            ]);
        }

        $writer->write([
            [
                'name'       => Schema::CBC.'Amount',
                'value'      => $this->amount,
                'attributes' => [
                    'currencyID' => Generator::$currencyID,
                ],
            ],
        ]);

        if (null !== $this->taxCategory) {
            $writer->write([
                Schema::CAC.'TaxCategory' => $this->taxCategory,
            ]);
        }

        if (null !== $this->taxTotal) {
            $writer->write([
                Schema::CAC.'TaxTotal' => $this->taxTotal,
            ]);
        }

        if (null !== $this->baseAmount) {
            $writer->write([
                [
                    'name'       => Schema::CBC.'BaseAmount',
                    'value'      => $this->baseAmount,
                    'attributes' => [
                        'currencyID' => Generator::$currencyID,
                    ],
                ],
            ]);
        }
    }
}
