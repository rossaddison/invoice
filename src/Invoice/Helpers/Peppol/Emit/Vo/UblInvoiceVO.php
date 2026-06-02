<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

/**
 * Typed VO for a PEPPOL BIS Billing 3.0 Invoice or CreditNote.
 *
 * Covers both document types — the same VO structure maps to cac:InvoiceLine
 * and cac:CreditNoteLine via the single $invoiceLines property.
 *
 * Built once from the XML by a hydrator; passed to all generated rule functions.
 * No DOM, no XPath at validation time.
 *
 * @psalm-suppress UnusedClass
 */
readonly class UblInvoiceVO
{
    /**
     * @param UblTaxTotalVO[]        $taxTotals
     * @param UblInvoiceLineVO[]     $invoiceLines         Covers both InvoiceLine and CreditNoteLine.
     * @param UblAllowanceChargeVO[] $allowanceCharges
     * @param UblPaymentMeansVO[]    $paymentMeans
     * @param string[]               $additionalDocumentReferenceIds
     */
    public function __construct(
        // ── Document identity ──────────────────────────────────────────────
        public string                 $profileId,
        public string                 $customizationId,
        public string                 $id,
        public string                 $issueDate,
        public string                 $invoiceTypeCode,
        public string                 $documentCurrencyCode,
        public ?string                $taxCurrencyCode,
        public ?string                $dueDate,
        public ?string                $note,

        // ── References ─────────────────────────────────────────────────────
        public ?string                $buyerReference,
        public ?string                $accountingCost,
        public ?UblOrderReferenceVO   $orderReference,
        public ?string                $contractDocumentReferenceId,
        public array                  $additionalDocumentReferenceIds,

        // ── Parties ────────────────────────────────────────────────────────
        public UblPartyVO             $supplier,
        public UblPartyVO             $customer,
        public ?UblPartyVO            $taxRepresentativeParty,
        public ?string                $deliveryLocationId,
        public ?string                $deliveryCountryCode,
        public ?string                $actualDeliveryDate,

        // ── Financials ─────────────────────────────────────────────────────
        public UblLegalMonetaryTotalVO $legalMonetaryTotal,
        public array                  $taxTotals,
        public array                  $invoiceLines,
        public array                  $allowanceCharges,
        public array                  $paymentMeans,
        public ?string                $paymentTermsNote,
        public ?string                $paymentId,
    ) {}
}
