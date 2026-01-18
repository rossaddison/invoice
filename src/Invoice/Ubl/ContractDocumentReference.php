<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Related Logic: PeppolUblXml function xml
 * If the $cdrId is null this CBC will not be invoked.
 * Neither will the CAC.
 */
class ContractDocumentReference implements XmlSerializable
{
    public function __construct(private readonly ?string $cdrId)
    {
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->cdrId !== null) {
            $writer->write([ Schema::CBC . 'ID' => $this->cdrId ]);
        }
    }
}
