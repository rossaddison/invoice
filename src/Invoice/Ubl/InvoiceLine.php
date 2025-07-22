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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getInvoicedQuantity(): float
    {
        return $this->invoicedQuantity;
    }

    public function setInvoicedQuantity(float $invoicedQuantity): self
    {
        $this->invoicedQuantity = $invoicedQuantity;

        return $this;
    }

    public function getLineExtensionAmount(): float
    {
        return $this->lineExtensionAmount;
    }

    public function setLineExtensionAmount(float $lineExtensionAmount): self
    {
        $this->lineExtensionAmount = $lineExtensionAmount;

        return $this;
    }

    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?TaxTotal $taxTotal): self
    {
        $this->taxTotal = $taxTotal;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getInvoicePeriod(): ?InvoicePeriod
    {
        return $this->invoicePeriod;
    }

    /**
     * @return InvoiceLine
     */
    public function setInvoicePeriod(?InvoicePeriod $invoicePeriod)
    {
        $this->invoicePeriod = $invoicePeriod;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    public function setUnitCode(string $unitCode): self
    {
        $this->unitCode = $unitCode;

        return $this;
    }

    public function getUnitCodeListId(): ?string
    {
        return $this->unitCodeListId;
    }

    /**
     * @return InvoiceLine
     */
    public function setUnitCodeListId(?string $unitCodeListId)
    {
        $this->unitCodeListId = $unitCodeListId;

        return $this;
    }

    public function getAccountingCostCode(): ?string
    {
        return $this->accountingCostCode;
    }

    public function setAccountingCostCode(?string $accountingCostCode): self
    {
        $this->accountingCostCode = $accountingCostCode;

        return $this;
    }

    public function getAccountingCost(): ?string
    {
        return $this->accountingCost;
    }

    public function setAccountingCost(?string $accountingCost): self
    {
        $this->accountingCost = $accountingCost;

        return $this;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=InvoiceLine
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC.'ID' => $this->id,
        ]);

        if (null !== $this->getNote()) {
            $writer->write([
                Schema::CBC.'Note' => $this->getNote(),
            ]);
        }

        $invoicedQuantityAttributes = [
            'unitCode' => $this->unitCode,
        ];

        if (null !== $this->getUnitCodeListId()) {
            $invoicedQuantityAttributes['unitCodeListID'] = $this->getUnitCodeListId();
        }

        $writer->write([
            [
                'name' => Schema::CBC.
                    ($this->isCreditNoteLine ? 'CreditedQuantity' : 'InvoicedQuantity'),
                'value'      => number_format($this->invoicedQuantity, 2, '.', ''),
                'attributes' => $invoicedQuantityAttributes,
            ],
            [
                'name'       => Schema::CBC.'LineExtensionAmount',
                'value'      => number_format($this->lineExtensionAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID,
                ],
            ],
        ]);

        if (null !== $this->accountingCostCode) {
            $writer->write([
                Schema::CBC.'AccountingCostCode' => $this->accountingCostCode,
            ]);
        }
        if (null !== $this->accountingCost) {
            $writer->write([
                Schema::CBC.'AccountingCost' => $this->accountingCost,
            ]);
        }
        if (null !== $this->invoicePeriod) {
            $writer->write([
                Schema::CAC.'InvoicePeriod' => $this->invoicePeriod,
            ]);
        }
        if (null !== $this->taxTotal) {
            $writer->write([
                Schema::CAC.'TaxTotal' => $this->taxTotal,
            ]);
        }

        $writer->write([
            Schema::CAC.'Item' => $this->item,
        ]);

        if (null !== $this->price) {
            $writer->write([
                Schema::CAC.'Price' => $this->price,
            ]);
        } else {
            $writer->write([
                Schema::CAC.'TaxScheme' => null,
            ]);
        }
    }
}
