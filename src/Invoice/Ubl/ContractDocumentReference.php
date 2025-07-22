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

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if (null !== $this->id) {
            $writer->write([Schema::CBC.'ID' => $this->id]);
        }
    }
}
