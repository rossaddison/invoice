<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class InvoicePeriod implements XmlSerializable
{
    public function __construct(private readonly string $startDate, private readonly string $endDate, private readonly string $descriptionCode)
    {
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    /**
     * The xmlSerialize method is called during xml writing.
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC.'StartDate' => $this->startDate ?: '',
        ]);

        $writer->write([
            Schema::CBC.'EndDate' => $this->endDate ?: '',
        ]);
        if ('' !== $this->descriptionCode) {
            $writer->write([
                Schema::CBC.'DescriptionCode' => $this->descriptionCode ?: '',
            ]);
        }
    }
}
