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

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return Item
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Item
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSellersItemIdentification(): ?string
    {
        return $this->sellersItemIdentification;
    }

    /**
     * @param string|null $sellersItemIdentification
     * @return Item
     */
    public function setSellersItemIdentification(?string $sellersItemIdentification): self
    {
        $this->sellersItemIdentification = $sellersItemIdentification;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBuyersItemIdentification(): ?string
    {
        return $this->buyersItemIdentification;
    }

    /**
     * @param string|null $buyersItemIdentification
     * @return Item
     */
    public function setBuyersItemIdentification(?string $buyersItemIdentification): self
    {
        $this->buyersItemIdentification = $buyersItemIdentification;
        return $this;
    }

    /**
     * @return ClassifiedTaxCategory|null
     */
    public function getClassifiedTaxCategory(): ?ClassifiedTaxCategory
    {
        return $this->classifiedTaxCategory;
    }

    /**
     * @param ClassifiedTaxCategory|null $classifiedTaxCategory
     * @return Item
     */
    public function setClassifiedTaxCategory(?ClassifiedTaxCategory $classifiedTaxCategory): self
    {
        $this->classifiedTaxCategory = $classifiedTaxCategory;
        return $this;
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->getDescription() !== null) {
            $writer->write([
                Schema::CBC . 'Description' => $this->description,
            ]);
        }

        $writer->write([
            Schema::CBC . 'Name' => $this->name,
        ]);

        if ($this->getBuyersItemIdentification() !== null) {
            $writer->write([
                Schema::CAC . 'BuyersItemIdentification' => [
                    Schema::CBC . 'ID' => $this->buyersItemIdentification,
                ],
            ]);
        }

        if ($this->sellersItemIdentification !== null) {
            $writer->write([
                Schema::CAC . 'SellersItemIdentification' => [
                    Schema::CBC . 'ID' => $this->sellersItemIdentification,
                ],
            ]);
        }

        if ($this->classifiedTaxCategory !== null) {
            $writer->write([
                Schema::CAC . 'ClassifiedTaxCategory' => $this->classifiedTaxCategory,
            ]);
        }
    }
}
