<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Ubl\{AdditionalDocumentReference, CreditNote, Delivery, Generator, Invoice, Party};
use Sabre\Xml\Writer;

final readonly class PeppolUblXml
{
    public function __construct(private sR $sR)
    {
    }

    public function xml(
        PeppolInvoiceHeader $header,
        AdditionalDocumentReference $additionalDocumentReference,
        Party $supplierParty,
        Party $customerParty,
        Delivery $delivery,
        PeppolPaymentData $payment,
        PeppolFinancialData $financial,
    ): Invoice {
        $documentClass = ($header->references->isCreditNote ?? false)
            ? CreditNote::class : Invoice::class;
        return new $documentClass(
            $this->sR,
            $header->profileID,
            (string) $header->id,
            $header->dates->issueDate,
            $header->dates->dueDate,
            $header->note,
            $header->dates->taxPointDate,
            $header->accountingCostCode,
            $header->buyerReference,
            $header->dates->invoicePeriod,
            $header->references->orderReference,
            $header->references->contractDocumentReference,
            $additionalDocumentReference,
            $supplierParty,
            $customerParty,
            $delivery,
            $payment->paymentMeans,
            $payment->paymentTerms,
            $financial->allowanceCharges,
            $financial->taxAmounts,
            $financial->taxSubtotal,
            $financial->legalMonetaryTotal,
            $financial->invoiceLines,
            $header->references->isCopyIndicator,
            $header->references->supplierAssignedAccountID,
        );
    }

    /**
     * @param Invoice $ubl_invoice
     * @return string
     */
    public function output(Invoice $ubl_invoice): string
    {
        $ubl_invoice->setDocumentCurrencyCode();
        $writer = new Writer();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->text($ubl_invoice instanceof CreditNote
            ? Generator::creditNote($ubl_invoice, $ubl_invoice->getDocumentCurrencyCode())
            : Generator::invoice($ubl_invoice, $ubl_invoice->getDocumentCurrencyCode()));
        $writer->endDocument();
        return $writer->outputMemory();
    }
}
