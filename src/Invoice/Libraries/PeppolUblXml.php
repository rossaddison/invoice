<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Ubl\{AdditionalDocumentReference, Address, Contact,
    ContractDocumentReference, Delivery, Generator, Invoice, InvoicePeriod,
    LegalMonetaryTotal, OrderReference, Party, PartyLegalEntity, PartyTaxScheme,
    PayeeFinancialAccount, PaymentTerms, PaymentMeans};
use Doctrine\Common\Collections\ArrayCollection;
use Sabre\Xml\Writer;
use Yiisoft\Translator\TranslatorInterface as Translator;
use DateTime;

final readonly class PeppolUblXml
{
    private ArrayCollection $items;
    private array $company;

    /**
     * @param sR $sR
     * @param Inv $invoice
     * @param iiaR $iiaR
     * @param InvAmount $inv_amount
     */
    public function __construct(private sR $sR, private Translator $t,
            private Inv $invoice, private iiaR $iiaR,
            private InvAmount $inv_amount)
    {
        $this->items = $this->invoice->getItems();
        $this->company = $this->sR->get_config_company_details();
    }

    public function xml(
        ?string $profileID,
        ?string $id,
        DateTime $issueDate,
        DateTime $dueDate,
        ?string $note,
        DateTime $taxPointDate,
        ?string $accountingCostCode,
        ?string $buyerReference,
        // InvoicePeriod
        string $start_date,
        string $end_date,
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
// cac-InvoicePeriod/cbc-DescriptionCode/
// The code of the date when the VAT becomes accountable
// for the Seller and for the Buyer.
        string $description_code,
        // OrderReference
        ?string $order_id,
        ?string $sales_order_id,
        ?string $cdr_id,
        AdditionalDocumentReference $additionalDocumentReference,
        // AccountingSupplierParty
        ?string $supplier_name,
        ?string $supplier_partyIdentificationId,
        ?string $supplier_partyIdentificationSchemeId,
        ?Address $supplier_postalAddress,
        ?Contact $supplier_contact,
        ?PartyTaxScheme $supplier_partyTaxScheme,
        ?PartyLegalEntity $supplier_partyLegalEntity,
        ?string $supplier_endpointID,
        string $supplier_endpointID_schemeID,
        // AccountingCustomerParty
        ?string $customer_name,
        ?string $customer_partyIdentificationId,
        ?string $customer_partyIdentificationSchemeId,
        ?Address $customer_postalAddress,
        ?Contact $customer_contact,
        ?PartyTaxScheme $customer_partyTaxScheme,
        ?PartyLegalEntity $customer_partyLegalEntity,
        ?string $customer_endpointID,
        string $customer_endpointID_schemeID,
        // Delivery
        ?DateTime $actualDeliveryDate,
        array $deliveryLocationID_scheme,
        ?Address $deliveryLocation,
        ?Party $deliveryParty,
        // PaymentMeans
        ?PayeeFinancialAccount $payeeFinancialAccount,
        string $paymentId,
        // PaymentTerms
        ?string $payment_terms,
        array $allowanceCharges,
        // TaxTotal
        array $taxAmounts,
        // TaxSubTotal
        array $taxSubtotal,
        // LegalMonetaryTotal
        float $lineExtensionAmount,
        float $taxExclusiveAmount,
        float $taxInclusiveAmount,
        float $allowanceTotalAmount,
        float $payableAmount,
        array $invoiceLines,
        ?bool $isCopyIndicator,
        ?string $supplierAssignedAccountID,
    ): Invoice {
        return new Invoice(
            $this->sR,
            $profileID,
            $id,
            $issueDate,
            $dueDate,
            $note,
            $taxPointDate,
            $accountingCostCode,
            $buyerReference,
            new InvoicePeriod(
                $start_date,
                $end_date,
                $description_code,
            ),
            new OrderReference(
                $order_id,
                $sales_order_id,
            ),
            null!== $cdr_id ? new ContractDocumentReference($cdr_id) : null,
            $additionalDocumentReference,
            // Accounting Supplier Party
            new Party(
                $this->t,
                $supplier_name,
                $supplier_partyIdentificationId,
                $supplier_partyIdentificationSchemeId,
                $supplier_postalAddress,
/**
 * Supplier Physical Location must not be supplied => null
 * Location: invoice_sqKOvgahINV107_peppol
 * Element/context: /:Invoice[1]
 * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:PhysicalLocation)
 * Error message: [UBL-CR-168]-A UBL invoice should not include the
      AccountingSupplierParty Party PhysicalLocation
 */
                null,
                $supplier_contact,
                $supplier_partyTaxScheme,
                $supplier_partyLegalEntity,
                $supplier_endpointID,
                $supplier_endpointID_schemeID,
            ),
            // Accounting Customer Party
            new Party(
                $this->t,
                $customer_name,
                $customer_partyIdentificationId,
                $customer_partyIdentificationSchemeId,
                $customer_postalAddress,
/**
 * Customer Physical Location must not be included => null
 * Warning
 * Location: invoice_sqKOvgahINV107_peppol
 * Element/context: /:Invoice[1]
 * XPath test: not(cac:AccountingCustomerParty/cac:Party/cac:PhysicalLocation)
 * Error message: [UBL-CR-231]-A UBL invoice should not include the
   AccountingCustomerParty Party PhysicalLocation
 */
                null,
                $customer_contact,
                $customer_partyTaxScheme,
                $customer_partyLegalEntity,
/**
 * Error
 * Location: invoice_8x8vShcxINV111_peppol
 * Element/context: /:Invoice[1]/cac:AccountingCustomerParty[1]/cac:Party[1]
 * XPath test: cbc:EndpointID
 * Error message: Buyer electronic address MUST be provided
 */
                $customer_endpointID,
                $customer_endpointID_schemeID,
            ),
            new Delivery(
                $actualDeliveryDate,
                $deliveryLocationID_scheme,
                $deliveryLocation,
                $deliveryParty,
            ),
            new PaymentMeans(
                $payeeFinancialAccount,
                $paymentId,
            ),
            new PaymentTerms($payment_terms),
            $allowanceCharges,
            $taxAmounts,
            $taxSubtotal,
            new LegalMonetaryTotal(
                $lineExtensionAmount,
                $taxExclusiveAmount,
                $taxInclusiveAmount,
                $allowanceTotalAmount,
                $payableAmount,
                $this->sR->getSetting('peppol_document_currency'),
                $this->sR,
            ),
            $invoiceLines,
            $isCopyIndicator,
            $supplierAssignedAccountID,
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
        //$writer->startDocument('1.0', 'UTF-8');
        $writer->text(Generator::invoice($ubl_invoice, $ubl_invoice->getDocumentCurrencyCode()));
        $writer->endDocument();
        return $writer->outputMemory();
    }
}
