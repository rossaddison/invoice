<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AllowanceCharge implements XmlSerializable
{
    private bool $chargeIndicator;
    private ?int $allowanceChargeReasonCode;
    private ?string $allowanceChargeReason;
    private ?int $multiplierFactorNumeric;
    private ?float $baseAmount;
    private float $amount;
    private ?TaxTotal $taxTotal;
    private ?TaxCategory $taxCategory;
    
    public function __construct(
            bool $chargeIndicator, 
            ?int $allowanceChargeReasonCode, 
            ?string $allowanceChargeReason, 
            ?int $multiplierFactorNumeric, 
            ?float $baseAmount, 
            float $amount, 
            ?TaxTotal $taxTotal, 
            ?TaxCategory $taxCategory
        ) {
        $this->chargeIndicator = $chargeIndicator;
        $this->allowanceChargeReasonCode = $allowanceChargeReasonCode;
        $this->allowanceChargeReason = $allowanceChargeReason;
        $this->multiplierFactorNumeric = $multiplierFactorNumeric;
        $this->baseAmount = $baseAmount;
        $this->amount = $amount;
        $this->taxTotal = $taxTotal;
        $this->taxCategory = $taxCategory;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ChargeIndicator' => $this->chargeIndicator ? 'true' : 'false',
        ]);

        if ($this->allowanceChargeReasonCode !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReasonCode' => $this->allowanceChargeReasonCode
            ]);
        }

        if ($this->allowanceChargeReason !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReason' => $this->allowanceChargeReason
            ]);
        }

        if ($this->multiplierFactorNumeric !== null) {
            $writer->write([
                Schema::CBC . 'MultiplierFactorNumeric' => $this->multiplierFactorNumeric
            ]);
        }

        $writer->write([
            [
                'name' => Schema::CBC . 'Amount',
                'value' => $this->amount,
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
        ]);

        if ($this->taxCategory !== null) {
            $writer->write([
                Schema::CAC . 'TaxCategory' => $this->taxCategory
            ]);
        }

        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal
            ]);
        }

        if ($this->baseAmount !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'BaseAmount',
                    'value' => $this->baseAmount,
                    'attributes' => [
                        'currencyID' => Generator::$currencyID
                    ]
                ]
            ]);
        }
    }
}
