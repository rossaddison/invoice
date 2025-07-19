<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class OrderReference implements XmlSerializable
{
    public function __construct(private readonly ?string $id, private readonly ?string $salesOrderId) {}

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=SalesOrderId
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->id !== null) {
            $writer->write([ Schema::CBC . 'ID' => $this->id ]);
        }
        if ($this->salesOrderId !== null) {
            $writer->write([ Schema::CBC . 'SalesOrderID' => $this->salesOrderId ]);
        }
    }
}
