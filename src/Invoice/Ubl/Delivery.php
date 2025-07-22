<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Delivery implements XmlSerializable
{
    public function __construct(private readonly ?\DateTime $actualDeliveryDate, private array $deliveryLocationID_scheme, private readonly ?Address $deliveryLocation, private readonly ?Party $deliveryParty)
    {
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if (null !== $this->actualDeliveryDate) {
            $writer->write([
                Schema::CBC.'ActualDeliveryDate' => $this->actualDeliveryDate->format('Y-m-d'),
            ]);
        }
        if (null !== $this->deliveryLocation) {
            $writer->write([
                ['name'     => Schema::CAC.'DeliveryLocation',
                    'value' => [[
                        'name'       => Schema::CBC.'ID',
                        'value'      => [$this->deliveryLocationID_scheme['ID']],
                        'attributes' => $this->deliveryLocationID_scheme['attributes']],
                        ['name'     => Schema::CAC.'Address',
                            'value' => $this->deliveryLocation],
                    ],
                ]]);
        }
        if (null !== $this->deliveryParty) {
            $writer->write([
                Schema::CAC.'DeliveryParty' => $this->deliveryParty,
            ]);
        }
    }
}
