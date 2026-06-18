<?php

declare(strict_types=1);

// Note this UBL is not currently being used. Refer to Invoice and
//  PeppolHelper function buildInvoiceLinesArray

namespace App\Invoice\Ubl;

use App\Invoice\Setting\SettingRepository;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class InvoiceLine implements XmlSerializable
{
    // See CreditNoteLine.php
    protected bool $isCreditNoteLine = false;

    public function __construct(
        private string $id,
        protected float $invoicedQuantity,
        private float $lineExtensionAmount,
        private ?TaxTotal $taxTotal,
        private ?InvoicePeriod $invoicePeriod,
        private ?string $note,
        private ?Item $item,
        private ?Price $price,
        private SettingRepository $s,
        private InvoiceLineAccountingFields $accountingFields = new InvoiceLineAccountingFields(),
    ) {}

    public function reqId(): string
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

    public function setInvoicePeriod(?InvoicePeriod $invoicePeriod): self
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

    public function getAccountingFields(): InvoiceLineAccountingFields
    {
        return $this->accountingFields;
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $af = $this->accountingFields;

        $writer->write([
            Schema::CBC . 'ID' => $this->id,
        ]);

        if (null !== $this->getNote()) {
            $writer->write([
                Schema::CBC . 'Note' => $this->getNote(),
            ]);
        }

        $invoicedQuantityAttributes = [
            'unitCode' => $af->getUnitCode(),
        ];

        if ($af->getUnitCodeListId() !== null) {
            $invoicedQuantityAttributes['unitCodeListID'] = $af->getUnitCodeListId();
        }

        $writer->write([
            [
                'name' => Schema::CBC
                    . ($this->isCreditNoteLine ?
                        'CreditedQuantity' : 'InvoicedQuantity'),
                'value' => number_format(
                        $this->invoicedQuantity ?: 0, 2, '.', ''),
                'attributes' => $invoicedQuantityAttributes,
            ],
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => $this->s->currencyConverter(
                 number_format($this->lineExtensionAmount ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' =>
                        $this->s->getSetting('peppol_document_currency'),
                ],
            ],
        ]);

        if ($af->getAccountingCostCode() !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCostCode' => $af->getAccountingCostCode(),
            ]);
        }
        if ($af->getAccountingCost() !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCost' => $af->getAccountingCost(),
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
