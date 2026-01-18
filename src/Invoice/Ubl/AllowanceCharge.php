<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use App\Invoice\Setting\SettingRepository;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AllowanceCharge implements XmlSerializable
{
    public function __construct(
        private readonly bool $chargeIndicator,
        private readonly ?int $allowanceChargeReasonCode,
        private readonly ?string $allowanceChargeReason,
        private readonly ?int $multiplierFactorNumeric,
        private readonly ?float $baseAmount,
        private readonly float $amount,
        private readonly ?TaxTotal $taxTotal,
        private readonly ?TaxCategory $taxCategory,
        private SettingRepository $s)
    {
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ChargeIndicator' => $this->chargeIndicator ?
                                                            'true' : 'false',
        ]);

        if ($this->allowanceChargeReasonCode !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReasonCode' =>
                    $this->allowanceChargeReasonCode,
            ]);
        }

        if ($this->allowanceChargeReason !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReason' =>
                    $this->allowanceChargeReason,
            ]);
        }

        if ($this->multiplierFactorNumeric !== null) {
            $writer->write([
                Schema::CBC . 'MultiplierFactorNumeric' =>
                    $this->multiplierFactorNumeric,
            ]);
        }

        $writer->write([
            [
                'name' => Schema::CBC . 'Amount',
                'value' => $this->s->currency_converter(
                 number_format($this->amount ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' =>
                                $this->s->getSetting('peppol_document_currency'),
                ],
            ],
        ]);

        if ($this->taxCategory !== null) {
            $writer->write([
                Schema::CAC . 'TaxCategory' => $this->taxCategory,
            ]);
        }

        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal,
            ]);
        }

        if ($this->baseAmount !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'BaseAmount',
                    'value' => $this->baseAmount,
                    'attributes' => [
                        'currencyID' =>
                                $this->s->getSetting('peppol_document_currency'),
                    ],
                ],
            ]);
        }
    }
}
