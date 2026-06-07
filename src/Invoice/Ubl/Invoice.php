<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

// Sabre
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Helpers\CurrencyHelper;
use DateTime;
use InvalidArgumentException;

class Invoice implements XmlSerializable
{
    // http://www.datypic.com/sc/ubl24/t-ns53_InvoiceType.html
    private ?string $UBLVersionID = '2.4';
    private ?string $customizationID = 'urn:cen.eu:en16931:2017#compliant#urn:'
            . 'fdc:peppol.eu:2017:poacc:billing:3.0';
    protected ?int $invoiceTypeCode = InvoiceTypeCode::INVOICE;
    private string $documentCurrencyCode = 'EUR';

    public function __construct(
     private readonly SettingRepository $sR,
     private readonly ?string $profileID,
     private readonly ?string $id,
     private readonly DateTime $issueDate,
     private readonly ?DateTime $dueDate,
     private readonly ?string $note,
     private readonly ?DateTime $taxPointDate,
     private readonly ?string $accountingCostCode,
     private readonly ?string $buyerReference,
     private readonly ?InvoicePeriod $invoicePeriod,
     private readonly ?OrderReference $orderReference,
     private readonly ?ContractDocumentReference $contractDocumentReference,
     private readonly ?AdditionalDocumentReference $additionalDocumentReference,
     private readonly ?Party $accountingSupplierParty,
     private readonly ?Party $accountingCustomerParty,
     private readonly ?Delivery $delivery,
     private readonly ?PaymentMeans $paymentMeans,
     private readonly ?PaymentTerms $paymentTerms,
     private readonly array $allowanceCharges,
     private readonly array $taxAmounts,
     private readonly array $taxSubTotal,
     private readonly ?LegalMonetaryTotal $legalMonetaryTotal,
     protected array $invoiceLines,
     private readonly ?bool $isCopyIndicator,
     private readonly ?string $supplierAssignedAccountID)
    {
    }

    /**
     * @return string|null
     */
    public function getUBLVersionID(): ?string
    {
        return $this->UBLVersionID;
    }

    /**
     * Related logic: http://www.schemacentral.com Business Document Standards
     * @param string|null $ublVersionID
     * eg. '2.1', '2.2', '2.3', '2.4'
     * @return Invoice
     */
    public function setUBLVersionID(?string $ublVersionID): self
    {
        $this->UBLVersionID = $ublVersionID;
        return $this;
    }

    /**
     * @return Invoice
     */
    public function setDocumentCurrencyCode(): self
    {
        /**
         * Amounts in the xml document are shown either in the
         * Sender's or the Receiver's currency. Default: Sender's
         */
        $this->documentCurrencyCode =
                $this->sR->getSetting('peppol_document_currency');
        return $this;
    }

    public function getDocumentCurrencyCode(): string
    {
        return $this->documentCurrencyCode;
    }

    /**
     * The validate function that is called during xml writing to validate the
     * data of the object.
     *
     * @throws InvalidArgumentException An error with information about
     * required data that is missing to write the XML
     */
    public function validate(): void
    {
        $mI = 'Missing invoice';
        if ($this->id === null) {
            throw new InvalidArgumentException($mI . ' id');
        }

        if ($this->note === null) {
            throw new InvalidArgumentException($mI . ' note');
        }

        if (!$this->issueDate instanceof DateTime) {
            throw new InvalidArgumentException('Invalid invoice issueDate');
        }

        if ($this->invoiceTypeCode === null) {
            throw new InvalidArgumentException($mI . ' invoiceTypeCode');
        }

        if ($this->accountingSupplierParty === null) {
            throw new InvalidArgumentException($mI . ' accountingSupplierParty');
        }

        if ($this->accountingCustomerParty === null) {
            throw new InvalidArgumentException($mI . ' accountingCustomerParty');
        }

        if (empty($this->invoiceLines)) {
            throw new InvalidArgumentException($mI . ' lines');
        }

        if ($this->legalMonetaryTotal === null) {
            throw new InvalidArgumentException($mI . ' LegalMonetaryTotal');
        }
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        $this->writeHeaderFields($writer);
        $this->writeReferenceFields($writer);
        $this->writePartyAndPaymentFields($writer);
        $this->writeTaxAndTotals($writer);
    }

    /**
     * Writes mandatory CBC header fields: IDs, dates, type code, currency.
     *
     * [UBL-CR-004] CopyIndicator must not appear — omitted intentionally.
     */
    private function writeHeaderFields(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'UBLVersionID'    => $this->UBLVersionID,
            Schema::CBC . 'CustomizationID' => $this->customizationID,
        ]);

        if ($this->profileID !== null) {
            $writer->write([Schema::CBC . 'ProfileID' => $this->profileID]);
        }

        $writer->write([Schema::CBC . 'ID' => $this->id]);

        $writer->write([
            Schema::CBC . 'IssueDate' => $this->issueDate->format('Y-m-d'),
        ]);

        if ($this->dueDate !== null) {
            $writer->write([
                Schema::CBC . 'DueDate' => $this->dueDate->format('Y-m-d'),
            ]);
        }

        $writer->write([Schema::CBC . 'InvoiceTypeCode' => $this->invoiceTypeCode]);

        if ($this->note !== null) {
            $writer->write([Schema::CBC . 'Note' => $this->note]);
        }

        if ($this->taxPointDate !== null) {
            $writer->write([
                Schema::CBC . 'TaxPointDate' => $this->taxPointDate->format('Y-m-d'),
            ]);
        }

        $currencyCode = $this->getDocumentCurrencyCode();
        $writer->write([
            Schema::CBC . 'DocumentCurrencyCode' =>
                $this->sR->getSetting('peppol_debug_with_emojis') == '1'
                    ? '➡' . $currencyCode . '➡'
                    : $currencyCode,
        ]);
    }

    /**
     * Writes optional reference fields: BuyerReference, InvoicePeriod,
     * OrderReference, ContractDocumentReference, AdditionalDocumentReference.
     *
     * [UBL-CR-010] AccountingCostCode must not appear — omitted intentionally.
     * [UBL-CR-114] AdditionalDocumentReference/DocumentType must not appear.
     */
    private function writeReferenceFields(Writer $writer): void
    {
        if ($this->buyerReference !== null) {
            $writer->write([Schema::CBC . 'BuyerReference' => $this->buyerReference]);
        }

        if ($this->invoicePeriod !== null) {
            $writer->write([Schema::CAC . 'InvoicePeriod' => $this->invoicePeriod]);
        }

        if ($this->orderReference !== null) {
            $writer->write([Schema::CAC . 'OrderReference' => $this->orderReference]);
        }

        if ($this->contractDocumentReference !== null) {
            $writer->write([
                Schema::CAC . 'ContractDocumentReference' => $this->contractDocumentReference,
            ]);
        }

        if ($this->additionalDocumentReference !== null) {
            $writer->write([
                Schema::CAC . 'AdditionalDocumentReference' => $this->additionalDocumentReference,
            ]);
        }
    }

    /**
     * Writes supplier/customer parties, delivery, payment means, payment terms,
     * and allowance charges.
     *
     * [UBL-CR-202] AccountingCustomerParty/SupplierAssignedAccountID must not
     * appear — omitted intentionally.
     */
    private function writePartyAndPaymentFields(Writer $writer): void
    {
        $writer->write([
            Schema::CAC . 'AccountingSupplierParty' => [
                Schema::CAC . 'Party' => $this->accountingSupplierParty,
            ],
            Schema::CAC . 'AccountingCustomerParty' => [
                Schema::CAC . 'Party' => $this->accountingCustomerParty,
            ],
        ]);

        if ($this->delivery !== null) {
            $writer->write([Schema::CAC . 'Delivery' => $this->delivery]);
        }

        if ($this->paymentMeans !== null) {
            $writer->write([Schema::CAC . 'PaymentMeans' => $this->paymentMeans]);
        }

        if ($this->paymentTerms !== null) {
            $writer->write([Schema::CAC . 'PaymentTerms' => $this->paymentTerms]);
        }

        if (!empty($this->allowanceCharges)) {
            /** @var AllowanceCharge $allowanceCharge */
            foreach ($this->allowanceCharges as $allowanceCharge) {
                $writer->write([Schema::CAC . 'AllowanceCharge' => $allowanceCharge]);
            }
        }
    }

    /**
     * Writes TaxTotal (with sub-totals), LegalMonetaryTotal, and InvoiceLines.
     */
    private function writeTaxAndTotals(Writer $writer): void
    {
        $this->validate();
        $tst = $this->taxAmounts;
        /** @var float $tst['supp_tax_cc_tax_amount'] */
        $suppTaxAmount = $tst['supp_tax_cc_tax_amount'] ?: 0.00;
        /** @var string $tst['supp_tax_cc'] */
        $suppCc = $tst['supp_tax_cc'] ?? '';

        $writer->write([
            [
                'name'  => Schema::CAC . 'TaxTotal',
                'value' => [
                    [
                        'name'       => Schema::CBC . 'TaxAmount',
                        'value'      => number_format($suppTaxAmount, 2, '.', ''),
                        'attributes' => ['currencyID' => $suppCc],
                    ],
                    [$this->buildTaxSubTotalsArray()],
                ],
            ],
        ]);

        $writer->write([
            Schema::CAC . 'LegalMonetaryTotal' => $this->legalMonetaryTotal,
        ]);

        /** @var array $invoiceLine */
        foreach ($this->invoiceLines as $invoiceLine) {
            $writer->write($invoiceLine);
        }
    }

    /*
     * Related logic: see PeppolHelper function buildTaxSubtotalArray
     * Take each Tax Category and build a tax sub total
     * @return array
     */
    public function buildTaxSubTotalsArray(): array
    {
        $merged_array = [];
        /**
         * @var array $value
         */
        foreach ($this->taxSubTotal as $value) {
            $tst = new TaxSubTotal($value, $this->sR);
            $merged_array[] = $tst->buildPreSerializedArray();
        }
        return $merged_array;
    }
}
