<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use App\Invoice\Setting\SettingRepository;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Price implements XmlSerializable
{
    private string $unitCode = UnitCode::UNIT;

    public function __construct(
        private readonly ?AllowanceCharge $allowanceCharge,
        private readonly string $priceAmount,
        private readonly string $baseQuantity,
        private readonly string $unitCodeListId,
        private SettingRepository $s,
    )
    {
    }

    /**
     * Related logic: see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=Price
     * @param Writer $writer
     */
    #[\Override]
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
                'value' => $this->s->currency_converter(
                 number_format((float) $this->priceAmount ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' =>
                                $this->s->getSetting('peppol_document_curency'),
                ],
            ],
            [
                'name' => Schema::CBC . 'BaseQuantity',
                'value' =>
                    number_format((float) $this->baseQuantity ?: 0, 2, '.', ''),
                'attributes' => $baseQuantityAttributes,
            ],
        ]);

        if ($this->allowanceCharge !== null) {
            $writer->write([
                Schema::CAC . 'AllowanceCharge' => $this->allowanceCharge,
            ]);
        }
    }
}
