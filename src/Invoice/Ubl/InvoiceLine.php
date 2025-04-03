<?php

declare(strict_types=1);

// Note this UBL is not currently being used. Refer to Invoice and PeppolHelper function build_invoice_lines_array

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class InvoiceLine implements XmlSerializable
{
    private string $unitCode = UnitCode::UNIT;

    // See CreditNoteLine.php
    protected bool $isCreditNoteLine = false;

    public function __construct(private string $id, protected float $invoicedQuantity, private float $lineExtensionAmount, private ?string $unitCodeListId, private ?TaxTotal $taxTotal, private ?InvoicePeriod $invoicePeriod, private ?string $note, private ?Item $item, private ?Price $price, private ?string $accountingCostCode, private ?string $accountingCost)
    {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return InvoiceLine
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return float
     */
    public function getInvoicedQuantity(): float
    {
        return $this->invoicedQuantity;
    }

    /**
     * @param float $invoicedQuantity
     * @return InvoiceLine
     */
    public function setInvoicedQuantity(float $invoicedQuantity): self
    {
        $this->invoicedQuantity = $invoicedQuantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getLineExtensionAmount(): float
    {
        return $this->lineExtensionAmount;
    }

    /**
     * @param float $lineExtensionAmount
     * @return InvoiceLine
     */
    public function setLineExtensionAmount(float $lineExtensionAmount): self
    {
        $this->lineExtensionAmount = $lineExtensionAmount;
        return $this;
    }

    /**
     * @return TaxTotal|null
     */
    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    /**
     * @param TaxTotal|null $taxTotal
     * @return InvoiceLine
     */
    public function setTaxTotal(?TaxTotal $taxTotal): self
    {
        $this->taxTotal = $taxTotal;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return InvoiceLine
     */
    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return InvoicePeriod|null
     */
    public function getInvoicePeriod(): ?InvoicePeriod
    {
        return $this->invoicePeriod;
    }

    /**
     * @param InvoicePeriod|null $invoicePeriod
     * @return InvoiceLine
     */
    public function setInvoicePeriod(?InvoicePeriod $invoicePeriod)
    {
        $this->invoicePeriod = $invoicePeriod;
        return $this;
    }

    /**
     * @return Item|null
     */
    public function getItem(): ?Item
    {
        return $this->item;
    }

    /**
     * @param Item|null $item
     * @return InvoiceLine
     */
    public function setItem(?Item $item): self
    {
        $this->item = $item;
        return $this;
    }

    /**
     * @return Price|null
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * @param Price|null $price
     * @return InvoiceLine
     */
    public function setPrice(?Price $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    /**
     * @param string $unitCode
     * @return InvoiceLine
     */
    public function setUnitCode(string $unitCode): self
    {
        $this->unitCode = $unitCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUnitCodeListId(): ?string
    {
        return $this->unitCodeListId;
    }

    /**
     * @param string|null $unitCodeListId
     * @return InvoiceLine
     */
    public function setUnitCodeListId(?string $unitCodeListId)
    {
        $this->unitCodeListId = $unitCodeListId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountingCostCode(): ?string
    {
        return $this->accountingCostCode;
    }

    /**
     * @param string|null $accountingCostCode
     * @return InvoiceLine
     */
    public function setAccountingCostCode(null|string $accountingCostCode): self
    {
        $this->accountingCostCode = $accountingCostCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountingCost(): null|string
    {
        return $this->accountingCost;
    }

    /**
     * @param string|null $accountingCost
     * @return InvoiceLine
     */
    public function setAccountingCost(null|string $accountingCost): self
    {
        $this->accountingCost = $accountingCost;
        return $this;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=InvoiceLine
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ID' => $this->id,
        ]);

        if (null !== $this->getNote()) {
            $writer->write([
                Schema::CBC . 'Note' => $this->getNote(),
            ]);
        }

        $invoicedQuantityAttributes = [
            'unitCode' => $this->unitCode,
        ];

        if ($this->getUnitCodeListId() !== null) {
            $invoicedQuantityAttributes['unitCodeListID'] = $this->getUnitCodeListId();
        }

        $writer->write([
            [
                'name' => Schema::CBC .
                    ($this->isCreditNoteLine ? 'CreditedQuantity' : 'InvoicedQuantity'),
                'value' => number_format($this->invoicedQuantity, 2, '.', ''),
                'attributes' => $invoicedQuantityAttributes,
            ],
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID,
                ],
            ],
        ]);

        if ($this->accountingCostCode !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCostCode' => $this->accountingCostCode,
            ]);
        }
        if ($this->accountingCost !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCost' => $this->accountingCost,
            ]);
        }
        if ($this->invoicePeriod !== null) {
            $writer->write([
                Schema::CAC . 'InvoicePeriod' => $this->invoicePeriod,
            ]);
        }
        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal,
            ]);
        }

        $writer->write([
            Schema::CAC . 'Item' => $this->item,
        ]);

        if ($this->price !== null) {
            $writer->write([
                Schema::CAC . 'Price' => $this->price,
            ]);
        } else {
            $writer->write([
                Schema::CAC . 'TaxScheme' => null,
            ]);
        }
    }
}
