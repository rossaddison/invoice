<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class ContractDocumentReference implements XmlSerializable
{
    public function __construct(private readonly ?string $id)
    {
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->id !== null) {
            $writer->write([ Schema::CBC . 'ID' => $this->id ]);
        }
    }
}
