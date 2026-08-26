<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * A single `cac:OrderLine/cac:LineItem` in an OrderResponseAdvanced --
 * mirrors the buyer's original `cac:OrderLine/cac:LineItem` shape parsed by
 * UblOrderXmlParser, but responding rather than ordering: carries a
 * LineStatusCode instead of a price/quantity request, and an
 * OrderLineReference back to the buyer's own line id (SalesOrderItem's
 * peppol_po_lineid, captured on import -- see docs.peppol.eu/poacc/upgrade-3/
 * syntax/OrderResponse/tree/cac-OrderLine/cac-LineItem/).
 */
class OrderResponseLine implements XmlSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly string $lineStatusCode,
        private readonly ?string $orderLineReferenceLineId,
        private readonly ?string $itemName,
    ) {}

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CAC . 'LineItem' => [
                Schema::CBC . 'ID' => $this->id,
                Schema::CBC . 'LineStatusCode' => $this->lineStatusCode,
                Schema::CAC . 'OrderLineReference' => [
                    Schema::CBC . 'LineID' =>
                        $this->orderLineReferenceLineId ?? $this->id,
                ],
                Schema::CAC . 'Item' => [
                    Schema::CBC . 'Name' => $this->itemName ?? '',
                ],
            ],
        ]);
    }
}
