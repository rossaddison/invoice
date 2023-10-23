<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Country implements XmlSerializable {

    private string $identificationCode;
    private ?string $listId;

    public function __construct(string $identificationCode, ?string $listId) {
        $this->identificationCode = $identificationCode;
        $this->listId = $listId;
    }

    // used in StoreCoveHelper 
    public function getIdentificationCode(): string {
        return $this->identificationCode;
    }

    /**
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void {
        $attributes = [];

        if (!empty($this->listId)) {
            // Alpha 2 => 2 digit country code
            $attributes['listID'] = 'ISO3166-1:Alpha2';
        }

        $writer->write([
            'name' => Schema::CBC . 'IdentificationCode',
            'value' => $this->identificationCode,
            /**
             * Warning
             * Location: invoice_a-362E8wINV107_peppol
             * Element/context: /:Invoice[1]
             * XPath test: not(//cac:Country/cbc:IdentificationCode/@listID)
             * Error message: [UBL-CR-660]-A UBL invoice should not include the Country Identification code listID
             */
            //'attributes' => $attributes
        ]);
    }

}
