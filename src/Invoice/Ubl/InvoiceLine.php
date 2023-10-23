<?php
// Note this UBL is not currently being used. Refer to Invoice and PeppolHelper function build_invoice_lines_array
namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class InvoiceLine implements XmlSerializable
{
    private string $id;
    protected float $invoicedQuantity;
    private float $lineExtensionAmount;
    private string $unitCode = UnitCode::UNIT;
    private ?string $unitCodeListId;
    private ?TaxTotal $taxTotal;
    private ?InvoicePeriod $invoicePeriod;
    private ?string $note;
    private ?Item $item;
    private ?Price $price;
    private ?string $accountingCostCode;
    private ?string $accountingCost;

    // See CreditNoteLine.php
    protected bool $isCreditNoteLine = false;

    public function __construct(
            string $id, float $invoicedQuantity, float $lineExtensionAmount,
            ?string $unitCodeListId, ?TaxTotal $taxTotal, ?InvoicePeriod $invoicePeriod,
            ?string $note, ?Item $item, ?Price $price, ?string $accountingCostCode, ?string $accountingCost
    ) {
            $this->id = $id;
            $this->invoicedQuantity = $invoicedQuantity;
            $this->lineExtensionAmount = $lineExtensionAmount;
            $this->unitCodeListId = $unitCodeListId;
            $this->taxTotal = $taxTotal;
            $this->invoicePeriod = $invoicePeriod;
            $this->note = $note;
            $this->item = $item;
            $this->price = $price;
            $this->accountingCostCode = $accountingCostCode;
            $this->accountingCost = $accountingCost;
    }
    
    /**
     * 
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
    public function setId(string $id): InvoiceLine
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
    public function setInvoicedQuantity(float $invoicedQuantity): InvoiceLine
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
    public function setLineExtensionAmount(float $lineExtensionAmount): InvoiceLine
    {
        $this->lineExtensionAmount = $lineExtensionAmount;
        return $this;
    }

    /**
     * @return null|TaxTotal
     */
    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    /**
     * @param null|TaxTotal $taxTotal
     * @return InvoiceLine
     */
    public function setTaxTotal(?TaxTotal $taxTotal): InvoiceLine
    {
        $this->taxTotal = $taxTotal;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param null|string $note
     * @return InvoiceLine
     */
    public function setNote(?string $note): InvoiceLine
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return null|InvoicePeriod
     */
    public function getInvoicePeriod(): ?InvoicePeriod
    {
        return $this->invoicePeriod;
    }

    /**
     * @param null|InvoicePeriod $invoicePeriod
     * @return InvoiceLine
     */
    public function setInvoicePeriod(?InvoicePeriod $invoicePeriod)
    {
        $this->invoicePeriod = $invoicePeriod;
        return $this;
    }

    /**
     * @return null|Item
     */
    public function getItem(): ?Item
    {
        return $this->item;
    }

    /**
     * @param null|Item $item
     * @return InvoiceLine
     */
    public function setItem(?Item $item): InvoiceLine
    {
        $this->item = $item;
        return $this;
    }

    /**
     * @return null|Price
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * @param null|Price $price
     * @return InvoiceLine
     */
    public function setPrice(?Price $price): InvoiceLine
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
    public function setUnitCode(string $unitCode): InvoiceLine
    {
        $this->unitCode = $unitCode;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getUnitCodeListId(): ?string
    {
        return $this->unitCodeListId;
    }

    /**
     * @param null|string $unitCodeListId
     * @return InvoiceLine
     */
    public function setUnitCodeListId(?string $unitCodeListId)
    {
        $this->unitCodeListId = $unitCodeListId;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getAccountingCostCode(): ?string
    {
        return $this->accountingCostCode;
    }

    /**
     * @param null|string $accountingCostCode
     * @return InvoiceLine
     */
    public function setAccountingCostCode(null|string $accountingCostCode): InvoiceLine
    {
        $this->accountingCostCode = $accountingCostCode;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getAccountingCost(): null|string
    {
        return $this->accountingCost;
    }

    /**
     * @param null|string $accountingCost
     * @return InvoiceLine
     */
    public function setAccountingCost(null|string $accountingCost): InvoiceLine
    {
        $this->accountingCost = $accountingCost;
        return $this;
    }
    
    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=InvoiceLine
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ID' => $this->id
        ]);

        if (null!==($this->getNote())) {
            $writer->write([
                Schema::CBC . 'Note' => $this->getNote()
            ]);
        }

        $invoicedQuantityAttributes = [
            'unitCode' => $this->unitCode,
        ];

        if (($this->getUnitCodeListId() !== null)) {
            $invoicedQuantityAttributes['unitCodeListID'] = $this->getUnitCodeListId();
        }

        $writer->write([
            [
                'name' => Schema::CBC .
                    ($this->isCreditNoteLine ? 'CreditedQuantity' : 'InvoicedQuantity'),
                'value' => number_format($this->invoicedQuantity, 2, '.', ''),
                'attributes' => $invoicedQuantityAttributes
            ],
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ]
        ]);

        if ($this->accountingCostCode !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCostCode' => $this->accountingCostCode
            ]);
        }
        if ($this->accountingCost !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCost' => $this->accountingCost
            ]);
        }
        if ($this->invoicePeriod !== null) {
            $writer->write([
                Schema::CAC . 'InvoicePeriod' => $this->invoicePeriod
            ]);
        }
        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal
            ]);
        }

        $writer->write([
            Schema::CAC . 'Item' => $this->item,
        ]);

        if ($this->price !== null) {
            $writer->write([
                Schema::CAC . 'Price' => $this->price
            ]);
        } else {
            $writer->write([
                Schema::CAC . 'TaxScheme' => null,
            ]);
        }
    }
}
