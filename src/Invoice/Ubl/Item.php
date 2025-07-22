<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Item implements XmlSerializable
{
    public function __construct(private ?string $description, private string $name, private ?string $buyersItemIdentification, private ?string $sellersItemIdentification, private ?ClassifiedTaxCategory $classifiedTaxCategory)
    {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSellersItemIdentification(): ?string
    {
        return $this->sellersItemIdentification;
    }

    public function setSellersItemIdentification(?string $sellersItemIdentification): self
    {
        $this->sellersItemIdentification = $sellersItemIdentification;

        return $this;
    }

    public function getBuyersItemIdentification(): ?string
    {
        return $this->buyersItemIdentification;
    }

    public function setBuyersItemIdentification(?string $buyersItemIdentification): self
    {
        $this->buyersItemIdentification = $buyersItemIdentification;

        return $this;
    }

    public function getClassifiedTaxCategory(): ?ClassifiedTaxCategory
    {
        return $this->classifiedTaxCategory;
    }

    public function setClassifiedTaxCategory(?ClassifiedTaxCategory $classifiedTaxCategory): self
    {
        $this->classifiedTaxCategory = $classifiedTaxCategory;

        return $this;
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if (null !== $this->getDescription()) {
            $writer->write([
                Schema::CBC.'Description' => $this->description,
            ]);
        }

        $writer->write([
            Schema::CBC.'Name' => $this->name,
        ]);

        if (null !== $this->getBuyersItemIdentification()) {
            $writer->write([
                Schema::CAC.'BuyersItemIdentification' => [
                    Schema::CBC.'ID' => $this->buyersItemIdentification,
                ],
            ]);
        }

        if (null !== $this->sellersItemIdentification) {
            $writer->write([
                Schema::CAC.'SellersItemIdentification' => [
                    Schema::CBC.'ID' => $this->sellersItemIdentification,
                ],
            ]);
        }

        if (null !== $this->classifiedTaxCategory) {
            $writer->write([
                Schema::CAC.'ClassifiedTaxCategory' => $this->classifiedTaxCategory,
            ]);
        }
    }
}
