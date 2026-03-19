<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Country implements XmlSerializable
{
    public function __construct(
        private readonly string $identificationCode,
        private readonly ?string $listId
    )
    {
    }

    // used in StoreCoveHelper
    public function getIdentificationCode(): string
    {
        return $this->identificationCode;
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name' => Schema::CBC . 'IdentificationCode',
            'value' => $this->identificationCode,
            /**
             * Warning
             * Location: invoice_a-362E8wINV107_peppol
             * Element/context: /:Invoice[1]
             * XPath test: not(//cac:Country/cbc:IdentificationCode/@listID)
             * Error message: [UBL-CR-660]-A UBL invoice should not include the
                  Country Identification code listID
             */
        ]);
    }
}
