<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Price implements XmlSerializable
{
    private string $priceAmount;
    private string $baseQuantity;
    private string $unitCode = UnitCode::UNIT;
    private string $unitCodeListId;
    private ?AllowanceCharge $allowanceCharge;

    public function __construct(?AllowanceCharge $allowanceCharge, string $priceAmount, string $baseQuantity, string $unitCodeListId ) {
        $this->allowanceCharge = $allowanceCharge;
        $this->baseQuantity = $baseQuantity;
        $this->unitCodeListId = $unitCodeListId;
        $this->priceAmount = $priceAmount;
    }
    
    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=Price
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $baseQuantityAttributes = [
            'unitCode' => $this->unitCode,
        ];

        if (!empty($this->unitCodeListId)) {
            $baseQuantityAttributes['unitCodeListID'] = $this->unitCodeListId;
        }

        $writer->write([
            [
                'name' => Schema::CBC . 'PriceAmount',
                'value' => number_format((float)$this->priceAmount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
            [
                'name' => Schema::CBC . 'BaseQuantity',
                'value' => number_format((float)$this->baseQuantity ?: 0, 2, '.', ''),
                'attributes' => $baseQuantityAttributes
            ]
        ]);

        if ($this->allowanceCharge !== null) {
            $writer->write([
                Schema::CAC . 'AllowanceCharge' => $this->allowanceCharge,
            ]);
        }
    }
}
